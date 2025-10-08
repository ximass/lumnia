<?php

namespace App\Services;

use App\Models\ErrorLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Throwable;

class ErrorLogService
{
    public static function log(Throwable $exception, $request = null): void
    {
        try {
            $level = 'error';
            
            // Verificar se é uma HttpException
            if (method_exists($exception, 'getStatusCode')) {
                $statusCode = $exception->getStatusCode();
                $level = $statusCode < 500 ? 'warning' : 'error';
            }

            $data = [
                'level' => $level,
                'message' => $exception->getMessage(),
                'exception' => get_class($exception),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString(),
            ];

            if ($request) {
                $data['url'] = $request->fullUrl();
                $data['method'] = $request->method();
                $data['ip'] = $request->ip();
                
                // Capturar dados da requisição (exceto senhas)
                $requestData = $request->except(['password', 'password_confirmation', 'token']);
                $data['request_data'] = !empty($requestData) ? $requestData : null;
            }

            if (Auth::check()) {
                $data['user_id'] = Auth::id();
            }

            ErrorLog::create($data);
        } catch (\Exception $e) {
            // Se falhar ao gravar o log, pelo menos registrar no log do Laravel
            Log::error('Failed to create error log entry: ' . $e->getMessage());
        }
    }
}
