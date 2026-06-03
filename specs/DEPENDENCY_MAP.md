# DEPENDENCY_MAP.md — CasaPastel ERP/CRM

> Generado: 2026-06-02 | Análisis de dependencias reales

---

## 1. Árbol de Dependencias PHP (require_once)

```
JsonHelper.php                     ← Base de todos. NINGUNA dependencia.
verificar_sesion.php               ← Sin dependencias propias.

crud_auditoria.php
  └── JsonHelper.php

sesion_info.php
  └── JsonHelper.php

login.php
  └── JsonHelper.php

login_administrador.php
  └── JsonHelper.php

logout.php                         ← Sin dependencias (solo $_SESSION)

registro_usuario.php
  └── JsonHelper.php

procesar_pago.php
  ├── JsonHelper.php
  ├── (sesión via PHP_SESSION) ← NO require verificar_sesion.php!
  └── crud_auditoria.php

validar_cupon.php
  └── JsonHelper.php
  └── (sesión via PHP_SESSION manual)

ofertas_logic.php
  └── JsonHelper.php

crud_promociones.php
  ├── JsonHelper.php
  ├── verificar_sesion.php
  └── crud_auditoria.php

crud_cupones.php
  ├── JsonHelper.php
  ├── verificar_sesion.php
  └── crud_auditoria.php

crud_devoluciones.php
  ├── JsonHelper.php
  └── (sesión manual — NO requiere verificar_sesion.php!)

crud_nomina.php
  ├── JsonHelper.php
  └── verificar_sesion.php
  ← NO incluye crud_auditoria.php!

crud_empleados.php
  ├── JsonHelper.php
  └── verificar_sesion.php
  ← NO incluye crud_auditoria.php!

crud_admons.php
  ├── JsonHelper.php
  ├── verificar_sesion.php
  └── crud_auditoria.php

crud_insumos.php
  ├── JsonHelper.php
  ├── verificar_sesion.php
  └── crud_auditoria.php

crud_ordenes.php
  ├── JsonHelper.php
  ├── verificar_sesion.php
  └── crud_auditoria.php

crud_asistencia.php
  ├── JsonHelper.php
  └── verificar_sesion.php
  ← NO incluye crud_auditoria.php!

listar_crm.php
  ├── JsonHelper.php
  └── verificar_sesion.php

actualizar_crm_cliente.php
  ├── JsonHelper.php
  └── verificar_sesion.php
  ← NO incluye crud_auditoria.php!

listar_pedidos.php / listar_nomina.php / listar_movimientos.php
listar_asistencia.php / listar_auditoria.php
  ├── JsonHelper.php
  └── verificar_sesion.php

crear_postre.php / editar_postre.php / eliminar_postre.php
editar_usuario.php / eliminar_usuario.php
  └── JsonHelper.php
  ← SIN verificar_sesion.php! ENDPOINTS DESPROTEGIDOS.
```

---

## 2. Mapa de Acceso a Archivos JSON por Módulo

| Módulo | Lee | Escribe |
|---|---|---|
| `procesar_pago.php` | postresitos, usuarios, crm_clientes, promociones, cupones | postresitos (stock), movimientos, pedidos, finanzas, cupones, crm_clientes, usuarios, auditoria |
| `crud_devoluciones.php` | solicitudes_devolucion, pedidos | solicitudes_devolucion, pedidos, finanzas |
| `crud_nomina.php` | — | nomina, finanzas |
| `crud_promociones.php` | promociones | promociones, auditoria |
| `crud_cupones.php` | cupones, (verificar usuarios) | cupones, auditoria |
| `validar_cupon.php` | cupones | — |
| `listar_crm.php` | usuarios, pedidos, crm_clientes, cupones | — |
| `actualizar_crm_cliente.php` | crm_clientes | crm_clientes, usuarios |
| `crud_ordenes.php` | ordenes_compra, insumos, postresitos | ordenes_compra, insumos, postresitos, movimientos, finanzas, auditoria |
| `crud_empleados.php` | empleados | empleados |
| `crud_insumos.php` | insumos | insumos, movimientos, auditoria |
| `crud_admons.php` | admons | admons, auditoria |
| `sesion_info.php` | usuarios, crm_clientes | — |
| `ofertas_logic.php` | postresitos, promociones | — |

