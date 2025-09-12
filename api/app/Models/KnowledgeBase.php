<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class KnowledgeBase extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'name',
        'description',
        'owner_id',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function sources()
    {
        return $this->hasMany(Source::class, 'kb_id');
    }

    public function chunks()
    {
        return $this->hasMany(Chunk::class, 'kb_id');
    }

    public function chats()
    {
        return $this->hasMany(Chat::class);
    }

    public function groups()
    {
        return $this->belongsToMany(
            Group::class,
            'group_knowledge_base', // pivot table
            'kb_id', // current model FK on pivot
            'group_id' // related model FK on pivot
        );
    }
}
