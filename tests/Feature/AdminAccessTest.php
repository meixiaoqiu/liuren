<?php

use App\Models\User;
use Filament\Models\Contracts\FilamentUser;

test('Filament admin login page remains available', function () {
    $this->get('/admin/login')->assertOk();
});

test('user exposes the access contract required by Filament', function () {
    expect(User::factory()->make())->toBeInstanceOf(FilamentUser::class);
});

test('local and testing environments allow admin access when no admin email whitelist is configured', function () {
    config(['app.admin_emails' => []]);

    $user = User::factory()->create();

    expect($user->hasAdminAccess())->toBeTrue();
});

test('configured admin email whitelist grants access only to verified matching users', function () {
    config(['app.admin_emails' => ['admin@example.com']]);

    $admin = User::factory()->create(['email' => 'admin@example.com']);
    $other = User::factory()->create(['email' => 'other@example.com']);
    $unverifiedAdmin = User::factory()->unverified()->make(['email' => 'admin@example.com']);

    expect($admin->hasAdminAccess())->toBeTrue()
        ->and($other->hasAdminAccess())->toBeFalse()
        ->and($unverifiedAdmin->hasAdminAccess())->toBeFalse();
});

test('production denies admin access when no whitelist is configured', function () {
    config(['app.admin_emails' => []]);
    app()->detectEnvironment(fn (): string => 'production');

    try {
        expect(User::factory()->make()->hasAdminAccess())->toBeFalse();
    } finally {
        app()->detectEnvironment(fn (): string => 'testing');
    }
});
