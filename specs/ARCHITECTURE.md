# ARCHITECTURE.md — CasaPastel ERP/CRM

> Generado: 2026-06-02 | Análisis estático sin modificación de código

---

## 1. Stack Tecnológico Real

| Capa | Tecnología | Observación |
|---|---|---|
| Backend | PHP procedural (sin framework, sin OOP) | `require_once` manual en cada archivo |
| Frontend | HTML + Vanilla CSS + Vanilla JS (ES2020+) | Sin bundler, sin framework |
| Persistencia | Archivos JSON en `/data/` | `JsonHelper.php` como ORM simulado |
| Sesión | `$_SESSION` PHP nativo | Timeout 2h configurado en `verificar_sesion.php` |
| Autenticación admins | Contraseña plana en JSON | `admons.json` — **crítico: sin hash** |
| Autenticación clientes | `password_verify()` con fallback a texto plano | `login.php` |
| Gráficas | Chart.js CDN | En panel e-commerce (`metricas.html`) |
| Fuentes | Google Fonts CDN | Playfair Display + Open Sans |

---

## 2. Capas Conceptuales (según AGENT_PROMPT.md)

```
┌──────────────────────────────────────────────────────────────┐
│  CAPA 1 — Estado y Autenticación                             │
│  sesion_info.php · verificar_sesion.php · login.php          │
│  login_administrador.php · logout.php                        │
└────────────────────────┬─────────────────────────────────────┘
                         │
┌────────────────────────▼─────────────────────────────────────┐
│  CAPA 2 — Motores de Negocio                                 │
│  procesar_pago.php · ofertas_logic.php · validar_cupon.php   │
│  crud_promociones.php · crud_devoluciones.php                │
│  crud_nomina.php · crud_empleados.php · crud_ordenes.php     │
│  crud_admons.php · crud_cupones.php · crud_insumos.php       │
│  crud_proveedores.php · crud_recetas.php · crud_asistencia.php│
│  actualizar_crm_cliente.php · listar_crm.php                 │
└────────────────────────┬─────────────────────────────────────┘
                         │
┌────────────────────────▼─────────────────────────────────────┐
│  CAPA 3 — Persistencia y Auditoría                           │
│  JsonHelper.php · crud_auditoria.php                         │
│  /data/*.json (20 archivos)                                  │
└──────────────────────────────────────────────────────────────┘
```

---

## 3. Portales (Dual-Portal Design)

### Portal Público — E-commerce (`/`)
| Archivo | Función |
|---|---|
| `index.html` | Landing page |
| `menu.html` | Catálogo de postres |
| `catalago.html` | Listado alternativo |
| `detalle_postre.php` | Página de producto individual |
| `carritoCompra.html` | Carrito con drawer |
| `pago.html` | Checkout con 6 métodos de pago |
| `seguimiento.html` | Seguimiento de pedidos (cliente) |
| `registroUsuario.html` | Registro de clientes |
| `login.html` | Login de clientes |
| `PantallaPrincipal.html` | Home post-login del cliente |
| `ofertas.php` | Página de ofertas (servidor-side render) |
| `buscar.html` | Búsqueda de productos |
| `conocenos.html` / `contacto.html` | Páginas informativas |

### Portal Administrativo — ERP/CRM (`panel.html`)
El panel es un **SPA monolítico** de **2,440 líneas** con módulos en línea:

| Módulo UI | Función JS | Roles Permitidos |
|---|---|---|
| Productos | `mostrarProductos()` | admin, empleado, crm, scm, gerente |
| Agregar Postre | `mostrarFormularioAgregarPostre()` | admin |
| Usuarios | `mostrarUsuariosClientes()` | admin, gerente |
| Métricas | `mostrarMetricas()` | admin, gerente, empleado |
| Pedidos | `mostrarSeguimiento()` | admin, empleado, gerente, crm, scm |
| Inventario SCM | `mostrarInventarioSCM()` | admin, scm, gerente |
| Producción | `mostrarProduccion()` | admin, scm |
| CRM | `mostrarCRM()` | admin, crm |
| Compras | `mostrarCompras()` | admin |
| Finanzas | `mostrarFinanzas()` | admin, gerente |
| RR.HH. | `mostrarRRHH()` | admin |
| Usuarios Panel | `mostrarAdmins()` | admin |
| Auditoría | `mostrarAuditoria()` | admin, gerente, crm |

