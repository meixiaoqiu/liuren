<?php

namespace App\Filament\Resources\PanResource\Pages;

use App\Filament\Resources\PanResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreatePan extends CreateRecord
{
    protected static string $resource = PanResource::class;

    protected function beforeCreate(): void
    {
        $already = $this->getModel()::firstWhere('sizhu', $this->data['sizhu']); // 相同的盘只存一份
        if (! empty($already) && $already->id > 0) {
            Notification::make()
                ->title('此盘已存在 id: '.$already->id)
                ->danger()
                ->seconds(99)
                ->send();

            $this->halt();
        }
    }
}
