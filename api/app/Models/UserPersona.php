<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPersona extends Model
{
    use HasFactory;

    protected $table = 'user_personas';

    protected $fillable = [
        'user_id',
        'instructions',
        'response_format',
        'creativity',
        'active'
    ];

    protected $casts = [
        'creativity' => 'decimal:2',
        'active' => 'boolean'
    ];

    protected $attributes = [
        'instructions' => null,
        'response_format' => null,
        'creativity' => 0.5,
        'active' => true
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
