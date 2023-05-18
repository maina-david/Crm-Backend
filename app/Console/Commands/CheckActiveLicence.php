<?php

namespace App\Console\Commands;

use App\Helpers\AccessChecker;
use App\Models\Company;
use App\Models\Internal\Company\Licence;
use App\Services\UserService;
use Illuminate\Console\Command;

class CheckActiveLicence extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'check:licence';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check companies active licences and update status';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $companies = Company::get();

        if ($companies->count() > 0) {
            foreach ($companies as $company) {
                $licence = Licence::where([
                    'company_id' => $company->id,
                    'active' => true
                ])->first();

                if (!$licence) {
                    $unlicencedCompany = Company::find($company->id);

                    $unlicencedCompany->active = false;

                    $unlicencedCompany->save();

                    $unlicencedCompany->users()->update([
                        'is_loggedin' => false,
                        'status' => 'INACTIVE'
                    ]);

                    foreach ($unlicencedCompany->users() as $user) {
                        $is_agent = AccessChecker::check_if_agent($user->id);
                        if ($is_agent) {
                            $user_service = new UserService();
                            $user_service->agent_logout($user->id);
                        }

                        $user->currentAccessToken()->delete();
                        $user->tokens()->delete();
                    }
                } else {
                    if (isset($licence->expires_on)) {
                        if (now() > $licence->expires_on) {
                            $licence->active = FALSE;
                            $licence->save();
                        }
                    }
                }
            }
        }
    }
}