---

## 3. Grafo de Dependencias entre Módulos de Negocio

```
                    ┌─────────────┐
                    │  sesion_info│ ← Punto central de sesión
                    └──────┬──────┘
                           │ consulta
           ┌───────────────┼──────────────────┐
           ▼               ▼                  ▼
     ┌──────────┐    ┌──────────┐      ┌──────────┐
     │ usuarios │    │crm_clien │      │  panel   │
     └──────────┘    └──────────┘      └──────────┘
           ▲               ▲
           │ actualiza     │ lee/escribe
           └──────┬────────┘
                  │
         ┌────────▼────────┐
         │ procesar_pago   │ ← Módulo más acoplado (toca 8 colecciones)
         └─────────────────┘
              │     │     │
              ▼     ▼     ▼
         pedidos finanzas auditoria
              │
              ▼
         cupones (marca usado)
              │
              ▼
         promociones (descuentos calculados inline)
```

---

## 4. Dependencias Frontend → Backend

### pago.html
```
pago.html
  ├── sesion_info.php        (prefill formulario, mostrar cupón)
  ├── validar_cupon.php      (validación de cupón)
  └── procesar_pago.php      (confirmación de compra)
```

### panel.html
```
panel.html
  ├── sesion_info.php           (aplicarRolPanel + IIFE duplicada)
  ├── listar_postres.php        (mostrarProductos, mostrarInventario, Métricas)
  ├── listar_postre_individual.php (abrirModalEditar)
  ├── crear_postre.php          (agregar postre)
  ├── editar_postre.php         (editar postre, ajustar stock)
  ├── eliminar_postre.php       (eliminar postre)
  ├── listar_pedidos.php        (mostrarSeguimiento, mostrarMetricas, mostrarFinanzas)
  ├── actualizar_pedido.php     (cambiar estado pedido)
  ├── listar_usuarios.php       (mostrarUsuarios)
  ├── listar_usuario_individual.php (editar usuario)
  ├── editar_usuario.php        (guardar edición usuario)
  ├── eliminar_usuario.php      (eliminar usuario)
  ├── listar_crm.php            (mostrarCRM)
  ├── actualizar_crm_cliente.php (editar cliente CRM)
  ├── crud_promociones.php      (CRUD promociones en panel)
  ├── crud_cupones.php          (CRUD cupones en panel)
  ├── crud_devoluciones.php     (GET/PUT devoluciones)
  ├── crud_empleados.php        (RRHH)
  ├── crud_nomina.php           (nómina)
  ├── crud_asistencia.php       (asistencia)
  ├── crud_insumos.php          (insumos SCM)
  ├── crud_proveedores.php      (proveedores)
  ├── crud_ordenes.php          (órdenes de compra)
  ├── crud_admons.php           (usuarios del panel)
  ├── listar_auditoria.php      (mostrarAuditoria)
  ├── listar_movimientos.php    (movimientos inventario)
  └── listar_nomina.php         (nómina RRHH)
```

### carritoCompra.html + carrito-drawer.js
```
carritoCompra.html / carrito-drawer.js
  ├── listar_postres.php        (catálogo)
  ├── sesion_info.php           (estado sesión)
  └── localStorage['carrito']  (persistencia local carrito)
```

---

## 5. Flujos de Datos Cross-Módulo

