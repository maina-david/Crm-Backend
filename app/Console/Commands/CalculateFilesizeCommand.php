<?php

namespace App\Console\Commands;

use App\Models\CDRTable;
use Illuminate\Console\Command;

class CalculateFilesizeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'calculate:filesize';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculates file sizes';

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
        $cdr_data = CDRTable::whereNotNull("audio_url")->get();
        foreach ($cdr_data as $key => $cdr) {
            $url = $cdr->audio_url;
            $headers = get_headers($url, true);
            $filesize = $headers["Content-Length"];
            $cdr->file_size = round($filesize / 1048576, 3);
            $cdr->save();
        }
    }
}
