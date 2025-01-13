<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

use App\Models\Message;
use App\Models\InformationSource;

class MessageController extends Controller
{
    public function getInformationSources(Request $request, Message $message)
    {
        $informationSources = $message->informationSources;

        return response()->json($informationSources);
    }
}