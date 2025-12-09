<?php
// detalle_postre.php
require_once 'JsonHelper.php';

// Obtener y validar ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header("Location: ofertas.php");
    exit;
}

// Buscar postre en JSON
$jsonHelper = new JsonHelper('./data/');
$postre = $jsonHelper->findById('postresitos', $id);

if (!$postre) {
    header("Location: ofertas.php");
    exit;
}

// Simulación de 20% OFF
$old_price = $postre['precio'];
$new_price = round($old_price * 0.8, 2);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($postre['titulo']) ?> – Detalle | La Casa del Pastel</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">
  <!-- Fuente Open Sans para textos del modal -->
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans&display=swap" rel="stylesheet">
  <style>
     footer.legal-footer {
      background-color: rgba(0, 0, 0, 0.8);
      color: #ccc;
      padding: 25px;
      text-align: center;
      font-size: 0.8em;
      font-family: 'Open Sans', sans-serif;
      z-index: 2;
      width: 100%;
    }

    footer.legal-footer a {
      color: #f4a261;
      text-decoration: none;
      margin: 0 10px;
    }

    footer.legal-footer a:hover {
      text-decoration: underline;
    }

    footer.legal-footer p {
      margin: 0;
      padding: 5px 0;
    }

    footer.legal-footer .credits {
      margin-top: 10px;
      opacity: 0.7;
    }
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Playfair Display', serif; background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)),
        url('https://cdn.pixabay.com/photo/2023/09/04/20/39/cake-8233676_1280.jpg') no-repeat center center/cover; color:#333; }
    header { display:flex; align-items:center; justify-content:space-between;
             padding:20px 60px; background:rgba(0, 0, 0, 1); position:fixed; width:100%; top:0; z-index:10; }
    .logo { display:flex; align-items:center; color:white; font-size:1.5em; }
    .logo-img { height:40px; margin-right:10px; }
    nav a { margin:0 15px; color:white; text-decoration:none; }
    .icons a { margin-left:15px; }
    .icon-img { width:24px; height:24px; transition:transform .2s; }
    .icon-img:hover { transform:scale(1.1); }
    .container { max-width:800px; margin:140px auto 60px; background:#fff;
                 border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,0.1); overflow:hidden; }
    .hero { width:100%; height:400px; background-size:cover; background-position:center; }
    .details { padding:30px; }
    .details h1 { color:#b33f4f; margin-bottom:15px; }
    .price { font-size:1.4em; margin-bottom:10px; }
    .old-price { text-decoration:line-through; color:#999; margin-right:10px; }
    .new-price { color:#e76f51; font-weight:bold; }
    .meta { margin:15px 0; font-size:0.95em; color:#555; }
    .meta span { display:inline-block; margin-right:15px; }
    .description { line-height:1.6; margin-bottom:25px; }
    .actions { display:flex; gap:15px; }
    .actions button {
      padding:12px 25px; border:none; border-radius:6px; cursor:pointer;
      font-size:1em; font-weight:bold;
    }
    .btn-cart { background:#28a745; color:white; }
    .btn-cart:hover { background:#218838; }
    .btn-back { background:#ccc; color:#333; }
    .btn-back:hover { background:#bbb; }

    /* --- MODAL PERSONALIZADO (CSS) --- */
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
    
    .custom-modal-title { 
      font-size: 1.4rem; font-weight: 700; margin-bottom: 10px; 
      color: #a30015; font-family: 'Playfair Display', serif; 
    }
    
    .custom-modal-msg { font-size: 1rem; margin-bottom: 25px; color: #555; line-height: 1.5; }
    
    .custom-btn-modal {
      padding: 12px 24px; border: none; border-radius: 8px;
      font-weight: 600; cursor: pointer; transition: all 0.2s; font-size: 0.95rem;
      background: linear-gradient(135deg, #a30015, #d6001c); color: white;
      box-shadow: 0 4px 10px rgba(214,0,28,0.3);
    }
    .custom-btn-modal:hover { transform: translateY(-2px); box-shadow: 0 6px 15px rgba(214,0,28,0.4); }
  </style>
</head>
<body>

  <!-- Nav fija -->
  <header>
    <div class="logo">
      <img src="CasaPastel.png" alt="Logo" class="logo-img">
      La Casa del Pastel
    </div>
    <nav>
      <a href="PantallaPrincipal.html">Inicio</a>
      <a href="menu.html">Menú</a>
      <a href="conocenos.html">Conócenos</a>
      <a href="ofertas.php">Ofertas</a>
      <a href="contacto.html">Contacto</a>
    </nav>
    <div class="icons">
            <a href="https://www.instagram.com/"><img src="https://static.vecteezy.com/system/resources/previews/016/716/469/non_2x/instagram-icon-free-png.png" alt="Instagram" class="icon-img"></a>
            <a href="https://www.facebook.com/"><img src="https://cliply.co/wp-content/uploads/2019/04/371903520_SOCIAL_ICONS_FACEBOOK.png" alt="Facebook" class="icon-img"></a>
            <a href="https://www.x.com/"><img src="https://vectorseek.com/wp-content/uploads/2023/07/Twitter-X-Logo-Vector-01-2.jpg" alt="X" class="icon-img"></a>
            <a href="carritoCompra.html">🛒</a>
    </div>
  </header>

  <!-- Contenido -->
  <div class="container">
    <div class="hero" style="background-image:url('<?= htmlspecialchars($postre['imagen_url']) ?>')"></div>
    <div class="details">
      <h1><?= htmlspecialchars($postre['titulo']) ?></h1>
      <div class="price">
        <span class="old-price">$<?= number_format($old_price,2) ?></span>
        <span class="new-price">$<?= number_format($new_price,2) ?> (20% MENOS)</span>
      </div>
      <div class="meta">
        <span>Categoría: <?= htmlspecialchars($postre['categoria']) ?></span>
        <span>Tamaño: <?= htmlspecialchars(strtoupper($postre['tamanio'])) ?></span>
        <span>Sabor: <?= htmlspecialchars($postre['sabor']) ?></span>
      </div>
      <div class="description"><?= nl2br(htmlspecialchars($postre['descripcion'])) ?></div>
      <div class="actions">
        <button class="btn-cart">Añadir al carrito</button>
        <button class="btn-back" onclick="history.back()">Volver</button>
      </div>
    </div>
  </div>

  <!-- MODAL PERSONALIZADO (HTML) -->
  <div id="custom-modal-overlay" class="custom-modal-overlay">
    <div class="custom-modal-box">
      <span id="custom-modal-icon" class="custom-modal-icon"></span>
      <div id="custom-modal-title" class="custom-modal-title"></div>
      <div id="custom-modal-msg" class="custom-modal-msg"></div>
      <button class="custom-btn-modal" onclick="closeModal()">Aceptar</button>
    </div>
  </div>

<script>
// --- FUNCIONES DEL MODAL ---
function showModal(message, title = "Aviso", icon = "ℹ️") {
  const overlay = document.getElementById('custom-modal-overlay');
  const msgElement = document.getElementById('custom-modal-msg');
  const titleElement = document.getElementById('custom-modal-title');
  const iconElement = document.getElementById('custom-modal-icon');
  
  msgElement.innerHTML = message; // Permite HTML simple
  titleElement.textContent = title;
  iconElement.textContent = icon;
  
  overlay.style.display = 'flex';
  // Forzar reflow para animación
  void overlay.offsetWidth; 
  overlay.classList.add('open');
}

function closeModal() {
  const overlay = document.getElementById('custom-modal-overlay');
  overlay.classList.remove('open');
  setTimeout(() => {
    overlay.style.display = 'none';
  }, 300);
}

// --- LOGICA DEL CARRITO ---
document.querySelector(".btn-cart").addEventListener("click", function () {
  const producto = {
    id: <?= $postre['id'] ?>,
    titulo: <?= json_encode($postre['titulo']) ?>,
    precio: <?= $new_price ?>,
    descripcion: <?= json_encode($postre['descripcion']) ?>,
    imagen_url: <?= json_encode($postre['imagen_url']) ?>,
    cantidad: 1,
    stock: <?= intval($postre['stock'] ?? 10) ?>
  };

  let carrito = JSON.parse(localStorage.getItem("carrito")) || [];
  const indexExistente = carrito.findIndex(p => p.id === producto.id);

  if (indexExistente !== -1) {
    const existente = carrito[indexExistente];
    if (existente.cantidad + 1 > existente.stock) {
      // REEMPLAZO: Alert nativo por modal
      showModal(`Solo hay <strong>${existente.stock}</strong> unidades disponibles en inventario.`, "Stock Limitado", "⚠️");
      return;
    }
    carrito[indexExistente].cantidad += 1;
  } else {
    carrito.push(producto);
  }

  localStorage.setItem("carrito", JSON.stringify(carrito));
  // REEMPLAZO: Alert nativo por modal de éxito
  showModal("El producto se ha añadido correctamente a tu carrito.", "¡Añadido!", "✅");
});
</script>
            <!-- INICIO: PIE DE PÁGINA LEGAL Y CRÉDITOS -->
  <footer class="legal-footer">
    <p>
      <a href="legales.html#terminos">Términos y Condiciones</a> |
      <a href="legales.html#privacidad">Aviso de Privacidad</a> |
      <a href="legales.html#devoluciones">Políticas de Devolución</a>
    </p>
    <p class="credits">
      Créditos de imagen: Todas las fotografías utilizadas en este sitio (incluyendo el fondo) fueron obtenidas de <a href="https://pixabay.com" target="_blank">Pixabay</a> bajo licencia de uso libre.
    </p>
    <p>© 2025 La Casa del Pastel. Todos los derechos reservados.</p>
  </footer>
  <!-- FIN: PIE DE PÁGINA -->

</body>
</html>