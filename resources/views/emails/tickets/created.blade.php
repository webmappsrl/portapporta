@php
    $site_url = request()->getHost();
    if ($ticket->geometry) {
        $g = json_decode(DB::select("SELECT st_asgeojson('{$ticket->geometry}') as g")[0]->g);
    }
    $trash_type = '';
    $trash_type_color = '#888888';
    if ($ticket->trashType) {
        $trash_type = $ticket->trashType->name;
        $trash_type_color = $ticket->trashType->color ?? '#888888';
    }
    $user_addresses = [];
    if (!empty($ticket->user->addresses)) {
        foreach ($ticket->user->addresses as $count => $address) {
            if ($address->location) {
                $user_addresses[$count]['geometry'] = json_decode(
                    DB::select("SELECT st_asgeojson('{$address->location}') as g")[0]->g,
                );
                $user_addresses[$count]['zone'] = $address->zone ?? null;
                $user_addresses[$count]['city'] = $address->city ?? '';
                $user_addresses[$count]['via'] = $address->address ?? '';
                $user_addresses[$count]['civico'] = $address->house_number ?? '';
            }
        }
    }
    $zone = null;
    $ticket_via = '';
    $ticket_civico = '';
    $ticket_city = '';
    if ($ticket->address) {
        $zone = $ticket->address->zone ?? null;
        $ticket_via = $ticket->address->address ?? '';
        $ticket_civico = $ticket->address->house_number ?? '';
    } elseif ($ticket->zone) {
        $zone = $ticket->zone;
        $parts = explode(', ', $ticket->location_address ?? '', 2);
        $ticket_via = $parts[0] ?? '';
        $ticket_civico = $parts[1] ?? '';
    } else {
        $locationParts = explode(' — ', $ticket->location_address ?? '', 2);
        $addressPart   = $locationParts[0];
        $ticket_city   = $locationParts[1] ?? '';
        $parts         = explode(', ', $addressPart, 2);
        $ticket_via    = $parts[0] ?? '';
        $ticket_civico = $parts[1] ?? '';
    }
    $type_labels = [
        'reservation' => 'Prenotazione ritiro',
        'abandonment' => 'Abbandono rifiuti',
        'report'      => 'Mancata raccolta',
        'info'        => 'Richiesta info',
    ];
    $type_label = $type_labels[$ticket->ticket_type] ?? $ticket->ticket_type;
    $type_color = ($company->primary_color ?? null) ?: '#2471a3';
@endphp
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" lang="it">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Segnalazione #{{ $ticket->id }}</title>
</head>
<body bgcolor="#f4f4f4">

<table border="0" cellpadding="0" cellspacing="0" width="100%" bgcolor="#f4f4f4">
<tr><td align="center" style="padding:20px 0;">

