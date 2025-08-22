<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Source extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'kb_id',
        'source_type',
        'source_identifier',
        'content_hash',
        'status',
        'metadata',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'metadata' => 'array',
    ];

    public function knowledgeBase()
    {
        return $this->belongsTo(KnowledgeBase::class, 'kb_id');
    }

    public function chunks()
    {
        return $this->hasMany(Chunk::class, 'source_id');
    }
}
