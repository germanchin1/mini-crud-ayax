// Mini CRUD — main.js (versión súper simple)
// Meta: código fácil de leer, dividido en tareas cortitas y bien comentadas.

const API = '/api.php';

// Referencias a los elementos HTML que vamos a tocar.
const ui = {
	tbody: document.getElementById('tbody'),
	emptyRow: document.getElementById('empty'),
	form: document.getElementById('formCreate'),
	submitBtn: document.querySelector('#formCreate button[type="submit"]'),
	logoutBtn: document.getElementById('logout')
};

// Estado del front: lista mostrada y cuál registro estamos editando.
const state = {
	list: [],
	editIndex: null
};

// -----------------------------
// UTILIDADES BÁSICAS
// -----------------------------

function safe(text) {
	return String(text || '').replaceAll('<', '&lt;').replaceAll('>', '&gt;');
}

async function apiRequest(action, { method = 'GET', body = null } = {}) {
	const config = { method, headers: {} };
	if (body !== null) {
		config.headers['Content-Type'] = 'application/json';
		config.body = JSON.stringify(body);
	}
	const response = await fetch(`${API}?action=${action}`, config);
	if (response.status === 401) {
		location.href = '/login.html';
		throw new Error('Sesión caducada');
	}
	return response.json();
}

function showError(message) {
	alert(message || 'Ocurrió un error');
}

// Funciones que representan cada llamada a la API.
const api = {
	list: () => apiRequest('list'),
	create: (payload) => apiRequest('create', { method: 'POST', body: payload }),
	update: (payload) => apiRequest('update', { method: 'POST', body: payload }),
	delete: (payload) => apiRequest('delete', { method: 'POST', body: payload }),
	logout: () => apiRequest('logout')
};

// -----------------------------
// DIBUJAR TABLA
// -----------------------------

function clearTable() {
	if (ui.tbody) ui.tbody.innerHTML = '';
}

function createRow(index, item) {
	const tr = document.createElement('tr');
	tr.innerHTML = `
		<td>${index + 1}</td>
		<td>${safe(item.nombre)}</td>
		<td>${safe(item.email)}</td>
		<td>
			<button class="edit" data-i="${index}">Editar</button>
			<button class="del" data-i="${index}">Eliminar</button>
		</td>`;
	return tr;
}

function renderTable(list) {
	state.list = Array.isArray(list) ? list : [];
	clearTable();
	if (!ui.tbody) return;
	if (state.list.length === 0) {
		if (ui.emptyRow) ui.tbody.appendChild(ui.emptyRow);
		return;
	}
	state.list.forEach((item, index) => ui.tbody.appendChild(createRow(index, item)));
}

// -----------------------------
// FORMULARIO (AGREGAR / EDITAR)
// -----------------------------

function getInput(name) {
	return ui.form ? ui.form.querySelector(`[name="${name}"]`) : null;
}

function fillForm(name = '', email = '') {
	const nameInput = getInput('nombre');
	const emailInput = getInput('email');
	if (nameInput) nameInput.value = name;
	if (emailInput) emailInput.value = email;
}

function setButtonLabel(label) {
	if (ui.submitBtn) ui.submitBtn.textContent = label;
}

function addCancelButton() {
	if (!ui.submitBtn || document.getElementById('cancel-edit')) return;
	const btn = document.createElement('button');
	btn.type = 'button';
	btn.id = 'cancel-edit';
	btn.textContent = 'Cancelar';
	btn.addEventListener('click', cancelEdit);
	ui.submitBtn.parentNode.appendChild(btn);
}

function removeCancelButton() {
	const btn = document.getElementById('cancel-edit');
	if (btn) btn.remove();
}

function enterEdit(index) {
	state.editIndex = index;
	const item = state.list[index];
	if (!item) return showError('Índice inválido');
	fillForm(item.nombre, item.email);
	setButtonLabel('Guardar');
	addCancelButton();
}

function exitEdit() {
	state.editIndex = null;
	fillForm();
	setButtonLabel('Agregar');
	removeCancelButton();
}

function cancelEdit() {
	if (ui.form) ui.form.reset();
	exitEdit();
}

async function handleSubmit(event) {
	event.preventDefault();
	if (!ui.form) return;
	const fd = new FormData(ui.form);
	const payload = {
		nombre: fd.get('nombre'),
		email: fd.get('email')
	};
	const action = state.editIndex === null ? api.create : api.update;
	const body = state.editIndex === null ? payload : { index: state.editIndex, ...payload };
	try {
		const result = await action(body);
		if (!result.ok) return showError(result.error);
		renderTable(result.data);
		ui.form.reset();
		if (state.editIndex !== null) exitEdit();
	} catch (error) {
		showError(error.message);
	}
}

// -----------------------------
// LISTAR / ELIMINAR DESDE LA TABLA
// -----------------------------

async function refreshTable() {
	try {
		const result = await api.list();
		if (!result.ok) return showError(result.error);
		renderTable(result.data);
	} catch (error) {
		showError(error.message);
	}
}

async function deleteEntry(index) {
	if (!confirm('¿Eliminar este registro?')) return;
	try {
		const result = await api.delete({ index });
		if (!result.ok) return showError(result.error);
		renderTable(result.data);
	} catch (error) {
		showError(error.message);
	}
}

function handleTableClick(event) {
	const button = event.target.closest('button');
	if (!button) return;
	const index = parseInt(button.dataset.i, 10);
	if (Number.isNaN(index)) return;
	if (button.classList.contains('edit')) return enterEdit(index);
	if (button.classList.contains('del')) return deleteEntry(index);
}

// -----------------------------
// LOGOUT
// -----------------------------

async function handleLogout() {
	try { await api.logout(); }
	catch (_) {}
	location.href = '/login.html';
}

// -----------------------------
// INICIO
// -----------------------------

function wireEvents() {
	if (ui.form) ui.form.addEventListener('submit', handleSubmit);
	if (ui.tbody) ui.tbody.addEventListener('click', handleTableClick);
	if (ui.logoutBtn) ui.logoutBtn.addEventListener('click', handleLogout);
}

function start() {
	wireEvents();
	refreshTable();
}

start();
