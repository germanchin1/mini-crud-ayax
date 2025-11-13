<?php
declare(strict_types=1);
session_start();
if (!isset($_SESSION['usuario'])) {
    header('Location: /login.html');
    exit;
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Mini CRUD AJAX (fetch + JSON)</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<!-- Hoja de estilos básica -->
 <link rel="stylesheet" href="/assets/css/styles.css">
</head>
<body>
<!-- Encabezado semántico -->
<header class="encabezado-aplicacion">
    <div class="encabezado-principal">
        <h1 class="encabezado-aplicacion__titulo">Mini CRUD con fetch() (sin Base de Datos)</h1>
        <p class="encabezado-aplicacion__descripcion">Esta pantalla usa JavaScript para hablar con la API PHP y actualizar la tabla sin recargar la página.</p>
    </div>
    <div class="encabezado-sesion">
        <?php if (isset($_SESSION['usuario'])): ?>
            <span id="usuario-nombre">Bienvenido, <?php echo htmlspecialchars($_SESSION['usuario']['nombre'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?></span>
            <button id="boton-logout" type="button">Cerrar sesión</button>
        <?php endif; ?>
    </div>
</header>
<main class="zona-principal" id="zona-principal" tabindex="-1">
<!-- Zona de mensajes (con aria-live para lectores de pantalla) -->
<div id="msg" class="mensajes-estado" role="status" aria-live="polite" aria-atomic="true"></div>
<!-- Formulario de alta de usuario con etiquetas asociadas y atributos de ayuda -->
<section class="bloque-formulario" aria-labelledby="titulo-formulario">
<h2 id="titulo-formulario">Agregar nuevo usuario</h2>
<form id="formCreate" class="formulario-alta-usuario" autocomplete="on" novalidate>
<div class="form-row">
<label for="campo-nombre" class="form-label">Nombre</label>
<input
id="campo-nombre"
name="nombre"
class="form-input"
type="text"
required
minlength="2"
maxlength="60"
placeholder="Ej.: Ana Pérez"
autocomplete="name"
inputmode="text"
>
</div>
<div class="form-row">
<label for="campo-email" class="form-label">Email</label>
<input
id="campo-email"
name="email"
class="form-input"
type="email"
required
maxlength="120"
placeholder="ejemplo@correo.com"
autocomplete="email"
inputmode="email"
inputmode="email"
>
</div>
<div class="form-actions">
<button id="boton-agregar-usuario" type="submit" class="boton-primario">
Agregar usuario
</button>
<span id="indicador-cargando" class="indicador-cargando" aria-hidden="true" hidden>
Cargando...
</span>
</div>
</form>
</section>
<!-- Listado de usuarios -->
<section class="bloque-listado" aria-labelledby="titulo-listado">
<h2 id="titulo-listado">Listado de usuarios</h2>
<div class="tabla-contenedor" role="region" aria-labelledby="titulo-listado">
<table class="tabla-usuarios">
<thead>
<tr>
<th scope="col">#</th>
<th scope="col">Nombre</th>
<th scope="col">Email</th>
<th scope="col">Acción</th>
</tr>
</thead>
<tbody id="tbody">
<!-- Fila de estado vacío (se alterna desde JS) -->
<tr id="fila-estado-vacio" class="fila-estado-vacio" hidden>
<td colspan="4"><em>No hay usuarios registrados todavía.</em></td>
</tr>
</tbody>
</table>
</div>
</section>
</main>
<!-- Enlace de retorno a la Parte 1 (opcional) -->
<footer class="pie-aplicacion">
<p><a href="/index.php">Ir a Parte 1 (clásica sin AJAX)</a> | <a href="/api.php?action=logout">Cerrar sesión</a></p>
</footer>
<!-- Nuestro JavaScript -->
<script src="/assets/js/main.js" defer></script>
</body>
</html>
