<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\Json;
use App\Http\Controllers\Controller;
use App\Models\QuestionAnswerPraktik;
use App\Services\Asset\AssetCommandService;
use App\Services\Asset\AssetQueryService;
use App\Services\DaftarPenilaian\DaftarPenilaianCommandService;
use App\Services\DaftarPenilaian\DaftarPenilaianQueryService;
use App\Services\Penilaian\PenilaianCommandService;
use App\Services\Penilaian\PenilaianQueryService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Uid\Ulid;
use Throwable;

final class PenilaianController extends Controller
{
    // Constructor
    public function __construct(
        private readonly DaftarPenilaianQueryService $daftarPenilaianQueryService,
        private readonly DaftarPenilaianCommandService $daftarPenilaianCommandService,
        private readonly PenilaianQueryService $penilaianQueryService,
        private readonly PenilaianCommandService $penilaianCommandService,
        private readonly AssetCommandService $assetCommandService,
        private readonly AssetQueryService $assetQueryService
    ) {}

    public function index(Request $request, string $id_daftar_penilaian): View|RedirectResponse
    {
        // Transactional
        try {
            DB::beginTransaction();

            // Get daftar penilaian
            $findDaftarPenilaian = $this->daftarPenilaianQueryService->findDaftarPenilaianById(id: $id_daftar_penilaian);

            // Cek apakah daftar penilaian ada dan user yang membuat daftar penilaian adalah user yang sedang login
            if (! isset($findDaftarPenilaian) || $findDaftarPenilaian->user_id != Auth::user()->id) {
                throw new Exception(trans('response.error.view', ['data' => 'Daftar Penilaian', 'error' => 'data tidak ditemukan']));
            }

            // Mendapatkan seluruh data pertanyaan (untuk kebutuhan tab)
            $getAllDomainQuestion = $this->penilaianQueryService->getAllQuestion(daftar_penilaian_id: $id_daftar_penilaian);

            if ($getAllDomainQuestion->isEmpty()) {
                DB::commit();

                return view('admin.pages.penilaian.index', [
                    'findDaftarPenilaian' => $findDaftarPenilaian,
                    'getAllDomainQuestion' => $getAllDomainQuestion,
                    'getAllQuestion' => $getAllDomainQuestion,
                    'getQuestionById' => null,
                    'getBidangKapabilitasById' => null,
                ]);
            }

            // Mengecek apakah ada request page ada, sekaligus mendefinisikan page yang akan diakses oleh user
            if (isset($request->page)) {
                $getQuestionById = $this->penilaianQueryService->getQuestionById(daftar_penilaian_id: $id_daftar_penilaian, id: $request->page);
            } else {
                $getQuestionById = $this->penilaianQueryService->getQuestionById(daftar_penilaian_id: $id_daftar_penilaian);
            }

            // Cek apakah ada atau tidak
            if (! isset($getQuestionById)) {
                throw new Exception(trans('response.error.view', ['data' => 'Domain', 'error' => 'data tidak ditemukan']));
            }

            // Mengecek apakah ada request sub_page, sekaligus mendefinisikan sub_page yang akan diakses oleh user
            if (isset($request->sub_page)) {
                $getBidangKapabilitasById = $this->penilaianQueryService->getQuestionBidangKapabilitasById(daftarPeniaianId: $getQuestionById->id, id: $request->sub_page);
            } else {
                $getBidangKapabilitasById = $this->penilaianQueryService->getQuestionBidangKapabilitasById(daftarPeniaianId: $getQuestionById->id);
            }

            // Cek apakah ada atau tidak
            if (! isset($getBidangKapabilitasById)) {
                throw new Exception(trans('response.error.view', ['data' => 'Bidang Kapabilitas', 'error' => 'data tidak ditemukan']));
            }

            // Mendapatkan seluruh pertanyaan berdasarkan halaman yang didefinisikan/diakses
            $getAllQuestion = $this->penilaianQueryService->getAllQuestion(daftar_penilaian_id: $id_daftar_penilaian, id_domain: $getQuestionById->domain_id, id_bidang_kapabilitas: $getBidangKapabilitasById->bidang_kapabilitas_id);

            DB::commit();

            // Return view
            return view('admin.pages.penilaian.index', compact(
                'getBidangKapabilitasById',
                'getAllQuestion',
                'getAllDomainQuestion',
                'findDaftarPenilaian',
                'getQuestionById'
            ));
        } catch (Throwable $th) {
            DB::rollBack();

            return redirect()->back()->with('error', $th->getMessage());
        }
    }

