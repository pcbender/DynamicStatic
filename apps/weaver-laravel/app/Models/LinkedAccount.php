<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LinkedAccount extends Model
{
    protected $table = 'linked_accounts';

    protected $fillable = [
        'user_id',
        'provider',
        'provider_sub',
        'access_token',
        'refresh_token',
        'expires_at',
        'email',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];
}
