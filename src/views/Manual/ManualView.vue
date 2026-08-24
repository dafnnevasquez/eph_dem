<template>
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
		</div>
	</nav>

	<div class="manual-page">
		<!-- HERO / BANNER -->
		<section class="hero hero-compact">
			<div class="hero-bg"></div>
			<div class="hero-content">
				<div class="hero-tag">MÓDULO EPHDEM</div>
				<h1 class="hero-title">Manual de Usuario</h1>
				<p class="hero-sub">Guía paso a paso para utilizar correctamente el sistema de Estudio de Preinversión Hospitalaria.</p>
			</div>
		</section>

		<main class="manual-content">
			<!-- BARRA DE NAVEGACIÓN -->
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

			<!-- INTRODUCCIÓN -->
			<section class="manual-intro">
				<div class="intro-icon"><i class="fa-solid fa-book-open"></i></div>
				<div>
					<h2 class="intro-title">¿Cómo usar EPHDEM?</h2>
					<p class="intro-desc">El sistema funciona en <strong>4 etapas secuenciales</strong>. Cada etapa depende de la anterior. Siga el flujo indicado a continuación para obtener resultados de equipamiento hospitalario.</p>
				</div>
			</section>

			<!-- FLUJO GENERAL -->
			<section class="flujo-row">
				<div class="flujo-step" v-for="(step, i) in flujoGeneral" :key="i" @click="scrollToPaso(step.id)">
					<div class="flujo-num">{{ i + 1 }}</div>
					<div class="flujo-icon"><i :class="step.icon"></i></div>
					<div class="flujo-label flujo-label-link">{{ step.label }}</div>
					<div v-if="i < flujoGeneral.length - 1" class="flujo-arrow"><i class="fa-solid fa-chevron-right"></i></div>
				</div>
			</section>

			<!-- ═══════════════════════════════════════════════════════════════ -->
			<!-- PASO 1: CREAR NUEVO PROYECTO                                   -->
			<!-- ═══════════════════════════════════════════════════════════════ -->
			<section id="paso-1" class="paso-card">
				<div class="paso-header">
					<div class="paso-num">Paso 1</div>
					<div class="paso-icon"><i class="fa-solid fa-plus-circle"></i></div>
					<div>
						<h3 class="paso-title">Crear un Nuevo Proyecto</h3>
						<p class="paso-desc">Para iniciar un nuevo estudio, haga clic en <strong>Nuevo proyecto → Atención cerrada</strong>. El proyecto se crea con su nombre y queda vinculado a su usuario.</p>
					</div>
				</div>
				<div class="paso-body">
					<!-- BOTÓN NUEVO PROYECTO (mockup) -->
					<div class="mockup-nuevo-row">
						<div class="mockup-btn-nuevo">
							<i class="fa-solid fa-plus"></i> Nuevo proyecto
						</div>
						<div class="mockup-submenu">
							<div class="mockup-submenu-item mockup-submenu-active">Atención cerrada <i class="fa-solid fa-arrow-right"></i></div>
							<div class="mockup-submenu-item">Atención abierta</div>
						</div>
					</div>
					<!-- FORMULARIO CREACIÓN -->
					<div class="mockup-form-proyecto">
						<div class="mockup-label">Nombre del proyecto</div>
						<div class="mockup-input"><span>Hospital San Martín</span></div>
						<div class="mockup-btn-row" style="margin-top:14px">
							<div class="mockup-btn mockup-btn-primary">Guardar</div>
							<div class="mockup-btn mockup-btn-out">Cancelar</div>
						</div>
					</div>
					<div class="paso-tips">
						<div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Haga clic en <strong>Nuevo proyecto → Atención cerrada</strong> para comenzar el flujo.</div>
						<div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> El nombre del proyecto debe ser único en el sistema.</div>
						<div class="tip"><i class="fa-solid fa-triangle-exclamation tip-icon tip-warn"></i> Al hacer <strong>Guardar</strong>, el proyecto queda registrado a su nombre de usuario.</div>
					</div>
				</div>
			</section>

			<!-- ═══════════════════════════════════════════════════════════════ -->
			<!-- PASO 2: PRESTACIONES (idéntico a la vista real)                -->
			<!-- ═══════════════════════════════════════════════════════════════ -->
			<section id="paso-2" class="paso-card">
				<div class="paso-header">
					<div class="paso-num">Paso 2</div>
					<div class="paso-icon"><i class="fa-solid fa-list-check"></i></div>
					<div>
						<h3 class="paso-title">Selección de Prestaciones</h3>
						<p class="paso-desc">Seleccione las prestaciones de salud que formarán parte del proyecto usando los paneles de <strong>Disponibles</strong> y <strong>Seleccionadas</strong>.</p>
					</div>
				</div>
				<div class="paso-body">
					<!-- INSTRUCCIÓN (igual a la vista real) -->
					<div class="instruccion-indicator">
						<span class="instruccion-icon-circle"><i class="fa-solid fa-circle-info"></i></span>
						<span class="instruccion-texto">
							Selecciona las prestaciones que se asociarán al proyecto: usa
							<span class="instruccion-badge instruccion-badge--agregar"><i class="fa-solid fa-plus"></i></span>
							para agregar y
							<span class="instruccion-badge instruccion-badge--quitar"><i class="fa-solid fa-xmark"></i></span>
							para eliminar de la selección. Luego presiona <strong>Guardar y confirmar</strong> para guardar la selección.
						</span>
					</div>

					<!-- FILTROS (igual a la vista real) -->
					<div class="filtros-panel-mockup">
						<div class="filtro-mockup filtro-buscar-mockup">
							<div class="filtro-label-mockup">Buscar</div>
							<div class="mockup-input"><span class="mockup-placeholder">Código o nombre</span></div>
						</div>
						<div class="filtro-mockup">
							<div class="filtro-label-mockup">Área</div>
							<div class="mockup-select"><span>Pabellón quirúrgico</span> <i class="fa-solid fa-chevron-down"></i></div>
						</div>
						<div class="filtro-mockup">
							<div class="filtro-label-mockup">Subárea</div>
							<div class="mockup-select"><span>Todas</span> <i class="fa-solid fa-chevron-down"></i></div>
						</div>
						<div class="filtro-mockup">
							<div class="filtro-label-mockup">Recinto</div>
							<div class="mockup-select"><span>Todos</span> <i class="fa-solid fa-chevron-down"></i></div>
						</div>
					</div>

					<!-- GRID DOS PANELES (igual a la vista real) -->
					<div class="prestaciones-grid-mockup">
						<!-- PANEL DISPONIBLES -->
						<div class="prestaciones-panel-mockup">
							<div class="panel-title-mockup">Disponibles</div>
							<div class="prestaciones-lista-mockup">
								<div class="prestacion-item-mockup">
									<div class="prestacion-info-mockup">
										<div class="prestacion-codigo-mockup">08-03-0100</div>
										<div class="prestacion-nombre-mockup">Colecistectomía laparoscópica</div>
									</div>
									<div class="accion-mockup accion-agregar-mockup"><i class="fa-solid fa-plus"></i></div>
								</div>
								<div class="prestacion-item-mockup">
									<div class="prestacion-info-mockup">
										<div class="prestacion-codigo-mockup">08-03-0200</div>
										<div class="prestacion-nombre-mockup">Apendicectomía</div>
									</div>
									<div class="accion-mockup accion-agregar-mockup"><i class="fa-solid fa-plus"></i></div>
								</div>
								<div class="prestacion-item-mockup">
									<div class="prestacion-info-mockup">
										<div class="prestacion-codigo-mockup">08-04-0100</div>
										<div class="prestacion-nombre-mockup">Herniorrafia inguinal</div>
									</div>
									<div class="accion-mockup accion-agregar-mockup"><i class="fa-solid fa-plus"></i></div>
								</div>
							</div>
						</div>
						<!-- PANEL SELECCIONADAS -->
						<div class="prestaciones-panel-mockup">
							<div class="panel-title-mockup">Seleccionadas</div>
							<div class="prestaciones-lista-mockup">
								<div class="prestacion-item-mockup prestacion-seleccionada-mockup">
									<div class="prestacion-info-mockup">
										<div class="prestacion-codigo-mockup">08-03-0100</div>
										<div class="prestacion-nombre-mockup">Colecistectomía laparoscópica</div>
									</div>
									<div class="accion-mockup accion-quitar-mockup"><i class="fa-solid fa-xmark"></i></div>
								</div>
								<div class="lista-vacia-mockup">Agrega más prestaciones desde el panel izquierdo.</div>
							</div>
						</div>
					</div>

					<!-- BARRA ACCIONES FINALES -->
					<div class="acciones-finales-mockup">
						<div class="acciones-resumen-mockup">Prestaciones seleccionadas: <b>1</b></div>
						<div class="mockup-btn mockup-btn-primary">Guardar y confirmar</div>
					</div>

					<div class="paso-tips">
						<div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Use <span class="instruccion-badge instruccion-badge--agregar"><i class="fa-solid fa-plus"></i></span> en "Disponibles" para mover una prestación a "Seleccionadas".</div>
						<div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Use <span class="instruccion-badge instruccion-badge--quitar"><i class="fa-solid fa-xmark"></i></span> en "Seleccionadas" para retirarla de la lista.</div>
						<div class="tip"><i class="fa-solid fa-triangle-exclamation tip-icon tip-warn"></i> Debe seleccionar al menos una prestación antes de <strong>Guardar y confirmar</strong>.</div>
					</div>
				</div>
			</section>

			<!-- ═══════════════════════════════════════════════════════════════ -->
			<!-- PASO 3: PARÁMETROS                                             -->
			<!-- ═══════════════════════════════════════════════════════════════ -->
			<section id="paso-3" class="paso-card">
				<div class="paso-header">
					<div class="paso-num">Paso 3</div>
					<div class="paso-icon"><i class="fa-solid fa-sliders"></i></div>
					<div>
						<h3 class="paso-title">Configuración de Parámetros</h3>
						<p class="paso-desc">Para cada prestación seleccionada, ingrese los valores de demanda, ocupación y tiempo de procedimiento. Use la calculadora para obtener los coeficientes derivados.</p>
					</div>
				</div>
				<div class="paso-body">
					<!-- CALCULADORA: solo el botón cerrado (igual a la vista real) -->
					<div class="mockup-calc-toggle-row">
						<div class="mockup-calc-toggle">
							<span class="mockup-calc-icono"><i class="fa-solid fa-calculator"></i></span>
							<span class="mockup-calc-texto">Calculadora de días cama para UPC</span>
							<i class="fa-solid fa-chevron-down mockup-calc-chevron"></i>
						</div>
						<p class="mockup-calc-desc">Herramienta auxiliar para calcular datos derivados necesarios para algunos tipos de prestación. Haga clic en el botón para desplegarla.</p>
					</div>

					<!-- TABLA PARÁMETROS (campos reales) -->
					<div class="mockup-params-table">
						<div class="mockup-params-head mockup-params-head-real">
							<span>Prestación</span>
							<span>Demanda</span>
							<span>Días al año disp.</span>
							<span>T. Procedimiento (min)</span>
							<span>Disponibilidad</span>
							<span>Jornada laboral</span>
						</div>
						<div class="mockup-params-row mockup-params-row-real">
							<span class="mockup-row-name">Colecistectomía laparoscópica</span>
							<span><div class="mockup-input-sm">450</div></span>
							<span><div class="mockup-input-sm">250</div></span>
							<span><div class="mockup-input-sm">60</div></span>
							<span><div class="mockup-input-sm">85</div></span>
							<span><div class="mockup-input-sm">8</div></span>
						</div>
					</div>

					<div class="mockup-btn-row" style="margin-top:4px">
						<div class="mockup-btn mockup-btn-primary"><i class="fa-solid fa-calculator"></i> Calcular y ver Resultados</div>
					</div>

					<div class="paso-tips">
						<div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Ningún campo puede quedar en 0 o vacío</strong> — el sistema mostrará un error de validación.</div>
						<div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Use la <strong>Calculadora de días cama</strong> si necesita datos auxiliares para su prestación.</div>
						<div class="tip"><i class="fa-solid fa-triangle-exclamation tip-icon tip-warn"></i> Haga clic en <strong>Calcular</strong> para procesar todos los datos y avanzar a Resultados.</div>
					</div>
				</div>
			</section>

			<!-- ═══════════════════════════════════════════════════════════════ -->
			<!-- PASO 4: RESULTADOS                                             -->
			<!-- ═══════════════════════════════════════════════════════════════ -->
			<section id="paso-4" class="paso-card">
				<div class="paso-header">
					<div class="paso-num">Paso 4</div>
					<div class="paso-icon"><i class="fa-solid fa-chart-bar"></i></div>
					<div>
						<h3 class="paso-title">Visualización de Resultados</h3>
						<p class="paso-desc">Revise el resumen de equipamiento calculado. Puede filtrar por tipo de equipo, recinto o prestación, ver detalles de URPA y exportar el informe.</p>
					</div>
				</div>
				<div class="paso-body">
					<div class="mockup-resultados">
						<div class="mockup-banner-res">
							<div><span class="mockup-metric-val">244</span><span class="mockup-metric-lab">Equipos (total)</span></div>
						</div>
						
						<div class="mockup-res-block">
							<div class="mockup-section-title">Recintos requeridos</div>
							<div class="mockup-pill-container">
								<div class="mockup-pill">Cubículo UTI <strong>1</strong></div>
								<div class="mockup-pill">Cubículo UCI <strong>1</strong></div>
							</div>
						</div>

						<div class="mockup-res-block">
							<div class="mockup-accordion mockup-accordion-resumen">
								<span class="mockup-acc-title">Resumen de equipos necesarios (total)</span>
								<div class="mockup-acc-icons">
									<div class="mockup-icon-box active"><i class="fa-solid fa-bars"></i></div>
									<div class="mockup-icon-box"><i class="fa-solid fa-table-cells-large"></i></div>
									<div class="mockup-icon-box"><i class="fa-solid fa-table-cells"></i></div>
									<i class="fa-solid fa-chevron-down dropdown-chev"></i>
								</div>
							</div>
						</div>

						<div class="mockup-res-block">
							<div class="mockup-section-title">Desglose de equipamiento</div>
							<div class="mockup-acc-grid">
								<div class="mockup-accordion mockup-accordion-small">
									<span>Cubículo UTI <span class="acc-num">(20)</span></span>
									<i class="fa-solid fa-chevron-down dropdown-chev"></i>
								</div>
								<div class="mockup-accordion mockup-accordion-small">
									<span>Cubículo UCI <span class="acc-num">(26)</span></span>
									<i class="fa-solid fa-chevron-down dropdown-chev"></i>
								</div>
								<div class="mockup-accordion mockup-accordion-normativa">
									<span><i class="fa-solid fa-file-lines"></i> Equipamiento por normativa y/o guías <span class="acc-num">(50)</span></span>
									<i class="fa-solid fa-chevron-down dropdown-chev"></i>
								</div>
							</div>
						</div>
					</div>

					<div class="mockup-export-row" style="margin-top: 16px;">
						<div class="mockup-btn mockup-btn-excel"><i class="fa-solid fa-file-excel"></i> Excel</div>
						<div class="mockup-btn mockup-btn-pdf"><i class="fa-solid fa-file-pdf"></i> PDF</div>
					</div>
					<div class="paso-tips">
						<div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> La vista de resultados presenta primero un resumen total y luego un desglose detallado, la cantidad de equipamiento se encuentra disponible en viñetas desplegables para un uso mas cómodo, solo haz click en ellas para ver la cantidad de equipamiento estimado.</div>
						<div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Puedes descargar el informe en formato excel o pdf, para ello debes presionar los botones dispuestos en la parte superior de la pagina.</div>
					</div>
				</div>
			</section>

			<!-- ═══════════════════════════════════════════════════════════════ -->
			<!-- PASO 5: GESTIÓN DE PROYECTOS                                   -->
			<!-- ═══════════════════════════════════════════════════════════════ -->
			<section id="paso-5" class="paso-card">
				<div class="paso-header">
					<div class="paso-num">Paso 5</div>
					<div class="paso-icon"><i class="fa-solid fa-folder-open"></i></div>
					<div>
						<h3 class="paso-title">Gestión de Proyectos</h3>
						<p class="paso-desc">Al ingresar al sistema verá su listado de proyectos. Puede <strong>visualizar</strong> o <strong>crear nuevos proyectos</strong>, los cuales quedan ligados a su cuenta de usuario. Además podrá ordenarlos por fecha o alfabéticamente.</p>
					</div>
				</div>
				<div class="paso-body">
					<div class="mockup-table">
						<div class="mockup-table-head">
							<span>Nombre del proyecto</span>
							<span>Fecha</span>
							<span>Tipo</span>
							<span>Acciones</span>
						</div>
						<div class="mockup-table-row">
							<span class="mockup-row-name">Hospital San Martín</span>
							<span>12/06/2026</span>
							<span><div class="mockup-chip">Atención cerrada</div></span>
							<span class="mockup-actions">
								<div class="mockup-btn mockup-btn-sm mockup-btn-primary">Ver</div>
							</span>
						</div>
						<div class="mockup-table-row">
							<span class="mockup-row-name">Hospital Regional Norte</span>
							<span>01/05/2026</span>
							<span><div class="mockup-chip">Atención cerrada</div></span>
							<span class="mockup-actions">
								<div class="mockup-btn mockup-btn-sm mockup-btn-primary">Ver</div>
							</span>
						</div>
					</div>
					<div class="paso-tips">
						<div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Solo verá los proyectos asociados a <strong>su cuenta de usuario</strong>.</div>
						<div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Use <strong>Ver</strong> para abrir un proyecto existente y visualizar los resultados calculados.</div>
						<div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Use el selector de orden para organizar sus proyectos por los más recientes, antiguos o alfabéticamente.</div>
					</div>

					<h4 class="sub-title-mockup"><i class="fa-solid fa-share-nodes"></i> Navegación y Edición del Proyecto</h4>
					<p class="paso-desc">Una vez que ingrese a <strong>Ver</strong> un proyecto, encontrará en la barra superior botones para re-ajustar su configuración en cualquier momento:</p>
					
					<div class="mockup-action-cards">
						<div class="mockup-action-card">
							<div class="mockup-btn mockup-btn-primary"><i class="fa-solid fa-sliders"></i> Editar parámetros</div>
							<p>Permite regresar a la tabla de configuración para ajustar las variables numéricas sin crear un nuevo proyecto.</p>
						</div>
						<div class="mockup-action-card">
							<div class="mockup-btn mockup-btn-primary"><i class="fa-solid fa-list-check"></i> Modificar prestaciones</div>
							<p>Permite volver a la pantalla de selección para agregar o quitar prestaciones del proyecto actual.</p>
						</div>
					</div>
				</div>
			</section>

			<!-- BARRA DE NAVEGACIÓN GLOBAL -->
			<section class="manual-navbar-guide">
				<h3 class="guide-title"><i class="fa-solid fa-compass"></i> Barra de navegación</h3>
				<p class="guide-desc">En todas las vistas del sistema encontrará una barra de navegación con los siguientes elementos:</p>
				<div class="navbar-guide-row">
					<div class="navbar-guide-item">
						<div class="mockup-btn mockup-btn-primary"><i class="fa-solid fa-arrow-left"></i> Volver</div>
						<p>Regresa a la vista anterior sin perder la sesión.</p>
					</div>
					<div class="navbar-guide-item">
						<div class="mockup-btn mockup-btn-primary"><i class="fa-solid fa-house-user"></i> Inicio</div>
						<p>Navega directamente a la pantalla de inicio del módulo.</p>
					</div>
					<div class="navbar-guide-item">
						<div class="mockup-session">
							<i class="fa-solid fa-circle-user"></i>
							<span>usuario@uv.cl</span>
							<div class="mockup-btn-logout"><i class="fa-solid fa-right-from-bracket"></i></div>
						</div>
						<p>Muestra el correo de sesión activa. El ícono <i class="fa-solid fa-right-from-bracket"></i> cierra la sesión.</p>
					</div>
				</div>
			</section>

		</main>
	</div>

	<!-- BOTTOM BAR -->
	<footer class="sigem-bottomline" id="footer">
		<div class="sigem-bottomline-content">
			<div class="sigem-bottomline-left">
				<img src="https://sigem-uv.cl/_general/logos/LOGO_SIGEM-UV_HORIZONTAL-BLANCO.png" alt="SIGEM-UV" height="48" />
			</div>
			<div class="sigem-bottomline-center">
				<div>Gral. Cruz 222, Valparaíso ::: +56 32 2603662</div>
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
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const flujoGeneral = [
	{ icon: 'fa-solid fa-plus-circle',  label: 'Crear proyecto',          id: 'paso-1' },
	{ icon: 'fa-solid fa-list-check',   label: 'Selección de prestaciones', id: 'paso-2' },
	{ icon: 'fa-solid fa-sliders',      label: 'Parámetros',              id: 'paso-3' },
	{ icon: 'fa-solid fa-chart-bar',    label: 'Resultados',              id: 'paso-4' },
	{ icon: 'fa-solid fa-folder-open',  label: 'Gestión de proyectos',    id: 'paso-5' },
]

