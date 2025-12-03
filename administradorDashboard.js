// adminDashboard.js

// Guarda globalmente el array de postres
let todosLosPostres = [];

// ============================================================
// --- SISTEMA DE MODALES PERSONALIZADOS (UI MEJORADA) ---
// ============================================================

/**
 * Inyecta el HTML y CSS necesarios para los modales en el documento.
 * Se ejecuta automáticamente para asegurar que los elementos existan.
 */
(function injectModalResources() {
  if (document.getElementById('custom-modal-overlay')) return;

  const css = `
    .custom-modal-overlay {
      position: fixed; top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(0,0,0,0.6); z-index: 3000;
      display: none; justify-content: center; align-items: center;
      backdrop-filter: blur(4px); opacity: 0; transition: opacity 0.3s ease;
    }
    .custom-modal-overlay.open { display: flex; opacity: 1; }
    .custom-modal-box {
      background: white; padding: 30px; border-radius: 15px;
      box-shadow: 0 10px 30px rgba(0,0,0,0.3);
      max-width: 420px; width: 90%; text-align: center;
      font-family: 'Open Sans', sans-serif;
      transform: translateY(20px); transition: transform 0.3s ease;
    }
    .custom-modal-overlay.open .custom-modal-box { transform: translateY(0); }
    .custom-modal-icon { font-size: 3rem; margin-bottom: 15px; display: block; }
    .custom-modal-title { font-size: 1.4rem; font-weight: 700; margin-bottom: 10px; color: #a30015; font-family: 'Playfair Display', serif; }
    .custom-modal-msg { font-size: 1rem; margin-bottom: 25px; color: #555; line-height: 1.5; }
    .custom-modal-actions { display: flex; justify-content: center; gap: 10px; }
    .custom-btn {
      padding: 12px 24px; border: none; border-radius: 8px;
      font-weight: 600; cursor: pointer; transition: all 0.2s; font-size: 0.95rem;
    }
    .btn-modal-confirm { background: linear-gradient(135deg, #d6001c, #a30015); color: white; box-shadow: 0 4px 10px rgba(214,0,28,0.3); }
    .btn-modal-confirm:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(214,0,28,0.4); }
    .btn-modal-cancel { background: #f0f0f0; color: #333; }
    .btn-modal-cancel:hover { background: #e0e0e0; }
  `;
  const style = document.createElement('style');
  style.innerHTML = css;
  document.head.appendChild(style);

  const html = `
    <div id="custom-modal-overlay" class="custom-modal-overlay">
      <div class="custom-modal-box">
        <span id="custom-modal-icon" class="custom-modal-icon"></span>
        <div id="custom-modal-title" class="custom-modal-title"></div>
        <div id="custom-modal-msg" class="custom-modal-msg"></div>
        <div id="custom-modal-actions" class="custom-modal-actions"></div>
      </div>
    </div>
  `;
  document.body.insertAdjacentHTML('beforeend', html);
})();

function closeCustomModal() {
  const overlay = document.getElementById('custom-modal-overlay');
  overlay.classList.remove('open');
  setTimeout(() => { overlay.style.display = 'none'; }, 300); // Esperar transición
}

/**
 * Reemplazo elegante para alert()
 */
function showNotification(message, title = "Aviso", type = "info") {
  return new Promise(resolve => {
    const overlay = document.getElementById('custom-modal-overlay');
    const icon = document.getElementById('custom-modal-icon');
    const titleEl = document.getElementById('custom-modal-title');
    const msgEl = document.getElementById('custom-modal-msg');
    const actions = document.getElementById('custom-modal-actions');

    overlay.style.display = 'flex';
    // Forzar reflow para animación
    void overlay.offsetWidth; 
    overlay.classList.add('open');

    titleEl.textContent = title;
    msgEl.innerHTML = message.replace(/\n/g, '<br>');
    
    // Iconos según tipo
    if (type === 'error') {
      icon.innerHTML = '❌'; 
      titleEl.style.color = '#d32f2f';
    } else if (type === 'success') {
      icon.innerHTML = '✅';
      titleEl.style.color = '#2e7d32';
    } else {
      icon.innerHTML = 'ℹ️';
      titleEl.style.color = '#a30015';
    }

    actions.innerHTML = `<button class="custom-btn btn-modal-confirm" id="btn-modal-ok">Entendido</button>`;
    
    document.getElementById('btn-modal-ok').onclick = () => {
      closeCustomModal();
      resolve();
    };
  });
}

