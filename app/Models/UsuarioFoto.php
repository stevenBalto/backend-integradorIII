<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Foto de perfil de un usuario. Dato PRIVADO: solo se sirve al propio dueno
 * autenticado, nunca por URL publica (por eso no va a Cloudinary).
 *
 * La PK es user_id, asi que hay como maximo una foto por usuario sin
 * necesidad de un UNIQUE extra.
 *
 * OJO: `contenido` es un bytea que puede pesar cientos de KB. No cargar esta
 * relacion "por si acaso" — para saber si el usuario tiene foto usar
 * loadExists('foto'), que resuelve con un EXISTS y no trae el blob.
 */
final class UsuarioFoto extends Model
{
    protected $table = 'usuario_fotos';

    protected $primaryKey = 'user_id';

    public $incrementing = false;

    protected $keyType = 'int';

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'contenido',
        'mime',
        'tamano_bytes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tamano_bytes' => 'integer',
        ];
    }

    /**
     * Traduce el bytea entre PHP y Postgres.
     *
     * Hace falta porque PDO manda los parametros como TEXTO: si se le pasara
     * el binario crudo, Postgres intentaria leerlo como UTF-8 y reventaria con
     * "invalid byte sequence" (que ademas sube como un QueryException que
     * tampoco se puede serializar a JSON, escondiendo la causa real).
     *
     *   - set: se escribe en el formato hex de bytea ('\x' + hex), que es
     *     ASCII puro y Postgres decodifica solo.
     *   - get: al leer, el driver devuelve un stream; justo despues de guardar,
     *     en cambio, el atributo en memoria todavia tiene el hex. Se cubren
     *     los dos casos para que `$foto->contenido` sea SIEMPRE binario.
     */
    protected function contenido(): Attribute
    {
        return Attribute::make(
            get: static function ($value): string {
                if (is_resource($value)) {
                    return (string) stream_get_contents($value);
                }

                $texto = (string) $value;

                if (str_starts_with($texto, '\x')) {
                    return (string) hex2bin(substr($texto, 2));
                }

                return $texto;
            },
            set: static fn (string $value): string => '\x'.bin2hex($value),
        );
    }

    /** Dueno de la foto. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
