<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Chat extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'user_id'];

    public function messages()
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function knowledgeBases()
    {
        return $this->belongsToMany(KnowledgeBase::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}