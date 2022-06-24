<?php

namespace App\Http\Middleware;

use App\Models\Company;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class checkIfCompanyUser
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $adminID = 1;
        $user = auth()->user();
        // se $user è nullo, l'utente non è loggato e il middleware non fa nulla lascia gestire la request a laravel
        if (is_null($user)) {
            return $next($request);
        }
        $userID = $user->id;
        // trovo tutti gli users che hanno una company
        $allUsersWithCompany =
            Company::all()
            ->filter(
                function ($company) {
                    return $company->user_id != null;
                }
            )->map(
                function ($c) {
                    return $c->user_id;
                }
            )->toArray();
        // se l'utente non è admin e non ha nessuna company è un utente di app e quindi non deve accedere al backend
        if ($userID !== $adminID && !in_array($userID, $allUsersWithCompany)) {
            return new Response(view('loggednocompanyuser', [
                'user' => $user,
            ]));
        }
        return $next($request);
    }
}
