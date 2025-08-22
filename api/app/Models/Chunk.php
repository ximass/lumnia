<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Chunk extends Model
{
    use HasFactory;

    protected $fillable = [
        'id',
        'source_id',
        'kb_id',
        'chunk_index',
        'text',
        'metadata',
    ];

    protected $keyType = 'string';
    public $incrementing = false;

    protected $casts = [
        'metadata' => 'array',
    ];

    public function source()
    {
        return $this->belongsTo(Source::class, 'source_id');
    }

    public function knowledgeBase()
    {
        return $this->belongsTo(KnowledgeBase::class, 'kb_id');
    }
}
