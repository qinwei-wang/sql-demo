<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\DevService;

class DevController extends Controller
{
    private $devService;

    /**
     * @param DevService $devService
     */
    public function __construct(DevService $devService)
    {
        $this->devService = $devService;
    }

    /**
     * 执行sql
     * @param Request $request
     * @return \Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function index(Request $request) {
        $sql = $request->input('sql', '');
        $page = $request->input("page", 1);
        return $this->devService->execute($sql, $page);
    }

    /**
     * 导出sql
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function exportExcel(Request $request) {
        $sql = $request->input('sql', '');
        $page = $request->input("page", 1);
        return $this->devService->exportExcel($sql, $page);
    }

    /**
     * 导出json
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\RedirectResponse
     */
    public function exportJson(Request $request) {
        $sql = $request->input('sql', '');
        $page = $request->input("page", 1);
        return $this->devService->exportJson($sql, $page);
    }
}
