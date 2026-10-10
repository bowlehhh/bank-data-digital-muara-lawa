<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\AuthenticationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, AuthenticationService $authentication): RedirectResponse
    {
        $authentication->login($request->validated(), $request);

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request, AuthenticationService $authentication): RedirectResponse
    {
        $authentication->logout($request);

        return redirect()->route('login')->with('status', 'Anda berhasil keluar.');
    }
}
