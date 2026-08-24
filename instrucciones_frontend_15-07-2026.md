# Frontend pendiente — EPHDEM-Cerrada · 15-jul-2026

Hola! Acá va todo lo que se hizo en el backend hoy y que necesita conexión
desde el frontend. **Todos los endpoints ya están funcionando y probados en
producción** — se probaron con `fetch()` real contra `sigem-uv.cl` y las
respuestas de ejemplo que aparecen abajo son **capturas reales**, no
inventadas.

## Cómo usar este documento

Cada sección tiene un **prompt listo para copiar y pegar** a tu asistente
de IA. Los prompts están escritos para que tu IA no necesite ver el código
del backend: llevan el contrato completo de cada endpoint embebido.

**Regla general para todos los prompts:** todo es **agregar**, nunca
modificar lo que ya funciona. El flujo actual (crear proyecto → prestaciones
→ parámetros → calcular → resultados) tiene que seguir funcionando
exactamente igual.

**Orden sugerido:** 1 → 2 → 3 → 4 → 5. Cada uno es verificable por separado.

---

## Contexto general: los 3 endpoints

Todos cuelgan de `/__v2/modulo_eph/ajax/`.

| Endpoint | Método | Para qué |
|---|---|---|
| `calcular_demanda.php` | POST | Ya lo usás. Guarda demanda y calcula. **Ahora también devuelve `urpa`.** |
| `obtener_resultados_proyecto.php` | GET | **Nuevo.** Trae resultados de un proyecto ya calculado, sin pasar por el flujo. |
| `get_prestaciones_demanda.php` | GET | **Nuevo en el server.** Trae las prestaciones de un proyecto + sus valores guardados. |

---

# 1. Router — parámetro opcional en `/resultados`

Es el cambio más chico y habilita el resto. Sin esto, el punto 2 no
funciona.

### Prompt para tu IA

```
En el archivo de rutas del proyecto (router de Vue), buscá la ruta de
`/resultados` y cambiala para que acepte un parámetro OPCIONAL llamado
`proyectoId`.

De:
  { path: '/resultados', name: 'resultados', component: ResultadosView }

A:
  { path: '/resultados/:proyectoId?', name: 'resultados', component: ResultadosView }

El `?` al final es obligatorio: hace que el parámetro sea opcional, de modo
que `/resultados` (sin id) siga funcionando exactamente igual que hoy, y
`/resultados/38` active la carga desde servidor.

No toques ninguna otra ruta. Después del cambio, navegar a `/resultados`
sin id debe seguir mostrando lo mismo que antes.
```

---

# 2. `ResultadosView.vue` — cargar un proyecto existente desde el servidor

Hoy `ResultadosView.vue` lee solo de `localStorage`. Hay que agregarle un
segundo camino: si viene un `proyectoId` en la ruta, pedir los datos al
servidor.

### Endpoint

```
GET /__v2/modulo_eph/ajax/obtener_resultados_proyecto.php?proyecto_id=38&usuario_id=553
```

Ambos parámetros son obligatorios y deben ser > 0. El `usuario_id` es el
del usuario logueado (el backend valida que el proyecto le pertenezca).

**Respuestas:**
- `200` → `{ ok: true, datos: {...} }`
- `400` → falta algún parámetro o es <= 0
- `404` → el proyecto no existe o no pertenece a ese usuario
- `500` → error interno

**Lo importante:** `datos` tiene **exactamente la misma forma** que lo que
ya devuelve `calcular_demanda.php` y que ya guardás en `localStorage`, con
dos diferencias:
- No trae `filas_guardadas` (no aplica: no se guardó nada nuevo).
- Sí trae `nombre_proyecto` en el nivel superior de `datos`.

Es decir: si tu componente ya sabe pintar el resultado de un cálculo
recién hecho, sabe pintar esto. Es el mismo objeto.

### Prompt para tu IA