---

## 4. Módulos Backend — Inventario de Endpoints

### Autenticación y Sesión
| Archivo | Método | Guard | Descripción |
|---|---|---|---|
| `sesion_info.php` | GET | Ninguno | Info de sesión actual (rol, nombre, uid) |
| `verificar_sesion.php` | include | — | Funciones guard: `requiereAdmin()`, `requierePersonal()`, `requiereCRM()`, `requiereSCM()`, `requiereCliente()` |
| `login.php` | POST | — | Auth cliente → Session['rol']='cliente' |
| `login_administrador.php` | POST | — | Auth admin → Session['rol']=rol del JSON |
| `logout.php` | GET/POST | — | Destruye sesión, retorna `usuario_id` |
| `registro_usuario.php` | POST | — | Crea usuario cliente |

### Productos / Inventario
| Archivo | Métodos | Guard |
|---|---|---|
| `listar_postres.php` | GET | requierePersonal |
| `listar_postre_individual.php` | GET | — (público) |
| `crear_postre.php` | POST | — (sin guard!) |
| `editar_postre.php` | POST | — (sin guard!) |
| `eliminar_postre.php` | POST | — (sin guard!) |
| `producir_postre.php` | POST | requierePersonal |
| `crud_insumos.php` | GET/POST/PUT/DELETE | requiereAdmin |

### Pedidos y Checkout
| Archivo | Métodos | Guard |
|---|---|---|
| `procesar_pago.php` | POST | Sin guard (acepta invitados) |
| `consultar_pedido.php` | GET | — |
| `actualizar_pedido.php` | POST | — (sin guard) |
| `listar_pedidos.php` | GET | requierePersonal |
| `listar_mis_pedidos.php` | GET | requiereCliente |

### Promociones y Cupones
| Archivo | Métodos | Guard |
|---|---|---|
| `crud_promociones.php` | GET/POST/PUT/DELETE | requiereAdmin |
| `crud_cupones.php` | GET/POST/DELETE | requiereCRM (POST/DELETE), requierePersonal (GET admin), requiereCliente (GET cliente) |
| `validar_cupon.php` | POST | requiereCliente (rol='cliente') |
| `ofertas_logic.php` | include | — (lógica, no endpoint) |

### CRM
| Archivo | Métodos | Guard |
|---|---|---|
| `listar_crm.php` | GET | requierePersonal |
| `actualizar_crm_cliente.php` | POST | requiereCRM |

### Finanzas
| Archivo | Métodos | Guard |
|---|---|---|
| `listar_movimientos.php` | GET | requierePersonal |
| (inline en panel) | — | datos de `finanzas.json` via fetch |

### RRHH / Nómina
| Archivo | Métodos | Guard |
|---|---|---|
| `crud_empleados.php` | GET/POST/PUT/DELETE | requiereAdmin |
| `crud_nomina.php` | POST | requiereAdmin |
| `listar_nomina.php` | GET | requiereAdmin |
| `crud_asistencia.php` | POST | requierePersonal |
| `listar_asistencia.php` | GET | requierePersonal |

### Auditoría
| Archivo | Métodos | Guard |
|---|---|---|
| `crud_auditoria.php` | include (función) | — |
| `listar_auditoria.php` | GET | requierePersonal |
| `listar_auditorio.php` | GET | requierePersonal (alias duplicado!) |

### Usuarios Panel
| Archivo | Métodos | Guard |
|---|---|---|
| `crud_admons.php` | GET/POST/PUT/DELETE | requiereAdmin |
| `editar_usuario.php` | POST | — (sin guard!) |
| `eliminar_usuario.php` | POST | — (sin guard!) |
| `listar_usuarios.php` | GET | requierePersonal |
| `listar_usuario_individual.php` | GET | — (sin guard!) |

