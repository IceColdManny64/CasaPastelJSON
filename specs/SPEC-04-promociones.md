# SPEC-04 — Promotion Engine

## Objetivo

Centralizar toda lógica de promociones.

---

## Problemas Detectados

- Descuentos inconsistentes.
- Lógica duplicada.
- CRM desconectado del checkout.

---

## Requisitos

Crear:
PromotionEngine

Debe controlar:
- promociones,
- cupones,
- descuentos automáticos,
- expiraciones,
- ownership.

---

## Restricciones

Ningún módulo puede calcular descuentos manualmente.

---

## Integraciones

Debe integrarse con:
- CRM,
- Checkout,
- Ventas,
- Auditoría.