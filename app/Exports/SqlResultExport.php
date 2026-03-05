<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class SqlResultExport implements FromView
{
    protected $results;

    /**
     * 构造函数：接收 SQL 执行结果
     */
    public function __construct($results)
    {
        $this->results = $results;
    }

    /**
     * 加载导出视图
     */
    public function view(): View
    {
        $headers = !empty($this->results) ? array_keys((array)$this->results[0]) : [];
        
        return view('exports.sql-result', [
            'results' => $this->results,
            'headers' => $headers,
        ]);
    }
}