```
En `ResultadosView.vue`, hay que agregar la capacidad de cargar los
resultados de un proyecto ya calculado desde el servidor, además del
comportamiento actual que lee de localStorage.

CONTEXTO: hoy el `onMounted` lee `localStorage` (key
`ephdem_resultado_calculo`), parsea el JSON y puebla los refs del
componente. Eso tiene que seguir funcionando igual.

CAMBIO PEDIDO — refactor del `onMounted` en tres funciones:

1. `aplicarDatos(datos)` — extraé a esta función TODO lo que hoy hace el
   onMounted una vez que ya tiene el objeto parseado (poblar los refs,
   apagar el loading). Usá los nombres de refs que ya existen en el
   componente, no inventes nuevos. Esta función debe ser el ÚNICO lugar
   que escribe esos refs.

   Ojo: el objeto `datos` puede traer `nombre_proyecto` (cuando viene del
   servidor). Si viene, usalo; si no, dejá el valor que ya tenías.

2. `cargarDesdeLocalStorage()` — lo que hace hoy: leer
   `ephdem_resultado_calculo`, parsear, y llamar `aplicarDatos()`. Si no
   hay nada o falla el parseo, mostrar el error como se muestra hoy.

3. `cargarDesdeServidor(proyectoId)` — nueva. Hace:

   const usuarioId = <el id del usuario logueado, del store de auth>
   const resp = await fetch(
     `/__v2/modulo_eph/ajax/obtener_resultados_proyecto.php?proyecto_id=${proyectoId}&usuario_id=${usuarioId}`
   )
   const json = await resp.json()
   if (!resp.ok || !json.ok) { mostrar json.error o un mensaje genérico }
   else { aplicarDatos(json.datos) }

   Envolver en try/catch para errores de red.

Y el onMounted queda:

   onMounted(() => {
     if (route.params.proyectoId) {
       cargarDesdeServidor(route.params.proyectoId)
     } else {
       cargarDesdeLocalStorage()
     }
   })

Necesitás importar `useRoute` de 'vue-router' y hacer `const route = useRoute()`.

IMPORTANTE:
- NO escribas en localStorage lo que llega del servidor. Si lo cacheás ahí,
  se puede mezclar con el flujo "recién calculado" y mostrar datos viejos.
- NO toques el <template>, ni los computed, ni `volverAtras`, ni
  `exportarExcel`, ni `exportarPdf`. Todos dependen de los mismos refs que
  `aplicarDatos()` sigue poblando igual, así que funcionan sin cambios.
- Los botones de exportar Excel/PDF van a funcionar solos desde esta vista,
  porque ambos exportadores recalculan en el servidor a partir del
  `proyecto_id`. En cuanto `aplicarDatos()` puebla el id del proyecto y el
  nombre, apuntan al proyecto correcto.

CÓMO VERIFICAR: navegar directo a `/resultados/38` (logueado como el dueño
de ese proyecto) debe mostrar los resultados sin pasar por el flujo. Y
`/resultados` sin id debe seguir funcionando igual que antes.
```

---

# 3. `ListaProyectos.vue` — conectar el botón "Ver"

### Prompt para tu IA

```
En `ListaProyectos.vue` hay una función `verProyecto` que hoy es un stub:

  function verProyecto(proyecto) {
    alert(`Visualizando: ${proyecto.nombre_proyecto}`)
  }

Reemplazala por una navegación a la vista de resultados pasando el id del
proyecto:

  function verProyecto(proyecto) {
    router.push(`/resultados/${proyecto.id}`)
  }

ATENCIÓN — verificá primero el nombre real del campo del id. En la base de
datos la columna se llama `id_proyecto`, pero el endpoint que llena este
listado podría estar mapeándola a `id`. Revisá qué trae realmente el objeto
`proyecto` (con un console.log si hace falta) y usá el nombre correcto.

NO toques `editarProyecto` en este cambio — eso es el punto 5 de este
documento y se hace aparte.
```

---

# 4. Mostrar la URPA (sala de recuperación post-anestésica)

Esto es **nuevo**: antes no existía en el sistema. La URPA es la sala donde
los pacientes se recuperan de la anestesia después de una cirugía. El
backend ahora la calcula automáticamente a partir del número de pabellones.

**Ya viene incluida en las respuestas de los dos endpoints** que devuelven
resultados (`calcular_demanda.php` y `obtener_resultados_proyecto.php`), en
`datos.urpa`. O sea: si hacés el punto 2, ya tenés el dato disponible sin
pedir nada extra.

### Cómo funciona el cálculo (para que entiendas qué estás mostrando)