---

## 5. Archivos de Persistencia JSON (`/data/`)

| Archivo | Descripción | Estado |
|---|---|---|
| `usuarios.json` | Clientes registrados | Activo |
| `admons.json` | Usuarios del panel | **Contraseñas en texto plano** |
| `pedidos.json` | Historial de pedidos | Activo |
| `postresitos.json` | Catálogo de productos | Activo |
| `promociones.json` | Promociones activas | Activo |
| `cupones.json` | Cupones de descuento | Vacío (`[]`→ `2 bytes`) |
| `finanzas.json` | Movimientos financieros | Activo |
| `movimientos.json` | Movimientos de stock | Activo |
| `auditoria.json` | Log de auditoría | Activo (13KB) |
| `crm_clientes.json` | Datos CRM | Activo |
| `empleados.json` | Empleados | Activo |
| `nomina.json` | Registros de nómina | Activo |
| `asistencia.json` | Registros de asistencia | Vacío |
| `insumos.json` | Insumos / materia prima | Activo |
| `ordenes_compra.json` | Órdenes de compra | Activo |
| `proveedores.json` | Proveedores | Activo |
| `recetas.json` | Recetas de postres | Activo |
| `solicitudes_devolucion.json` | Solicitudes de devolución | Vacío (`4 bytes`) |
| `evaluaciones.json` | Evaluaciones de empleados | Vacío |
| `cupones.json` | Ver arriba | Vacío |

---

## 6. Estructura de JsonHelper.php

```
JsonHelper
├── readData(filename)      → Lee y decodifica JSON (sin validación de schema)
├── writeData(filename, data) → Escribe directamente (SIN archivo temporal)
├── findById(filename, id)  → Busca por campo 'id'
├── findWhere(filename, criteria) → Filtro simple
├── create(filename, data)  → Genera ID auto-increment (max+1), escribe
├── update(filename, id, data) → Merge parcial, escribe
├── delete(filename, id)    → Filtra y escribe
├── getAll(filename)        → Retorna todo el array
└── authenticateUser(...)   → Comparación de string plano (sin hash)
```

> [!CAUTION]
> `writeData()` hace `file_put_contents()` directo **sin archivo temporal**, violando SPEC-09.
> Múltiples solicitudes simultáneas pueden corromper archivos JSON.

---

## 7. Control de Acceso

```
Roles definidos en verificar_sesion.php:
├── 'admin'   → requiereAdmin() — acceso total al panel
├── 'empleado' → requierePersonal() — pedidos, productos lectura
├── 'crm'     → requiereCRM() + requierePersonal() — CRM, cupones, promociones
├── 'scm'     → requiereSCM() — inventario, insumos
├── 'gerente' → requierePersonal() — métricas, finanzas (lectura)
└── 'cliente' → requiereCliente() — portal e-commerce

AUSENTE en ROLE-MATRIX.md pero presente en código:
├── 'scm' (Supply Chain Manager) — no documentado en specs
└── 'gerente' — no documentado en specs
```

---

## 8. Función refreshPanelState() — Estado Actual

**SPEC-00 y SPEC-06 requieren `refreshPanelState()`.**

En el código real existe `aplicarRolPanel()` en `panel.html` (línea 742) que:
- ✅ Consulta `sesion_info.php`
- ✅ Actualiza badge de perfil
- ✅ Filtra botones por rol
- ❌ NO se ejecuta tras cambio de CRM
- ❌ NO se ejecuta tras logout (panel queda con estado anterior)
- ❌ NO tiene nombre `refreshPanelState()` (discrepancia con spec)
- ❌ Existe un `aplicarRol()` duplicado (IIFE) en línea 457 que hace lo mismo

**Hay DOS implementaciones paralelas de la misma función** — una IIFE anónima y `aplicarRolPanel()` — sin coordinación.