### Flujo A: Checkout → CRM → Sesión
```
pago.html
  → procesar_pago.php
      ├── Lee: postresitos (stock check)
      ├── Lee: usuarios (tipo_cuenta, etiqueta CRM)
      ├── Lee: crm_clientes (etiqueta)
      ├── Lee: promociones (descuentos)
      ├── Lee: cupones (validación)
      ├── Escribe: postresitos (stock -=cantidad)
      ├── Escribe: movimientos (salida)
      ├── Escribe: pedidos (nuevo pedido)
      ├── Escribe: finanzas (ingreso)
      ├── Escribe: cupones (marca usado)
      ├── Escribe: crm_clientes (actualiza LTV + etiqueta)
      ├── Escribe: usuarios (sincroniza tipo_cuenta)
      └── Escribe: auditoria (log)
```

### Flujo B: Cambio de Etiqueta CRM → Sesión
```
panel.html (mostrarCRM)
  → actualizar_crm_cliente.php
      ├── Escribe: crm_clientes (etiqueta, nombre, telefono)
      └── Escribe: usuarios (tipo_cuenta mapeado de etiqueta)
      ← NO notifica al frontend del portal cliente
      ← sesion_info.php NO se recarga automáticamente
```

### Flujo C: Nómina → Finanzas
```
panel.html (mostrarRRHH)
  → crud_nomina.php
      ├── Escribe: nomina (registro)
      └── Escribe: finanzas (egreso)
      ← NO escribe auditoria (falta en SPEC-07)
```

### Flujo D: Devolución → Finanzas → Pedido
```
panel.html / seguimiento.html
  → crud_devoluciones.php (POST — cliente crea)
      └── Escribe: solicitudes_devolucion

panel.html (mostrarDevoluciones, rol CRM)
  → crud_devoluciones.php (PUT — admin aprueba)
      ├── Escribe: solicitudes_devolucion (estado)
      ├── Escribe: pedidos (estado → 'Cancelado')
      └── Escribe: finanzas (egreso reembolso)
      ← NO escribe auditoria (falta integración)
      ← NO restaura stock (falta implementación)
```

---

## 6. Mapa de Acoplamiento de Módulos

```
ALTAMENTE ACOPLADO (⚠️ riesgo alto de regresión):
  procesar_pago.php    → 8 colecciones JSON, sin transacciones
  panel.html           → 25+ endpoints distintos
  listar_crm.php       → 4 colecciones (JOIN manual en PHP)

MEDIANAMENTE ACOPLADO:
  crud_ordenes.php     → 5 colecciones (ordenes, insumos, postresitos, movimientos, finanzas)
  crud_devoluciones.php → 3 colecciones (solicitudes, pedidos, finanzas)
  sesion_info.php      → 2 colecciones (usuarios, crm_clientes)

MÍNIMAMENTE ACOPLADO (seguro para modificar):
  listar_pedidos.php   → 1 colección
  listar_nomina.php    → 1 colección
  listar_movimientos.php → 1 colección
  listar_asistencia.php → 1 colección
  crud_empleados.php   → 1 colección
  crud_asistencia.php  → 1 colección
  validar_cupon.php    → 1 colección (solo lectura)
```

---

## 7. Dependencias Circulares Detectadas

| Origen | Destino | Naturaleza |
|---|---|---|
| `procesar_pago.php` | `crm_clientes` + `usuarios` | Escribe en ambos, debería hacerlo `actualizar_crm_cliente.php` |
| `actualizar_crm_cliente.php` | `usuarios` | Duplica lógica de sync de `procesar_pago.php` |
| `listar_crm.php` | `usuarios` + `pedidos` + `crm_clientes` | Recalcula etiqueta diferente a como la calcula `procesar_pago.php` |

> [!WARNING]
> La lógica de clasificación de clientes (nuevo/frecuente/vip) está implementada en **3 lugares diferentes** con criterios distintos:
> - `procesar_pago.php`: `≥5 compras = vip, ≥3 = frecuente`
> - `listar_crm.php`: `≥3 pedidos = frecuente, LTV>500 = vip`
> - `actualizar_crm_cliente.php`: solo sincroniza lo que viene del frontend
