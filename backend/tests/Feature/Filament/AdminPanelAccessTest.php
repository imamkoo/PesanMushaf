<?php

use App\Models\User;

test('admin role can open the admin panel', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get('/admin')->assertOk();
});

test('buyer role is forbidden from the admin panel', function () {
    $buyer = User::factory()->create(['role' => 'buyer']);

    $this->actingAs($buyer)->get('/admin')->assertForbidden();
});

test('admin can open the profile page to change the password', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->get('/admin/profile')->assertOk();
});
