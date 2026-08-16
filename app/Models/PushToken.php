<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Token de un dispositivo registrado en Firebase Cloud Messaging. Mapea `push_tokens`.
 *
 * Es por INSTALACION, no por usuario: un cliente con telefono y tablet tiene una
 * fila por aparato, y a todas se les manda el push.
 *
 * No usa PerteneceAInstancia: el aislamiento multi-tenant ya lo da el usuario
 * duenio (los tokens solo se alcanzan desde su user_id, nunca sueltos).
 */
class PushToken extends Model
{
    use HasFactory;

    protected $table = 'push_tokens';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'token',
        'plataforma',
    ];

    /** @return BelongsTo<User, PushToken> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
