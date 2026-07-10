# Análisis y Refactorización del Firmware NodeMCU — `nodered5.ino`

## 1. Bugs y Problemas Encontrados

### 🔴 BUG 1: Falso positivo en lista blanca (CRÍTICO)
**Línea 248:** `listaBlanca.indexOf(id)` puede dar **falsos positivos**.
Si la lista blanca es `"1,13,18"` y llega `id = "1"`, funciona. Pero si llega `id = "3"`, también encuentra `"3"` dentro de `"13"` → **abre la puerta a un usuario no autorizado**.

```diff
-  if (listaBlanca.indexOf(id) != -1) {
+  if (("," + listaBlanca + ",").indexOf("," + id + ",") != -1) {
```

### 🟡 BUG 2: `enviarLogNube` no verifica respuesta HTTP
**Línea 274-276:** Se ignora el `httpCode`. Si el servidor devuelve error (403, 500, timeout), el Serial imprime "Log enviado" aunque haya fallado. El dato se pierde silenciosamente.

### 🟡 BUG 3: Sin cola de reintentos offline
Si el WiFi se cae en el momento de una marcación, `enviarLogNube` falla y el registro se pierde para siempre. `LittleFS` está incluido pero **nunca se usa**.

### 🟡 BUG 4: Reconexión agresiva al biométrico
**Línea 68-71:** Si el Hikvision está apagado o desconectado, el `loop()` intenta reconectar cada 2 segundos infinitamente, bloqueando las tareas de nube (polling de comandos) y consumiendo RAM.

### 🟢 BUG 5: Heap libre no monitoreado
El ESP8266 tiene ~40KB de heap. Las concatenaciones de `String` y conexiones `WiFiClientSecure` pueden fragmentar la memoria hasta causar un crash silencioso sin ningún diagnóstico.

---

## 2. Mejoras Propuestas

| # | Mejora | Prioridad | Impacto |
|---|---|---|---|
| 1 | Corregir falso positivo en lista blanca | 🔴 Alta | Seguridad |
| 2 | Verificar respuesta HTTP en `enviarLogNube` | 🔴 Alta | Fiabilidad |
| 3 | Cola offline con LittleFS | 🟡 Media | Resiliencia |
| 4 | Backoff exponencial en reconexión al biométrico | 🟡 Media | Estabilidad |
| 5 | Monitor de heap libre en Serial | 🟢 Baja | Diagnóstico |
| 6 | Contador de reconexiones y uptime | 🟢 Baja | Diagnóstico |
| 7 | LED de estado (parpadeo = sin WiFi, fijo = OK) | 🟢 Baja | UX Hardware |
| 8 | Watchdog timer para evitar cuelgues | 🟡 Media | Estabilidad |

---

## 3. Nuevas Funcionalidades Sugeridas

### 3.1 Cola Offline con LittleFS
Guardar marcaciones en archivo cuando no hay WiFi y reenviarlas automáticamente al recuperar conexión. Esto garantiza que ninguna asistencia se pierda.

### 3.2 Heartbeat al servidor
Enviar un ping periódico con diagnósticos (heap libre, uptime, IP, señal WiFi) para monitoreo avanzado desde el panel admin.

### 3.3 Comando REBOOT remoto
Permitir al admin reiniciar el NodeMCU desde el panel web cuando se detecte un comportamiento anómalo.

### 3.4 Buzzer de retroalimentación
Agregar un buzzer piezoeléctrico para dar feedback sonoro: 1 beep = acceso OK, 2 beeps rápidos = denegado.

---

## 4. Código Refactorizado (v3.0)

El código completo refactorizado se encuentra en el archivo [nodered5.ino](file:///home/user1/web/acceso.istae.edu.ec/public_html/nodered5.ino) después de aplicar los cambios.

### Cambios principales respecto a v2.3:
- ✅ Fix de falso positivo en lista blanca (búsqueda exacta con delimitadores)
- ✅ Verificación de respuesta HTTP con logging del código de error
- ✅ Cola offline con LittleFS (hasta 50 marcaciones pendientes)
- ✅ Backoff exponencial en reconexión al biométrico (2s → 4s → 8s → ... → 60s máx)
- ✅ Monitor de heap libre cada 30 segundos
- ✅ Comentarios completos en español en cada sección
- ✅ Soporte para comando REBOOT remoto
- ✅ LED integrado como indicador de estado
