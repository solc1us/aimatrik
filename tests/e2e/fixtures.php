<?php

// Manual setup/migrate/cleanup; Playwright uses prepare/inspect after server attestation.
use App\Models\{Asset, BidangKapabilitas, BidangPraktik, DaftarPenilaian, DaftarPenilaianQuestion, Domain, Praktik, QuestionAnswerBidPraktik, QuestionAnswerPraktik, User};
use App\Services\DaftarPenilaian\DaftarPenilaianCommandService;
use Illuminate\Support\Facades\{DB, Hash, Storage};

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
try {
    extract(require __DIR__.'/bootstrap.php');
    $action = $argv[1] ?? 'check';
    if ($action === 'migrate') {
        // Explicit command only. Never fresh/reset, never seed application defaults.
        $exit = $app->make(Illuminate\Contracts\Console\Kernel::class)->call('migrate', ['--force' => true]);
        if ($exit !== 0) {
            throw new RuntimeException('Migration failed.');
        }
        echo json_encode(['migrated' => true]);
        exit;
    }
    if ($action === 'setup') {
        if (is_file($manifestPath) || User::query()->exists() || Domain::query()->exists() || DaftarPenilaian::withTrashed()->exists()) {
            throw new RuntimeException('Setup requires a clean dedicated E2E database, not a database copy.');
        }
        $run = bin2hex(random_bytes(8));
        $prefix = 'E2E-'.$run;
        $manifest = DB::transaction(function () use ($run, $prefix) {
            $users = [];
            foreach (['super' => 'SUPER', 'a' => 'ADMIN_A', 'b' => 'ADMIN_B'] as $role => $env) {
                $email = Illuminate\Support\Env::get('PW_'.$env.'_EMAIL');
                $password = Illuminate\Support\Env::get('PW_'.$env.'_PASSWORD');
                if (! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL) || ! str_ends_with($email, '@aimatrik-e2e.invalid')
                    || ! is_string($password) || strlen($password) < 12) {
                    throw new RuntimeException('Use distinct E2E-only emails and environment passwords of at least 12 characters.');
                }
                $users[$role] = User::create([
                    'username' => $prefix.'-'.$role, 'name' => $prefix.' '.$role, 'email' => $email,
                    'password' => Hash::make($password), 'email_verified_at' => now(),
                    'is_active' => true, 'is_super_admin' => $role === 'super',
                ])->id;
            }
            $domain = Domain::create(['code' => $prefix.'-BASE', 'name' => $prefix.' Domain', 'is_active' => true]);
            $capability = BidangKapabilitas::create(['domain_id' => $domain->id, 'code' => $prefix.'-CAP', 'name' => $prefix.' Kapabilitas', 'is_active' => true]);
            $field = BidangPraktik::create(['bidang_kapabilitas_id' => $capability->id, 'code' => $prefix.'-FIELD', 'name' => $prefix.' Bidang Praktik', 'is_active' => true]);
            $practices = [];
            foreach ([1, 2] as $number) {
                $practices[] = Praktik::create(['bidang_praktik_id' => $field->id, 'praktik' => $prefix.' Praktik '.$number, 'kategori_level' => 0, 'is_active' => true])->id;
            }

            return ['run' => $run, 'prefix' => $prefix, 'database' => 'aimatrik_e2e', 'users' => $users,
                'domain' => $domain->id, 'capability' => $capability->id, 'field' => $field->id, 'practices' => $practices];
        });
        // Small valid blank-page PDF, generated solely for this fixture.
        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach (['<< /Type /Catalog /Pages 2 0 R >>', '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 100 100] /Resources << >> /Contents 4 0 R >>', "<< /Length 0 >>\nstream\nendstream"] as $index => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($index + 1)." 0 obj\n".$object."\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 5\n0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer\n<< /Size 5 /Root 1 0 R >>\nstartxref\n$xref\n%%EOF\n";
        file_put_contents($runtime.'/dummy.pdf', $pdf);
        file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR));
    } else {
        $manifest = json_decode(file_get_contents($manifestPath), true, flags: JSON_THROW_ON_ERROR);
    }
    if ($manifest['database'] !== 'aimatrik_e2e' || ! preg_match('/^E2E-[a-f0-9]{16}$/', $manifest['prefix'])) {
        throw new RuntimeException('Invalid E2E manifest.');
    }
    foreach ($manifest['users'] as $role => $id) {
        if (! User::whereKey($id)->where('username', $manifest['prefix'].'-'.$role)->exists()) {
            throw new RuntimeException('E2E user identity mismatch.');
        }
    }
    $prefix = $manifest['prefix'];
    $owned = fn () => DaftarPenilaian::withTrashed()->whereIn('user_id', $manifest['users'])->where('name', 'like', $prefix.' %');
    $dropAssessment = function (DaftarPenilaian $assessment): void {
        $ids = DB::table('question_answer_praktiks as p')
            ->join('question_answer_bid_praktiks as f', 'f.id', '=', 'p.question_answer_bid_praktik_id')
            ->join('question_answer_bid_kapabilitas as c', 'c.id', '=', 'f.question_answer_bid_kapabilitas_id')
            ->join('daftar_penilaian_questions as q', 'q.id', '=', 'c.daftar_penilaian_question_id')
            ->where('q.daftar_penilaian_id', $assessment->id)->pluck('p.id');
        $assets = Asset::where('asset_type', QuestionAnswerPraktik::class)->whereIn('asset_id', $ids);
        foreach ((clone $assets)->get() as $asset) {
            if (! str_starts_with($asset->asset_path, 'penilaian/'.$asset->asset_id.'/')) {
                throw new RuntimeException('Unexpected fixture asset path.');
            }
            Storage::delete('public/'.$asset->asset_path);
        }
        $assets->delete();
        $assessment->forceDelete();
    };
    if ($action === 'cleanup') {
        if (DaftarPenilaian::withTrashed()->whereIn('user_id', $manifest['users'])->where('name', 'not like', $prefix.' %')->exists()) {
            throw new RuntimeException('Cleanup refused: non-fixture data references fixture accounts.');
        }
        DB::transaction(function () use ($owned, $dropAssessment, $prefix, $manifest) {
            foreach ($owned()->get() as $assessment) {
                $dropAssessment($assessment);
            }
            foreach (Domain::where('code', 'like', $prefix.'-%')->get() as $domain) {
                if (DaftarPenilaianQuestion::withTrashed()->where('domain_id', $domain->id)->exists()) {
                    throw new RuntimeException('Cleanup refused: domain is still referenced.');
                }
                $domain->delete();
            }
            User::whereIn('id', $manifest['users'])->where('username', 'like', $prefix.'-%')->delete();
        });
        unlink($manifestPath);
        unlink($runtime.'/dummy.pdf');
        echo json_encode(['cleaned' => true]);
        exit;
    }
    if (in_array($action, ['prepare', 'inspect'], true)) {
        $case = $argv[2] ?? '';
        if (! in_array($case, ['BB-07', 'BB-08', 'BB-09', 'BB-10', 'BB-11', 'BB-12', 'BB-13', 'BB-14', 'BB-16'], true)) {
            throw new RuntimeException('Unknown E2E case.');
        }
        $name = $prefix.' '.$case;
        if ($action === 'prepare') {
            DB::transaction(function () use ($owned, $dropAssessment, $name, $case, $manifest, $prefix) {
                foreach ($owned()->where('name', 'like', $name.' %')->get() as $assessment) {
                    $dropAssessment($assessment);
                }
                if (in_array($case, ['BB-08', 'BB-09'], true)) {
                    $domain = Domain::where('code', $prefix.'-'.$case)->first();
                    if ($domain) {
                        if (DaftarPenilaianQuestion::withTrashed()->where('domain_id', $domain->id)->exists()) {
                            throw new RuntimeException('Cannot replace referenced fixture domain.');
                        }
                        $domain->delete();
                    }
                    if ($case === 'BB-09') {
                        Domain::create(['code' => $prefix.'-'.$case, 'name' => $name.' Domain', 'is_active' => false]);
                    }
                }
                if (in_array($case, ['BB-11', 'BB-12', 'BB-13', 'BB-14', 'BB-16'], true)) {
                    foreach ($case === 'BB-14' ? ['a', 'b'] : ['a'] as $role) {
                        $assessment = DaftarPenilaian::create(['user_id' => $manifest['users'][$role], 'name' => $name.' '.strtoupper($role), 'description' => $name, 'is_active' => true]);
                        if ($case !== 'BB-16') {
                            app(DaftarPenilaianCommandService::class)->storeDaftarPenilaianQuestion($assessment, Domain::whereKey($manifest['domain'])->get());
                            if ($case !== 'BB-11') {
                                $fields = QuestionAnswerBidPraktik::whereHas('questionAnswerBidKapabilitas.daftarPenilaianQuestion', fn ($q) => $q->where('daftar_penilaian_id', $assessment->id))->get();
                                foreach ($fields as $field) {
                                    $field->update(['level_target' => 0]);
                                    $field->questionAnswerPraktik()->update(['answer' => 'F']);
                                    if ($role === 'b') {
                                        $practice = $field->questionAnswerPraktik()->firstOrFail();
                                        $path = 'penilaian/'.$practice->id.'/sentinel.pdf';
                                        Storage::put('public/'.$path, file_get_contents(dirname(__DIR__, 2).'/node_modules/.cache/aimatrik-e2e/dummy.pdf'));
                                        app(App\Services\Asset\AssetCommandService::class)->storeAsset(QuestionAnswerPraktik::class, $practice->id, $path, 'penilaian-file-pendukung-'.$practice->id);
                                    }
                                }
                            }
                        }
                    }
                }
            });
        }
        $manifest['case'] = $case;
        $manifest['name'] = $name;
        $manifest['dummyDomain'] = Domain::where('code', $prefix.'-'.$case)->value('id');
        foreach (['a', 'b'] as $role) {
            $assessment = $owned()->where('name', $name.' '.strtoupper($role))->where('user_id', $manifest['users'][$role])->first();
            $fields = $assessment ? QuestionAnswerBidPraktik::whereHas('questionAnswerBidKapabilitas.daftarPenilaianQuestion', fn ($q) => $q->where('daftar_penilaian_id', $assessment->id))->get() : collect();
            $answers = QuestionAnswerPraktik::whereIn('question_answer_bid_praktik_id', $fields->pluck('id'))->orderBy('id')->get();
            $assets = Asset::where('asset_type', QuestionAnswerPraktik::class)->whereIn('asset_id', $answers->pluck('id'))->orderBy('id')->get()->map(fn ($a) => [
                'id' => $a->id, 'path' => $a->asset_path, 'hash' => hash('sha256', Storage::get('public/'.$a->asset_path)),
            ]);
            $manifest[$role] = ['assessment' => $assessment?->only(['id', 'name', 'description', 'is_active', 'is_publish', 'count_domain_question', 'count_domain_answered']),
                'fields' => $fields->map->only(['id', 'level_target'])->all(),
                'answers' => $answers->map->only(['id', 'answer', 'description'])->all(), 'assets' => $assets->all(),
                'files' => $answers->flatMap(fn ($answer) => Storage::allFiles('public/penilaian/'.$answer->id))->sort()->values()->all()];
        }
    } elseif (! in_array($action, ['check', 'setup'], true)) {
        throw new RuntimeException('Unknown fixture command.');
    }
    echo json_encode($manifest, JSON_THROW_ON_ERROR);
} catch (Throwable $exception) {
    // Do not emit SQL/configuration exceptions or environment credential values.
    fwrite(STDERR, "E2E fixture refused/failed. Verify .env.testing, dedicated PostgreSQL database, fixture manifest and operation prerequisites. No reset is performed.\n");
    exit(1);
}
