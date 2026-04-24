<?php

namespace App\Observers;

use App\Models\Provider;
use App\Models\ProviderTerminal;
use App\Services\RequisiteProviders\ProviderSelector;
use App\Services\RequisiteProviders\RequisiteProviderChain;

class ProviderObserver
{
    public function created(Provider $provider): void
    {
        $this->flushCaches();
    }

    public function updated(Provider $provider): void
    {
        $this->flushCaches();
    }

    public function deleted(Provider $provider): void
    {
        $this->flushCaches();
    }

    public function restored(Provider $provider): void
    {
        $this->flushCaches();
    }

    private function flushCaches(): void
    {
        Provider::clearCache();
        ProviderTerminal::clearCache();
        ProviderSelector::invalidateCache();
        RequisiteProviderChain::invalidate();
    }
}
