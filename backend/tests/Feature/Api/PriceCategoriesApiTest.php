<?php

it('returns the active price categories in display order', function () {
    $this->getJson('/api/price-categories')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.0.slug', 'reguler')
        ->assertJsonPath('data.0.amount', 30000)
        ->assertJsonPath('data.1.slug', 'vip')
        ->assertJsonPath('data.1.amount', 50000);
});
