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

	<div class="prestaciones-page">
		<!-- HERO COMPACTO -->
		<section class="hero hero-compact">
			<div class="hero-bg"></div>
			<div class="hero-content">
				<div class="hero-tag">MÓDULO EPHDEM</div>
				<h1 class="hero-title">Estudio de Preinversión Hospitalaria</h1>
				<p class="hero-sub">Herramienta para la estimación de equipamiento médico necesario para satisfacer una demanda proyectada de prestaciones.</p>
			</div>
		</section>

		<main class="prestaciones-content">
			<header class="prestaciones-header">
				<div class="nav-bar">
					<div class="nav-buttons">
						<button class="btn-back" type="button" @click="volverAtras"><i class="fa-solid fa-arrow-left"></i> Volver</button>
						<button class="btn-back" type="button" @click="router.push('/inicio')"><i class="fa-solid fa-house-user"></i> Inicio</button>
					</div>
					<div class="session-badge">
						<i class="fa-solid fa-circle-user"></i>
						<span class="session-nombre">{{ authStore.correoUsuario }}</span>
						<button class="btn-logout" type="button" @click="cerrarSesion" title="Cerrar sesión">
							<i class="fa-solid fa-right-from-bracket"></i>
						</button>
					</div>
				</div>
				<div class="prestaciones-header-top">
					<h2 class="section-title">Prestaciones</h2>
					<div ref="consideracionesRef" class="consideraciones-wrapper">
					<button
						type="button"
						class="consideraciones-toggle consideraciones-toggle-header"
						:aria-expanded="mostrarConsideraciones"
						aria-controls="consideraciones-contenido"
						@click="mostrarConsideraciones = !mostrarConsideraciones"
					>
						<span class="consideraciones-icono" aria-hidden="true">
							<i class="fa-solid fa-triangle-exclamation"></i>
						</span>
						<span class="consideraciones-texto">Consideraciones de uso</span>
					</button>
						<section class="consideraciones-panel consideraciones-panel-header" :class="{ 'is-open': mostrarConsideraciones }">
							<div v-show="mostrarConsideraciones" id="consideraciones-contenido" class="consideraciones-contenido">
								<ol>
									<li>Antes de seleccionar alguna prestación de UPC ya sea de UTI o UCI, selecciona las prestaciones "Día Cama de Hospitalización Integral Adulto en Unidad de Cuidado Intensivo (U.C.I.)" o "Día Cama de Hospitalización Integral Adulto en Unidad de Tratamiento Intermedio (U.T.I.)" según corresponda, esto es necesario para que el modelo pueda calcular la cantidad de módulos necesarios para la proyección.</li>
									
								</ol>
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
					Selecciona las prestaciones que se asociarán al proyecto: usa
					<span class="instruccion-badge instruccion-badge--agregar"><i class="fa-solid fa-plus"></i></span>
					para agregar y
					<span class="instruccion-badge instruccion-badge--quitar"><i class="fa-solid fa-xmark"></i></span>
					para eliminar de la selección. Luego presiona <strong>Guardar y confirmar</strong> para guardar la selección.
				</span>
				</div>
			</header>

			<section class="filtros-panel">
				<div class="filtro filtro-buscar">
					<label>Buscar</label>
					<input v-model="filtros.texto" type="text" placeholder="Código o nombre" />
				</div>
				<div class="filtro">
					<label>Área</label>
					<select v-model="filtros.area">
						<option value="">Todas</option>
						<option v-for="area in opcionesArea" :key="area" :value="area">{{ area }}</option>
					</select>
				</div>
				<div class="filtro">
					<label>Subárea</label>
					<select v-model="filtros.subarea">
						<option value="">Todas</option>
						<option v-for="subarea in opcionesSubarea" :key="subarea" :value="subarea">{{ subarea }}</option>
					</select>
				</div>
				<div class="filtro">
					<label>Recinto</label>
					<select v-model="filtros.recinto">
						<option value="">Todos</option>
						<option v-for="recinto in opcionesRecinto" :key="recinto" :value="recinto">{{ recinto }}</option>
					</select>
				</div>
			</section>

			<section class="prestaciones-grid">
				<div class="prestaciones-panel">
					<div class="panel-title">Disponibles</div>
					<div v-if="isLoading" class="lista-vacia">Cargando prestaciones...</div>
					<div v-else-if="loadError" class="lista-vacia">{{ loadError }}</div>
					<div v-else-if="prestacionesFiltradas.length === 0" class="lista-vacia">No hay prestaciones disponibles.</div>
					<div v-else class="prestaciones-lista">
						<div v-for="prestacion in prestacionesFiltradas" :key="prestacion.id" class="prestacion-item">
							<div class="prestacion-info">
								<div class="prestacion-codigo">{{ prestacion.codigo_fonasa }}</div>
								<div class="prestacion-nombre">{{ prestacion.nombre_prestacion }}</div>
							</div>
								<button class="accion accion-agregar" @click="agregarPrestacion(prestacion)"><i class="fa-solid fa-plus"></i></button>
						</div>
					</div>
				</div>

				<div class="prestaciones-panel">
					<div class="panel-header-seleccionadas">
						<div class="panel-title">Seleccionadas</div>
						<button
							type="button"
							class="btn-limpiar-seleccion"
							:disabled="prestacionesSeleccionadas.length === 0"
							@click="limpiarSeleccion"
						>
							<span class="limpiar-icono" aria-hidden="true">
								<i class="fa-solid fa-trash-can"></i>
							</span>
							<span class="limpiar-texto">Limpiar selección</span>
						</button>
					</div>
					<div v-if="prestacionesSeleccionadas.length === 0" class="lista-vacia">Aún no has seleccionado prestaciones.</div>
					<div v-else class="prestaciones-lista">
						<div v-for="prestacion in prestacionesSeleccionadas" :key="prestacion.id" class="prestacion-item">
							<div class="prestacion-info">
								<div class="prestacion-codigo">{{ prestacion.codigo_fonasa }}</div>
								<div class="prestacion-nombre">{{ prestacion.nombre_prestacion }}</div>
							</div>
								<button class="accion accion-quitar" @click="quitarPrestacion(prestacion)"><i class="fa-solid fa-xmark"></i></button>
						</div>
					</div>
				</div>
			</section>

			<section class="acciones-finales">
				<div class="acciones-resumen">
					Prestaciones seleccionadas: <b>{{ prestacionesSeleccionadas.length }}</b>
				</div>
				<button class="btn-confirmar" :disabled="prestacionesSeleccionadas.length === 0" @click="guardarYConfirmar">
					Guardar y confirmar
				</button>
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
import { computed, onMounted, onBeforeUnmount, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const STORAGE_KEY = 'ephdem_prestaciones_seleccionadas'
const PRESTACIONES_URL = `${import.meta.env.VITE_API_BASE}/get_prestaciones.php`
const PRESTACIONES_PRIORITARIAS = [
	'Día Cama de Hospitalización Integral Adulto en Unidad de Cuidado Intensivo (U.C.I.)',
	'Día Cama de Hospitalización Integral Adulto en Unidad de Tratamiento Intermedio (U.T.I.)',
]

const filtros = ref({
	area: '',
	texto: '',
	subarea: '',
	recinto: '',
})

const prestaciones = ref([])
const isLoading = ref(false)
const loadError = ref('')
const mostrarConsideraciones = ref(false)
const consideracionesRef = ref(null)

const seleccionadas = ref([])
const nombreProyectoActivo = ref('')

function normalizarTexto(valor) {
	return String(valor ?? '')
		.normalize('NFD')
		.replace(/[\u0300-\u036f]/g, '')
		.toLowerCase()
		.trim()
}

const opcionesArea = computed(() => {
	return [...new Set(prestaciones.value.map((p) => p.area))]
})

const opcionesSubarea = computed(() => {
	const base = filtros.value.area
		? prestaciones.value.filter((p) => p.area === filtros.value.area)
		: prestaciones.value
	return [...new Set(base.map((p) => p.subarea))]
})

const opcionesRecinto = computed(() => {
	const base = prestaciones.value.filter((p) => {
		if (filtros.value.area && p.area !== filtros.value.area) return false
		if (filtros.value.subarea && p.subarea !== filtros.value.subarea) return false
		return true
	})
	return [...new Set(base.map((p) => p.recinto))]
})

const prestacionesFiltradas = computed(() => {
	const seleccionadasIds = new Set(seleccionadas.value.map((p) => p.id))
	const texto = normalizarTexto(filtros.value.texto)
	return prestaciones.value.filter((p) => {
		if (seleccionadasIds.has(p.id)) return false
		if (filtros.value.area && p.area !== filtros.value.area) return false
		if (filtros.value.subarea && p.subarea !== filtros.value.subarea) return false
		if (filtros.value.recinto && p.recinto !== filtros.value.recinto) return false
		if (texto) {
			const codigo = normalizarTexto(p.codigo_fonasa)
			const nombre = normalizarTexto(p.nombre_prestacion)
			if (!codigo.includes(texto) && !nombre.includes(texto)) return false
		}
		return true
	}).sort((a, b) => {
		const prioridadA = PRESTACIONES_PRIORITARIAS.findIndex((nombre) => normalizarTexto(nombre) === normalizarTexto(a.nombre_prestacion))
		const prioridadB = PRESTACIONES_PRIORITARIAS.findIndex((nombre) => normalizarTexto(nombre) === normalizarTexto(b.nombre_prestacion))

		if (prioridadA !== prioridadB) {
			if (prioridadA === -1) return 1
			if (prioridadB === -1) return -1
			return prioridadA - prioridadB
		}

		return normalizarTexto(a.nombre_prestacion).localeCompare(normalizarTexto(b.nombre_prestacion), 'es', {
			sensitivity: 'base',
		})
	})
})

const prestacionesSeleccionadas = computed(() => {
	return seleccionadas.value
})

onMounted(async () => {
	nombreProyectoActivo.value = localStorage.getItem('ephdem_nombre_proyecto_activo') || 'Desconocido'
	await cargarPrestaciones()
	
	if (route.params.proyectoId) {
		await cargarDesdeServidor(route.params.proyectoId)
	} else {
		cargarSeleccionadas()
	}
})

async function cargarDesdeServidor(proyectoId) {
	try {
		// Cargar en paralelo: datos del proyecto + lista base de prestaciones (para obtener tiempo_procedimiento)
		const [resp, respPrestacionesBase] = await Promise.all([
			fetch(`${import.meta.env.VITE_API_BASE}/get_prestaciones_demanda.php?proyecto_id=${proyectoId}`),
			fetch(`${import.meta.env.VITE_API_BASE}/get_prestaciones.php`),
		])
		const json = await resp.json()
		const jsonBase = await respPrestacionesBase.json()

		if (!resp.ok || !json.ok) {
			alert(json.error || 'Error al cargar datos del proyecto.')
			return
		}

		// Construir mapa de tiempo_procedimiento base (desde la tabla de prestaciones)
		const tiempoMap = new Map()
		if (jsonBase?.ok && Array.isArray(jsonBase?.datos)) {
			for (const p of jsonBase.datos) {
				if (p.id_prestacion != null && p.tiempo_procedimiento != null) {
					tiempoMap.set(p.id_prestacion, p.tiempo_procedimiento)
				}
			}
		}

		// Recuperar los overrides de tiempo_procedimiento que el usuario guardó previamente para este proyecto
		// Prioridad: override del usuario > valor base de tabla > 60
		let tpOverrides = {}
		try {
			const rawOverrides = localStorage.getItem(`ephdem_tp_overrides_${proyectoId}`)
			if (rawOverrides) tpOverrides = JSON.parse(rawOverrides)
		} catch (_) { /* si falla el parse, ignorar */ }

		const idsGuardados = new Set(json.datos.map((item) => item.id_prestacion))
		seleccionadas.value = prestaciones.value.filter((p) => idsGuardados.has(p.id))

		const params = json.datos.map((item) => ({
			id: item.id_prestacion,
			demanda: item.valores?.demanda_anual ?? 0,
			diasAnuales: item.valores?.dias_laborales ?? item.defaults?.dias_laborales ?? 365,
			disponibilidad: item.valores ? (item.valores.disponibilidad * 100) : (item.defaults?.disponibilidad ? item.defaults.disponibilidad * 100 : 100),
			jornadaLaboral: item.valores?.jornada_efectiva ?? item.defaults?.jornada_efectiva ?? 24,
			// Si el usuario ya modificó este valor previamente, conservarlo; si no, usar el de la tabla base
			tiempoProcedimiento: tpOverrides[item.id_prestacion] ?? tiempoMap.get(item.id_prestacion) ?? 60
		}))
		localStorage.setItem('ephdem_parametros_prestaciones', JSON.stringify(params))
		localStorage.setItem('ephdem_origen_edicion', 'prestaciones')
	} catch (e) {
		console.error('Error al cargar prestaciones del proyecto:', e)
		alert('Error de red al intentar cargar datos del proyecto.')
	}
}

async function cargarPrestaciones() {
	isLoading.value = true
	loadError.value = ''

	try {
		const response = await fetch(PRESTACIONES_URL, {
			method: 'GET',
			credentials: 'same-origin',
		})
		if (!response.ok) {
			throw new Error('No se pudieron cargar las prestaciones.')
		}

		const payload = await response.json()
		if (!payload?.ok) {
			throw new Error(payload?.error || 'Error al cargar prestaciones.')
		}

		const rawPrestaciones = Array.isArray(payload?.datos)
			? payload.datos
			: Array.isArray(payload?.datos?.prestaciones)
				? payload.datos.prestaciones
				: []

		prestaciones.value = rawPrestaciones
			.map((item) => ({
				id: item?.id_prestacion ?? item?.id,
				codigo_fonasa: item?.codigo_fonasa ?? '',
				nombre_prestacion: item?.nombre_prestacion ?? '',
				tiempo_procedimiento: item?.tiempo_procedimiento ?? item?.tiempoProcedimiento ?? '',
				area: item?.area_hospitalaria ?? item?.area ?? '',
				subarea: item?.subarea_hospitalaria ?? item?.subarea ?? '',
				recinto: item?.nombre_recinto ?? item?.recinto ?? '',
			}))
			.filter((item) => item.id != null)
	} catch (error) {
		console.error('Error al cargar prestaciones:', error)
		loadError.value = 'No se pudieron cargar las prestaciones. Intenta nuevamente.'
		prestaciones.value = []
	} finally {
		isLoading.value = false
	}
}

function cargarSeleccionadas() {
	const raw = localStorage.getItem(STORAGE_KEY)
	if (!raw) return

	try {
		const guardadas = JSON.parse(raw)
		if (!Array.isArray(guardadas)) return
		const idsGuardados = new Set(guardadas.map((item) => item.id))
		seleccionadas.value = prestaciones.value.filter((p) => idsGuardados.has(p.id))
	} catch (error) {
		localStorage.removeItem(STORAGE_KEY)
		alert('No se pudieron cargar las prestaciones guardadas previamente.')
		console.error('Error al leer prestaciones guardadas:', error)
	}
}

function agregarPrestacion(prestacion) {
	if (seleccionadas.value.find((p) => p.id === prestacion.id)) return
	seleccionadas.value.push(prestacion)
}

function quitarPrestacion(prestacion) {
	seleccionadas.value = seleccionadas.value.filter((p) => p.id !== prestacion.id)
}

function limpiarSeleccion() {
	seleccionadas.value = []
}

function guardarYConfirmar() {
	if (seleccionadas.value.length === 0) {
		alert('Debes seleccionar al menos una prestacion.')
		return
	}
	localStorage.setItem(STORAGE_KEY, JSON.stringify(seleccionadas.value))
	if (route.params.proyectoId) {
		router.push(`/parametros/${route.params.proyectoId}`)
	} else {
		router.push('/parametros')
	}
}

function volverAtras() {
	router.back()
}

function cerrarConsideracionesSiCorresponde(event) {
	const contenedor = consideracionesRef.value
	if (!contenedor) return
	if (!mostrarConsideraciones.value) return
	if (contenedor.contains(event.target)) return
	mostrarConsideraciones.value = false
}

onMounted(() => {
	document.addEventListener('pointerdown', cerrarConsideracionesSiCorresponde)
})

onBeforeUnmount(() => {
	document.removeEventListener('pointerdown', cerrarConsideracionesSiCorresponde)
})

function cerrarSesion() {
	authStore.logout()
	router.push('/login')
}
</script>

<style lang="scss" scoped>
@import '@/assets/styles/variables';

// --- SIGEM-UV TOPBAR 1 ---
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

// --- SIGEM-UV TOPBAR 2 ---
.sigem-topbar2 {
	position: sticky;
	top: 0;
	width: 100%;
	background: $color-blanco;
	box-shadow: 0 2px 8px rgba(0,0,0,0.15);
	z-index: 1000;
}

// --- HERO ---
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

// --- CONTENIDO ---
.page-layout {
	min-height: 100vh;
	flex: 1;
	display: flex;
	flex-direction: column;
}
.prestaciones-page {
	background: $color-fondo;
	flex: 1;
}
.prestaciones-content {
	max-width: 1200px;
	margin: 32px auto 72px auto;
	padding: 0 24px;
	display: flex;
	flex-direction: column;
	gap: 24px;
}
.prestaciones-header {
	text-align: left;
	display: flex;
	flex-direction: column;
	gap: 8px;
	position: relative;
}
.prestaciones-header-top {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 16px;
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
	gap: 10px;
	align-self: flex-start;
	margin-bottom: 0;
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
	margin: 0;
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

.instruccion-badge {
	display: inline-flex;
	align-items: center;
	gap: 5px;
	padding: 2px 9px;
	border-radius: 6px;
	font-size: 0.88rem;
	font-weight: 700;
	vertical-align: middle;
	white-space: nowrap;
}

.instruccion-badge--agregar {
	background: $color-exito;
	color: $color-blanco;
}

.instruccion-badge--quitar {
	background: $color-peligro;
	color: $color-blanco;
}

.instruccion-badge--guardar {
	background: $color-primario;
	color: $color-blanco;
}

.filtros-panel {
	display: grid;
	grid-template-columns: minmax(0, 2fr) repeat(3, minmax(0, 1fr));
	gap: 16px;
	background: $color-blanco;
	border-radius: 16px;
	padding: 18px 20px;
	border: 1px solid $color-borde;
	box-shadow: 0 10px 22px $color-sombra-suave;
}
.filtro {
	display: flex;
	flex-direction: column;
	gap: 6px;
	font-weight: 600;
	color: $color-primario;
	max-width: 190px;
}
.filtro-buscar {
	max-width: 420px;
}
.filtro select {
	padding: 10px 12px;
	border-radius: 10px;
	border: 1px solid $color-borde;
	font-weight: 500;
	color: $color-texto-principal;
}
.filtro input {
	padding: 10px 12px;
	border-radius: 10px;
	border: 1px solid $color-borde;
	font-weight: 500;
	color: $color-texto-principal;
}

.consideraciones-wrapper {
	position: relative;
	display: flex;
	flex-direction: column;
	align-items: flex-end;
	flex: 0 0 auto;
}

.consideraciones-panel {
	overflow: visible;
}

.consideraciones-panel-header {
	position: absolute;
	top: calc(100% + 14px);
	right: 0;
	width: min(560px, 100vw - 32px);
	max-width: 560px;
	z-index: 3;
	pointer-events: auto;
	background: transparent;
	backdrop-filter: none;
	box-shadow: none;
}

.consideraciones-toggle {
	width: auto;
	display: flex;
	align-items: center;
	gap: 10px;
	padding: 10px 16px;
	border: 1.5px solid #d97706;
	border-radius: 999px;
	background: rgba(251, 191, 36, 0.1);
	color: #92400e;
	font-weight: 700;
	font-size: 0.93rem;
	cursor: pointer;
	white-space: nowrap;
	transition: background 0.2s ease, box-shadow 0.2s ease;

	&:hover {
		background: rgba(251, 191, 36, 0.18);
		box-shadow: 0 2px 10px rgba(217, 119, 6, 0.18);
	}
}

.consideraciones-icono {
	flex: 0 0 auto;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 26px;
	height: 26px;
	border-radius: 50%;
	background: rgba(251, 191, 36, 0.22);
	color: #b45309;
	font-size: 0.95rem;
}

.consideraciones-texto {
	flex: 1 1 auto;
	font-size: 0.93rem;
}

.consideraciones-contenido {
	position: relative;
	z-index: 1;
	padding: 18px 20px;
	background: #fffbeb;
	border: 1.5px solid #fbbf24;
	border-top: 4px solid #f59e0b;
	border-radius: 12px;
	box-shadow: 0 8px 24px rgba(217, 119, 6, 0.12);
	color: #78350f;

	ol {
		margin: 0;
		padding-left: 20px;
		display: grid;
		gap: 12px;
	}

	li {
		line-height: 1.65;
		font-size: 0.9rem;
		font-weight: 500;
		color: #78350f;

		&::marker {
			color: #d97706;
			font-weight: 700;
		}
	}
}


.prestaciones-grid {
	display: grid;
	grid-template-columns: repeat(2, 1fr);
	gap: 22px;
}
.acciones-finales {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 16px;
	background: $color-blanco;
	border-radius: 14px;
	padding: 14px 18px;
	border: 1px solid $color-borde;
	box-shadow: 0 10px 22px $color-sombra-suave;
}
.acciones-resumen {
	color: $color-texto-principal;
	font-weight: 500;
}
.btn-confirmar {
	background: $color-primario;
	color: $color-blanco;
	border: none;
	border-radius: 10px;
	padding: 10px 18px;
	font-weight: 700;
	cursor: pointer;
	transition: background 0.2s ease, opacity 0.2s ease;

	&:hover {
		background: mix($color-blanco, $color-primario, 8%);
	}

	&:disabled {
		opacity: 0.55;
		cursor: not-allowed;
	}
}
.prestaciones-panel {
	background: $color-blanco;
	border-radius: 18px;
	padding: 20px;
	border: 1px solid $color-borde;
	box-shadow: 0 10px 22px $color-sombra-suave;
	min-height: 360px;
	display: flex;
	flex-direction: column;
}
.panel-header-seleccionadas {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
	margin-bottom: 14px;
}

.panel-header-seleccionadas .panel-title {
	margin-bottom: 0;
}

.panel-title {
	font-size: 1.1rem;
	font-weight: 700;
	color: $color-primario;
	margin-bottom: 14px;
}

.btn-limpiar-seleccion {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 7px 14px;
	border: 1.5px solid #dc2626;
	border-radius: 999px;
	background: rgba(239, 68, 68, 0.08);
	color: #991b1b;
	font-weight: 700;
	font-size: 0.85rem;
	cursor: pointer;
	white-space: nowrap;
	transition: background 0.2s ease, box-shadow 0.2s ease;

	&:hover:not(:disabled) {
		background: rgba(239, 68, 68, 0.16);
		box-shadow: 0 2px 10px rgba(220, 38, 38, 0.18);
	}

	&:disabled {
		border-color: #9ca3af;
		background: rgba(156, 163, 175, 0.08);
		color: #9ca3af;
		cursor: not-allowed;

		.limpiar-icono {
			background: rgba(156, 163, 175, 0.18);
			color: #9ca3af;
		}
	}
}

.limpiar-icono {
	flex: 0 0 auto;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 24px;
	height: 24px;
	border-radius: 50%;
	background: rgba(239, 68, 68, 0.18);
	color: #b91c1c;
	font-size: 0.82rem;
}

.limpiar-texto {
	flex: 1 1 auto;
	font-size: 0.85rem;
}
.prestaciones-lista {
	display: flex;
	flex-direction: column;
	gap: 12px;
	max-height: min(60vh, 560px);
	overflow-y: auto;
	padding-right: 6px;
}
.prestacion-item {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
	padding: 12px 14px;
	border-radius: 12px;
	background: $color-claro;
	border: 1px solid $color-borde;
}
.prestacion-info {
	flex: 1 1 auto;
	min-width: 0;
	display: flex;
	flex-direction: column;
	gap: 4px;
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
	line-height: 1.25;
	overflow-wrap: anywhere;
}
.accion {
	flex: 0 0 auto;
	width: 34px;
	height: 34px;
	border-radius: 8px;
	border: none;
	color: $color-blanco;
	font-weight: 700;
	cursor: pointer;
	display: flex;
	align-items: center;
	justify-content: center;
	transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
}
.accion-agregar {
	background: $color-exito;
	&:hover {
		transform: translateY(-1px) scale(1.02);
		box-shadow: 0 4px 8px rgba(12, 90, 45, 0.18);
	}
	&:active {
		transform: scale(0.99);
	}
}
.accion-quitar {
	background: $color-peligro;
	&:hover {
		transform: translateY(-1px) scale(1.02);
		box-shadow: 0 4px 8px rgba(128, 22, 22, 0.18);
	}
	&:active {
		transform: scale(0.99);
	}
}
.lista-vacia {
	background: $color-claro;
	padding: 24px;
	border-radius: 12px;
	text-align: center;
	color: $color-texto-secundario;
	border: 1px dashed $color-borde;
}

// --- SIGEM-UV BOTTOM BAR ---
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
	.filtros-panel {
		grid-template-columns: 1fr;
	}
	.prestaciones-grid {
		grid-template-columns: 1fr;
	}
	.acciones-finales {
		flex-direction: column;
		align-items: stretch;
	}
}
</style>