- `camillas = 2 × nº total de pabellones`
- `salas = ceil(camillas / 12)` (máximo 12 camillas por sala)
- Hay **dos tipos de equipos con multiplicadores distintos**:
  - **Equipos de sala:** se cargan 1 vez por cada sala de URPA, sin
    importar cuántas camillas tenga. Se multiplican × nº de salas.
  - **Equipos de camilla:** escalan con cada camilla. Se multiplican × nº
    de camillas.

Por eso, con 9 pabellones → 18 camillas → 2 salas: el carro de paro sale
×2 (uno por sala) y el monitor multiparámetros sale ×18 (uno por camilla).

### Estructura real de `datos.urpa` (respuesta capturada de producción)

```json
{
  "nombre_recinto": "URPA (Sala de recuperación post-anestésica)",
  "nro_pabellones": 9,
  "nro_camillas": 18,
  "nro_salas": 2,
  "equipos_sala": [
    { "equipo_id": 33,  "nombre_equipo": "Carro de paro completo", "cantidad_base": 1, "cantidad": 2 },
    { "equipo_id": 48,  "nombre_equipo": "Computador", "cantidad_base": 1, "cantidad": 2 },
    { "equipo_id": 53,  "nombre_equipo": "Dispensador de jabón", "cantidad_base": 1, "cantidad": 2 },
    { "equipo_id": 88,  "nombre_equipo": "Lavamanos clínico", "cantidad_base": 1, "cantidad": 2 },
    { "equipo_id": 107, "nombre_equipo": "Mesón de estación de enfermería", "cantidad_base": 1, "cantidad": 2 },
    { "equipo_id": 110, "nombre_equipo": "Monitor central", "cantidad_base": 1, "cantidad": 2 },
    { "equipo_id": 124, "nombre_equipo": "Porta toalla de papel", "cantidad_base": 1, "cantidad": 2 }
  ],
  "equipos_camilla": [
    { "equipo_id": 17,  "nombre_equipo": "Bomba de infusión volumétrica", "cantidad_base": 1, "cantidad": 18 },
    { "equipo_id": 22,  "nombre_equipo": "Botella de oxígeno", "cantidad_base": 1, "cantidad": 18 },
    { "equipo_id": 27,  "nombre_equipo": "Camilla de recuperación con barandas", "cantidad_base": 1, "cantidad": 18 },
    { "equipo_id": 68,  "nombre_equipo": "Fonendoscopio", "cantidad_base": 1, "cantidad": 18 },
    { "equipo_id": 113, "nombre_equipo": "Monitor multiparámetros", "cantidad_base": 1, "cantidad": 18 },
    { "equipo_id": 123, "nombre_equipo": "Porta sueros", "cantidad_base": 1, "cantidad": 18 },
    { "equipo_id": 166, "nombre_equipo": "Tomas de aspiración central", "cantidad_base": 1, "cantidad": 18 }
  ],
  "equipos": [
    { "equipo_id": 17, "nombre_equipo": "Bomba de infusión volumétrica", "cantidad": 18 },
    { "equipo_id": 22, "nombre_equipo": "Botella de oxígeno", "cantidad": 18 },
    { "equipo_id": 27, "nombre_equipo": "Camilla de recuperación con barandas", "cantidad": 18 },
    { "equipo_id": 33, "nombre_equipo": "Carro de paro completo", "cantidad": 2 }
  ]
}
```

**Tenés 3 listas y podés usar la que prefieras:**
- `equipos_sala` + `equipos_camilla` → dos sub-tablas separadas. Es lo más
  informativo: se ve por qué cada cantidad es la que es.
- `equipos` → **una sola tabla plana** con la fusión de ambas (suma por
  `equipo_id`). Tiene la misma forma que los `equipos` de `por_recinto`
  (`equipo_id`, `nombre_equipo`, `cantidad`), así que **podés reusar el
  componente de tabla que ya tenés** para los otros recintos.

**Caso borde a manejar:** si el proyecto no tiene pabellones (solo UPC, por
ejemplo), la URPA viene toda en ceros y con listas vacías:
```json
{ "nombre_recinto": "...", "nro_pabellones": 0, "nro_camillas": 0,
  "nro_salas": 0, "equipos_sala": [], "equipos_camilla": [], "equipos": [] }
```
En ese caso lo razonable es no mostrar la sección, o mostrarla con un
mensaje de "sin requerimiento de URPA".

