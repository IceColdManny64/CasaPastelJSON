# SESSION_FLOW.md — CasaPastel ERP/CRM

> Generado: 2026-06-02 | Análisis de flujo de sesión

---

## 1. Dominios de Sesión

El sistema tiene **un único dominio de sesión PHP** (`$_SESSION`) compartido, pero dos portales con lógica de sesión diferente:

```
┌─────────────────────────────────────────────────────────────┐
│                    $_SESSION (PHP)                          │
│  rol | nombre | usuario | usuario_id | admin_id | última_act│
└────────────────┬────────────────────────┬───────────────────┘
                 │                        │
    ┌────────────▼─────────┐   ┌─────────▼──────────────┐
    │  Portal Cliente       │   │  Portal Admin (Panel)   │
    │  login.php           │   │  login_administrador.php │
    │  rol = 'cliente'     │   │  rol = admons[x].rol     │
    │  usuario_id = int    │   │  admin_id = admons[x].id │
    └──────────────────────┘   └────────────────────────-─┘
```

> [!WARNING]
> Si un admin visita `login.php` y hace login como cliente, su sesión de admin se **sobreescribe** con `rol='cliente'`. No hay protección contra sesión mixta.

---

## 2. Flujo de Login — Portal Cliente

```
[login.html]
    │
    ▼ POST correo + contrasena
[login.php]
    │
    ├─ Validar campos no vacíos
    │       ↓ vacíos → redirect login.html?error=1
    │
    ├─ JsonHelper::getAll('usuarios')
    │
    ├─ Buscar por correo:
    │   ├─ password_verify($input, $hash) → ✅
    │   └─ $pass === $input (fallback texto plano) → ✅ (RIESGO: RB-02)
    │
    ├─ Si encontrado:
    │   $_SESSION['rol']        = 'cliente'   ← hardcoded
    │   $_SESSION['usuario']    = $correo
    │   $_SESSION['usuario_id'] = $user['id']
    │   $_SESSION['nombre']     = $user['nombre']
    │
    └─ redirect → PantallaPrincipal.html

ESTADO POST-LOGIN (cliente):
  activo: true
  rol: 'cliente'
  usuario_id: [int]
  nombre: [string]
  correo: [en JSON, NO en sesión]
  tipo_cuenta: [en JSON, NO en sesión]
  etiqueta_crm: [en JSON crm_clientes, NO en sesión]
```

> [!IMPORTANT]
> El `correo`, `tipo_cuenta` y `etiqueta_crm` **no se almacenan en sesión**. `sesion_info.php` los lee de JSON en cada petición. Esto es correcto por diseño (evita caché), pero implica una lectura extra de disco por cada call a `sesion_info.php`.

---

## 3. Flujo de Login — Portal Admin (Panel)

```
[login_administrador.html]
    │
    ▼ POST usuario + contrasena
[login_administrador.php]
    │
    ├─ Validar campos no vacíos
    │
    ├─ JsonHelper::authenticateUser('admons', 'usuario', 'passw', $u, $p)
    │   └─ Comparación: $item[$userField] === $user && $item[$passField] === $pass
    │      ← COMPARACIÓN DE STRINGS PLANOS (CRÍTICO: RC-04)
    │
    ├─ Si encontrado:
    │   $_SESSION['rol']        = $admin['rol'] ?? 'empleado'
    │   $_SESSION['usuario']    = $usuario
    │   $_SESSION['nombre']     = $admin['nombre']
    │   $_SESSION['usuario_id'] = $admin['id']
    │   $_SESSION['admin_id']   = $admin['id']
    │
    └─ redirect → panel.html?t=[timestamp]  ← cache-busting

ROLES POSIBLES POST-LOGIN:
  admin | empleado | crm | scm | gerente
  (NO: 'rrhh', NO: 'superusuario' — no existen en admons.json)
```

---

## 4. Flujo de sesion_info.php

```
[cualquier página]
    │
    ▼ fetch('sesion_info.php')
[sesion_info.php]
    │
    ├─ session_start()
    ├─ Headers: Cache-Control: no-store, no-cache
    │
    ├─ Construir $out base:
    │   rol:         $_SESSION['rol'] ?? ''
    │   nombre:      $_SESSION['nombre'] ?? ''
    │   activo:      !empty($_SESSION['rol'])
    │   usuario_id:  $_SESSION['usuario_id'] ?? null
    │   correo:      ''
    │   telefono:    ''
    │   direccion:   ''
    │   tipo_cuenta: ''
    │   etiqueta_crm:'cliente'
    │
    ├─ Si usuario_id presente Y rol === 'cliente':
    │   ├─ findById('usuarios', usuario_id) → enriquece correo, tel, dir, tipo_cuenta, nombre
    │   └─ getAll('crm_clientes') → busca etiqueta CRM por usuario_id
    │
    └─ echo json_encode($out)

GAPS DE sesion_info.php vs SPEC-00:
  ❌ No retorna 'permisos' (array de permisos específicos)
  ❌ No retorna 'modulos_habilitados'
  ❌ Solo enriquece datos para rol='cliente', los admins solo tienen rol+nombre
```

