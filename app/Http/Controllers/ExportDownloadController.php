<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;

class ExportDownloadController extends Controller
{
    public function download($fileName)
    {
        if (!Storage::disk('public')->exists($fileName)) {
            abort(404);
        }

        $path = Storage::path('public/' . $fileName);
        $response = response()->download($path)->deleteFileAfterSend(true);

        return $response;
    }
}
