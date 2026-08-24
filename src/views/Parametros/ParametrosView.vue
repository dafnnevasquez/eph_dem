<template>
	<div class="page-layout">
	<!-- TOP BAR 1: Logos institucionales -->
	<div class="sigem-topbar1">
		<div class="sigem-topbar1-center">
			<a href="https://uv.cl" target="_blank"><img src="https://sigem-uv.cl/_general/logos/uv_blanco.png" alt="UV" height="36" class="sigem-topbar1-logo" /></a>
			<a href="https://biomedica.uv.cl/" target="_blank"><img src="https://sigem-uv.cl/_general/logos/biomedica_logo.png" alt="Biomedica" height="36" class="sigem-topbar1-logo" /></a>
		</div>
		<a href="#" class="sigem-topbar1-acceder">Acceder</a>
	</div>

	<!-- TOP BAR 2: Bootstrap navbar -->
	<nav class="navbar navbar-expand-md navbar-light bg-light shadow sigem-topbar2" style="z-index:1020;">
		<div class="container-fluid">
			<a class="navbar-brand d-flex align-items-center" href="https://sigem-uv.cl/" target="_blank">
				<img src="https://sigem-uv.cl/_general/logos/LOGO%20SIGEM-UV%20HORIZONTAL.png" alt="SIGEM-UV" height="28" style="padding-left:10px;">
			</a>
			<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSIGEMUV" aria-controls="navbarSIGEMUV" aria-expanded="false" aria-label="Toggle navigation">
				<span class="navbar-toggler-icon"></span>
			</button>
			<div class="collapse navbar-collapse" id="navbarSIGEMUV">
				<ul class="navbar-nav ms-auto mb-2 mb-md-0">
					<li class="nav-item">
						<a class="nav-link d-flex align-items-center" href="https://sigem-uv.cl/__v2/#MODULOS" target="_blank"><i class="fa fa-th me-2"></i>Módulos</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="https://sigem-uv.cl/__v2/#PROYECTOS" target="_blank">Proyectos</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="https://sigem-uv.cl/__v2/nosotros.php" target="_blank">Nosotros</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="#footer">Contacto</a>
					</li>
					<li class="nav-item">
						<a class="nav-link" href="./admin_sigem/sigem_login.php?m=CPANEL&link=../endesarrollo.php"><i class="fa fa-cog"></i></a>
					</li>
				</ul>
			</div>
		</div>
	</nav>

	<div class="parametros-page">
		<section class="hero hero-compact">
			<div class="hero-bg"></div>
			<div class="hero-content">
				<div class="hero-tag">MÓDULO EPHDEM</div>
				<h1 class="hero-title">Estudio de Preinversión Hospitalaria</h1>
				<p class="hero-sub">Configura los parámetros por prestación para calcular equipamiento médico.</p>
			</div>
		</section>

		<!-- Tooltip flotante global -->
		<div v-if="tooltipPosicion.visible" class="tooltip-flotante" :class="{ 'tooltip-flotante--left': tooltipPosicion.abrirIzquierda }" :style="{ top: tooltipPosicion.top, left: tooltipPosicion.left }">
			<div class="tooltip-contenido">{{ tooltipPosicion.texto }}</div>
			<div class="tooltip-flecha"></div>
		</div>

		<main class="parametros-content">
			<header class="parametros-header">
				<div class="nav-bar">
					<div class="nav-buttons">
						<button class="btn-back" type="button" @click="volverAtras"><i class="fa-solid fa-arrow-left"></i> Volver</button>
						<button class="btn-back" type="button" @click="router.push('/inicio')"><i class="fa-solid fa-house-user"></i> Inicio</button>
						<div class="nav-divider"></div>
						<button class="btn-back" type="button" @click="irAPrestaciones"><i class="fa-solid fa-list-check"></i> Modificar prestaciones</button>
					</div>
					<div class="session-badge">
						<i class="fa-solid fa-circle-user"></i>
						<span class="session-nombre">{{ authStore.correoUsuario }}</span>
						<button class="btn-logout" type="button" @click="cerrarSesion" title="Cerrar sesión">
							<i class="fa-solid fa-right-from-bracket"></i>
						</button>
					</div>
				</div>
				<div class="parametros-header-top">
					<h2 class="section-title">Parámetros</h2>
					<div class="calculadora-wrapper" ref="calculadoraRef">
						<button
							type="button"
							class="calculadora-toggle"
							:aria-expanded="mostrarCalculadora"
							@click="mostrarCalculadora = !mostrarCalculadora"
						>
							<span class="calculadora-icono" aria-hidden="true"><i class="fa-solid fa-calculator"></i></span>
							<span class="calculadora-texto">Calculadora de días cama para UPC</span>
							<i class="fa-solid fa-chevron-down calculadora-chevron" :class="{ 'is-open': mostrarCalculadora }"></i>
						</button>
						<section class="calculadora-panel" :class="{ 'is-open': mostrarCalculadora }">
							<div v-show="mostrarCalculadora" class="calculadora-contenido">
								<p class="calc-formula-hint">
									<i class="fa-solid fa-circle-info"></i>
									Días cama = (Coef. Técnico × PUAC ÷ 1000) × Promedio Estancia
								</p>
								<div class="calc-form">
									<div class="calc-fields">
										<div class="calc-field">
											<label>
												Coeficiente Técnico
												<span class="info-icon" :data-tooltip="infoTexts.calcCoeficienteTecnico" @mouseenter="mostrarTooltip" @mouseleave="ocultarTooltip">i</span>
											</label>
											<input v-model.number="calc.coeficienteTecnico" type="number" min="0" step="0.001" placeholder="0.000" />
										</div>
										<div class="calc-field">
											<label>
												PUAC <span class="calc-field-hint">(÷ 1000)</span>
												<span class="info-icon" :data-tooltip="infoTexts.calcPuac" @mouseenter="mostrarTooltip" @mouseleave="ocultarTooltip">i</span>
											</label>
											<input v-model.number="calc.puac" type="number" min="0" step="1" placeholder="0" />
										</div>
										<div class="calc-field">
											<label>
												Promedio Estancia (días)
												<span class="info-icon" :data-tooltip="infoTexts.calcPromedioEstancia" @mouseenter="mostrarTooltip" @mouseleave="ocultarTooltip">i</span>
											</label>
											<input v-model.number="calc.promedioEstancia" type="number" min="0" step="0.1" placeholder="0" />
										</div>
									</div>
									<div class="calc-resultado">
										<div class="calc-resultado-label">Días cama</div>
										<div class="calc-resultado-valor">{{ diasCamaCalculados }}</div>
									</div>
								</div>
								<div class="calc-footer">
									<button type="button" class="calc-limpiar" @click="limpiarCalculadora">
										<i class="fa-solid fa-rotate-left"></i> Limpiar
									</button>
								</div>
							</div>
						</section>
					</div>
				</div>
				<div class="proyecto-activo-badge">
					<span class="badge-label">Proyecto en edición</span>
					<span class="badge-name">{{ nombreProyectoActivo }}</span>
				</div>
				<div class="instruccion-indicator">
					<span class="instruccion-icon-circle">
						<i class="fa-solid fa-circle-info"></i>
					</span>
					<span class="instruccion-texto">
						Completa las variables para cada prestación seleccionada. En el símbolo<span class="info-icon info-icon--demo" aria-hidden="true">i</span>
						podrás ver en específico las características de cada parámetro, para el caso de la UPC, puedes usar la calculadora en base a coeficiente técnico para obtener los días cama, si ya conoces ese valor, ingrésalo en la demanda de la prestación <strong>"Día cama"</strong> correspondiente de UPC.
					</span>
				</div>
			</header>

			<section v-if="filas.length === 0" class="panel-vacio">
				<p>No hay prestaciones seleccionadas para parametrizar.</p>
				<button class="btn-secundario" @click="router.push('/prestaciones')">Volver a prestaciones</button>
			</section>

			<section v-else class="tabla-panel">
				<div class="tabla-scroll">
					<table class="tabla-parametros">
						<thead>
							<tr>
								<th>Prestación</th>
								<th>
									Demanda
									<span class="info-icon" :data-tooltip="infoTexts.demanda" @mouseenter="mostrarTooltip" @mouseleave="ocultarTooltip">i</span>
								</th>
								<th>
									Días al año disponibles
									<span class="info-icon" :data-tooltip="infoTexts.diasAnuales" @mouseenter="mostrarTooltip" @mouseleave="ocultarTooltip">i</span>
								</th>
								<th>
									Tiempo de procedimiento (min)
									<span class="info-icon" :data-tooltip="infoTexts.tiempoProcedimiento" @mouseenter="mostrarTooltip" @mouseleave="ocultarTooltip">i</span>
								</th>

								<th>
									Disponibilidad (%)
									<span class="info-icon" :data-tooltip="infoTexts.disponibilidad" @mouseenter="mostrarTooltip" @mouseleave="ocultarTooltip">i</span>
								</th>
								<th>
									Jornada laboral (hrs)
									<span class="info-icon" :data-tooltip="infoTexts.jornadaLaboral" @mouseenter="mostrarTooltip" @mouseleave="ocultarTooltip">i</span>
								</th>
							</tr>
						</thead>
						<tbody>
							<tr v-for="fila in filas" :key="fila.id">
								<td>
									<div class="prestacion-codigo">{{ fila.codigo_fonasa }}</div>
									<div class="prestacion-nombre">{{ fila.nombre_prestacion }}</div>
								</td>
								<td>
									<input v-model.number="fila.demanda" type="number" min="0" step="1" :class="{ 'input-error': erroresCeldas.has(`${fila.id}-demanda`) }" @input="limpiarError(fila.id, 'demanda')" />
								</td>
								<td>
									<input v-model.number="fila.diasAnuales" type="number" min="1" max="366" step="1" :class="{ 'input-error': erroresCeldas.has(`${fila.id}-diasAnuales`) }" @input="limpiarError(fila.id, 'diasAnuales')" />
								</td>
								<td>
									<input v-model.number="fila.tiempoProcedimiento" type="number" min="0" step="0.1" :class="{ 'input-error': erroresCeldas.has(`${fila.id}-tiempoProcedimiento`) }" @input="limpiarError(fila.id, 'tiempoProcedimiento')" />
								</td>
								<td>
									<input v-model.number="fila.disponibilidad" type="number" min="0" max="100" step="0.1" :class="{ 'input-error': erroresCeldas.has(`${fila.id}-disponibilidad`) }" @input="limpiarError(fila.id, 'disponibilidad')" />
								</td>
								<td>
									<input v-model.number="fila.jornadaLaboral" type="number" min="0" max="24" step="0.1" :class="{ 'input-error': erroresCeldas.has(`${fila.id}-jornadaLaboral`) }" @input="limpiarError(fila.id, 'jornadaLaboral')" />
								</td>
							</tr>
						</tbody>
					</table>
				</div>

				<div class="acciones-finales">
					<transition name="fade-error">
						<div v-if="errorValidacion" class="banner-error" role="alert">
							<i class="fa-solid fa-triangle-exclamation"></i>
							{{ errorValidacion }}
						</div>
					</transition>
					<button class="btn-principal" @click="guardarYCalcular">Guardar y calcular</button>
				</div>
			</section>
		</main>
	</div>

	<!-- BOTTOM BAR: Informacion de contacto -->
	<footer class="sigem-bottomline" id="footer">
		<div class="sigem-bottomline-content">
			<div class="sigem-bottomline-left">
				<img src="https://sigem-uv.cl/_general/logos/LOGO_SIGEM-UV_HORIZONTAL-BLANCO.png" alt="SIGEM-UV" height="48" />
			</div>
			<div class="sigem-bottomline-center">
				<div>Gral. Cruz 222, Valparaiso ::: +56 32 2603662</div>
				<div>
					<a href="mailto:contacto@sigem-uv.cl" style="color:#fff">contacto@sigem-uv.cl</a> :::
					<a href="https://biomedica.uv.cl" style="color:#fff" target="_blank">www.biomedica.uv.cl</a>
				</div>
				<div class="sigem-bottomline-social">
					<a title="LinkedIn" href="https://www.linkedin.com" target="_blank"><img src="https://sigem-uv.cl/_general/logos/icons8-linkedin-48.png" alt="LinkedIn" height="28"/></a>
					<a title="Twitter" href="https://www.twitter.com" target="_blank"><img src="https://sigem-uv.cl/_general/logos/icons8-twitterx-50.png" alt="Twitter" height="28"/></a>
					<a title="Instagram" href="https://www.instagram.com" target="_blank"><img src="https://sigem-uv.cl/_general/logos/icons8-instagram-48.png" alt="Instagram" height="28"/></a>
					<a title="Facebook" href="https://www.facebook.com" target="_blank"><img src="https://sigem-uv.cl/_general/logos/icons8-facebook-48.png" alt="Facebook" height="28"/></a>
				</div>
				<div class="sigem-bottomline-copy">&copy; 2026 <b>SIGEM-UV</b> | Todos los Derechos Reservados</div>
			</div>
			<div class="sigem-bottomline-right">
				<img src="https://sigem-uv.cl/_general/logos/uv_blanco.png" alt="UV" height="48" />
			</div>
		</div>
	</footer>
	</div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const PRESTACIONES_STORAGE_KEY = 'ephdem_prestaciones_seleccionadas'
