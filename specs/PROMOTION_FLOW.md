# PROMOTION_FLOW.md — CasaPastel ERP/CRM

> Generado: 2026-06-02 | Análisis del motor de promociones y cupones

---

## 1. Estado Actual: Sin PromotionEngine Centralizado

**SPEC-04 requiere** un `PromotionEngine` centralizado que controle:
- Promociones
- Cupones
- Descuentos automáticos
- Expiraciones
- Ownership

**Estado real:** La lógica de promociones está fragmentada en:

| Archivo | Uso | Contexto |
|---|---|---|
| `procesar_pago.php` | Calcula descuentos en checkout | Backend — servidor |
| `ofertas_logic.php` | Calcula precio con descuento para mostrar en UI | Backend — include en ofertas.php |
| `pago.html` | Recalcula descuento de cupón localmente | Frontend — JavaScript |
| `crud_promociones.php` | CRUD de promociones | Backend — panel |
| `crud_cupones.php` | CRUD de cupones | Backend — panel |
| `validar_cupon.php` | Valida cupón individual | Backend — checkout |

---

## 2. Estructura de Datos

### 2.1 Promoción (promociones.json)

```json
{
    "id": int,
    "nombre": "string",
    "tipo": "descuento_porcentaje" | "precio_especial" | "2x1",
    "valor": float,          // % o precio fijo según tipo
    "aplica_a": "todos" | "categoria" | "producto",
    "referencia": "string",  // ID o nombre de categoría/producto
    "clientes": "todos" | "frecuente" | "vip",
    "activa": bool,
    "fecha_inicio": "YYYY-MM-DD" | null,
    "fecha_fin": "YYYY-MM-DD" | null
}
```

### 2.2 Cupón (cupones.json)

```json
{
    "id": int,
    "codigo": "string",        // UPPERCASE
    "usuario_id": int | null,  // null = cupón universal (⚠️)
    "tipo": "descuento_porcentaje" | "monto_fijo",
    "valor": float,
    "descripcion": "string",
    "fecha_expiracion": "YYYY-MM-DD" | null,
    "usado": bool,
    "creado_en": "YYYY-MM-DD HH:MM:SS"
    // ❌ FALTA: usos_restantes (SPEC-01)
    // ❌ FALTA: estado (SPEC-01)
    // ❌ FALTA: folio_pedido (se agrega solo al marcar usado)
}
```

---

## 3. Flujo de Promociones Automáticas

### 3.1 En Checkout (procesar_pago.php)

```
ENTRADA: subtotal, items[], usuario_id, etiqueta_crm

PASO 1: Obtener etiqueta CRM del usuario
    ├─ Si uid presente: leer usuarios.json → tipo_cuenta
    ├─ Si uid presente: buscar en crm_clientes por usuario_id → etiqueta
    └─ Si sin sesión: etiqueta = 'nuevo'

PASO 2: Obtener promociones activas
    └─ getAll('promociones')

PASO 3: Por cada promoción:
    ├─ Filtro de actividad: activa === true
    ├─ Filtro de fechas: fecha_inicio <= hoy <= fecha_fin (null = sin límite)
    ├─ Filtro de segmento:
    │   ├─ 'todos' → aplica a todos
    │   ├─ 'vip' → solo si etiqueta === 'vip'
    │   └─ 'frecuente' → si etiqueta in ['frecuente', 'vip']
    │
    ├─ Calcular base según aplica_a:
    │   ├─ 'todos' → base = subtotal completo
    │   ├─ 'categoria' → base = Σ(items de esa categoría)
    │   │   └─ Requiere findById('postresitos') por cada item → N queries
    │   └─ 'producto' → base = Σ(items que coincidan por ID o título)
    │
    └─ Calcular descuento:
        ├─ 'descuento_porcentaje' → base × (valor/100)
        ├─ 'precio_especial' → max(0, base - valor)
        └─ '2x1' → base × 0.5

RESULTADO: descuentoPromo = min(Σdescuentos, subtotal)
```

### 3.2 En Portal Público (ofertas_logic.php)

