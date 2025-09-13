<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class MessageRating extends Model
{
    use HasFactory;
    
    protected $fillable = ['message_id', 'user_id', 'rating'];

    protected $casts = [
        'rating' => 'string'
    ];

    public function message()
    {
        return $this->belongsTo(Message::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
