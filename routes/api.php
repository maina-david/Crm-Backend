<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use App\Http\Controllers\account\AccountController;
use App\Http\Controllers\account\AccountFormController;
use App\Http\Controllers\account\AccountTypeController;
use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\call\CallController;
use App\Http\Controllers\call\CallReportController;
use App\Http\Controllers\call\CallReportControllers;
use App\Http\Controllers\call\IntegrationController;
use App\Http\Controllers\campaign\CampaignController;
use App\Http\Controllers\ChannelController;
use App\Http\Controllers\ChatDesk\ChatAccounts\ChatAccountsController;
use App\Http\Controllers\ChatDesk\chatbot\ChatBotController;
use App\Http\Controllers\ChatDesk\chatqueue\ChatQueueController;
use App\Http\Controllers\company\CallcenterSettingController;
use App\Http\Controllers\company\CompanyController;
use App\Http\Controllers\company\GroupController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\queue\IVRController;
use App\Http\Controllers\queue\MOHController;
use App\Http\Controllers\queue\QueueController;
use App\Http\Controllers\user\AccessRightController;
use App\Http\Controllers\user\AgentController;
use App\Http\Controllers\user\InvitationController;
use App\Http\Controllers\user\UserController;
use App\Http\Controllers\contact\ContactController;
use App\Http\Controllers\contact\ContactFormController;
use App\Http\Controllers\ChatDesk\conversation\ConversationController;
use App\Http\Controllers\ChatDesk\email\EmailSettingController;
use App\Http\Controllers\ChatDesk\sms\SmsAccountController;
use App\Http\Controllers\TestEventController;
use App\Http\Controllers\ticket\EscallationMatrixController;
use App\Http\Controllers\ticket\HelpDeskTeamConfigurationController;
use App\Http\Controllers\ticket\HelpDeskTeamController;
use App\Http\Controllers\ticket\TicketController;
use App\Http\Controllers\ticket\TicketCreationFormController;
use App\Http\Controllers\ticket\TicketEscallationLevelController;
use App\Http\Controllers\ticket\TicketPriorityController;
use App\Http\Controllers\ticket\TicketReminderController;
use App\Http\Controllers\ticket\TicketReminderTypeController;
use App\Http\Controllers\ChatDesk\twitter\TwitterOauthController;
use App\Http\Controllers\ChatDesk\whatsapp\WhatsappAccountController;
use App\Http\Controllers\CentralizedFormController;
use App\Http\Controllers\ChatDesk\DashboardController;
use App\Http\Controllers\crm\CrmReportController;
use App\Http\Controllers\ChatDesk\facebook\FaceBookPageController;
use App\Http\Controllers\ChatDesk\instagram\InstagramAccountController;
use App\Http\Controllers\ChatDesk\ChatReportController;
use App\Http\Controllers\quality_assurance\QAController;
use App\Http\Controllers\quality_assurance\QATeamController;
use App\Http\Controllers\quality_assurance\QAFormController;
use App\Http\Controllers\ticket\EscalationLevelController;
use App\Http\Controllers\ticket\EscalationPointController;
use App\Http\Controllers\ticket\TicketFormController;
use App\Http\Controllers\ticket\TicketReportController;
use App\Http\Controllers\ChatDesk\twitter\TwitterAccountController;
use App\Http\Controllers\company\EmailTemplateController;
use App\Http\Controllers\knowledge_base\KnowledgeBaseController;
use App\Http\Controllers\quality_assurance\reports\AgentReportController;
use App\Http\Controllers\quality_assurance\reports\TeamReportController;
use App\Http\Controllers\SMSTamplateController;
use App\Http\Controllers\ticket\TicketDashboardController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the 'api' middleware group. Enjoy building your API!
|
*/

Route::get('get_users', [UserController::class, 'get_users']);
Route::get('testevent', [TestEventController::class, 'testevent']);

Route::prefix('user')->group(function () {
    Route::post('/signup', [UserController::class, 'create_user']);
    Route::post('/forget_password', [UserController::class, 'forget_password']);
    Route::put('/resend_signup_token', [UserController::class, 'resend_signup_token']);
    Route::post('/login', [UserController::class, 'login']);
    Route::post('/force_login', [UserController::class, 'new_session']);
    Route::put('/password_reset', [UserController::class, 'password_reset']);
    Route::put('/confirm_email', [UserController::class, 'confirm_email']);
    Route::post('/accept_invitation', [InvitationController::class, 'accept_invitation']);
});

Route::prefix('helper')->group(function () {
    Route::get('/get_country_code', [CompanyController::class, 'get_country_code']);
    Route::get('/get_languages', [CompanyController::class, 'get_languages']);
});


