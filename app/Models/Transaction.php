<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
     use HasFactory;
    protected $guarded = ['id'];

     public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function metadata()
    {
        return $this->hasOne(Metadata::class, 'checkout_session_id', 'checkout_session_id');
    }
}
