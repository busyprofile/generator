<?php

namespace App\Observers;

use App\Models\ProviderTerminal;
use App\Services\RequisiteProviders\ProviderSelector;
use App\Services\RequisiteProviders\RequisiteProviderChain;

class ProviderTerminalObserver
{
    public function created(ProviderTerminal $providerTerminal): void
    {
        $this->flushCaches();
    }

    public function updated(ProviderTerminal $providerTerminal): void
    {
        $this->flushCaches();
    }

    public function deleted(ProviderTerminal $providerTerminal): void
    {
        $this->flushCaches();
    }

    public function restored(ProviderTerminal $providerTerminal): void
    {
        $this->flushCaches();
    }

    private function flushCaches(): void
    {
        ProviderTerminal::clearCache();
        ProviderSelector::invalidateCache();
        RequisiteProviderChain::invalidate();
    }
}
