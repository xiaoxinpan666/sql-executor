<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends Controller
{
    // 构造函数：未登录用户才能访问注册页
    public function __construct()
    {
        $this->middleware('guest');
    }

    // 显示注册页面
    public function showRegistrationForm()
    {
        return view('auth.register');
    }

    // 处理注册请求
    public function register(Request $request)
    {
        // 验证表单
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        // 创建用户
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // 自动登录
        auth()->login($user);

        // 重定向到 /dev
        return redirect('/dev');
    }
}