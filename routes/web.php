<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;

// 登录/注册路由
Route::middleware('guest')->group(function () {
    // 登录
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    
    // 注册
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

// 登出路由（需登录）
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// 默认首页
Route::get('/', function () {
    return redirect('/login');
});