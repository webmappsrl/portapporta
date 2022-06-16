<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {


        $result = collect(Ticket::where('company_id', $request->id)
            ->where('user_id', Auth::user()->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray())->map(function ($item) {
            return $this->iterator($item);
        });



        return $this->sendResponse($result, 'User tickets');
    }

    function iterator($item)
    {
        $geometry = $item['geometry'];
        $g = json_decode(DB::select("SELECT st_asgeojson('$geometry') as g")[0]->g);
        unset($item['geometry']);
        $item['location'] = [$g->coordinates[0], $g->coordinates[1]];
        return $item;
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Auth::user();
        try {
            $request->validate([
                'ticket_type' => [
                    'required',
                    Rule::in(['reservation', 'info', 'abandonment', 'report']),
                ],
            ]);
        } catch (Exception $e) {
            return $this->sendError($e->getMessage());
        }

        // Create Ticket
        $ticket = new Ticket();
        $ticket->ticket_type = $request->ticket_type;
        $ticket->company_id = $request->id;
        $ticket->user_id = Auth::user()->id;
        if ($request->exists('trash_type_id')) {
            $ticket->trash_type_id = $request->trash_type_id;
        }
        if ($request->exists('note')) {
            $ticket->note = $request->note;
        }
        if ($request->exists('phone')) {
            $ticket->phone = $request->phone;
        }
        if ($request->exists('image')) {
            $ticket->image = $request->image;
        }
        if ($request->exists('location')) {
            $ticket->geometry = (DB::select(DB::raw("SELECT ST_GeomFromText('POINT({$request->location[0]} {$request->location[1]})') as g;")))[0]->g;
        }
        $ticket->save();

        // Response
        return $this->sendResponse($ticket, 'Ticket created.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Ticket  $ticket
     * @return \Illuminate\Http\Response
     */
    public function show(Ticket $ticket)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Ticket  $ticket
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Ticket $ticket)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Ticket  $ticket
     * @return \Illuminate\Http\Response
     */
    public function destroy(Ticket $ticket)
    {
        //
    }
}
