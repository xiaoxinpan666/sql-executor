<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{
    /**
     * 构造函数：仅登录用户可访问
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * 显示普通用户首页
     */
    public function index()
    {
        // 获取当前登录用户信息
        $user = Auth::user();
        
        return view('home.index', compact('user'));
    }
}