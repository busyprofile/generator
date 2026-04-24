<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $request_id
 * @property string $provider_name
 * @property array|null $request_data
 * @property array|null $response_data
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property float|null $execution_time
 * @property int|null $status_code
 * @property bool $is_successful
 * @property string|null $error_message
 * @property string|null $exception_class
 * @property string|null $exception_message
 * @property int|null $merchant_id
 * @property int|null $order_id
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class RequisiteProviderCallbackLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_id',
        'provider_name',
        'request_data',
        'response_data',
        'ip_address',
        'user_agent',
        'execution_time',
        'status_code',
        'is_successful',
        'error_message',
        'exception_class',
        'exception_message',
        'merchant_id',
        'order_id',
    ];

    protected $casts = [
        'request_data' => 'array',
        'response_data' => 'array',
        'is_successful' => 'boolean',
        'execution_time' => 'float',
    ];

    public function merchant(): BelongsTo
    {
        return $this->belongsTo(Merchant::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
