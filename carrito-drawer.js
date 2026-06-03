/* ═══════════════════════════════════════════════════════
   carrito-drawer.js  –  La Casa del Pastel
   Carrito flotante lateral reutilizable

   USO EN CUALQUIER PÁGINA:
     1. <link rel="stylesheet" href="carrito-drawer.css">
     2. <script src="carrito-drawer.js" defer></script>
     3. Reemplazar el enlace del carrito en el nav por:
          <a href="#" id="btn-abrir-carrito"
             onclick="abrirCarritoDrawer();return false;">
            🛒 <span id="cart-nav-badge" class="cart-nav-badge hidden">0</span>
          </a>
   ═══════════════════════════════════════════════════════ */

(function () {
  'use strict';

  /* ── Clave de localStorage ─────────────────────────── */
  const STORAGE_KEY     = 'carrito';
  const STORAGE_USER_KEY= 'carrito_usuario_id';
  const CUPON_KEY       = 'cupon_recuperacion';

  let _abandonoTimer = null;

  /* ══════════════════════════════════════════════════════
     HELPERS DE DATOS
  ══════════════════════════════════════════════════════ */
  function getCarrito() {
    try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); }
    catch { return []; }
  }

  function setCarrito(arr) {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(arr));
  }

  /* ══════════════════════════════════════════════════════
     GESTIÓN DE CARRITO POR SESIÓN DE USUARIO
     - Si cambia la cuenta, guarda el carrito del usuario
       anterior y limpia el actual.
     - Si se cierra sesión, guarda el carrito y lo limpia.
  ══════════════════════════════════════════════════════ */
  async function initCarritoSesion() {
    try {
      const r    = await fetch('sesion_info.php');
      const info = await r.json();

      if (info.activo && info.usuario_id) {
        const nuevoId    = String(info.usuario_id);
        const idGuardado = localStorage.getItem(STORAGE_USER_KEY);

        if (idGuardado && idGuardado !== nuevoId) {
          // Cambió de cuenta → preservar carrito anterior
          localStorage.setItem(
            'carrito_backup_' + idGuardado,
            localStorage.getItem(STORAGE_KEY) || '[]'
          );
          localStorage.removeItem(STORAGE_KEY);
        }
        localStorage.setItem(STORAGE_USER_KEY, nuevoId);

      } else {
        // Sin sesión activa
        const idAnterior = localStorage.getItem(STORAGE_USER_KEY);
        if (idAnterior) {
          // Se cerró sesión → preservar carrito y limpiar
          localStorage.setItem(
            'carrito_backup_' + idAnterior,
            localStorage.getItem(STORAGE_KEY) || '[]'
          );
          localStorage.removeItem(STORAGE_KEY);
          localStorage.removeItem(STORAGE_USER_KEY);
        }
      }
    } catch (_) { /* sesion_info.php no disponible; silencioso */ }

    actualizarBadge();
  }

  /* ══════════════════════════════════════════════════════
     INYECCIÓN DEL HTML DEL DRAWER
     Se inserta automáticamente al cargar el script.
  ══════════════════════════════════════════════════════ */
  function inyectarDrawerHTML() {
    if (document.getElementById('cart-drawer')) return; // ya existe

    const overlay = document.createElement('div');
    overlay.id = 'cart-drawer-overlay';
    overlay.addEventListener('click', cerrarCarritoDrawer);

    const drawer = document.createElement('div');
    drawer.id = 'cart-drawer';
    drawer.setAttribute('role', 'dialog');
    drawer.setAttribute('aria-label', 'Carrito de compras');
    drawer.innerHTML = `
      <div id="cart-drawer-header">
        <h3>🛒 Tu Carrito</h3>
        <button id="cart-drawer-close" aria-label="Cerrar carrito">✕</button>
      </div>
      <div id="cart-drawer-body">
        <div id="cd-empty" style="display:none;">
          Tu carrito está vacío 🍰<br>
          <a href="menu.html">Explorar productos</a>
        </div>
        <div id="cd-items"></div>
      </div>
      <div id="cart-drawer-footer">
        <div id="cd-total">Total: $0.00</div>
        <button id="cd-btn-pagar">Confirmar y Pagar</button>
        <a id="cd-ver-completo" href="carritoCompra.html">Ver carrito completo →</a>
      </div>
    `;

    document.body.appendChild(overlay);
    document.body.appendChild(drawer);

    drawer.querySelector('#cart-drawer-close').addEventListener('click', cerrarCarritoDrawer);
    drawer.querySelector('#cd-btn-pagar').addEventListener('click', irAPago);
  }

  /* ══════════════════════════════════════════════════════
     ABRIR / CERRAR
  ══════════════════════════════════════════════════════ */
  function abrirCarritoDrawer() {
    renderCartDrawer();
    document.getElementById('cart-drawer').classList.add('open');
    document.getElementById('cart-drawer-overlay').classList.add('open');
    document.body.style.overflow = 'hidden';

    // Programar aviso de abandono si hay productos
    clearTimeout(_abandonoTimer);
    if (getCarrito().length) {
      _abandonoTimer = setTimeout(mostrarAvisoAbandono, 90000);
    }
  }

  function cerrarCarritoDrawer() {
    const drawer  = document.getElementById('cart-drawer');
    const overlay = document.getElementById('cart-drawer-overlay');
    if (drawer)  drawer.classList.remove('open');
    if (overlay) overlay.classList.remove('open');
    document.body.style.overflow = '';
    clearTimeout(_abandonoTimer);
  }

  /* ══════════════════════════════════════════════════════
     RENDER DEL CONTENIDO
  ══════════════════════════════════════════════════════ */
  function renderCartDrawer() {
    const carrito  = getCarrito();
    const itemsDiv = document.getElementById('cd-items');
    const emptyDiv = document.getElementById('cd-empty');
    const totalDiv = document.getElementById('cd-total');
    const pagarBtn = document.getElementById('cd-btn-pagar');

    if (!itemsDiv) return;
    itemsDiv.innerHTML = '';

    if (!carrito.length) {
      if (emptyDiv) emptyDiv.style.display = 'block';
      if (pagarBtn) pagarBtn.disabled = true;
      if (totalDiv) totalDiv.textContent = 'Total: $0.00';
      actualizarBadge();
      return;
    }

    if (emptyDiv) emptyDiv.style.display = 'none';
    if (pagarBtn) pagarBtn.disabled = false;

    let total = 0;

    carrito.forEach(function (p, i) {
      const qty    = Number(p.cantidad) || 1;
      const precio = parseFloat(p.precio) || 0;
      total += qty * precio;

      const imgSrc = p.imagen_url || p.img ||
        'https://placehold.co/64x64/F8D7DA/A30015?text=🍰';

      const div = document.createElement('div');
      div.className = 'cd-item';
      div.innerHTML = `
        <img src="${_esc(imgSrc)}" alt="${_esc(p.titulo || p.nombre || '')}">
        <div class="cd-item-info">
          <div class="cd-item-title">${_esc(p.titulo || p.nombre || 'Producto')}</div>
          <div class="cd-item-price">
            $${precio.toFixed(2)} c/u
            &nbsp;·&nbsp;
            <strong>$${(qty * precio).toFixed(2)}</strong>
          </div>
          <div class="cd-qty">
            <button data-action="menos" data-index="${i}" aria-label="Reducir cantidad">−</button>
            <span>${qty}</span>
            <button data-action="mas" data-index="${i}" aria-label="Aumentar cantidad">+</button>
          </div>
        </div>
        <button class="cd-item-remove" data-index="${i}" aria-label="Quitar producto">✕</button>
      `;

      // Eventos de cantidad
      div.querySelector('[data-action="menos"]').addEventListener('click', function () {
        cdCambiarCantidad(parseInt(this.dataset.index), -1);
      });
      div.querySelector('[data-action="mas"]').addEventListener('click', function () {
        cdCambiarCantidad(parseInt(this.dataset.index), 1);
      });

      // Botón quitar con rescate
      div.querySelector('.cd-item-remove').addEventListener('click', function () {
        cdQuitarConRescate(parseInt(this.dataset.index));
      });

      itemsDiv.appendChild(div);
    });

    if (totalDiv) totalDiv.textContent = 'Total: $' + total.toFixed(2);
    actualizarBadge();
  }

  /* ══════════════════════════════════════════════════════
     OPERACIONES DE CARRITO
  ══════════════════════════════════════════════════════ */
  function cdCambiarCantidad(index, delta) {
    const carrito = getCarrito();
    if (!carrito[index]) return;

    const nuevo    = (Number(carrito[index].cantidad) || 1) + delta;
    const maxStock = carrito[index].stock != null ? carrito[index].stock : Infinity;

    if (nuevo < 1) {
      // Bajar de 1 = intento de quitar → disparar rescate
      cdQuitarConRescate(index);
      return;
    }
    if (nuevo > maxStock) return;

    carrito[index].cantidad = nuevo;
    setCarrito(carrito);
    renderCartDrawer();
  }

  /* ── Rescate al quitar ─────────────────────────────── */
  function cdQuitarConRescate(index) {
    const carrito  = getCarrito();
    const producto = carrito[index];
    if (!producto) return;

    const toastId = 'cd-toast-' + index + '-' + Date.now();

    const toast = document.createElement('div');
    toast.id        = toastId;
    toast.className = 'cd-toast-rescate';
    toast.innerHTML = `
      <div class="cd-toast-rescate-title">🎁 ¡Un momento!</div>
      <div class="cd-toast-rescate-body">
        Si te llevas <strong>${_esc(producto.titulo || producto.nombre || 'este producto')}</strong>,
        te regalamos un <strong>10% de descuento</strong> en tu próximo pedido.
      </div>
      <div class="cd-toast-btns">
        <button class="cd-toast-btn-accept">¡Lo quiero!</button>
        <button class="cd-toast-btn-dismiss">Quitar igual</button>
      </div>
    `;

    document.body.appendChild(toast);

toast.querySelector('.cd-toast-btn-accept').addEventListener('click', function () {

cdAceptarDescuento(toastId).then((res) => {

  if (res?.codigo) {

    localStorage.setItem(
      'cupon_recuperacion',
      JSON.stringify({
        codigo: res.codigo,
        expira: Date.now() + 604800000
      })
    );

  }

});

});

    toast.querySelector('.cd-toast-btn-dismiss').addEventListener('click', function () {
      toast.remove();
      _eliminarItem(index);
    });

    // Auto-descartar en 10 s sin acción
    setTimeout(function () {
      if (document.getElementById(toastId)) {
        toast.remove();
        _eliminarItem(index);
      }
    }, 10000);
  }

  function _eliminarItem(index) {
    const carrito = getCarrito();
    carrito.splice(index, 1);
    setCarrito(carrito);
    renderCartDrawer();
  }

  /* ── Cupón de recuperación ─────────────────────────── */
  function _guardarCupon() {
    localStorage.setItem(CUPON_KEY, JSON.stringify({
      tipo   : 'porcentaje',
      valor  : 10,
      expira : Date.now() + 86400000 // 24 h
    }));
  }

  /* ══════════════════════════════════════════════════════
     AVISO DE ABANDONO
  ══════════════════════════════════════════════════════ */
  function mostrarAvisoAbandono() {
    if (document.getElementById('cd-aviso-abandono')) return;

    const aviso = document.createElement('div');
    aviso.id = 'cd-aviso-abandono';
    aviso.innerHTML = `
      <div class="cd-abandono-title">⏰ ¿Aún decides?</div>
      <div class="cd-abandono-body">
        ¡No pierdas tus productos! Completa tu compra ahora.
      </div>
      <button class="cd-abandono-btn-pay">Comprar ahora →</button>
      <button class="cd-abandono-btn-close">Cerrar</button>
    `;

    aviso.querySelector('.cd-abandono-btn-pay').addEventListener('click', function () {
      aviso.remove();
      window.location.href = 'pago.html';
    });
    aviso.querySelector('.cd-abandono-btn-close').addEventListener('click', function () {
      aviso.remove();
    });

    document.body.appendChild(aviso);
  }

  /* ══════════════════════════════════════════════════════
     NAVEGAR A PAGO
  ══════════════════════════════════════════════════════ */
  function irAPago() {
    clearTimeout(_abandonoTimer);
    cerrarCarritoDrawer();
    window.location.href = 'pago.html';
  }

  /* ══════════════════════════════════════════════════════
     BADGE DEL NAV
  ══════════════════════════════════════════════════════ */
  function actualizarBadge() {
    const badge = document.getElementById('cart-nav-badge');
    if (!badge) return;
    const n = getCarrito().reduce(function (s, p) {
      return s + (Number(p.cantidad) || 1);
    }, 0);
    badge.textContent = n;
    if (n > 0) {
      badge.classList.remove('hidden');
    } else {
      badge.classList.add('hidden');
    }
  }

  /* ══════════════════════════════════════════════════════
     SNACK NOTIFICATION LIGERO
  ══════════════════════════════════════════════════════ */
  function _mostrarSnack(msg, color) {
    color = color || '#333';
    const n = document.createElement('div');
    n.style.cssText = [
      'position:fixed', 'bottom:20px', 'left:50%',
      'transform:translateX(-50%)',
      'background:' + color,
      'color:#fff', 'padding:11px 22px',
      'border-radius:8px', 'z-index:9500',
      'font-family:Open Sans,sans-serif',
      'font-size:.9rem', 'font-weight:700',
      'box-shadow:0 4px 16px rgba(0,0,0,.2)',
      'pointer-events:none',
      'animation:cdSlideUp .3s ease'
    ].join(';');
    n.textContent = msg;
    document.body.appendChild(n);
    setTimeout(function () { n.remove(); }, 3500);
  }

  function cdAceptarDescuento(toastId) {
  document.getElementById(toastId)?.remove();

  // Intentar crear cupón en cuenta si hay sesión
  fetch('sesion_info.php')
    .then(r => r.json())
    .then(info => {
      if (info.activo && info.rol === 'cliente') {
        // Crear cupón real en la cuenta del cliente
        return fetch('crud_cupones.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ accion: 'rescate' })
        })
        .then(r => r.json())
        .then(res => {
          if (res.ok) {
            // Guardar código en localStorage para que pago.html lo pre-llene
            localStorage.setItem('cupon_recuperacion', JSON.stringify({
              codigo: res.codigo,
              tipo: 'porcentaje',
              valor: 10,
              expira: Date.now() + 30 * 86400000
            }));
            _mostrarSnack('✅ Cupón ' + res.codigo + ' guardado en tu cuenta. ¡Úsalo en tu próxima compra!', '#28a745');
          }
        });
      } else {
        // Sin sesión: solo aviso, no se puede guardar
        _mostrarSnack('⚠️ Inicia sesión para guardar tu cupón de descuento.', '#f97316');
      }
    })
    .catch(() => {
      _mostrarSnack('Error al guardar el cupón. Intenta de nuevo.', '#dc3545');
    });
}

  /* ── Escape HTML básico ────────────────────────────── */
  function _esc(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;');
  }

  /* ══════════════════════════════════════════════════════
     EXPOSICIÓN PÚBLICA
     Las funciones que necesitan llamarse desde el HTML
     quedan en window.
  ══════════════════════════════════════════════════════ */
  window.abrirCarritoDrawer  = abrirCarritoDrawer;
  window.cerrarCarritoDrawer = cerrarCarritoDrawer;
  window.actualizarBadge     = actualizarBadge;
  window.renderCartDrawer    = renderCartDrawer;
  // También exponer agregarAlCarritoDrawer para que otras
  // páginas puedan añadir productos y abrir el drawer
  window.agregarAlCarritoDrawer = function (producto, cantidad) {
    cantidad = cantidad || 1;
    const carrito = getCarrito();
    const idx     = carrito.findIndex(function (p) { return p.id === producto.id; });
    if (idx >= 0) {
      const nuevo    = (Number(carrito[idx].cantidad) || 1) + cantidad;
      const maxStock = carrito[idx].stock != null ? carrito[idx].stock : Infinity;
      carrito[idx].cantidad = Math.min(nuevo, maxStock);
    } else {
      carrito.push(Object.assign({}, producto, { cantidad: cantidad }));
    }
    setCarrito(carrito);
    actualizarBadge();
    abrirCarritoDrawer();
  };

  /* ══════════════════════════════════════════════════════
     INICIALIZACIÓN AUTOMÁTICA AL CARGAR
  ══════════════════════════════════════════════════════ */
  document.addEventListener('DOMContentLoaded', function () {
    inyectarDrawerHTML();
    initCarritoSesion();
  });

})();