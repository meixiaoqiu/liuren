<?php

use App\Extensions\LiurenPlugin;
use Tests\Fixtures\FakePlugin\FakePluginServiceProvider;

return new class implements LiurenPlugin
{
    public function id(): string
    {
        return 'fake-plugin';
    }

    public function providers(): array
    {
        return [FakePluginServiceProvider::class];
    }
};
