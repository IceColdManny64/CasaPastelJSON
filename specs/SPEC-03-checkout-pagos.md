# SPEC-03 — Checkout y Métodos de Pago

## Objetivo

Unificar el flujo de checkout para todos los métodos de pago.

---

## Métodos Requeridos

- tarjeta
- paypal
- mercadopago
- transferencia
- cheque electrónico

---

## Pipeline Obligatorio

seleccionar metodo
→ validar
→ crear orden
→ procesar pago
→ registrar finanzas
→ registrar auditoría
→ actualizar stock
→ finalizar checkout

---

## Restricciones

Ningún método puede omitir:
- creación de pedido,
- auditoría,
- movimiento financiero.

---

## Validaciones

Todos los métodos deben:
- finalizar correctamente,
- actualizar UI,
- generar confirmación.