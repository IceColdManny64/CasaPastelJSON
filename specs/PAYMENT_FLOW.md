# PAYMENT_FLOW.md — CasaPastel ERP/CRM

> Generado: 2026-06-02 | Análisis de flujo de pago y checkout

---

## 1. Arquitectura General del Checkout

```
[carritoCompra.html]
    │ localStorage['carrito']
    ▼
[pago.html] ← SPA de checkout
    │
    ├─ Carga inicial:
    │   ├─ loadCart() → lee localStorage
    │   ├─ prefillCheckout() → fetch('sesion_info.php') → rellena formulario
    │   ├─ checkSesionPago() → fetch('sesion_info.php') → muestra/oculta cupón
    │   └─ prefillCupon() → lee localStorage['cupon_recuperacion']
    │
    ├─ Selección de método → togglePayment(method)
    │
    ├─ Cupón → validarCupon() → fetch('validar_cupon.php')
    │
    └─ Confirmar → btnConfirmPay.click → _registrarPedido() → fetch('procesar_pago.php')
```

---

## 2. Pipeline Obligatorio (SPEC-03)

```
seleccionar método
→ validar datos locales
→ crear pedido (procesar_pago.php)
    ├─ verificar stock
    ├─ calcular subtotal
    ├─ aplicar promociones automáticas
    ├─ aplicar cupón
    ├─ descontar stock
    ├─ registrar movimientos
    ├─ guardar pedido en pedidos.json
    ├─ registrar ingreso en finanzas.json
    ├─ marcar cupón como usado
    ├─ sincronizar CRM
    └─ registrar auditoría
→ actualizar UI
→ redirect seguimiento.html
```

---

## 3. Estado por Método de Pago

### 3.1 Tarjeta de Crédito/Débito (`card`)

```
[pago.html] → usuario llena datos de tarjeta
    │
    ▼ click "Pagar con Tarjeta"
Validaciones locales (pago.html):
    ├─ cardNum vacío o < 15 dígitos → error
    ├─ cardNum empieza '0000' → "banco declinó" (simulado)
    ├─ cardNum empieza '1111' → "fondos insuficientes" (simulado)
    └─ cardNum empieza '5555' → "error de conexión" (simulado)
    │
    ▼ await _registrarPedido('card')
        ↑
        └─── BUG: _registrarPedido() se llama Y LUEGO
             el código continúa y llama procesar_pago.php de nuevo (línea 686)
             → POSIBLE DOBLE REGISTRO

ESTADO: ✅ Registra pedido en backend | ⚠️ Posible doble llamada
```

### 3.2 PayPal (`paypal`)

**Camino A — Botón "Pagar con PayPal" (payment-body):**
```
[pago.html]
    ▼ click btn-paypal
processExternalPayment('PayPal')
    → openModal('default', 'Conectando...')
    → setTimeout 2500ms
    → window.location.href = 'seguimiento.html'
    ← NO llama procesar_pago.php
    ← NO registra pedido en backend
    ← NO registra finanzas ni auditoría
```

**Camino B — Botón "Confirmar Compra" (main) con PayPal seleccionado:**
```
[pago.html] → btnConfirmPay click
    ▼ selectedMethod === 'paypal'
    → await _registrarPedido('paypal')
    → return   ← sale del setTimeout
    ← SIN segundo fetch a procesar_pago.php
```

> [!CAUTION]
> El Camino A (botón de PayPal propio) **nunca registra el pedido**. El Camino B sí lo hace, pero el `btnConfirmPay` se oculta (`btn.style.display = 'none'`) cuando el método es `paypal`. Entonces el **flujo real para PayPal no registra pedido**.

**ESTADO: ❌ No registra pedido | ❌ No registra finanzas | ❌ No registra auditoría**

### 3.3 MercadoPago (`mercadopago`)

Idéntico a PayPal (misma función `processExternalPayment`):

**ESTADO: ❌ No registra pedido | ❌ No registra finanzas | ❌ No registra auditoría**

### 3.4 Monedero Digital (`wallet`)

