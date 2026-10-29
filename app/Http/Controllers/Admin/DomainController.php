<?php

namespace App\Http\Controllers\Admin;

use App\Constants\AppConst;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Domain\DomainStoreRequest;
use App\Http\Requests\Admin\Domain\DomainUpdateRequest;
use App\Services\Domain\DomainCommandService;
use App\Services\Domain\DomainQueryService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

final class DomainController extends Controller
{
    // Constructor
    public function __construct(
        private readonly DomainCommandService $domainCommandService,
        private readonly DomainQueryService $domainQueryService
    ) {}

    public function index(Request $request): View
    {
        // Mendapatkan semua data domain
        $getAllDomain = $this->domainQueryService->getAllDomain();

        // Membuat next code
        $nextNumber = count($getAllDomain) + 1;
        do {
            $nextCode = AppConst::CODE_NAME['domain'].'0'.$nextNumber++;
        } while ($getAllDomain->contains('code', $nextCode));

        // Return view
        return view('admin.pages.domain.index', compact('getAllDomain', 'nextCode'));
    }

    public function store(DomainStoreRequest $request): RedirectResponse
    {
        // Transactional
        try {
            DB::beginTransaction();
            // Menyimpan domain
            $this->domainCommandService->storeDomain(request: $request);
            DB::commit();

            // Redirect
            return to_route('admin.master-data.domain.index')->with('success', trans('response.success.store', ['data' => 'Domain']));
        } catch (Throwable $th) {
            DB::rollBack();

            return to_route('admin.master-data.domain.index')->with('error', $th->getMessage());
        }
    }

    public function update(DomainUpdateRequest $request, string $id): RedirectResponse
    {
        // Transactional
        try {
            DB::beginTransaction();
            // Mendapatkan domain
            $domain = $this->domainQueryService->findDomainById(id: $id);

            // Cek apakah domain ada
            if (! isset($domain)) {
                throw new Exception(trans('response.error.update', ['data' => 'Domain', 'error' => 'data tidak ditemukan']));
            }

            // Ubah domain
            $this->domainCommandService->updateDomain(request: $request, domain: $domain);
            DB::commit();

            // Redirect
            return to_route('admin.master-data.domain.index')->with('success', trans('response.success.update', ['data' => 'Domain']));
        } catch (Throwable $th) {
            DB::rollBack();

            return to_route('admin.master-data.domain.index')->with('error', $th->getMessage());
        }
    }

    public function delete(Request $request, string $id): RedirectResponse
    {
        // Transactional
        try {
            DB::beginTransaction();
            // Mendapatkan domain
            $domain = $this->domainQueryService->findDomainById(id: $id);

            // Cek apakah domain ada
            if (! isset($domain)) {
                throw new Exception(trans('response.error.delete', ['data' => 'Domain', 'error' => 'data tidak ditemukan']));
            }

            // Cek apakah domain masih digunakan di pertanyaan
            if ($domain->daftarPenilaianQuestion->count() > 0) {
                throw new Exception(trans('response.error.delete', ['data' => 'Domain', 'error' => 'Domain masih digunakan pada Daftar Penilaian']));
            }

            // Cek apakah domain masih digunakan
            if ($domain->bidangKapabilitas->count() > 0) {
                throw new Exception(trans('response.error.delete', ['data' => 'Domain', 'error' => 'Domain masih digunakan pada bidang kapabilitas']));
            }

            // Cek apakah domain masih aktif
            if ($domain->is_active) {
                throw new Exception(trans('response.error.delete', ['data' => 'Domain', 'error' => 'Domain masih aktif']));
            }

            // Hapus domain
            $this->domainCommandService->deleteDomain(domain: $domain);
            DB::commit();

            // Redirect
            return to_route('admin.master-data.domain.index')->with('success', trans('response.success.delete', ['data' => 'Domain']));
        } catch (Throwable $th) {
            DB::rollBack();

            return to_route('admin.master-data.domain.index')->with('error', $th->getMessage());
        }
    }

    public function setStatus(Request $request, string $id): RedirectResponse
    {
        // Transactional
        try {
            DB::beginTransaction();
            // Mendapatkan domain
            $domain = $this->domainQueryService->findDomainById(id: $id);

            // Cek apakah domain ada
            if (! isset($domain)) {
                throw new Exception(trans('response.error.update', ['data' => 'Domain', 'error' => 'data tidak ditemukan']));
            }

            // Ubah domain
            $this->domainCommandService->setStatus(domain: $domain);
            DB::commit();

            // Redirect
            return to_route('admin.master-data.domain.index')->with('success', trans('response.success.switch', ['data' => 'Perubahan status Domain']));
        } catch (Throwable $th) {
            DB::rollBack();

            return to_route('admin.master-data.domain.index')->with('error', $th->getMessage());
        }
    }
}
