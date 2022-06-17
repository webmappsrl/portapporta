@php
    use Jenssegers\Agent\Agent;
    use App\Models\Zone;
    use App\Models\App;
    $agent = new Agent();

    $zone = Zone::find($user->zone_id)->first();
    $company = strtoupper($zone->company->name);

    $link_apple = '#';
    $link_android = '#';

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
                --swiper-theme-color: #007aff;
                --swiper-navigation-size: 44px;
                line-height: inherit;
                font-family: "Inter";
                box-sizing: border-box;
                border-width: 0;
                border-style: solid;
                --tw-border-opacity: 1;
                --tw-shadow: 0 0 #0000;
                --tw-ring-inset: var(--tw-empty,/*!*/ /*!*/);
                --tw-ring-offset-width: 0px;
                --tw-ring-offset-color: #fff;
                --tw-ring-color: rgba(59, 130, 246, 0.5);
                --tw-ring-offset-shadow: 0 0 #0000;
                --tw-ring-shadow: 0 0 #0000;
                position: fixed;
                bottom: 0px;
                z-index: 1000;
                display: flex;
                align-items: center;
                justify-content: center;
                height: 5rem;
                width: 100%;
                flex-direction: row;
                --tw-bg-opacity: 1;
                padding-left: 1rem;
                padding-right: 1rem;
                box-shadow: 0px -2px 12px rgba(0, 0, 0, 0.2);
            }
            .button-container {
                tab-size: 4;
                -webkit-text-size-adjust: 100%;
                --swiper-theme-color: #007aff;
                --swiper-navigation-size: 44px;
                line-height: inherit;
                font-family: "Inter";
                --tw-bg-opacity: 1;
                box-sizing: border-box;
                border-width: 0;
                border-style: solid;
                --tw-border-opacity: 1;
                --tw-shadow: 0 0 #0000;
                --tw-ring-offset-width: 0px;
                --tw-ring-offset-color: #fff;
                --tw-ring-color: rgba(59, 130, 246, 0.5);
                --tw-ring-offset-shadow: 0 0 #0000;
                --tw-ring-shadow: 0 0 #0000;
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
                --swiper-theme-color: #007aff;
                --swiper-navigation-size: 44px;
                line-height: inherit;
                font-family: "Inter";
                box-sizing: border-box;
                border-width: 0;
                border-style: solid;
                --tw-border-opacity: 1;
                --tw-shadow: 0 0 #0000;
                --tw-ring-offset-width: 0px;
                --tw-ring-offset-color: #fff;
                --tw-ring-color: rgba(59, 130, 246, 0.5);
                --tw-ring-offset-shadow: 0 0 #0000;
                --tw-ring-shadow: 0 0 #0000;
                text-decoration: inherit;
                display: flex;
                height: 3rem;
                width: 100%;
                align-items: center;
                justify-content: center;
                border-radius: 9999px;
                --tw-bg-opacity: 1;
                font-weight: 600;
                --tw-text-opacity: 1;
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
