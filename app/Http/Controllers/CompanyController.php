<?php

namespace App\Http\Controllers;
use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends Controller
{
    public function index(Request $request)
    {
        $company = Company::find($request->id);
        $formJson = json_decode($company->form_json, true);
        return $this->sendResponse($formJson, 'Json of the registration form fields.');
    }
}
