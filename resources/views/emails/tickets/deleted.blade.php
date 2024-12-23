@php
$site_url = request()->getHost();
if ($ticket->geometry) {
$g = json_decode(DB::select("SELECT st_asgeojson('{$ticket->geometry}') as g")[0]->g);
}
$trash_type = '';
if ($ticket->trashType) {
$trash_type = $ticket->trashType->name;
}
$user_addresses = [];
if (!empty($ticket->user->addresses)){
foreach ($ticket->user->addresses as $count => $address){
if ($address->location) {
$user_addresses[$count]['geometry'] = json_decode(DB::select("SELECT st_asgeojson('{$address->location}') as g")[0]->g);
$addresse_string = implode(' ', [$address->address, $address->house_number, $address->city]);
$user_addresses[$count]['address'] = $addresse_string;
}
}
}
@endphp
<div>
    <h2>Segnalazione Cancellata #{{ $ticket->id }}</h2>
    Data cancellazione: {{ $ticket->updated_at }}<br>
    Email: {{ $ticket->user->email }}<br>
    Nome: {{ $ticket->user->name }}<br>
    Codice fiscale: {{ $ticket->user->fiscal_code }}<br>
    Codice Utente: {{ $ticket->user->user_code }}<br>
    Telefono: {{ $ticket->phone }}<br>
    @if(!empty($user_addresses))
    <br>
    <strong>{{ count($user_addresses) > 1 ? "Gli indirizzi" : "L'indirizzo" }} dell'utente:</strong><br>
    @foreach($user_addresses as $count => $address)

    Posizione (lat,lon): {{ $address['geometry']->coordinates[1] }},{{ $address['geometry']->coordinates[0] }} <a href="https://www.openstreetmap.org/?mlat={{ $address['geometry']->coordinates[1] }}&mlon={{ $address['geometry']->coordinates[0] }}#map=15/{{ $address['geometry']->coordinates[1] }}/{{ $address['geometry']->coordinates[0] }}">MAPPA</a><br>
    Indirizzo: {{ $address['address'] }}<br>
    @endforeach
    @endif
    Tipo segnalazione: {{ $ticket->ticket_type }}<br>
    Tipo spazzatura: {{ $trash_type }}<br>
    @isset($g)
    <br>
    <strong>L'indirizzo per cui l'utente ha fatto la segnalazione:</strong><br>
    Posizione (lat,lon): {{ $g->coordinates[1] }},{{ $g->coordinates[0] }} <a href="https://www.openstreetmap.org/?mlat={{ $g->coordinates[1] }}&mlon={{ $g->coordinates[0] }}#map=15/{{ $g->coordinates[1] }}/{{ $g->coordinates[0] }}">MAPPA</a><br>
    Indirizzo: {{ $ticket->location_address }}<br>
    @endisset
    Note: {{ $ticket->note }}<br>
    Link al Ticket: <a href="https://{{$site_url . '/resources/tickets/' . $ticket->id}}">https://{{$site_url . '/resources/tickets/' . $ticket->id}}</a>
</div>
