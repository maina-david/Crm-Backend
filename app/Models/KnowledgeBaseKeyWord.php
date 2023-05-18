<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeBaseKeyWord extends Model
{
    use HasFactory;

    protected $fillable = [
        "knowledge_base_id",
        "key_word_id"
    ];

    /**
     * Get the knowledge_base that owns the KnowledgeBaseKeyWord
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function knowledge_base(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBase::class, 'knowledge_base_id');
    }

    /**
     * Get the key_word that owns the KnowledgeBaseKeyWord
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function key_word(): BelongsTo
    {
        return $this->belongsTo(KeyWord::class, 'key_word_id');
    }
}
