<?php

namespace App\Console\Commands;

use App\Models\DidList;
use Illuminate\Console\Command;

class Add_Did_Command extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:did';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'It add DID numbers';

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
        $did_list = array(
            array('did' => '0730672005', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672006', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672007', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672008', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672009', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672010', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672011', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672012', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672013', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672014', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672015', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672016', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672017', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672018', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672019', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672020', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672021', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672022', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672023', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672024', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672025', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672026', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672027', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672028', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672029', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672030', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672031', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672032', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672033', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672034', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672035', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672036', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672037', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672038', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672039', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
            array('did' => '0730672040', 'carrier_id' => '7', 'created_at' => now(), 'updated_at' => now()),
        );

        DidList::insert($did_list);
    }
}
