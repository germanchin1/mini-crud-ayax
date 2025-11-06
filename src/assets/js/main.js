// -----------------------------------------------------------------------------
// Mini CRUD AJAX — Lado cliente (sin librerías)
// Archivo: /assets/js/main.js
// -----------------------------------------------------------------------------
/** URL absoluta o relativa del endpoint PHP (API del servidor) */
const URL_API_SERVIDOR = '/api.php';
/** Elementos de la interfaz que necesitamos manipular */
const nodoCuerpoTablaUsuarios = document.getElementById('tbody'); // <tbody> del listado
const nodoFilaEstadoVacio = document.getElementById('fila-estado-vacio'); // fila de “no hay datos”
const formularioAltaUsuario = document.getElementById('formCreate'); // <form> de alta
const nodoZonaMensajesEstado = document.getElementById('msg'); // <div> mensajes
const nodoBotonAgregarUsuario = document.getElementById('boton-agregar-usuario');
const nodoIndicadorCargando = document.getElementById('indicador-cargando');
// Estado de edición
let indiceEnEdicion = null;
let nodoBotonCancelarEdicion = null;
// -----------------------------------------------------------------------------
// BLOQUE: Gestión de mensajes de estado (éxito / error)
// -----------------------------------------------------------------------------
function mostrarMensajeDeEstado(tipoEstado, textoMensaje) {
nodoZonaMensajesEstado.className = tipoEstado; // .ok | .error | ''
nodoZonaMensajesEstado.textContent = textoMensaje;
if (tipoEstado !== '') {
setTimeout(() => {
nodoZonaMensajesEstado.className = '';
nodoZonaMensajesEstado.textContent = '';
}, 2000);
}}
// -----------------------------------------------------------------------------
// BLOQUE: Indicador de carga + bloqueo de botón
// -----------------------------------------------------------------------------
function activarEstadoCargando() {
if (nodoBotonAgregarUsuario) nodoBotonAgregarUsuario.disabled = true;
if (nodoIndicadorCargando) nodoIndicadorCargando.hidden = false;
}
function desactivarEstadoCargando() {
if (nodoBotonAgregarUsuario) nodoBotonAgregarUsuario.disabled = false;
if (nodoIndicadorCargando) nodoIndicadorCargando.hidden = true;
}
// -----------------------------------------------------------------------------
// BLOQUE: Sanitización de texto
// -----------------------------------------------------------------------------
function convertirATextoSeguro(entradaPosiblementePeligrosa) {
return String(entradaPosiblementePeligrosa)
.replaceAll('&', '&amp;')
.replaceAll('<', '&lt;')
.replaceAll('>', '&gt;')
.replaceAll('"', '&quot;')
.replaceAll("'", '&#39;');
}
// -----------------------------------------------------------------------------
// BLOQUE: Renderizado del listado de usuarios
// -----------------------------------------------------------------------------
function renderizarTablaDeUsuarios(arrayUsuarios) {
nodoCuerpoTablaUsuarios.innerHTML = '';
// Mostrar u ocultar la fila de estado vacío según haya datos o no
if (Array.isArray(arrayUsuarios) && arrayUsuarios.length > 0) {
if (nodoFilaEstadoVacio) nodoFilaEstadoVacio.hidden = true;
} else {
if (nodoFilaEstadoVacio) nodoFilaEstadoVacio.hidden = false;
return; // no hay filas que pintar
}
arrayUsuarios.forEach((usuario, posicionEnLista) => {
const nodoFila = document.createElement('tr');
nodoFila.innerHTML = `
<td>${posicionEnLista + 1}</td>
<td>${convertirATextoSeguro(usuario?.nombre ?? '')}</td>
<td>${convertirATextoSeguro(usuario?.email ?? '')}</td>
<td>
	<button type="button" data-posicion="${posicionEnLista}" data-action="edit" aria-label="Editar usuario ${posicionEnLista + 1}">Editar</button>
	<button type="button" data-posicion="${posicionEnLista}" data-action="delete" aria-label="Eliminar usuario ${posicionEnLista + 1}">Eliminar</button>
</td>
`;
nodoCuerpoTablaUsuarios.appendChild(nodoFila);
});
}
// -----------------------------------------------------------------------------
// BLOQUE: Carga inicial y refresco del listado (GET list)
// -----------------------------------------------------------------------------
async function obtenerYMostrarListadoDeUsuarios() {
try {
const respuestaHttp = await fetch(`${URL_API_SERVIDOR}?action=list`);
const cuerpoJson = await respuestaHttp.json();
if (!cuerpoJson.ok) {
throw new Error(cuerpoJson.error || 'No fue posible obtener el listado.');
}
renderizarTablaDeUsuarios(cuerpoJson.data);
} catch (error) {
mostrarMensajeDeEstado('error', error.message);
}
}
// -----------------------------------------------------------------------------
// BLOQUE: Alta de usuario (POST create) sin recargar la página
// -----------------------------------------------------------------------------
formularioAltaUsuario?.addEventListener('submit', async (evento) => {
evento.preventDefault();
const datosFormulario = new FormData(formularioAltaUsuario);
const datosUsuarioNuevo = {
nombre: String(datosFormulario.get('nombre') || '').trim(),
email: String(datosFormulario.get('email') || '').trim(),
};
// Validación HTML5 rápida (por si el navegador no la lanza)
if (!datosUsuarioNuevo.nombre || !datosUsuarioNuevo.email) {
mostrarMensajeDeEstado('error', 'Los campos Nombre y Email son obligatorios.');
return;
}
try {
	activarEstadoCargando();
	// Si estamos en modo edición, llamamos a la acción update
	if (Number.isInteger(indiceEnEdicion)) {
		const respuestaHttp = await fetch(`${URL_API_SERVIDOR}?action=update`, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ index: indiceEnEdicion, ...datosUsuarioNuevo }),
		});
		const cuerpoJson = await respuestaHttp.json();
		if (!cuerpoJson.ok) {
			throw new Error(cuerpoJson.error || 'No fue posible actualizar el usuario.');
		}
		renderizarTablaDeUsuarios(cuerpoJson.data);
		formularioAltaUsuario.reset();
		salirModoEdicion();
		mostrarMensajeDeEstado('ok', 'Usuario actualizado correctamente.');
	} else {
		const respuestaHttp = await fetch(`${URL_API_SERVIDOR}?action=create`, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify(datosUsuarioNuevo),
		});
		const cuerpoJson = await respuestaHttp.json();
		if (!cuerpoJson.ok) {
			throw new Error(cuerpoJson.error || 'No fue posible crear el usuario.');
		}
		renderizarTablaDeUsuarios(cuerpoJson.data);
		formularioAltaUsuario.reset();
		mostrarMensajeDeEstado('ok', 'Usuario agregado correctamente.');
	}
} catch (error) {
	mostrarMensajeDeEstado('error', error.message);
} finally {
	desactivarEstadoCargando();
}
});

