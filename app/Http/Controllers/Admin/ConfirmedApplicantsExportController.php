<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ConfirmedApplicantsExcelExporter;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConfirmedApplicantsExportController extends Controller
{
    public function __invoke(ConfirmedApplicantsExcelExporter $exporter): StreamedResponse
    {
        return $exporter->download();
    }
}