```
[pago.html]
    ▼ click "Pagar con Saldo"
Validación:
    └─ currentTotal > currentWalletBalance → error "Saldo Insuficiente"
    │ (currentWalletBalance = 5000 hardcoded, sin backend)
    │
    ▼ await _registrarPedido('wallet')
    → llama procesar_pago.php con metodo='wallet'
    → Registra pedido, finanzas, auditoría

PROBLEMA: El balance del monedero no se descuenta en ningún JSON.
El saldo de 5000 es siempre el mismo independientemente de compras previas.
```

**ESTADO: ✅ Registra pedido | ⚠️ Wallet mock sin backend real**

### 3.5 Cheque Electrónico (`check`)

```
[pago.html]
    ▼ click "Emitir Cheque"
Validación:
    └─ checkNumber vacío → error
    │
    ▼ await _registrarPedido('check')
    → llama procesar_pago.php con metodo='check'
    → Registra pedido, finanzas, auditoría

```

**ESTADO: ✅ Registra pedido**

### 3.6 Depósito Bancario / Transferencia (`atm`)

```
[pago.html]
    ▼ click "Imprimir Ficha"
    → Sin validación adicional
    ▼ await _registrarPedido('atm')
    → llama procesar_pago.php con metodo='atm'
    → Registra pedido, finanzas, auditoría
```

**ESTADO: ✅ Registra pedido (pago diferido)**

---

## 4. Resumen de Métodos

| Método | Registra pedido | Registra finanzas | Registra auditoría | Valida stock |
|---|---|---|---|---|
| Tarjeta | ✅ Sí* | ✅ Sí | ✅ Sí | ✅ Sí |
| PayPal | ❌ No | ❌ No | ❌ No | ❌ No |
| MercadoPago | ❌ No | ❌ No | ❌ No | ❌ No |
| Monedero | ✅ Sí | ✅ Sí | ✅ Sí | ✅ Sí |
| Cheque | ✅ Sí | ✅ Sí | ✅ Sí | ✅ Sí |
| Transferencia | ✅ Sí | ✅ Sí | ✅ Sí | ✅ Sí |

*Tarjeta puede tener doble llamada por bug de código muerto.

---

## 5. Pipeline de procesar_pago.php — Detalle

```
POST /procesar_pago.php
{
    items: [{id, titulo, cantidad, precio}],
    cliente: string,
    correo: string,
    direccion: string,
    metodo: string,
    cupon_codigo: string|''
}

PASO 1: Validar request
    └─ items ausentes → 400

PASO 2: Generar folio
    └─ 'CP-' + date('Ymd') + '-' + substr(uniqid(), -4)
    ← No garantiza unicidad (RC-03)

PASO 3: Verificar stock (loop)
    └─ findById('postresitos', item.id)
    └─ if stock < cantidad → 409 "Stock insuficiente"

PASO 4: Calcular subtotal
    └─ sum(precio × cantidad)

PASO 5: Aplicar promociones automáticas
    └─ getAll('promociones') → filtrar activas + vigentes
    └─ Loop por cada promoción:
        ├─ Verificar segmento (todos/vip/frecuente) vs etiqueta CRM del usuario
        ├─ Calcular base según aplica_a (todos/categoria/producto)
        └─ Aplicar tipo (descuento_porcentaje/precio_especial/2x1)
    └─ Cap: descuentoPromo <= subtotal

PASO 6: Aplicar cupón
    └─ Si cupon_codigo Y usuario_id presente:
        ├─ Buscar cupón por código
        ├─ Validar: esSuyo (empty usuario_id OR mismo uid) + vigente + no usado
        └─ Calcular: % del (subtotal - descuentoPromo) o monto fijo

PASO 7: total = max(0, subtotal - descuentoPromo - descuentoCupon)

PASO 8: Descontar stock
    └─ update('postresitos', id, {stock: nuevo})
    └─ create('movimientos', {tipo:'salida', ...})
    ← Sin transacción: si falla en la mitad, stock queda inconsistente

PASO 9: Crear pedido
    └─ create('pedidos', {...})

PASO 10: Registrar ingreso en finanzas
    └─ create('finanzas', {tipo:'ingreso', monto: total})
    ← Registra monto total DESPUÉS de descuentos (correcto)

PASO 11: Marcar cupón usado
    └─ update('cupones', cuponId, {usado: true, folio_pedido: folio})

PASO 12: Sincronizar CRM
    └─ Buscar en crm_clientes por correo o usuario_id
    ├─ Si no existe: create con etiqueta='nuevo', total_compras=1
    └─ Si existe:
        ├─ nuevasCompras = existente.total_compras + 1
        ├─ nuevoLTV = existente.ltv + total
        ├─ nuevaEtiqueta = ≥5→vip, ≥3→frecuente, else→nuevo
        ├─ update('crm_clientes', ...)
        └─ update('usuarios', uid, {tipo_cuenta: mapa[etiqueta]})

PASO 13: Registrar auditoría
    └─ registrarAuditoria('Ventas', 'pedido.confirmar', ...)

PASO 14: Respuesta OK
    └─ {ok, msg, folio, subtotal, descuento_promo, descuento_cupon, total}
```