// Entrar en modo edición: rellenar formulario y ajustar botones
function entrarModoEdicion(indice, usuario) {
	indiceEnEdicion = indice;
	formularioAltaUsuario.querySelector('[name="nombre"]').value = usuario.nombre ?? '';
	formularioAltaUsuario.querySelector('[name="email"]').value = usuario.email ?? '';
	if (nodoBotonAgregarUsuario) nodoBotonAgregarUsuario.textContent = 'Guardar cambios';
	// Crear botón cancelar si no existe
	if (!nodoBotonCancelarEdicion && nodoBotonAgregarUsuario) {
		nodoBotonCancelarEdicion = document.createElement('button');
		nodoBotonCancelarEdicion.type = 'button';
		nodoBotonCancelarEdicion.id = 'boton-cancelar-edicion';
		nodoBotonCancelarEdicion.textContent = 'Cancelar';
		nodoBotonCancelarEdicion.className = 'boton-secundario';
		nodoBotonAgregarUsuario.insertAdjacentElement('afterend', nodoBotonCancelarEdicion);
		nodoBotonCancelarEdicion.addEventListener('click', () => {
			formularioAltaUsuario.reset();
			salirModoEdicion();
		});
	}
}

function salirModoEdicion() {
	indiceEnEdicion = null;
	if (nodoBotonAgregarUsuario) nodoBotonAgregarUsuario.textContent = 'Agregar usuario';
	if (nodoBotonCancelarEdicion) {
		nodoBotonCancelarEdicion.remove();
		nodoBotonCancelarEdicion = null;
	}
}
// -----------------------------------------------------------------------------
// BLOQUE: Eliminación de usuario (POST delete) mediante delegación
// -----------------------------------------------------------------------------
// Delegación para Editar / Eliminar
nodoCuerpoTablaUsuarios?.addEventListener('click', async (evento) => {
	const nodoBoton = evento.target.closest('button[data-posicion]');
	if (!nodoBoton) return;
	const posicion = parseInt(nodoBoton.dataset.posicion, 10);
	if (!Number.isInteger(posicion)) return;
	const accion = nodoBoton.dataset.action || 'delete';

	if (accion === 'edit') {
		// Rellenar formulario y cambiar a modo edición
		// Intentamos tomar los valores desde la fila para mayor rapidez
		const fila = nodoBoton.closest('tr');
		const celdas = fila ? fila.querySelectorAll('td') : [];
		const nombre = celdas[1]?.textContent?.trim() ?? '';
		const email = celdas[2]?.textContent?.trim() ?? '';
		entrarModoEdicion(posicion, { nombre, email });
		return;
	}

	// Acción por defecto: eliminar
	if (!window.confirm('¿Deseas eliminar este usuario?')) return;
	try {
		const respuestaHttp = await fetch(`${URL_API_SERVIDOR}?action=delete`, {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify({ index: posicion }),
		});
		const cuerpoJson = await respuestaHttp.json();
		if (!cuerpoJson.ok) {
			throw new Error(cuerpoJson.error || 'No fue posible eliminar el usuario.');
		}
		renderizarTablaDeUsuarios(cuerpoJson.data);
		mostrarMensajeDeEstado('ok', 'Usuario eliminado correctamente.');
		// Si eliminamos una fila mientras estábamos editando esa misma fila, salir del modo edición
		if (Number.isInteger(indiceEnEdicion) && indiceEnEdicion === posicion) {
			formularioAltaUsuario.reset();
			salirModoEdicion();
		}
	} catch (error) {
		mostrarMensajeDeEstado('error', error.message);
	}
});
// -----------------------------------------------------------------------------
// BLOQUE: Inicialización de la pantalla
// -----------------------------------------------------------------------------
obtenerYMostrarListadoDeUsuarios();