<?php

use App\Enums\Role;
use App\Models\Activity;
use App\Models\Person;
use App\Models\User;
use App\Services\WhatsappIngestionService;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$checks = [];
$record = function (string $name, bool $ok, ?string $detail = null) use (&$checks): void {
    $checks[] = [$name, $ok, $detail];
};

$local = '9'.random_int(10000000, 99999999);
$normalized = '593'.$local;
$spaced = '+593 '.substr($local, 0, 2).' '.substr($local, 2, 3).' '.substr($local, 5);
$national = '0'.$local;
$plain = '593'.$local;
$refA = 'chatwoot:msg:'.random_int(100000000, 199999999);
$refB = 'chatwoot:msg:'.random_int(200000000, 299999999);
$beforePeople = Person::query()->count();

DB::beginTransaction();

try {
    $service = $app->make(WhatsappIngestionService::class);

    $empty = $service->findDuplicates($spaced, null, 'Prueba Ingesta', null);
    $record('RPC sin coincidencia para número nuevo', $empty->isEmpty());

    $first = $service->ingest([
        'phone' => $spaced,
        'first_name' => 'Prueba Ingesta',
        'result' => 'Mensaje de prueba',
        'external_ref' => $refA,
    ]);
    $record('Insert crea contacto WhatsApp sin gestor', $first['created_contact'] === true
        && $first['person']->owner_id === null
        && $first['person']->sourceLabel() === 'WhatsApp'
        && $first['person']->next_action === 'Responder mensaje entrante');
    $record('phone_normalized une el formato con espacios', $first['person']->phone_normalized === $normalized);
    $record('Actividad guarda el texto y external_ref', $first['created_activity'] === true
        && $first['activity']->body === 'Mensaje de prueba'
        && $first['activity']->user_id === null
        && $first['activity']->external_ref === $refA);

    $second = $service->ingest([
        'phone' => $national,
        'first_name' => 'Prueba Ingesta',
        'result' => 'Segundo mensaje',
        'external_ref' => $refB,
    ]);
    $record('Segundo mensaje no duplica el contacto', $second['created_contact'] === false
        && $second['person']->id === $first['person']->id
        && $second['created_activity'] === true);

    $replay = $service->ingest([
        'phone' => $plain,
        'first_name' => 'Prueba Ingesta',
        'result' => 'Segundo mensaje',
        'external_ref' => $refB,
    ]);
    $record('Mismo external_ref no duplica la actividad', $replay['created_activity'] === false
        && $replay['activity']->id === $second['activity']->id
        && Activity::query()->where('person_id', $first['person']->id)->count() === 2);

    $variant = $service->findDuplicates($plain, null, null, null);
    $record('Variante 593… resuelve el mismo contacto', $variant->count() === 1
        && $variant->first()->id === $first['person']->id);

    $kernel = $app->make(HttpKernel::class);
    $anonymous = $kernel->handle(Request::create('/api/v1/channels', 'GET', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
    ]));
    $record('Sin token no lee channels', $anonymous->getStatusCode() === 401);

    $anonymousWrite = $kernel->handle(Request::create('/api/v1/whatsapp/messages', 'POST', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
        'CONTENT_TYPE' => 'application/json',
    ], json_encode([
        'phone' => $national,
        'result' => 'no debe persistir',
        'external_ref' => 'chatwoot:msg:'.random_int(300000000, 399999999),
    ], JSON_UNESCAPED_UNICODE)));
    $record('Sin token no escribe mensajes', $anonymousWrite->getStatusCode() === 401);

    $user = User::query()->create([
        'name' => 'Verificación ingesta',
        'email' => 'ingesta-'.Str::uuid().'@example.test',
        'password' => Hash::make(Str::random(40)),
        'role' => Role::Admin,
    ]);
    $accessToken = $user->createToken('verificacion-ingesta');

    $authorized = $kernel->handle(Request::create('/api/v1/contacts/find-duplicates', 'POST', [], [], [], [
        'HTTP_ACCEPT' => 'application/json',
        'CONTENT_TYPE' => 'application/json',
        'HTTP_AUTHORIZATION' => 'Bearer '.$accessToken->plainTextToken,
    ], json_encode(['phone' => $national], JSON_UNESCAPED_UNICODE)));
    $payload = json_decode($authorized->getContent(), true);
    $record('Token autenticado ejecuta la búsqueda', $authorized->getStatusCode() === 200
        && count($payload['data'] ?? []) === 1
        && ($payload['data'][0]['phone_normalized'] ?? null) === $normalized);

    $accessToken->accessToken->delete();
} catch (Throwable $exception) {
    $record('Ejecución del script', false, $exception->getMessage());
} finally {
    try {
        DB::rollBack();
    } catch (Throwable) {
        // La transacción ya estaba cerrada.
    }
}

$leftover = Person::query()->where('phone_normalized', $normalized)->get();
foreach ($leftover as $person) {
    Activity::query()->where('person_id', $person->id)->delete();
    $person->delete();
}
User::query()->where('email', 'like', 'ingesta-%@example.test')->delete();

$record('La prueba no deja contactos nuevos', Person::query()->count() === $beforePeople);

$failed = 0;
foreach ($checks as [$name, $ok, $detail]) {
    echo ($ok ? 'OK   ' : 'FAIL ').$name.($detail ? ' — '.$detail : '').PHP_EOL;
    if (! $ok) {
        $failed++;
    }
}

echo $failed === 0
    ? "Checklist de ingesta: todo OK\n"
    : "Checklist de ingesta: {$failed} fallos\n";

exit($failed === 0 ? 0 : 1);