Route::middleware(['auth:sanctum', 'type.customer'])->group(function () {
    Route::prefix('notification')->group(
        function () {
            Route::get('/get_unreadNotifications', [NotificationController::class, 'get_unreadNotifications']);
            Route::get('/get_allNotifications', [NotificationController::class, 'get_allNotifications']);
            Route::put('/mark_as_read', [NotificationController::class, 'mark_as_read']);
            Route::delete('/clear_notification', [NotificationController::class, 'clear_notification']);
        }
    );

    Route::prefix('user')->group(
        function () {
            Route::post('/logout', [UserController::class, 'logout']);
            Route::put('/change_password', [UserController::class, 'change_password']);
            Route::get('/get_current_access_rigts', [UserController::class, 'get_current_access_rigts']);
            Route::put('/change_user_information', [UserController::class, 'change_user_information']);

            //********************************user mangement ******************************************** */
            Route::get('/all_users', [UserController::class, 'all_users']); //->middleware('is_user_manager');
            Route::get('/get_user_table', [UserController::class, 'get_user_table']); //->middleware('is_user_manager');
            Route::put('/activate_deactivate_user', [UserController::class, 'activate_deactivate_user']); //->middleware('is_user_manager');
            Route::put('/reset_user_password', [UserController::class, 'reset_user_password']); //->middleware('is_user_manager');
    
            //********************************Role profile mangement ******************************************** */
            Route::post('/create_role_profile', [AccessRightController::class, 'create_role_profile']); //->middleware('is_role_profile_manager');
            Route::put('/edit_role_profile', [AccessRightController::class, 'edit_role_profile']); //->middleware('is_role_profile_manager');
            Route::post('/assign_access_to_profile', [AccessRightController::class, 'assign_access_to_profile']); //->middleware('is_role_profile_manager');
            Route::post('/assign_access_to_profile_bulk', [AccessRightController::class, 'assign_access_to_profile_bulk']); //->middleware('is_role_profile_manager');
            Route::delete('/revoke_access_from_role_profile', [AccessRightController::class, 'revoke_access_from_role_profile']); //->middleware('is_role_profile_manager');
            Route::get('/get_access_rights', [AccessRightController::class, 'get_access_rights']);
            Route::get('/get_access_rights_table', [AccessRightController::class, 'get_access_rights_table']);
            Route::get('/get_role_profile', [AccessRightController::class, 'get_role_profile']);
            Route::get('/get_user_role_profile_table', [AccessRightController::class, 'get_user_role_profile_table']);
            Route::post('/assign_role_profile_to_user', [AccessRightController::class, 'assign_role_profile_to_user']); //->middleware('is_role_profile_manager');
            Route::get('/get_user_role_profile', [AccessRightController::class, 'get_user_role_profile']);
            Route::delete('/revoke_role_profile_from_user', [AccessRightController::class, 'revoke_role_profile_from_user']); //->middleware('is_role_profile_manager');
            Route::get('/get_access_not_in_profile', [AccessRightController::class, 'get_access_not_in_profile']);
            Route::get('/get_users_in_profile', [AccessRightController::class, 'get_users_in_profile']);
            Route::get('/has_access', [AccessRightController::class, 'has_access']);
            Route::get('/get_formated_access_rights', [AccessRightController::class, 'get_formated_access_rights']);

            //********************************invitation mangement ******************************************** */
            Route::post('/invite_users', [InvitationController::class, 'invite_users']); //->middleware('is_invitation_manager');
            Route::get('/get_all_invitations', [InvitationController::class, 'get_all_invitations']);
            Route::get('/get_all_invitations_table', [InvitationController::class, 'get_all_invitations_table']);
            Route::put('/revoke_invitation', [InvitationController::class, 'revoke_invitation']); //->middleware('is_invitation_manager');
            Route::put('/reactivate_invite', [InvitationController::class, 'reactivate_invite']); //->middleware('is_invitation_manager');
            Route::put('/resend_invitation', [InvitationController::class, 'resend_invitation']); //->middleware('is_invitation_manager');
        }
    );

    Route::prefix('company')->group(
        function () {
            Route::post('/create_compnay_once', [CompanyController::class, 'create_compnay_once']);
            //********************************company mangement ******************************************** */
            Route::post('/create_company', [CompanyController::class, 'create_company']); //->middleware('is_company_manager');
            Route::put('/edit_company', [CompanyController::class, 'edit_company']); //->middleware('is_company_manager');
            Route::post('/add_company_address', [CompanyController::class, 'add_company_address']); //->middleware('is_company_manager');
            Route::post('/add_company_contact', [CompanyController::class, 'add_company_contact']); //->middleware('is_company_manager');
            Route::get('/get_company_contact_type', [CompanyController::class, 'get_company_contact_type']);
            Route::get('/get_company_information', [CompanyController::class, 'get_company_information']);
            Route::put('/edit_company_address', [CompanyController::class, 'edit_company_address']); //->middleware('is_company_manager');
            Route::put('/edit_company_contact', [CompanyController::class, 'edit_company_contact']); //->middleware('is_company_manager');
            Route::delete('/remove_company_contact', [CompanyController::class, 'remove_company_contact']); //->middleware('is_company_manager');
    
            //********************************group mangement ******************************************** */
            Route::post('/create_group', [GroupController::class, 'create_group']); //->middleware('is_group_manager');
            Route::put('/update_group', [GroupController::class, 'update_group']); //->middleware('is_group_manager');
            Route::post('/assign_users_to_group', [GroupController::class, 'assign_users_to_group']); //->middleware('is_group_manager');
            Route::get('/get_all_groups', [GroupController::class, 'get_all_groups']);
            Route::delete('/remove_user_from_group', [GroupController::class, 'remove_user_from_group']); //->middleware('is_group_manager');
            Route::get('/get_agents_in_group', [GroupController::class, 'get_agents_in_group']);
            Route::get('/get_agents', [GroupController::class, 'get_agents']);
            Route::put('/assign_queue_group', [GroupController::class, 'assign_queue_group']);
            Route::put('/remove_group_from_queue', [GroupController::class, 'remove_group_from_queue']);
        }
    );

    Route::prefix('channel')->group(
        function () {
            //********************************channel mangement ******************************************** */
            Route::get('/view_available_dids', [ChannelController::class, 'view_available_dids']);
            Route::get('/view_available_dids_table', [ChannelController::class, 'view_available_dids_table']);
            Route::get('/get_carriers', [ChannelController::class, 'get_carriers']);
            Route::get('/get_allocated_dids', [ChannelController::class, 'get_allocated_dids']);
            Route::get('/get_allocated_dids_table', [ChannelController::class, 'get_allocated_dids_table']);
            Route::put('/assign_phone_number', [ChannelController::class, 'assign_phone_number']); //->middleware('is_channel_manager');
            Route::put('/remove_phone_number', [ChannelController::class, 'remove_phone_number']); //->middleware('is_channel_manager');
            Route::get('/get_did_with_ivr', [ChannelController::class, 'get_did_with_ivr']);
            Route::get('/get_did_without_ivr', [ChannelController::class, 'get_did_without_ivr']);
        }
    );

    Route::prefix('queue')->group(
        function () {
            Route::post('/create_queue', [QueueController::class, 'create_queue']); //->middleware('is_queue_manager');
            Route::put('/edit_queue', [QueueController::class, 'edit_queue']); //->middleware('is_queue_manager');
            Route::put('/activate_queue', [QueueController::class, 'activate_queue']); //->middleware('is_queue_manager');
    
            Route::get('/get_all_queues', [QueueController::class, 'get_all_queues']);
            Route::get('/get_all_queues_table', [QueueController::class, 'get_all_queues_table']);
            Route::get('/get_agents_in_queue', [QueueController::class, 'get_agents_in_queue']);
            Route::get('/get_unsigned_queues', [QueueController::class, 'get_unsigned_queues']);
            Route::get('/get_queue_by_group', [QueueController::class, 'get_queue_by_group']);
            ///assign agents to queue
            Route::post('/assign_agents_to_queue', [QueueController::class, 'assign_agents_to_queue']); //->middleware('is_queue_manager');
            Route::delete('/remove_agent_queue', [QueueController::class, 'remove_agent_queue']); //->middleware('is_queue_manager');
    
            /////asign MOH to Queue
            Route::put('/assign_moh_to_queue', [QueueController::class, 'assign_moh_to_queue']); //->middleware('is_queue_manager');
    
            ///////////////moh acccess
            Route::post('/create_moh', [MOHController::class, 'create_moh']); //->middleware('is_moh_manager');
            Route::put('/update_moh', [MOHController::class, 'update_moh']); //->middleware('is_moh_manager');
            Route::post('/create_moh_file_bulk', [MOHController::class, 'create_moh_file_bulk']); //->middleware('is_moh_manager');
            Route::put('/rename_moh_file', [MOHController::class, 'rename_moh_file']); //->middleware('is_moh_manager');
            Route::get('/get_all_moh', [MOHController::class, 'get_all_moh']);
            Route::delete('/remove_moh_file', [MOHController::class, 'remove_moh_file']); //->middleware('is_moh_manager');
            Route::put('/re_order_files', [MOHController::class, 're_order_files']); //->middleware('is_moh_manager');
        }
    );

    Route::prefix('ivr')->group(
        function () {
            Route::post('/create_ivr', [IVRController::class, 'create_ivr']);
            Route::put('/update_ivr', [IVRController::class, 'update_ivr']);
            Route::get('/get_all_ivrs', [IVRController::class, 'get_all_ivrs']);
            Route::get('/get_ivr_files_table', [IVRController::class, 'get_ivr_files_table']);
            Route::put('/map_ivr_to_did', [IVRController::class, 'map_ivr_to_did']);
            Route::put('/delink_ivr_to_did', [IVRController::class, 'delink_ivr_to_did']);
            Route::post('/upload_ivr_files', [IVRController::class, 'upload_ivr_files']);
            Route::get('/get_ivr_files', [IVRController::class, 'get_ivr_files']);
            Route::post('/add_ivr_flow', [IVRController::class, 'add_ivr_flow']);
            Route::get('/get_ivr_json', [IVRController::class, 'get_ivr_json']);
            Route::delete('/remove_ivr_files', [IVRController::class, 'remove_ivr_files']);
        }
    );

    Route::prefix('helper')->group(
        function () {
            Route::get('/get_activity_log', [ActivityLogController::class, 'get_activity_log']);
        }
    );

    Route::prefix('call_action')->group(
        function () {
            Route::put('/cancel_call', [CallController::class, 'cancel_call']);
            Route::put('/hangup_click_tocall', [CallController::class, 'hangup_click_tocall']);
            Route::put('/hungup_transfer', [CallController::class, 'hungup_transfer']);
            Route::put('/mute_call', [CallController::class, 'mute_call']);
            Route::put('/mute_call_transfer', [CallController::class, 'mute_call_transfer']);
            Route::put('/hold_call', [CallController::class, 'hold_call']);
            Route::put('/call_hold_transfer', [CallController::class, 'call_hold_transfer']);
            Route::post('/click_to_call', [CallController::class, 'click_to_call']);
            Route::post('/call_sip_to_sip', [CallController::class, 'call_sip_to_sip']);
            Route::post('/call_transfer', [CallController::class, 'call_transfer']);
            Route::post('/agent_answered_campaign', [CallController::class, 'agent_answered_campaign']);
            Route::get('/get_caller_information', [CallController::class, 'get_caller_information']);
            Route::get('/get_transfer_caller_information', [CallController::class, 'get_transfer_caller_information']);

            Route::get('/get_total_call_inprogress', [CallReportController::class, 'get_total_call_inprogress']);
            Route::get('/calls_per_queue_daily', [CallReportController::class, 'calls_per_queue_daily']);
            Route::get('/get_queue_kpi_daily', [CallReportController::class, 'get_queue_kpi_daily']);
            Route::get('/get_call_abandonment_rate_daily', [CallReportController::class, 'get_call_abandonment_rate_daily']);
            Route::get('/calls_per_agent_daily', [CallReportController::class, 'calls_per_agent_daily']);
            Route::get('/get_agent_kpi_daily', [CallReportController::class, 'get_agent_kpi_daily']);
            Route::get('/get_agent_call_abandonment_rate_daily', [CallReportController::class, 'get_agent_call_abandonment_rate_daily']);

            Route::get('/get_agent_progress_daily', [CallReportController::class, 'get_agent_progress_daily']);
            Route::get('/agent_queue_daily', [CallReportController::class, 'agent_queue_daily']);
        }
    );

    Route::prefix('call_report')->group(
        function () {
            Route::get('/queue_report', [CallReportControllers::class, 'queue_report']);
            Route::get('/cdr_report', [CallReportControllers::class, 'cdr_report']);
            Route::get('/get_agent_call_report', [CallReportControllers::class, 'get_agent_call_report']);
            Route::get('/get_agent_activity_report', [CallReportControllers::class, 'get_agent_activity_report']);
            Route::get('/get_ivr_hit_report', [CallReportControllers::class, 'get_ivr_hit_report']);
            Route::get('/get_ivr_background', [CallReportControllers::class, 'get_ivr_background']);
            Route::get('/get_service_level_daily', [CallReportController::class, 'get_service_level_daily']);
            Route::get('/get_first_call_resolution_daily', [CallReportController::class, 'get_first_call_resolution_daily']);
            Route::get('/click_to_call_report', [CallReportControllers::class, 'click_to_call_report']);
        }
    );

    Route::prefix('agent')->group(
        function () {
            Route::get('/get_active_agents', [AgentController::class, 'get_active_agents']);
            Route::get('/get_agent_status', [AgentController::class, 'get_agent_status']);
            Route::get('/get_all_sip', [AgentController::class, 'get_all_sip']);
            Route::get('/get_all_sip_modified', [AgentController::class, 'get_all_sip_modified']);
            Route::get('/get_agent_with_click_to_call', [AgentController::class, 'get_agent_with_click_to_call']);

            Route::post('/create_break', [AgentController::class, 'create_break']);
            Route::put('/update_break', [AgentController::class, 'update_break']);
            Route::put('/update_break_status', [AgentController::class, 'update_break_status']);
            Route::get('/get_all_breaks', [AgentController::class, 'get_all_breaks']);
            Route::get('/get_active_break', [AgentController::class, 'get_active_break']);
            Route::post('/take_break', [AgentController::class, 'take_break']);
            Route::post('/resume_from_break', [AgentController::class, 'resume_from_break']);
            Route::post('/assign_did_to_agent', [AgentController::class, 'assign_did_to_agent']);
            Route::get('/get_call_log', [AgentController::class, 'get_call_log']);
            Route::put('/reset_penality', [AgentController::class, 'reset_penality']);
        }
    );

    Route::prefix('callcenter_setting')->group(
        function () {
            Route::put('/setup_penality_sl', [CallcenterSettingController::class, 'setup_penality_sl']);
            Route::put('/switch_callcenter_off', [CallcenterSettingController::class, 'switch_callcenter_off']);
            Route::get('/get_callcenter_basic_settings', [CallcenterSettingController::class, 'get_callcenter_basic_settings']);

            Route::post('/add_to_blacklist', [CallcenterSettingController::class, 'add_to_blacklist']);
            Route::delete('/remove_from_blacklist', [CallcenterSettingController::class, 'remove_from_blacklist']);
            Route::get('/get_blacklisted', [CallcenterSettingController::class, 'get_blacklisted']);


            Route::post('/add_callcenter_setting_audio', [CallcenterSettingController::class, 'add_callcenter_setting_audio']);
            Route::put('/edit_callcenter_setting_audio_name', [CallcenterSettingController::class, 'edit_callcenter_setting_audio_name']);
            Route::get('/get_callcenter_setting_audio', [CallcenterSettingController::class, 'get_callcenter_setting_audio']);
            Route::delete('/delete_callcenter_setting_audio', [CallcenterSettingController::class, 'delete_callcenter_setting_audio']);

            Route::put('/update_working_hour', [CallcenterSettingController::class, 'update_working_hour']);
            Route::get('/get_working_hour', [CallcenterSettingController::class, 'get_working_hour']);

            Route::post('/add_contactcenter_holiday', [CallcenterSettingController::class, 'add_contactcenter_holiday']);
            Route::put('/update_holiday', [CallcenterSettingController::class, 'update_holiday']);
            Route::delete('/remove_holiday', [CallcenterSettingController::class, 'remove_holiday']);
            Route::get('/get_holidays', [CallcenterSettingController::class, 'get_holidays']);
            Route::put('/callcenter_off_music', [CallcenterSettingController::class, 'callcenter_off_music']);
            Route::get('/get_callcenter_off', [CallcenterSettingController::class, 'get_callcenter_off']);
        }
    );

    Route::prefix("account")->group(
        function () {
            Route::post("/create_account_type", [AccountTypeController::class, "create_account_type"]);
            Route::put("/edit_account_type", [AccountTypeController::class, "edit_account_type"]);
            Route::get("/get_account_type", [AccountTypeController::class, "get_account_type"]);
            Route::get("/get_account_type_table", [AccountTypeController::class, "get_account_type_table"]);
            Route::post("/assign_account_to_group", [AccountTypeController::class, "assign_account_to_group"]);
            Route::delete("/remove_group_account_type", [AccountTypeController::class, "remove_group_account_type"]);

            Route::post('/create_account_form', [AccountFormController::class, 'create_account_form']);
            Route::put('/edit_account_form', [AccountFormController::class, 'edit_account_form']);
            Route::put('/assign_account_form_to_account_type', [AccountFormController::class, 'assign_account_form_to_account_type']);
            Route::post('/create_account_form_items', [AccountFormController::class, 'create_account_form_items']);
            Route::put('/update_account_form_items', [AccountFormController::class, 'update_account_form_items']);
            Route::post('/add_account_form_item', [AccountFormController::class, 'add_account_form_item']);
            Route::put('/update_form_sequence', [AccountFormController::class, 'update_form_sequence']);

            Route::get('/get_account_forms', [AccountFormController::class, 'get_account_forms']);
            Route::post('/get_account_form_items', [AccountFormController::class, 'get_account_form_items']);

            ////////////account
            Route::post('/associate_account_to_social', [AccountController::class, 'associate_account_to_social']);
            Route::post('/create_account_number', [AccountController::class, 'create_account_number']);
            Route::put('/update_account_number', [AccountController::class, 'update_account_number']);
            Route::get('/get_account_numbers', [AccountController::class, 'get_account_numbers']);
            Route::put('/assign_account_number_to_account_type', [AccountController::class, 'assign_account_number_to_account_type']);

            Route::post('/create_account', [AccountController::class, 'create_account']);
            Route::post('/account_type_form', [AccountController::class, 'account_type_form']);
            Route::post('/migrate_campaign_contacts_by_filter', [AccountController::class, 'migrate_campaign_contacts_by_filter']);
            Route::put('/update_account', [AccountController::class, 'update_account']);
            Route::get('/get_account_detail', [AccountController::class, 'get_account_detail']);
            Route::get('/get_account_detail_modified', [AccountController::class, 'get_account_detail_modified']);
            Route::get('/get_accounts', [AccountController::class, 'get_accounts']);
            Route::get('/get_account_pending', [AccountController::class, 'get_account_pending']);
            Route::get('/get_account_detail_pending', [AccountController::class, 'get_account_detail_pending']);
            Route::put('/account_approve_request', [AccountController::class, 'account_approve_request']);

            Route::get("/account_type_report", [CrmReportController::class, "account_type_report"]);
            Route::get("/general_account_report", [CrmReportController::class, "general_account_report"]);
            Route::get("/campaign_report", [CrmReportController::class, "campaign_report"]);
            Route::get("/survey_report_table", [CrmReportController::class, "survey_report_table"]);
            Route::get("/survey_report", [CrmReportController::class, "survey_report"]);
        }
    );

    Route::prefix('contact')->group(
        function () {
            Route::post('/create_contact_form', [ContactFormController::class, 'create_contact_form']);
            Route::put('/update_contact_form', [ContactFormController::class, 'update_contact_form']);
            Route::get('/get_contact_form', [ContactFormController::class, 'get_contact_form']);
            Route::put('/assign_contact_form_account_type', [ContactFormController::class, 'assign_contact_form_account_type']);

            Route::post('/create_contact_form_items', [ContactFormController::class, 'create_contact_form_items']);
            Route::post('/add_contact_form_items', [ContactFormController::class, 'add_contact_form_items']);
            Route::put('/update_contact_form_items', [ContactFormController::class, 'update_contact_form_items']);
            Route::put('/update_form_sequence', [ContactFormController::class, 'update_form_sequence']);
            Route::get('/get_contact_form_items', [ContactFormController::class, 'get_contact_form_items']);

            Route::post('/create_contact', [ContactController::class, 'create_contact']);
            Route::get('/get_contacts', [ContactController::class, 'get_contacts']);
            Route::get('/get_contact_detail', [ContactController::class, 'get_contact_detail']);
            Route::get('/get_contact_detail_modified', [ContactController::class, 'get_contact_detail_modified']);
            Route::get('/get_contact_pending', [ContactController::class, 'get_contact_pending']);
            Route::get('/get_account_detail_pending', [ContactController::class, 'get_account_detail_pending']);
            Route::put('/contact_approve_request', [ContactController::class, 'contact_approve_request']);
            Route::put('/update_contact', [ContactController::class, 'update_contact']);
        }
    );

    Route::prefix("campaign")->group(
        function () {
            Route::get("/get_campaign_type", [CampaignController::class, "get_campaign_type"]);
            Route::post("/create_campaign", [CampaignController::class, "create_campaign"]); //->middleware("is_campaign_manager");
            Route::put("/update_campaign", [CampaignController::class, "update_campaign"]); //->middleware("is_campaign_manager");
            Route::put("/change_campaign_status", [CampaignController::class, "change_campaign_status"]); //->middleware("is_campaign_manager");
            Route::put("/add_group_to_campaign", [CampaignController::class, "add_group_to_campaign"]); //->middleware("is_campaign_manager");
            Route::delete("/remove_group_from_campaign", [CampaignController::class, "remove_group_from_campaign"]); //->middleware("is_campaign_manager");
            Route::post("/add_campaign_working_hour", [CampaignController::class, "add_campaign_working_hour"]); //->middleware("is_campaign_manager");
            Route::delete("/remove_campaign_working_hour", [CampaignController::class, "remove_campaign_working_hour"]); //->middleware("is_campaign_manager");
            Route::put("/campaign_setting_setup", [CampaignController::class, "campaign_setting_setup"]); //->middleware("is_campaign_manager");
            Route::post("/upload_contact_campaign", [CampaignController::class, "upload_contact_campaign"]); //->middleware("is_campaign_manager");
            Route::post("/select_contact_for_campaign", [CampaignController::class, "select_contact_for_campaign"]); //->middleware("is_campaign_manager");
            Route::post("/create_campaign_once", [CampaignController::class, "create_campaign_once"]); //->middleware("is_campaign_manager");
            Route::get("/get_campaigns", [CampaignController::class, "get_campaigns"]);
            Route::get("/get_campaign_working_hour", [CampaignController::class, "get_campaign_working_hour"]);
            Route::get("/get_sender_id", [CampaignController::class, "get_sender_id"]);
            Route::get("/get_queue_from_camapaign", [CampaignController::class, "get_queue_from_camapaign"]);
            Route::get("/get_campaign_contact", [CampaignController::class, "get_campaign_contact"]);
            Route::post("/filter_campain_contacts", [CampaignController::class, "filter_campain_contacts"]); //->middleware("is_campaign_manager");
            Route::post("/add_question_camapign", [CampaignController::class, "add_question_camapign"]); //->middleware("is_campaign_manager");
            Route::post("/survey_submit", [CampaignController::class, "survey_submit"]);
        }
    );

    ##Ticket Module
    #######################################################################
    Route::prefix('helpdesk')->group(
        function () {
            Route::prefix('team')->group(
                function () {
                        Route::post('/activate', [HelpDeskTeamController::class, 'activateTeam']); //->middleware("is_help_desk_manager");
                        Route::post('/deactivate', [HelpDeskTeamController::class, 'deactivateTeam']); //->middleware("is_help_desk_manager");
                        Route::post('/users', [HelpDeskTeamController::class, 'team_users']); //->middleware("is_help_desk_manager");
                        Route::post('/add_user', [HelpDeskTeamController::class, 'addUserToTeam']); //->middleware("is_help_desk_manager");
                        Route::get('/user_teams', [HelpDeskTeamController::class, 'viewUserTeams']);
                        Route::post('/remove_user', [HelpDeskTeamController::class, 'removeUserFromTeam']); //->middleware("is_help_desk_manager");
                    }
            );
        }
    );
    Route::apiResource('helpDeskTeams', HelpDeskTeamController::class);

    Route::apiResource('helpDeskTeamConfigurations', HelpDeskTeamConfigurationController::class);

    Route::apiResource('escalationPoints', EscalationPointController::class);

    Route::post('/addPriorityToEscallationPoint', [EscalationPointController::class, 'addPriority']);

    Route::post('/addEscalationMatrixToEscallationPoint', [EscalationPointController::class, 'addEscallationMatrix']);

    Route::post('/EscalationPointLevels', [EscalationLevelController::class, 'index']);

    Route::apiResource('escalationLevels', EscalationLevelController::class, ['except' => ['index']]);

    Route::apiResource('ticketEscallationLevels', TicketEscallationLevelController::class); //->middleware("is_ticket_escalation_manager");

    Route::apiResource('ticketPriorities', TicketPriorityController::class); //->middleware("is_ticket_escalation_manager");

    Route::apiResource('ticketReminderTypes', TicketReminderTypeController::class);

    Route::apiResource('ticketReminders', TicketReminderController::class);

    Route::apiResource('ticketCreationForms', TicketCreationFormController::class);

    Route::post('/ticketCreationFormComponents', [TicketCreationFormController::class, 'storeFormComponents']);

    #######################################################################
    #Ticket Management
    Route::prefix('tickets')->group(
        function () {
            Route::get('/companyTickets', [TicketController::class, 'index']);
            Route::get('/createdTickets', [TicketController::class, 'createdTickets']);
            Route::get('/assignedTickets', [TicketController::class, 'assignedTickets']);
            Route::post('/create_ticket_form', [TicketFormController::class, 'create_ticket_form']); //->middleware("is_ticket_form_manager");
            Route::post('/update_ticket_form', [TicketFormController::class, 'update_ticket_form']); //->middleware("is_ticket_form_manager");
            Route::post('/activate_ticket_form', [TicketFormController::class, 'activateTicketForm']); //->middleware("is_ticket_form_manager");
            Route::post('/deactivate_ticket_form', [TicketFormController::class, 'deactivateTicketForm']); //->middleware("is_ticket_form_manager");
            Route::get('/active_ticket_form', [TicketFormController::class, 'activeTicketForm']); //->middleware("is_ticket_form_manager");
            Route::post('/add_items_to_ticket_form', [TicketFormController::class, 'add_items_to_ticket_form']); //->middleware("is_ticket_form_manager");
            Route::get('/get_ticket_form', [TicketFormController::class, 'get_ticket_form']);
            Route::get('/get_form_items', [TicketFormController::class, 'get_form_items']);
            Route::get('/get_ticket_form_json', [TicketFormController::class, 'get_ticket_form_json']);
            Route::post('/create_ticket', [TicketFormController::class, 'create_ticket']);
            Route::post('/add_ticket_interactions', [TicketFormController::class, 'add_ticket_interactions']);
            Route::post('/get_ticket_details', [TicketController::class, 'ticketDetails']);
            Route::post('/notes', [TicketController::class, 'ticketNotes']);
            Route::post('/resolve', [TicketController::class, 'resolveTicket']);
            Route::apiResource('escallationMatrices', EscallationMatrixController::class);
            Route::post('/activateEscallationMatrix/{id}', [EscallationMatrixController::class, 'activateEscallationMatrix']);
            Route::post('/deactivateEscallationMatrix/{id}', [EscallationMatrixController::class, 'deactivateEscallationMatrix']);
            Route::post('/addEscallationMatrix', [EscalationPointController::class, 'addEscallationMatrix']);

            // Escalation Forms
            Route::post('/escalate_ticket', [TicketController::class, 'escalate_ticket']);

            //Tickets Dashboard
            Route::prefix('dashboard')->group(
                function () {
                    Route::controller(TicketDashboardController::class)->group(
                        function () {
                                    Route::get('/ticket_statistics', 'ticketStatistics');
                                    Route::get('/agent_data_analytics', 'AgentDataAnalytics');
                                    Route::get('/agent_sla_report', 'AgentSLAReport');
                                    Route::get('/agent_resolution_rate', 'AgentResolutionRate');

                                    Route::get('/helpdesk_data_analytics', 'HelpDeskDataAnalytics');
                                    Route::get('/helpdesk_sla_report', 'HelpDeskSLAReport');
                                    Route::get('/helpdesk_resolution_rate', 'HelpDeskResolutionRate');
                                }
                    );
                }
            );

            //Reports
            Route::prefix('reports')->group(
                function () {
                    Route::get('/resolution_report', [TicketReportController::class, 'resolutionReport']);
                    Route::get('/sla_report', [TicketReportController::class, 'slaReport']);
                }
            );
        }
    );

    #chatdesk Module
    #######################################################################
    Route::prefix('dashboard')->group(
        function () {
            Route::controller(DashboardController::class)->group(
                function () {
                        Route::get('/conversations_statistics', 'conversations_statistics');
                        Route::get('/channel_all_conversations_statistics', 'channel_all_conversations_statistics');
                        Route::get('/channel_open_conversations_statistics', 'channel_open_conversations_statistics');
                        Route::get('/channel_closed_conversations_statistics', 'channel_closed_conversations_statistics');
                        Route::get('/queue_all_conversations_statistics', 'queue_all_conversations_statistics');
                        Route::get('/queue_open_conversations_statistics', 'queue_open_conversations_statistics');
                        Route::get('/queue_closed_conversations_statistics', 'queue_closed_conversations_statistics');
                        Route::get('/agent_all_conversations_statistics', 'agent_all_conversations_statistics');
                        Route::get('/agent_open_conversations_statistics', 'agent_open_conversations_statistics');
                        Route::get('/agent_closed_conversations_statistics', 'agent_closed_conversations_statistics');
                    }
            );
        }
    );
    Route::prefix('channels')->group(
        function () {
            Route::get('/list_supported', [ChatQueueController::class, 'channels']);
            Route::get('/sms_providers', [SmsAccountController::class, 'smsProviders']);
            Route::get('/companyAccounts', [ChatAccountsController::class, 'index']);
            Route::apiResource('whatsappAccounts', WhatsappAccountController::class); //->middleware("is_chat_account_manager");
            Route::apiResource('faceBookPages', FaceBookPageController::class); //->middleware("is_chat_account_manager");
            Route::apiResource('instagramAccounts', InstagramAccountController::class); //->middleware("is_chat_account_manager");
            Route::apiResource('twitterAccounts', TwitterAccountController::class); //->middleware("is_chat_account_manager");
            Route::apiResource('SmsAccounts', SmsAccountController::class); //->middleware("is_chat_account_manager");
            Route::apiResource('emailSettings', EmailSettingController::class); //->middleware("is_chat_account_manager");
        }
    );

    Route::apiResource('chatQueues', ChatQueueController::class); //->middleware("is_chat_queue_manager");
    Route::prefix('chatQueue')->group(
        function () {
            Route::post('/associate_email', [ChatQueueController::class, 'associateEmail']);
            Route::post('/activate', [ChatQueueController::class, 'activateChatQueue']); //->middleware("is_chat_queue_manager");
            Route::post('/deactivate', [ChatQueueController::class, 'deactivateChatQueue']); //->middleware("is_chat_queue_manager");
            Route::post('/addUser', [ChatQueueController::class, 'addUser']); //->middleware("is_chat_queue_manager");
            Route::post('/removeUser', [ChatQueueController::class, 'removeUser']); //->middleware("is_chat_queue_manager");
            Route::post('/add/Autoreply/message', [ChatQueueController::class, 'addMessage']); //->middleware("is_chat_queue_manager");
            Route::post('/remove/Autoreply/message', [ChatQueueController::class, 'removeMessage']); //->middleware("is_chat_queue_manager");
        }
    );

    Route::prefix('chatbot')->group(
        function () {
            Route::post('/Accounts/filterByChannel', [ChatBotController::class, 'listSocialAccount']); //->middleware("is_chat_flow_manager");
            Route::post('/link/Account', [ChatBotController::class, 'createChatbotAccount']); //->middleware("is_chat_flow_manager");
            Route::post('/listLinkedAccounts', [ChatBotController::class, 'getAccounts']);
            Route::post('/createChatbot', [ChatBotController::class, 'createChatbot']); //->middleware("is_chat_flow_manager");
            Route::post('/updateChatbot', [ChatBotController::class, 'updateChatbot']); //->middleware("is_chat_flow_manager");
            Route::get('/listChatbots', [ChatBotController::class, 'listChatbots']);
            Route::post('/uploadChatbotFiles', [ChatBotController::class, 'uploadChatbotFiles']); //->middleware("is_chat_flow_manager");
            Route::get('/getChatbotFiles', [ChatBotController::class, 'getChatbotFiles']);
            Route::post('/deleteChatbotFile', [ChatBotController::class, 'deleteChatbotFile']); //->middleware("is_chat_flow_manager");
            Route::post('/addChatbotFLow', [ChatBotController::class, 'addChatbotFLow']); //->middleware("is_chat_flow_manager");
            Route::post('/getChatBotJSON', [ChatBotController::class, 'getChatBotJSON']);
        }
    );

    Route::prefix('Conversation')->group(
        function () {
            Route::get('/Channels', [ConversationController::class, 'conversationChannels']);
            Route::get('/listAssigned', [ConversationController::class, 'assignedConversations']);
            Route::post('/listByChannel', [ConversationController::class, 'assignedConversationsPerChannel']);
            Route::post('/listMessages', [ConversationController::class, 'conversationMessages']);
            Route::get('/list', [ConversationController::class, 'index']);
            Route::post('/reply', [ConversationController::class, 'replyMessage']); //->middleware("is_chat_agent");
            Route::post('/Message/markAsRead', [ConversationController::class, 'markAsRead']); //->middleware("is_chat_agent");
            Route::post('/close', [ConversationController::class, 'closeConversation']); //->middleware("is_chat_agent");
            Route::post('/returnToQueue', [ConversationController::class, 'returnToQueue']); //->middleware("is_chat_agent");
            Route::post('/associate_contact_to_account', [ConversationController::class, 'associate_contact_to_account']); //->middleware("is_chat_agent");
    
            //Reports
            Route::prefix('reports')->group(
                function () {
                    Route::get('/channel_chat_volume_trend', [ChatReportController::class, 'ChannelChatVolumeTrend']);
                    Route::get('/queue_chat_volume_trend', [ChatReportController::class, 'QueueChatVolumeTrend']);
                    Route::get('/agent_chat_volume_trend', [ChatReportController::class, 'AgentChatVolumeTrend']);
                }
            );
        }
    );
    #######################################################################

    Route::controller(CentralizedFormController::class)->group(
        function () {
            Route::prefix('Centralized')->group(
                function () {
                        Route::post('/create_form', 'create_form');
                        Route::put('/edit_form/{id}', 'edit_form');
                        Route::post('/create_form_items', 'createFormItems');
                        Route::put('/update_form_items', 'update_form_items');
                        Route::post('/add_form_item', 'add_form_item');
                        Route::put('/update_form_sequence', 'update_form_sequence');
                        Route::get('/get_forms', 'get_forms');
                        Route::get('/get_form_items', 'get_form_items');
                        Route::get('/helpdesk_forms', 'helpdesk_forms');
                    }
            );
        }
    );

    ######################################################################
    Route::apiResource('q_a_form', QAFormController::class); //->middleware('is_qa_form_manager');
    Route::prefix('quality_assurances')->group(
        function () {
            Route::controller(QAController::class)->group(
                function () {
                        Route::prefix('settings')->group(
                            function () {
                                            Route::get('list', 'listQASettings');
                                            Route::post('save', 'saveQASettings');
                                            Route::put('update/{id}', 'updateQASettings');
                                            Route::delete('delete/{id}', 'deleteQASettings');
                                        }
                        );
                        Route::get('list_open_reviews', 'list_open_reviews');
                        Route::get('list_closed_reviews', 'list_closed_reviews');
                        Route::get('get_interaction_details', 'get_interaction_details');
                        Route::get('show_review_details', 'show_review_details');
                    }
            );

            Route::controller(QAFormController::class)->group(
                function () {
                        Route::post('create_q_a_form', 'create_q_a_form'); //->middleware('is_qa_form_manager');
                        Route::put('update_q_a_form', 'update_q_a_form'); //->middleware('is_qa_form_manager');
                        Route::get('get_q_a_form', 'get_q_a_form');
                        Route::post('add_items_to_qa_form', 'add_items_to_qa_form'); //->middleware('is_qa_form_manager');
                        Route::put('update_items_to_qa_form', 'update_items_to_qa_form'); //->middleware('is_qa_form_manager');
                        Route::get('get_qa_form_by_id', 'get_qa_form_by_id');
                        Route::post('add_qa_form_response', 'add_qa_form_response');
                    }
            );

            Route::controller(QATeamController::class)->group(
                function () {
                        Route::post('create_q_a_team', 'create_q_a_team'); //->middleware('is_qa_team_manager');
                        Route::put('update_q_a_team', 'update_q_a_team'); //->middleware('is_qa_team_manager');
                        Route::get('get_q_a_teams', 'get_q_a_teams');
                        Route::get('get_qa_users', 'get_qa_users');
                        Route::put('add_form_to_q_a_team', 'add_form_to_q_a_team'); //->middleware('is_qa_team_manager');
                        Route::post('add_members_to_qa_team', 'add_members_to_qa_team'); //->middleware('is_qa_team_manager');
                        Route::delete('remove_member_from_qa_team', 'remove_member_from_qa_team'); //->middleware('is_qa_team_manager');
                        Route::post('add_supervisor_to_qa_team', 'add_supervisor_to_qa_team'); //->middleware('is_qa_team_manager');
                        Route::delete('remove_supervisor_from_qa_team', 'remove_supervisor_from_qa_team'); //->middleware('is_qa_team_manager');
                        Route::post('add_queue_to_qa_team', 'add_queue_to_qa_team'); //->middleware('is_qa_team_manager');
                        Route::delete('remove_queue_from_qa_team', 'remove_queue_from_qa_team'); //->middleware('is_qa_team_manager');
                        Route::patch('toggle_qa_member_status', 'toggle_qa_member_status'); //->middleware('is_qa_team_manager');
                    }
            );

            Route::prefix('reports')->group(
                function () {
                        Route::controller(AgentReportController::class)->group(
                            function () {
                                            Route::get('/agentAverageReviewReport', 'agentAverageReviewReport');
                                            Route::get('/agentReportPerAttribute', 'agentReportPerAttribute');
                                            Route::get('/agentDetailedReport', 'agentDetailedReport');
                                        }
                        );
                        Route::controller(TeamReportController::class)->group(
                            function () {
                                            Route::get('/teamPerformanceReport', 'teamPerformanceReport');
                                            Route::get('/teamMemberReviews', 'teamMemberReviews');
                                            Route::get('/reviewerScoringReport', 'reviewerScoringReport');
                                            Route::get('/individualReviewerReport', 'individualReviewerReport');
                                        }
                        );
                    }
            );
        }
    );

    ######################################################################
    ##Workflow Management###

    ##Workflow Management###

    ######################################################################
    ##Knowledge base###
    Route::prefix('knowledge_base')->group(
        function () {
            Route::post('create_key_word', [KnowledgeBaseController::class, 'create_key_word']);
            Route::get('get_key_words', [KnowledgeBaseController::class, 'get_key_words']);
            Route::post('create_knowledge_base', [KnowledgeBaseController::class, 'create_knowledge_base']);
            Route::put('update_knowledge_base', [KnowledgeBaseController::class, 'update_knowledge_base']);
            Route::get('get_knowledge_base_list', [KnowledgeBaseController::class, 'get_knowledge_base_list']);
            Route::get('get_knowledge_base_stage_list', [KnowledgeBaseController::class, 'get_knowledge_base_stage_list']);
            Route::get('get_knowledge_base', [KnowledgeBaseController::class, 'get_knowledge_base']);
            Route::get('get_knowledge_base_stage', [KnowledgeBaseController::class, 'get_knowledge_base_stage']);
            Route::put('approve_knowledge_base', [KnowledgeBaseController::class, 'approve_knowledge_base']);
        }
    );
    ##Knowledge base###

    ######################################################################
    ##SMS template###
    Route::prefix('sms_template')->group(
        function () {
            Route::post('create_sms_template', [SMSTamplateController::class, 'create_sms_template']);
            Route::put('update_sms_template', [SMSTamplateController::class, 'update_sms_template']);
            Route::get('get_sms_template', [SMSTamplateController::class, 'get_sms_template']);
            Route::get('get_sms_template_detail', [SMSTamplateController::class, 'get_sms_template_detail']);
            Route::post('send_sms_template', [SMSTamplateController::class, 'send_sms_template']);
            Route::delete('delete_sms_template', [SMSTamplateController::class, 'delete_sms_template']);
        }
    );
    ##SMS template###

    ######################################################################
    ##IntegrationController###
    Route::prefix('integration')->group(
        function () {
            Route::post('create_call_integration', [IntegrationController::class, 'create_call_integration']);
            Route::get('get_call_integrations', [IntegrationController::class, 'get_call_integrations']);
            Route::get('get_call_integration', [IntegrationController::class, 'get_call_integration']);
            Route::put('update_call_integration', [IntegrationController::class, 'update_call_integration']);
            Route::delete('delete_call_integration', [IntegrationController::class, 'delete_call_integration']);
        }
    );
    ##IntegrationController###

    Route::apiResource('emailTemplates', EmailTemplateController::class);
    Route::prefix('emailtemplate')->group(
        function () {
            Route::controller(EmailTemplateController::class)->group(
                function () {
                        Route::post('/activate', 'activateTemplate');
                        Route::post('/deactivate', 'deactivateTemplate');
                    }
            );
        }
    );
});


