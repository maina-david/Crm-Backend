<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class KnowledgeBase extends Model
{
    use HasFactory;

    protected $fillable = [
        "company_id",
        "title",
        "detail"
    ];
    
    /**
     * Get all of the key_words for the KnowledgeBase
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function key_words(): HasManyThrough
    {
        return $this->hasManyThrough(
            KeyWord::class,
            KnowledgeBaseKeyWord::class,
            'knowledge_base_id',
            'id',
            'id',
            'key_word_id'
        );
    }
}