const PARAMETROS_STORAGE_KEY = 'ephdem_parametros_prestaciones'
const MINUTOS_POR_HORA = 60

const infoTexts = {
	demanda: 'Cantidad de atenciones proyectadas para esta prestación en el período.',
	diasAnuales: 'Número de días disponibles al año para operar. Máximo 366 días (año bisiesto). En atención cerrada normalmente 365.',
	tiempoProcedimiento: 'Minutos que dura el procedimiento.',
	disponibilidad: 'Porcentaje de disponibilidad real del equipo para esta prestación.',
	jornadaLaboral: 'Horas efectivas de operación al día. En atención cerrada normalmente 24, pero editable.',
	calcCoeficienteTecnico: 'Número de camas UCI o UTI por cada 1.000 habitantes. Dato propio del establecimiento o estándar ministerial. Ejemplo: 0.020 significa 20 camas por 1.000 hab.',
	calcPuac: 'Población Usuaria Asignada al Centro (PUAC). Total de habitantes que dependen del establecimiento para esta prestación.',
	calcPromedioEstancia: 'Promedio de días que un paciente ocupa una cama. UCI estándar ≈ 5 días, UTI estándar ≈ 8 días.',

}

const filas = ref([])
const tooltipPosicion = ref({ top: '0px', left: '0px', visible: false, texto: '', abrirIzquierda: false })
const erroresCeldas = ref(new Set())
const errorValidacion = ref('')
const nombreProyectoActivo = ref('')

