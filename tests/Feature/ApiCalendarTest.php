<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * https://portapporta.webmapp.it/api/c/{company_id}/calendar
 * 
 * Restituisce il calendario dell'utente loggato (Zona + UserType) con i successivi 15 elementi
 * con il seguente formato
 *     {
	  '2022-06-03' : [
	    {
		  'trash_types' : [id1,id2,...,idn],
		  'start_time' : '07:00',
		  'stop_time' : '13:00'
		},
	    {
		  'trash_types' : [idA1,idA2,...,idAn],
		  'start_time' : '04:00',
		  'stop_time' : '19:00'
		}
	  ],
	  '2022-06-03' : [
	    {
		  'trash_types' : [id1,id2,...,idn],
		  'start_time' : '07:00',
		  'stop_time' : '13:00'
		},
	    {
 * 
 */
class ApiCalendarTest extends TestCase
{

}
