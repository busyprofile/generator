<?php

namespace App\Jobs;

use App\Models\RequisiteProviderCallbackLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateRequisiteProviderCallbackLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 10;

    public function __construct(
        private string $requestId,
        private string $providerName,
        private array $requestData,
        private ?string $ipAddress = null,
        private ?string $userAgent = null
    ) {
        $this->afterCommit();
        $this->onQueue('logging');
    }

    public function handle(): void
    {
        RequisiteProviderCallbackLog::create([
            'request_id' => $this->requestId,
            'provider_name' => $this->providerName,
            'request_data' => $this->requestData,
            'ip_address' => $this->ipAddress,
            'user_agent' => $this->userAgent,
            'is_successful' => false,
        ]);
    }

    public function backoff(): array
    {
        return [5, 15, 30];
    }
}