```
PROPÓSITO: Mostrar precio con descuento en página de ofertas

DIFERENCIAS vs procesar_pago.php:
├─ Solo considera IDs hardcoded [1,2,3] (⚠️ RB-05)
├─ Solo toma el MAYOR descuento porcentual (not acumulativo)
├─ Si no hay promo: aplica 20% por defecto (descuento fantasma)
├─ NO verifica segmento de cliente (aplica a todos)
└─ Calcula precio visual, no precio de checkout

INCONSISTENCIA: Un usuario puede ver en oferta un precio X,
pero al llegar al checkout pagar un precio diferente si:
- No califica para el segmento de la promo
- La promo expiró entre el momento de ver y el checkout
- La promo aplica a categoría/producto diferente
```

---

## 4. Flujo de Cupones

### 4.1 Ciclo de Vida del Cupón

```
[Creación — CRM Panel]
    crud_cupones.php (POST, requiereCRM)
        ├─ requiere: codigo (único) + usuario_id
        ├─ requiere: tipo + valor
        ├─ opcional: descripcion, fecha_expiracion
        └─ Estado inicial: usado=false

        │
        ▼

[Consulta — Cliente en checkout]
    crud_cupones.php (GET, cliente ve sus cupones)
        └─ Filtra: usuario_id === uid, vigente, no usado

        │
        ▼

[Validación — Pre-checkout]
    validar_cupon.php (POST)
        ├─ Verifica: !usado, ownership, no expirado
        └─ Retorna: tipo, valor (NO marca como usado)

        │
        ▼

[Aplicación — En checkout]
    procesar_pago.php
        ├─ Vuelve a verificar: ownership, vigencia, !usado
        └─ Aplica descuento

        │
        ▼

[Marcado — Post-pago]
    procesar_pago.php
        └─ update('cupones', id, {usado:true, folio_pedido: folio})
```

### 4.2 Problemas en el Ciclo de Vida

| Paso | Problema |
|---|---|
| Creación | `usuario_id` puede ser null → cupón universal sin ownership |
| Validación | No marca como "en_proceso" → window de race condition |
| Aplicación | Revalida condiciones, pero sin lock → RC-05 |
| Marcado | Si procesar_pago falla después del marcado, cupón quemado sin pedido |

---

## 5. Clasificación de Clientes — Tres Implementaciones Incompatibles

### Implementación 1: procesar_pago.php (Al comprar)
```php
$nuevasCompras = $existente['total_compras'] + 1;
$nuevaEtiqueta = $nuevasCompras >= 5 ? 'vip' 
               : ($nuevasCompras >= 3 ? 'frecuente' 
               : 'nuevo');
```

### Implementación 2: listar_crm.php (Vista CRM)
```php
$etiq = $crmc['etiqueta'] ?? ($n >= 3 ? 'frecuente' : ($total > 500 ? 'vip' : 'nuevo'));
```

### Implementación 3: actualizar_crm_cliente.php (Manual)
```php
// Solo sincroniza lo que viene del frontend — sin recálculo automático
```

**Resultado:** Un mismo cliente puede tener:
- `etiqueta = 'frecuente'` en `crm_clientes.json` (CRM view)
- `tipo_cuenta = 'vip'` en `usuarios.json` (procesar_pago sync)
- `etiqueta_crm = 'nuevo'` en `sesion_info.php` (si el crm_clientes no existe aún)

---

## 6. Segmentación de Promociones — Matriz de Aplicación

```
┌──────────────┬────────────────┬──────────────────────────────────┐
│ aplica_a     │ clientes       │ Comportamiento                   │
├──────────────┼────────────────┼──────────────────────────────────┤
│ 'todos'      │ 'todos'        │ Aplica a todos sin excepción     │
│ 'todos'      │ 'vip'          │ Solo usuarios con etiqueta vip   │
│ 'todos'      │ 'frecuente'    │ frecuente + vip                  │
│ 'categoria'  │ 'todos'        │ Items de esa categoría           │
│ 'categoria'  │ 'vip'          │ Items de categoría, solo vip     │
│ 'producto'   │ 'todos'        │ Item específico (ID o título)    │
│ 'producto'   │ cualquier      │ Item específico + segmento       │
└──────────────┴────────────────┴──────────────────────────────────┘
```

