<?php

namespace App\Models;

use App\Models\Provider;
use App\Models\ProviderTerminal;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $provider_name
 * @property int|null $provider_id
 * @property int|null $provider_terminal_id
 * @property int|null $merchant_id
 * @property int|null $order_id
 * @property string $request_type
 * @property array|null $request_params
 * @property array|null $response_data
 * @property bool $success
 * @property string|null $error_message
 * @property int|null $response_time_ms
 * @property int $retry_attempt
 * @property string|null $detail_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property Merchant|null $merchant
 * @property Order|null $order
 * @property Provider|null $provider
 * @property ProviderTerminal|null $providerTerminal
 */
class RequisiteProviderLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_name',
        'provider_id',
        'provider_terminal_id',
        'merchant_id',
        'order_id',
        'request_type',
        'request_params',
        'response_data',
        'success',
        'error_message',
        'response_time_ms',
        'retry_attempt',
        'detail_id',
    ];

    protected $casts = [
        'request_params' => 'array',
        'response_data' => 'array',
        'success' => 'boolean',
        'response_time_ms' => 'integer',
        'retry_attempt' => 'integer',
        'provider_id' => 'integer',
        'provider_terminal_id' => 'integer',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Создать запись лога для провайдера
     */
    public static function createLog(
        string $providerName,
        string $requestType,
        bool $success,
        ?int $responseTimeMs = null,
        ?string $errorMessage = null,
        ?array $requestParams = null,
        ?array $responseData = null,
        ?int $merchantId = null,
        ?int $orderId = null,
        ?string $detailId = null,
        int $retryAttempt = 1,
        ?int $providerId = null,
        ?int $providerTerminalId = null
    ): self {
        return static::create([
            'provider_name' => $providerName,
            'provider_id' => $providerId,
            'provider_terminal_id' => $providerTerminalId,
            'merchant_id' => $merchantId,
            'order_id' => $orderId,
            'request_type' => $requestType,
            'request_params' => $requestParams,
            'response_data' => $responseData,
            'success' => $success,
            'error_message' => $errorMessage,
            'response_time_ms' => $responseTimeMs,
            'retry_attempt' => $retryAttempt,
            'detail_id' => $detailId,
        ]);
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(Provider::class);
    }

    public function providerTerminal(): BelongsTo
    {
        return $this->belongsTo(ProviderTerminal::class);
    }
} 