@php
    $site_url = request()->getHost();
    $g = json_decode(DB::select("SELECT st_asgeojson('{$ticket->geometry}') as g")[0]->g);
    $trash_type = '';
    if ($ticket->trashType) {
        $trash_type = $ticket->trashType->name;
    }
@endphp
<div>
    Data segnalazione: {{ $ticket->created_at }}<br>
    Email: {{ $ticket->user->email }}<br>
    Nome: {{ $ticket->user->name }}<br>
    Tipo segnalazione: {{ $ticket->ticket_type }}<br>
    Tipo spazzatura: {{ $trash_type }}<br>
    Posizione (lat,lon): {{ $g->coordinates[0] }},{{ $g->coordinates[1] }}<br>
    Indirizzo: {{ $ticket->location_address }}<br>
    Telefono: {{ $ticket->phone }}<br>
    Note: {{ $ticket->note }}<br>
    Link al Ticket: {{$site_url . '/resources/tickets/' . $ticket->id}}
</div>