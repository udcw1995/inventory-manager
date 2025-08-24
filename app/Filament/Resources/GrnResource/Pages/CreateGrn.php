<?php

namespace App\Filament\Resources\GrnResource\Pages;

use App\Filament\Resources\GrnResource;
use Filament\Resources\Pages\Page;

class CreateGrn extends Page
{
    protected static string $resource = GrnResource::class;

    protected static string $view = 'filament.resources.grn-resource.pages.create-grn';

    public function mount(): void
    {
        // Initialize any required data
    }
}
