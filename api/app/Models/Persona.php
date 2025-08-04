<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Persona extends Model
{
    use HasFactory;

    protected $table = 'personas';

    protected $fillable = [
        'name',
        'description',
        'instructions',
        'response_format',
        'keywords',
        'creativity',
        'active'
    ];

    protected $casts = [
        'creativity' => 'decimal:2',
        'active' => 'boolean',
        'keywords' => 'array'
    ];

    public function chats()
    {
        return $this->hasMany(Chat::class);
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function getKeywordsStringAttribute()
    {
        return is_array($this->keywords) ? implode(', ', $this->keywords) : '';
    }
}
