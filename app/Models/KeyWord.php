<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class KeyWord extends Model
{
    use HasFactory;

    protected $fillable = [
        "company_id",
        "key_word"
    ];

    /**
     * Get all of the knowledge_bases for the KeyWord
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function knowledge_bases(): HasManyThrough
    {
        return $this->hasManyThrough(
            KnowledgeBase::class,
            KnowledgeBaseKeyWord::class,
            'key_word_id',
            'id',
            'id',
            'knowledge_base_id'
        );
    }
}
