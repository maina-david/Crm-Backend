<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class KnowledgeBaseStage extends Model
{
    use HasFactory;

    protected $fillable = [
        "company_id",
        "knowledge_base_id",
        "title",
        "detail",
        "status",
        "type"
    ];

    /**
     * Get all of the knowledge_base_key_word for the KnowledgeBaseStage
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function knowledge_base_key_word(): HasMany
    {
        return $this->hasMany(KnowledgeBaseKeyWord::class, 'knowledge_base_stage_id', 'local_key');
    }

    /**
     * Get all of the key_words for the KnowledgeBase
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasManyThrough
     */
    public function key_words(): HasManyThrough
    {
        return $this->hasManyThrough(
            KeyWord::class,
            KnowledgeBaseKeyWordStage::class,
            'knowledge_base_stage_id',
            'id',
            'id',
            'key_word_id'
        );
    }

    /**
     * Get the knowledge_base that owns the KnowledgeBaseStage
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function knowledge_base(): BelongsTo
    {
        return $this->belongsTo(KnowledgeBase::class, 'knowledge_base_id');
    }
}
