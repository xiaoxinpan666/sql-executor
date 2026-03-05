<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>普通用户首页 | SQL Executor</title>
    <!-- Tailwind CSS（保持样式统一） -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- 导航栏（包含退出按钮） -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('home') }}" class="text-xl font-bold text-gray-800">SQL Executor</a>
                </div>
                <div class="flex items-center space-x-4">
                    <!-- 显示当前用户名 -->
                    <span class="text-gray-600">欢迎, {{ $user->name }} (普通用户)</span>
                    <!-- Logout 退出按钮（POST 表单） -->
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-red-600 hover:text-red-800 focus:outline-none">
                            <i class="fa-solid fa-right-from-bracket mr-1"></i> 退出登录
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- 主内容区 -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
        <div class="bg-white rounded-lg shadow-md p-8 text-center">
            <i class="fa-solid fa-user-clock text-6xl text-yellow-500 mb-4"></i>
            <h1 class="text-3xl font-bold text-gray-800 mb-2">普通用户权限</h1>
            <p class="text-gray-600 mb-6">
                你当前登录的是普通用户账号，无权限访问 SQL 执行器（/dev）页面<br>
                如需使用 SQL 执行功能，请联系管理员升级权限，或使用管理员账号登录。
            </p>
            <!-- 跳转登录页按钮（方便切换账号） -->
            <a href="{{ route('login') }}" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                <i class="fa-solid fa-right-to-bracket mr-2"></i> 切换到管理员账号登录
            </a>
        </div>
    </main>
</body>
</html>