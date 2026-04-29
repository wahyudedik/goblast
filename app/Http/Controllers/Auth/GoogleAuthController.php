<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\Contracts\GoogleAuthServiceInterface;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\InvalidStateException;

class GoogleAuthController extends Controller
{
    public function __construct(
        private GoogleAuthServiceInterface $googleAuthService,
    ) {}

    /**
     * Redirect the user to Google's OAuth consent page.
     */
    public function redirect(): RedirectResponse
    {
        try {
            return Socialite::driver('google')
                ->scopes(['openid', 'profile', 'email'])
                ->redirect();
        } catch (\Exception $e) {
            Log::error('Google OAuth redirect failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Tidak dapat terhubung ke layanan Google. Silakan coba lagi.');
        }
    }

    /**
     * Handle the callback from Google after authentication.
     */
    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            // Validate email format
            if (! filter_var($googleUser->getEmail(), FILTER_VALIDATE_EMAIL)) {
                Log::warning('Invalid email from Google OAuth', [
                    'email' => $googleUser->getEmail(),
                ]);

                return redirect()->route('login')
                    ->with('error', 'Email yang diterima dari Google tidak valid.');
            }

            $user = $this->googleAuthService->findOrCreateUser([
                'id' => $googleUser->getId(),
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'avatar' => $googleUser->getAvatar(),
            ]);

            Auth::login($user);

            // Regenerate session to prevent session fixation
            request()->session()->regenerate();

            Log::info('User logged in via Google OAuth', [
                'user_id' => $user->id,
                'email' => $user->email,
            ]);

            return redirect()->intended(route('dashboard'));
        } catch (InvalidStateException $e) {
            Log::warning('Invalid state in Google OAuth callback', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Sesi autentikasi tidak valid. Silakan coba lagi.');
        } catch (\Exception $e) {
            Log::error('Google OAuth callback failed', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->route('login')
                ->with('error', 'Tidak dapat terhubung ke layanan Google. Silakan coba lagi.');
        }
    }
}
