<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SignatureController;

use Illuminate\Support\Facades\Event;
use App\Events\PruebaWebsocketEvent;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});
Route::get('/sign/{token}', [SignatureController::class, 'showSigningPage'])->name('contracts.sign');

Route::get('/prueba-websocket', function () {
    $mensaje = '¡Hola desde la ruta de prueba!';
    
    // Log para debug
    \Log::info('Disparando evento websocket: ' . $mensaje);
    
    // Disparar el evento
    $evento = new PruebaWebsocketEvent($mensaje);
    Event::dispatch($evento);
    
    // Verificar si el evento se está enviando
    \Log::info('Evento disparado, verificando broadcasting...');
    
    return 'Evento disparado: ' . $mensaje . '. Revisa tu navegador y la terminal de websockets.';
});