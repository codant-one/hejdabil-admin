<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SignatureController;

use Illuminate\Support\Facades\Event;
use App\Events\NotificationsWebsocketEvent;
use Illuminate\Http\Request;

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

Route::get('/notifications-websocket', function (Request $request) {

    $inputText = (string) $request->query('text', '');

    // Si no se envió el parámetro 'text', mostrar el formulario
    if (!$request->has('text')) {
        $html = '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Notificaciones Websocket - Prueba</title><style>body{font-family:system-ui,-apple-system,Segoe UI,Roboto,Ubuntu,Cantarell,Noto Sans,Arial;margin:2rem}.card{max-width:560px;padding:1.25rem;border:1px solid #e5e7eb;border-radius:12px}.row{display:flex;gap:.5rem}input[type=text]{flex:1;padding:.625rem .75rem;border-radius:8px;border:1px solid #cbd5e1}button{padding:.625rem .9rem;border-radius:8px;border:1px solid #0ea5e9;background:#0ea5e9;color:#fff;cursor:pointer}button:hover{background:#0284c7;border-color:#0284c7}.muted{color:#64748b;font-size:.9rem;margin-top:.5rem}.result{margin-top:1rem;color:#111827;font-weight:500}.error{color:#b91c1c}</style></head><body><div class="card"><h2>Enviar notificación de prueba</h2><form id="notify-form" class="row" action="/notifications-websocket" method="get"><input type="text" name="text" id="text" placeholder="Escribe el mensaje a enviar" value="Hola, esto es una notificación" required minlength="1" pattern=".*\\S.*" title="Escribe al menos un carácter no vacío"><button type="submit">Enviar</button></form><p class="muted">Se emitirá un evento vía websockets y esta ruta devolverá el texto enviado.</p><div id="result" class="result"></div></div><script>const form=document.getElementById("notify-form"),res=document.getElementById("result");form.addEventListener("submit",async e=>{e.preventDefault();if(!form.reportValidity())return;const p=new URLSearchParams(new FormData(form)),r=await fetch(form.action+"?"+p.toString());const t=await r.text();res.textContent=t;res.classList.toggle("error",!r.ok);});</script></body></html>';
        return response($html)->header('Content-Type', 'text/html; charset=utf-8');
    }

    // Validación: no permitir texto vacío o sólo espacios
    if (trim($inputText) === '') {
        return response('El campo "text" es requerido.', 422)->header('Content-Type', 'text/plain; charset=utf-8');
    }

    $message = (object) [
        'title' => 'Aviseringstest',
        'subtitle' => '¡Hej användare!',
        'time' => now()->format('H:i:s'),
        'img' => 'https://demos.pixinvent.com/vuexy-vuejs-laravel-admin-template/demo-3/build/assets/avatar-5-CmycerLe.png',
        'color' => 'primary',
        'icon' => 'tabler-bell',
        'text' => $inputText,
    ];

    $evento = new NotificationsWebsocketEvent($message);
    Event::dispatch($evento);

    return 'Evento disparado: ' . $message->text . '. Revisa tu navegador y la terminal de websockets.';
});