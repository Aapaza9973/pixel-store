<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Hash histórico de contraseñas de un usuario, para impedir la reutilización
 * de las últimas 5 (HU-1.6).
 *
 * @property int $id
 * @property int $user_id
 * @property string $password_hash
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class PasswordHistory extends Model
{
    protected $table = 'password_histories';

    protected $fillable = [
        'user_id',
        'password_hash',
    ];

    protected $hidden = [
        'password_hash',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
