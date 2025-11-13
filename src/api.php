<?php
declare(strict_types=1);
/**
* API del Mini-CRUD
* Acciones admitidas: list | create | delete
* Persistencia: archivo JSON (data.json) en el mismo directorio.
*
* Nota didáctica:
* - Este archivo se invoca desde el navegador mediante fetch() (AJAX).
* - Siempre respondemos en JSON.
* - Las validaciones mínimas se realizan en servidor, aunque el cliente valide.
*/
// 1) Todas las respuestas serán JSON UTF-8
header('Content-Type: application/json; charset=utf-8');
// Iniciamos sesión para soportar autenticación simple
session_start();

// Ruta al archivo de usuarios para autenticación
$rutaArchivoUsuarios = __DIR__ . '/users.json';
if (!file_exists($rutaArchivoUsuarios)) {
	file_put_contents($rutaArchivoUsuarios, json_encode([]) . "\n");
}
// Cargar usuarios
$listaUsuariosRegistrados = json_decode((string) file_get_contents($rutaArchivoUsuarios), true);
if (!is_array($listaUsuariosRegistrados)) $listaUsuariosRegistrados = [];

function usuario_autenticado(): ?array {
	return isset($_SESSION['usuario']) && is_array($_SESSION['usuario']) ? $_SESSION['usuario'] : null;
}

