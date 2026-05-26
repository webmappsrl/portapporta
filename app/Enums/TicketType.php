<?php

namespace App\Enums;

enum TicketType: string
{
    case Report      = 'report';
    case Abandonment = 'abandonment';
    case Reservation = 'reservation';
    case Info        = 'info';

    public function label(): string
    {
        return match($this) {
            self::Report      => 'Segnalazione mancato ritiro',
            self::Abandonment => 'Segnalazione di abbandono',
            self::Reservation => 'Prenotazione servizio di ritiro',
            self::Info        => 'Richiesta informazioni',
        };
    }

    public function finalMessage(): string
    {
        return 'Puoi visualizzarla nella sezione "i miei ticket".';
    }

    public function config(string $companyName): array
    {
        return match($this) {
            self::Report => [
                'ticketType'   => $this->value,
                'label'        => $this->label(),
                'cancel'       => 'Sicuro di voler cancellare la prenotazione?',
                'finalMessage' => $this->finalMessage(),
                'pages'        => 6,
                'step'         => [
                    [
                        'label'    => "Questo servizio ti permette di segnalare ad $companyName un mancato ritiro del servizio di raccolta porta a porta. Al termine ti verrà assegnato un codice e verrà inviata una email a $companyName: verrai ricontattato in caso di necessità. Vai avanti per iniziare.",
                        'type'     => 'label',
                        'required' => false,
                    ],
                    [
                        'label'    => 'Scegli il tipo di servizio che non ha funzionato:',
                        'type'     => 'calendar_trash_type_id',
                        'required' => true,
                        'recap'    => 'Servizio',
                    ],
                    [
                        'label'    => 'Puoi aggiungere una foto: ci aiuterà a capire meglio cosa è successo',
                        'type'     => 'image',
                        'required' => false,
                        'recap'    => 'Immagine',
                    ],
                    [
                        'label'    => 'Se lo ritieni opportuno puoi inserire delle note',
                        'type'     => 'note',
                        'required' => false,
                        'recap'    => 'Note',
                    ],
                    [
                        'label'    => 'Inserisci un numero di telefono',
                        'type'     => 'phone',
                        'required' => true,
                        'recap'    => 'Telefono',
                    ],
                    [
                        'label'    => '',
                        'type'     => 'recap',
                        'required' => false,
                    ],
                ],
            ],

            self::Abandonment => [
                'ticketType'   => $this->value,
                'label'        => $this->label(),
                'cancel'       => 'Sicuro di voler cancellare la prenotazione?',
                'finalMessage' => $this->finalMessage(),
                'pages'        => 6,
                'step'         => [
                    [
                        'label'    => "Questo servizio ti permette di inviare una segnalazione di abbandono ad $companyName. Al termine della segnalazione ti verrà assegnato un codice e verrà inviata una email a $companyName. Vai avanti per iniziare.",
                        'type'     => 'label',
                        'required' => false,
                    ],
                    [
                        'label'    => 'Scegli il tipo di abbandono:',
                        'type'     => 'trash_type_id',
                        'required' => true,
                        'recap'    => 'Servizio',
                    ],
                    [
                        'label'       => 'Seleziona il luogo:',
                        'type'        => 'location',
                        'required'    => true,
                        'recap'       => 'Indirizzo',
                        'userAddress' => true,
                    ],
                    [
                        'label'    => 'Aggiungi una foto: ci aiuterà a capire la situazione',
                        'type'     => 'image',
                        'required' => false,
                        'recap'    => 'Immagine',
                    ],
                    [
                        'label'    => 'Se lo ritieni opportuno puoi inserire delle note',
                        'type'     => 'note',
                        'required' => false,
                        'recap'    => 'Note',
                    ],
                    [
                        'label'    => 'Inserisci un numero di telefono',
                        'type'     => 'phone',
                        'required' => true,
                        'recap'    => 'Telefono',
                    ],
                    [
                        'label'    => '',
                        'type'     => 'recap',
                        'required' => false,
                    ],
                ],
            ],

            self::Reservation => [
                'ticketType'   => $this->value,
                'label'        => $this->label(),
                'cancel'       => 'Sicuro di voler cancellare la prenotazione?',
                'finalMessage' => $this->finalMessage(),
                'pages'        => 6,
                'step'         => [
                    [
                        'label'    => "Questo servizio ti permette di inviare una richiesta di prenotazione di un servizio $companyName. Al termine della segnalazione ti verrà assegnato un codice della segnalazione e verrà inviata una email a $companyName: verrai ricontattato per concordare i dettagli della prenotazione. Vai avanti per iniziare.",
                        'type'     => 'label',
                        'required' => false,
                    ],
                    [
                        'label'    => 'Scegli il tipo di servizio da prenotare:',
                        'type'     => 'trash_type_id',
                        'required' => true,
                        'recap'    => 'Servizio',
                    ],
                    [
                        'label'       => 'Seleziona il luogo:',
                        'type'        => 'location',
                        'required'    => true,
                        'recap'       => 'Indirizzo',
                        'userAddress' => true,
                    ],
                    [
                        'label'    => 'Puoi aggiungere una foto: ci aiuterà a capire meglio la situazione',
                        'type'     => 'image',
                        'required' => false,
                        'recap'    => 'Immagine',
                    ],
                    [
                        'label'    => 'Se lo ritieni opportuno, puoi inserire delle note',
                        'type'     => 'note',
                        'required' => false,
                        'recap'    => 'Note',
                    ],
                    [
                        'label'    => 'Inserisci un numero di telefono',
                        'type'     => 'phone',
                        'required' => true,
                        'recap'    => 'Telefono',
                    ],
                    [
                        'label'    => '',
                        'type'     => 'recap',
                        'required' => false,
                    ],
                ],
            ],

            self::Info => [
                'ticketType'   => $this->value,
                'label'        => $this->label(),
                'cancel'       => 'Uscendo perderai tutti i dati inseriti. Sicuro di volerlo fare?',
                'finalMessage' => $this->finalMessage(),
                'pages'        => 4,
                'step'         => [
                    [
                        'label'    => "Questo servizio ti permette di richiedere informazioni direttamente a $companyName. Al termine ti verrà assegnato un codice della richiesta e verrà inviata una email a $companyName: verrai ricontattato appena possibile. Clicca sul bottone per iniziare.",
                        'type'     => 'label',
                        'required' => false,
                    ],
                    [
                        'label'    => "Scrivi qui le informazioni che vorresti richiedere a $companyName",
                        'type'     => 'note',
                        'required' => true,
                        'value'    => '',
                        'recap'    => 'Richiesta',
                    ],
                    [
                        'label'    => 'Inserisci un numero di telefono',
                        'type'     => 'phone',
                        'required' => true,
                        'recap'    => 'Telefono',
                    ],
                    [
                        'label'    => '',
                        'type'     => 'recap',
                        'required' => false,
                    ],
                ],
            ],
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
