<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\GoogleProvider;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;

test('google auth redirect can be accessed', function () {
    $response = $this->get('/auth/google');

    $response->assertRedirect();
});

test('new users can register via google oauth', function () {
    $googleUser = Mockery::mock(SocialiteUser::class);
    $googleUser->shouldReceive('getId')->andReturn('123456789');
    $googleUser->shouldReceive('getName')->andReturn('John Doe');
    $googleUser->shouldReceive('getEmail')->andReturn('john@example.com');
    $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andReturn($googleUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->get('/auth/google/callback');

    $this->assertAuthenticated();
    $response->assertRedirect('/dashboard');

    $user = User::where('email', 'john@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->name)->toBe('John Doe');
    expect($user->email)->toBe('john@example.com');
    expect($user->email_verified_at)->not->toBeNull();
    expect($user->hasSocialLogin('google'))->toBeTrue();
    expect($user->getSocialLoginId('google'))->toBe('123456789');
});

test('existing users can login via google oauth', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'name' => 'John Doe',
    ]);

    $googleUser = Mockery::mock(SocialiteUser::class);
    $googleUser->shouldReceive('getId')->andReturn('123456789');
    $googleUser->shouldReceive('getName')->andReturn('John Doe');
    $googleUser->shouldReceive('getEmail')->andReturn('john@example.com');
    $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andReturn($googleUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->get('/auth/google/callback');

    $this->assertAuthenticated();
    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/dashboard');

    $user->refresh();
    expect($user->hasSocialLogin('google'))->toBeTrue();
    expect($user->getSocialLoginId('google'))->toBe('123456789');
});

test('existing user with google login can login again', function () {
    $user = User::factory()->create([
        'email' => 'john@example.com',
        'name' => 'John Doe',
        'social_logins' => [
            'google' => [
                'id' => '123456789',
                'name' => 'John Doe',
                'avatar' => 'https://example.com/old-avatar.jpg',
            ]
        ]
    ]);

    $googleUser = Mockery::mock(SocialiteUser::class);
    $googleUser->shouldReceive('getId')->andReturn('123456789');
    $googleUser->shouldReceive('getName')->andReturn('John Doe');
    $googleUser->shouldReceive('getEmail')->andReturn('john@example.com');
    $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/new-avatar.jpg');

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andReturn($googleUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->get('/auth/google/callback');

    $this->assertAuthenticated();
    $this->assertAuthenticatedAs($user);
    $response->assertRedirect('/dashboard');
});

test('google oauth callback handles socialite exception', function () {
    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andThrow(new Exception('OAuth error'));

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->get('/auth/google/callback');

    $this->assertGuest();
    $response->assertRedirect('/login');
    $response->assertSessionHasErrors(['email' => 'Failed to authenticate with Google.']);
});

test('google oauth callback redirects to intended url', function () {
    session(['url.intended' => '/settings']);

    $googleUser = Mockery::mock(SocialiteUser::class);
    $googleUser->shouldReceive('getId')->andReturn('123456789');
    $googleUser->shouldReceive('getName')->andReturn('John Doe');
    $googleUser->shouldReceive('getEmail')->andReturn('john@example.com');
    $googleUser->shouldReceive('getAvatar')->andReturn('https://example.com/avatar.jpg');

    $provider = Mockery::mock(GoogleProvider::class);
    $provider->shouldReceive('user')->andReturn($googleUser);

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);

    $response = $this->get('/auth/google/callback');

    $this->assertAuthenticated();
    $response->assertRedirect('/settings');
});