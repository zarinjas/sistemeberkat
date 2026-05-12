<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\SiteSetting;
use App\Models\User;
use App\Services\LoginAccessLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function __construct(
        private readonly LoginAccessLogger $loginAccessLogger,
    ) {
    }

    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
            'heroConfig' => [
                'image_url' => SiteSetting::get('login_hero_image_url', ''),
                'heading' => SiteSetting::get('login_hero_heading', 'Selamat Datang ke e-BERKAT!'),
                'subtext' => SiteSetting::get('login_hero_subtext', 'Sistem Pengurusan Bantuan Digital Bersepadu yang pintar, pantas, dan telus.'),
                'overlay_color' => SiteSetting::get('login_hero_overlay_color', '#020617'),
                'overlay_opacity' => (int) SiteSetting::get('login_hero_overlay_opacity', 60),
            ],
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
        $authenticatedUser = $request->user();

        if ($authenticatedUser) {
            $this->loginAccessLogger->log($request, $authenticatedUser, 'standard');
        }

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Handle first-time login activation using NRIC.
     *
     * @throws ValidationException
     */
    public function firstTimeLogin(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'nric' => ['required', 'string', 'max:20'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $normalizedNric = preg_replace('/\D+/', '', (string) $validated['nric']);

        $user = User::query()
            ->where('nric', $normalizedNric)
            ->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'nric' => 'No. IC tidak dijumpai dalam rekod sistem.',
            ]);
        }

        if ($user->first_login_completed) {
            throw ValidationException::withMessages([
                'nric' => 'Akaun ini telah diaktifkan. Sila gunakan log masuk biasa.',
            ]);
        }

        $currentEmail = strtolower((string) $user->email);
        $inputEmail = strtolower((string) $validated['email']);
        $isPendingEmail = str_ends_with($currentEmail, '@pending.local');

        if ($currentEmail && ! $isPendingEmail && $currentEmail !== $inputEmail) {
            throw ValidationException::withMessages([
                'email' => 'E-mel tidak sepadan dengan rekod sistem untuk No. IC ini.',
            ]);
        }

        $user->email = $inputEmail;
        $user->password = Hash::make($validated['password']);
        $user->first_login_completed = true;
        $user->save();

        Auth::login($user);
        $request->session()->regenerate();
        $this->loginAccessLogger->log($request, $user, 'first_time');

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
