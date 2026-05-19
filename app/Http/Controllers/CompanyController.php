<?php

namespace App\Http\Controllers;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function formJson(Request $request)
    {
        $company = Company::find($request->id);
        if (!$company) {
            return $this->sendError('Company not found.', "", 404);
        }
        $formJson = json_decode($company->form_json, true);
        return $this->sendResponse($formJson, 'Json of the registration form fields.');
    }

    /**
     * Registration form schema (form_json) plus company properties (e.g. feature flags).
     */
    public function companiesData(Request $request)
    {
        $company = Company::find($request->id);
        if (!$company) {
            return $this->sendError('Company not found.', "", 404);
        }
        return $this->sendResponse([
            'form_json' => json_decode($company->form_json, true),
            'properties' => $company->properties ?? [],
        ], 'Company form JSON and properties.');
    }
}

