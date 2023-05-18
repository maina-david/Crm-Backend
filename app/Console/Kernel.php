<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\CheckCallInQueue::class,
        Commands\SessionCheckCommand::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        $schedule->command('assign:conversation')->everyMinute();
        $schedule->command('check:conversationTimeout')->everyMinute();
        $schedule->command('check:call_in_queue')->everyMinute();
        $schedule->command('check:ticket_sla')->everyMinute();
        $schedule->command('check:session')->withoutOverlapping()->dailyAt('23:59');
        // $schedule->command('log:clear --keep-last')->daily();
        $schedule->command('review:interactions')->everyMinute();
        $schedule->command('sms:delivery')->everyMinute();
        $schedule->command('fetch:emails')->everyMinute();
        $schedule->command('check:licence')->everyMinute();
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__ . '/Commands');

        require base_path('routes/console.php');
    }
}