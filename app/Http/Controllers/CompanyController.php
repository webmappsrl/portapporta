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
}