### Prompt para tu IA

```
Hay que AGREGAR una sección nueva en la vista de resultados para mostrar la
URPA (Sala de Recuperación Post-Anestésica). Es una sección nueva: no
reemplaza ni modifica nada de lo que ya se muestra.

DE DÓNDE SALEN LOS DATOS: el objeto de resultados que ya recibe la vista
(el mismo que ya usás para pintar pabellones, boxes y equipamiento) ahora
trae una clave nueva `urpa` en el nivel superior de `datos`, hermana de
`pabellones`, `boxes` y `equipamiento`. No hay que hacer ninguna llamada
extra al servidor: el dato ya viene.

ESTRUCTURA DE `datos.urpa`:
{
  "nombre_recinto": "URPA (Sala de recuperación post-anestésica)",  // string
  "nro_pabellones": 9,    // int, nº de pabellones del que se derivó
  "nro_camillas": 18,     // int, = 2 × nro_pabellones
  "nro_salas": 2,         // int, = ceil(nro_camillas / 12)
  "equipos_sala": [       // equipos que se cargan 1 vez POR SALA
    { "equipo_id": 33, "nombre_equipo": "Carro de paro completo",
      "cantidad_base": 1, "cantidad": 2 }
    // cantidad = cantidad_base × nro_salas
  ],
  "equipos_camilla": [    // equipos que escalan POR CAMILLA
    { "equipo_id": 27, "nombre_equipo": "Camilla de recuperación con barandas",
      "cantidad_base": 1, "cantidad": 18 }
    // cantidad = cantidad_base × nro_camillas
  ],
  "equipos": [            // fusión de las dos listas anteriores (suma por equipo_id)
    { "equipo_id": 17, "nombre_equipo": "Bomba de infusión volumétrica",
      "cantidad": 18 }
  ]
}

QUÉ MOSTRAR:
1. Un encabezado con `nombre_recinto`.
2. Un resumen con las 3 cifras: `nro_camillas` camillas distribuidas en
   `nro_salas` sala(s), derivadas de `nro_pabellones` pabellones.
3. La lista de equipos. Elegí UNA de estas dos opciones:
   a) Dos sub-tablas: "Equipos por sala" (usando `equipos_sala`) y "Equipos
      por camilla" (usando `equipos_camilla`). Es más informativo porque se
      entiende de dónde sale cada cantidad.
   b) Una sola tabla usando `equipos`, que tiene la misma forma
      (`equipo_id`, `nombre_equipo`, `cantidad`) que los equipos de
      `por_recinto`, así que podés reusar el componente de tabla existente.

CASO BORDE OBLIGATORIO: si `nro_salas === 0` (proyecto sin pabellones, solo
UPC), las tres listas vienen vacías. En ese caso no muestres la tabla:
ocultá la sección completa, o mostrá un mensaje tipo "Este proyecto no
requiere URPA".

RESTRICCIONES:
- Es una sección ADICIONAL. No modifiques ni muevas las secciones de
  pabellones, boxes ni equipamiento por recinto.
- No sumes las cantidades de URPA con las de otros recintos: son espacios
  físicos distintos, cada uno con su propia dotación.
```

---

# 5. Editar un proyecto existente y recalcular

El objetivo: que desde los resultados de un proyecto viejo se pueda volver
a parámetros, cambiar valores, y recalcular **sobreescribiendo el mismo
proyecto** (sin crear uno nuevo).

### La buena noticia: el backend ya hace todo

- `calcular_demanda.php` **nunca crea proyectos**. Siempre recibe un
  `proyecto_id` y trabaja sobre él. Si le mandás el id de un proyecto viejo,
  sobreescribe ese proyecto. **No hay que llamar a ningún endpoint nuevo
  para recalcular** — es el mismo POST que ya usás.
- El guardado ya borra la demanda anterior del proyecto y reinserta la nueva
  en una transacción. Así que si el usuario saca prestaciones, no quedan
  filas fantasma.

O sea: **si `Parametros.vue` hace el POST de siempre con el `proyecto_id`
del proyecto viejo en vez de uno nuevo, la sobreescritura ya funciona.**

### Lo que falta: precargar el formulario

Para eso está este endpoint:

