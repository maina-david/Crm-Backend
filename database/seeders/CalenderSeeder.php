<?php

namespace Database\Seeders;

use DateTime;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CalenderSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        

        $start_date = DateTime::createFromFormat('Y-m-d H:i:s', '1990-01-01 00:00:00');
        $end_date   = DateTime::createFromFormat('Y-m-d H:i:s', '2050-31-12 23:59:59');

        $current = clone $start_date;

        while ($current < $end_date) {
            $d            = $current->format('Y-m-d');
            $dt           = $current->format('Y-m-d H:i:s');
            $is_weekend   = in_array($current->format('D'), ['Sat', 'Sun']);
            $day          = $current->format('d');
            $month        = $current->format('n'); // month num
            $year         = $current->format('Y');
            $week         = $current->format('W');
            $weekday      = (int)$current->format('w'); // weekday num
            $month_name   = $current->format('F');
            $weekday_name = $current->format('l');

            DB::table('calendar_tables')->insert([
                'd'            => $d,
                'dt'           => $dt,
                'is_weekend'   => $is_weekend,
                'day'          => $day,
                'month'        => $month,
                'year'         => $year,
                'week'         => $week,
                'weekday'      => $weekday,
                'month_name'   => $month_name,
                'weekday_name' => $weekday_name,
            ]);

            $current = $current->modify('+1 day');
        }
    }
}
