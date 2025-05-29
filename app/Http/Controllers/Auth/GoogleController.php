<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Nette\Utils\Random;

class GoogleController extends Controller
{
    public function redirectToGoogle(): RedirectResponse
    {
        return Socialite::driver('google')->redirect();
    }

    public function handleGoogleCallback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (\Exception $e) {
            return redirect()->route('login')->withErrors(['email' => 'Failed to authenticate with Google.']);
        }

        $user = User::where('email', $googleUser->getEmail())->first();

        if ($user) {
            if (!$user->hasSocialLogin('google')) {
                $user->addSocialLogin('google', $googleUser->getId(), [
                    'name' => $googleUser->getName(),
                    'avatar' => $googleUser->getAvatar(),
                ]);
            }
        } else {
            $user = User::create([
                'name' => $googleUser->getName(),
                'email' => $googleUser->getEmail(),
                'password' => Hash::make(Random::generate(16)), // Generate a random password
                'email_verified_at' => now(),
                'social_logins' => [
                    'google' => [
                        'id' => $googleUser->getId(),
                        'name' => $googleUser->getName(),
                        'avatar' => $googleUser->getAvatar(),
                    ]
                ]
            ]);

            event(new Registered($user));
        }

        Auth::login($user);

        return redirect()->intended('/dashboard');
    }
}