<?php

namespace App\Enums;

enum Fonts: string
{

    case HELVETICA = 'Helvetica';
    case INTER = 'Inter';
    case LATO = 'Lato';
    case MERRIWEATHER = 'Merriweather';
    case MONTSERRAT = 'Montserrat';
    case MONTSERRAT_LIGHT = 'Montserrat Light';
    case MONROPE = 'Monrope';
    case NOTO_SANS = 'Noto Sans';
    case NOTO_SERIF = 'Noto Serif';
    case OPEN_SANS = 'Open Sans';
    case ROBOTO = 'Roboto';
    case ROBOTO_SLAB = 'Roboto Slab';
    case SORA = 'Sora';
    case SOURCE_SANS_PRO = 'Source Sans Pro';

    public static function toArray()
    {
        return [
            'Helvetica' => 'Helvetica',
            'Inter' => 'Inter',
            'Lato' => 'Lato',
            'Merriweather' => 'Merriweather',
            'Montserrat' => 'Montserrat',
            'Montserrat Light' => 'Montserrat Light',
            'Monrope' => 'Monrope',
            'Noto Sans' => 'Noto Sans',
            'Noto Serif' => 'Noto Serif',
            'Open Sans' => 'Open Sans',
            'Roboto' => 'Roboto',
            'Roboto Slab' => 'Roboto Slab',
            'Sora' => 'Sora',
            'Source Sans Pro' => 'Source Sans Pro',
        ];
    }
}
