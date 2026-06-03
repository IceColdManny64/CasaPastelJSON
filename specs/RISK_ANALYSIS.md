# RISK_ANALYSIS.md — CasaPastel ERP/CRM

> Generado: 2026-06-02 | Sin modificación de código

---

## Resumen Ejecutivo

| Severidad | Cantidad | Categoría Principal |
|---|---|---|
| 🔴 CRÍTICO | 7 | Seguridad, integridad de datos, race conditions |
| 🟠 ALTO | 9 | Lógica duplicada, estado desincronizado, permisos inconsistentes |
| 🟡 MEDIO | 8 | Auditoría incompleta, endpoints incompletos, UX degradado |
| 🟢 BAJO | 5 | Deuda técnica, nomenclatura, mejoras menores |

---

## RIESGOS CRÍTICOS 🔴

### RC-01 — Race Condition en JsonHelper.writeData()
**Archivo:** `JsonHelper.php` L33-36  
**Problema:** `file_put_contents()` directo sin bloqueo de archivo (`LOCK_EX` ausente) y sin archivo temporal.  
**Escenario:** Dos clientes completan checkout simultáneamente → ambos leen el mismo stock → ambos decrementan → el segundo sobrescribe el primero → **pérdida de un pedido**.  
**Violación:** SPEC-09 (escritura atómica)  
**Afecta:** `pedidos.json`, `postresitos.json`, `finanzas.json`, `cupones.json`  
**Mitigación requerida:** `file_put_contents($path, $json, LOCK_EX)` + escritura en archivo temporal + rename atómico.

### RC-02 — Endpoints de Productos sin Guard de Autenticación
**Archivos:** `crear_postre.php`, `editar_postre.php`, `eliminar_postre.php`  
**Problema:** Cualquier persona con acceso HTTP puede crear, editar o eliminar productos sin estar autenticada.  
**Escenario:** Request directo via `curl -X POST crear_postre.php` sin sesión → producto creado.  
**Violación:** SPEC-12, ROLE-MATRIX.md  
**Severidad:** CRÍTICA — sabotaje de catálogo sin rastro de auditoría.

### RC-03 — Endpoints de Usuarios sin Guard
**Archivos:** `editar_usuario.php`, `eliminar_usuario.php`, `listar_usuario_individual.php`  
**Problema:** Sin `require verificar_sesion.php` → cualquier usuario (o atacante) puede modificar/eliminar cualquier cuenta cliente.  
**Violación:** SPEC-12 (validar sesión en backend)  

### RC-04 — Contraseñas en Texto Plano — Panel Administrativo
**Archivo:** `data/admons.json`  
**Problema:** Todos los usuarios del panel (incluyendo admin) tienen contraseñas en texto plano (`"passw": "admin"`).  
**Función afectada:** `JsonHelper.authenticateUser()` L148-158 — compara strings directamente, sin `password_verify()`.  
**Violación:** SPEC-12, buenas prácticas de seguridad mínimas.  
**Riesgo adicional:** El archivo `data/.htaccess` bloquea acceso web directo, pero si falla XAMPP config, el JSON queda expuesto.

### RC-05 — Cupón Reutilizable por Condición de Carrera
**Archivos:** `validar_cupon.php`, `procesar_pago.php`  
**Problema:** `validar_cupon.php` valida pero **no marca el cupón como usado**. `procesar_pago.php` lo marca después.  
**Escenario de race condition:** Usuario A y Usuario B tienen el mismo código de cupón (sin `usuario_id`). Ambos validan simultáneamente → ambos obtienen `ok:true` → ambos llaman `procesar_pago.php` casi simultáneamente → el segundo escribe `usado=true` pero el primero ya lo aplicó.  
**Violación:** SPEC-01 (usos_restantes), SPEC-12  

### RC-06 — Pago Externo (PayPal/MercadoPago) Redirige sin Procesar Pedido
**Archivo:** `pago.html` L659-662  
**Problema:**
```javascript
} else if (selectedMethod === 'paypal' || selectedMethod === 'mercadopago') {
    await _registrarPedido(selectedMethod);  // llama _registrarPedido()
    return;
}
```
`_registrarPedido()` está definido en `pago.html` pero el bloque exterior luego llama `procesar_pago.php` de todos modos (L686-706). **Hay código muerto y doble llamada potencial.**  
Adicionalmente, `processExternalPayment('PayPal')` (línea 618-623) redirige a `seguimiento.html` tras 2.5 segundos **sin registrar el pedido en absoluto**.