const mostrarCalculadora = ref(false)
const calculadoraRef = ref(null)
const calc = ref({ coeficienteTecnico: null, puac: null, promedioEstancia: null })

const diasCamaCalculados = computed(() => {
	if (calc.value.coeficienteTecnico === null || calc.value.puac === null || calc.value.promedioEstancia === null) return '—'
	const ct = Number(calc.value.coeficienteTecnico)
	const puac = Number(calc.value.puac)
	const pe = Number(calc.value.promedioEstancia)
	if (!Number.isFinite(ct) || !Number.isFinite(puac) || !Number.isFinite(pe)) return '—'
	const result = (ct * puac / 1000) * pe
	return Number.isFinite(result) ? Math.ceil(result) : '—'
})

function mostrarTooltip(event) {
	const span = event.target
	const rect = span.getBoundingClientRect()
	const TOOLTIP_WIDTH = 240
	const MARGEN = 16
	const abrirIzquierda = (rect.left + TOOLTIP_WIDTH + MARGEN) > window.innerWidth
	tooltipPosicion.value = {
		top: `${rect.bottom + 8}px`,
		left: abrirIzquierda ? `${rect.right - TOOLTIP_WIDTH}px` : `${rect.left}px`,
		visible: true,
		texto: span.getAttribute('data-tooltip'),
		abrirIzquierda,
	}
}