    public function setStatusPenilaian(string $id_daftar_penilaian): RedirectResponse
    {
        // Transactional
        try {
            DB::beginTransaction();
            // Cari daftar penilaian
            $findDaftarPenilaian = $this->daftarPenilaianQueryService->findDaftarPenilaianById(id: $id_daftar_penilaian);

            // Cek apakah daftar penilaian ada dan user yang membuat daftar penilaian adalah user yang sedang login
            if (! isset($findDaftarPenilaian) || $findDaftarPenilaian->user_id != Auth::user()->id) {
                abort(Response::HTTP_NOT_FOUND);
            }

            // Ubah status daftar penilaian
            $this->daftarPenilaianCommandService->setStatusDaftarPenilaian(daftarPenilaian: $findDaftarPenilaian);
            DB::commit();

            return redirect()->back()->with('success', 'Bidang Praktik Berhasil Diubah Statusnya');
        } catch (Throwable $th) {
            DB::rollBack();

            return redirect()->back()->with('error', $th->getMessage());
        }
    }

    public function loadImagePenilaian(Request $request, string $id_daftar_penilaian): JsonResponse
    {
        // Transactional
        try {
            DB::beginTransaction();
            // Cari daftar penilaian
            $findDaftarPenilaian = $this->daftarPenilaianQueryService->findDaftarPenilaianById(id: $id_daftar_penilaian);

            // Cek apakah daftar penilaian ada dan user yang membuat daftar penilaian adalah user yang sedang login
            if (! isset($findDaftarPenilaian) || $findDaftarPenilaian->user_id != Auth::user()->id) {
                throw new Exception('Daftar Penilaian tidak ditemukan');
            }

            // Cari praktik
            $findPraktik = $this->penilaianQueryService->findPraktikById(id: $request->idTarget, daftarPenilaianId: $findDaftarPenilaian->id);

            // Cek apakah praktik ada
            if (! isset($findPraktik)) {
                throw new Exception('Praktik tidak ditemukan');
            }

            // Mendapatkan semua asset
            $assetKeyPrefix = 'penilaian-file-pendukung-';
            $assets = $this->assetQueryService->getAllAsset(pathType: QuestionAnswerPraktik::class, pathId: $findPraktik->id, withAssetKey: $assetKeyPrefix, usingLike: true);

            // Mapping
            $data = $assets->map(function ($item) {
                $item->asset_path = asset('storage/'.$item->asset_path);

                return $item;
            });

            DB::commit();

            // Return json
            return Json::success(data: $data);
        } catch (Throwable $th) {
            DB::rollBack();

            return Json::error(error: $th->getMessage());
        }
    }

    public function storeLevelTarget(Request $request, string $id_daftar_penilaian): JsonResponse
    {
        // Transactional
        try {
            DB::beginTransaction();
            // Cari daftar penilaian
            $findDaftarPenilaian = $this->daftarPenilaianQueryService->findDaftarPenilaianById(id: $id_daftar_penilaian);

            // Cek apakah daftar penilaian ada dan user yang membuat daftar penilaian adalah user yang sedang login
            if (! isset($findDaftarPenilaian) || $findDaftarPenilaian->user_id != Auth::user()->id) {
                throw new Exception('Daftar Penilaian tidak ditemukan');
            }

            if ($findDaftarPenilaian->is_publish) {
                throw new Exception('Data Penilaian sudah dipublish, silahkan unpublish terlebih dahulu untuk melakukan perubahan');
            }

            // Cari bidang praktik
            $findBidangPraktik = $this->penilaianQueryService->findBidangPraktikById(id: $request->level, daftarPenilaianId: $findDaftarPenilaian->id);

            // Cek apakah bidang praktik ada
            if (! isset($findBidangPraktik)) {
                throw new Exception('Bidang Praktik tidak ditemukan');
            }

            // Update level bidang praktik
            $this->penilaianCommandService->storePenilaianBidangPraktik(questionAnswerBidPraktik: $findBidangPraktik, levelTarget: $request->level_target);

            DB::commit();

            // Return json
            return Json::success(data: $findBidangPraktik);
        } catch (Throwable $th) {
            DB::rollBack();

            return Json::error(error: $th->getMessage());
        }
    }

