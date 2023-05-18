<?php

namespace App\Providers;

use App\Models\AccessProfile;
use App\Models\User;
use App\Models\UserAccessProfile;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        /* define a admin user role */
        Gate::define('User Management', function ($user) {
            $user_role_profile=UserAccessProfile::where('user_id',$user->id)->first();
            $access_profile= AccessProfile::where(['role_profile_id'=>$user_role_profile->access_profile_id,"access_name"=>'User Management'])->first();
            return $user->role == 'User Management';
        });

        /* define a manager user role */
        Gate::define('isManager', function ($user) {
            return $user->role == 'manager';
        });

        /* define a user role */
        Gate::define('isUser', function ($user) {
            return $user->role == 'user';
        });
    }
}
