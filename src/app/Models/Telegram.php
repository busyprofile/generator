<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property string $telegram_id
 * @property string $member_status
 * @property string $user_id
 * @property User $user
 */
class Telegram extends Model
{
    use HasFactory;

    protected $fillable = [
        'telegram_id',
        'member_status',
        'user_id',
    ];

    public $timestamps = false;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