    public function deleteImagePenilaian(Request $request, string $id_daftar_penilaian): JsonResponse
    {
        // Transactional
        try {
            DB::beginTransaction();
            // Cari daftar penilaian
            $findDaftarPenilaian = $this->daftarPenilaianQueryService->findDaftarPenilaianById(id: $id_daftar_penilaian);

            // Cek apakah daftar penilaian ada dan user yang membuat daftar penilaian adalah user yang sedang login
            if (! isset($findDaftarPenilaian) || $findDaftarPenilaian->user_id != Auth::user()->id) {
                throw new Exception('Daftar Penilaian tidak ditemukan');
            }

            if ($findDaftarPenilaian->is_publish) {
                throw new Exception('Data Penilaian sudah dipublish, silahkan unpublish terlebih dahulu untuk melakukan perubahan');
            }

            // Cari praktik
            $findPraktik = $this->penilaianQueryService->findPraktikById(id: $request->idTarget, daftarPenilaianId: $findDaftarPenilaian->id);

            // Cek apakah praktik ada
            if (! isset($findPraktik)) {
                throw new Exception('Praktik tidak ditemukan');
            }

            // Mendapatkan semua asset
            $assetKeyPrefix = 'penilaian-file-pendukung-';
            $assets = $this->assetQueryService->getAllAsset(pathType: QuestionAnswerPraktik::class, pathId: $findPraktik->id, withAssetKey: $assetKeyPrefix, usingLike: true);

            // Hapus semua asset fisik
            foreach ($assets as $asset) {
                Storage::delete('public/'.$asset->asset_path);
            }

            // Hapus semua asset database
            $delete = $this->assetCommandService->deleteAllAsset(pathType: QuestionAnswerPraktik::class, pathId: $findPraktik->id, assetKey: $assetKeyPrefix, usingLike: true);

            DB::commit();

            // Return json
            return Json::success(data: $delete);
        } catch (Throwable $th) {
            DB::rollBack();

            return Json::error(error: $th->getMessage());
        }
    }

