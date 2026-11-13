<?php

it('returns the active price categories in display order', function () {
    $this->getJson('/api/price-categories')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(3, 'data')
        ->assertJsonPath('data.0.slug', 'kampung')
        ->assertJsonPath('data.0.name', 'Mushaf Kampung')
        ->assertJsonPath('data.0.amount', 20000)
        ->assertJsonPath('data.1.slug', 'reguler')
        ->assertJsonPath('data.1.amount', 30000)
        ->assertJsonPath('data.2.slug', 'vip')
        ->assertJsonPath('data.2.amount', 50000);
});
