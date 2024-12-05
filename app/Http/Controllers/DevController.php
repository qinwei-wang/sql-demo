<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Services\DevService;

class DevController extends Controller
{
    private $devService;

    public function __construct(DevService $devService)
    {
        $this->devService = $devService;
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application
     */
    public function index(Request $request) {
        $sql = $request->input('sql');
        $page = $request->input("page", 1);
        return $this->devService->execute($sql, $page);
    }

    public function exportExcel(Request $request) {
        return $this->devService->exportExcel();
    }

    public function exportJson(Request $request) {
        return $this->devService->exportJson();
    }
}
