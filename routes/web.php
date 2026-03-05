<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\DevController;
// 新增：引入 Home 控制器（下一步创建）
use App\Http\Controllers\HomeController;

// 登录/注册路由（未登录用户可访问）
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);
});

// 登出路由（需登录）
Route::post('/logout', [LoginController::class, 'logout'])->name('logout')->middleware('auth');

// Dev 路由（核心：需登录 + 自定义 admin 中间件）
Route::middleware(['auth', 'admin'])->group(function () {
    Route::get('/dev', [DevController::class, 'index'])->name('dev.index');
    Route::post('/dev/execute', [DevController::class, 'execute'])->name('dev.execute');
    Route::post('/dev/export-excel', [DevController::class, 'exportExcel'])->name('dev.export.excel');
    Route::post('/dev/export-json', [DevController::class, 'exportJson'])->name('dev.export.json');
});

// 新增：普通用户首页 /home 路由（仅需登录，无需 admin 权限）
Route::middleware('auth')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');
});

// 默认首页重定向到登录
Route::get('/', function () {
    return redirect('/login');
});

// 测试路由（可选）
Route::get('/test', function () {
    return "Laravel 服务正常！";
});