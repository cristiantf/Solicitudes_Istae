# Plan de Implementación: Firma Digital (Fase 4)

La Fase 4 es el paso definitivo hacia el "Cero Papeles". El objetivo es que la solicitud deje de imprimirse físicamente y, en su lugar, la institución la firme electrónicamente y el estudiante la descargue desde su portal.

## Cambio en el Flujo de Trabajo (Arquitectura)
Actualmente, el PDF se genera *en el navegador del estudiante* y no se guarda en tu servidor (solo se guardan los datos de texto en MySQL). Para poder firmar el documento, debemos cambiar esto:
1. **Subida del PDF:** Cuando el estudiante presione "Enviar", el sistema generará el PDF en su navegador pero, en lugar de solo descargarlo, lo enviará ocultamente a tu servidor y se guardará en una carpeta (ej: `backend/uploads/`).
2. **Descarga desde el Portal:** En la página de consulta (`consulta.html`), si la solicitud está `APROBADA`, aparecerá un botón de "Descargar Documento Firmado".

## Opciones de Firma (¡Necesito tu decisión!)

Para estampar la firma en el PDF desde el servidor (PHP), existen dos caminos. Por favor, lee atentamente y dime cuál prefieres implementar:

### Opción A: Firma Institucional Visual (Más fácil y rápido)
- **Cómo funciona:** Cuando Secretaría cambia el estado a `APROBADA`, el servidor PHP toma el PDF guardado y le "estampa" encima la imagen escaneada de la firma del Rector (o sello de Secretaría) junto con la fecha de aprobación y un Código QR de validación.
- **Ventajas:** No requiere certificados criptográficos comprados, funciona en cualquier servidor compartido, y es instantáneo de programar.
- **Requisitos:** Solo necesitaré que me pases (más adelante) una imagen PNG con la firma/sello que deseas estampar.

### Opción B: Firma Electrónica Real con Certificado P12 (Avanzado)
- **Cómo funciona:** Utilizamos criptografía real. El PDF se encripta con un certificado `.p12` (emitido por el Banco Central o Security Data). Al abrirlo en Adobe Acrobat, dirá "Firmado digitalmente por Instituto ISTAE" con un check verde.
- **Ventajas:** Validez legal máxima en Ecuador.
- **Requisitos:** Tu servidor (XAMPP/Webmin) debe tener habilitada la extensión `openssl`. Además, tendrás que subir tu archivo `.p12` real al servidor y poner su contraseña en el código (lo cual es un riesgo de seguridad si el servidor no está bien protegido). Es mucho más complejo de programar y requiere instalar librerías externas de PHP (como TCPDF o FPDI).

> [!IMPORTANT]
> **Decisión Requerida**
> Para proceder con la Fase 4, por favor respóndeme:
> 1. ¿Prefieres implementar la **Opción A (Sello visual/QR)** o la **Opción B (Certificado criptográfico P12)**?
> 2. ¿Estás de acuerdo con que modifique `app.js` y `api.php` para que los PDFs generados se guarden en una carpeta de tu servidor?