function scrollToPaso(id) {
	const el = document.getElementById(id)
	if (!el) return
	const rect = el.getBoundingClientRect()
	const topbarOffset = 110 // altura aproximada de los dos topbars
	const viewportCenter = (window.innerHeight - topbarOffset) / 2
	const elCenter = rect.top + rect.height / 2
	window.scrollBy({ top: elCenter - viewportCenter - topbarOffset / 2, behavior: 'smooth' })
}

function volverAtras() {
	router.back()
	setTimeout(() => window.scrollTo({ top: 0, left: 0, behavior: 'auto' }), 0)
}

function cerrarSesion() {
	authStore.logout()
	router.push('/login')
}
</script>

<style lang="scss" scoped>
@import '@/assets/styles/variables';

// ─── TOPBARS ────────────────────────────────────────────────
.sigem-topbar1 {
	width: 100%; background: #003c58;
	display: flex; align-items: center; justify-content: center;
	position: relative; padding: 10px 0; min-height: 56px; z-index: 1001;
}
.sigem-topbar1-center {
	display: flex; align-items: center; gap: 32px;
	position: absolute; left: 50%; transform: translateX(-50%);
}
.sigem-topbar1-logo { opacity: 0.8; transition: opacity 0.2s; &:hover { opacity: 1; } }
.sigem-topbar1-acceder {
	color: #fff; font-weight: 500; text-decoration: none; font-size: 1.08rem;
	margin-right: 32px; cursor: pointer; position: absolute; right: 0;
	background: none !important; border: none !important; padding: 0 !important;
}
.sigem-topbar2 {
	position: sticky; top: 0; width: 100%;
	background: $color-blanco; box-shadow: 0 2px 8px rgba(0,0,0,0.15); z-index: 1000;
}

