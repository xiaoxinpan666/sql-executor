<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckAdmin
{
    /**
     * 验证用户是否为管理员（is_admin = true）
     */
    public function handle(Request $request, Closure $next)
    {
        // 1. 检查用户是否登录
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', '请先登录！');
        }

        // 2. 检查用户是否为管理员（核心：验证 is_admin 字段）
        if (!Auth::user()->is_admin) {
            abort(403, '无访问权限！仅管理员可访问此页面。');
        }

        return $next($request);
    }
}