<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DocumentSequence extends Model
{
    use HasFactory;

    protected $fillable = [
        'doc_type',
        'ymd',
        'seq',
    ];

    protected $casts = [
        'doc_type' => DocumentType::class,
    ];
}
