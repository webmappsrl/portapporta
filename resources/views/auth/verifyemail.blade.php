@php
    use Jenssegers\Agent\Agent;
    use App\Models\Zone;
    use App\Models\App;
    $agent = new Agent();

    $zone = Zone::find($user->zone_id)->first();
    $company = strtoupper($zone->company->name);

    $link_apple = $zone->company->ios_store_link;
    $link_android = $zone->company->android_store_link;

    if ($already_validated) {
        $verification_message = "La tua email è già stata convalidata";
    } else {
        $verification_message = "Da ora puoi accedere alla applicazione con le credenziali che ha creato durante la regsitrazione.";
    }
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>portAPPorta - {{$company}}</title>

        <!-- Fonts -->
        <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap" rel="stylesheet">

        
        <style>
            body {
                font-family: 'Nunito', sans-serif;
            }
            .content {
                position: relative;
                display: flex;
                align-items: center;
                justify-content: center;
                width: 100%;
                height: 100vh;
                flex-direction: column;
            }
            p {
                font-size: 18px;
                text-align: center;
            }
            .button-wrapper {
                tab-size: 4;
                -webkit-text-size-adjust: 100%;
                line-height: inherit;
                box-sizing: border-box;
                border-width: 0;
                border-style: solid;
                position: fixed;
                bottom: 0px;
                z-index: 1000;
                display: flex;
                align-items: center;
                justify-content: center;
                height: 5rem;
                width: 100%;
                flex-direction: row;
                padding-left: 1rem;
                padding-right: 1rem;
                box-shadow: 0px -2px 12px rgba(0, 0, 0, 0.2);
            }
            .button-container {
                tab-size: 4;
                line-height: inherit;
                box-sizing: border-box;
                border-width: 0;
                border-style: solid;
                display: flex;
                width: 33.333333%;
                align-items: center;
                justify-content: space-between;
            }
            .button {
                tab-size: 4;
                background-color: #007aff;
                color: white;
                -webkit-text-size-adjust: 100%;
                line-height: inherit;
                box-sizing: border-box;
                border-width: 0;
                border-style: solid;
                text-decoration: inherit;
                display: flex;
                height: 3rem;
                width: 100%;
                align-items: center;
                justify-content: center;
                border-radius: 9999px;
                font-weight: 600;
            }
        </style>
    </head>
    <body class="antialiased">
        <div class="content">
            <p>Grazie, {{$user->name}} per aver completato la registrazione sulla APP PortAPPorta.</p>
            <p>{{$verification_message}}</p>

            <div class="button-wrapper">
                <div class="button-container">
                    @if ($agent->is('iPhone'))
                        <a target="_blank" class="button" href="{{$link_apple}}">Vai alla APP</a>
                    @else
                        <a target="_blank" class="button" href="{{$link_android}}">Vai alla APP</a>
                    @endif
                </div>
            </div>
        </div>
    </body>
</html>