/**
 * Reemplazo elegante y asíncrono para confirm()
 * Uso: const acepto = await showConfirmation("¿Seguro?");
 */
function showConfirmation(message, title = "Confirmación") {
  return new Promise(resolve => {
    const overlay = document.getElementById('custom-modal-overlay');
    const icon = document.getElementById('custom-modal-icon');
    const titleEl = document.getElementById('custom-modal-title');
    const msgEl = document.getElementById('custom-modal-msg');
    const actions = document.getElementById('custom-modal-actions');

    overlay.style.display = 'flex';
    void overlay.offsetWidth;
    overlay.classList.add('open');

    icon.innerHTML = '❓';
    titleEl.textContent = title;
    titleEl.style.color = '#a30015';
    msgEl.innerHTML = message;

    actions.innerHTML = `
      <button class="custom-btn btn-modal-cancel" id="btn-modal-cancel">Cancelar</button>
      <button class="custom-btn btn-modal-confirm" id="btn-modal-yes">Sí, continuar</button>
    `;

    document.getElementById('btn-modal-yes').onclick = () => {
      closeCustomModal();
      resolve(true);
    };
    document.getElementById('btn-modal-cancel').onclick = () => {
      closeCustomModal();
      resolve(false);
    };
  });
}

// ============================================================
// --- LÓGICA DEL DASHBOARD (Actualizada con nuevos modales) ---
// ============================================================

/**
 * Lista todos los postres y los muestra en la sección de Gestión de Productos.
 */
async function mostrarProductos() {
  try {
    const res = await fetch('listar_postres.php');
    if (!res.ok) throw new Error('Error al obtener productos');
    const data = await res.json();
    todosLosPostres = data;

    // Construye la tabla
    let html = `
      <p class="titulo-contenido">Gestión de Productos</p>
      <table>
        <tr>
          <th>ID</th><th>Título</th><th>Precio</th><th>Categoría</th>
          <th>Tamaño</th><th>Sabor</th><th>Imagen</th><th>Stock</th><th>Acciones</th>
        </tr>
    `;
    data.forEach(p => {
      html += `
        <tr>
          <td>${p.id}</td>
          <td>${p.titulo}</td>
          <td>$${parseFloat(p.precio).toFixed(2)}</td>
          <td>${p.categoria}</td>
          <td>${p.tamanio}</td>
          <td>${p.sabor}</td>
          <td>${p.imagen_url
            ? `<img src="${p.imagen_url}" alt="" style="max-width:80px;max-height:60px; border-radius:4px;">`
            : '—'}</td>
          <td>${p.stock}</td>
          <td class="acciones">
            <button class="btn-editar" onclick="abrirModalEditar(${p.id})">Editar</button>
            <button class="btn-eliminar" onclick="eliminarPostre(${p.id})">Eliminar</button>
          </td>
        </tr>
      `;
    });
    html += `</table>`;
    document.getElementById('contenido').innerHTML = html;
  } catch (err) {
    // Reemplazo de mensaje de error simple
    document.getElementById('contenido').innerHTML = `<p style="color:red; text-align:center;">Error de conexión: ${err.message}</p>`;
    showNotification("No se pudieron cargar los productos. Verifica tu conexión.", "Error de Sistema", "error");
  }
}

/**
 * Muestra el formulario para Agregar un nuevo postre.
 */
