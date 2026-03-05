<?php

namespace App\Http\Controllers;

use App\Models\SqlExecutionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SqlResultExport;

class DevController extends Controller
{
    /**
     * 构造函数：应用 admin 中间件（确保只有管理员可访问）
     */
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * 显示 SQL 执行页面
    */
    public function index()
    {
        // 首次加载页面时，传递空的默认变量，避免视图中未定义
        return view('dev.index', [
            'error' => null,
            'results' => null,
            'headers' => [],
            'pagination' => [],
            'sql' => '',
        ]);
    }

    /**
     * 执行 SQL 并返回结果
     */
    public function execute(Request $request)
    {
        $request->validate([
            'sql' => 'required|string',
        ]);

        $sql = trim($request->sql);
        $error = null;
        $results = null;
        $headers = [];
        $pagination = null;

        // 1. 检查是否为 SELECT 语句
        if (strtoupper(substr($sql, 0, 6)) !== 'SELECT') {
            $error = '仅允许执行 SELECT 语句！';
        } else {
            try {
                // 2. 执行 SQL
                $query = DB::select($sql);
                
                // 3. 处理分页
                $perPage = $request->input('per_page', 10);
                $currentPage = $request->input('page', 1);
                $total = count($query);
                $results = collect($query)->forPage($currentPage, $perPage);
                $pagination = [
                    'total' => $total,
                    'per_page' => $perPage,
                    'current_page' => $currentPage,
                    'last_page' => ceil($total / $perPage),
                ];

                // 4. 获取表头
                if (!empty($query)) {
                    $headers = array_keys((array)$query[0]);
                }

            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }

        // 3. 记录日志
        SqlExecutionLog::create([
            'user_id' => Auth::id(),
            'sql' => $sql,
            'error' => $error,
        ]);

        // 4. 返回响应
        if ($request->ajax()) {
            return response()->json([
                'success' => is_null($error),
                'error' => $error,
                'results' => $results,
                'headers' => $headers,
                'pagination' => $pagination,
            ]);
        }

        return view('dev.index', compact('error', 'results', 'headers', 'pagination', 'sql'));
    }

    /**
     * 导出 Excel
     */
    public function exportExcel(Request $request)
    {
        $sql = $request->input('sql');
        if (strtoupper(substr($sql, 0, 6)) !== 'SELECT') {
            return back()->with('error', '仅允许导出 SELECT 语句结果！');
        }

        try {
            $results = DB::select(DB::raw($sql));
            return Excel::download(new SqlResultExport($results), 'sql-result-' . time() . '.xlsx');
        } catch (\Exception $e) {
            return back()->with('error', '导出失败：' . $e->getMessage());
        }
    }

    /**
     * 导出 JSON
     */
    public function exportJson(Request $request)
    {
        $sql = $request->input('sql');
        if (strtoupper(substr($sql, 0, 6)) !== 'SELECT') {
            return back()->with('error', '仅允许导出 SELECT 语句结果！');
        }

        try {
            $results = DB::select(DB::raw($sql));
            $json = json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            
            return response($json)
                ->header('Content-Type', 'application/json')
                ->header('Content-Disposition', 'attachment; filename="sql-result-' . time() . '.json"');
        } catch (\Exception $e) {
            return back()->with('error', '导出失败：' . $e->getMessage());
        }
    }
}