### RC-07 — Finanzas Calculada Inconsistentemente
**Archivo:** `panel.html` función `mostrarFinanzas()` L1158-1200  
**Problema:** El módulo de Finanzas en el panel **no lee `finanzas.json`**. En cambio, calcula ingresos leyendo `listar_pedidos.php` y egresos leyendo `crud_ordenes.php`. Esto ignora:  
- Egresos por nómina registrados en `finanzas.json`  
- Egresos por devoluciones registrados en `finanzas.json`  
- Cualquier movimiento que no sea pedido u orden de compra  
**Resultado:** Los KPIs financieros mostrados en el panel son **incorrectos** por naturaleza.

---

## RIESGOS ALTOS 🟠

### RA-01 — Doble Implementación de aplicarRol (IIFE + función)
**Archivo:** `panel.html` L457-472 y L742-785  
**Problema:** Dos funciones distintas hacen lo mismo: IIFE `aplicarRol()` en L457 y `aplicarRolPanel()` en L742. La IIFE se ejecuta inmediatamente al cargar, `aplicarRolPanel()` se ejecuta en `DOMContentLoaded`. Ambas llaman `sesion_info.php`.  
**Consecuencias:**  
- Doble petición HTTP innecesaria en cada carga del panel  
- Inconsistencia si las dos obtienen respuestas distintas (improbable pero posible)  
- Código imposible de mantener correctamente  
**Violación:** SPEC-00 (función única `refreshPanelState()`)

### RA-02 — Lógica de Clasificación VIP/Frecuente en 3 Lugares
**Archivos:** `procesar_pago.php` L198-199, `listar_crm.php` L30, `actualizar_crm_cliente.php`  
**Discrepancia detectada:**
| Lugar | Criterio frecuente | Criterio VIP |
|---|---|---|
| `procesar_pago.php` | `total_compras >= 3` | `total_compras >= 5` |
| `listar_crm.php` | `count(pedidos) >= 3` | `LTV > 500` |
| `actualizar_crm_cliente.php` | Manual desde UI | Manual desde UI |

**Impacto:** Un cliente puede ser `vip` en CRM pero `frecuente` en sesión y `nuevo` en ofertas.

### RA-03 — Panel Conserva Estado Tras Logout
**Archivos:** `panel.html`, `logout.php`  
**Problema:** `logout.php` destruye la sesión PHP correctamente. Pero el panel HTML **no tiene un handler de logout que limpie el estado visual**. Si el usuario cierra sesión y abre otra sesión con distinto rol, el panel puede mostrar botones del rol anterior hasta que `DOMContentLoaded` se re-ejecute (solo ocurre en full page reload).  
**Violación:** SPEC-06 ("destruir estado tras logout"), SPEC-11

### RA-04 — Estado del Carrito Persiste en localStorage Indefinidamente
**Archivos:** `carritoCompra.html`, `carrito-drawer.js`, `pago.html`  
**Problema:** El carrito se guarda en `localStorage['carrito']`. No hay TTL. No se limpia si la sesión expira. Un usuario puede agregar productos, cerrar sesión, y el carrito persiste para el siguiente usuario del mismo navegador.  
**Caso límite:** En dispositivo compartido (cibercafé), usuario B ve carrito de usuario A.

### RA-05 — Cambio de Rol CRM no Notifica al Portal Cliente
**Flujo:** `actualizar_crm_cliente.php` actualiza `usuarios.json` (tipo_cuenta) y `crm_clientes.json` (etiqueta), pero el portal cliente solo lee estos datos en `sesion_info.php`. La sesión PHP del cliente sigue con los datos viejos hasta que haga logout/login.  
**Violación:** SPEC-02 (sincronización completa), SPEC-00 (refreshPanelState post-CRM)

