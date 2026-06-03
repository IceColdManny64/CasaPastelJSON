# AGENT PROMPT — CasaPastel ERP/CRM

## Objetivo General

Mantener y extender el sistema CasaPastel sin introducir regresiones,
manteniendo compatibilidad con la arquitectura actual basada en:
- PHP procedural
- HTML/CSS/JavaScript
- Persistencia JSON
- Panel ERP/CRM modular

---

## Reglas Globales

1. Nunca reescribir archivos completos si no es necesario.
2. Nunca modificar múltiples módulos críticos simultáneamente.
3. Siempre mostrar:
   - archivos afectados,
   - funciones afectadas,
   - riesgos,
   - dependencias.
4. Todo cambio debe incluir:
   - validación frontend,
   - validación backend,
   - persistencia,
   - actualización visual,
   - auditoría.
5. Nunca asumir permisos desde frontend.
6. Toda validación crítica debe ejecutarse en backend.
7. Toda operación monetaria debe registrarse en finanzas y auditoría.
8. Toda operación sensible debe validar sesión activa.

---

## Flujo Obligatorio de Trabajo

1. Analizar.
2. Identificar dependencias.
3. Proponer plan.
4. Implementar una sola fase.
5. Validar.
6. Esperar confirmación.

---

## Restricciones

- No migrar a frameworks.
- No reorganizar carpetas masivamente.
- No convertir el proyecto completo a OOP.
- No reemplazar persistencia JSON.
- No introducir dependencias externas innecesarias.

---

## Arquitectura Actual

El sistema está dividido conceptualmente en:

1. Estado y autenticación
2. Motores de negocio
3. Persistencia y auditoría

Toda modificación debe respetar esta separación.

---

## Función Central Obligatoria

El panel debe reconstruirse mediante:

refreshPanelState()

Debe ejecutarse después de:
- login,
- logout,
- cambio de rol,
- actualización CRM,
- actualización de sesión.

---

## Persistencia JSON

Toda escritura JSON debe:
1. leer archivo actual,
2. modificar en memoria,
3. validar estructura,
4. escribir archivo temporal,
5. reemplazar archivo original.

---

## Seguridad

Nunca permitir:
- cupones sin sesión,
- acceso por frontend solamente,
- cambios de rol sin validación,
- descuentos directos sin PromotionEngine.