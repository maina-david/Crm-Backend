<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Group extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'description',
        'company_id'
    ];

    public function users()
    {
        return $this->hasManyThrough(User::class, UserGroup::class, "group_id", "id", "id", "user_id");
    }

    public function queues()
    {
        return $this->hasMany(Queue::class);
    }

    /**
     * Get all of the chatQueues for the Group
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function chatQueues(): HasMany
    {
        return $this->hasMany(ChatQueue::class);
    }

    public function accounttype()
    {
        return $this->hasManyThrough(AccountType::class, AccountTypeGroup::class, 'group_id', 'id', 'id', 'account_type_id');
    }

    /**
     * Get all of the group_users for the Group
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function group_users(): HasMany
    {
        return $this->hasMany(UserGroup::class, 'group_id');
    }
}