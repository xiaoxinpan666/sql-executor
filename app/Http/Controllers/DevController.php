<?php

namespace App\Http\Controllers;

use App\Models\SqlExecutionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SqlResultExport;
use Illuminate\Support\Str;

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
        return view('dev.index', [
            'error' => null,
            'results' => null,
            'headers' => [],
            'pagination' => [],
            'sql' => '',
        ]);
    }

    /**
     * 执行 SQL 并返回结果（新增类型校验）
     */
    public function execute(Request $request)
    {
        $request->validate([
            'sql' => 'required|string|max:1000',
        ]);

        $sql = trim($request->sql);
        $error = null;
        $results = null;
        $headers = [];
        $pagination = null;

        // 1. 注入防护校验
        $validateResult = $this->validateSqlForInjection($sql);
        if ($validateResult !== true) {
            $error = $validateResult;
        } elseif (strtoupper(substr($sql, 0, 6)) !== 'SELECT') {
            $error = '仅允许执行 SELECT 语句！';
        } else {
            try {
                $escapedSql = $this->escapeSqlSpecialChars($sql);
                $query = DB::select($escapedSql);

                // ========== 核心修复：增加返回值类型校验 ==========
                if (!is_array($query)) {
                    throw new \Exception('SQL执行结果异常（非数组类型），请检查SQL语句');
                }

                // 处理分页
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

                // 获取表头（增加非空校验）
                if (!empty($query)) {
                    $headers = array_keys((array)$query[0]);
                }

            } catch (\Exception $e) {
                $error = $e->getMessage();
            }
        }

        // 记录日志
        SqlExecutionLog::create([
            'user_id' => Auth::id(),
            'sql' => $sql,
            'error' => $error,
        ]);

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
     * 导出 Excel（修复布尔值访问问题）
     */
    public function exportExcel(Request $request)
    {
        $sql = $request->input('sql');
        
        // 1. 注入防护校验
        $validateResult = $this->validateSqlForInjection($sql);
        if ($validateResult !== true) {
            return back()->with('error', $validateResult)->withInput(['sql' => $sql]);
        }
        
        if (strtoupper(substr($sql, 0, 6)) !== 'SELECT') {
            return back()->with('error', '仅允许导出 SELECT 语句结果！')->withInput(['sql' => $sql]);
        }

        try {
            $escapedSql = $this->escapeSqlSpecialChars($sql);
            $results = DB::select($escapedSql);

            // ========== 核心修复：增加类型校验 ==========
            if (!is_array($results)) {
                throw new \Exception('SQL执行结果异常，无法导出（结果非数组）');
            }
            // 无数据时提示，避免导出空文件
            if (empty($results)) {
                throw new \Exception('SQL执行结果为空，无需导出');
            }

            return Excel::download(new SqlResultExport($results), 'sql-result-' . time() . '.xlsx');
        } catch (\Exception $e) {
            return redirect()->route('dev.index')
            ->with('error', '导出失败：' . $e->getMessage())
            ->withInput(['sql' => $sql]);
        }
    }

    /**
     * 导出 JSON（同步修复）
     */
    public function exportJson(Request $request)
    {
        $sql = $request->input('sql');
        
        // 1. 注入防护校验
        $validateResult = $this->validateSqlForInjection($sql);
        if ($validateResult !== true) {
            return back()->with('error', $validateResult)->withInput(['sql' => $sql]);
        }

        if (strtoupper(substr($sql, 0, 6)) !== 'SELECT') {
            return back()->with('error', '仅允许导出 SELECT 语句结果！')->withInput(['sql' => $sql]);
        }

        try {
            $escapedSql = $this->escapeSqlSpecialChars($sql);
            $results = DB::select($escapedSql);

            // ========== 核心修复：增加类型校验 ==========
            if (!is_array($results)) {
                throw new \Exception('SQL执行结果异常，无法导出（结果非数组）');
            }

            $json = json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if ($json === false) {
                throw new \Exception('结果转换JSON失败，请检查数据格式');
            }
            
            return response($json)
                ->header('Content-Type', 'application/json')
                ->header('Content-Disposition', 'attachment; filename="sql-result-' . time() . '.json"');
        } catch (\Exception $e) {
            return back()->with('error', '导出失败：' . $e->getMessage())->withInput(['sql' => $sql]);
        }
    }

    /**
     * SQL 注入防护：校验 SQL 合法性
     */
    private function validateSqlForInjection(string $sql): bool|string
    {
        $sqlUpper = Str::upper($sql);
        
        // 1. 禁止危险函数/关键词
        $dangerFunctions = [
            'UNION', 'JOIN', 'SUBQUERY', 'EXEC', 'PROCEDURE', 'FUNCTION',
            'INTO', 'OUTFILE', 'LOAD_FILE', 'DUMPFILE', 'CHAR', 'CONCAT',
            'MID', 'SUBSTR', 'LEFT', 'RIGHT', 'HEX', 'UNHEX', 'BASE64',
            'SLEEP', 'BENCHMARK', 'IF', 'CASE', 'WHEN', 'ELSE'
        ];
        foreach ($dangerFunctions as $func) {
            if (Str::contains($sqlUpper, $func)) {
                return "禁止使用危险函数/关键词：{$func}（防SQL注入）";
            }
        }

        // 2. 禁止注释符
        $commentChars = ['--', '#', '/*', '*/'];
        foreach ($commentChars as $char) {
            if (Str::contains($sql, $char)) {
                return "禁止使用注释符：{$char}（防SQL注入）";
            }
        }

        // 3. 禁止分号
        if (Str::contains($sql, ';')) {
            return "禁止使用分号（;），仅允许执行单条 SELECT 语句";
        }

        // 4. 限制长度
        if (strlen($sql) > 1000) {
            return "SQL语句长度超过限制（最大1000字符）";
        }

        // 5. 过滤控制字符
        if (preg_match('/[\x00-\x1F\x7F]/', $sql)) {
            return "SQL语句包含非法控制字符（防注入）";
        }

        return true;
    }

    /**
     * 转义 SQL 特殊字符
     */
    private function escapeSqlSpecialChars(string $sql): string
    {
        // 转义单引号
        $sql = str_replace("'", "\\'", $sql);
        // 转义双引号
        $sql = str_replace('"', '\\"', $sql);
        // 过滤换行、制表符
        $sql = preg_replace('/[\n\r\t]/', ' ', $sql);
        // 去除连续空格
        $sql = preg_replace('/\s+/', ' ', $sql);
        
        return $sql;
    }

    /**
     * 保留原有方法
     */
    public function executeSafeSQLWithLaravel($sql, $params = [])
    {
        $result = DB::select($sql, $params);
        // 增加类型校验
        if (!is_array($result)) {
            return [];
        }
        return $result;
    }
}