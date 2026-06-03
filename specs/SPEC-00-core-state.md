# SPEC-00 — Core State & Session Management

## Objetivo

Centralizar la sincronización de:
- sesión,
- permisos,
- estado del panel,
- estado visual,
- cache frontend.

---

## Problemas Detectados

- Panel desactualizado tras login.
- Botones incorrectos según rol.
- Estado persistido visualmente.
- Sesiones inconsistentes.
- Refresco manual requerido.

---

## Requisitos

### Backend

Debe existir un endpoint:
- sesion_info.php

Debe devolver:
- usuario actual,
- rol,
- permisos,
- módulos habilitados,
- estado autenticación.

---

### Frontend

Debe existir:
refreshPanelState()

Funciones:
- recargar permisos,
- reconstruir menú,
- ocultar módulos,
- actualizar botones,
- actualizar etiquetas.

---

## Eventos Obligatorios

Ejecutar refreshPanelState() después de:
- login,
- logout,
- cambio de rol,
- actualización CRM.

---

## Validaciones

- El frontend nunca será fuente de verdad.
- Los permisos deben recalcularse desde backend.