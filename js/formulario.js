'use strict';

function validarProducto(datos) {
  const codigo = datos.get('codigo') ?? '';
  const nombre = datos.get('nombre') ?? '';
  const precio = datos.get('precio') ?? '';
  const descripcion = datos.get('descripcion') ?? '';
  const error = (campo, mensaje) => ({ campo, mensaje });
  const vacio = valor => /^[\s\u0085]*$/u.test(valor);

  if (vacio(codigo)) return error('codigo', 'El código del producto no puede estar en blanco.');
  if (/[^A-Za-z0-9]/.test(codigo) || !/[A-Za-z]/.test(codigo) || !/[0-9]/.test(codigo)) {
    return error('codigo', 'El código del producto debe contener letras y números');
  }
  if (codigo.length < 5 || codigo.length > 15) {
    return error('codigo', 'El código del producto debe tener entre 5 y 15 caracteres.');
  }
  if (vacio(nombre)) return error('nombre', 'El nombre del producto no puede estar en blanco.');
  if (Array.from(nombre).length < 2 || Array.from(nombre).length > 50) {
    return error('nombre', 'El nombre del producto debe tener entre 2 y 50 caracteres.');
  }
  for (const [campo, mensaje] of [
    ['bodega_id', 'Debe seleccionar una bodega.'],
    ['sucursal_id', 'Debe seleccionar una sucursal para la bodega seleccionada.'],
    ['moneda_id', 'Debe seleccionar una moneda para el producto.'],
  ]) {
    const valor = datos.get(campo) ?? '';
    if (!/^[1-9][0-9]*(?![\s\S])/.test(valor) || Number(valor) > 2147483647) return error(campo, mensaje);
  }
  if (vacio(precio)) return error('precio', 'El precio del producto no puede estar en blanco.');
  if (!/^[0-9]+(?:\.[0-9]{1,2})?(?![\s\S])/.test(precio) || !/[1-9]/.test(precio)) {
    return error('precio', 'El precio del producto debe ser un número positivo con hasta dos decimales.');
  }
  const materiales = datos.getAll('materiales[]');
  if (materiales.length < 2 || materiales.length > 5) {
    return error('materiales', 'Debe seleccionar al menos dos materiales para el producto.');
  }
  if (materiales.some(material => !['1', '2', '3', '4', '5'].includes(material))) {
    return error('materiales', 'Los materiales seleccionados no son válidos.');
  }
  if (new Set(materiales).size !== materiales.length) {
    return error('materiales', 'Debe seleccionar al menos dos materiales distintos para el producto.');
  }
  if (vacio(descripcion)) return error('descripcion', 'La descripción del producto no puede estar en blanco.');
  if (Array.from(descripcion).length < 10 || Array.from(descripcion).length > 1000) {
    return error('descripcion', 'La descripción del producto debe tener entre 10 y 1000 caracteres.');
  }
  return null;
}

const formulario = document.getElementById('formulario-producto');
const bodega = document.getElementById('bodega_id');
const sucursal = document.getElementById('sucursal_id');
const moneda = document.getElementById('moneda_id');
const guardar = document.getElementById('guardar');
const estado = document.getElementById('estado');
const aviso = document.getElementById('aviso');
const mensajeAviso = document.getElementById('mensaje-aviso');
let controlAviso = null;
let csrf = '';
let solicitudSucursales = null;
let versionSucursales = 0;

function mostrarAviso(mensaje, control = document.activeElement) {
  estado.textContent = '';
  mensajeAviso.textContent = mensaje;
  controlAviso = control;
  if (!aviso.open) aviso.showModal();
}

aviso.addEventListener('close', () => controlAviso?.focus());
aviso.addEventListener('click', (evento) => {
  if (evento.target !== aviso) return;
  const limites = aviso.getBoundingClientRect();
  if (evento.clientX < limites.left || evento.clientX > limites.right
      || evento.clientY < limites.top || evento.clientY > limites.bottom) {
    aviso.close();
  }
});

function mostrarError(error) {
  const control = error.campo === 'materiales'
    ? formulario.querySelector('input[type="checkbox"]')
    : document.getElementById(error.campo);
  if (control) {
    control.setAttribute('aria-invalid', 'true');
  }
  mostrarAviso(error.mensaje, control || document.activeElement);
}

function llenarSelect(select, opciones) {
  select.replaceChildren(new Option('', ''));
  for (const opcion of opciones) {
    select.add(new Option(opcion.nombre, opcion.id));
  }
}

