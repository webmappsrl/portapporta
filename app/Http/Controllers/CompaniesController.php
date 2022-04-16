<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CompaniesController extends Controller
{
    /**
     * Return Company's info.json.
     *
     * @param Request $request
     * @param int     $id
     * @param array   $headers
     *
     * @return JsonResponse
     */
    public function infoJson(Request $request, int $id, array $headers = []): JsonResponse {
        $company = Company::find($id);
        
        if (is_null($company))
        return response()->json(['code' => 404, 'error' => "Not Found"], 404);
        
        return response()->json($company->createInfoJson($company), 200, $headers);
    }
}
