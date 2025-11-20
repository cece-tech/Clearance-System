<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Mail\MyTestEmail;
use Barryvdh\DomPDF\Facade\Pdf; // Make sure to import the correct PDF facade

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

Route::get('/auth', function () {
    return view('auth.auth');
});

Route::get('/testroute', function () {
    Mail::to('sv8905958@gmail.com')->send(new MyTestEmail('EC'));
    return "Email sent!";
});

// PDF test route
Route::get('/test-pdf', function () {
    // Make sure the view 'pdf.example' exists in resources/views/pdf/example.blade.php
    $pdf = Pdf::loadView('pdf.example', ['data' => 'Hello World']);
    return $pdf->download('example.pdf');
});

Route::get('/export-pdf', function () {

    $data = [
        'name' => 'EC'
    ];

    $pdf = Pdf::loadView('pdf.example', $data);

    return $pdf->download('myfile.pdf');
});