    public function storePenilaian(Request $request, string $id_daftar_penilaian): RedirectResponse
    {
        // Transactional
        try {
            DB::beginTransaction();
            // Cari daftar penilaian
            $findDaftarPenilaian = $this->daftarPenilaianQueryService->findDaftarPenilaianById(id: $id_daftar_penilaian);

            // Cek apakah daftar penilaian ada dan user yang membuat daftar penilaian adalah user yang sedang login
            if (! isset($findDaftarPenilaian) || $findDaftarPenilaian->user_id != Auth::user()->id) {
                throw new Exception('Daftar Penilaian tidak ditemukan');
            }

            if ($findDaftarPenilaian->is_publish) {
                throw new Exception('Data Penilaian sudah dipublish, silahkan unpublish terlebih dahulu untuk melakukan perubahan');
            }

            // Cek apakah ada data request pelaksanaan atau request pelaksanaan ada yang kosong
            if (! isset($request->pelaksanaan) || empty(array_filter($request->pelaksanaan, fn ($group) => array_filter($group)))) {
                throw new Exception('Mohon isi setidaknya satu data pelaksanaan');
            }

            // Melakukan foreach data pelaksanaan
            foreach ($request->pelaksanaan as $indexBidangPraktik => $bidangPraktik) {
                // Cari bidang praktik berdasarkan index dari data pelaksanaan
                $findBidangPraktik = $this->penilaianQueryService->findBidangPraktikById(id: $indexBidangPraktik, daftarPenilaianId: $findDaftarPenilaian->id);

                // Cek apakah ada atau tidak
                if (! isset($findBidangPraktik)) {
                    throw new Exception('Bidang Praktik tidak ditemukan');
                }

                // Looping bidang praktik yang telah didapatkan
                $loopIndex = 0;
                foreach ($bidangPraktik as $indexData => $valueData) {

                    // Cek apakah data level target ada
                    $nilaiLevelTarget = $request->level_target[$indexBidangPraktik] ?? null;

                    // Jika kosong
                    if ($nilaiLevelTarget === null || $nilaiLevelTarget === '') {
                        throw new Exception('Level target wajib diisi');
                    }

                    // Cek apakah ada data jawaban yang kosong untuk setiap level
                    if (in_array(null, $valueData)) {
                        if (count(array_filter($valueData, fn ($v) => is_null($v))) === count($valueData) && $loopIndex === 0) {
                            throw new Exception('Seluruh Praktik dalam suatu level wajib diisi');
                        }

                        if (count(array_filter($valueData, fn ($v) => is_null($v))) !== count($valueData)) {
                            throw new Exception('Seluruh Praktik dalam suatu level wajib diisi');
                        }
                    }

                    // Looping data jawaban
                    foreach ($valueData as $indexItemData => $valueItemData) {
                        // Cari bidang praktik
                        $findPraktik = $findBidangPraktik->questionAnswerPraktik()->whereKey($indexItemData)->first();

                        // Cek apakah ada atau tidak
                        if (! isset($findPraktik)) {
                            throw new Exception('Praktik tidak ditemukan');
                        }

                        // Store Catatan
                        $catatan = $request->catatan[$indexBidangPraktik][$indexData][$indexItemData] ?? null;

                        // Store Penilaian
                        $penilaian = $this->penilaianCommandService->storePenilaianAnswerPraktik(questionAnswerPraktik: $findPraktik, value: $valueItemData, catatan: $catatan);

                        // File Pendukung
                        $filePendukung = $request->file_pendukung[$indexBidangPraktik][$indexData][$indexItemData] ?? null;
                        if (isset($filePendukung)) {
                            $this->_penilaianFilePendukung(filePendukung: $filePendukung, questionAnswerPraktik: $penilaian);
                        }
                    }
                    // Looping increment data jawaban
                    $loopIndex++;
                }

                // Store dan Update Penilaian Bidang Praktik
                $this->penilaianCommandService->storePenilaianBidangPraktik(questionAnswerBidPraktik: $findBidangPraktik, levelTarget: $request->level_target[$indexBidangPraktik]);
            }

            // Lakukan perhitungan jawaban yang sudah terjawab
            $this->_hitungPerbandinganPertanyaanTerjawab(id_daftar_penilaian: $id_daftar_penilaian);

            DB::commit();

            // Return redirect
            return redirect()->back()->with('success', 'Bidang Praktik Berhasil Ditambahkan');
        } catch (Throwable $th) {
            DB::rollBack();

            return redirect()->back()->with('error', $th->getMessage());
        }
    }

    // Private function here
    private function _penilaianFilePendukung(mixed $filePendukung, QuestionAnswerPraktik $questionAnswerPraktik): void
    {
        // Key Prefix Asset
        $assetKeyPrefix = 'penilaian-file-pendukung-';

        // Cari semua asset lama
        $oldAssets = $this->assetQueryService->getAllAsset(pathType: QuestionAnswerPraktik::class, pathId: $questionAnswerPraktik->id, withAssetKey: $assetKeyPrefix, usingLike: true);
        // Hapus semua asset lama secara fisik
        foreach ($oldAssets as $asset) {
            Storage::delete('public/'.$asset->asset_path);
        }

        // Kosongkan sebelum ditambahkan
        $this->assetCommandService->deleteAllAsset(pathType: QuestionAnswerPraktik::class, pathId: $questionAnswerPraktik->id, assetKey: $assetKeyPrefix, usingLike: true);

        // Cek apakah assetnya 1 atau banyak
        if (is_array($filePendukung)) {
            // Lakukan foreach jika lebih dari 1
            foreach ($filePendukung as $file) {
                $this->_handleImage(
                    file: $file,
                    pathType: QuestionAnswerPraktik::class,
                    assetKeyPrefix: $assetKeyPrefix,
                    pathId: $questionAnswerPraktik->id
                );
            }
        } elseif ($filePendukung instanceof \Illuminate\Http\UploadedFile) {
            // Langsung hapus jika hanya 1
            $this->_handleImage(
                file: $filePendukung,
                pathType: QuestionAnswerPraktik::class,
                assetKeyPrefix: $assetKeyPrefix,
                pathId: $questionAnswerPraktik->id
            );
        }
    }

