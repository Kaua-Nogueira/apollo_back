<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserApiToken extends Model
{
    protected $guarded = [];

    protected $hidden = ['token_hash'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