function ocultarTooltip() {
	tooltipPosicion.value.visible = false
}

function crearFila(prestacion, parametrosGuardados) {
	const tiempoProcedimientoInicial =
		parametrosGuardados?.tiempoProcedimiento ??
		prestacion.tiempo_procedimiento ??
		prestacion.tiempoProcedimiento ??
		MINUTOS_POR_HORA

	return {
		id: prestacion.id,
		codigo_fonasa: prestacion.codigo_fonasa,
		nombre_prestacion: prestacion.nombre_prestacion,
		demanda: parametrosGuardados?.demanda ?? 0,
		diasAnuales: parametrosGuardados?.diasAnuales ?? 365,
		tiempoProcedimiento: tiempoProcedimientoInicial,
		disponibilidad: parametrosGuardados?.disponibilidad ?? 100,
		jornadaLaboral: parametrosGuardados?.jornadaLaboral ?? 24,
	}
}

function redondear(valor) {
	return Math.round(valor * 100) / 100
}

function numeroSeguro(valor) {
	return Number.isFinite(valor) ? valor : 0
}



function cargarDatos() {
	const rawPrestaciones = localStorage.getItem(PRESTACIONES_STORAGE_KEY)
	if (!rawPrestaciones) return

	const rawParametros = localStorage.getItem(PARAMETROS_STORAGE_KEY)

	try {
		const prestaciones = JSON.parse(rawPrestaciones)
		if (!Array.isArray(prestaciones)) return

		const parametrosGuardados = rawParametros ? JSON.parse(rawParametros) : []
		const parametrosMap = new Map(
			Array.isArray(parametrosGuardados) ? parametrosGuardados.map((item) => [item.id, item]) : [],
		)

		filas.value = prestaciones.map((prestacion) => crearFila(prestacion, parametrosMap.get(prestacion.id)))
	} catch (error) {
		localStorage.removeItem(PRESTACIONES_STORAGE_KEY)
		localStorage.removeItem(PARAMETROS_STORAGE_KEY)
		alert('No se pudieron cargar las prestaciones/parametros guardados.')
		console.error('Error al cargar datos guardados:', error)
	}
}

const CAMPOS_REQUERIDOS = ['demanda', 'diasAnuales', 'tiempoProcedimiento', 'disponibilidad', 'jornadaLaboral']

function validarFilas() {
	const errores = new Set()
	for (const fila of filas.value) {
		for (const campo of CAMPOS_REQUERIDOS) {
			const val = fila[campo]
			if (val === null || val === undefined || val === '' || Number(val) <= 0) {
				errores.add(`${fila.id}-${campo}`)
			}
		}
		if (Number(fila.diasAnuales) > 366) {
			errores.add(`${fila.id}-diasAnuales`)
		}
		if (Number(fila.disponibilidad) > 100) {
			errores.add(`${fila.id}-disponibilidad`)
		}
		if (Number(fila.jornadaLaboral) > 24) {
			errores.add(`${fila.id}-jornadaLaboral`)
		}
	}
	erroresCeldas.value = errores
	return errores.size === 0
}

function limpiarError(id, campo) {
	erroresCeldas.value.delete(`${id}-${campo}`)
	erroresCeldas.value = new Set(erroresCeldas.value)
	if (erroresCeldas.value.size === 0) errorValidacion.value = ''
}

