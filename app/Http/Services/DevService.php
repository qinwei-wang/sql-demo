<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SQLResultsExport;
use Illuminate\Pagination\LengthAwarePaginator;

class DevService
{
    private SQLLogService $sqlLogService;

    public function __construct(SQLLogService $sqlLogService)
    {
        $this->sqlLogService = $sqlLogService;
    }

    /**
     * 执行 SQL 查询并返回分页结果
     *
     * @param string $sql
     * @param int $page
     * @param int $pageSize
     * @return \Illuminate\Contracts\View\View
     */
    public function execute(string $sql, int $page = 1, int $pageSize = 10): \Illuminate\Contracts\View\View|View
    {
        // 去除 SQL 语句两端的空格和末尾的分号
        $sql = $this->trimSql($sql);

        if (empty($sql)) {
            return $this->renderDevPage(null);
        }

        // 校验 SQL 安全性
        $validationError = $this->validateSql($sql);
        if ($validationError) {
            return $this->renderDevPage(null, $validationError);
        }

        try {
            // 执行分页查询
            $results = $this->executeSqlPaginator($sql, $page, $pageSize);
            $error = '';
        } catch (\Exception $e) {
            $results = null;
            $error = 'SQL Error: ' . $e->getMessage();
        }

        // 记录日志
        $this->sqlLogService->save($sql, $error);

        return $this->renderDevPage($results, $error);
    }

    /**
     * 导出为 Excel 文件
     *
     * @param string $sql
     * @param int $page
     * @param int $pageSize
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportExcel(string $sql, int $page = 1, int $pageSize = 10): \Symfony\Component\HttpFoundation\BinaryFileResponse
    {
        // 去除 SQL 语句两端的空格和末尾的分号
        $sql = $this->trimSql($sql);

        // 校验 SQL
        $validationError = $this->validateSql($sql);
        if ($validationError) {
            return back()->withErrors($validationError);
        }

        try {
            // 直接执行 SQL 查询，无需分页和统计总数
            $results = $this->executeSql($sql, $page, $pageSize);

            // 记录 SQL 执行日志
//            $this->sqlLogService->save($sql);

            // 导出为 Excel 文件
            return Excel::download(new SQLResultsExport($results), 'results.xlsx');
        } catch (\Exception $e) {
//            $this->sqlLogService->save($sql, $e->getMessage());
            return back()->withErrors('SQL Error: ' . $e->getMessage());
        }
    }

    /**
     * 导出为 JSON 文件
     *
     * @param string $sql
     * @param int $page
     * @param int $pageSize
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function exportJson(string $sql, int $page = 1, int $pageSize = 10): \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
    {
        // 去除 SQL 语句两端的空格和末尾的分号
        $sql = $this->trimSql($sql);

        // 校验 SQL
        $validationError = $this->validateSql($sql);
        if ($validationError) {
            return back()->withErrors($validationError);
        }

        try {
            // 直接执行 SQL 查询，无需分页和统计总数
            $results = $this->executeSql($sql, $page, $pageSize);

            // 记录 SQL 执行日志
//            $this->sqlLogService->save($sql);

            // 返回 JSON 格式的查询结果
            return response()->json($results);
        } catch (\Exception $e) {
//            $this->sqlLogService->save($sql, $e->getMessage());
            return back()->withErrors('SQL Error: ' . $e->getMessage());
        }
    }

    /**
     * 去除 SQL 语句两端的空格和末尾的分号
     *
     * @param string $sql
     * @return string
     */
    private function trimSql(string $sql): string
    {
        return rtrim(trim($sql), ';');
    }

    /**
     * 校验 SQL 是否符合安全要求
     *
     * @param string $sql
     * @return string|null
     */
    private function validateSql(string $sql): ?string
    {
        // 禁止非 SELECT 查询
        if (stripos($sql, 'SELECT') !== 0) {
            return 'Only SELECT queries are allowed.';
        }

        // 禁止危险操作
        $dangerousKeywords = ['INSERT', 'UPDATE', 'DELETE', 'DROP', 'TRUNCATE', 'ALTER'];
        foreach ($dangerousKeywords as $keyword) {
            if (stripos($sql, $keyword) !== false) {
                return 'Dangerous SQL keywords detected.';
            }
        }

        return null;
    }

    /**
     * 执行分页查询 SQL
     *
     * @param string $sql
     * @param int $page
     * @param int $pageSize
     * @return LengthAwarePaginator
     */
    private function executeSqlPaginator(string $sql, int $page = 1, int $pageSize = 10): LengthAwarePaginator
    {
        // 执行查询
        $results = $this->executeSql($sql, $page, $pageSize);

        // 获取总记录数
        $countQuery = "SELECT COUNT(*) AS total FROM ($sql) AS total_query";
        $totalResults = DB::select($countQuery);
        $totalCount = $totalResults[0]->total;

        // 转换为集合
        $resultsCollection = collect($results);

        // 使用 LengthAwarePaginator 进行分页处理
        return new LengthAwarePaginator(
            $resultsCollection,  // 当前页的数据
            $totalCount,         // 总数据条数
            $pageSize,           // 每页显示条数
            $page,               // 当前页
            ['path' => url()->current()]  // 分页链接的基础路径
        );
    }

    /**
     * 执行 SQL 查询
     *
     * @param string $sql
     * @param int $page
     * @param int $pageSize
     * @return array
     */
    private function executeSql(string $sql, int $page = 1, int $pageSize = 10): array
    {
        // 如果 SQL 查询没有 LIMIT 子句，添加分页
        if (!$this->isSqlContainsLimit($sql)) {
            $offset = ($page - 1) * $pageSize;
            $sql = $sql . " LIMIT $pageSize OFFSET $offset";
        }

        // 执行 SQL 查询
        return DB::select($sql);
    }

    /**
     * 检查 SQL 是否包含 LIMIT 子句
     *
     * @param string $sql
     * @return bool
     */
    private function isSqlContainsLimit(string $sql): bool
    {
        return stripos($sql, 'LIMIT') !== false;
    }

    /**
     * 渲染开发页面
     *
     * @param $results
     * @param string|null $error
     * @return View
     */
    private function renderDevPage($results, string $error = null): View
    {
        $data = [];
        $data['results'] = $results;
        if (!empty($error)) $data['error'] = $error;
        return view('dev.index', $data);
    }
}
