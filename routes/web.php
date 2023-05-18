<?php

use App\Models\SmsMessage;
use Illuminate\Support\Facades\Route;

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::post('/africas_talking/sms/callback', function (Request $request) {

    $message = SmsMessage::where('message_id', $request['id'])
        ->update(['delivery_status' => $request['status']]);

    if ($message) {
        return response()->json('success', 200);
    }
});

Route::match(['get', 'post'], '/advanta/sms/callback', function (Request $request) {
    $message = SmsMessage::where('message_id', $request['messageid'])
        ->update(['delivery_status' => $request['description']]);
});

Route::get('/', function () {
    return view('welcome');
    ## View 
    Route::get('/accounttype', 'AccounttypeController@index')->name('accounttype');

    ## Create
    Route::get('/accounttype/create', 'AccounttypeController@create')->name('accounttype.create');
    Route::post('/accounttype/store', 'AccounttypeController@store')->name('accounttype.store');

    ## Update
    Route::get('/accounttype/store/{id}', 'AccounttypeController@edit')->name('accounttype.edit');
    Route::post('/accounttype/update/{id}', 'AccounttypeController@update')->name('accounttype.update');

    ## Delete
    Route::get('/accounttype/delete/{id}', 'AccounttypeController@destroy')->name('accounttype.delete');

    Route::get('/accounttype', 'AccounttypeController@index')->name('accounttype');

    ##Contact form
    ## Create
    Route::get('/contactform/create', 'ContactFormController@create')->name('contactform.create');
    Route::post('/contactform/store', 'ContactFormController@store')->name('contactform.store');
});