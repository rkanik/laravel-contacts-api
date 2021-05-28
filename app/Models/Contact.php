<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Contact extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'id',
        'user_id',
        'avatar',
        'first_name',
        'last_name',
        'company',
        'job_title',
        'is_favorite',
        'note',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function phone_numbers()
    {
        return $this->hasMany(PhoneNumber::class, 'contact_id');
    }

    public function emails()
    {
        return $this->hasMany(Email::class, 'contact_id');
    }
}
