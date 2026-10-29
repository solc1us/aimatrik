<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\DaftarPenilaianController;
use App\Http\Controllers\Admin\DomainController;
use App\Http\Controllers\Admin\PenilaianController;
use App\Http\Requests\Admin\DaftarPenilaian\DaftarPenilaianUpdateRequest;
use App\Http\Requests\Admin\Domain\DomainStoreRequest;
use App\Http\Requests\Admin\Domain\DomainUpdateRequest;
use App\Models\BidangKapabilitas;
use App\Models\BidangPraktik;
use App\Models\DaftarPenilaian;
use App\Models\Domain;
use App\Models\Praktik;
use App\Models\QuestionAnswerPraktik;
use App\Models\User;
use App\Services\Asset\AssetCommandService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class TargetedBugFixesTest extends TestCase
{
    private User $owner;

    private DaftarPenilaian $assessment;

    private QuestionAnswerPraktik $practice;

    private PenilaianController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        // Never migrate or write to a connection from the user's environment.
        config([
            'database.default' => 'phase2_testing',
            'database.connections.phase2_testing' => [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
                'foreign_key_constraints' => true,
            ],
            'filesystems.default' => 'phase2_testing',
        ]);
        DB::purge('phase2_testing');
        $this->assertSame('sqlite', DB::connection()->getDriverName());
        $this->assertSame(':memory:', DB::connection()->getDatabaseName());
        foreach (glob(database_path('migrations/*.php')) as $migration) {
            // Telemetry migrations may select a separate, environment-configured connection.
            if (! str_contains($migration, 'pulse') && ! str_contains($migration, 'telescope')) {
                (require $migration)->up();
            }
        }
        Storage::fake('phase2_testing');
        $this->app['view']->share('errors', new ViewErrorBag);

        $this->owner = User::factory()->create(['username' => 'OWNER', 'is_super_admin' => true]);
        $this->assessment = $this->assessmentFor($this->owner);
        $this->practice = $this->practiceFor($this->assessment);
        $this->actingAs($this->owner);
        $this->controller = app(PenilaianController::class);
    }

    public function test_foreign_assessment_children_cannot_be_used_for_answers_targets_or_files(): void
    {
        $otherUser = User::factory()->create(['username' => 'OTHER']);
        foreach ([$otherUser, $this->owner] as $user) {
            $foreign = $this->practiceFor($this->assessmentFor($user));
            $field = $foreign->questionAnswerBidPraktik;
            $asset = $this->evidenceFor($foreign);

            $this->assertRejected($this->controller->storeLevelTarget(
                new Request(['level' => $field->id, 'level_target' => 4]), $this->assessment->id
            ));
            $this->assertNull($field->fresh()->level_target);

            $request = $this->answerRequest($foreign);
            $request->files->set('file_pendukung', [$field->id => [1 => [
                $foreign->id => UploadedFile::fake()->create('replacement.pdf', 1, 'application/pdf'),
            ]]]);
            $this->assertRejected($this->controller->storePenilaian($request, $this->assessment->id));
            $this->assertNull($foreign->fresh()->answer);

            $this->assertRejected($this->controller->loadImagePenilaian(
                new Request(['idTarget' => $foreign->id]), $this->assessment->id
            ));
            $this->assertRejected($this->controller->deleteImagePenilaian(
                new Request(['idTarget' => $foreign->id]), $this->assessment->id
            ));
            $this->assertDatabaseHas('assets', ['id' => $asset->id]);
            Storage::assertExists('public/'.$asset->asset_path);
            $this->assertSame('test evidence', Storage::get('public/'.$asset->asset_path));
        }
    }

    public function test_practice_must_belong_to_the_submitted_field_even_in_the_same_assessment(): void
    {
        $sibling = $this->practiceFor($this->assessment);
        $request = $this->answerRequest($sibling, $this->practice->question_answer_bid_praktik_id);
        $this->assertRejected($this->controller->storePenilaian($request, $this->assessment->id));
        $this->assertNull($sibling->fresh()->answer);
        $this->assertNull($this->practice->questionAnswerBidPraktik->fresh()->level_target);
    }

    public function test_existing_parent_ownership_check_rejects_another_users_assessment(): void
    {
        $other = User::factory()->create(['username' => 'OTHER']);
        $foreignAssessment = $this->assessmentFor($other);
        $foreign = $this->practiceFor($foreignAssessment);
        $this->assertRejected($this->controller->storePenilaian($this->answerRequest($foreign), $foreignAssessment->id));
        $this->assertRejected($this->controller->storeLevelTarget(
            new Request(['level' => $foreign->question_answer_bid_praktik_id, 'level_target' => 4]), $foreignAssessment->id
        ));
        $this->assertRejected($this->controller->loadImagePenilaian(new Request(['idTarget' => $foreign->id]), $foreignAssessment->id));
        $this->assertRejected($this->controller->deleteImagePenilaian(new Request(['idTarget' => $foreign->id]), $foreignAssessment->id));
        $this->assertRejected($this->controller->setStatusPenilaian($foreignAssessment->id));
        $this->assertNull($foreign->fresh()->answer);
        $this->assertFalse((bool) $foreignAssessment->fresh()->is_publish);
    }

    public function test_published_answers_targets_and_evidence_are_read_only(): void
    {
        $this->assessment->update(['is_publish' => true]);
        $asset = $this->evidenceFor($this->practice);
        $file = UploadedFile::fake()->create('replacement.pdf', 1, 'application/pdf');
        $request = $this->answerRequest($this->practice);
        $fieldId = $this->practice->question_answer_bid_praktik_id;
        $request->files->set('file_pendukung', [$fieldId => [1 => [$this->practice->id => $file]]]);

        $this->assertRejected($this->controller->storePenilaian($request, $this->assessment->id));
        $this->assertRejected($this->controller->storeLevelTarget(
            new Request(['level' => $fieldId, 'level_target' => 4]), $this->assessment->id
        ));
        $this->assertRejected($this->controller->deleteImagePenilaian(
            new Request(['idTarget' => $this->practice->id]), $this->assessment->id
        ));
        $this->assertNull($this->practice->fresh()->answer);
        $this->assertNull($this->practice->questionAnswerBidPraktik->fresh()->level_target);
        $this->assertDatabaseHas('assets', ['id' => $asset->id]);
        Storage::assertExists('public/'.$asset->asset_path);
        $this->assertCount(1, Storage::allFiles());
        $this->assertSame(200, $this->controller->loadImagePenilaian(
            new Request(['idTarget' => $this->practice->id]), $this->assessment->id
        )->getStatusCode());
    }

    public function test_published_metadata_status_and_deletion_are_blocked_but_unpublish_works(): void
    {
        $this->assessment->update(['is_publish' => true]);
        $controller = app(DaftarPenilaianController::class);
        $this->assertRejected($controller->update(
            new DaftarPenilaianUpdateRequest(['name' => 'Changed', 'description' => 'Changed']), $this->assessment->id
        ));
        $this->assertRejected($controller->setStatus(new Request, $this->assessment->id));
        $this->assertRejected($controller->delete(new Request, $this->assessment->id));
        $this->assertSame('Test assessment', $this->assessment->fresh()->name);
        $this->assertFalse((bool) $this->assessment->fresh()->is_active);
        $this->assertNotSoftDeleted($this->assessment);

        $this->controller->setStatusPenilaian($this->assessment->id);
        $this->assertFalse((bool) $this->assessment->fresh()->is_publish);
        $controller->update(new DaftarPenilaianUpdateRequest(['name' => 'Changed', 'description' => 'Changed']), $this->assessment->id);
        $this->assertSame('Changed', $this->assessment->fresh()->name);
    }

    public function test_owned_draft_answers_targets_and_evidence_still_work(): void
    {
        $request = $this->answerRequest($this->practice);
        $fieldId = $this->practice->question_answer_bid_praktik_id;
        $request->files->set('file_pendukung', [$fieldId => [1 => [
            $this->practice->id => UploadedFile::fake()->create('evidence.pdf', 1, 'application/pdf'),
        ]]]);
        $response = $this->controller->storePenilaian($request, $this->assessment->id);
        $this->assertNotEmpty($response->getSession()->get('success'));
        $this->assertSame('F', $this->practice->fresh()->answer);
        $this->assertSame(3, (int) $this->practice->questionAnswerBidPraktik->fresh()->level_target);
        $this->assertSame(1, (int) $this->assessment->fresh()->count_domain_answered);

        $response = $this->controller->storeLevelTarget(new Request(['level' => $fieldId, 'level_target' => 4]), $this->assessment->id);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(4, (int) $this->practice->questionAnswerBidPraktik->fresh()->level_target);
        $response = $this->controller->loadImagePenilaian(new Request(['idTarget' => $this->practice->id]), $this->assessment->id);
        $this->assertCount(1, $response->getData()->data);
        $assetPath = ltrim(parse_url($response->getData()->data[0]->asset_path, PHP_URL_PATH), '/');
        Storage::assertExists(str_replace('storage/', 'public/', $assetPath));
        $response = $this->controller->deleteImagePenilaian(new Request(['idTarget' => $this->practice->id]), $this->assessment->id);
        $this->assertSame(200, $response->getStatusCode());
        $this->assertDatabaseCount('assets', 0);
        $this->assertSame([], Storage::allFiles());
    }

    public function test_empty_assessment_renders_existing_empty_state_and_domain_navigation_still_works(): void
    {
        $empty = $this->assessmentFor($this->owner);
        $view = $this->controller->index(new Request, $empty->id);
        $this->assertInstanceOf(View::class, $view);
        $this->assertStringContainsString('Belum ada data yang tersedia', $view->render());

        $view = $this->controller->index(new Request, $this->assessment->id);
        $this->assertInstanceOf(View::class, $view);
        $this->assertCount(1, $view->getData()['getAllDomainQuestion']);
        $this->assertStringContainsString('Test domain', $view->render());
    }

    public function test_domain_code_skips_collisions_after_deletion_and_preserves_format(): void
    {
        // Keep the fixture and D03 so count + 1 suggests the existing D03.
        $first = Domain::create(['code' => 'D01', 'name' => 'First']);
        $deleted = Domain::create(['code' => 'D02', 'name' => 'Deleted']);
        Domain::create(['code' => 'D03', 'name' => 'Third']);
        $deleted->delete();
        $first->delete();
        $controller = app(DomainController::class);
        $this->assertSame('D04', $controller->index(new Request)->getData()['nextCode']);
        for ($i = 4; $i <= 9; $i++) {
            Domain::create(['code' => 'D0'.$i, 'name' => 'Domain '.$i]);
        }
        Domain::create(['code' => 'D01', 'name' => 'First']);
        $this->assertSame('D010', $controller->index(new Request)->getData()['nextCode']);
    }

    public function test_domain_crud_and_existing_unique_validation_still_work(): void
    {
        $controller = app(DomainController::class);
        $request = new DomainStoreRequest(['code' => 'D01', 'name' => 'New domain']);
        $this->assertFalse(Validator::make($request->all(), $request->rules())->fails());
        $controller->store($request);
        $domain = Domain::where('code', 'D01')->firstOrFail();
        $this->assertTrue(Validator::make($request->all(), $request->rules())->fails());
        $update = new DomainUpdateRequest(['id' => $domain->id, 'code' => 'D01', 'name' => 'Updated domain']);
        $this->assertFalse(Validator::make($update->all(), $update->rules())->fails());
        $controller->update($update, $domain->id);
        $this->assertSame('Updated domain', $domain->fresh()->name);
        $controller->delete(new Request, $domain->id);
        $this->assertDatabaseMissing('domains', ['id' => $domain->id]);
    }

    public function test_login_with_existing_credentials_still_works(): void
    {
        Auth::logout();
        $this->assertTrue(Auth::attempt(['email' => $this->owner->email, 'password' => 'password']));
        $this->assertSame($this->owner->id, Auth::id());
    }

    private function assessmentFor(User $user): DaftarPenilaian
    {
        return $user->daftarPenilaian()->create([
            'name' => 'Test assessment', 'description' => 'Test', 'is_active' => false,
        ]);
    }

    private function practiceFor(DaftarPenilaian $assessment): QuestionAnswerPraktik
    {
        $domain = Domain::create(['code' => 'TEST-'.Str::ulid(), 'name' => 'Test domain']);
        $capability = BidangKapabilitas::create(['domain_id' => $domain->id, 'code' => 'TEST-'.Str::ulid(), 'name' => 'Test capability']);
        $field = BidangPraktik::create(['bidang_kapabilitas_id' => $capability->id, 'code' => 'TEST-'.Str::ulid(), 'name' => 'Test field']);
        $practice = Praktik::create(['bidang_praktik_id' => $field->id, 'praktik' => 'Test practice', 'kategori_level' => 1]);
        $question = $assessment->daftarPenilaianQuestion()->create(['domain_id' => $domain->id]);
        $answerCapability = $question->questionAnswerBidKapabilitas()->create(['bidang_kapabilitas_id' => $capability->id]);
        $answerField = $answerCapability->questionAnswerBidPraktik()->create(['bidang_praktik_id' => $field->id]);

        return $answerField->questionAnswerPraktik()->create(['praktik_id' => $practice->id]);
    }

    private function answerRequest(QuestionAnswerPraktik $practice, ?string $fieldId = null): Request
    {
        $fieldId ??= $practice->question_answer_bid_praktik_id;

        return new Request([
            'pelaksanaan' => [$fieldId => [1 => [$practice->id => 'F']]],
            'level_target' => [$fieldId => 3],
        ]);
    }

    private function evidenceFor(QuestionAnswerPraktik $practice): \App\Models\Asset
    {
        $path = 'penilaian/'.$practice->id.'/evidence.pdf';
        Storage::put('public/'.$path, 'test evidence');

        return app(AssetCommandService::class)->storeAsset(
            QuestionAnswerPraktik::class, $practice->id, $path, 'penilaian-file-pendukung-'.Str::ulid()
        );
    }

    private function assertRejected(JsonResponse|RedirectResponse $response): void
    {
        if ($response instanceof JsonResponse) {
            $this->assertNotSame(200, $response->getStatusCode());
            $this->assertNotEmpty($response->getData()->meta->error);
        } else {
            $this->assertTrue($response->getSession()->has('error'));
        }
    }
}
