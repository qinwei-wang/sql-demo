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
    //
    public function index(Request $request) {
        return $this->devService->execute();
    }

    public function exportExcel(Request $request) {
        return $this->devService->exportExcel();
    }

    public function exportJson(Request $request) {
        return $this->devService->exportJson();
    }
}
