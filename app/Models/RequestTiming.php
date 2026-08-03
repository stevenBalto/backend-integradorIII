<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class RequestTiming extends Model
{
    protected $table = 'request_timings';

    protected $fillable = [
        'method', 'path', 'route', 'duration_ms', 'status_code', 'user_id', 'ip',
    ];

    public $timestamps = true;
}
