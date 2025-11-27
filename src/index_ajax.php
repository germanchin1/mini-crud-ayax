<?php
// Página protegida mínima. Redirige al login si no hay sesión.
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: /login.html');
    exit;
}

// Solo los administradores pueden ver este panel.
$role = $_SESSION['user']['role'] ?? 'user';
if ($role !== 'admin') {
    header('Location: /sin-permiso.html');
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Mini CRUD (protegido)</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
    <header>
        <h1>Mini CRUD — Sesión</h1>
        <div>Bienvenido, <?php echo htmlspecialchars($_SESSION['user']['nombre']); ?> — <button id="logout">Cerrar sesión</button></div>
    </header>

    <main>
        <section>
            <h2>Agregar usuario</h2>
            <form id="formCreate">
                <input name="nombre" placeholder="Nombre" required>
                <input name="email" type="email" placeholder="Email" required>
                <button type="submit">Agregar</button>
            </form>
        </section>

        <section>
            <h2>Listado</h2>
            <table>
                <thead><tr><th>#</th><th>Nombre</th><th>Email</th><th>Acción</th></tr></thead>
                <tbody id="tbody"><tr id="empty"><td colspan="4"><em>No hay elementos.</em></td></tr></tbody>
            </table>
        </section>
    </main>

    <script src="/assets/js/main.js" defer></script>
</body>
</html>
