<?php

namespace App\Http\Controllers;

use App\Enums\TicketType;
use App\Models\Company;
use Illuminate\Http\Request;

class TicketFormsConfigController extends Controller
{
    public function index(Request $request)
    {
        $company = Company::findOrFail($request->id);

        $enumConfig = collect(TicketType::cases())
            ->mapWithKeys(fn(TicketType $type) => [
                $type->value => $type->config($company->name),
            ])
            ->toArray();

        if (!empty($company->ticket_forms_config)) {
            $config = array_replace_recursive($enumConfig, $company->ticket_forms_config);
        } else {
            $config = $enumConfig;
        }

        return $this->sendResponse($config, 'Ticket forms config');
    }
}