async function solicitar(url, opciones = {}) {
  const limite = AbortSignal.timeout(15000);
  const signal = opciones.signal ? AbortSignal.any([opciones.signal, limite]) : limite;
  const respuesta = await fetch(url, { credentials: 'same-origin', ...opciones, signal });
  let datos;
  try {
    datos = await respuesta.json();
  } catch {
    if (signal.aborted) throw signal.reason;
    throw new Error('El servidor no devolvió una respuesta válida. Intente nuevamente.');
  }
  if (!respuesta.ok || !datos.ok) {
    const error = new Error(datos.mensaje || 'No se pudo completar la operación.');
    error.campo = datos.campo;
    throw error;
  }
  return datos;
}

async function cargarCatalogos() {
  estado.textContent = 'Cargando bodegas y monedas…';
  try {
    const datos = await solicitar('php/catalogos.php');
    llenarSelect(bodega, datos.bodegas);
    llenarSelect(moneda, datos.monedas);
    csrf = datos.csrf;
    bodega.disabled = false;
    moneda.disabled = false;
    guardar.disabled = false;
    estado.textContent = '';
  } catch {
    mostrarError({ mensaje: 'No se pudieron cargar las bodegas y monedas. Recargue la página para reintentar.' });
  }
}

bodega.addEventListener('change', async () => {
  solicitudSucursales?.abort();
  const version = ++versionSucursales;
  llenarSelect(sucursal, []);
  sucursal.disabled = true;
  estado.textContent = '';
  if (!bodega.value) return;

  solicitudSucursales = new AbortController();
  estado.textContent = 'Cargando sucursales…';
  try {
    const datos = await solicitar(`php/sucursales.php?bodega_id=${encodeURIComponent(bodega.value)}`, {
      signal: solicitudSucursales.signal,
    });
    if (version !== versionSucursales) return;
    llenarSelect(sucursal, datos.sucursales);
    sucursal.disabled = datos.sucursales.length === 0;
    estado.textContent = '';
    if (!datos.sucursales.length) mostrarAviso('La bodega seleccionada no tiene sucursales disponibles.', bodega);
  } catch (error) {
    if (error.name === 'AbortError' || version !== versionSucursales) return;
    mostrarError({ campo: 'bodega_id', mensaje: 'No se pudieron cargar las sucursales. Seleccione nuevamente la bodega para reintentar.' });
  }
});

formulario.addEventListener('input', (evento) => evento.target.removeAttribute('aria-invalid'));
formulario.addEventListener('change', (evento) => evento.target.removeAttribute('aria-invalid'));

formulario.addEventListener('submit', async (evento) => {
  evento.preventDefault();
  if (guardar.disabled) return;
  for (const control of formulario.querySelectorAll('[aria-invalid]')) control.removeAttribute('aria-invalid');
  const datos = new FormData(formulario);
  const error = validarProducto(datos);
  if (error) {
    mostrarError(error);
    return;
  }

  guardar.disabled = true;
  guardar.textContent = 'Guardando…';
  estado.textContent = 'Guardando producto…';
  formulario.setAttribute('aria-busy', 'true');
  const controles = Array.from(formulario.querySelectorAll('input, select, textarea'));
  const deshabilitados = controles.map(control => control.disabled);
  for (const control of controles) control.disabled = true;
  let respuesta;
  let fallo;
  try {
    respuesta = await solicitar('php/productos.php', {
      method: 'POST',
      headers: { 'X-CSRF-Token': csrf },
      body: datos,
    });
  } catch (errorGuardado) {
    fallo = {
      campo: errorGuardado.campo,
      mensaje: errorGuardado.message,
    };
    if (errorGuardado.name === 'TimeoutError') {
      fallo.mensaje = 'La solicitud tardó demasiado. No se pudo confirmar el guardado. Reintente con el mismo código.';
    } else if (errorGuardado instanceof TypeError) {
      fallo.mensaje = 'No se pudo confirmar el guardado. Compruebe su conexión y reintente con el mismo código.';
    }
  } finally {
    controles.forEach((control, indice) => { control.disabled = deshabilitados[indice]; });
    guardar.disabled = false;
    guardar.textContent = 'Guardar Producto';
    formulario.removeAttribute('aria-busy');
  }
  if (fallo) {
    mostrarError(fallo);
    return;
  }
  solicitudSucursales?.abort();
  ++versionSucursales;
  formulario.reset();
  llenarSelect(sucursal, []);
  sucursal.disabled = true;
  mostrarAviso(respuesta.mensaje, document.getElementById('codigo'));
});

cargarCatalogos();
