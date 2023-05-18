<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, LogsActivity;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone_number',
        'company_id',
        'status',
        'is_locked',
        'is_owner',
        "email_verified_at"
    ];

    protected static $logFillable = true;

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /* Appending the gravatar attribute to the user model. */
    protected $appends = ['gravatar'];

    /**
     * > If the user has a gravatar, return it. Otherwise, return a default image
     * 
     * @return The Gravatar URL for the user's email address.
     */
    public function GetGravatarAttribute()
    {
        return 'https://www.gravatar.com/avatar/' . md5(strtolower(trim($this->email)));
    }

    /**
     * user will belong to one company
     */
    public function company()
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /**
     * user will has one role profile
     */
    public function role_profile()
    {
        return $this->hasOneThrough(RoleProfile::class, UserAccessProfile::class, 'user_id', "id", "id", "access_profile_id");
    }

    public function queue()
    {
        return $this->hasManyThrough(Queue::class, UserQueue::class, 'user_id', "id", "id", "queue_id");
    }
    public function groups()
    {
        return $this->hasManyThrough(Group::class, UserGroup::class, "user_id", "id", "id", "group_id");
    }

    public function sip()
    {
        return $this->belongsTo(SipList::class, 'sip_id');
    }

    public function user_groups()
    {
        return $this->hasMany(UserGroup::class);
    }

    public function queue_logs()
    {
        return $this->hasMany(QueueLog::class);
    }

    public function helpdeskteams()
    {
        return $this->belongsToMany(HelpDeskTeam::class, 'help_desk_team_users');
    }

    public function assignedTickets()
    {
        return $this->belongsToMany(Ticket::class, 'ticket_assignees');
    }

    /**
     * Get all of the user_access_profiles for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function user_access_profiles(): HasMany
    {
        return $this->hasMany(UserAccessProfile::class, 'user_id');
    }

    public function users_by_profile($profiles)
    {
        return $this->belongsToMany(UserAccessProfile::class)
            ->wherePivotIn('access_profile_id', $profiles);
    }

    /**
     * Get all of the conversations for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function conversations(): HasMany
    {
        return $this->hasMany(Conversation::class, 'assigned_to', 'id');
    }

    /**
     * > This function returns a collection of all the reviews that have been written about this agent
     * 
     * @return HasMany A collection of QAEvaluation models.
     */
    public function reviews(): HasMany
    {
        return $this->hasMany(QAEvaluation::class, 'agent_id', 'id');
    }

    /**
     * > This function returns a collection of all the reviews that were assessed by the user
     * 
     * @return HasMany A collection of QAEvaluation objects.
     */
    public function evaluations(): HasMany
    {
        return $this->hasMany(QAEvaluation::class, 'assessed_by', 'id');
    }

    /**
     * > This function returns a collection of all the reviews that were assessed by the user
     * 
     * @return HasMany A collection of QAEvaluation objects.
     */
    public function interaction_reviews(): HasMany
    {
        return $this->hasMany(QAInteractionReview::class, 'agent_id', 'id');
    }

    /**
     * Get all of the assigned_tickets for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function assigned_tickets(): HasMany
    {
        return $this->hasMany(TicketAssignment::class, 'user_id', 'id');
    }

    /**
     * Get all of the ticket escallations for the User
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function escalations(): HasMany
    {
        return $this->hasMany(EscalationLog::class, 'changed_by', 'id');
    }
}