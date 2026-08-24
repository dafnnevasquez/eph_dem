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

	<div class="resultados-page">
		<section class="hero hero-compact">
			<div class="hero-bg"></div>
			<div class="hero-content">
				<div class="hero-tag">MODULO EPHDEM</div>
				<h1 class="hero-title">Estudio de Preinversion Hospitalaria</h1>
				<p class="hero-sub">Resumen de equipamiento calculado desde el back-end para el proyecto seleccionado.</p>
			</div>
		</section>

		<main class="resultados-content">
			<header class="resultados-header">
				<!-- Fila 1: navegación y sesión -->
				<div class="nav-bar">
					<div class="nav-buttons">
						<button class="btn-back" type="button" @click="volverAtras"><i class="fa-solid fa-arrow-left"></i> Volver</button>
						<button class="btn-back" type="button" @click="router.push('/inicio')"><i class="fa-solid fa-house-user"></i> Inicio</button>
						<div class="nav-divider" v-if="proyectoIdActivo"></div>
						<button class="btn-back" type="button" @click="volverAParametros" v-if="proyectoIdActivo"><i class="fa-solid fa-sliders"></i> Editar parámetros</button>
						<button class="btn-back" type="button" @click="modificarPrestaciones" v-if="proyectoIdActivo"><i class="fa-solid fa-list-check"></i> Modificar prestaciones</button>
					</div>
					<div class="session-badge">
						<i class="fa-solid fa-circle-user"></i>
						<span class="session-nombre">{{ authStore.correoUsuario }}</span>
						<button class="btn-logout" type="button" @click="cerrarSesion" title="Cerrar sesión">
							<i class="fa-solid fa-right-from-bracket"></i>
						</button>
					</div>
				</div>
				<!-- Fila 2: título + botones de exportar -->
				<div class="title-actions-row">
					<div>
						<h2 class="section-title">Resultados</h2>
						<p class="section-subtitle">Resumen general y desglose por recinto del equipamiento.</p>
					</div>
					<div class="header-actions">
						<button class="btn-export btn-export-excel" type="button" @click="exportarExcel"><i class="fa-solid fa-file-excel"></i> Excel</button>
						<button class="btn-export btn-export-pdf" type="button" @click="exportarPdf"><i class="fa-solid fa-file-pdf"></i> PDF</button>
					</div>
				</div>
			</header>

			<section class="resumen-banner">
				<div class="banner-left">
					<h3>Proyecto</h3>
					<p class="banner-sub">{{ nombreProyecto }}</p>
				</div>
				<div class="banner-total" v-if="cargando">
					<span class="metric-label">Cargando...</span>
				</div>
				<div class="banner-total" v-else-if="error">
					<span class="metric-label" style="color:#ffaaaa">{{ error }}</span>
				</div>
				<div class="banner-total" v-else>
					<span class="metric-value">{{ totalUnidadesEquipos }}</span>
					<span class="metric-label">Equipos (total)</span>
				</div>
			</section>

			<!-- Desglose de pabellones/boxes por recinto (si el back-end lo entrega) -->
			<section v-if="!cargando && !error && (pabellonesPorRecinto.length || boxesPorRecinto.length)" class="recintos-conteo-panel">
				<div class="panel-title">Recintos requeridos</div>
				<div class="conteo-chips">
					<div v-for="rec in pabellonesPorRecinto" :key="'pab-' + rec.id" class="conteo-chip">
						<span class="chip-nombre">{{ rec.nombre }}</span>
						<span class="chip-valor">{{ rec.cantidad }}</span>
					</div>
					<div v-for="rec in boxesPorRecinto" :key="'box-' + rec.id" class="conteo-chip">
						<span class="chip-nombre">{{ rec.nombre }}</span>
						<span class="chip-valor">{{ rec.cantidad }}</span>
					</div>
				</div>
			</section>

			<section class="filtros-panel" v-if="!cargando && !error">
				<div class="filtro filtro-buscar">
					<label>Buscar equipo</label>
					<input v-model="filtros.texto" type="text" placeholder="Nombre de equipo" />
				</div>
			</section>

			<!-- NIVEL A: Resumen total de equipos -->
			<section class="resumen-panel" :class="{ 'resumen-panel-cerrado': !resumenAbierto }" v-if="!cargando && !error">
				<div class="panel-title panel-title-toggle" @click="resumenAbierto = !resumenAbierto">
					<span>Resumen de equipos necesarios (total)</span>
					<div class="vista-toggle-group" @click.stop>
						<!-- Lista -->
						<button
							class="vista-btn"
							:class="{ 'vista-btn-active': vistaResumen === 'lista' }"
							title="Vista lista"
							@click="vistaResumen = 'lista'"
						>
							<svg width="18" height="18" viewBox="0 0 18 18" fill="none">
								<rect x="1" y="3" width="16" height="2.5" rx="1.2" fill="currentColor"/>
								<rect x="1" y="7.75" width="16" height="2.5" rx="1.2" fill="currentColor"/>
								<rect x="1" y="12.5" width="16" height="2.5" rx="1.2" fill="currentColor"/>
							</svg>
						</button>
						<!-- Mosaico 2 col -->
						<button
							class="vista-btn"
							:class="{ 'vista-btn-active': vistaResumen === 'mosaico2' }"
							title="Mosaico 2 columnas"
							@click="vistaResumen = 'mosaico2'"
						>
							<svg width="18" height="18" viewBox="0 0 18 18" fill="none">
								<rect x="1" y="1" width="7" height="7" rx="1.5" fill="currentColor"/>
								<rect x="10" y="1" width="7" height="7" rx="1.5" fill="currentColor"/>
								<rect x="1" y="10" width="7" height="7" rx="1.5" fill="currentColor"/>
								<rect x="10" y="10" width="7" height="7" rx="1.5" fill="currentColor"/>
							</svg>
						</button>
						<!-- Mosaico 3 col -->
						<button
							class="vista-btn"
							:class="{ 'vista-btn-active': vistaResumen === 'mosaico3' }"
							title="Mosaico 3 columnas"
							@click="vistaResumen = 'mosaico3'"
						>
							<svg width="18" height="18" viewBox="0 0 18 18" fill="none">
								<rect x="1" y="1" width="4.5" height="7" rx="1.2" fill="currentColor"/>
								<rect x="6.75" y="1" width="4.5" height="7" rx="1.2" fill="currentColor"/>
								<rect x="12.5" y="1" width="4.5" height="7" rx="1.2" fill="currentColor"/>
								<rect x="1" y="10" width="4.5" height="7" rx="1.2" fill="currentColor"/>
								<rect x="6.75" y="10" width="4.5" height="7" rx="1.2" fill="currentColor"/>
								<rect x="12.5" y="10" width="4.5" height="7" rx="1.2" fill="currentColor"/>
							</svg>
						</button>
						<i class="fa-solid vista-chevron" :class="resumenAbierto ? 'fa-chevron-up' : 'fa-chevron-down'" @click="resumenAbierto = !resumenAbierto"></i>
					</div>
				</div>

				<!-- Vista LISTA -->
				<div class="resumen-list" v-show="resumenAbierto && vistaResumen === 'lista'">
					<div class="resumen-row resumen-row-head">
						<div>Equipo</div>
						<div class="row-total">Total</div>
					</div>
					<div v-if="resumenEquipos.length === 0" class="lista-vacia" style="padding:12px 16px;">Sin equipos para este filtro.</div>
					<div v-for="equipo in resumenEquipos" :key="equipo.equipo_id" class="resumen-row">
						<div class="row-main">
							<div class="equipo-nombre">{{ equipo.nombre_equipo }}</div>
						</div>
						<div class="row-total">{{ equipo.cantidad }}</div>
					</div>
				</div>

				<!-- Vista MOSAICO -->
				<div
					v-show="resumenAbierto && (vistaResumen === 'mosaico2' || vistaResumen === 'mosaico3')"
					class="resumen-mosaico"
					:class="vistaResumen === 'mosaico3' ? 'resumen-mosaico-3' : 'resumen-mosaico-2'"
				>
					<div v-if="resumenEquipos.length === 0" class="lista-vacia" style="padding:12px 16px;">Sin equipos para este filtro.</div>
					<div v-for="equipo in resumenEquipos" :key="equipo.equipo_id" class="mosaic-card">
						<div class="mosaic-header">
							<div class="mosaic-nombre">{{ equipo.nombre_equipo }}</div>
							<span class="mosaic-total">{{ equipo.cantidad }}</span>
						</div>
					</div>
				</div>
			</section>

			<!-- NIVEL B: Equipos por recinto (tarjetas colapsables) -->
			<section class="desglose-panel" v-if="!cargando && !error">
				<div class="panel-title">Desglose de equipamiento</div>
				<div class="desglose-section">
					<div v-if="recintosAgrupados.length === 0" class="lista-vacia">Sin equipos por recinto para este filtro.</div>
					<div v-else class="recintos-grid">
						<div v-for="recinto in recintosAgrupados" :key="recinto.id" class="recinto-card">
							<!-- Cabecera colapsable -->
							<div class="recinto-title recinto-title-toggle" @click="toggleRecinto(recinto.id)">
								<span>{{ recinto.nombre }} <span class="recinto-count">({{ recinto.items.length }})</span></span>
								<i class="fa-solid" :class="recintoAbierto[recinto.id] ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
							</div>
							<!-- Cuerpo colapsable -->
							<div class="recinto-body" v-show="recintoAbierto[recinto.id]">
								<!-- Items generales del recinto (kit, demanda, tipo2) -->
								<div class="tabla-mini">
									<div class="tabla-mini-head">
										<span>Equipo</span>
										<span class="tabla-mini-cantidad">Cantidad</span>
									</div>
									<template v-if="recinto.items.length > 0">
								<div v-for="item in recinto.items" :key="item.equipo_id" class="tabla-mini-row">
									<span>{{ item.nombre_equipo }}</span>
									<span class="tabla-mini-cantidad">{{ item.cantidad }}</span>
								</div>
									</template>
									<div v-else class="tabla-mini-vacia">Sin equipos propios de recinto.</div>
								</div>
								<!-- Sub-desplegable: Estación de Enfermería (solo UCI/UTI, desde estacion_enfermeria[]) -->
								<div v-if="recinto.estacionEnfermeria.length > 0" class="subrecinto-section">
									<div class="subrecinto-title" @click.stop="toggleSubNorma(recinto.id)">
										<i class="fa-solid fa-kit-medical subrecinto-icon"></i>
										<span>Estación de Enfermería <span class="subrecinto-badge">Norma UPC</span></span>
										<span class="subrecinto-count">({{ recinto.estacionEnfermeria.length }})</span>
										<i class="fa-solid subrecinto-chevron" :class="subnormaAbierto[recinto.id] ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
									</div>
									<div class="subrecinto-body" v-show="subnormaAbierto[recinto.id]">
										<div class="tabla-mini tabla-mini-upc">
											<div class="tabla-mini-head tabla-mini-head-upc">
												<span>Equipo (Est. Enfermería)</span>
												<span class="tabla-mini-cantidad">Cant.</span>
											</div>
											<div v-for="item in recinto.estacionEnfermeria" :key="'ee-' + item.equipo_id" class="tabla-mini-row tabla-mini-row-upc">
												<span>{{ item.nombre_equipo }}</span>
												<span class="tabla-mini-cantidad">{{ item.cantidad }}</span>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

				</div>

				<!-- Demanda compartida: equipos que cruzan mas de un recinto -->
				<div class="desglose-section desglose-section-especifico" v-if="demandaCompartida.length > 0">
					<div class="desglose-title">Equipos de demanda compartida (multi-recinto)</div>
					<div class="recinto-table especifico-table">
						<div class="recinto-title">No atribuibles a un unico recinto</div>
						<div class="tabla-mini">
							<div class="tabla-mini-head tabla-mini-head-compartida">
								<span>Equipo</span>
								<span>Recintos</span>
								<span class="tabla-mini-cantidad">Cantidad</span>
							</div>
							<div v-for="item in demandaCompartida" :key="item.equipo_id" class="tabla-mini-row tabla-mini-row-compartida">
								<span>{{ item.nombre_equipo }}</span>
								<span class="compartida-recintos">{{ item.recintos_involucrados.map(r => r.nombre_recinto).join(', ') }}</span>
								<span class="tabla-mini-cantidad">{{ item.cantidad }}</span>
							</div>
						</div>
					</div>
					<p class="nota-recinto">
						Estos equipos reciben demanda de prestaciones de varios recintos. Su cantidad es el total real
						(no se reparte entre recintos para no inventar cifras).
					</p>
				</div>

				<!-- Equipamiento requerido por normativa y/o guías + Prestaciones + URPA -->
				<div
					v-if="equiposNormativa.length > 0 || equiposPrestaciones.length > 0 || (urpaRaw && urpaRaw.nro_salas > 0)"
					class="recintos-grid recintos-grid-extra"
				>
					<!-- Tarjeta Normativa -->
					<div v-if="equiposNormativa.length > 0" class="recinto-card">
						<div class="recinto-title recinto-title-toggle recinto-title-normativa" @click="normativaAbierta = !normativaAbierta">
							<span>
								<i class="fa-solid fa-file-medical" style="margin-right:6px;"></i>
								Equipamiento por normativa y/o guías
								<span class="recinto-count">({{ equiposNormativa.length }})</span>
							</span>
							<i class="fa-solid" :class="normativaAbierta ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
						</div>
						<div class="recinto-body" v-show="normativaAbierta">
							<div class="tabla-mini">
								<div class="tabla-mini-head">
									<span>Equipo</span>
									<span class="tabla-mini-cantidad">Total</span>
								</div>
								<div v-for="equipo in equiposNormativa" :key="equipo.equipo_id" class="tabla-mini-row">
									<span>{{ equipo.nombre_equipo }}</span>
									<span class="tabla-mini-cantidad">{{ equipo.cantidad }}</span>
								</div>
							</div>
						</div>
					</div>

					<!-- Tarjeta Prestaciones -->
					<div v-if="equiposPrestaciones.length > 0" class="recinto-card">
						<div class="recinto-title recinto-title-toggle recinto-title-prestaciones" @click="prestacionesAbierta = !prestacionesAbierta">
							<span>
								<i class="fa-solid fa-stethoscope" style="margin-right:6px;"></i>
								Equipamiento específico de prestaciones
								<span class="recinto-count">({{ equiposPrestaciones.length }})</span>
							</span>
							<i class="fa-solid" :class="prestacionesAbierta ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
						</div>
						<div class="recinto-body" v-show="prestacionesAbierta">
							<div class="tabla-mini">
								<div class="tabla-mini-head">
									<span>Equipo</span>
									<span class="tabla-mini-cantidad">Total</span>
								</div>
								<div v-for="equipo in equiposPrestaciones" :key="equipo.equipo_id" class="tabla-mini-row">
									<span>{{ equipo.nombre_equipo }}</span>
									<span class="tabla-mini-cantidad">{{ equipo.cantidad }}</span>
								</div>
							</div>
						</div>
					</div>

					<!-- Tarjeta URPA -->
					<div v-if="urpaRaw && urpaRaw.nro_salas > 0" class="recinto-card">
						<div class="recinto-title recinto-title-toggle recinto-title-urpa" @click="urpaAbierta = !urpaAbierta">
							<span>
								<i class="fa-solid fa-bed-pulse" style="margin-right:6px;"></i>
								{{ urpaRaw.nombre_recinto }}
								<span class="recinto-count">({{ urpaRaw.equipos.length }})</span>
							</span>
							<i class="fa-solid" :class="urpaAbierta ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
						</div>
						<div class="recinto-body" v-show="urpaAbierta">
							<div style="padding: 12px 14px; font-size: 0.95rem; color: #555; border-bottom: 1px solid #e0e0e0;">
								<strong>Resumen:</strong> {{ urpaRaw.nro_camillas }} camillas distribuidas en {{ urpaRaw.nro_salas }} sala(s), derivadas de {{ urpaRaw.nro_pabellones }} pabellones.
							</div>
							<div class="tabla-mini">
								<div class="tabla-mini-head">
									<span>Equipo</span>
									<span class="tabla-mini-cantidad">Total</span>
								</div>
								<div v-for="item in urpaRaw.equipos" :key="item.equipo_id" class="tabla-mini-row">
									<span>{{ item.nombre_equipo }}</span>
									<span class="tabla-mini-cantidad">{{ item.cantidad }}</span>
								</div>
							</div>
						</div>
					</div>
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
import { computed, onMounted, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const urpaRaw = ref(null)

const nombreProyecto = ref('Proyecto seleccionado')
const proyectoIdActivo = ref(null)
const pabellones = ref(0)
const boxes = ref(0)
const cargando = ref(true)
const error = ref(null)

const cajasFiltro = ref(false)
const resumenAbierto = ref(true)
const vistaResumen = ref('lista') // 'lista' | 'mosaico2' | 'mosaico3'

// Datos crudos del back-end (contrato calcular_demanda.php)
const equipos = ref([])              // datos.equipamiento.equipos (Nivel A)
const porRecinto = ref({})           // datos.equipamiento.por_recinto (Nivel B)
const demandaCompartidaRaw = ref([]) // datos.equipamiento.demanda_compartida
const pabellonesPorRecintoRaw = ref({}) // datos.pabellones.pabellones_por_recinto
const boxesPorRecintoRaw = ref({})   // datos.boxes.por_recinto

const filtros = ref({
	texto: '',
	recinto: '',
})

// Estado colapsable de cada tarjeta de recinto y sub-panel norma_upc
const recintoAbierto = ref({})   // { [recintoId]: boolean }
const subnormaAbierto = ref({})  // { [recintoId]: boolean }
const normativaAbierta = ref(false)
const prestacionesAbierta = ref(false)
const urpaAbierta = ref(false)

// Etiqueta legible para cada origen del calculo.
function etiquetaOrigen(origen) {
	const mapa = {
		kit: 'Kit',
		demanda: 'Demanda',
		norma_upc: 'Norma UPC',
		tipo2_relacion: 'Tipo 2',
	}
	return mapa[origen] || origen
}

// Normaliza texto quitando tildes/diacriticos para filtrado insensible a acentos.
function normalizar(str) {
	return (str || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase()
}

// Consolidado de equipos base + URPA
const equiposConsolidados = computed(() => {
	const base = equipos.value || []
	const urpa = urpaRaw.value?.equipos || []

	if (urpa.length === 0) return base

	const map = new Map()
	base.forEach(e => {
		map.set(e.equipo_id, { ...e })
	})

	urpa.forEach(e => {
		if (map.has(e.equipo_id)) {
			const existente = map.get(e.equipo_id)
			existente.cantidad += e.cantidad
		} else {
			map.set(e.equipo_id, {
				equipo_id: e.equipo_id,
				nombre_equipo: e.nombre_equipo,
				cantidad: e.cantidad,
				origenes: { urpa: true }
			})
		}
	})

	return Array.from(map.values())
})

// Total de unidades de equipo (suma de todas las cantidades del Nivel A).
const totalUnidadesEquipos = computed(() =>
	equiposConsolidados.value.reduce((acc, e) => acc + (e.cantidad || 0), 0)
)

// Conteo de pabellones/boxes por recinto para los chips.
const pabellonesPorRecinto = computed(() =>
	Object.entries(pabellonesPorRecintoRaw.value).map(([id, info]) => ({
		id,
		nombre: nombreRecinto(id),
		cantidad: info.pabellones,
	}))
)
const boxesPorRecinto = computed(() =>
	Object.entries(boxesPorRecintoRaw.value).map(([id, info]) => ({
		id,
		nombre: nombreRecinto(id),
		cantidad: info.boxes,
	}))
)

// Nombre legible de recinto por id (fallback si el back-end no lo trae).
function nombreRecinto(id) {
	const mapa = { 1: 'Cubiculo UTI', 2: 'Cubiculo UCI', 3: 'Pabellon menor', 4: 'Pabellon mayor' }
	return mapa[id] || ('Recinto ' + id)
}

// Opciones de recinto para el filtro (a partir de por_recinto).
const opcionesRecinto = computed(() =>
	Object.values(porRecinto.value)
		.filter((r) => r.equipos && r.equipos.length > 0)
		.map((r) => r.nombre_recinto)
)

// NIVEL A — Resumen total, con filtros aplicados.
const resumenEquipos = computed(() => {
	const texto = normalizar(filtros.value.texto.trim())
	return equiposConsolidados.value
		.filter((e) => (e.cantidad || 0) > 0)
		.filter((e) => !texto || normalizar(e.nombre_equipo).includes(texto))
})

// NIVEL B — Equipos por recinto, con filtros aplicados.
// Usa el contrato real del back-end: info.equipos[] (generales) + info.estacion_enfermeria[] (solo UTI/UCI).
// Cross-referencia el mapa de orígenes del Nivel A para excluir de las tarjetas
// los equipos que provienen EXCLUSIVAMENTE de normas de razón (tipo2_relacion),
// ya que estos no son propios del recinto sino de una regla transversal.
// Solo se muestran en la tarjeta equipos con origen kit o demanda.
const recintosAgrupados = computed(() => {
	const texto = normalizar(filtros.value.texto.trim())
	const coincide = (item) => !texto || normalizar(item.nombre_equipo).includes(texto)

	// Mapa equipo_id → origenes desde el Nivel A (que sí tiene origenes por equipo)
	const origenMap = Object.fromEntries(
		equipos.value.map((e) => [e.equipo_id, e.origenes || {}])
	)
	// Un equipo es "propio del recinto" si tiene origen 'kit'.
	// Se excluyen los que vienen por 'demanda' (prestación) o 'tipo2_relacion' (regla).
	// Los de 'norma_upc' ya van por otra vía (estacionEnfermeria).
	const esPropioDel = (item) => {
		const orig = origenMap[item.equipo_id] || {}
		return (orig.kit ?? 0) > 0
	}

	return Object.entries(porRecinto.value)
		.map(([id, info]) => ({
			id,
			nombre: info.nombre_recinto || nombreRecinto(id),
			items: (info.equipos || []).filter((item) => coincide(item) && esPropioDel(item)),
			// estacion_enfermeria: solo viene con elementos en recintos 1 (UTI) y 2 (UCI);
			// en pabellones (3/4) el back-end entrega [] — no se renderiza el sub-panel.
			estacionEnfermeria: (info.estacion_enfermeria || []).filter(coincide),
		}))
		// Mostrar el recinto si tiene equipos propios O de enfermeria.
		.filter((r) => r.items.length > 0 || r.estacionEnfermeria.length > 0)
		.filter((r) => !filtros.value.recinto || r.nombre === filtros.value.recinto)
})

// Demanda compartida (multi-recinto), con filtro de texto.
const demandaCompartida = computed(() => {
	const texto = normalizar(filtros.value.texto.trim())
	return demandaCompartidaRaw.value.filter((item) => {
		if (texto && !normalizar(item.nombre_equipo).includes(texto)) return false
		return true
	})
})

// Equipamiento requerido por normativa y/o guías (origen norma_upc o tipo2_relacion).
const equiposNormativa = computed(() => {
	const texto = normalizar(filtros.value.texto.trim())
	return equipos.value
		.filter((e) => (e.cantidad || 0) > 0)
		.filter((e) => {
			const orig = e.origenes || {}
			return ('norma_upc' in orig) || ('tipo2_relacion' in orig)
		})
		.filter((e) => !texto || normalizar(e.nombre_equipo).includes(texto))
})

// Equipamiento específico de prestaciones seleccionadas (origen demanda).
const equiposPrestaciones = computed(() => {
	const texto = normalizar(filtros.value.texto.trim())
	return equipos.value
		.filter((e) => (e.cantidad || 0) > 0)
		.filter((e) => {
			const orig = e.origenes || {}
			return ('demanda' in orig)
		})
		.filter((e) => !texto || normalizar(e.nombre_equipo).includes(texto))
})

// Toggle apertura/cierre de tarjeta de recinto
function toggleRecinto(id) {
	recintoAbierto.value = { ...recintoAbierto.value, [id]: !recintoAbierto.value[id] }
}

// Toggle sub-panel norma_upc dentro de un recinto
function toggleSubNorma(id) {
	subnormaAbierto.value = { ...subnormaAbierto.value, [id]: !subnormaAbierto.value[id] }
}

function aplicarDatos(datos) {
	proyectoIdActivo.value = datos.proyecto_id ?? null

	pabellones.value = datos.pabellones?.total ?? 0
	boxes.value = datos.boxes?.total ?? 0

	equipos.value = datos.equipamiento?.equipos ?? []
	porRecinto.value = datos.equipamiento?.por_recinto ?? {}
	demandaCompartidaRaw.value = datos.equipamiento?.demanda_compartida ?? []
	pabellonesPorRecintoRaw.value = datos.pabellones?.pabellones_por_recinto ?? {}
	boxesPorRecintoRaw.value = datos.boxes?.por_recinto ?? {}
	urpaRaw.value = datos.urpa ?? null

	if (datos.nombre_proyecto) {
		nombreProyecto.value = datos.nombre_proyecto
		localStorage.setItem('ephdem_nombre_proyecto_activo', datos.nombre_proyecto)
	} else {
		nombreProyecto.value = localStorage.getItem('ephdem_nombre_proyecto_activo') || 'Desconocido'
	}

	if (datos.proyecto_id) {
		localStorage.setItem('ephdem_proyecto_activo', datos.proyecto_id)
	}

	cargando.value = false
}

function cargarDesdeLocalStorage() {
	const raw = localStorage.getItem('ephdem_resultado_calculo')
	if (!raw) {
		error.value = 'No hay resultados disponibles. Vuelve a parametros y calcula.'
		cargando.value = false
		return
	}
	try {
		const parsed = JSON.parse(raw)
		const datos = parsed.datos ? parsed.datos : parsed
		aplicarDatos(datos)
	} catch (e) {
		error.value = 'Error al leer los resultados.'
		cargando.value = false
	}
}

async function cargarDesdeServidor(proyectoId) {
	const usuarioId = authStore.usuarioId
	if (!usuarioId) {
		error.value = 'No hay sesión activa.'
		cargando.value = false
		return
	}
	try {
		const url = `${import.meta.env.VITE_API_BASE}/obtener_resultados_proyecto.php?proyecto_id=${proyectoId}&usuario_id=${usuarioId}`
		const resp = await fetch(url)
		const json = await resp.json()
		if (!resp.ok || !json.ok) {
			error.value = json.error || 'Error al cargar resultados del servidor.'
			cargando.value = false
		} else {
			aplicarDatos(json.datos)
		}
	} catch (e) {
		error.value = 'Error de red al intentar cargar resultados.'
		cargando.value = false
	}
}

onMounted(() => {
	if (route.params.proyectoId) {
		cargarDesdeServidor(route.params.proyectoId)
	} else {
		cargarDesdeLocalStorage()
	}
})

function volverAParametros() {
	router.push(`/parametros/${proyectoIdActivo.value}`)
}

function modificarPrestaciones() {
	router.push(`/prestaciones/${proyectoIdActivo.value}`)
}

function volverAtras() {
	// Limpia los resultados previos del caché para evitar datos residuales al re-calcular
	localStorage.removeItem('ephdem_resultado_calculo')
	
	router.back()
	setTimeout(() => window.scrollTo({ top: 0, left: 0, behavior: 'auto' }), 0)
}

function cerrarSesion() {
	authStore.logout()
	router.push('/login')
}

function exportarExcel() {
	if (!proyectoIdActivo.value) {
		alert('No se pudo identificar el proyecto activo.')
		return
	}
	const nombre = encodeURIComponent(nombreProyecto.value || 'Proyecto')
	const base = 'https://sigem-uv.cl/__v2/modulo_eph/ajax/generar_xls_cerrada.php'
	window.open(`${base}?proyecto_id=${proyectoIdActivo.value}&nombre=${nombre}`, '_blank')
}

function exportarPdf() {
	if (!proyectoIdActivo.value) {
		alert('No se pudo identificar el proyecto activo.')
		return
	}
	const nombre = encodeURIComponent(nombreProyecto.value || 'Proyecto')
	const base = 'https://sigem-uv.cl/__v2/modulo_eph/ajax/generar_pdf_cerrada.php'
	window.open(`${base}?proyecto_id=${proyectoIdActivo.value}&nombre=${nombre}`, '_blank')
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
	max-width: 740px;
	line-height: 1.5;
	margin: 0;
}

.page-layout {
	min-height: 100vh;
	flex: 1;
	display: flex;
	flex-direction: column;
}
.resultados-page {
	background: $color-fondo;
	flex: 1;
}
// Anular el margin-top del footer global para que quede pegado al fondo
.sigem-bottomline {
	margin-top: 0;
}
.resultados-content {
	max-width: 1480px;
	margin: 32px auto 48px auto;
	padding: 0 20px;
	display: flex;
	flex-direction: column;
	gap: 24px;
}
.resultados-header {
	display: flex;
	flex-direction: column;
	gap: 10px;
}
.title-actions-row {
	display: flex;
	align-items: flex-end;
	justify-content: space-between;
	gap: 16px;
}
.header-actions {
	display: flex;
	gap: 10px;
	flex-wrap: wrap;
	align-items: center;
}
.nav-bar {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 12px;
	margin-bottom: 0;
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
.btn-back {
	align-self: flex-start;
	background: $color-primario;
	color: $color-blanco;
	border: 1px solid $color-primario;
	border-radius: 999px;
	padding: 6px 12px;
	font-weight: 600;
	cursor: pointer;
	margin-bottom: 6px;
	transition: background 0.2s ease, border 0.2s ease;

	&:hover {
		background: mix($color-blanco, $color-primario, 6%);
		border-color: mix($color-blanco, $color-primario, 6%);
	}
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
	margin-bottom: 6px;
	transition: background 0.2s ease, border 0.2s ease;

	&:hover {
		background: mix($color-blanco, $color-primario, 6%);
		border-color: mix($color-blanco, $color-primario, 6%);
	}
}
.btn-export {
	background: $color-blanco;
	border: 1px solid $color-borde;
	color: $color-primario;
	border-radius: 12px;
	padding: 10px 14px;
	font-weight: 700;
	cursor: pointer;
	display: inline-flex;
	align-items: center;
	gap: 8px;
	box-shadow: 0 6px 16px rgba(0, 60, 88, 0.08);
}
.btn-export-excel {
	color: #1e8e5a;
	border-color: rgba(30, 142, 90, 0.35);
}
.btn-export-pdf {
	background: $color-blanco;
	border-color: $color-borde;
	color: #d5431c;
}
.section-title {
	font-size: 1.6rem;
	font-weight: 700;
	color: $color-primario;
	margin: 0 0 4px 0;
}
.section-subtitle {
	margin: 0;
	color: $color-texto-secundario;
}

.resumen-banner {
	background: $color-primario;
	border-radius: 18px;
	padding: 22px 24px;
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 18px;
	border: 1px solid rgba(255, 255, 255, 0.12);
	box-shadow: 0 10px 22px rgba(0, 60, 88, 0.25);
}
.banner-left h3 {
	margin: 0;
	font-size: 1.3rem;
	font-weight: 700;
	color: $color-blanco;
}
.banner-sub {
	margin: 4px 0 0 0;
	font-size: 0.95rem;
	color: rgba(255, 255, 255, 0.75);
}
.banner-total {
	display: flex;
	align-items: baseline;
	gap: 10px;
	color: $color-blanco;
}
.metric-value {
	font-size: 1.6rem;
	font-weight: 700;
}
.metric-label {
	font-size: 0.85rem;
	color: rgba(255, 255, 255, 0.7);
}

/* Chips de recintos requeridos */
.recintos-conteo-panel {
	background: $color-blanco;
	border-radius: 16px;
	padding: 18px 20px;
	border: 1px solid $color-borde;
	box-shadow: 0 10px 22px $color-sombra-suave;
}
.conteo-chips {
	display: flex;
	flex-wrap: wrap;
	gap: 12px;
}
.conteo-chip {
	display: flex;
	align-items: center;
	gap: 10px;
	background: #eef5f9;
	border: 1px solid $color-borde;
	border-radius: 999px;
	padding: 8px 16px;
}
.chip-nombre {
	font-weight: 600;
	color: $color-primario;
}
.chip-valor {
	font-weight: 700;
	font-size: 1.2rem;
	color: $color-primario;
}

.filtros-panel {
	background: $color-blanco;
	border-radius: 16px;
	padding: 18px 20px;
	border: 1px solid $color-borde;
	box-shadow: 0 10px 22px $color-sombra-suave;
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
	gap: 14px;
}
.filtro label {
	font-size: 0.85rem;
	color: $color-primario;
	font-weight: 600;
	margin-bottom: 6px;
	display: block;
}
.filtro input,
.filtro select {
	width: 100%;
	padding: 8px 10px;
	border: 1px solid $color-borde;
	border-radius: 10px;
	font-weight: 500;
	color: $color-texto-principal;
}

.resumen-panel,
.desglose-panel {
	background: $color-blanco;
	border-radius: 16px;
	padding: 20px;
	border: 1px solid $color-borde;
	box-shadow: 0 10px 22px $color-sombra-suave;
}
.panel-title {
	font-weight: 700;
	color: $color-primario;
	margin-bottom: 16px;
}
.panel-title-toggle {
	display: flex;
	justify-content: space-between;
	align-items: center;
	background: #eef5f9;
	margin: -20px -20px 16px -20px;
	padding: 10px 16px;
	border-radius: 14px 14px 0 0;
	cursor: pointer;
	user-select: none;
	transition: background 0.15s;
	&:hover { background: #ddeaf4; }
}
.resumen-panel-cerrado {
	padding-bottom: 0 !important;
}
.resumen-panel-cerrado .panel-title-toggle {
	margin-bottom: 0 !important;
	border-radius: 14px !important;
}

/* Grupo de botones de selección de vista */
.vista-toggle-group {
	display: flex;
	align-items: center;
	gap: 4px;
}
.vista-btn {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	width: 30px;
	height: 30px;
	background: transparent;
	border: 1.5px solid transparent;
	border-radius: 8px;
	color: $color-primario;
	opacity: 0.45;
	cursor: pointer;
	transition: opacity 0.15s, background 0.15s, border-color 0.15s;
	padding: 0;
	&:hover {
		opacity: 0.9;
		background: rgba(0, 60, 88, 0.08);
		border-color: rgba(0, 60, 88, 0.18);
	}
	&.vista-btn-active {
		opacity: 1;
		background: $color-primario;
		border-color: $color-primario;
		color: #fff;
	}
}
.vista-chevron {
	font-size: 0.85rem;
	opacity: 0.55;
	margin-left: 6px;
	cursor: pointer;
	transition: opacity 0.15s;
	&:hover { opacity: 1; }
}

/* Mosaico */
.resumen-mosaico {
	display: grid;
	gap: 8px;
	&.resumen-mosaico-2 { grid-template-columns: repeat(2, 1fr); }
	&.resumen-mosaico-3 { grid-template-columns: repeat(3, 1fr); }
}
.mosaic-card {
	background: #f4f8fb;
	border: 1px solid $color-borde;
	border-radius: 8px;
	padding: 8px 12px;
	display: flex;
	flex-direction: column;
	gap: 3px;
	transition: box-shadow 0.18s, background 0.15s;
	&:hover {
		background: #e8f2fa;
		box-shadow: 0 3px 10px rgba(0,60,88,0.10);
	}
}
.mosaic-header {
	display: flex;
	align-items: center;
	justify-content: space-between;
	gap: 8px;
}
.mosaic-nombre {
	font-size: 0.8rem;
	font-weight: 600;
	color: $color-texto-principal;
	line-height: 1.3;
	flex: 1;
}
.mosaic-total {
	font-size: 1.05rem;
	font-weight: 800;
	color: $color-primario;
	white-space: nowrap;
	flex-shrink: 0;
}
.mosaic-origenes {
	display: flex;
	flex-wrap: wrap;
	gap: 3px;
}
.resumen-list {
	display: flex;
	flex-direction: column;
	gap: 0;
	border: 1px solid $color-borde;
	border-radius: 12px;
	overflow: hidden;
}
.resumen-row {
	background: $color-blanco;
	padding: 12px 16px;
	display: grid;
	grid-template-columns: 1fr 70px;
	gap: 16px;
	align-items: center;
	border-bottom: 1px solid $color-borde;
}
.resumen-list .resumen-row:not(.resumen-row-head):nth-child(even) {
	background: #f0f6fb;
}
.resumen-list .resumen-row:not(.resumen-row-head):nth-child(odd) {
	background: #f8fbfd;
}
.resumen-row:last-child {
	border-bottom: none;
}
.resumen-row-head {
	background: #ddeaf4; /* Más oscuro para destacar */
	font-weight: 700;
	color: $color-primario;
	font-size: 1.4rem;
}
.row-main {
	display: flex;
	flex-direction: column;
	gap: 4px;
}
.row-origenes {
	display: flex;
	flex-wrap: wrap;
	gap: 6px;
	justify-content: flex-end;
}
.origen-badge {
	font-size: 0.72rem;
	font-weight: 700;
	padding: 2px 8px;
	border-radius: 999px;
	background: #eef5f9;
	color: $color-primario;
	border: 1px solid $color-borde;
	white-space: nowrap;
}
.origen-kit { background: #eaf3ff; }
.origen-demanda { background: #fff2e0; }
.origen-norma_upc { background: #e9f7ef; }
.origen-tipo2_relacion { background: #f3e9ff; }
.row-total {
	font-size: 1.4rem;
	font-weight: 700;
	color: $color-primario;
	text-align: right;
}
.equipo-nombre {
	font-weight: 700;
	color: $color-primario;
}
.desglose-title {
	font-weight: 700;
	color: $color-primario;
	margin-bottom: 12px;
}
.desglose-section {
	margin-bottom: 20px;
}
.desglose-section-especifico {
	margin-top: 8px;
}
.recintos-grid {
	columns: 2;
	column-gap: 18px;
}
.recinto-table {
	border-radius: 12px;
	padding: 0;
	border: 1px solid $color-borde;
	box-shadow: none;
	overflow: hidden;
	background: $color-blanco;
}
// Tarjeta colapsable de recinto
.recinto-card {
	border-radius: 12px;
	border: 1px solid $color-borde;
	box-shadow: 0 2px 8px $color-sombra-suave;
	overflow: hidden;
	background: $color-blanco;
	transition: box-shadow 0.2s;
	break-inside: avoid;       /* no partir tarjeta entre columnas */
	margin-bottom: 18px;       /* separación entre tarjetas de la misma columna */
	&:hover { box-shadow: 0 4px 16px $color-sombra-suave; }
}
.recinto-title {
	font-weight: 700;
	color: $color-primario;
	background: #eef5f9;
	margin: 0;
	padding: 10px 14px;
	border-radius: 0;
}
.recinto-title-toggle {
	display: flex;
	align-items: center;
	justify-content: space-between;
	cursor: pointer;
	user-select: none;
	transition: background 0.15s;
	&:hover { background: #ddeaf4; }
	i { font-size: 0.85rem; opacity: 0.7; transition: transform 0.2s; }
}
.recinto-body {
	border-top: 1px solid $color-borde;
}
.recinto-count {
	font-weight: 500;
	color: $color-texto-secundario;
}
// Sub-panel Estación de Enfermería — diseño azul pizarra médico
.subrecinto-section {
	border-top: none;
	margin: 0 10px 10px 10px;
	border-radius: 10px;
	overflow: hidden;
	border: 1px solid #c5d7f0;
	box-shadow: 0 2px 10px rgba(30, 80, 160, 0.07);
}
.subrecinto-title {
	display: flex;
	align-items: center;
	gap: 8px;
	padding: 9px 12px 9px 14px;
	cursor: pointer;
	user-select: none;
	font-size: 0.86rem;
	font-weight: 700;
	color: #1a4d8f;
	background: linear-gradient(90deg, #e8f0fb 0%, #f0f5ff 100%);
	border-left: 4px solid #3a7bd5;
	transition: background 0.15s;
	&:hover { background: linear-gradient(90deg, #d8e6f8 0%, #e8f0fd 100%); }
}
.subrecinto-icon {
	font-size: 0.9rem;
	color: #3a7bd5;
	flex-shrink: 0;
}
.subrecinto-badge {
	display: inline-flex;
	align-items: center;
	font-size: 0.63rem;
	font-weight: 800;
	padding: 1px 6px;
	border-radius: 4px;
	background: #3a7bd5;
	color: #fff;
	margin-left: 4px;
	vertical-align: middle;
	text-transform: uppercase;
	letter-spacing: 0.6px;
}
.subrecinto-count {
	font-size: 0.78rem;
	font-weight: 500;
	color: #5580b0;
	margin-left: 2px;
}
.subrecinto-chevron {
	margin-left: auto;
	font-size: 0.78rem;
	color: #3a7bd5;
	opacity: 0.8;
}
.subrecinto-body {
	border-top: 1px solid #d0e2f5;
	background: #f5f8fe;
}
.tabla-mini-upc {
	background: #f5f8fe;
}
.tabla-mini-head-upc {
	background: #dce9f8;
	color: #1a4d8f;
	border-left: 4px solid #3a7bd5;
}
.tabla-mini-row-upc {
	background: #f5f8fe;
	color: #1e3a6b;
	border-left: 4px solid #b8d0ef;
	&:hover { background: #eaf1fc; border-left-color: #3a7bd5; }
	&:last-child { border-bottom: none; }
}
.tabla-mini-vacia {
	padding: 10px 12px;
	font-size: 0.83rem;
	color: $color-texto-secundario;
	font-style: italic;
}
.tabla-mini {
	display: flex;
	flex-direction: column;
	gap: 0;
}
.tabla-mini-head {
	display: grid;
	grid-template-columns: 1fr 80px;
	font-size: 0.8rem;
	font-weight: 700;
	color: $color-primario;
	background: #eef5f9;
	border-radius: 0;
	padding: 8px 12px;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}
.tabla-mini-head-compartida {
	grid-template-columns: 1fr 1fr 80px;
}
.tabla-mini-row {
	display: grid;
	grid-template-columns: 1fr 80px;
	align-items: center;
	padding: 8px 12px;
	border-bottom: 1px solid $color-borde;
	font-weight: 600;
	color: $color-texto-principal;
}
.tabla-mini-row-compartida {
	grid-template-columns: 1fr 1fr 80px;
}
.compartida-recintos {
	font-size: 0.82rem;
	font-weight: 500;
	color: $color-texto-secundario;
}
.tabla-mini-row:last-child {
	border-bottom: none;
}
.tabla-mini-cantidad {
	text-align: right;
}
.nota-recinto {
	margin: 12px 0 0 0;
	font-size: 0.8rem;
	color: $color-texto-secundario;
	font-style: italic;
	line-height: 1.4;
}
.especifico-table {
	background: $color-blanco;
	max-width: 100%;
}
.lista-vacia {
	color: $color-texto-secundario;
	font-weight: 500;
	padding: 12px 0;
}

/* Cabeceras coloreadas para normativa y prestaciones */
.recinto-title-normativa {
	background: #fff7ed;
	color: #92400e;
	border-left: 4px solid #f59e0b;
	i { color: #d97706; }
	&:hover { background: #fef3c7; }
}
.recinto-title-prestaciones {
	background: #f5f3ff;
	color: #4c1d95;
	border-left: 4px solid #8b5cf6;
	i { color: #7c3aed; }
	&:hover { background: #ede9fe; }
}
.recinto-title-urpa {
	background: #e0f7fa;
	color: #006064;
	border-left: 4px solid #00bcd4;
	i { color: #0097a7; }
	&:hover { background: #b2ebf2; }
}
.recintos-grid-extra {
	margin-top: 18px;
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
	.resultados-header {
		flex-direction: column;
		align-items: flex-start;
	}
	.resumen-banner {
		flex-direction: column;
		align-items: flex-start;
	}
	.resumen-row {
		grid-template-columns: 1fr;
		text-align: left;
	}
	.row-origenes {
		justify-content: flex-start;
	}
	.row-total {
		text-align: left;
	}
	.recintos-grid {
		grid-template-columns: 1fr;
	}
	.especifico-table {
		max-width: 100%;
	}
}
</style>