### RA-06 — `procesar_pago.php` sin Guard de Sesión Explícito
**Archivo:** `procesar_pago.php` L1-3  
**Problema:** Solo hace `if (session_status() === PHP_SESSION_NONE) session_start()`. No llama `requiereCliente()` ni ningún guard. Acepta pedidos de **usuarios no autenticados** (`uid = null`).  
**Impacto parcial:** Los pedidos de invitados son válidos por diseño, pero no valida que el cupón sea del mismo usuario.

### RA-07 — Devolución Aprobada no Restaura Stock
**Archivo:** `crud_devoluciones.php` L106-130  
**Problema:** Cuando una devolución es aprobada, se cancela el pedido y se registra un egreso, pero **no se restaura el stock de los productos devueltos**.  
**Violación:** SPEC-05 (integridad de inventario)

### RA-08 — Nómina sin Auditoría
**Archivo:** `crud_nomina.php`  
**Problema:** No llama `registrarAuditoria()`. Un pago de nómina no deja rastro en `auditoria.json`.  
**Violación:** SPEC-07, SPEC-08

### RA-09 — Endpoints Admin del Panel sin Filtro de Roles en UI
**Archivo:** `panel.html`  
**Problema:** La ocultación de botones es solo frontend (`btn.style.display = 'none'`). Si alguien inspecciona el DOM y ejecuta `mostrarRRHH()` desde consola mientras tiene rol `crm`, el frontend cargará el módulo. Los endpoints sí están protegidos por backend, pero la UI no refuerza el contexto.

---

## RIESGOS MEDIOS 🟡

### RM-01 — `listar_auditorio.php` Duplicado de `listar_auditoria.php`
**Problema:** Existe `listar_auditorio.php` (con "o" al final) que hace exactamente lo mismo que `listar_auditoria.php`. El panel usa `listar_auditoria.php`. Archivo muerto y confuso.

### RM-02 — Devolución sin Auditoría
**Archivo:** `crud_devoluciones.php` — ningún path llama `registrarAuditoria()`.  
**Violación:** SPEC-05, SPEC-08

### RM-03 — `validar_cupon.php` Solo Valida para `rol === 'cliente'`
**Problema:** El endpoint `validar_cupon.php` rechaza peticiones si `$rol !== 'cliente'`. Pero un usuario del panel también podría necesitar validar cupones (ej. venta presencial). Acoplamiento innecesario de rol.

### RM-04 — Ausencia de PromotionEngine Centralizado
**Problema:** La lógica de descuentos existe en `procesar_pago.php` y en `ofertas_logic.php` con implementaciones parcialmente distintas. No existe un `PromotionEngine` como indica SPEC-04.  
**Riesgo:** Cada nueva integración (CRM, ventas, etc.) replicará la lógica.

### RM-05 — `crud_cupones.php` sin campo `usos_restantes`
**Problema:** Los cupones solo tienen campo `usado: bool`. SPEC-01 requiere `usos_restantes` para cupones multi-uso.

### RM-06 — Wallet (Monedero) es Mock sin Backend
**Archivo:** `pago.html` — `currentWalletBalance = 5000` hardcoded.  
**Problema:** El monedero digital no tiene respaldo en ningún JSON ni endpoint. El balance es ficticio y local.

### RM-07 — Transferencia/Cajero (ATM) No Registra Pedido
**Archivo:** `pago.html` — método `atm` → "Imprimir Ficha" → ¿llama `_registrarPedido()`?  
**Problema:** El flujo de `_registrarPedido()` en el código tiene una referencia pero la función no está claramente definida en el fragmento visible. La transferencia bancaria puede no generar pedido en backend.

### RM-08 — `mostrarUsuariosClientes()` vs `mostrarUsuarios()`
**Archivo:** `panel.html`  
**Problema:** El botón llama `mostrarUsuariosClientes()` pero en el código existe `mostrarUsuarios()`. Posible función incorrecta o nombre mal referenciado.

---

## RIESGOS BAJOS 🟢

### RB-01 — Encoding UTF-8 Roto en panel.html
**Archivo:** `panel.html` — múltiples instancias de caracteres corruptos como `ÿ`, `Ã±`, `â€"`, `ðŸ"`.  
**Causa:** El archivo fue editado/guardado con encoding incorrecto (probablemente Latin-1 vs UTF-8).

