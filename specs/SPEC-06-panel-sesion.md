# SPEC-06 — Panel y Sesión

## Objetivo

Corregir inconsistencias visuales y de autenticación del panel.

---

## Problemas Detectados

- Login falla intermitentemente.
- Panel conserva estado viejo.
- Botones no reflejan permisos.
- Superusuarios inconsistentes.

---

## Requisitos

El panel debe:
- reconstruirse tras login,
- destruir estado tras logout,
- recargar permisos dinámicamente.

---

## Etiquetas

Mostrar:
- usuario actual,
- rol actual,
- estado de sesión.