```
GET /__v2/modulo_eph/ajax/get_prestaciones_demanda.php?proyecto_id=38
```

Devuelve, en una sola llamada, **las prestaciones que ese proyecto tiene
guardadas + su metadata + los valores que el usuario había cargado**.

**Respuesta real capturada de producción:**

```json
{
  "ok": true,
  "datos": [
    {
      "id_prestacion": 116,
      "nombre_prestacion": "Cuerpo extraño rectal, extracción por vía anal",
      "area_hospitalaria": "Gastroenterología Intervencional",
      "subarea_hospitalaria": "Cirugía Proctológica",
      "categoria": "urgencia",
      "defaults": {
        "dias_laborales": 365,
        "jornada_efectiva": 24,
        "disponibilidad": 1
      },
      "valores": {
        "demanda_anual": 200,
        "dias_laborales": 250,
        "disponibilidad": 0.85,
        "jornada_efectiva": 8
      }
    },
    {
      "id_prestacion": 149,
      "nombre_prestacion": "Día Cama de Hospitalización Integral Adulto en Unidad de Cuidado Intensivo (U.C.I.)",
      "area_hospitalaria": "UPC",
      "subarea_hospitalaria": "UCI",
      "categoria": "box",
      "defaults": { "dias_laborales": 365, "jornada_efectiva": 24, "disponibilidad": 1 },
      "valores": { "demanda_anual": 1000, "dias_laborales": 365, "disponibilidad": 1, "jornada_efectiva": 24 }
    }
  ]
}
```

**⚠️ El detalle más importante de todo este documento:** fijate en la
prestación 116. Sus `defaults` de categoría "urgencia" dicen
`dias_laborales: 365, jornada_efectiva: 24`, pero sus `valores` guardados
dicen `250` y `8`. El usuario había personalizado esos campos.

**Por eso: precargá con `valores`, y usá `defaults` SOLO como fallback si
`valores` viene `null`.** Si precargás con `defaults`, le pisás al usuario
lo que había guardado.

`valores` viene `null` cuando esa prestación no tiene demanda guardada.

### Prompt para tu IA

