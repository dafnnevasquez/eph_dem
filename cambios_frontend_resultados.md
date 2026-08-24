# Instrucciones de cambios — Conexión backend en ResultadosView.vue

## Contexto

El backend ya está funcionando y retorna pabellones y boxes calculados.
Cuando el usuario hace clic en **"Guardar y calcular"** en `ParametrosView.vue`, se llama al endpoint y el resultado se guarda en `localStorage` con la clave `ephdem_resultado_calculo`.

`ResultadosView.vue` debe leer ese resultado y mostrarlo. A continuación se detallan los **dos cambios** necesarios.

---

## Cambio 1 — Reemplazar el `<script setup>` completo

Reemplaza **todo** el bloque `<script setup>` actual por el siguiente:

```javascript
<script setup>
import { computed, onMounted, ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()

const nombreProyecto = ref('Proyecto seleccionado')
const pabellones = ref(0)
const boxes = ref(0)
const cargando = ref(true)
const error = ref(null)

const filtros = ref({
	texto: '',
	tipo: '',
	origen: '',
	recinto: '',
	prestacion: '',
})

const resultados = ref([])

const opcionesTipo = computed(() => [...new Set(resultados.value.map((e) => e.tipo))])
const opcionesRecinto = computed(() => [...new Set(resultados.value.flatMap((e) => e.recintos))])
const opcionesPrestacion = computed(() => [...new Set(resultados.value.flatMap((e) => e.prestaciones))])

const resultadosFiltrados = computed(() => {
	const texto = filtros.value.texto.trim().toLowerCase()
	return resultados.value.filter((equipo) => {
		if (texto && !equipo.nombre.toLowerCase().includes(texto)) return false
		if (filtros.value.tipo && equipo.tipo !== filtros.value.tipo) return false
		if (filtros.value.recinto && !equipo.recintos.includes(filtros.value.recinto)) return false
		if (filtros.value.prestacion && !equipo.prestaciones.includes(filtros.value.prestacion)) return false
		if (filtros.value.origen === 'recinto' && equipo.recinto === 0) return false
		if (filtros.value.origen === 'especifico' && equipo.especifico === 0) return false
		return true
	})
})

const resumenEquipos = computed(() => resultadosFiltrados.value.map((equipo) => ({
	...equipo,
	porcentajeRecinto: Math.round((equipo.recinto / (equipo.total || 1)) * 100),
	porcentajeEspecifico: Math.round((equipo.especifico / (equipo.total || 1)) * 100),
})))

const equiposRecinto = computed(() => resultadosFiltrados.value.filter((e) => e.recinto > 0))
const equiposEspecificos = computed(() => resultadosFiltrados.value.filter((e) => e.especifico > 0))

const recintosAgrupados = computed(() => {
	const mapa = new Map()
	const colores = ['pastel-amber', 'pastel-rose', 'pastel-peach', 'pastel-sand']
	let colorIndex = 0
	equiposRecinto.value.forEach((equipo) => {
		const recintos = Array.isArray(equipo.recintos) && equipo.recintos.length > 0 ? equipo.recintos : ['Recinto']
		const cantidadPorRecinto = Math.max(1, Math.round(equipo.recinto / recintos.length))
		recintos.forEach((recinto) => {
			if (!mapa.has(recinto)) {
				mapa.set(recinto, { nombre: recinto, items: [], colorClass: colores[colorIndex % colores.length] })
				colorIndex++
			}
			mapa.get(recinto).items.push({ nombre: equipo.nombre, cantidad: cantidadPorRecinto })
		})
	})
	return Array.from(mapa.values())
})

const equiposEspecificosTabla = computed(() => equiposEspecificos.value.map((e) => ({ nombre: e.nombre, cantidad: e.especifico })))
const totalEquipos = computed(() => resultadosFiltrados.value.reduce((acc, e) => acc + e.total, 0))

onMounted(() => {
	const raw = localStorage.getItem('ephdem_resultado_calculo')
	if (!raw) {
		error.value = 'No hay resultados disponibles. Vuelve a parámetros y calcula.'
		cargando.value = false
		return
	}
	try {
		const datos = JSON.parse(raw)
		pabellones.value = datos.pabellones?.total ?? 0
		boxes.value      = datos.boxes?.total ?? 0
		cargando.value   = false
	} catch (e) {
		error.value = 'Error al leer los resultados.'
		cargando.value = false
	}
})

function volverAtras() {
	router.back()
	setTimeout(() => window.scrollTo({ top: 0, left: 0, behavior: 'auto' }), 0)
}

function exportarExcel() {
	alert('Exportar a Excel (pendiente de integrar con el back-end).')
}

function exportarPdf() {
	alert('Exportar a PDF (pendiente de integrar con el back-end).')
}
</script>
```