function asegurar_autenticado(): void {
	if (!usuario_autenticado()) {
		responder_json_error('No autorizado. Inicie sesión.', 401);
	}
}
/**
* Envía una respuesta de éxito con envoltura homogénea.
*
* @param mixed $contenidoDatos Datos a devolver (ej: lista de usuarios)
* @param int $codigoHttp Código de estado HTTP (200 por defecto).
*/
function responder_json_exito(mixed $contenidoDatos = [], int $codigoHttp = 200): void {
http_response_code($codigoHttp);
echo json_encode(
['ok' => true, 'data' => $contenidoDatos],
JSON_UNESCAPED_UNICODE
);
exit;
}
/**
* Envía una respuesta de error con envoltura homogénea.
*
* @param string $mensajeError Mensaje de error legible para el cliente.
* @param int $codigoHttp Código de estado HTTP (400 por defecto).
*/
function responder_json_error(string $mensajeError, int $codigoHttp = 400): void {
http_response_code($codigoHttp);
echo json_encode(
['ok' => false, 'error' => $mensajeError],
JSON_UNESCAPED_UNICODE
);
exit;
}
// 2) Ruta al archivo de persistencia (misma carpeta)
$rutaArchivoDatosJson = __DIR__ . '/data.json';
// 2.1) Si no existe, lo creamos con un array JSON vacío ([])
if (!file_exists($rutaArchivoDatosJson)) {
file_put_contents($rutaArchivoDatosJson, json_encode([]) . "\n");
}
// 2.2) Cargar su contenido como array asociativo de PHP
$listaUsuarios = json_decode((string) file_get_contents($rutaArchivoDatosJson), true);
// 2.3) Si por cualquier motivo no es un array, lo normalizamos a []
if (!is_array($listaUsuarios)) {
$listaUsuarios = [];
}
// 3) Método HTTP y acción (por querystring o formulario)
// - Por simplicidad: list en GET; create y delete por POST.
// - Si no llega 'action', usamos 'list' como valor por defecto.
$metodoHttpRecibido = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$accionSolicitada = $_GET['action'] ?? $_POST['action'] ?? 'list';
// Acciones públicas: register, login, logout, auth
if ($accionSolicitada === 'register' && $metodoHttpRecibido === 'POST') {
	$cuerpoBruto = (string) file_get_contents('php://input');
	$datos = $cuerpoBruto !== '' ? (json_decode($cuerpoBruto, true) ?? []) : [];
	$nombre = trim((string) ($datos['nombre'] ?? $_POST['nombre'] ?? ''));
	$email = trim((string) ($datos['email'] ?? $_POST['email'] ?? ''));
	$password = (string) ($datos['password'] ?? $_POST['password'] ?? '');
	if ($nombre === '' || $email === '' || $password === '') {
		responder_json_error('Faltan campos requeridos.', 422);
	}
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		responder_json_error('Email inválido.', 422);
	}
	$emailNorm = mb_strtolower($email);
	// comprobar duplicado
	foreach ($listaUsuariosRegistrados as $u) {
		if (isset($u['email']) && mb_strtolower($u['email']) === $emailNorm) {
			responder_json_error('Ya existe un usuario con ese email.', 409);
		}
	}
	$hash = password_hash($password, PASSWORD_DEFAULT);
	$nuevoUsuario = ['nombre' => $nombre, 'email' => $emailNorm, 'password' => $hash];
	$listaUsuariosRegistrados[] = $nuevoUsuario;
	file_put_contents($rutaArchivoUsuarios, json_encode($listaUsuariosRegistrados, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
	// iniciar sesión automáticamente
	$_SESSION['usuario'] = ['nombre' => $nombre, 'email' => $emailNorm];
	responder_json_exito(['nombre' => $nombre, 'email' => $emailNorm], 201);
}

if ($accionSolicitada === 'login' && $metodoHttpRecibido === 'POST') {
	$cuerpoBruto = (string) file_get_contents('php://input');
	$datos = $cuerpoBruto !== '' ? (json_decode($cuerpoBruto, true) ?? []) : [];
	$email = trim((string) ($datos['email'] ?? $_POST['email'] ?? ''));
	$password = (string) ($datos['password'] ?? $_POST['password'] ?? '');
	if ($email === '' || $password === '') responder_json_error('Faltan credenciales.', 422);
	$emailNorm = mb_strtolower($email);
	foreach ($listaUsuariosRegistrados as $u) {
		if (isset($u['email']) && mb_strtolower($u['email']) === $emailNorm) {
			if (isset($u['password']) && password_verify($password, $u['password'])) {
				$_SESSION['usuario'] = ['nombre' => $u['nombre'], 'email' => $emailNorm];
				responder_json_exito(['nombre' => $u['nombre'], 'email' => $emailNorm], 200);
			}
			responder_json_error('Credenciales inválidas.', 401);
		}
	}
	responder_json_error('Credenciales inválidas.', 401);
}

if ($accionSolicitada === 'logout') {
	session_unset();
	session_destroy();
	responder_json_exito([], 200);
}

if ($accionSolicitada === 'auth') {
	$u = usuario_autenticado();
	if ($u) responder_json_exito($u);
	responder_json_error('No autenticado', 401);
}
// 4) LISTAR usuarios: GET /api.php?action=list
if ($metodoHttpRecibido === 'GET' && $accionSolicitada === 'list') {
	asegurar_autenticado();
	responder_json_exito($listaUsuarios); // 200 OK
}
// 5) CREAR usuario: POST /api.php?action=create
// Body JSON esperado: { "nombre": "...", "email": "..." }
if ($metodoHttpRecibido === 'POST' && $accionSolicitada === 'create') {
	asegurar_autenticado();
$cuerpoBruto = (string) file_get_contents('php://input');
$datosDecodificados = $cuerpoBruto !== '' ? (json_decode($cuerpoBruto, true) ?? []) : [];
// Extraemos datos y normalizamos
$nombreUsuarioNuevo = trim((string) ($datosDecodificados['nombre'] ?? $_POST['nombre'] ?? ''));
$correoUsuarioNuevo = trim((string) ($datosDecodificados['email'] ?? $_POST['email'] ?? ''));
$correoUsuarioNormalizado = mb_strtolower($correoUsuarioNuevo);
// Validación mínima en servidor
if ($nombreUsuarioNuevo === '' || $correoUsuarioNuevo === '') {
responder_json_error('Los campos "nombre" y "email" son obligatorios.', 422);
}
if (!filter_var($correoUsuarioNuevo, FILTER_VALIDATE_EMAIL)) {
responder_json_error('El campo "email" no tiene un formato válido.', 422);
}
// Límites razonables para este ejercicio
if (mb_strlen($nombreUsuarioNuevo) > 60) {
responder_json_error('El campo "nombre" excede los 60 caracteres.', 422);
}
if (mb_strlen($correoUsuarioNuevo) > 120) {
responder_json_error('El campo "email" excede los 120 caracteres.', 422);
}
// Evitar duplicados por email
if (existeEmailDuplicado($listaUsuarios, $correoUsuarioNormalizado)) {
responder_json_error('Ya existe un usuario con ese email.', 409);
}
// Agregamos y persistimos (guardamos el email normalizado)
$listaUsuarios[] = [
'nombre' => $nombreUsuarioNuevo,
'email' => $correoUsuarioNormalizado,
];
file_put_contents(
$rutaArchivoDatosJson,
json_encode($listaUsuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n"
);
responder_json_exito($listaUsuarios, 201);
}

// 5.b) ACTUALIZAR usuario: POST /api.php?action=update
// Body JSON esperado: { "index": 0, "nombre": "...", "email": "..." }
if ($metodoHttpRecibido === 'POST' && $accionSolicitada === 'update') {
	$cuerpoBruto = (string) file_get_contents('php://input');
	$datosDecodificados = $cuerpoBruto !== '' ? (json_decode($cuerpoBruto, true) ?? []) : [];
	$indice = isset($datosDecodificados['index']) ? (int) $datosDecodificados['index'] : (isset($_POST['index']) ? (int) $_POST['index'] : null);
	$nombreNuevo = trim((string) ($datosDecodificados['nombre'] ?? $_POST['nombre'] ?? ''));
	$correoNuevo = trim((string) ($datosDecodificados['email'] ?? $_POST['email'] ?? ''));
	$correoNormalizado = mb_strtolower($correoNuevo);

	if ($indice === null) {
		responder_json_error('Falta el parámetro "index" para actualizar.', 422);
	}
	if (!isset($listaUsuarios[$indice])) {
		responder_json_error('El índice indicado no existe.', 404);
	}
	// Validaciones similares a create
	if ($nombreNuevo === '' || $correoNuevo === '') {
		responder_json_error('Los campos "nombre" y "email" son obligatorios.', 422);
	}
	if (!filter_var($correoNuevo, FILTER_VALIDATE_EMAIL)) {
		responder_json_error('El campo "email" no tiene un formato válido.', 422);
	}
	// Evitar duplicados por email, ignorando el propio índice que actualizamos
	foreach ($listaUsuarios as $i => $u) {
		if ($i === $indice) continue;
		if (isset($u['email']) && is_string($u['email']) && mb_strtolower($u['email']) === $correoNormalizado) {
			responder_json_error('Ya existe un usuario con ese email.', 409);
		}
	}
	// Aplicar cambios
	$listaUsuarios[$indice] = [
		'nombre' => $nombreNuevo,
		'email' => $correoNormalizado,
	];
	file_put_contents(
		$rutaArchivoDatosJson,
		json_encode($listaUsuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n"
	);
	responder_json_exito($listaUsuarios, 200);
}
// 6) ELIMINAR usuario: POST /api.php?action=delete
// Body JSON esperado: { "index": 0 }
// Nota: podríamos usar método DELETE; aquí lo simplificamos a POST.
if (($metodoHttpRecibido === 'POST' || $metodoHttpRecibido === 'DELETE') && $accionSolicitada ===
'delete') {
	asegurar_autenticado();
// 6.1) Intentamos obtener el índice por distintos canales
$indiceEnQuery = $_GET['index'] ?? null;
if ($indiceEnQuery === null) {
$cuerpoBruto = (string) file_get_contents('php://input');
if ($cuerpoBruto !== '') {
$datosDecodificados = json_decode($cuerpoBruto, true) ?? [];
$indiceEnQuery = $datosDecodificados['index'] ?? null;
} else {
$indiceEnQuery = $_POST['index'] ?? null;
}
}
// 6.2) Validaciones de existencia del parámetro
if ($indiceEnQuery === null) {
responder_json_error('Falta el pa rámetro "index" para eliminar.', 422);
}
$indiceUsuarioAEliminar = (int) $indiceEnQuery;
if (!isset($listaUsuarios[$indiceUsuarioAEliminar])) {
responder_json_error('El índice indicado no existe.', 404);
}
// 6.3) Eliminamos y reindexamos para mantener la continuidad
unset($listaUsuarios[$indiceUsuarioAEliminar]);
$listaUsuarios = array_values($listaUsuarios);
// 6.4) Guardamos el nuevo estado en disco
file_put_contents(
$rutaArchivoDatosJson,
json_encode($listaUsuarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n"
);
// 6.5) Devolvemos el listado actualizado
responder_json_exito($listaUsuarios); // 200 OK
}
// 7) Si llegamos aquí, la acción solicitada no está soportada
responder_json_error('Acción no soportada. Use list | create | delete', 400);

/**
* Comprueba si ya existe un usuario con el email dado (comparación exacta).
*
* @param array $usuarios Lista actual en memoria.
* @param string $emailNormalizado Email normalizado en minúsculas.
*/
function existeEmailDuplicado(array $usuarios, string $emailNormalizado): bool {
foreach ($usuarios as $u) {
if (isset($u['email']) && is_string($u['email']) && mb_strtolower($u['email']) ===
$emailNormalizado) {
return true;
}
}
return false;
}