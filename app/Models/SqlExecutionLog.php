<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SqlExecutionLog extends Model
{
    use HasFactory;

    /**
     * 关闭 Laravel 自动维护 created_at/updated_at 时间戳
     */
    public $timestamps = false;

    /**
     * 指定模型对应的表名（可选，确保表名匹配）
     */
    protected $table = 'sql_execution_logs';

    /**
     * 可批量赋值的字段
     */
    protected $fillable = [
        'user_id',
        'sql',
        'error',
        // 注意：executed_at 是表中的字段，但由数据库默认值（CURRENT_TIMESTAMP）自动填充，无需手动赋值
    ];

    /**
     * 字段类型转换
     */
    protected $casts = [
        'executed_at' => 'datetime',
    ];

    /**
     * 关联用户模型
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}