<table border="0" cellpadding="0" cellspacing="0" width="600" bgcolor="#ffffff">

    {{-- ===== HEADER ===== --}}
    <tr>
        <td colspan="2" bgcolor="{{ $type_color }}" style="background-color:{{ $type_color }};padding:22px 24px;">
            <table border="0" cellpadding="0" cellspacing="0" width="100%">
                <tr>
                    <td valign="middle">
                        <table border="0" cellpadding="0" cellspacing="0">
                            <tr><td style="padding-bottom:6px;color:#ffffff;font-size:11px;font-family:Arial,Helvetica,sans-serif;">{{ $type_label }}</td></tr>
                            <tr><td style="padding-bottom:4px;color:#ffffff;font-size:22px;font-family:Arial,Helvetica,sans-serif;"><b>Segnalazione #{{ $ticket->id }}</b></td></tr>
                            <tr><td style="color:#ffffff;font-size:13px;font-family:Arial,Helvetica,sans-serif;">{{ $ticket->created_at }}</td></tr>
                        </table>
                    </td>
                    <td align="right" valign="middle" width="170" style="padding-left:12px;">
                        <table border="0" cellpadding="0" cellspacing="0">
                            <tr>
                                <td bgcolor="#ffffff" style="background-color:#ffffff;padding:9px 16px;">
                                    <a href="https://{{ $site_url }}/resources/tickets/{{ $ticket->id }}"
                                       style="color:{{ $type_color }};font-size:13px;text-decoration:none;font-family:Arial,Helvetica,sans-serif;"><b>Apri nel portale &rarr;</b></a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

    {{-- Spacer --}}
    <tr><td colspan="2" height="14" bgcolor="#f4f4f4" style="font-size:1px;">&nbsp;</td></tr>

    {{-- ===== DOVE INTERVENIRE ===== --}}
    <tr>
        <td colspan="2" bgcolor="#f0f0f0" style="background-color:#f0f0f0;padding:9px 14px 9px 18px;font-size:11px;color:#555555;font-family:Arial,Helvetica,sans-serif;border-left:4px solid {{ $type_color }};"><b>DOVE INTERVENIRE</b></td>
    </tr>

    @if($zone)
    <tr>
        <td width="130" bgcolor="#fafafa" style="background-color:#fafafa;padding:9px 14px;color:#777777;font-size:14px;font-family:Arial,Helvetica,sans-serif;border-bottom:1px solid #eeeeee;">Zona</td>
        <td bgcolor="#fafafa" style="background-color:#fafafa;padding:9px 14px;font-size:14px;color:#222222;font-family:Arial,Helvetica,sans-serif;border-bottom:1px solid #eeeeee;"><b>{{ $zone->label }}</b></td>
    </tr>
    <tr>
        <td width="130" style="padding:9px 14px;color:#777777;font-size:14px;font-family:Arial,Helvetica,sans-serif;border-bottom:1px solid #eeeeee;">Comune</td>
        <td style="padding:9px 14px;font-size:14px;color:#333333;font-family:Arial,Helvetica,sans-serif;border-bottom:1px solid #eeeeee;">{{ $zone->comune }}</td>
    </tr>
    @endif

    @if($ticket_city)
    <tr>
        <td width="130" style="padding:9px 14px;color:#777777;font-size:14px;font-family:Arial,Helvetica,sans-serif;border-bottom:1px solid #eeeeee;">Comune</td>
        <td style="padding:9px 14px;font-size:14px;color:#333333;font-family:Arial,Helvetica,sans-serif;border-bottom:1px solid #eeeeee;">{{ $ticket_city }}</td>
    </tr>
    @endif

    @if($ticket_via)
    <tr>
        <td width="130" bgcolor="#fafafa" style="background-color:#fafafa;padding:9px 14px;color:#777777;font-size:14px;font-family:Arial,Helvetica,sans-serif;border-bottom:1px solid #eeeeee;">Indirizzo</td>
        <td bgcolor="#fafafa" style="background-color:#fafafa;padding:9px 14px;font-size:14px;color:#222222;font-family:Arial,Helvetica,sans-serif;border-bottom:1px solid #eeeeee;"><b>{{ $ticket_via }}{{ $ticket_civico ? ', '.$ticket_civico : '' }}</b></td>
    </tr>
    @endif

    @isset($g)
    <tr>
        <td width="130" style="padding:9px 14px;color:#777777;font-size:14px;font-family:Arial,Helvetica,sans-serif;">Coordinate</td>
        <td style="padding:9px 14px;font-size:14px;color:#333333;font-family:Arial,Helvetica,sans-serif;">
            {{ $g->coordinates[1] }}, {{ $g->coordinates[0] }}&nbsp;&nbsp;<a href="https://www.openstreetmap.org/?mlat={{ $g->coordinates[1] }}&mlon={{ $g->coordinates[0] }}#map=15/{{ $g->coordinates[1] }}/{{ $g->coordinates[0] }}" style="color:#2471a3;font-size:13px;text-decoration:none;font-family:Arial,Helvetica,sans-serif;"><b>[MAPPA]</b></a>
        </td>
    </tr>
    @endisset

    {{-- Spacer --}}
    <tr><td colspan="2" height="14" bgcolor="#f4f4f4" style="font-size:1px;">&nbsp;</td></tr>

    {{-- ===== DETTAGLI SEGNALAZIONE ===== --}}
    <tr>
        <td colspan="2" bgcolor="#f0f0f0" style="background-color:#f0f0f0;padding:9px 14px 9px 18px;font-size:11px;color:#555555;font-family:Arial,Helvetica,sans-serif;border-left:4px solid {{ $type_color }};"><b>DETTAGLI SEGNALAZIONE</b></td>
    </tr>
    <tr>
        <td width="130" bgcolor="#fafafa" style="background-color:#fafafa;padding:9px 14px;color:#777777;font-size:14px;font-family:Arial,Helvetica,sans-serif;border-bottom:1px solid #eeeeee;">Tipo</td>
        <td bgcolor="#fafafa" style="background-color:#fafafa;padding:9px 14px;font-size:14px;color:#333333;font-family:Arial,Helvetica,sans-serif;border-bottom:1px solid #eeeeee;">{{ $type_label }}</td>
    </tr>
    <tr>
        <td width="130" style="padding:9px 14px;color:#777777;font-size:14px;font-family:Arial,Helvetica,sans-serif;{{ !empty($ticket->note) ? 'border-bottom:1px solid #eeeeee;' : '' }}">Spazzatura</td>
        <td style="padding:9px 14px;font-size:14px;font-family:Arial,Helvetica,sans-serif;{{ !empty($ticket->note) ? 'border-bottom:1px solid #eeeeee;' : '' }}">
            @if($trash_type)
                <span style="background-color:{{ $trash_type_color }};color:#ffffff;padding:2px 10px;font-size:12px;font-family:Arial,Helvetica,sans-serif;"><b>{{ $trash_type }}</b></span>
            @else
                <span style="color:#333333;">-</span>
            @endif
        </td>
    </tr>
    @if(!empty($ticket->note))
    <tr>
        <td width="130" bgcolor="#fffdf0" style="background-color:#fffdf0;padding:9px 14px;color:#777777;font-size:14px;font-family:Arial,Helvetica,sans-serif;border-left:3px solid #e0b800;vertical-align:top;">Note</td>
        <td bgcolor="#fffdf0" style="background-color:#fffdf0;padding:9px 14px;font-size:14px;color:#333333;font-family:Arial,Helvetica,sans-serif;font-style:italic;">{{ $ticket->note }}</td>
    </tr>
    @endif

    {{-- Spacer --}}
    <tr><td colspan="2" height="14" bgcolor="#f4f4f4" style="font-size:1px;">&nbsp;</td></tr>

    {{-- ===== CHI HA SEGNALATO ===== --}}
    <tr>
        <td colspan="2" bgcolor="#f0f0f0" style="background-color:#f0f0f0;padding:9px 14px 9px 18px;font-size:11px;color:#555555;font-family:Arial,Helvetica,sans-serif;border-left:4px solid #bbbbbb;"><b>CHI HA SEGNALATO</b></td>
    </tr>

    @include('emails.tickets.partials.user-form-fields', [
        'user'    => $ticket->user,
        'company' => $ticket->company ?? null,
        'ticket'  => $ticket,
        'format'  => 'table',
    ])

    @foreach ($user_addresses as $addr)
    <tr>
        <td width="130" bgcolor="#fafafa" style="background-color:#fafafa;padding:9px 14px;color:#777777;font-size:14px;font-family:Arial,Helvetica,sans-serif;border-top:1px solid #eeeeee;">Indirizzo utente</td>
        <td bgcolor="#fafafa" style="background-color:#fafafa;padding:9px 14px;font-size:14px;color:#333333;font-family:Arial,Helvetica,sans-serif;border-top:1px solid #eeeeee;">
            @if($addr['via']){{ $addr['via'] }}{{ $addr['civico'] ? ', '.$addr['civico'] : '' }}@endif
            @if($addr['zone']) &mdash; {{ $addr['zone']->comune }}@elseif($addr['city']) &mdash; {{ $addr['city'] }}@endif
            @isset($addr['geometry'])
                &nbsp;<a href="https://www.openstreetmap.org/?mlat={{ $addr['geometry']->coordinates[1] }}&mlon={{ $addr['geometry']->coordinates[0] }}#map=15/{{ $addr['geometry']->coordinates[1] }}/{{ $addr['geometry']->coordinates[0] }}" style="color:#2471a3;font-size:13px;text-decoration:none;font-family:Arial,Helvetica,sans-serif;">[MAPPA]</a>
            @endisset
        </td>
    </tr>
    @endforeach

    {{-- ===== FOOTER CTA ===== --}}
    <tr><td colspan="2" height="16" bgcolor="#eeeeee" style="font-size:1px;">&nbsp;</td></tr>
    <tr>
        <td colspan="2" align="center" style="padding:20px 24px;">
            <table border="0" cellpadding="0" cellspacing="0" align="center">
                <tr>
                    <td bgcolor="{{ $type_color }}" style="background-color:{{ $type_color }};padding:13px 36px;">
                        <a href="https://{{ $site_url }}/resources/tickets/{{ $ticket->id }}"
                           style="color:#ffffff;font-size:15px;text-decoration:none;font-family:Arial,Helvetica,sans-serif;">
                            <b>Apri Ticket #{{ $ticket->id }} nel Portale &rarr;</b>
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>

</table>

</td></tr>
</table>
</body>
</html>