Route::webhooks('CallWebhookHandler', 'CallWebhookHandler');
Route::webhooks('DTMFWebhook', 'DTMFWebhook');
Route::webhooks('MusicStoppedWebhook', 'MusicStoppedWebhook');
Route::webhooks('HangupWebhook', 'HangupWebhook');
Route::webhooks('AgentAnsweredHandler', 'AgentAnsweredHandler');
Route::webhooks('AgentHangupHandler', 'AgentHangupHandler');
Route::webhooks('OutboundCallHandler', 'OutboundCallHandler');
Route::webhooks('ClickToCallHandler', 'ClickToCallHandler');
Route::webhooks('BusyCallHandler', 'BusyCallHandler');

//Accepts only post requests
Route::webhooks('WhatsAppWebhook', 'WhatsAppWebhookHandler');
Route::webhooks('FacebookWebhook', 'FacebookWebhookHandler');
Route::webhooks('InstagramWebhook', 'InstagramWebhookHandler');
Route::webhooks('Webhook/Twitter', 'TwitterWebhookHandler');

//validate whatsapp webhook
Route::get('/WhatsAppWebhook', function (Request $request) {
    if ($request->hub_challenge) {
        return $request->hub_challenge;
    }
});
//validate facebook webhook
Route::get('/FacebookWebhook', function (Request $request) {
    if ($request->hub_challenge) {
        return $request->hub_challenge;
    }
});
//validate instagram webhook
Route::get('/InstagramWebhook', function (Request $request) {
    if ($request->hub_challenge) {
        return $request->hub_challenge;
    }
});
//validate twitter webhook
Route::get('/Webhook/Twitter', function (Request $request) {
    if ($request['crc_token']) {
        $signature = hash_hmac('sha256', $request['crc_token'], 'NzCAm2oaQbrVXUN3meXL7vFm25MAvQtYMlqbwXTB3aHBBcfwfc', true);
        $response['response_token'] = 'sha256=' . base64_encode($signature);
        print json_encode($response);
    }
});

Route::prefix('Twitter')->group(function () {
    Route::get('/registerWebhook', [TwitterOauthController::class, 'registerWebhook']);
    Route::get('/subscribeToWebhook', [TwitterOauthController::class, 'subscribeToWebhook']);
    Route::get('/Webhooks', [TwitterOauthController::class, 'listWebhooks']);
});