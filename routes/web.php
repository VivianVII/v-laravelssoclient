<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', function (Request $request) {
    $request->session()->put("state",$state = Str::random(40));
    $query = http_build_query([
        "client_id" => "9363d4f5-45ea-4ac0-8133-7c0e6a9bc8b7",
        "redirect_uri" => "http://127.0.0.1:8080/callback",
        "response_type"=> "code",
        "scope" => "view-user",
        "state" => $state
    ]);
    return redirect("http://127.0.0.1:8000/oauth/authorize?". $query);
});


Route::get('/callback', function (Request $request) {
   $state = $request->session()->pull("state");

   throw_unless(strlen($state) > 0 && $state == $request->state, InvalidArgumentException::class);
   $response = Http::asForm()->post(
    "http://127.0.0.1:8000/oauth/token",   
    [
       "grant_type" => "authorization_code",
       "client_id" => "9363d4f5-45ea-4ac0-8133-7c0e6a9bc8b7",
       "client_secret" => "pYE57utC3tgm7ytJjRgAzxWPpB1SX6GV3QrvtREV",
       "redirect_uri" => "http://127.0.0.1:8080/callback",
       "code" => $request->code
   ]);
   $request->session()->put($response->json());
   return redirect("/authuser");
});

Route::get('/authuser', function (Request $request) {
    $access_token = $request->session()->get("access_token");
    Log::info($access_token);
    $response = Http::withHeaders([
        "Accept" => "application/json",
        "Authorization" => "Bearer " . $access_token
    ])->get("http://127.0.0.1:8000/api/user");

    return $response->json();
});

Route::get('/logout', function (Request $request) {
    $access_token = $request->session()->get("access_token");
    $response = Http::withHeaders([
        "Accept" => "application/json",
        "Authorization" => "Bearer " . $access_token
    ])->post("http://127.0.0.1:8000/api/logout");

    return $response->json();
});