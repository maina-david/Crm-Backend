<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FormItemSeed extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('form_items')->delete();
        $did_list = array(
            array('item_name' => 'textfield', 'item_display_name' => 'Text field', 'created_at' => now(), 'updated_at' => now()),
            array('item_name' => 'textarea', 'item_display_name' => 'Text area', 'created_at' => now(), 'updated_at' => now()),
            array('item_name' => 'number',  'item_display_name' => 'Number', 'created_at' => now(), 'updated_at' => now()),
            array('item_name' => 'email',  'item_display_name' => 'Email', 'created_at' => now(), 'updated_at' => now()),
            array('item_name' => 'password',  'item_display_name' => 'Password', 'created_at' => now(), 'updated_at' => now()),
            array('item_name' => 'date',  'item_display_name' => 'Date', 'created_at' => now(), 'updated_at' => now()),
            array('item_name' => 'select', 'item_display_name' => 'Select', 'created_at' => now(), 'updated_at' => now()),
            array('item_name' => 'radio',  'item_display_name' => 'Radio', 'created_at' => now(), 'updated_at' => now()),
            array('item_name' => 'checkbox',  'item_display_name' => 'Check box', 'created_at' => now(), 'updated_at' => now()),
            array('item_name' => 'image', 'item_display_name' => 'Image', 'created_at' => now(), 'updated_at' => now()),
            array('item_name' => 'file',  'item_display_name' => 'File', 'created_at' => now(), 'updated_at' => now()),
            array('item_name' => 'phone', 'item_display_name' => 'Phone', 'created_at' => now(), 'updated_at' => now()),
            array('item_name' => 'url', 'item_display_name' => 'Url', 'created_at' => now(), 'updated_at' => now()),
            array('item_name' => 'singlename', 'item_display_name' => 'Single name', 'created_at' => now(), 'updated_at' => now()),
            array('item_name' => 'multiplename', 'item_display_name' => 'Multiple name', 'created_at' => now(), 'updated_at' => now()),
        );

        DB::table('form_items')->insert($did_list);
    }
}
