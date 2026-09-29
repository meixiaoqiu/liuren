<?php

namespace App\Filament\Resources\PanResource\Pages;

use App\Filament\Resources\PanResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListPans extends ListRecords
{
    protected static string $resource = PanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
