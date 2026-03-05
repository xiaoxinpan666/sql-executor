<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class LoginController extends Controller
{
    // 构造函数：未登录用户才能访问登录页
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    // 显示登录页面
    public function showLoginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        // 验证表单
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);
    
        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }
    
        // 尝试登录
        $credentials = $request->only('email', 'password');
        if (Auth::attempt($credentials, $request->has('remember'))) {
            $request->session()->regenerate();
            
            // 新增：根据用户类型跳转不同页面
            if (Auth::user()->is_admin) {
                // 管理员跳 /dev
                return redirect()->intended('/dev');
            } else {
                // 普通用户跳 /home
                return redirect()->intended('/home');
            }
        }
    
        // 登录失败
        return back()->withErrors([
            'email' => '邮箱或密码错误，请重试。',
        ])->withInput();
    }
    // 处理登出请求
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }
}