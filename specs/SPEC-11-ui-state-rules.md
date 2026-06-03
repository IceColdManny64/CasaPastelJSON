# SPEC-11 — UI State Rules

## Objetivo

Definir reglas visuales del sistema.

---

## Requisitos

La interfaz debe:
- recalcular permisos,
- ocultar módulos inválidos,
- actualizar botones,
- reconstruirse tras autenticación.

---

## Restricciones

Nunca:
- conservar permisos obsoletos,
- depender solo de cache frontend.

---

## Eventos UI

Actualizar interfaz después de:
- login,
- logout,
- cambio de rol,
- actualización CRM.