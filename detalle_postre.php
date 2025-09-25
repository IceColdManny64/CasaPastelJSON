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
  <style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Playfair Display', serif; background:#f9f9f9; color:#333; }
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
        <span class="new-price">$<?= number_format($new_price,2) ?> (20% OFF)</span>
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

<script>
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
      alert(`Solo hay ${existente.stock} unidades disponibles.`);
      return;
    }
    carrito[indexExistente].cantidad += 1;
  } else {
    carrito.push(producto);
  }

  localStorage.setItem("carrito", JSON.stringify(carrito));
  alert("Producto añadido al carrito.");
});
</script>

</body>
</html>