### RB-02 — Fallback de Login a Contraseña en Texto Plano para Clientes
**Archivo:** `login.php` L29-33 — acepta contraseña plana si `password_verify()` falla.  
**Riesgo:** Clientes con contraseñas legacy nunca migran a hash hasta que cambian contraseña manualmente.

### RB-03 — IDs auto-increment no son atómicos
**Archivo:** `JsonHelper.php` L81-87 — `maxId + 1`. En race condition, dos creates simultáneos pueden generar el mismo ID.

### RB-04 — Sin Paginación en Listados
**Problema:** `listar_pedidos.php`, `listar_auditoria.php`, etc. retornan todos los registros sin límite. Con volumen real puede causar timeouts.

### RB-05 — `ofertas_logic.php` tiene IDs hardcodeados
**Archivo:** `ofertas_logic.php` L51 — `$idsOferta = [1, 2, 3]` — solo los primeros 3 productos siempre aparecen como ofertas.

---

## Comparación contra Specs

| SPEC | Requerimiento | Estado | Gap Detectado |
|---|---|---|---|
| SPEC-00 | `refreshPanelState()` centralizada | ❌ Parcial | Función duplicada, nombre incorrecto |
| SPEC-00 | Módulos habilitados en sesion_info | ❌ Falta | `sesion_info.php` no retorna permisos/módulos |
| SPEC-01 | Cupones con `usos_restantes` | ❌ Falta | Solo `usado: bool` |
| SPEC-01 | Solo propietario puede usar cupón | ✅ Parcial | Cupones sin `usuario_id` son universales |
| SPEC-02 | Cambio de rol → refresh panel | ❌ Falta | Sin evento/trigger post-CRM en portal cliente |
| SPEC-03 | Todos los métodos crean pedido + finanzas + auditoría | ❌ Parcial | PayPal/MP redirigen sin registrar; Wallet es mock |
| SPEC-04 | PromotionEngine centralizado | ❌ Falta | Lógica en 2 archivos distintos |
| SPEC-05 | Devolución restaura stock | ❌ Falta | Stock no se restaura |
| SPEC-05 | Devolución registra auditoría | ❌ Falta | Sin llamada a registrarAuditoria |
| SPEC-06 | Panel destruye estado en logout | ❌ Falta | Sin cleanup visual en logout |
| SPEC-07 | Nómina registra auditoría | ❌ Falta | crud_nomina.php sin auditoría |
| SPEC-08 | Auditoría con tipo (ingreso/egreso/etc.) | ❌ Falta | Sin campo tipo en registros actuales |
| SPEC-09 | Escritura JSON atómica con temporal | ❌ Falta | writeData() es directo |
| SPEC-10 | Sistema de eventos (pedido_creado, etc.) | ❌ No existe | Comunicación directa PHP sin eventos |
| SPEC-11 | Permisos nunca desde caché frontend | ❌ Parcial | Hay un IIFE que puede quedar cacheado |
| SPEC-12 | Validar sesión en backend siempre | ❌ Falta | crear/editar/eliminar_postre sin guard |
| ROLE-MATRIX | 7 roles definidos | ❌ Parcial | 'rrhh' y 'superusuario' no implementados |
| ROLE-MATRIX | RRHH separado de admin | ❌ Falta | RRHH solo accesible a 'admin' |

---

## Puntos de Riesgo Críticos — Mapa de Calor

```
RIESGO MÁS ALTO:
  procesar_pago.php    ██████████ Race condition + sin transacción
  JsonHelper.writeData ██████████ Sin LOCK_EX, sin archivo temporal
  crear_postre.php     █████████░ Sin autenticación
  editar_postre.php    █████████░ Sin autenticación
  eliminar_postre.php  █████████░ Sin autenticación
  editar_usuario.php   ████████░░ Sin autenticación
  eliminar_usuario.php ████████░░ Sin autenticación
  admons.json          ████████░░ Contraseñas en texto plano
  pago.html (PayPal)   ███████░░░ Pedido no registrado
  panel.html (Finanzas)██████░░░░ Datos incorrectos por diseño
```