// ─── HERO ───────────────────────────────────────────────────
.hero { background: $color-secundario; position: relative; padding: 38px 48px; overflow: hidden; text-align: center; }
.hero-compact { padding: 28px 48px; }
.hero-bg { position: absolute; inset: 0; background: url('@/assets/img/mac.jpg') center/cover no-repeat; }
.hero-content { position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; gap: 10px; }
.hero-tag { font-size: 12px; color: rgba($color-blanco, 0.35); letter-spacing: 2.5px; text-transform: uppercase; }
.hero-title { font-size: 26px; font-weight: 500; color: $color-blanco; margin: 0; }
.hero-sub { font-size: 14px; color: rgba($color-blanco, 0.6); max-width: 640px; line-height: 1.5; margin: 0; }

// ─── LAYOUT ─────────────────────────────────────────────────
.manual-page { background: $color-fondo; }
.manual-content { max-width: 1100px; margin: 32px auto 72px auto; padding: 0 28px; display: flex; flex-direction: column; gap: 28px; }

// ─── NAV BAR ────────────────────────────────────────────────
.nav-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.nav-buttons { display: flex; gap: 10px; }
.btn-back {
	background: $color-primario; color: $color-blanco; border: 1px solid $color-primario;
	border-radius: 999px; padding: 6px 14px; font-weight: 600; cursor: pointer;
	transition: background 0.2s;
	&:hover { background: mix($color-blanco, $color-primario, 6%); }
}
.session-badge {
	display: flex; align-items: center; gap: 8px;
	padding: 6px 14px 6px 10px;
	background: rgba(0, 60, 88, 0.06); border: 1.5px solid rgba(0, 60, 88, 0.18);
	border-radius: 999px; color: $color-primario; font-size: 0.88rem; font-weight: 600;
}
.session-nombre { white-space: nowrap; }
.btn-logout {
	background: none; border: none; color: $color-primario;
	cursor: pointer; padding: 2px 4px; font-size: 0.95rem; opacity: 0.7;
	transition: opacity 0.2s, color 0.2s;
	&:hover { opacity: 1; color: #c62828; }
}

// ─── INTRO ──────────────────────────────────────────────────
.manual-intro {
	display: flex; align-items: flex-start; gap: 18px;
	background: $color-blanco; border-radius: 16px; padding: 24px 28px;
	border: 1px solid $color-borde; box-shadow: 0 6px 18px $color-sombra-suave;
}
.intro-icon {
	flex: 0 0 auto; width: 48px; height: 48px; border-radius: 14px;
	background: $color-primario; color: $color-blanco;
	display: flex; align-items: center; justify-content: center; font-size: 1.4rem;
}
.intro-title { font-size: 1.25rem; font-weight: 700; color: $color-primario; margin: 0 0 6px; }
.intro-desc { color: $color-texto-secundario; margin: 0; line-height: 1.6; }

// ─── FLUJO ──────────────────────────────────────────────────
.flujo-row {
	display: flex; align-items: center; justify-content: space-between;
	background: $color-blanco; border-radius: 16px; padding: 20px 28px;
	border: 1px solid $color-borde; box-shadow: 0 6px 18px $color-sombra-suave;
	flex-wrap: nowrap; overflow-x: auto; gap: 0; width: 100%;
}
.flujo-step {
	display: flex; align-items: center; gap: 8px; cursor: pointer;
	flex: 1 1 0; justify-content: center;
	&:first-child { justify-content: flex-start; }
	&:last-child  { justify-content: flex-end; }
}
.flujo-label-link {
	font-weight: 700; color: $color-texto-principal; font-size: 0.86rem;
	text-decoration: none;
	&:hover { color: $color-primario; }
}
.flujo-num {
	width: 34px; height: 34px; border-radius: 50%; flex-shrink: 0;
	background: $color-primario; color: $color-blanco;
	font-weight: 700; font-size: 0.9rem;
	display: flex; align-items: center; justify-content: center;
}
.flujo-icon { font-size: 1.4rem; color: $color-primario; flex-shrink: 0; }
.flujo-label { font-weight: 600; color: $color-texto-principal; font-size: 0.92rem; }
.flujo-arrow { color: rgba($color-primario, 0.3); font-size: 0.9rem; margin: 0 6px; flex-shrink: 0; }

// ─── PASO CARD ──────────────────────────────────────────────
.paso-card {
	background: $color-blanco; border-radius: 18px;
	border: 1px solid $color-borde; box-shadow: 0 8px 22px $color-sombra-suave; overflow: hidden;
}
.paso-header {
	display: flex; align-items: flex-start; gap: 16px;
	padding: 22px 28px; border-bottom: 1px solid $color-borde; background: #f5f9fc;
}
.paso-num {
	flex-shrink: 0; background: $color-primario; color: $color-blanco;
	font-size: 0.78rem; font-weight: 700; letter-spacing: 1px;
	text-transform: uppercase; padding: 4px 10px; border-radius: 999px; align-self: flex-start;
}
.paso-icon {
	flex-shrink: 0; width: 44px; height: 44px; border-radius: 12px;
	background: rgba($color-primario, 0.1); color: $color-primario;
	display: flex; align-items: center; justify-content: center; font-size: 1.3rem;
}
.paso-title { font-size: 1.1rem; font-weight: 700; color: $color-primario; margin: 0 0 4px; }
.paso-desc { color: $color-texto-secundario; margin: 0; font-size: 0.93rem; line-height: 1.5; }
.paso-body { padding: 24px 28px; display: flex; flex-direction: column; gap: 18px; }

// ─── TIPS ───────────────────────────────────────────────────
.paso-tips { display: flex; flex-direction: column; gap: 8px; }
.tip {
	display: flex; align-items: flex-start; gap: 10px;
	font-size: 0.88rem; color: $color-texto-secundario; line-height: 1.5;
}
.tip-icon { color: $color-primario; flex-shrink: 0; margin-top: 2px; }
.tip-warn { color: #d97706; }

// ─── INSTRUCCIÓN (igual a la vista real) ─────────────────────
.instruccion-indicator {
	display: flex; align-items: center; gap: 12px;
	background: rgba($color-primario, 0.04);
	border: 1px solid rgba(0, 60, 88, 0.14);
	border-radius: 10px; padding: 10px 16px;
}
.instruccion-icon-circle {
	display: flex; align-items: center; justify-content: center;
	color: $color-primario; font-size: 1.4rem; flex: 0 0 auto;
}
.instruccion-texto {
	font-size: 1.05rem; color: $color-primario; line-height: 1.8;
	strong { font-weight: 700; }
}
.instruccion-badge {
	display: inline-flex; align-items: center; gap: 5px;
	padding: 2px 9px; border-radius: 6px; font-size: 0.88rem;
	font-weight: 700; vertical-align: middle; white-space: nowrap;
}
.instruccion-badge--agregar { background: $color-exito; color: $color-blanco; }
.instruccion-badge--quitar  { background: $color-peligro; color: $color-blanco; }

// ─── FILTROS MOCKUP (igual a la vista real) ──────────────────
.filtros-panel-mockup {
	display: grid; grid-template-columns: minmax(0, 2fr) repeat(3, minmax(0, 1fr));
	gap: 16px; background: $color-blanco; border-radius: 16px;
	padding: 18px 20px; border: 1px solid $color-borde;
	box-shadow: 0 10px 22px $color-sombra-suave;
}
.filtro-mockup { display: flex; flex-direction: column; gap: 6px; }
.filtro-buscar-mockup { max-width: 100%; }
.filtro-label-mockup { font-weight: 600; color: $color-primario; font-size: 0.88rem; }

// ─── PANELES PRESTACIONES (igual a la vista real) ────────────
.prestaciones-grid-mockup {
	display: grid; grid-template-columns: repeat(2, 1fr); gap: 22px;
}
.prestaciones-panel-mockup {
	background: $color-blanco; border-radius: 18px; padding: 20px;
	border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave;
	min-height: 200px; display: flex; flex-direction: column;
}
.panel-title-mockup {
	font-size: 1.1rem; font-weight: 700; color: $color-primario; margin-bottom: 14px;
}
.prestaciones-lista-mockup { display: flex; flex-direction: column; gap: 12px; }
.prestacion-item-mockup {
	display: flex; align-items: center; justify-content: space-between; gap: 12px;
	padding: 12px 14px; border-radius: 12px;
	background: $color-claro; border: 1px solid $color-borde;
}
.prestacion-seleccionada-mockup {
	border-color: $color-primario; background: rgba($color-primario, 0.04);
}
.prestacion-info-mockup { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; gap: 4px; }
.prestacion-codigo-mockup { font-size: 0.85rem; font-weight: 700; color: $color-primario; }
.prestacion-nombre-mockup { font-size: 0.9rem; font-weight: 500; color: $color-texto-principal; line-height: 1.25; }
.accion-mockup {
	flex: 0 0 auto; width: 34px; height: 34px; border-radius: 8px;
	color: $color-blanco; font-weight: 700;
	display: flex; align-items: center; justify-content: center;
}
.accion-agregar-mockup { background: $color-exito; }
.accion-quitar-mockup  { background: $color-peligro; }
.lista-vacia-mockup {
	background: $color-claro; padding: 18px; border-radius: 12px;
	text-align: center; color: $color-texto-secundario; font-size: 0.9rem;
	border: 1px dashed $color-borde;
}
.acciones-finales-mockup {
	display: flex; align-items: center; justify-content: space-between; gap: 16px;
	background: $color-blanco; border-radius: 14px; padding: 14px 18px;
	border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave;
}
.acciones-resumen-mockup { color: $color-texto-principal; font-weight: 500; }

// ─── MOCKUP NUEVO PROYECTO ───────────────────────────────────
.mockup-nuevo-row { display: flex; align-items: flex-start; gap: 14px; flex-wrap: wrap; }
.mockup-btn-nuevo {
	display: inline-flex; align-items: center; gap: 8px;
	background: $color-primario; color: $color-blanco;
	border-radius: 12px; padding: 12px 20px; font-weight: 700; font-size: 1rem;
	box-shadow: 0 4px 14px rgba(0, 60, 88, 0.25);
}
.mockup-submenu {
	display: flex; flex-direction: column; gap: 6px;
	border: 1px solid $color-borde; border-radius: 12px;
	overflow: hidden; background: $color-blanco;
	box-shadow: 0 6px 16px rgba(0,0,0,0.12);
}
.mockup-submenu-item {
	padding: 10px 16px; font-weight: 600; font-size: 0.92rem;
	color: $color-texto-principal; display: flex; align-items: center;
	justify-content: space-between; gap: 8px;
}
.mockup-submenu-active {
	background: rgba($color-primario, 0.08); color: $color-primario;
}
.mockup-form-proyecto {
	background: #f5f9fc; border-radius: 12px; padding: 20px 24px;
	border: 1px solid $color-borde; max-width: 420px;
}

// ─── MOCKUPS GENÉRICOS ──────────────────────────────────────
.mockup-input {
	border: 1px solid $color-borde; border-radius: 8px; padding: 8px 12px;
	background: $color-blanco; font-size: 0.9rem; color: $color-texto-principal;
}
.mockup-input-sm {
	border: 1px solid $color-borde; border-radius: 8px; padding: 5px 10px;
	background: $color-blanco; font-size: 0.88rem; text-align: center;
	color: $color-texto-principal; font-weight: 600;
}
.mockup-placeholder { color: rgba($color-texto-principal, 0.35); }
.mockup-select {
	display: flex; align-items: center; justify-content: space-between;
	border: 1px solid $color-borde; border-radius: 8px;
	padding: 8px 12px; background: $color-blanco;
	font-size: 0.88rem; color: $color-texto-principal;
}
.mockup-btn-row { display: flex; gap: 10px; flex-wrap: wrap; }
.mockup-btn {
	display: inline-flex; align-items: center; gap: 7px;
	padding: 8px 16px; border-radius: 8px; font-weight: 600;
	font-size: 0.88rem; user-select: none;
}
.mockup-btn-primary { background: $color-primario; color: $color-blanco; }
.mockup-btn-sec     { background: $color-secundario; color: $color-blanco; border-radius: 7px; padding: 6px 12px; font-size: 0.82rem; }
.mockup-btn-out     { background: none; color: $color-primario; border: 1px solid $color-primario; border-radius: 7px; padding: 6px 12px; font-size: 0.82rem; }
.mockup-btn-sm      { padding: 5px 10px; font-size: 0.8rem; }
.mockup-btn-excel   { background: none; color: #1e8e5a; border: 1px solid rgba(30,142,90,0.35); }
.mockup-btn-pdf     { background: none; color: #d5431c; border: 1px solid rgba(213,67,28,0.3); }
.mockup-btn-logout  {
	width: 28px; height: 28px; border-radius: 50%;
	background: rgba($color-primario, 0.08); color: $color-primario;
	display: flex; align-items: center; justify-content: center; font-size: 0.85rem;
}
.mockup-chip {
	display: inline-flex; align-items: center; justify-content: center;
	padding: 3px 10px; border-radius: 999px;
	background: rgba(0,60,88,0.08); color: $color-primario; font-weight: 600; font-size: 0.8rem;
}
.mockup-actions { display: flex; gap: 6px; }
.mockup-row-name { font-weight: 600; }

.mockup-table { border: 1px solid $color-borde; border-radius: 12px; overflow: hidden; }
.mockup-table-head {
	display: grid; grid-template-columns: 2fr 1fr 1.4fr 1.2fr;
	gap: 12px; background: #e9f1f6; padding: 10px 14px;
	font-weight: 700; font-size: 0.82rem; color: $color-primario;
}
.mockup-table-row {
	display: grid; grid-template-columns: 2fr 1fr 1.4fr 1.2fr;
	gap: 12px; padding: 12px 14px; border-top: 1px solid $color-borde;
	align-items: center; font-size: 0.88rem;
}

.mockup-params-table { border: 1px solid $color-borde; border-radius: 12px; overflow: hidden; }
.mockup-params-head {
	display: grid; grid-template-columns: 2.2fr 1fr 1fr 1.4fr 1fr 1fr;
	gap: 8px; background: #e9f1f6; padding: 10px 14px;
	font-weight: 700; font-size: 0.8rem; color: $color-primario;
}
.mockup-params-row {
	display: grid; grid-template-columns: 2.2fr 1fr 1fr 1.4fr 1fr 1fr;
	gap: 8px; padding: 12px 14px; border-top: 1px solid $color-borde; align-items: center;
}

.mockup-calc-hint {
	display: flex; align-items: center; gap: 10px;
	background: rgba($color-primario, 0.05); border: 1px solid rgba($color-primario, 0.15);
	border-radius: 10px; padding: 12px 16px;
	font-size: 0.88rem; color: $color-primario;
	i { font-size: 1.1rem; flex-shrink: 0; }
}

// Toggle calculadora cerrado (igual a la vista real)
.mockup-calc-toggle-row {
	display: flex; flex-direction: column; gap: 10px;
}
.mockup-calc-toggle {
	display: inline-flex; align-items: center; gap: 10px;
	padding: 10px 16px; border: 1.5px solid rgba($color-primario, 0.25);
	border-radius: 999px; background: rgba($color-primario, 0.05);
	color: $color-primario; font-weight: 700; font-size: 0.93rem;
	cursor: default; white-space: nowrap; width: fit-content;
}
.mockup-calc-icono {
	flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center;
	width: 26px; height: 26px; border-radius: 50%;
	background: rgba($color-primario, 0.12); color: $color-primario; font-size: 0.95rem;
}
.mockup-calc-texto { flex: 1 1 auto; font-size: 0.93rem; }
.mockup-calc-chevron { font-size: 0.85rem; opacity: 0.6; }
.mockup-calc-desc {
	font-size: 0.88rem; color: $color-texto-secundario;
	margin: 0; line-height: 1.5; padding-left: 4px;
}

// Tabla 3 columnas (parámetros reales)
.mockup-params-head-3 {
	grid-template-columns: 2fr 1fr 1fr 1.5fr !important;
}
.mockup-params-row-3 {
	grid-template-columns: 2fr 1fr 1fr 1.5fr !important;
}
// Tabla 6 columnas (parámetros reales completos)
.mockup-params-head-real {
	grid-template-columns: 2fr 1fr 1.2fr 1.6fr 1fr 1fr !important;
}
.mockup-params-row-real {
	grid-template-columns: 2fr 1fr 1.2fr 1.6fr 1fr 1fr !important;
}

/* Paso 4 - Resultados Mockup */
.mockup-resultados { display: flex; flex-direction: column; gap: 16px; width: 100%; }
.mockup-banner-res {
	display: flex; gap: 20px; align-items: baseline;
	background: $color-primario; border-radius: 12px; padding: 14px 20px;
}
.mockup-metric-val { font-size: 1.5rem; font-weight: 700; color: $color-blanco; }
.mockup-metric-lab { font-size: 0.9rem; color: rgba(255, 255, 255, 0.8); margin-left: 6px; }

.mockup-res-block { background: $color-blanco; border: 1px solid $color-borde; border-radius: 10px; padding: 16px; box-shadow: 0 4px 10px rgba(0,0,0,0.02); }
.mockup-section-title { font-size: 0.95rem; font-weight: 700; color: $color-texto-principal; margin-bottom: 12px; }

.mockup-pill-container { display: flex; flex-wrap: wrap; gap: 12px; }
.mockup-pill {
	background: rgba(0, 60, 88, 0.05); border: 1px solid rgba(0, 60, 88, 0.1); border-radius: 999px;
	padding: 6px 16px; font-size: 0.9rem; color: $color-primario; display: flex; align-items: center; gap: 8px; font-weight: 500;
}
.mockup-pill strong { font-size: 1.1rem; }

.mockup-accordion {
	background: rgba(0, 60, 88, 0.03); border: 1px solid rgba(0, 60, 88, 0.08); border-radius: 8px;
	padding: 10px 16px; display: flex; justify-content: space-between; align-items: center;
	color: $color-primario; font-weight: 600; font-size: 0.9rem;
}
.mockup-accordion-resumen { padding: 8px 16px; }
.mockup-acc-title { font-size: 0.95rem; }
.mockup-acc-icons { display: flex; align-items: center; gap: 8px; }
.mockup-icon-box { padding: 4px 6px; border-radius: 4px; color: rgba(0, 60, 88, 0.5); font-size: 1.1rem; }
.mockup-icon-box.active { background: $color-primario; color: $color-blanco; }
.dropdown-chev { color: rgba(0, 60, 88, 0.5); margin-left: 10px; }

.mockup-acc-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
.acc-num { color: rgba(0, 60, 88, 0.6); font-weight: 400; margin-left: 4px; }
.mockup-accordion-normativa {
	background: rgba(255, 152, 0, 0.08); border: 1px solid rgba(255, 152, 0, 0.2); color: #d35400;
}
.mockup-accordion-normativa .acc-num { color: rgba(211, 84, 0, 0.7); }
.mockup-accordion-normativa .dropdown-chev { color: rgba(211, 84, 0, 0.5); }
.mt-2 { margin-top: 12px; }

/* Paso 5 - Gestión Mockup */
.sub-title-mockup { font-size: 1.1rem; font-weight: 700; color: $color-primario; margin-top: 24px; margin-bottom: 8px; border-top: 1px solid $color-borde; padding-top: 20px; }
.mockup-action-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px; }
.mockup-action-card {
	background: rgba(0, 60, 88, 0.02); border: 1px dashed rgba(0, 60, 88, 0.2); border-radius: 8px; padding: 16px;
	display: flex; flex-direction: column; align-items: flex-start; gap: 10px;
}
.mockup-action-card p { font-size: 0.85rem; color: $color-texto-secundario; margin: 0; line-height: 1.4; }

.mockup-export-row { display: flex; gap: 10px; }

.mockup-session {
	display: inline-flex; align-items: center; gap: 8px;
	padding: 6px 14px 6px 10px;
	background: rgba(0, 60, 88, 0.06); border: 1.5px solid rgba(0, 60, 88, 0.18);
	border-radius: 999px; color: $color-primario; font-size: 0.88rem; font-weight: 600;
}

// ─── NAVBAR GUIDE ───────────────────────────────────────────
.manual-navbar-guide {
	background: $color-blanco; border-radius: 18px;
	border: 1px solid $color-borde; box-shadow: 0 8px 22px $color-sombra-suave;
	padding: 24px 28px;
}
.guide-title {
	font-size: 1.1rem; font-weight: 700; color: $color-primario;
	margin: 0 0 6px; display: flex; align-items: center; gap: 10px;
}
.guide-desc { color: $color-texto-secundario; font-size: 0.9rem; margin: 0 0 20px; }
.navbar-guide-row { display: flex; gap: 24px; flex-wrap: wrap; }
.navbar-guide-item {
	flex: 1 1 200px; display: flex; flex-direction: column; align-items: flex-start; gap: 10px;
	p { font-size: 0.85rem; color: $color-texto-secundario; margin: 0; line-height: 1.5; }
}

// ─── BOTTOMBAR ──────────────────────────────────────────────
.sigem-bottomline { width: 100%; background: #003c58; color: #fff; padding: 0; margin-top: 48px; }
.sigem-bottomline-content {
	display: flex; align-items: center; justify-content: space-between;
	max-width: 1400px; margin: 0 auto; padding: 12px 24px; flex-wrap: wrap;
}
.sigem-bottomline-left, .sigem-bottomline-right { flex: 0 0 auto; display: flex; align-items: center; }
.sigem-bottomline-center { flex: 1 1 400px; text-align: center; font-size: 1.05rem; }
.sigem-bottomline-social { margin: 8px 0; display: flex; gap: 10px; justify-content: center; }
.sigem-bottomline-copy { font-size: 0.95rem; opacity: 0.8; margin-top: 4px; }

@media (max-width: 780px) {
	.filtros-panel-mockup { grid-template-columns: 1fr; }
	.prestaciones-grid-mockup { grid-template-columns: 1fr; }
	.flujo-row { flex-direction: column; }
	.mockup-params-head,
	.mockup-params-row { grid-template-columns: 1fr 1fr 1fr; }
}
</style>
