<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class DisableBuffering
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Disable output buffering for streaming responses
        if ($response->headers->get('Content-Type') === 'text/event-stream') {
            // Disable all output buffering
            while (ob_get_level()) {
                ob_end_clean();
            }
            
            // Set additional headers to prevent buffering
            $response->headers->set('X-Accel-Buffering', 'no');
            $response->headers->set('Cache-Control', 'no-cache');
            $response->headers->set('Connection', 'keep-alive');
        }

        return $response;
    }
}
