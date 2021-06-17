<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


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
// Route::post('/register', 'UserController@register');
//     Route::post('/login', 'UserController@authenticate');
//     Route::get('/open', 'DataController@open');

//     Route::group(['middleware' => ['jwt.verify']], function() {
//         Route::get('/user', 'UserController@getAuthenticatedUser');
//         Route::get('/closed', 'DataController@closed');
//     });
Route::get('/', function () {
    return view('welcome');
}); 
Auth::routes(['verify' => true]);

