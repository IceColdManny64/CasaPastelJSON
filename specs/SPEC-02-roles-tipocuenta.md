# SPEC-02 — Roles y Tipos de Cuenta

## Objetivo

Garantizar sincronización completa entre:
- CRM,
- sesión,
- panel,
- permisos visuales.

---

## Problemas Detectados

- Cambios no reflejados visualmente.
- Roles inconsistentes.
- Permisos congelados.

---

## Requisitos

El cambio de rol debe:
1. actualizar persistencia,
2. actualizar sesión,
3. reconstruir panel,
4. actualizar botones.

---

## UI

Mostrar etiqueta visible:
- perfil actual,
- rol actual.

---

## Seguridad

Nunca permitir:
- escalamiento manual frontend,
- permisos cacheados permanentemente.