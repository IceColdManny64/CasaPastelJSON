# SPEC-01 — Sistema de Cupones

## Objetivo

Implementar cupones ligados exclusivamente al cliente autenticado.

---

## Problemas Detectados

- Cupones sin ownership.
- Posible acceso sin sesión.
- Descuentos inconsistentes.

---

## Requisitos

Cada cupón debe incluir:
- id
- user_id
- codigo
- descuento
- expiracion
- usos_restantes
- estado

---

## Reglas

- Solo usuarios autenticados pueden obtener cupones.
- Solo el propietario puede usarlo.
- El backend debe validar ownership.

---

## CRM

Agregar módulo:
- Promociones por Cliente

Funciones:
- asignar cupón,
- desactivar,
- consultar historial.

---

## Checkout

Todos los descuentos deben pasar por:
PromotionEngine