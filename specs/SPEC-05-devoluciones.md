# SPEC-05 — Devoluciones y Cancelaciones

## Objetivo

Permitir solicitudes de:
- devolución,
- cancelación,
- reembolso.

---

## Flujo

Cliente:
- solicita devolución.

CRM:
- revisa,
- aprueba/rechaza.

Sistema:
- actualiza pedido,
- actualiza finanzas,
- registra auditoría.

---

## Finanzas

Toda devolución debe:
- generar egreso,
- afectar balances,
- registrarse históricamente.

---

## Auditoría

Registrar:
- usuario,
- fecha,
- monto,
- decisión.