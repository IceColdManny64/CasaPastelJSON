<?php
// ver_receta.php
require_once 'JsonHelper.php';

// Recupera el id vía GET (p.ej. ?id=5)
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$jsonHelper = new JsonHelper('./data/');
$postre = $jsonHelper->findById('postresitos', $id);

if (!$postre) {
    echo "<p>Receta no encontrada.</p>";
    exit;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($postre['titulo']) ?> – La Casa del Pastel</title>
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display&display=swap" rel="stylesheet">
  <style>
    body { margin:0; font-family:'Playfair Display', serif; background:#fff4e6; color:#333; }
    header{display:flex;justify-content:space-between;align-items:center;padding:20px 40px;
      background:rgba(0,0,0,0.6);color:#fff;}
    .logo, .icons a { color:#fff; text-decoration:none; }
    .detalle { max-width:800px; margin:30px auto; background:#fff; border-radius:8px;
      box-shadow:0 2px 8px rgba(0,0,0,0.1); overflow:hidden; }
    .detalle img { width:100%; height:400px; object-fit:cover; }
    .info { padding:20px; }
    .info h1 { margin-bottom:10px; color:#b33f4f; }
    .info p { margin:8px 0; line-height:1.5; }
    .info .precio { font-size:1.4em; font-weight:bold; color:#2e7d32; margin:15px 0; }
    .info button { background:#f4a261; color:#000; border:none; padding:12px 20px;
      border-radius:5px; cursor:pointer; transition:background .2s; }
    .info button:hover { background:#e76f51; color:#fff; }
  </style>
</head>
<body>

  <header>
    <a href="menu.html" class="logo">← Volver al Menú</a>
    <div class="icons">
      <a href="carritoCompra.html">🛒 Carrito</a>
    </div>
  </header>

  <div class="detalle">
    <img src="<?= htmlspecialchars($postre['imagen_url']) ?>" alt="<?= htmlspecialchars($postre['titulo']) ?>">
    <div class="info">
      <h1><?= htmlspecialchars($postre['titulo']) ?></h1>
      <p><strong>Categoría:</strong> <?= htmlspecialchars($postre['categoria']) ?></p>
      <p><strong>Sabor:</strong> <?= htmlspecialchars($postre['sabor']) ?></p>
      <p><strong>Tamaño:</strong> <?= htmlspecialchars(strtoupper($postre['tamanio'])) ?></p>
      <p class="precio">$<?= number_format($postre['precio'], 2) ?></p>
      <p><?= nl2br(htmlspecialchars($postre['descripcion'])) ?></p>
      <button id="btnAgregar">🛒 Añadir al carrito</button>
    </div>
  </div>

  <script>
    document.getElementById('btnAgregar').addEventListener('click', () => {
      const receta = {
        id:    <?= json_encode($postre['id']) ?>,
        titulo:<?= json_encode($postre['titulo']) ?>,
        precio:<?= json_encode($postre['precio']) ?>,
        imagen:<?= json_encode($postre['imagen_url']) ?>,
        cantidad: 1
      };
      const carrito = JSON.parse(localStorage.getItem('carrito')) || [];
      const idx = carrito.findIndex(p => p.id === receta.id);
      if (idx > -1) {
        carrito[idx].cantidad++;
      } else {
        carrito.push(receta);
      }
      localStorage.setItem('carrito', JSON.stringify(carrito));
      alert(`✅ "${receta.titulo}" añadido al carrito.`);
    });
  </script>

</body>
</html>