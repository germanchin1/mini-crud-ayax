<?php
/**
 * API mínima pensada para ser fácil de leer.
 * Cada acción (register, login, list, etc.) tiene su propia función handle_*.
 * Las utilidades de lectura/escritura JSON también viven en funciones cortas.
 */
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
session_start();

// Archivos de persistencia sencillos (si no existen se crean vacíos)
$USERS_FILE = __DIR__ . '/users.json';
$DATA_FILE = __DIR__ . '/data.json';
if (!file_exists($USERS_FILE)) file_put_contents($USERS_FILE, json_encode([]) . "\n");
if (!file_exists($DATA_FILE)) file_put_contents($DATA_FILE, json_encode([]) . "\n");

// ------------------------
// UTILIDADES GENERALES
// ------------------------

function ok($data = []): void { http_response_code(200); echo json_encode(['ok' => true, 'data' => $data]); exit; }
function err(string $msg, int $code = 400): void { http_response_code($code); echo json_encode(['ok' => false, 'error' => $msg]); exit; }

function get_action(): string {
	return $_GET['action'] ?? $_POST['action'] ?? 'list';
}

function read_json(string $path): array {
	return json_decode((string) file_get_contents($path), true) ?: [];
}

function write_json(string $path, array $payload): void {
	file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n");
}

function get_body(): array {
	$raw = json_decode(file_get_contents('php://input'), true);
	return is_array($raw) ? $raw : ($_POST ?: []);
}

function require_auth(): void {
	if (!isset($_SESSION['user'])) err('No autorizado', 401);
}

function load_users(): array {
	global $USERS_FILE;
	return read_json($USERS_FILE);
}

function save_users(array $users): void {
	global $USERS_FILE;
	write_json($USERS_FILE, $users);
}

function load_entries(): array {
	global $DATA_FILE;
	return read_json($DATA_FILE);
}

function save_entries(array $entries): void {
	global $DATA_FILE;
	write_json($DATA_FILE, $entries);
}

// ------------------------
// HANDLERS DE AUTENTICACIÓN
// ------------------------

function handle_register(): void {
	// Paso 1: leer datos tal cual vienen.
	$b = get_body();
	$name = trim((string)($b['nombre'] ?? ''));
	$email = trim(mb_strtolower((string)($b['email'] ?? '')));
	$pass = (string)($b['password'] ?? '');
	if ($name === '' || $email === '' || $pass === '') err('Completa todos los campos', 422);
	// Paso 2: evitar duplicados de forma muy directa.
	$users = load_users();
	foreach ($users as $user) {
		if (($user['email'] ?? '') === $email) err('Ese email ya existe', 409);
	}
	// Paso 3: guardar y dejar sesión iniciada.
	$users[] = ['nombre' => $name, 'email' => $email, 'password' => password_hash($pass, PASSWORD_DEFAULT)];
	save_users($users);
	$_SESSION['user'] = ['nombre' => $name, 'email' => $email];
	ok($_SESSION['user']);
}

function handle_login(): void {
	// Solo necesitamos email + password. Nada más.
	$b = get_body();
	$email = trim(mb_strtolower((string)($b['email'] ?? '')));
	$pass = (string)($b['password'] ?? '');
	if ($email === '' || $pass === '') err('Escribe email y contraseña', 422);
	foreach (load_users() as $user) {
		$matchesEmail = (($user['email'] ?? '') === $email);
		$matchesPass = isset($user['password']) && password_verify($pass, $user['password']);
		if ($matchesEmail && $matchesPass) {
			$_SESSION['user'] = ['nombre' => $user['nombre'], 'email' => $email];
			ok($_SESSION['user']);
		}
	}
	err('Credenciales inválidas', 401);
}

function handle_logout(): void {
	// Cerrar sesión es literalmente limpiar $_SESSION y responder ok.
	session_unset();
	session_destroy();
	ok([]);
}

function handle_auth(): void {
	// Devuelve los datos de sesión si existen.
	if (isset($_SESSION['user'])) ok($_SESSION['user']);
	err('No autenticado', 401);
}

// ------------------------
// HANDLERS DEL CRUD
// ------------------------

function handle_list(): void {
	// Leer todo el archivo y devolverlo tal cual.
	require_auth();
	ok(load_entries());
}

function handle_create(): void {
	// Agregar un registro nuevo con nombre + email.
	require_auth();
	$b = get_body();
	$name = trim((string)($b['nombre'] ?? ''));
	$email = trim((string)($b['email'] ?? ''));
	if ($name === '' || $email === '') err('Completa nombre y email', 422);
	$entries = load_entries();
	$entries[] = ['nombre' => $name, 'email' => $email];
	save_entries($entries);
	ok($entries);
}

function handle_delete(): void {
	// Borrar por posición en el array.
	require_auth();
	$idx = (int) (($b = get_body())['index'] ?? -1);
	$entries = load_entries();
	if (!isset($entries[$idx])) err('Índice no existe', 404);
	array_splice($entries, $idx, 1);
	save_entries($entries);
	ok($entries);
}

function handle_update(): void {
	// Reemplazar un registro existente.
	require_auth();
	$b = get_body();
	$idx = (int)($b['index'] ?? -1);
	$name = trim((string)($b['nombre'] ?? ''));
	$email = trim((string)($b['email'] ?? ''));
	$entries = load_entries();
	if (!isset($entries[$idx])) err('Índice no existe', 404);
	if ($name === '' || $email === '') err('Completa nombre y email', 422);
	$entries[$idx] = ['nombre' => $name, 'email' => $email];
	save_entries($entries);
	ok($entries);
}

// ------------------------
// LLamar a las funciones 
// ------------------------

$action = get_action();

switch ($action) {
	case 'register': handle_register(); break;
	case 'login': handle_login(); break;
	case 'logout': handle_logout(); break;
	case 'auth': handle_auth(); break;
	case 'list': handle_list(); break;
	case 'create': handle_create(); break;
	case 'delete': handle_delete(); break;
	case 'update': handle_update(); break;
	default: err('Acción no soportada', 400);
}