---

## Cambio 2 — Reemplazar el banner de métricas en el `<template>`

Dentro del `<template>`, busca este bloque en la sección `resumen-banner`:

```html
<div class="banner-total">
    <span class="metric-value">{{ totalEquipos }}</span>
    <span class="metric-label">Equipos totales</span>
</div>
```

Reemplázalo por este:

```html
<div class="banner-total" v-if="cargando">
    <span class="metric-label">Cargando...</span>
</div>
<div class="banner-total" v-else-if="error">
    <span class="metric-label" style="color:#ffaaaa">{{ error }}</span>
</div>
<div v-else style="display:flex; gap:32px; align-items:baseline;">
    <div class="banner-total">
        <span class="metric-value">{{ pabellones }}</span>
        <span class="metric-label">Pabellones</span>
    </div>
    <div class="banner-total">
        <span class="metric-value">{{ boxes }}</span>
        <span class="metric-label">Boxes UPC</span>
    </div>
</div>
```

---

## Cambio 3 — Reemplazar `guardarYCalcular` en `ParametrosView.vue`

En `ParametrosView.vue`, busca la función `guardarYCalcular` y reemplázala por esta:

```javascript
async function guardarYCalcular() {
	// Leer el id del proyecto activo (guardado al crear el proyecto)
	const idProyectoActual = localStorage.getItem('ephdem_proyecto_activo')
	if (!idProyectoActual) {
		alert('No hay un proyecto activo. Vuelve a crear o seleccionar un proyecto.')
		router.push('/crear-proyecto') // ajustar a la ruta real si se llama distinto
		return
	}

	// Guardar parámetros en localStorage como respaldo (igual que antes)
	localStorage.setItem(PARAMETROS_STORAGE_KEY, JSON.stringify(filas.value))

	// Construir el payload con el contrato exacto del endpoint
	const payload = {
		proyecto_id: Number(idProyectoActual),
		filas: filas.value.map((fila) => ({
			prestacion_id:    fila.id,
			demanda_anual:    numeroSeguro(fila.demanda),
			dias_laborales:   numeroSeguro(fila.diasAnuales),
			disponibilidad:   numeroSeguro(fila.disponibilidad) / 100, // % → decimal (100 → 1.0)
			jornada_efectiva: numeroSeguro(fila.jornadaLaboral),
		})),
	}

	try {
		const resp = await fetch('https://sigem-uv.cl/__v2/modulo_eph/ajax/calcular_demanda.php', {
			method: 'POST',
			headers: { 'Content-Type': 'application/json' },
			body: JSON.stringify(payload),
		})
		const data = await resp.json()

		if (!data.ok) {
			alert('Error al calcular: ' + (data.error || '') + (data.detalle ? '\n' + data.detalle.join('\n') : ''))
			return
		}

		// Guardar el resultado para mostrarlo en /resultados
		localStorage.setItem('ephdem_resultado_calculo', JSON.stringify(data.datos))
		router.push('/resultados')
	} catch (error) {
		console.error('Error de conexión con el backend:', error)
		alert('No se pudo conectar con el servidor de cálculo.')
	}
}
```

> **Nota:** La función `numeroSeguro` ya existe en `ParametrosView.vue`, no hay que redeclararla.

---

## Resumen de qué hace cada cambio

| Cambio | Archivo | Qué hace |
|--------|---------|----------|
| 1 | `ResultadosView.vue` | Lee `ephdem_resultado_calculo` del localStorage al montar la vista y extrae `pabellones.total` y `boxes.total` |
| 2 | `ResultadosView.vue` | Muestra los números reales de pabellones y boxes en el banner superior en vez de `totalEquipos` |
| 3 | `ParametrosView.vue` | Conecta el botón "Guardar y calcular" al endpoint del backend y guarda el resultado en localStorage |

---

## Flujo completo una vez aplicados los cambios

```
Usuario crea proyecto → id guardado en localStorage['ephdem_proyecto_activo']
         ↓
Selecciona prestaciones
         ↓
Rellena parámetros → clic "Guardar y calcular"
         ↓
POST a /ajax/calcular_demanda.php
         ↓
Backend guarda en EPHAC_Proyecto_Demanda y calcula
         ↓
Resultado guardado en localStorage['ephdem_resultado_calculo']
         ↓
Navega a /resultados → muestra pabellones y boxes
```
