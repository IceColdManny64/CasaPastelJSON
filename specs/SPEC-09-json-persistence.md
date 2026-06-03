# SPEC-09 — Persistencia JSON

## Objetivo

Evitar corrupción e inconsistencias en archivos JSON.

---

## Requisitos

Toda escritura debe:
1. leer archivo actual,
2. validar estructura,
3. escribir temporal,
4. validar resultado,
5. reemplazar original.

---

## Restricciones

Nunca:
- sobrescribir directamente,
- asumir estructura válida,
- ignorar errores de lectura.

---

## Validaciones

Debe existir:
- manejo de corrupción,
- recuperación básica,
- validación schema.