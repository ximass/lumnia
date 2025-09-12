<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function knowledgeBases()
    {
        return $this->belongsToMany(
            KnowledgeBase::class,
            'group_knowledge_base', // pivot table
            'group_id', // current model FK on pivot
            'kb_id' // related model FK on pivot (db uses kb_id)
        );
    }

    public function users()
    {
        return $this->belongsToMany(User::class);
    }
}
