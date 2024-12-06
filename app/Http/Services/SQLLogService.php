<?php

namespace App\Http\Services;
use App\Models\SQLLog;

class SQLLogService {

    public function save($sql, $error = null) {
        SQLLog::create([
            'user_id' => auth()->id(),
            'sql' => $sql,
            'error' => $error
        ]);
    }
}