async function guardarYCalcular() {
	if (!validarFilas()) {
		errorValidacion.value = 'Hay celdas vacías, con valor 0, o con valores fuera de rango (días al año no puede superar 366). Revisa los campos marcados en rojo.'
		return
	}
	errorValidacion.value = ''

	// Leer el id del proyecto activo (guardado al crear el proyecto o de la ruta)
	const idProyectoActual = route.params.proyectoId || localStorage.getItem('ephdem_proyecto_activo')
	if (!idProyectoActual) {
		alert('No hay un proyecto activo. Vuelve a crear o seleccionar un proyecto.')
		router.push('/crear-proyecto')
		return
	}

	// Guardar parámetros en localStorage como respaldo (igual que antes)
	localStorage.setItem(PARAMETROS_STORAGE_KEY, JSON.stringify(filas.value))

	// Guardar los valores de tiempo_procedimiento modificados por el usuario,
	// asociados a este proyecto (el backend no los persiste, los guardamos en localStorage)
	const tpOverrides = {}
	for (const fila of filas.value) {
		tpOverrides[fila.id] = fila.tiempoProcedimiento
	}
	localStorage.setItem(`ephdem_tp_overrides_${idProyectoActual}`, JSON.stringify(tpOverrides))

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
		// Usamos VITE_API_BASE para que funcione tanto en local como en producción
		const url = `${import.meta.env.VITE_API_BASE}/calcular_demanda.php`
		const resp = await fetch(url, {
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

function volverAtras() {
	router.back()
	setTimeout(() => {
		window.scrollTo({ top: 0, left: 0, behavior: 'auto' })
	}, 0)
}

function irAPrestaciones() {
	// Guardar los parametros actuales para no perder el progreso si el usuario vuelve
	localStorage.setItem(PARAMETROS_STORAGE_KEY, JSON.stringify(filas.value))
	if (route.params.proyectoId) {
		router.push(`/prestaciones/${route.params.proyectoId}`)
	} else {
		router.push('/prestaciones')
	}
}

function cerrarCalculadoraSiCorresponde(event) {
	const contenedor = calculadoraRef.value
	if (!contenedor || !mostrarCalculadora.value) return
	if (!contenedor.contains(event.target)) mostrarCalculadora.value = false
}

function limpiarCalculadora() {
	calc.value = { coeficienteTecnico: null, puac: null, promedioEstancia: null }
}

async function cargarDesdeServidor(proyectoId) {
	try {
		// Cargar en paralelo: datos del proyecto + lista base de prestaciones (para obtener tiempo_procedimiento)
		const [respDemanda, respPrestaciones] = await Promise.all([
			fetch(`${import.meta.env.VITE_API_BASE}/get_prestaciones_demanda.php?proyecto_id=${proyectoId}`),
			fetch(`${import.meta.env.VITE_API_BASE}/get_prestaciones.php`),
		])
		const json = await respDemanda.json()
		const jsonPrestaciones = await respPrestaciones.json()

		if (!respDemanda.ok || !json.ok) {
			alert(json.error || 'Error al cargar datos del proyecto.')
			router.push('/proyectos')
			return
		}

		// Construir mapa de tiempo_procedimiento base (desde la tabla de prestaciones)
		const tiempoMap = new Map()
		if (jsonPrestaciones?.ok && Array.isArray(jsonPrestaciones?.datos)) {
			for (const p of jsonPrestaciones.datos) {
				if (p.id_prestacion != null && p.tiempo_procedimiento != null) {
					tiempoMap.set(p.id_prestacion, p.tiempo_procedimiento)
				}
			}
		}

		// Recuperar los overrides que el usuario guardó previamente para este proyecto
		let tpOverrides = {}
		try {
			const rawOverrides = localStorage.getItem(`ephdem_tp_overrides_${proyectoId}`)
			if (rawOverrides) tpOverrides = JSON.parse(rawOverrides)
		} catch (_) { /* si falla el parse, ignorar */ }

		filas.value = json.datos.map((item) => {
			const vals = item.valores
			const defs = item.defaults || {}
			// Prioridad: override guardado por el usuario → valor base de la tabla → fallback 60
			const tiempoBase = tiempoMap.get(item.id_prestacion) ?? MINUTOS_POR_HORA
			const tiempoProcedimiento = tpOverrides[item.id_prestacion] ?? tiempoBase

			return {
				id: item.id_prestacion,
				codigo_fonasa: item.codigo_fonasa || '',
				nombre_prestacion: item.nombre_prestacion,
				demanda: vals?.demanda_anual ?? 0,
				diasAnuales: vals ? vals.dias_laborales : (defs.dias_laborales ?? 365),
				tiempoProcedimiento,
				disponibilidad: vals ? (vals.disponibilidad * 100) : (defs.disponibilidad ? defs.disponibilidad * 100 : 100),
				jornadaLaboral: vals ? vals.jornada_efectiva : (defs.jornada_efectiva ?? 24)
			}
		})
	} catch (e) {
		console.error(e)
		alert('Error de red al intentar cargar datos del proyecto.')
	}
}

onMounted(() => {
	nombreProyectoActivo.value = localStorage.getItem('ephdem_nombre_proyecto_activo') || 'Desconocido'
	
	if (route.params.proyectoId) {
		if (localStorage.getItem('ephdem_origen_edicion') === 'prestaciones') {
			localStorage.removeItem('ephdem_origen_edicion')
			cargarDatos()
		} else {
			cargarDesdeServidor(route.params.proyectoId)
		}
	} else {
		cargarDatos()
	}
	
	document.addEventListener('pointerdown', cerrarCalculadoraSiCorresponde)
})

onBeforeUnmount(() => {
	document.removeEventListener('pointerdown', cerrarCalculadoraSiCorresponde)
})

function cerrarSesion() {
	authStore.logout()
	router.push('/login')
}
</script>

<style lang="scss" scoped>
@import '@/assets/styles/variables';

.sigem-topbar1 {
	width: 100%;
	background: #003c58;
	display: flex;
	align-items: center;
	justify-content: center;
	position: relative;
	padding: 10px 0 10px 0;
	min-height: 56px;
	z-index: 1001;
}
.sigem-topbar1-center {
	display: flex;
	align-items: center;
	gap: 32px;
	position: absolute;
	left: 50%;
	transform: translateX(-50%);
}
.sigem-topbar1-logo {
	opacity: 0.8;
	transition: opacity 0.2s;
	&:hover { opacity: 1; }
}
.sigem-topbar1-acceder {
	color: #fff;
	font-weight: 500;
	text-decoration: none;
	font-size: 1.08rem;
	margin-right: 32px;
	cursor: pointer;
	position: absolute;
	right: 0;
	background: none !important;
	border: none !important;
	padding: 0 !important;
	border-radius: 0 !important;
	transition: none !important;
}

.sigem-topbar2 {
	position: sticky;
	top: 0;
	width: 100%;
	background: $color-blanco;
	box-shadow: 0 2px 8px rgba(0,0,0,0.15);
	z-index: 1000;
}

.hero {
	background: $color-secundario;
	position: relative;
	padding: 38px 48px;
	overflow: hidden;
	text-align: center;
}
.hero-compact {
	padding: 28px 48px;
}
.hero-bg {
	position: absolute;
	inset: 0;
	background: url('@/assets/img/mac.jpg') center/cover no-repeat;
	opacity: 1;
	filter: none;
}
.hero-content {
	position: relative;
	z-index: 2;
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 10px;
}
.hero-tag {
	font-size: 12px;
	color: rgba($color-blanco, 0.35);
	letter-spacing: 2.5px;
	text-transform: uppercase;
}
.hero-title {
	font-size: 26px;
	font-weight: 500;
	color: $color-blanco;
	line-height: 1.25;
	margin: 0;
}
.hero-sub {
	font-size: 14px;
	color: rgba($color-blanco, 0.6);
	max-width: 700px;
	line-height: 1.5;
	margin: 0;
}

.page-layout {
	min-height: 100vh;
	flex: 1;
	display: flex;
	flex-direction: column;
}
.parametros-page {
	background: $color-fondo;
	flex: 1;
}
.parametros-content {
	max-width: 1480px;
	margin: 32px auto 72px auto;
	padding: 0 20px;
	display: flex;
	flex-direction: column;
	gap: 24px;
}
.nav-bar {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
	margin-bottom: 10px;
}
.nav-buttons {
	display: flex;
	gap: 12px;
	align-items: center;
}
.nav-divider {
	width: 1px;
	height: 24px;
	background-color: #cbd5e1;
	margin: 0 4px;
}
.session-badge {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 6px 14px 6px 10px;
	background: rgba(0, 60, 88, 0.06);
	border: 1.5px solid rgba(0, 60, 88, 0.18);
	border-radius: 999px;
	color: $color-primario;
	font-size: 0.88rem;
	font-weight: 600;
	i { font-size: 1rem; }
}
.session-nombre {
	white-space: nowrap;
}
.btn-logout {
	background: none;
	border: none;
	color: $color-primario;
	cursor: pointer;
	padding: 2px 4px;
	font-size: 0.95rem;
	opacity: 0.7;
	transition: opacity 0.2s, color 0.2s;
	&:hover { opacity: 1; color: #c62828; }
}
.btn-back {
	align-self: flex-start;
	background: $color-primario;
	color: $color-blanco;
	border: 1px solid $color-primario;
	border-radius: 999px;
	padding: 6px 12px;
	font-weight: 600;
	cursor: pointer;
	margin-bottom: 10px;
	transition: background 0.2s ease, border 0.2s ease;

	&:hover {
		background: mix($color-blanco, $color-primario, 6%);
		border-color: mix($color-blanco, $color-primario, 6%);
	}
}
.section-title {
	font-size: 1.6rem;
	font-weight: 700;
	color: $color-primario;
	margin: 0 0 6px 0;
}
.instruccion-indicator {
	display: flex;
	align-items: center;
	gap: 10px;
	margin-top: 6px;
	background: rgba(0, 60, 88, 0.06);
	border: 1px solid rgba(0, 60, 88, 0.14);
	border-radius: 10px;
	padding: 10px 16px;
}
.proyecto-activo-badge {
	display: inline-flex;
	align-items: center;
	align-self: flex-start;
	background: rgba(0, 60, 88, 0.05);
	border-radius: 6px;
	padding: 6px 12px;
	margin-top: 6px;
	border: 1px solid rgba(0, 60, 88, 0.1);
}
.badge-label {
	font-size: 0.75rem;
	font-weight: 600;
	text-transform: uppercase;
	color: rgba(0, 60, 88, 0.6);
	margin-right: 8px;
	letter-spacing: 0.5px;
}
.badge-name {
	font-size: 0.95rem;
	font-weight: 700;
	color: $color-primario;
}
.instruccion-icon-circle {
	display: flex;
	align-items: center;
	justify-content: center;
	color: $color-primario;
	font-size: 1.4rem;
	flex: 0 0 auto;
}

.instruccion-texto {
	font-size: 1.05rem;
	color: $color-primario;
	line-height: 1.8;

	strong {
		font-weight: 700;
	}
}

.info-icon--demo {
	vertical-align: middle;
	pointer-events: none;
	cursor: default;
}

.panel-vacio,
.tabla-panel {
	background: $color-blanco;
	border-radius: 16px;
	padding: 20px;
	border: 1px solid $color-borde;
	box-shadow: 0 10px 22px $color-sombra-suave;
	overflow: visible;
}
.panel-vacio {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 14px;
}

.tabla-scroll {
	overflow-x: auto;
	overflow-y: visible;
}
.tabla-parametros {
	width: 100%;
	min-width: 1200px;
	border-collapse: separate;
	border-spacing: 0;
}
.tabla-parametros th,
.tabla-parametros td {
	padding: 12px 10px;
	border-bottom: 1px solid $color-borde;
	text-align: left;
	vertical-align: middle;
}
.tabla-parametros th {
	background: #e9f1f6;
	color: $color-primario;
	font-size: 0.9rem;
	font-weight: 700;
	position: sticky;
	top: 0;
}
.tabla-parametros td input {
	width: 100%;
	padding: 8px 10px;
	border: 1px solid $color-borde;
	border-radius: 8px;
	background: $color-blanco;
	font-weight: 500;
	color: $color-texto-principal;
	transition: border-color 0.2s ease, box-shadow 0.2s ease;
}
.tabla-parametros td input.input-error {
	border-color: #e53935;
	background: #fff5f5;
	box-shadow: 0 0 0 2px rgba(229, 57, 53, 0.18);
	animation: shake 0.35s ease;
}

@keyframes shake {
	0%   { transform: translateX(0); }
	20%  { transform: translateX(-5px); }
	40%  { transform: translateX(5px); }
	60%  { transform: translateX(-4px); }
	80%  { transform: translateX(4px); }
	100% { transform: translateX(0); }
}

.banner-error {
	display: flex;
	align-items: center;
	gap: 10px;
	background: #fff5f5;
	border: 1.5px solid #e53935;
	color: #c62828;
	border-radius: 10px;
	padding: 10px 16px;
	font-size: 0.88rem;
	font-weight: 600;
	width: 100%;
	max-width: 600px;

	i { font-size: 1rem; flex-shrink: 0; }
}

.fade-error-enter-active,
.fade-error-leave-active {
	transition: opacity 0.25s ease, transform 0.25s ease;
}
.fade-error-enter-from,
.fade-error-leave-to {
	opacity: 0;
	transform: translateY(-6px);
}

.prestacion-codigo {
	font-size: 0.85rem;
	font-weight: 700;
	color: $color-primario;
}
.prestacion-nombre {
	font-size: 0.95rem;
	font-weight: 500;
	color: $color-texto-principal;
}
.info-icon {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 16px;
	height: 16px;
	margin-left: 6px;
	border-radius: 50%;
	background: rgba(0, 60, 88, 0.14);
	color: $color-primario;
	font-size: 0.72rem;
	font-weight: 700;
	cursor: help;
}

.tooltip-flotante {
	position: fixed;
	z-index: 10000;
	pointer-events: none;
}

.tooltip-contenido {
	width: 240px;
	padding: 10px 14px;
	background: $color-primario;
	color: $color-blanco;
	font-size: 0.85rem;
	font-weight: 500;
	border-radius: 8px;
	text-align: center;
	line-height: 1.5;
	box-shadow: 0 6px 16px rgba(0, 0, 0, 0.2);
	word-wrap: break-word;
	border: 3px solid rgba(255, 255, 255, 0.25);
	outline: 2px solid rgba(255, 255, 255, 0.12);
}

.tooltip-flecha {
	position: absolute;
	top: -7px;
	left: 7px;
	width: 0;
	height: 0;
	border-left: 7px solid transparent;
	border-right: 7px solid transparent;
	border-bottom: 7px solid $color-primario;
}

.tooltip-flotante--left .tooltip-flecha {
	left: auto;
	right: 7px;
}

.acciones-finales {
	margin-top: 16px;
	display: flex;
	flex-direction: column;
	align-items: flex-end;
	gap: 10px;
}
.btn-principal,
.btn-secundario {
	border: none;
	border-radius: 10px;
	padding: 10px 18px;
	font-weight: 700;
	cursor: pointer;
}
.btn-principal {
	background: $color-primario;
	color: $color-blanco;
}
.btn-secundario {
	background: rgba(0, 60, 88, 0.12);
	color: $color-primario;
}

.sigem-bottomline {
	width: 100%;
	background: #003c58;
	color: #fff;
	padding: 0;
	margin-top: 48px;
}
.sigem-bottomline-content {
	display: flex;
	align-items: center;
	justify-content: space-between;
	max-width: 1400px;
	margin: 0 auto;
	padding: 12px 24px;
	flex-wrap: wrap;
}
.sigem-bottomline-left,
.sigem-bottomline-right {
	flex: 0 0 auto;
	display: flex;
	align-items: center;
}
.sigem-bottomline-center {
	flex: 1 1 400px;
	text-align: center;
	font-size: 1.05rem;
}
.sigem-bottomline-social {
	margin: 8px 0;
	display: flex;
	gap: 10px;
	justify-content: center;
}
.sigem-bottomline-copy {
	font-size: 0.95rem;
	opacity: 0.8;
	margin-top: 4px;
}

@media (max-width: 980px) {
	.panel-vacio {
		flex-direction: column;
		align-items: flex-start;
	}
}

// --- CALCULADORA DE DÍAS CAMA ---
.parametros-header-top {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 16px;
}

.calculadora-wrapper {
	position: relative;
	display: flex;
	flex-direction: column;
	align-items: flex-end;
	flex: 0 0 auto;
}

.calculadora-toggle {
	display: flex;
	align-items: center;
	gap: 10px;
	padding: 10px 16px;
	border: 1.5px solid $color-primario;
	border-radius: 999px;
	background: rgba(0, 60, 88, 0.06);
	color: $color-primario;
	font-weight: 700;
	font-size: 0.93rem;
	cursor: pointer;
	white-space: nowrap;
	transition: background 0.2s ease, box-shadow 0.2s ease;

	&:hover {
		background: rgba(0, 60, 88, 0.12);
		box-shadow: 0 2px 8px rgba(0, 60, 88, 0.12);
	}
}

.calculadora-icono {
	font-size: 1.05rem;
}

.calculadora-chevron {
	font-size: 0.8rem;
	transition: transform 0.25s ease;

	&.is-open {
		transform: rotate(180deg);
	}
}

.calculadora-panel {
	position: absolute;
	top: calc(100% + 10px);
	right: 0;
	width: min(620px, 100vw - 32px);
	z-index: 100;
	pointer-events: none;

	&.is-open {
		pointer-events: auto;
	}
}

.calculadora-contenido {
	background: $color-blanco;
	border: 1.5px solid $color-primario;
	border-radius: 16px;
	padding: 20px;
	box-shadow: 0 8px 28px rgba(0, 60, 88, 0.14);
	display: flex;
	flex-direction: column;
	gap: 16px;
}

.calc-formula-hint {
	margin: 0;
	font-size: 0.82rem;
	color: $color-primario;
	background: rgba(0, 60, 88, 0.07);
	padding: 8px 12px;
	border-radius: 8px;
	display: flex;
	align-items: center;
	gap: 8px;
	font-weight: 600;
}

.calc-form {
	display: flex;
	align-items: flex-end;
	gap: 16px;
}

.calc-fields {
	display: grid;
	grid-template-columns: repeat(3, 1fr);
	gap: 12px;
	flex: 1 1 auto;
}

.calc-field {
	display: flex;
	flex-direction: column;
	justify-content: flex-end;
	gap: 4px;

	label {
		font-size: 0.75rem;
		font-weight: 700;
		color: $color-primario;
		display: flex;
		align-items: center;
		gap: 4px;
		white-space: nowrap;
	}

	input {
		padding: 8px 10px;
		border: 1.5px solid $color-borde;
		border-radius: 8px;
		font-size: 0.95rem;
		font-weight: 600;
		color: $color-texto-principal;
		width: 100%;
		transition: border-color 0.2s ease;

		&:focus {
			outline: none;
			border-color: $color-primario;
		}
	}
}

.calc-field-hint {
	font-size: 0.72rem;
	color: $color-texto-secundario;
	font-weight: 600;
}

.calc-resultado {
	display: flex;
	flex-direction: column;
	align-items: center;
	gap: 4px;
	background: $color-primario;
	border-radius: 12px;
	padding: 10px 20px;
	min-width: 110px;
}

.calc-resultado-label {
	font-size: 0.72rem;
	font-weight: 700;
	color: rgba(255, 255, 255, 0.7);
	white-space: nowrap;
}

.calc-resultado-valor {
	font-size: 1.6rem;
	font-weight: 800;
	color: $color-blanco;
	letter-spacing: -0.5px;
}

.calc-footer {
	display: flex;
	justify-content: flex-end;
}

.calc-limpiar {
	background: none;
	border: 1px solid $color-borde;
	color: $color-texto-secundario;
	border-radius: 8px;
	padding: 6px 12px;
	font-size: 0.82rem;
	font-weight: 600;
	cursor: pointer;
	display: flex;
	align-items: center;
	gap: 6px;
	transition: background 0.2s ease, color 0.2s ease, border-color 0.2s ease;

	&:hover {
		background: rgba(0, 60, 88, 0.06);
		color: $color-primario;
		border-color: $color-primario;
	}
}
</style>
