<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Message extends Model
{
    use HasFactory;
    
    protected $fillable = ['chat_id', 'user_id', 'text', 'answer'];

    public function chat()
    {
        return $this->belongsTo(Chat::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function informationSources()
    {
        return $this->hasMany(InformationSource::class);
    }

    public function rating()
    {
        return $this->hasOne(MessageRating::class);
    }

    public function ratings()
    {
        return $this->hasMany(MessageRating::class);
    }
}