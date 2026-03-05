<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SQL Executor | Admin</title>
    <!-- Tailwind CSS (现代化UI) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Alpine JS (轻量级交互) -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <!-- 自定义样式 -->
    <style type="text/tailwindcss">
        @layer utilities {
            .content-auto {
                content-visibility: auto;
            }
            .sql-editor {
                @apply w-full h-64 p-4 border rounded-lg bg-gray-50 font-mono text-sm focus:outline-none focus:ring-2 focus:ring-blue-500;
            }
            .btn-primary {
                @apply bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors;
            }
            .btn-success {
                @apply bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700 transition-colors;
            }
            .btn-danger {
                @apply bg-red-600 text-white px-4 py-2 rounded-lg hover:bg-red-700 transition-colors;
            }
            .alert-error {
                @apply bg-red-50 border border-red-200 text-red-800 p-4 rounded-lg mb-4;
            }
            .alert-warning {
                @apply bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-lg mb-4;
            }
            .table-responsive {
                @apply overflow-x-auto;
            }
            .pagination-link {
                @apply px-3 py-1 border rounded hover:bg-gray-100;
            }
            .pagination-active {
                @apply px-3 py-1 border rounded bg-blue-600 text-white;
            }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">
    <!-- 导航栏 -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('dev.index') }}" class="text-xl font-bold text-blue-600">SQL Executor</a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-600">欢迎, {{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="text-red-600 hover:text-red-800">退出登录</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <!-- 主内容 -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow-md p-6">
            <h1 class="text-2xl font-bold mb-6">SQL 执行器 (仅允许 SELECT)</h1>

            <!-- 错误提示 -->
            @if(session('error') || $error ?? null)
                <div class="alert-error">
                    <i class="fa-solid fa-circle-exclamation mr-2"></i>
                    {{ session('error') ?? $error }}
                </div>
            @endif

            <!-- SQL 输入表单 -->
            <form id="sqlForm" method="POST" action="{{ route('dev.execute') }}" class="mb-8">
                @csrf
                <div class="mb-4">
                    <label for="sql" class="block text-gray-700 mb-2">SQL 语句</label>
                    <textarea id="sql" name="sql" class="sql-editor" placeholder="SELECT * FROM users LIMIT 10;">{{ old('sql', $sql ?? '') }}</textarea>
                </div>
                <div class="flex space-x-4">
                    <button type="submit" class="btn-primary">
                        <i class="fa-solid fa-play mr-2"></i> Execute
                    </button>
                    <button type="button" id="exportExcelBtn" class="btn-success">
                        <i class="fa-solid fa-file-excel mr-2"></i> 导出 Excel
                    </button>
                    <button type="button" id="exportJsonBtn" class="btn-success">
                        <i class="fa-solid fa-file-code mr-2"></i> 导出 JSON
                    </button>
                </div>
            </form>

            <!-- 结果展示区域 -->
            @if(isset($results) && !empty($results))
                <div class="mt-8">
                    <h2 class="text-xl font-semibold mb-4">执行结果 (共 {{ $pagination['total'] }} 条)</h2>
                    <div class="table-responsive">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    @foreach($headers as $header)
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">{{ $header }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($results as $row)
                                    <tr>
                                        @foreach($headers as $header)
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ $row->$header ?? '-' }}</td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <!-- 分页 -->
                    @if($pagination['last_page'] > 1)
                        <div class="mt-4 flex justify-center">
                            <div class="flex space-x-1">
                                @for($i = 1; $i <= $pagination['last_page']; $i++)
                                    <a href="javascript:submitForm({{ $i }})" class="{{ $i == $pagination['current_page'] ? 'pagination-active' : 'pagination-link' }}">
                                        {{ $i }}
                                    </a>
                                @endfor
                            </div>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </main>

    <!-- 导出表单（隐藏） -->
    <form id="exportExcelForm" method="POST" action="{{ route('dev.export.excel') }}" class="hidden">
        @csrf
        <input type="hidden" name="sql" id="exportExcelSql">
    </form>
    <form id="exportJsonForm" method="POST" action="{{ route('dev.export.json') }}" class="hidden">
        @csrf
        <input type="hidden" name="sql" id="exportJsonSql">
    </form>

    <!-- JavaScript -->
    <script>
        // 分页提交
        function submitForm(page) {
            const form = document.getElementById('sqlForm');
            const pageInput = document.createElement('input');
            pageInput.type = 'hidden';
            pageInput.name = 'page';
            pageInput.value = page;
            form.appendChild(pageInput);
            form.submit();
        }

        // 导出 Excel
        document.getElementById('exportExcelBtn').addEventListener('click', function() {
            const sql = document.getElementById('sql').value.trim();
            if (!sql) {
                alert('请输入 SQL 语句！');
                return;
            }
            if (sql.substring(0,6).toUpperCase() !== 'SELECT') {
                alert('仅允许导出 SELECT 语句结果！');
                return;
            }
            document.getElementById('exportExcelSql').value = sql;
            document.getElementById('exportExcelForm').submit();
        });

        // 导出 JSON
        document.getElementById('exportJsonBtn').addEventListener('click', function() {
            const sql = document.getElementById('sql').value.trim();
            if (!sql) {
                alert('请输入 SQL 语句！');
                return;
            }
            if (sql.substring(0,6).toUpperCase() !== 'SELECT') {
                alert('仅允许导出 SELECT 语句结果！');
                return;
            }
            document.getElementById('exportJsonSql').value = sql;
            document.getElementById('exportJsonForm').submit();
        });

        // 表单提交验证
        document.getElementById('sqlForm').addEventListener('submit', function(e) {
            const sql = document.getElementById('sql').value.trim();
            if (!sql) {
                e.preventDefault();
                alert('请输入 SQL 语句！');
                return;
            }
        });
    </script>
</body>
</html>