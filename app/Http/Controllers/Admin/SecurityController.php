<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Security\SecurityUpdateRequest;
use App\Services\User\UserCommandService;
use App\Services\User\UserQueryService;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

final class SecurityController extends Controller
{
    // Constructor
    public function __construct(
        private readonly UserQueryService $userQueryService,
        private readonly UserCommandService $userCommandService
    ) {}

    public function index(Request $request): View
    {
        // Return view
        return view('admin.pages.setting.security');
    }

    public function update(SecurityUpdateRequest $request): RedirectResponse
    {
        // Transactional
        try {
            DB::beginTransaction();
            // Cek user yang login
            $user = $this->userQueryService->findUserById(id: Auth::user()->id, checkPrimaryAccount: false);
            if (! isset($user)) {
                throw new Exception(trans('response.error.update', ['data' => 'User', 'error' => 'User not found']));
            }

            if (! $this->userCommandService->updateUserSecurity(request: $request, user: $user)) {
                throw new Exception(trans('response.error.update', ['data' => 'User Security', 'error' => 'Data gagal disimpan']));
            }

            DB::commit();

            // Apakah mengganti password
            if (isset($request->password)) {
                // Otomatis logout dan redirect ke login
                Auth::logout();

                return to_route('login')->with('success', trans('response.success.update', ['data' => 'User Security']));
            }

            // Redirect kehalaman setting security
            return to_route('admin.setting.security.index')->with('success', trans('response.success.update', ['data' => 'User Security']));
        } catch (Throwable $th) {
            DB::rollBack();

            return to_route('admin.setting.security.index')->with('error', $th->getMessage());
        }
    }
}
