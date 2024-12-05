<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SQLLog extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'sql', 'error'];

    protected $table = 'sql_logs';

    // 关联 User 模型
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
