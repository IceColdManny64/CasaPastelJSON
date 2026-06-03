# SPEC-12 — Security Rules

## Objetivo

Definir reglas mínimas de seguridad del sistema.

---

## Reglas

- Validar sesión en backend.
- Validar ownership.
- Validar permisos por rol.
- Validar operaciones monetarias.

---

## Restricciones

Nunca permitir:
- promociones sin sesión,
- acceso administrativo directo,
- cambios de rol desde frontend,
- descuentos manuales inseguros.

---

## Auditoría

Toda operación sensible debe:
- registrarse,
- incluir usuario,
- incluir fecha,
- incluir resultado.