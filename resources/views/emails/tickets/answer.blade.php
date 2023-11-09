<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Risposta al Ticket</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
        }
    </style>
</head>

<body>
    <div>
        <p><strong>Data risposta:</strong> {{ $ticket->updated_at ?? '/' }}</p>
        <p><strong>Email:</strong> {{ $ticket->user->email ?? '/' }}</p>
        <p><strong>Nome:</strong> {{ $ticket->user->name ?? '/' }}</p>
        <p><strong>Tipo segnalazione:</strong> {{ $ticket->ticket_type }}</p>
        <p><strong>Telefono:</strong> {{ $ticket->phone ?? '/' }}</p>
        <p><strong>Note:</strong> {{ $ticket->note ?? '/' }}</p>
        <br>
        <br>
        <br>
    </div>
    <div>
        <h4>Caro {{ $ticket->user->name }},</h4>
        <p>{{ $answer }}</p>
    </div>
</body>

</html>
