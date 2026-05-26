<?php

use App\Enums\TicketType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->jsonb('ticket_forms_config')->nullable()->after('form_json');
        });

        $company1Messages = [
            'report'      => 'Ci scusiamo per il disservizio, provvederemo quanto prima a recuperare il rifiuto (entro 24h dalla segnalazione). Lasciarlo esposto. Eventualmente se ci sarà la necessità verrà contattata.',
            'abandonment' => 'La ringraziamo per la segnalazione, provvederemo quanto prima a recuperare il rifiuto (entro 24h dalla segnalazione). Eventualmente se ci sarà la necessità verrà contattata.',
            'reservation' => "La sua segnalazione è stata presa in carico, verrà contattata quanto prima per darle l'appuntamento.",
        ];

        DB::table('companies')->select('id', 'name')->orderBy('id')->each(function ($company) use ($company1Messages) {
            $config = collect(TicketType::cases())
                ->mapWithKeys(function ($type) use ($company, $company1Messages) {
                    $typeConfig = $type->config($company->name);
                    if ($company->id === 1 && isset($company1Messages[$type->value])) {
                        $typeConfig['finalMessage'] = $company1Messages[$type->value];
                    }
                    return [$type->value => $typeConfig];
                })
                ->toArray();

            DB::table('companies')
                ->where('id', $company->id)
                ->update(['ticket_forms_config' => json_encode($config)]);
        });
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropColumn('ticket_forms_config');
        });
    }
};