---

## 5. Flujo de Logout

```
[cualquier página — botón Salir]
    │
    ▼ fetch('logout.php') o link directo
[logout.php]
    │
    ├─ $uid = $_SESSION['usuario_id'] ?? null
    ├─ $_SESSION = []
    ├─ setcookie(session_name(), '', time()-42000, ...)
    ├─ session_destroy()
    │
    └─ echo json_encode(['ok' => true, 'usuario_id' => $uid])

PROBLEMA DETECTADO (RA-03):
  El link "Salir" en panel.html (línea 11) es:
  <a href="index.html">Salir</a>
  → No llama logout.php
  → No destruye sesión PHP
  → No limpia carrito ni estado visual
  → La sesión PHP expira por timeout (2h) o por nueva visita
```

> [!CAUTION]
> El botón "Salir" del panel NO hace logout real. Solo navega a `index.html` mientras la sesión PHP permanece activa. Un usuario que hace clic en Salir y vuelve a `panel.html` verá el panel completo.

---

## 6. Máquina de Estados del Panel (aplicarRolPanel)

```
Estado Inicial: panel.html carga
         │
         ▼
[DOMContentLoaded] → aplicarRolPanel()
         │
         ▼ fetch('sesion_info.php')
         │
    ┌────┴──────────────────┐
    │ Error de red          │ → redirect login_administrador.html
    └───────────────────────┘
         │ Respuesta OK
         ▼
    ┌────┴──────────────────┐
    │ !sesion.activo        │ → redirect login_administrador.html
    │ !sesion.rol           │
    └───────────────────────┘
         │ Sesión válida
         ▼
    Actualizar badge (nombre + rol)
         │
         ▼
    Filtrar botones por data-roles
         │
         ▼
    Restaurar mensaje inicial

TAMBIÉN:
[On load — IIFE] → aplicarRol() (PARALELA, DUPLICADA)
    → fetch('sesion_info.php') (2da petición)
    → filtra botones de nuevo
```

---

## 7. Timeout de Sesión

**Archivo:** `verificar_sesion.php` L4-10
```php
if (!empty($_SESSION['ultima_actividad']) && (time() - $_SESSION['ultima_actividad'] > 7200)) {
    session_unset();
    session_destroy();
    session_start();
}
$_SESSION['ultima_actividad'] = time();
```

- Timeout: **2 horas de inactividad**
- El timeout solo aplica en endpoints que hacen `require verificar_sesion.php`
- **NO aplica** en `sesion_info.php` (que tiene su propio `session_start()` independiente)
- **NO aplica** en `procesar_pago.php` (usa `session_start()` directo)
- El panel podría tener sesión "activa" según `sesion_info.php` pero "expirada" según un endpoint protegido

---

## 8. Inconsistencias de Sesión Detectadas

| Escenario | Comportamiento Real | Comportamiento Esperado (Spec) |
|---|---|---|
| Login cliente → panel directo | Puede acceder a panel si escribe URL | Debería redirigir a portal cliente |
| Admin → logout (link Salir) | Sesión PHP persiste 2h | Sesión destruida inmediatamente |
| CRM cambia etiqueta cliente | Sesión del cliente no se actualiza | Sesión se recalcula (SPEC-02) |
| Timeout backend + request frontend | Frontend no sabe que expiró | Panel debería detectar y redirigir |
| Doble tab mismo usuario | Sesiones inconsistentes posibles | Sesión unificada (no aplica a JSON) |
| Rol 'cliente' intenta acceder a crud_empleados.php | 401 correcto | ✅ Funciona |
| Sin sesión, acceder a crear_postre.php | ✅ Éxito (BUG CRÍTICO) | 403 requerido |

---

## 9. Estado de cumplimiento — SPEC-06

| Req. SPEC-06 | Implementado | Notas |
|---|---|---|
| Panel se reconstruye tras login | ✅ Parcial | `aplicarRolPanel()` en DOMContentLoaded |
| Panel destruye estado tras logout | ❌ No | El "Salir" es solo un link href |
| Recarga permisos dinámicamente | ❌ No | Sin mecanismo de polling o evento |
| Muestra usuario actual | ✅ Sí | Badge en header |
| Muestra rol actual | ✅ Sí | Badge en header |
| Muestra estado de sesión | ❌ No | Sin indicador "activo/expirado" |
