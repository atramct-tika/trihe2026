<?php
/**
 * Formulario de contacto TRIHE — envío por correo con mail() de PHP.
 * Requisitos en cdmon: PHP 8.x y el servicio de correo del hosting activo.
 * Destinatario: cambiar $destino si el buzón cambia.
 */

declare(strict_types=1);

$destino = 'info@trihe.es';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    header('Location: /contacto/', true, 303);
    exit;
}

// Honeypot: los robots rellenan el campo oculto "web"; las personas no.
if (!empty($_POST['web'])) {
    header('Location: /contacto/gracias/', true, 303);
    exit;
}

$nombre   = trim((string)($_POST['nombre'] ?? ''));
$telefono = trim((string)($_POST['telefono'] ?? ''));
$email    = trim((string)($_POST['email'] ?? ''));
$asunto   = trim((string)($_POST['asunto'] ?? ''));
$viviendas = preg_replace('/\D/', '', (string)($_POST['viviendas'] ?? ''));
$viviendas = substr($viviendas, 0, 4);
$mensaje  = trim((string)($_POST['mensaje'] ?? ''));
$consent  = ($_POST['privacidad'] ?? '') === 'si';

if (!in_array($asunto, ['', 'Abogadas', 'Administración de Fincas'], true)) {
    $asunto = '';
}

$abogada = trim((string)($_POST['abogada'] ?? ''));
if (!in_array($abogada, ['', 'Conchi Herrero', 'Mercedes Jiménez'], true)) {
    $abogada = '';
}

$emailValido = $email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
$telefonoValido = strlen(preg_replace('/\D/', '', $telefono)) >= 9;
if ($nombre === '' || $mensaje === '' || !$emailValido || !$consent
    || !$telefonoValido
    || mb_strlen($nombre) > 200 || mb_strlen($telefono) > 40
    || mb_strlen($mensaje) > 5000) {
    http_response_code(400);
    header('Location: /contacto/error/', true, 303);
    exit;
}

// Evita inyección de cabeceras en el correo
$nombreLimpio = str_replace(["\r", "\n"], ' ', $nombre);

$cuerpo = "Consulta desde www.trihe.es\n"
        . "----------------------------------------\n"
        . "Nombre:   {$nombreLimpio}\n"
        . "Teléfono: " . str_replace(["\r", "\n"], ' ', $telefono) . "\n"
        . "Email:    " . ($email !== '' ? $email : '(no indicado)') . "\n"
        . "Asunto:   " . ($asunto !== '' ? $asunto : '(sin indicar)') . "\n"
        . ($abogada !== '' ? "Cita con: {$abogada}\n" : '')
        . ($viviendas !== '' ? "Viviendas: {$viviendas}\n" : '')
        . "Fecha:    " . date('d/m/Y H:i') . "\n"
        . "----------------------------------------\n\n"
        . $mensaje . "\n";

$asunto = '=?UTF-8?B?' . base64_encode("Consulta web de {$nombreLimpio}") . '?=';
$cabeceras = "From: TRIHE Web <no-reply@trihe.es>\r\n"
           . ($email !== '' ? "Reply-To: {$email}\r\n" : '')
           . "Content-Type: text/plain; charset=UTF-8\r\n"
           . "Content-Transfer-Encoding: 8bit";

$enviado = @mail($destino, $asunto, $cuerpo, $cabeceras);

header('Location: ' . ($enviado ? '/contacto/gracias/' : '/contacto/error/'), true, 303);
exit;
