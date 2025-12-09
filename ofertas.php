<?php
// Incluir la lógica que prepara $ofertas (ahora segura contra errores)
include 'ofertas_logic.php';
?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Ofertas Especiales - La Casa del Pastel</title>
  <!-- Fuente Open Sans para textos del modal -->
  <link href="https://fonts.googleapis.com/css2?family=Open+Sans&display=swap" rel="stylesheet">
<style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html,
        body {
            height: 100%;
            font-family: 'Playfair Display', serif;
        }

        

        body {
            position: relative;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

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
        


        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)),
                url('https://cdn.pixabay.com/photo/2023/09/04/20/39/cake-8233676_1280.jpg') no-repeat center center/cover;
            z-index: -1;
        }

        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 60px;
            background-color: rgba(0, 0, 0, 0.6);
            z-index: 1;
        }

        .logo {
            display: flex;
            align-items: center;
            font-size: 1.5em;
            color: white;
        }

        .logo-img {
            height: 40px;
            margin-right: 10px;
        }

        nav a {
            margin: 0 15px;
            text-decoration: none;
            color: white;
            font-size: 1em;
        }

        .icons a {
            margin-left: 15px;
            color: white;
            text-decoration: none;
        }

        .icon-img {
            width: 24px;
            height: 24px;
            transition: transform 0.2s;
        }

        .icon-img:hover {
            transform: scale(1.1);
        }

        .offers-container {
            max-width: 1200px;
            margin: 40px auto;
            padding: 0 20px;
            z-index: 2;
            position: relative;
        }

        .offers-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .offers-header h1 {
            font-size: 2.5em;
            color: white;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        .offers-header p {
            font-size: 1.2em;
            color: white;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .offer-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 30px;
        }

        .offer-card {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
            position: relative;
        }

        .offer-card:hover {
            transform: translateY(-10px);
        }

        .offer-image {
            height: 200px;
            background-size: cover;
            background-position: center;
        }

        .offer-content {
            padding: 20px;
        }

        .offer-tag {
            display: inline-block;
            background-color: #e76f51;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.8em;
            margin-bottom: 10px;
        }

        .offer-title {
            font-size: 1.5em;
            color: #5a3d7a;
            margin-bottom: 10px;
        }

        .offer-description {
            color: #666;
            margin-bottom: 20px;
            line-height: 1.5;
        }

        .offer-price {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .old-price {
            text-decoration: line-through;
            color: #999;
            margin-right: 10px;
        }

        .new-price {
            font-size: 1.3em;
            color: #e76f51;
            font-weight: bold;
        }

        .offer-actions {
            display: flex;
            justify-content: space-between;
        }

        .details-btn {
            background-color: #5a3d7a;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .details-btn:hover {
            background-color: #3d2855;
        }

        nav a {
            margin: 0 15px;
            text-decoration: none;
            color: white;
            font-size: 1em;
        }

        .order-btn {
            background-color: #f4a261;
            color: black;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .order-btn:hover {
            background-color: #e76f51;
        }

        .weekly-offer-tag {
            position: fixed;
            top: 100px;
            right: 20px;
            width: 120px;
            height: 120px;
            background-color: #e76f51;
            border-radius: 50%;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
            z-index: 10;
            transform: rotate(15deg);
            animation: pulse 2s infinite;
            cursor: pointer;
            text-decoration: none;
        }

        .weekly-offer-tag::before {
            content: '';
            position: absolute;
            top: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 0;
            height: 0;
            border-left: 15px solid transparent;
            border-right: 15px solid transparent;
            border-bottom: 25px solid #e76f51;
        }

        .weekly-offer-tag h3 {
            font-size: 1.1em;
            margin-bottom: 5px;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
        }

        .weekly-offer-tag p {
            font-size: 0.9em;
            text-shadow: 1px 1px 1px rgba(0, 0, 0, 0.3);
        }

        @keyframes pulse {
            0% { transform: rotate(15deg) scale(1); }
            50% { transform: rotate(15deg) scale(1.05); }
            100% { transform: rotate(15deg) scale(1); }
        }

        .pointing-arrow {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%) rotate(180deg);
            width: 0;
            height: 0;
            border-left: 15px solid transparent;
            border-right: 15px solid transparent;
            border-top: 25px solid #e76f51;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {transform: translateX(-50%) rotate(180deg) translateY(0);}
            40% {transform: translateX(-50%) rotate(180deg) translateY(-10px);}
            60% {transform: translateX(-50%) rotate(180deg) translateY(-5px);}
        }

        @media (max-width: 768px) {
            .weekly-offer-tag {
                width: 100px;
                height: 100px;
                top: 80px;
                right: 10px;
            }
            
            .weekly-offer-tag h3 {
                font-size: 0.9em;
            }
            
            .weekly-offer-tag p {
                font-size: 0.8em;
            }
        }

        .highlight-offers {
            animation: highlight 2s;
        }

        @keyframes highlight {
            0% { background-color: transparent; }
            20% { background-color: rgba(244, 162, 97, 0.3); }
            100% { background-color: transparent; }
        }

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

  <div class="offers-container">
    <div class="offers-header">
      <h1>Ofertas Especiales</h1>
      <p>¡Aprovecha un 20% de descuento en estas delicias!</p>
    </div>

    <div class="offer-cards">
      <?php if (empty($ofertas)): ?>
        <p style="text-align:center; font-size:1.2em; color:white; background:rgba(0,0,0,0.5); padding:20px; border-radius:10px;">No hay ofertas disponibles en este momento.</p>
      <?php else: ?>
        <?php foreach ($ofertas as $o): ?>
          <div class="offer-card">
            <div class="offer-image"
                 style="background-image: url('<?= htmlspecialchars($o['imagen_url']) ?>');">
            </div>
            <div class="offer-content">
              <span class="offer-tag">OFERTA</span>
              <h3 class="offer-title"><?= htmlspecialchars($o['titulo']) ?></h3>
              <p class="offer-description"><?= htmlspecialchars($o['descripcion']) ?></p>
              <div class="offer-price">
                <span class="old-price">$<?= number_format($o['old_price'],2) ?></span>
                <span class="new-price">$<?= number_format($o['new_price'],2) ?></span>
              </div>
              <div class="offer-actions">
                <button class="details-btn"
                        onclick="window.location='detalle_postre.php?id=<?= $o['id'] ?>'">
                  Ver más
                </button>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- MODAL PERSONALIZADO (HTML - Estructura base) -->
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
      
      msgElement.innerHTML = message;
      titleElement.textContent = title;
      iconElement.textContent = icon;
      
      overlay.style.display = 'flex';
      // Forzar reflow
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