**Gap detectado:** El segmento 'frecuente' aplica a `etiqueta in ['frecuente','vip']` pero el segmento 'vip' solo aplica a `etiqueta === 'vip'`. Esto es correcto conceptualmente pero la lógica de `ofertas_logic.php` **no verifica el segmento del usuario** — muestra el descuento a todos.

---

## 7. Acumulación de Descuentos

**En procesar_pago.php:** Los descuentos de múltiples promociones se **acumulan** (suma de todos los descuentos aplicables), con cap en `subtotal`.

**En ofertas_logic.php:** Solo se toma el **mayor descuento** de una sola promoción.

**En pago.html:** Solo muestra el descuento de cupón (no el de promociones automáticas pre-checkout).

> [!WARNING]
> El usuario no puede saber cuánto descuento automático recibirá antes de confirmar el pago. Los descuentos de promoción son calculados en backend y retornados solo en la respuesta de confirmación.

---

## 8. Endpoints de Gestión de Promociones

### crud_promociones.php
```
GET    → getAll('promociones')                           [requiereAdmin]
POST   → crear nueva promoción + auditoría              [requiereAdmin]
PUT    → editar promoción por id + auditoría            [requiereAdmin]
DELETE → eliminar promoción por id + auditoría          [requiereAdmin]
```

**Gap:** Solo `admin` puede gestionar promociones. Según ROLE-MATRIX, `CRM` también debería poder gestionar promociones.

### crud_cupones.php
```
GET    (cliente) → cupones propios vigentes             [sesión cliente]
GET    (personal) → todos los cupones                   [requierePersonal]
POST   → crear cupón para usuario                       [requiereCRM]
DELETE → eliminar cupón                                 [requiereCRM]
```

**Gap SPEC-01:** No existe endpoint para:
- Desactivar cupón (sin eliminar)
- Consultar historial de uso de cupón
- Cupones multi-uso (usos_restantes)

---

## 9. Gaps vs SPEC-04

| Req. SPEC-04 | Estado |
|---|---|
| PromotionEngine centralizado | ❌ No existe — lógica en 2 archivos PHP |
| Ningún módulo calcula descuentos manualmente | ❌ pago.html calcula cupón en JS |
| Integración con CRM | ✅ Parcial — verifica etiqueta CRM |
| Integración con Checkout | ✅ procesar_pago.php aplica descuentos |
| Integración con Ventas | ❌ No hay módulo de ventas separado |
| Integración con Auditoría | ✅ Promociones CRUD registra auditoría |
| Control de expiraciones | ✅ Parcial — fecha_inicio/fin en promo |
| Control de ownership | ✅ Parcial — usuario_id en cupón (puede ser null) |

---

## 10. Gaps vs SPEC-01 (Cupones)

| Req. SPEC-01 | Estado |
|---|---|
| Cupón con user_id | ✅ Presente (puede ser null) |
| Cupón con código | ✅ Presente |
| Cupón con descuento | ✅ Presente |
| Cupón con expiración | ✅ Presente (puede ser null) |
| Cupón con usos_restantes | ❌ Solo `usado: bool` |
| Cupón con estado (activo/inactivo) | ❌ Solo `usado: bool` |
| Solo autenticados pueden obtener cupones | ✅ requiereCliente en GET |
| Solo propietario puede usar cupón | ✅ Parcial (usuario_id null = universal) |
| Backend valida ownership | ✅ Sí, en validar_cupon.php y procesar_pago.php |
| Módulo CRM: asignar cupón | ✅ crud_cupones.php POST |
| Módulo CRM: desactivar cupón | ❌ Solo DELETE (eliminación física) |
| Módulo CRM: consultar historial | ❌ No existe endpoint |
| Todos los descuentos pasan por PromotionEngine | ❌ pago.html calcula en JS |