```
Hay que agregar la capacidad de EDITAR un proyecto ya calculado: volver a
la pantalla de parámetros con los valores precargados, modificarlos, y
recalcular sobreescribiendo el mismo proyecto (sin crear uno nuevo).

Son dos partes.

=== PARTE A: botón "Volver a parámetros" en la vista de resultados ===

Agregá un botón en `ResultadosView.vue` (por ejemplo "Editar parámetros" o
"Volver a parámetros") que navegue a la pantalla de parámetros pasando el
id del proyecto actual, de modo que Parametros.vue sepa que está en modo
edición. Por ejemplo, si la ruta de parámetros es `/parametros`, agregá un
param opcional igual que se hizo con `/resultados/:proyectoId?` y navegá a
`/parametros/${proyectoIdActivo}`.

Este botón es ADICIONAL: no reemplaces el botón "volver atrás" que ya
existe.

=== PARTE B: `Parametros.vue` en modo edición ===

Hoy Parametros.vue depende de localStorage para dos cosas:
  - la lista de prestaciones seleccionadas (que llena el paso previo
    /prestaciones)
  - los valores de demanda cargados

Cuando se entra a editar un proyecto existente DIRECTO desde el listado,
ese localStorage no existe o tiene datos de otro proyecto. Hay que
reconstruir ambas cosas desde el servidor.

COMPORTAMIENTO PEDIDO:
- Si NO hay `proyectoId` en la ruta → comportamiento actual intacto (lee de
  localStorage, flujo normal de proyecto nuevo).
- Si SÍ hay `proyectoId` → modo edición: pedir todo al servidor con:

  GET /__v2/modulo_eph/ajax/get_prestaciones_demanda.php?proyecto_id=<id>

  Respuesta: { ok: true, datos: [ ... ] }
  Cada item de `datos` trae:
    {
      "id_prestacion": 116,                    // int
      "nombre_prestacion": "...",              // string
      "area_hospitalaria": "...",              // string
      "subarea_hospitalaria": "...",           // string
      "categoria": "urgencia" | "electivo" | "box",
      "defaults": {                            // valores por defecto de la categoría
        "dias_laborales": 365,
        "jornada_efectiva": 24,
        "disponibilidad": 1
      },
      "valores": {                             // lo que el usuario había guardado
        "demanda_anual": 200,                  // ← puede ser null si no hay nada guardado
        "dias_laborales": 250,
        "disponibilidad": 0.85,
        "jornada_efectiva": 8
      }
    }

  Con eso construí las filas del formulario: `datos` ya trae TODO lo que
  necesitás (los ids de prestación, sus nombres para renderizar, y los
  valores). No hace falta llamar a ningún otro endpoint ni depender de
  localStorage en este modo.

⚠️ CRÍTICO — cómo precargar cada fila:
  Usá `item.valores` si NO es null. Usá `item.defaults` SOLO como fallback
  cuando `item.valores` es null.
  NO pises `valores` con `defaults`: el usuario puede haber personalizado
  valores distintos al default de su categoría. Ejemplo real: una
  prestación de categoría "urgencia" tiene defaults 365 días / 24 h, pero
  el usuario guardó 250 días / 8 h. Si precargás con defaults, le borrás su
  trabajo.
  Ojo también: `defaults` NO trae `demanda_anual` (solo dias_laborales,
  jornada_efectiva y disponibilidad). Si `valores` es null, `demanda_anual`
  arranca vacío/0.

=== RECALCULAR (sobreescribir) ===

Al apretar calcular en modo edición, hacé EXACTAMENTE el mismo POST que ya
hacés hoy a:

  POST /__v2/modulo_eph/ajax/calcular_demanda.php
  Body JSON: { "proyecto_id": <id>, "filas": [ ... ] }

...pero con el `proyecto_id` del proyecto que se está editando, en vez de
uno nuevo.

El backend ya se encarga de sobreescribir: borra la demanda anterior de ese
proyecto e inserta la nueva, en transacción. NO se crea un proyecto nuevo.
No hay que llamar a ningún endpoint distinto ni pasar ninguna bandera de
"modo edición".

Formato de cada fila (sin cambios respecto a hoy):
  { prestacion_id, demanda_anual, dias_laborales, disponibilidad, jornada_efectiva }

Recordatorio: `disponibilidad` va como decimal (0.85), no como porcentaje
(85). El backend rechaza con 400 si no está en el rango (0, 1].

RESTRICCIONES:
- El flujo de proyecto nuevo (sin proyectoId en la ruta) tiene que quedar
  intacto.
- No cambies el formato del POST ni el nombre de los campos.
```

---

# 6. Prueba end-to-end (cuando estén los 5 puntos)

1. Entrar a "Mis proyectos" → click en "Ver" de un proyecto ya calculado →
   debe cargar resultados reales desde el servidor. Verificá en la pestaña
   Network que se llamó a `obtener_resultados_proyecto.php` (y que NO se
   leyó de localStorage).
2. En esa vista, confirmar que aparece la sección URPA con sus cifras.
3. Confirmar que exportar Excel y PDF siguen funcionando desde ahí.
4. Click en "Volver a parámetros" → confirmar que el formulario se precarga
   con los valores guardados (no con los defaults).
5. Cambiar un valor → recalcular → confirmar que los resultados cambian y
   que **NO se creó un proyecto nuevo** (el listado de proyectos debe seguir
   teniendo la misma cantidad, y el id debe ser el mismo).
6. **Regresión:** crear un proyecto nuevo desde cero con el flujo completo y
   confirmar que todo sigue funcionando igual que antes.

---

# Datos de prueba reales disponibles

| Proyecto | Sirve para |
|---|---|
| `proyecto_id=38`, `usuario_id=553` | 9 pabellones → URPA con 18 camillas / 2 salas. 4 prestaciones con valores guardados. **El mejor para probar todo.** |
| `proyecto_id=25`, `usuario_id=553` | "Proyecto prueba 1". Solo UPC, 0 pabellones → sirve para probar el **caso borde de URPA vacía**. |

---

# Notas finales

- Las listas de equipos de la URPA son **provisionales**, pendientes de
  validación con la profesora guía. Los nombres y cantidades pueden cambiar,
  pero **la estructura del JSON no va a cambiar** — podés programar contra
  ella con confianza.
- Si algo devuelve un `500` con respuesta vacía, avisanos: es un error de
  backend, no de tu código.
