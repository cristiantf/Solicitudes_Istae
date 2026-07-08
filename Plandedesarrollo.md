# Plan de Desarrollo Futuro

La versión actual del **Asistente de Solicitudes ISTAE** cubre satisfactoriamente la generación documental automatizada y la notificación básica. Para escalar el sistema hacia un **Flujo de Aprobación Documental (BPM)** integral, se recomienda la siguiente hoja de ruta (Roadmap).

## Fase 1: Migración a Base de Datos Relacional (MySQL/MariaDB)
Actualmente, las solicitudes se cuentan mediante un archivo plano (`contador.txt`).
- **Objetivo:** Registrar cada solicitud en una tabla de MySQL (ej. `solicitudes_estudiantes`).
- **Datos a almacenar:** Código, Fecha, Cédula, Nombres, Carrera, Trámite, Estado (`PENDIENTE`).
- **Beneficio:** Permitirá llevar auditorías y evitará pérdida de datos por sobreescritura.

## Fase 2: Panel Administrativo de Aprobación
- **Objetivo:** Desarrollar un "Dashboard" para el departamento de Secretaría.
- **Funcionamiento:** 
  1. Secretaría se loguea (puede integrarse al login de Moodle mediante SSO o ser independiente).
  2. Visualiza una tabla con las solicitudes `PENDIENTES`.
  3. Puede cambiar el estado a `EN REVISIÓN`, `APROBADO`, `RECHAZADO`.
- **Beneficio:** Abandona la gestión basada exclusivamente en el correo electrónico y centraliza los flujos en un solo lugar.

## Fase 3: Portal del Estudiante
- **Objetivo:** Que el estudiante consulte el estado de su trámite.
- **Funcionamiento:** Mediante el ingreso de su Cédula y su Código de Trámite (ej: `SOL-DS-2026-0005`), el estudiante podrá ver en tiempo real si su documento ya fue procesado o requiere correcciones.

## Fase 4: Firma Digital (Opcional Avanzado)
- **Objetivo:** Cero papeles.
- **Funcionamiento:** Integración con plataformas de firma electrónica (P12/Token). Una vez que la solicitud es `APROBADA` por secretaría, el rector o coordinador la firma masivamente (batch signing) y el PDF resultante se envía directamente por correo al estudiante y al sistema de archivo (Quipux o similar).

> [!TIP]
> **Próximo Paso Inmediato:** Para iniciar la **Fase 1**, asegúrate de que el panel de Virtualmin tenga creada una Base de Datos y otórgame las credenciales (o solicítame el script SQL) en la próxima sesión.
