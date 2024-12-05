<?php

namespace App\Http\Services;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SQLResultsExport;
use App\Models\SQLLog;
use Illuminate\Pagination\LengthAwarePaginator;
class DevService
{
    /**
     * @param $sql
     * @param $page
     * @param $pageSize
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application
     */
    public function  execute($sql, $page = 1, $pageSize = 10) {
        // 去除空格分号
        $sql = $this->trim($sql);
        if (empty($sql)) {
            return view('dev.index');
        }

        // 校验
        if ($this->validate($sql)) {
            return view('dev.index', [
                'error' => 'Only SELECT queries are allowed.', // 错误信息
            ]);
        }

        try {
            // sql查询
            $paginatedResults = $this->executeSqlPaginator($sql, $page, $pageSize);
            // 记录 SQL 执行日志
            SQLLog::create([
                'user_id' => auth()->id(),
                'sql' => $sql,
                'error' => null,
            ]);

            // 返回查询结果和分页
            return view('dev.index', [
                'results' => $paginatedResults
            ]);

        } catch (\Exception $e) {
            // 如果 SQL 执行失败，返回错误信息
            SQLLog::create([
                'user_id' => auth()->id(),
                'sql' => $sql,
                'error' => $e->getMessage(),
            ]);

            return view('dev.index', [
                'error' => 'SQL Error: ' . $e->getMessage(), // 错误信息
            ]);
        }
    }

    /**
     * @param $sql
     * @param $page
     * @param $pageSize
     * @return \Illuminate\Http\RedirectResponse
     */
    public function exportExcel($sql, $page = 1, $pageSize = 10) {
        // 去除空格分号
        $sql = $this->trim($sql);

        // 校验
        if ($this->validate($sql)) {
            return back()->withErrors('Only SELECT queries are allowed.');
        }

        // // 执行 SQL 查询
        try {
            $results = $this->executeSql($sql, $page, $pageSize);

            // 记录 SQL 执行日志
            SQLLog::create([
                'user_id' => auth()->id(),
                'sql' => $sql,
                'error' => null,
            ]);

            // 导出为 Excel 文件
            return Excel::download(new SQLResultsExport($results), 'results.xlsx');
        } catch (\Exception $e) {
            // 如果 SQL 执行失败，记录错误并返回
            SQLLog::create([
                'user_id' => auth()->id(),
                'sql' => $sql,
                'error' => $e->getMessage(),
            ]);
            return back()->withErrors('SQL Error: ' . $e->getMessage());
        }
    }

    public function exportJson() {
        return null;
    }

    /**
     * @param $sql
     * @return string
     */
    private function trim($sql) {
        // 去除两端的空格
        $sql = trim($sql);
        // 去除 SQL 语句末尾的分号
        $sql = rtrim($sql, ';');

        return $sql;
    }

    /**
     * @param $sql
     * @param $page
     * @param $pageSize
     * @return LengthAwarePaginator
     */
    private function executeSqlPaginator($sql, $page = 1, $pageSize = 10)
    {
        $results = $this->executeSql($sql, $page, $pageSize);

        // 获取总数
        $countQuery = "SELECT COUNT(*) AS total FROM (" . $sql . ") AS total_query";
        $totalResults = DB::select($countQuery);
        $totalCount = $totalResults[0]->total;

        // 将查询结果转化为集合
        $resultsCollection = collect($results);

        // 使用 Laravel 的 LengthAwarePaginator 进行分页处理
        return new LengthAwarePaginator(
            $resultsCollection,  // 当前页的数据
            $totalCount,         // 总数据条数
            $pageSize,            // 每页显示条数
            $page,               // 当前页
            ['path' => url()->current()] // 分页链接的基础路径
        );
    }

    /**
     * @param $sql
     * @param $page
     * @param $pageSize
     * @return array
     */
    private function executeSql($sql, $page = 1, $pageSize = 10)
    {
        if (! $this->isContainsLimit($sql)) {
            // 构造分页 SQL 查询
            $offset = ($page - 1) * $pageSize;
            $sql = $sql . " LIMIT $pageSize OFFSET $offset";
        }

        // 执行 SQL 查询
        $results = DB::select($sql);

        return $results;

    }

    /**
     * @param $sql
     * @return bool
     */
    private function validate($sql) {
        // 禁止其它危险操作
        $dangerousKeywords = ['INSERT', 'UPDATE', 'DELETE', 'DROP', 'TRUNCATE', 'ALTER'];
        foreach ($dangerousKeywords as $keyword) {
            if (stripos($sql, $keyword) !== false) {
                return true;
            }
        }

        return  stripos($sql, 'SELECT') === false;
    }

    /**
     * @param $sql
     * @return bool
     */
    private function isContainsLimit($sql)
    {
        return stripos($sql, 'LIMIT') !== false;
    }
}