---

## 6. Flujo de Validación de Cupón

```
POST /validar_cupon.php { codigo: string }

GUARDIA: uid requerido + rol === 'cliente'
    → Si no: 'Debes iniciar sesión'

BÚSQUEDA: strtoupper(codigo) contra todos los cupones

VALIDACIONES:
    1. Existe el código → si no: 'Cupón no válido'
    2. !usado → si usado: 'Ya fue utilizado'
    3. usuario_id vacío OR mismo uid → si no: 'No corresponde a tu cuenta'
    4. fecha_expiracion vacía OR >= hoy → si no: 'Ha expirado'

RESPUESTA OK:
    { ok, msg, tipo, valor, codigo, id }

NOTA: Solo valida, NO usa ni descuenta. El uso lo registra procesar_pago.php.
```

---

## 7. Flujo de Descuentos — Diagrama de Cálculo

```
subtotal = Σ(precio × cantidad)
    │
    ▼
descuentoPromo = 0
    │
    ├─ Para cada promoción activa vigente:
    │   ├─ Segmento: ¿usuario califica? (todos/frecuente/vip)
    │   ├─ Base: subtotal parcial según aplica_a
    │   └─ Acumula:
    │       porcentaje  → base × (valor/100)
    │       precio_esp. → max(0, base - valor)
    │       2x1         → base × 0.5
    │
    ▼
descuentoPromo = min(descuentoPromo, subtotal)
    │
    ▼
descuentoCupon = 0
    │
    ├─ Si cupón válido:
    │   porcentaje → (subtotal - descuentoPromo) × (valor/100)
    │   monto_fijo → valor
    │
    ▼
total = max(0, subtotal - descuentoPromo - descuentoCupon)

DISCREPANCIA: pago.html recalcula el descuento de cupón sobre subtotal bruto.
procesar_pago.php calcula el descuento de cupón sobre (subtotal - descuentoPromo).
El usuario ve un descuento distinto al que realmente se aplica.
```

---

## 8. Gaps vs SPEC-03

| Req. SPEC-03 | Estado |
|---|---|
| Pipeline unificado para todos los métodos | ❌ PayPal/MP no siguen el pipeline |
| Crear pedido en todos los métodos | ❌ PayPal/MP no crean pedido |
| Registrar finanzas en todos los métodos | ❌ PayPal/MP no registran |
| Registrar auditoría en todos los métodos | ❌ PayPal/MP no registran |
| Actualizar stock en todos los métodos | ❌ PayPal/MP no actualizan |
| Finalizar checkout con confirmación | ✅ Para métodos que pasan el pipeline |
| Actualizar UI tras pago | ✅ Redirect a seguimiento.html |
| Generar folio de confirmación | ✅ Formato CP-YYYYMMDD-XXXX |

---

## 9. Problemas de Sincronización Frontend/Backend

| Problema | Detalle |
|---|---|
| Descuento de cupón diferente en UI vs backend | pago.html calcula sobre subtotal, procesar_pago.php sobre (subtotal - promo) |
| Carrito en localStorage vs stock real | Sin reserva de stock, otro cliente puede comprar mientras el usuario está en checkout |
| Promociones automáticas no visibles en pago.html | El usuario no ve los descuentos de promo hasta después de confirmar (la respuesta del backend incluye los montos pero la UI no los muestra antes) |
| `_registrarPedido()` vs llamada directa | La función interna no está bien definida para todos los paths, causando flujo caótico |
