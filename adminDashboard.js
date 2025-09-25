// adminDashboard.js

// Guarda globalmente el array de postres
let todosLosPostres = [];

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
            ? `<img src="${p.imagen_url}" alt="" style="max-width:80px;max-height:60px;">`
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
    document.getElementById('contenido').innerHTML = `<p style="color:red;">${err.message}</p>`;
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
        alert(msg);
        mostrarProductos();
      } catch (err) {
        alert('Error al crear: ' + err);
      }
    });
}

/**
 * Elimina un postre pidiendo confirmación.
 */
async function eliminarPostre(id) {
  if (!confirm('¿Eliminar este postre?')) return;
  try {
    const formData = new FormData();
    formData.append('id', id);
    const res = await fetch('eliminar_postre.php', {
      method: 'POST',
      body: formData
    });
    const msg = await res.text();
    alert(msg);
    mostrarProductos();
  } catch (err) {
    alert('Error al eliminar: ' + err);
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
    alert(err.message);
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
  form.addEventListener('submit', async e => {
    e.preventDefault();
    const formData = new FormData(form);
    try {
      const res = await fetch('editar_postre.php', {
        method: 'POST',
        body: formData
      });
      const msg = await res.text();
      alert(msg);
      cerrarModal();
      mostrarProductos();
    } catch (err) {
      alert('Error al actualizar: ' + err);
    }
  });
});
