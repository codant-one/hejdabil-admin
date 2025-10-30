<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SignatureController;

use Illuminate\Support\Facades\Event;
use App\Events\NotificationsWebsocketEvent;


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
    // $message = '¡Hola desde la ruta de prueba!';
    // $message = (object) [
    //     'title' => 'Prueba de notificación',
    //     'subtitle' => '¡Hola usuario!',
    //     'time' => now()->format('H:i:s'),
    //     //'img' => 'https://via.placeholder.com/150',
    //     'color' => 'primary',
    //     'icon' => 'tabler-bell',
    //     'text' => '¡Desde la ruta de prueba!',
    // ];

    $message = (object) [
        'title' => 'Aviseringstest',
        'subtitle' => '¡Hej användare!',
        'time' => now()->format('H:i:s'),
        //'img' => 'https://via.placeholder.com/150',
        'color' => 'primary',
        'icon' => 'tabler-bell',
        'text' => '¡Från testvägen!',
    ];
    
    // Log para debug
    // \Log::info('Disparando evento websocket: ' . $message->text);
    
    // Disparar el evento
    $evento = new NotificationsWebsocketEvent($message);
    Event::dispatch($evento);
    
    // Verificar si el evento se está enviando
    // \Log::info('Evento disparado, verificando broadcasting...');
    
    return 'Evento disparado: ' . $message->text . '. Revisa tu navegador y la terminal de websockets.';
});