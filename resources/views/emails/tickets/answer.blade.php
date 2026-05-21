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
        <h3>Segnalazione</h3>
        <p><strong>Data risposta:</strong> {{ $ticket->updated_at ?? '/' }}</p>
        @include('emails.tickets.partials.user-form-fields', [
            'user' => $ticket->user,
            'company' => $ticket->company ?? null,
            'ticket' => $ticket,
            'format' => 'paragraph',
        ])
        <p><strong>Tipo segnalazione:</strong> {{ $ticket->ticket_type }}</p>
        <p><strong>Note:</strong> {{ $ticket->note ?? '/' }}</p>
        <br>
        <br>
        <br>
    </div>
    <div>
        <h3>Risposta</h3>
        <h4>Caro {{ $ticket->user->name }},</h4>
        <div>{!! $answer !!}</div>
    </div>
</body>

</html>
