<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\Log;

use App\Models\KnowledgeBase;

class KnowledgeBaseController extends Controller
{
    public function getKnowledgeBases(Request $request)
    {
        $knowledgeBases = KnowledgeBase::all();

        return response()->json($knowledgeBases);
    }
}