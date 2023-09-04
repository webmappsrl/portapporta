<?php

namespace App\Providers;

use App\Models\Company;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();
        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            $company = Company::find($notifiable['app_company_id']);
            $user_name = $notifiable['name'];
            return (new MailMessage)
                ->from('noreply@webmapp.it', "PortAPPorta-$company->name")
                ->subject("Conferma email - APP PortAPPorta-$company->name")
                ->greeting("Salve, $user_name")
                ->line("Riceve questa email perché è stata effettuata una registrazione sulla APP PortAPPorta-$company->name con il suo indirizzo email")
                ->line("Per completare le registrazione faccia click sul pulsante qui sotto che permette di verificare il suo indirizzo email")
                ->action('verifica indirizzo email', $url)
                ->line("Se non ha effettuato lei la registrazione, ignori questa email")
                ->salutation("Grazie, il TEAM di PortAPPorta-$company->name");
        });
        //
    }
}