    private function _handleImage(array|\Illuminate\Http\UploadedFile|null $file, string $pathType, string $assetKeyPrefix, string $pathId): array
    {
        // Extensi yang diizinkan untuk ditambahkan
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'docx', 'doc', 'pdf'];
        $savedAssets = [];

        // Pastikan jadi array agar bisa di-loop
        $files = is_array($file) ? $file : [$file];

        // Looping files
        foreach ($files as $uploadedFile) {

            // Jika bukan file diunggah, lewati
            if (! ($uploadedFile instanceof \Illuminate\Http\UploadedFile)) {
                continue;
            }

            // Lakukan pengecekan extension
            $extension = strtolower($uploadedFile->getClientOriginalExtension());

            if (! in_array($extension, $allowedExtensions)) {
                // Lewati file tidak valid (atau bisa lempar exception tergantung kebutuhan)
                continue;
            }

            // Generate nama file
            $fileName = time().'_'.$uploadedFile->getClientOriginalName();

            // Generate lokasi file
            $filePath = $uploadedFile->storeAs('public/penilaian/'.$pathId, $fileName);

            // Simpan asset
            $savedAssets[] = $this->assetCommandService->storeAsset(
                pathType: $pathType,
                pathId: $pathId,
                pathName: str_replace('public/', '', $filePath),
                assetKey: $assetKeyPrefix.Ulid::generate()
            );
        }

        // Kembalikan array
        return $savedAssets;
    }

    private function _hitungPerbandinganPertanyaanTerjawab(string $id_daftar_penilaian): void
    {
        // Cari daftar penilaian
        $findDaftarPenilaian = $this->daftarPenilaianQueryService->findDaftarPenilaianById(id: $id_daftar_penilaian);

        // Cek apakah daftar penilaian ada
        if (! isset($findDaftarPenilaian)) {
            throw new Exception('Daftar Penilaian tidak ditemukan');
        }

        // Mendapatkan seluruh data domain
        $getAllDomain = $this->penilaianQueryService->getAllQuestion(daftar_penilaian_id: $findDaftarPenilaian->id);

        // Looping semua domain
        foreach ($getAllDomain as $itemDomain) {
            // Looping semua anwer bid kapabilitas
            foreach ($itemDomain->questionAnswerBidKapabilitas as $itemBidKapabilitas) {

                // Looping semua anwer bid praktik
                foreach ($itemBidKapabilitas->questionAnswerBidPraktik as $itemBidPraktik) {
                    // Hitung Bidang Praktik
                    $praktikTerjawab = $itemBidPraktik->questionAnswerPraktik->where('answer', '!=', null)->count();
                    $itemBidPraktik->is_completed_answer = $praktikTerjawab > 0 ? true : false;
                    $itemBidPraktik->save();
                }
                // Hitung Bidang Kapabilitas
                $bidPraktikTerjawab = $itemBidKapabilitas->questionAnswerBidPraktik->where('is_completed_answer', true)->count();
                $bidPraktikTotal = $itemBidKapabilitas->questionAnswerBidPraktik->count();
                $itemBidKapabilitas->is_completed_answer = $bidPraktikTerjawab === $bidPraktikTotal ? true : false;
                $itemBidKapabilitas->save();
            }
            // Hitung Domain
            $bidKapabilitasTerjawab = $itemDomain->questionAnswerBidKapabilitas->where('is_completed_answer', true)->count();
            $bidKapabilitasTotal = $itemDomain->questionAnswerBidKapabilitas->count();
            $itemDomain->is_completed_answer = $bidKapabilitasTerjawab === $bidKapabilitasTotal ? true : false;
            $itemDomain->save();
        }
        // Hitung Daftar Penilaian
        $countDomainQuestion = $getAllDomain->where('is_completed_answer', true)->count();
        $countDomainTotal = $getAllDomain->count();
        $findDaftarPenilaian->count_domain_question = $countDomainTotal;
        $findDaftarPenilaian->count_domain_answered = $countDomainQuestion;
        $findDaftarPenilaian->save();

        // tidak perlu return apa-apa karena void
    }
}
