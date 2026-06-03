# SPEC-10 — Event System

## Objetivo

Centralizar comunicación entre módulos.

---

## Eventos Requeridos

- pedido_creado
- pago_aprobado
- devolucion_aprobada
- nomina_pagada
- cupon_usado
- rol_actualizado

---

## Beneficios

- reducir duplicación,
- sincronizar módulos,
- mejorar trazabilidad.

---

## Integraciones

Eventos deben notificar:
- Finanzas,
- Auditoría,
- CRM,
- Panel.