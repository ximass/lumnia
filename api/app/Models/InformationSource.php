<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class InformationSource extends Model
{
    use HasFactory;

    protected $fillable = ['content'];

    public function message()
    {
        return $this->hasOne(Message::class);
    }
}