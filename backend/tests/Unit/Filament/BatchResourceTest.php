<?php

use App\Filament\Resources\Batches\BatchResource;
use App\Filament\Resources\Batches\RelationManagers\RegistrationsRelationManager;
use App\Filament\Resources\Batches\Schemas\BatchForm;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;

test('batch resource registers registrations relation manager', function () {
    expect(BatchResource::getRelations())->toContain(RegistrationsRelationManager::class);
});

test('batch form uses UMUM education level key', function () {
    $schema = BatchForm::configure(Schema::make());

    /** @var Select|null $educationLevelField */
    $educationLevelField = collect($schema->getComponents())
        ->first(fn ($component) => method_exists($component, 'getName') && $component->getName() === 'education_level');

    expect($educationLevelField)->not->toBeNull();
    expect($educationLevelField->getOptions())
        ->toHaveKey('UMUM')
        ->not->toHaveKey('Umum');
});
