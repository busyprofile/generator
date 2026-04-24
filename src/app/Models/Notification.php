<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $message
 * @property int $recipients_count
 * @property int $delivered_count
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'message',
        'recipients_count',
        'delivered_count',
    ];
}
