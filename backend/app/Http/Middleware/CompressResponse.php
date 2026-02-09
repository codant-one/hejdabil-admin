<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CompressResponse
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Solo comprimir respuestas JSON
        if (!$response instanceof \Illuminate\Http\JsonResponse) {
            return $response;
        }

        // Verificar si el cliente acepta compresión
        $acceptEncoding = $request->header('Accept-Encoding', '');
        
        if (strpos($acceptEncoding, 'gzip') !== false) {
            $content = $response->getContent();
            
            // Solo comprimir si el contenido es mayor a 1KB
            if (strlen($content) > 1024) {
                $compressed = gzencode($content, 6); // Nivel de compresión 6 (balanceado)
                
                if ($compressed !== false) {
                    $response->setContent($compressed);
                    $response->headers->set('Content-Encoding', 'gzip');
                    $response->headers->set('Content-Length', strlen($compressed));
                }
            }
        }

        return $response;
    }
}
