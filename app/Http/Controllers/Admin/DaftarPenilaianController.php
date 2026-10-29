<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DaftarPenilaian\DaftarPenilaianStoreRequest;
use App\Http\Requests\Admin\DaftarPenilaian\DaftarPenilaianUpdateRequest;
use App\Services\DaftarPenilaian\DaftarPenilaianCommandService;
use App\Services\DaftarPenilaian\DaftarPenilaianQueryService;
use App\Services\Domain\DomainQueryService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

final class DaftarPenilaianController extends Controller
{
    // Constructor
    public function __construct(
        private readonly DaftarPenilaianCommandService $daftarPenilaianCommandService,
        private readonly DaftarPenilaianQueryService $daftarPenilaianQueryService,
        private readonly DomainQueryService $domainQueryService
    ) {}

    public function index(Request $request): View
    {
        // Mendapatkan semua daftar penilaian
        $getAllDaftarPenilaian = $this->daftarPenilaianQueryService->getAllDaftarPenilaian(withPaginate: true);

        // Return View
        return view('admin.pages.daftar-penilaian.index', compact('getAllDaftarPenilaian'));
    }

    public function store(DaftarPenilaianStoreRequest $request): RedirectResponse
    {
        // Transactional
        try {
            DB::beginTransaction();
            // Buat daftar penilaian
            $dataStore = $this->daftarPenilaianCommandService->storeDaftarPenilaian(request: $request);

            // Cek apakah daftar penilaian ada/berhasil dibuat
            if (! isset($dataStore)) {
                throw new Exception(trans('response.error.store', ['data' => 'Daftar Penilaian', 'error' => 'data tidak ditemukan']));
            }

            // Mendapatkan semua domain aktif
            $domain = $this->domainQueryService->getAllDomain(isActiveOnly: true);

            // Ambil seluruh domain, kapabilitas, bidang praktik, dan praktik dari domain untuk dijadikan pertanyaan pada daftar penilaian
            $daftarPenilaianQuestion = $this->daftarPenilaianCommandService->storeDaftarPenilaianQuestion(daftarPenilaian: $dataStore, domain: $domain);

            // Cek apakah pertanyaan berhasil ditambahkan
            if (! $daftarPenilaianQuestion) {
                throw new Exception(trans('response.error.store', ['data' => 'Daftar Penilaian', 'error' => 'data gagal ditambahkan']));
            }
            DB::commit();

            // Redirect
            return to_route('admin.daftar-penilaian.index')->with('success', trans('response.success.store', ['data' => 'Daftar Penilaian']));
        } catch (Throwable $th) {
            DB::rollBack();

            return to_route('admin.daftar-penilaian.index')->with('error', $th->getMessage());
        }
    }

    public function update(DaftarPenilaianUpdateRequest $request, string $id): RedirectResponse
    {
        // Transactional
        try {
            DB::beginTransaction();
            // Mendapatkan daftar penilaian
            $daftarPenilaian = $this->daftarPenilaianQueryService->findDaftarPenilaianById(id: $id);

            // Cek apakah daftar penilaian ada
            if (! isset($daftarPenilaian)) {
                throw new Exception(trans('response.error.update', ['data' => 'Daftar Penilaian', 'error' => 'data tidak ditemukan']));
            }

            if ($daftarPenilaian->is_publish) {
                throw new Exception('Data Penilaian sudah dipublish, silahkan unpublish terlebih dahulu untuk melakukan perubahan');
            }

            // Ubah daftar penilaian
            $this->daftarPenilaianCommandService->updateDaftarPenilaian(request: $request, daftarPenilaian: $daftarPenilaian);
            DB::commit();

            // Redirect
            return to_route('admin.daftar-penilaian.index')->with('success', trans('response.success.update', ['data' => 'Daftar Penilaian']));
        } catch (Throwable $th) {
            DB::rollBack();

            return to_route('admin.daftar-penilaian.index')->with('error', $th->getMessage());
        }
    }

    public function delete(Request $request, string $id): RedirectResponse
    {
        // Transactional
        try {
            DB::beginTransaction();
            // Mendapatkan daftar penilaian
            $daftarPenilaian = $this->daftarPenilaianQueryService->findDaftarPenilaianById(id: $id);
            // Cek apakah daftar penilaian ada
            if (! isset($daftarPenilaian)) {
                throw new Exception(trans('response.error.delete', ['data' => 'Daftar Penilaian', 'error' => 'data tidak ditemukan']));
            }

            if ($daftarPenilaian->is_publish) {
                throw new Exception('Data Penilaian sudah dipublish, silahkan unpublish terlebih dahulu untuk melakukan perubahan');
            }

            // Cek apakah daftar penilaian masih aktif
            if ($daftarPenilaian->is_active) {
                throw new Exception(trans('response.error.delete', ['data' => 'Daftar Penilaian', 'error' => 'Daftar Penilaian masih aktif']));
            }

            // Hapus daftar penilaian
            $this->daftarPenilaianCommandService->deleteDaftarPenilaian(daftarPenilaian: $daftarPenilaian);
            DB::commit();

            // Redirect
            return to_route('admin.daftar-penilaian.index')->with('success', trans('response.success.delete', ['data' => 'Daftar Penilaian']));
        } catch (Throwable $th) {
            DB::rollBack();

            return to_route('admin.daftar-penilaian.index')->with('error', $th->getMessage());
        }
    }

    public function setStatus(Request $request, string $id): RedirectResponse
    {
        // Transactional
        try {
            DB::beginTransaction();
            // Mendapatkan daftar penilaian
            $daftarPenilaian = $this->daftarPenilaianQueryService->findDaftarPenilaianById(id: $id);

            // Cek apakah daftar penilaian ada
            if (! isset($daftarPenilaian)) {
                throw new Exception(trans('response.error.update', ['data' => 'Daftar Penilaian', 'error' => 'data tidak ditemukan']));
            }

            if ($daftarPenilaian->is_publish) {
                throw new Exception('Data Penilaian sudah dipublish, silahkan unpublish terlebih dahulu untuk melakukan perubahan');
            }

            // Ubah daftar penilaian
            $this->daftarPenilaianCommandService->setStatus(daftarPenilaian: $daftarPenilaian);
            DB::commit();

            // Redirect
            return to_route('admin.daftar-penilaian.index')->with('success', trans('response.success.switch', ['data' => 'Perubahan status DaftarPenilaian']));
        } catch (Throwable $th) {
            DB::rollBack();

            return to_route('admin.daftar-penilaian.index')->with('error', $th->getMessage());
        }
    }
}
