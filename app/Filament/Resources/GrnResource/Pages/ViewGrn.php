<?php

namespace App\Filament\Resources\GrnResource\Pages;

use App\Filament\Resources\GrnResource;
use Filament\Resources\Pages\Page;

class ViewGrn extends Page
{
    protected static string $resource = GrnResource::class;

    protected static string $view = 'filament.resources.grn-resource.pages.view-grn';

    public $record;

    public function mount(int | string $record): void
    {
        $this->record = $this->resolveRecord($record);
    }
}