function mostrarFormularioAgregarPostre() {
  document.getElementById('contenido').innerHTML = `
    <p class="titulo-contenido">Agregar Nueva Receta</p>
    <form id="formAgregarPostre">
      <label>Título</label><input type="text" name="titulo" required><br><br>
      <label>Descripción</label><textarea name="descripcion" rows="3" required></textarea><br><br>
      <label>Precio</label><input type="number" name="precio" step="0.01" required><br><br>
      <label>Categoría</label><input type="text" name="categoria"><br><br>
      <label>Tamaño</label><input type="text" name="tamanio"><br><br>
      <label>Sabor</label><input type="text" name="sabor"><br><br>
      <label>Imagen URL</label><input type="text" name="imagen_url"><br><br>
      <label>Stock</label><input type="number" name="stock" value="0"><br><br>
      <button type="submit">Agregar Receta</button>
    </form>
  `;

  document
    .getElementById('formAgregarPostre')
    .addEventListener('submit', async e => {
      e.preventDefault();
      const form = e.target;
      const formData = new FormData(form);
      try {
        const res = await fetch('crear_postre.php', {
          method: 'POST',
          body: formData
        });
        const msg = await res.text();
        
        // REEMPLAZO: Modal de éxito
        await showNotification(msg, "Éxito", "success");
        mostrarProductos();
      } catch (err) {
        // REEMPLAZO: Modal de error
        showNotification('Error al crear: ' + err, "Error", "error");
      }
    });
}

/**
 * Elimina un postre pidiendo confirmación (ASÍNCRONO AHORA).
 */
async function eliminarPostre(id) {
  // REEMPLAZO: Confirmación asíncrona
  const confirmado = await showConfirmation('¿Estás seguro de que deseas eliminar este postre permanentemente?');
  if (!confirmado) return;

  try {
    const formData = new FormData();
    formData.append('id', id);
    const res = await fetch('eliminar_postre.php', {
      method: 'POST',
      body: formData
    });
    const msg = await res.text();
    
    // REEMPLAZO: Notificación
    await showNotification(msg, "Eliminado", "success");
    mostrarProductos();
  } catch (err) {
    showNotification('Error al eliminar: ' + err, "Error", "error");
  }
}

/**
 * Abre el modal de edición y carga los datos del postre.
 */
async function abrirModalEditar(id) {
  try {
    const res = await fetch(`listar_postre_individual.php?id=${id}`);
    if (!res.ok) throw new Error('No se encontró el postre');
    const p = await res.json();

    // Rellena el formulario
    document.getElementById('editar_id').value = p.id;
    document.getElementById('editar_titulo').value = p.titulo;
    document.getElementById('editar_descripcion').value = p.descripcion;
    document.getElementById('editar_precio').value = p.precio;
    document.getElementById('editar_categoria').value = p.categoria;
    document.getElementById('editar_tamanio').value = p.tamanio;
    document.getElementById('editar_sabor').value = p.sabor;
    document.getElementById('editar_imagen_url').value = p.imagen_url;
    document.getElementById('editar_stock').value = p.stock;

    document.getElementById('modalEditar').style.display = 'flex';
  } catch (err) {
    showNotification(err.message, "Error", "error");
  }
}

/**
 * Cierra el modal de edición.
 */
function cerrarModal() {
  document.getElementById('modalEditar').style.display = 'none';
}

/**
 * Captura el envío del formulario de edición.
 */
document.addEventListener('DOMContentLoaded', () => {
  const form = document.getElementById('formEditarPostre');
  // Verificamos que el elemento exista antes de añadir listener (por si acaso)
  if (form) {
      form.addEventListener('submit', async e => {
        e.preventDefault();
        const formData = new FormData(form);
        try {
          const res = await fetch('editar_postre.php', {
            method: 'POST',
            body: formData
          });
          const msg = await res.text();
          
          // REEMPLAZO: Notificación
          await showNotification(msg, "Actualizado", "success");
          cerrarModal();
          mostrarProductos();
        } catch (err) {
          showNotification('Error al actualizar: ' + err, "Error", "error");
        }
      });
  }
});