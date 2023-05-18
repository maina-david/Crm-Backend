<?php

namespace App\Http;

use App\Http\Middleware\CustomerMiddleware;
use App\Http\Middleware\StaffMiddleware;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array<int, class-string|string>
     */
    protected $middleware = [
        // \App\Http\Middleware\TrustHosts::class,
        \App\Http\Middleware\TrustProxies::class,
        \Fruitcake\Cors\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,
        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,
        \App\Http\Middleware\OwnCors::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array<string, array<int, class-string|string>>
     */
    protected $middlewareGroups = [
        'web' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],

        'api' => [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,

        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array<string, class-string|string>
     */
    protected $routeMiddleware = [
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        'abilities' => \Laravel\Sanctum\Http\Middleware\CheckAbilities::class,
        'ability' => \Laravel\Sanctum\Http\Middleware\CheckForAnyAbility::class,
        'is_owner' => \App\Http\Middleware\IsOwnerMiddleware::class,
        'is_user_manager' => \App\Http\Middleware\IsUserManagerMiddleware::class,
        'is_role_profile_manager' => \App\Http\Middleware\IsRoleProfileManagerMiddleware::class,
        'is_invitation_manager' => \App\Http\Middleware\IsInvitationManagerMiddleware::class,
        'is_channel_manager' => \App\Http\Middleware\IsChannelManagerMiddleware::class,
        'is_company_manager' => \App\Http\Middleware\IsCompanyManagerMiddleware::class,
        'is_group_manager' => \App\Http\Middleware\IsGroupManagerMiddleware::class,
        'is_queue_manager' => \App\Http\Middleware\IsQueueManagementMiddleware::class,
        'is_queue_agent_manager' => \App\Http\Middleware\IsQueueAgentManagementMiddleware::class,
        'is_moh_queue_manager' => \App\Http\Middleware\IsMOHQueueManagementMiddleware::class,
        'is_moh_manager' => \App\Http\Middleware\IsMOHManagementMiddleware::class,
        'is_ivr_manager' => \App\Http\Middleware\IsIVRManagementMiddleware::class,
        'is_campaign_manager' => \App\Http\Middleware\IsCampaignManagerMiddleware::class,

        'is_chat_agent' => \App\Http\Middleware\IsChatAgentMiddleware::class,
        'is_chat_queue_manager' => \App\Http\Middleware\IsChatQueueManagerMiddleware::class,
        'is_chat_account_manager' => \App\Http\Middleware\IsChatAccountManagerMiddleware::class,
        'is_chat_flow_manager' => \App\Http\Middleware\IsChatFlowManagerMiddleware::class,

        'is_ticket_form_manager' => \App\Http\Middleware\IsTicketFormManagerMiddleware::class,
        'is_ticket_escalation_manager' => \App\Http\Middleware\IsTicketEscalationManagerMiddleware::class,
        'is_help_desk_manager' => \App\Http\Middleware\IsHelpDeskManagerMiddleware::class,
        'is_ticket_user' => \App\Http\Middleware\IsTicketUserMiddleware::class,
        'is_ticket_create_user' => \App\Http\Middleware\IsTicketCreateUserMiddleware::class,

        'is_qa_form_manager' => \App\Http\Middleware\IsQAFormManagerMiddleware::class,
        'is_qa_team_manager' => \App\Http\Middleware\IsQATeamManagerMiddleware::class,

        'type.customer' => CustomerMiddleware::class,
        'type.staff' => StaffMiddleware::class,
    ];
}