<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'main.mainpage')->name('home');
Route::view('/business', 'business.dashboard')->name('business.dashboard');
Route::view('/admin', 'admin.dashboard')->name('admin.dashboard');
Route::view('/login', 'auth.login')->name('login');
Route::view('/register', 'auth.register')->name('register');
