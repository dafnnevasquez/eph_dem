<template>
  <AppLayout>
    <div class="manual-page">
      <section class="hero hero-compact">
        <div class="hero-bg"></div>
        <div class="hero-content">
          <div class="hero-tag">MÓDULO EPHDEM</div>
          <h1 class="hero-title">Manual de Usuario</h1>
          <p class="hero-sub">Todo lo que necesitas para usar EPHDEM, paso a paso.</p>
        </div>
      </section>

      <main class="manual-content">
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

        <!-- PESTAÑAS -->
        <nav class="tabs-manual" role="tablist">
          <button
            v-for="t in pestanas"
            :key="t.id"
            type="button"
            role="tab"
            class="tab-manual"
            :class="{ activa: pestana === t.id }"
            :aria-selected="pestana === t.id"
            @click="pestana = t.id"
          >
            <i :class="t.icon"></i>
            <span>{{ t.label }}</span>
          </button>
        </nav>

        <!-- ===================== PESTAÑA LECTURA ===================== -->
        <template v-if="pestana === 'lectura'">

        <!-- INTRODUCCIÓN -->
        <section class="manual-intro">
          <div class="intro-icon"><i class="fa-solid fa-book-open"></i></div>
          <div>
            <h2 class="intro-title">¿Para qué sirve EPHDEM?</h2>
            <p class="intro-desc">
              EPHDEM calcula cuántos equipos médicos necesita un hospital.
              Tú ingresas algunos datos del hospital y el sistema hace el cálculo por ti.
              Son <strong>5 pasos</strong> y hay que hacerlos <strong>en orden</strong>.
            </p>
          </div>
        </section>

        <!-- FLUJO -->
        <section class="flujo-row">
          <div class="flujo-step" v-for="(step, i) in flujoGeneral" :key="i" @click="scrollToPaso(step.id)">
            <div class="flujo-num">{{ i + 1 }}</div>
            <div class="flujo-icon"><i :class="step.icon"></i></div>
            <div class="flujo-label flujo-label-link">{{ step.label }}</div>
            <div v-if="i < flujoGeneral.length - 1" class="flujo-arrow"><i class="fa-solid fa-chevron-right"></i></div>
          </div>
        </section>

        <!-- PASO 1 -->
        <section id="paso-1" class="paso-card">
          <div class="paso-header">
            <div class="paso-num">Paso 1</div>
            <div class="paso-icon"><i class="fa-solid fa-plus-circle"></i></div>
            <div>
              <h3 class="paso-title">Crea un proyecto</h3>
              <p class="paso-desc">Un proyecto es el lugar donde se guarda tu estudio. Ponle un nombre fácil de reconocer, por ejemplo: <em>"Hospital UV 2026"</em>.</p>
            </div>
          </div>
          <div class="paso-body">
            <div class="mockup-nuevo-row">
              <div class="mockup-btn-nuevo"><i class="fa-solid fa-plus"></i> Nuevo proyecto</div>
              <div class="mockup-submenu">
                <div class="mockup-submenu-item mockup-submenu-active">Atención cerrada <i class="fa-solid fa-arrow-right"></i></div>
                <div class="mockup-submenu-item">Atención abierta</div>
              </div>
            </div>
            <div class="mockup-form-proyecto">
              <div class="mockup-label">Nombre del proyecto</div>
              <div class="mockup-input"><span>Hospital UV 2026</span></div>
              <div class="mockup-btn-row" style="margin-top:14px">
                <div class="mockup-btn mockup-btn-primary">Guardar</div>
                <div class="mockup-btn mockup-btn-out">Cancelar</div>
              </div>
            </div>
            <div class="paso-tips">
              <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Para empezar, haz clic en <strong>Nuevo proyecto</strong> y elige <strong>Atención cerrada o abierta</strong>.</div>
              <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Cada proyecto necesita un nombre distinto: no puedes repetir uno que ya usaste.</div>
              <div class="tip"><i class="fa-solid fa-triangle-exclamation tip-icon tip-warn"></i> El proyecto queda guardado en tu cuenta. Nadie más puede verlo.</div>
            </div>
          </div>
        </section>

        <!-- PASO 2 -->
        <section id="paso-2" class="paso-card">
          <div class="paso-header">
            <div class="paso-num">Paso 2</div>
            <div class="paso-icon"><i class="fa-solid fa-list-check"></i></div>
            <div>
              <h3 class="paso-title">Elige las prestaciones</h3>
              <p class="paso-desc">
                Las prestaciones son las atenciones y procedimientos que hará el hospital, por ejemplo una cirugía o consulta.
                Elige solo las que necesitas para tu estudio de preinversión hospitalaria.
              </p>
            </div>
          </div>
          <div class="paso-body">
            <div class="instruccion-indicator">
              <span class="instruccion-icon-circle"><i class="fa-solid fa-circle-info"></i></span>
              <span class="instruccion-texto">
                Aprieta <span class="instruccion-badge instruccion-badge--agregar"><i class="fa-solid fa-plus"></i></span> para agregar una prestación y
                <span class="instruccion-badge instruccion-badge--quitar"><i class="fa-solid fa-xmark"></i></span> para quitarla.
                Cuando termines, aprieta <strong>Guardar y confirmar</strong>.
              </span>
            </div>
            <div class="filtros-panel-mockup">
              <div class="filtro-mockup filtro-buscar-mockup">
                <div class="filtro-label-mockup">Buscar</div>
                <div class="mockup-input"><span class="mockup-placeholder">Escribe el nombre o código FONASA</span></div>
              </div>
              <div class="filtro-mockup">
                <div class="filtro-label-mockup">Área</div>
                <div class="mockup-select"><span>UPC</span> <i class="fa-solid fa-chevron-down"></i></div>
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
            <div class="prestaciones-grid-mockup">
              <div class="prestaciones-panel-mockup">
                <div class="panel-title-mockup">Disponibles</div>
                <div class="prestaciones-lista-mockup">
                  <div class="prestacion-item-mockup">
                    <div class="prestacion-info-mockup">
                      <div class="prestacion-codigo-mockup">1802081</div>
                      <div class="prestacion-nombre-mockup">Colecistectomía por videolaparoscopía, proc. completo</div>
                    </div>
                    <div class="accion-mockup accion-agregar-mockup"><i class="fa-solid fa-plus"></i></div>
                  </div>
                  <div class="prestacion-item-mockup">
                    <div class="prestacion-info-mockup">
                      <div class="prestacion-codigo-mockup">1802053</div>
                      <div class="prestacion-nombre-mockup">Apendicectomía y/o dren. absceso apendicular (proc. aut.)</div>
                    </div>
                    <div class="accion-mockup accion-agregar-mockup"><i class="fa-solid fa-plus"></i></div>
                  </div>
                </div>
              </div>
              <div class="prestaciones-panel-mockup">
                <div class="panel-title-mockup">Seleccionadas</div>
                <div class="prestaciones-lista-mockup">
                  <div class="prestacion-item-mockup prestacion-seleccionada-mockup">
                    <div class="prestacion-info-mockup">
                      <div class="prestacion-codigo-mockup">1802081</div>
                      <div class="prestacion-nombre-mockup">Colecistectomía por videolaparoscopía, proc. completo</div>
                    </div>
                    <div class="accion-mockup accion-quitar-mockup"><i class="fa-solid fa-xmark"></i></div>
                  </div>
                  <div class="lista-vacia-mockup">Agrega más prestaciones desde el panel izquierdo.</div>
                </div>
              </div>
            </div>
            <div class="acciones-finales-mockup">
              <div class="acciones-resumen-mockup">Prestaciones seleccionadas: <b>1</b></div>
              <div class="mockup-btn mockup-btn-primary">Guardar y confirmar</div>
            </div>
            <div class="paso-tips">
              <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Puedes buscar una prestación por su nombre o por su código FONASA.</div>
              <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> ¿Quieres todas las de la lista? Aprieta <strong>Agregar todas</strong> y se seleccionan de una vez.</div>
              <div class="tip"><i class="fa-solid fa-triangle-exclamation tip-icon tip-warn"></i> ¿Vas a usar UCI o UTI? Primero agrega <strong>"Día Cama UCI"</strong> o <strong>"Día Cama UTI"</strong>. Sin eso, el sistema no puede calcular los cubículos.</div>
            </div>
          </div>
        </section>

        <!-- PASO 3 -->
        <section id="paso-3" class="paso-card">
          <div class="paso-header">
            <div class="paso-num">Paso 3</div>
            <div class="paso-icon"><i class="fa-solid fa-sliders"></i></div>
            <div>
              <h3 class="paso-title">Ingresa los parámetros</h3>
              <p class="paso-desc">
                Para cada prestación llena una fila con los datos del hospital.
                Con estos números el sistema sabe cuánto se va a usar cada recinto.
              </p>
            </div>
          </div>
          <div class="paso-body">
            <div class="mockup-params-table">
              <div class="mockup-params-head mockup-params-head-real">
                <span>Prestación</span>
                <span>Demanda</span>
                <span>Días al año disponibles</span>
                <span>Tiempo procedimiento (min)</span>
                <span>Disponibilidad (%)</span>
                <span>Jornada laboral(hrs)</span>
              </div>
              <div class="mockup-params-row mockup-params-row-real">
                <span class="mockup-row-name">Colecistectomía por videolaparoscopía, proc. completo</span>
                <span><div class="mockup-input-sm">450</div></span>
                <span><div class="mockup-input-sm">250</div></span>
                <span><div class="mockup-input-sm">60</div></span>
                <span><div class="mockup-input-sm">85</div></span>
                <span><div class="mockup-input-sm">8</div></span>
              </div>
            </div>
            <div class="paso-tips">
              <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Demanda:</strong> cuántas veces al año se va a hacer la prestación.</div>
              <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Días al año disponibles:</strong> cuántos días al año funciona el recinto.</div>
              <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Tiempo procedimiento (min):</strong> cuántos minutos dura el procedimiento.</div>
              <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Disponibilidad (%):</strong> qué porcentaje del tiempo el equipo está disponible para usarse.</div>
              <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Jornada laboral (hrs):</strong> cuántas horas al día opera el recinto.</div>
              <div class="tip"><i class="fa-solid fa-triangle-exclamation tip-icon tip-warn"></i> No dejes casillas vacías ni en 0. Si falta algo, el sistema te avisa.</div>
              <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> ¿Un dato es igual para todas las prestaciones? Escríbelo una sola vez en la fila de arriba y haz click en <strong>Aplicar</strong>.</div>
            </div>
            <div class="mockup-calc-toggle-row">
              <div class="mockup-calc-toggle">
                <span class="mockup-calc-icono"><i class="fa-solid fa-calculator"></i></span>
                <span class="mockup-calc-texto">Calculadora Atencion Cerrada Pabellones</span>
                <i class="fa-solid fa-chevron-down mockup-calc-chevron"></i>
              </div>
              <p class="mockup-calc-desc">¿No sabes cuántos días cama poner en UCI o UTI? Esta calculadora te ayuda. Solo pide el coeficiente técnico, la población que atiende el hospital y cuántos días se queda en promedio un paciente.</p>
            </div>
            <div class="mockup-btn-row">
              <div class="mockup-btn mockup-btn-primary"><i class="fa-solid fa-calculator"></i> Calcular y ver Resultados</div>
            </div>
          </div>
        </section>

        <!-- PASO 4 -->
        <section id="paso-4" class="paso-card">
          <div class="paso-header">
            <div class="paso-num">Paso 4</div>
            <div class="paso-icon"><i class="fa-solid fa-chart-bar"></i></div>
            <div>
              <h3 class="paso-title">Mira los resultados</h3>
              <p class="paso-desc">
                Aquí ves cuántos equipos necesita el hospital, separados por recinto (UCI, UTI, pabellón, etc.).
                También puedes descargar el proyecto en pdf o excel.
              </p>
            </div>
          </div>
          <div class="paso-body">
            <div class="mockup-resultados">
              <div class="mockup-banner-res">
                <div><span class="mockup-metric-val">244</span><span class="mockup-metric-lab">Equipos en total</span></div>
              </div>
              <div class="mockup-res-block">
                <div class="mockup-section-title">Recintos que necesitan equipamiento</div>
                <div class="mockup-pill-container">
                  <div class="mockup-pill">Cubículo UTI <strong>1</strong></div>
                  <div class="mockup-pill">Cubículo UCI <strong>1</strong></div>
                </div>
              </div>
              <div class="mockup-res-block">
                <div class="mockup-acc-grid">
                  <div class="mockup-accordion mockup-accordion-small">
                    <span>Cubículo UTI <span class="acc-num">(20 equipos)</span></span>
                    <i class="fa-solid fa-chevron-down dropdown-chev"></i>
                  </div>
                  <div class="mockup-accordion mockup-accordion-small">
                    <span>Cubículo UCI <span class="acc-num">(26 equipos)</span></span>
                    <i class="fa-solid fa-chevron-down dropdown-chev"></i>
                  </div>
                </div>
              </div>
            </div>
            <div class="mockup-export-row" style="margin-top:16px">
              <div class="mockup-btn mockup-btn-excel"><i class="fa-solid fa-file-excel"></i> Descargar Excel</div>
              <div class="mockup-btn mockup-btn-pdf"><i class="fa-solid fa-file-pdf"></i> Descargar PDF</div>
            </div>
            <div class="paso-tips">
              <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Haz clic en un recinto para ver la lista de sus equipos.</div>
              <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Descarga el informe en Excel o PDF para guardarlo o compartirlo.</div>
            </div>
          </div>
        </section>

        <!-- PASO 5 -->
        <section id="paso-5" class="paso-card">
          <div class="paso-header">
            <div class="paso-num">Paso 5</div>
            <div class="paso-icon"><i class="fa-solid fa-folder-open"></i></div>
            <div>
              <h3 class="paso-title">Vuelve a tus proyectos</h3>
              <p class="paso-desc">
                Tus proyectos quedan guardados y puedes abrirlos cuando quieras.
                También puedes cambiarles las prestaciones o los datos sin empezar de cero.
              </p>
            </div>
          </div>
          <div class="paso-body">
            <div class="mockup-table">
              <div class="mockup-table-head">
                <span>Nombre del proyecto</span><span>Fecha</span><span>Tipo</span><span>Acciones</span>
              </div>
              <div class="mockup-table-row">
                <span class="mockup-row-name">Hospital UV 2026</span>
                <span>12/06/2026</span>
                <span><div class="mockup-chip">Atención cerrada</div></span>
                <span class="mockup-actions"><div class="mockup-btn mockup-btn-sm mockup-btn-primary">Ver</div></span>
              </div>
            </div>
            <div class="paso-tips">
              <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Solo tú ves tus proyectos, porque están en tu cuenta.</div>
              <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Aprieta <strong>Ver</strong> para abrir un proyecto y mirar sus resultados.</div>
              <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Desde los resultados puedes volver a cambiar los datos o las prestaciones.</div>
            </div>
            <h4 class="sub-title-mockup"><i class="fa-solid fa-share-nodes"></i> ¿Necesitas cambiar algo?</h4>
            <div class="mockup-action-cards">
              <div class="mockup-action-card">
                <div class="mockup-btn mockup-btn-primary"><i class="fa-solid fa-sliders"></i> Editar parámetros</div>
                <p>Cambia los números del estudio sin empezar de cero.</p>
              </div>
              <div class="mockup-action-card">
                <div class="mockup-btn mockup-btn-primary"><i class="fa-solid fa-list-check"></i> Modificar prestaciones</div>
                <p>Agrega o quita prestaciones y vuelve a calcular.</p>
              </div>
            </div>
          </div>
        </section>

        <!-- BARRA DE NAVEGACIÓN -->
        <section class="manual-navbar-guide">
          <h3 class="guide-title"><i class="fa-solid fa-compass"></i> Cómo moverte por la página</h3>
          <p class="guide-desc">Arriba de cada pantalla tienes estos botones:</p>
          <div class="navbar-guide-row">
            <div class="navbar-guide-item">
              <div class="mockup-btn mockup-btn-primary"><i class="fa-solid fa-arrow-left"></i> Volver</div>
              <p>Te lleva a la pantalla anterior.</p>
            </div>
            <div class="navbar-guide-item">
              <div class="mockup-btn mockup-btn-primary"><i class="fa-solid fa-house-user"></i> Inicio</div>
              <p>Te lleva a la pantalla de inicio.</p>
            </div>
            <div class="navbar-guide-item">
              <div class="mockup-session">
                <i class="fa-solid fa-circle-user"></i>
                <span>usuario@uv.cl</span>
                <div class="mockup-btn-logout"><i class="fa-solid fa-right-from-bracket"></i></div>
              </div>
              <p>Muestra con qué cuenta entraste. El ícono <i class="fa-solid fa-right-from-bracket"></i> cierra tu sesión.</p>
            </div>
          </div>
        </section>

        </template>

        <!-- ===================== PESTAÑA VIDEO ===================== -->
        <section v-else class="video-card">
          <div class="video-wrap">
            <iframe
              v-if="VIDEO_YOUTUBE_ID"
              :src="`https://www.youtube-nocookie.com/embed/${VIDEO_YOUTUBE_ID}?rel=0`"
              title="Manual de usuario EPHDEM"
              frameborder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowfullscreen
            ></iframe>
            <video v-else-if="VIDEO_MP4" :src="VIDEO_MP4" controls preload="metadata"></video>
            <div v-else class="video-placeholder">
              <i class="fa-solid fa-circle-play"></i>
              <span>Video del manual disponible próximamente</span>
            </div>
          </div>
          <p class="video-desc">Recorre en video los 5 pasos para usar EPHDEM, desde crear un proyecto hasta descargar los resultados.</p>
        </section>

      </main>
    </div>
  </AppLayout>
</template>
<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppLayout from '@/layouts/AppLayout.vue'

const router = useRouter()
const authStore = useAuthStore()

// Pestañas del manual
const pestanas = [
  { id: 'lectura', label: 'Lectura', icon: 'fa-solid fa-book-open' },
  { id: 'video',   label: 'Video',   icon: 'fa-solid fa-circle-play' },
]
const pestana = ref('lectura')

// Video del manual: completar UNA de las dos opciones
const VIDEO_YOUTUBE_ID = ''  // solo el ID de YouTube, ej: 'AbC123xYz'
const VIDEO_MP4 = ''         // o un .mp4 del servidor, ej: '/__v2/modulo_eph/docs/manual.mp4'

const flujoGeneral = [
  { icon: 'fa-solid fa-plus-circle',  label: 'Crear proyecto',           id: 'paso-1' },
  { icon: 'fa-solid fa-list-check',   label: 'Elegir prestaciones',      id: 'paso-2' },
  { icon: 'fa-solid fa-sliders',      label: 'Completar datos',         id: 'paso-3' },
  { icon: 'fa-solid fa-chart-bar',    label: 'Ver resultados',          id: 'paso-4' },
  { icon: 'fa-solid fa-folder-open',  label: 'Mis proyectos',           id: 'paso-5' },
]

function scrollToPaso(id) {
  const el = document.getElementById(id)
  if (!el) return
  const rect = el.getBoundingClientRect()
  const topbarOffset = 110
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

.manual-page { background: $color-fondo; flex: 1; }
.manual-content { width: 100%; max-width: none; margin: 32px 0 72px; padding: 0 40px; box-sizing: border-box; display: flex; flex-direction: column; gap: 32px; }

.hero { background: $color-secundario; position: relative; padding: 38px 48px; overflow: hidden; text-align: center; }
.hero-compact { padding: 28px 48px; }
.hero-bg { position: absolute; inset: 0; background: url('@/assets/img/mac.jpg') center/cover no-repeat; }
.hero-content { position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; gap: 10px; }
.hero-tag { font-size: 12px; color: rgba(255,255,255,0.35); letter-spacing: 2.5px; text-transform: uppercase; }
.hero-title { font-size: 26px; font-weight: 500; color: #fff; margin: 0; }
.hero-sub { font-size: 14px; color: rgba(255,255,255,0.6); max-width: 700px; line-height: 1.5; margin: 0; }

.nav-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.nav-buttons { display: flex; gap: 10px; }
.session-badge { display: flex; align-items: center; gap: 8px; padding: 6px 14px 6px 10px; background: rgba(0,60,88,0.06); border: 1.5px solid rgba(0,60,88,0.18); border-radius: 999px; color: $color-primario; font-size: 0.88rem; font-weight: 600; }
.session-nombre { white-space: nowrap; }
.btn-logout { background: none; border: none; color: $color-primario; cursor: pointer; padding: 2px 4px; opacity: 0.7; &:hover { opacity: 1; color: #c62828; } }
.btn-back { background: $color-primario; color: #fff; border: 1px solid $color-primario; border-radius: 999px; padding: 6px 12px; font-weight: 600; cursor: pointer; &:hover { background: mix(#fff, $color-primario, 6%); } }

.manual-intro { display: flex; align-items: flex-start; gap: 20px; background: #fff; border-radius: 16px; padding: 24px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; }
.intro-icon { font-size: 2rem; color: $color-primario; flex: 0 0 auto; }
.intro-title { font-size: 1.4rem; font-weight: 700; color: $color-primario; margin: 0 0 8px; }
.intro-desc { margin: 0; color: $color-texto-secundario; line-height: 1.6; }

.flujo-row { display: flex; align-items: center; justify-content: center; gap: 8px; flex-wrap: wrap; background: #fff; border-radius: 16px; padding: 20px 24px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; }
.flujo-step { display: flex; align-items: center; gap: 8px; cursor: pointer; }
.flujo-num { width: 28px; height: 28px; border-radius: 50%; background: $color-primario; color: #fff; font-weight: 700; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; flex: 0 0 auto; }
.flujo-icon { font-size: 1.1rem; color: $color-primario; }
.flujo-label { font-size: 0.9rem; font-weight: 600; color: $color-primario; }
.flujo-label-link { &:hover { text-decoration: underline; } }
.flujo-arrow { color: $color-borde; font-size: 0.8rem; }

.paso-card { background: #fff; border-radius: 16px; padding: 28px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; display: flex; flex-direction: column; gap: 20px; }
.paso-header { display: flex; align-items: flex-start; gap: 16px; }
.paso-num { background: $color-primario; color: #fff; border-radius: 8px; padding: 4px 12px; font-weight: 700; font-size: 0.85rem; flex: 0 0 auto; }
.paso-icon { font-size: 1.8rem; color: $color-primario; flex: 0 0 auto; }
.paso-title { font-size: 1.2rem; font-weight: 700; color: $color-primario; margin: 0 0 6px; }
.paso-desc { margin: 0; color: $color-texto-secundario; line-height: 1.6; }
.paso-body { display: flex; flex-direction: column; gap: 16px; }
.paso-tips { display: flex; flex-direction: column; gap: 8px; }
.tip { display: flex; align-items: flex-start; gap: 8px; font-size: 0.9rem; color: $color-texto-secundario; line-height: 1.5; }
.tip-icon { color: $color-primario; flex: 0 0 auto; margin-top: 2px; }
.tip-warn { color: #d97706; }

// Mockups
.mockup-nuevo-row { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
.mockup-btn-nuevo { background: $color-primario; color: #fff; border-radius: 10px; padding: 10px 18px; font-weight: 700; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 8px; }
.mockup-submenu { display: flex; flex-direction: column; gap: 8px; background: #fff; border: 1px solid $color-borde; border-radius: 12px; padding: 10px; box-shadow: 0 8px 20px rgba(0,30,45,0.12); }
.mockup-submenu-item { padding: 8px 12px; border-radius: 8px; font-size: 0.9rem; font-weight: 600; color: $color-texto-secundario; cursor: pointer; }
.mockup-submenu-active { background: rgba(0,60,88,0.08); color: $color-primario; }
.mockup-form-proyecto { display: flex; flex-direction: column; gap: 10px; background: $color-claro; border-radius: 12px; padding: 16px; border: 1px solid $color-borde; max-width: 400px; }
.mockup-label { font-size: 0.85rem; font-weight: 600; color: $color-primario; }
.mockup-input { background: #fff; border: 1.5px solid $color-borde; border-radius: 8px; padding: 10px 14px; font-size: 0.95rem; color: $color-texto-principal; }
.mockup-placeholder { color: $color-texto-secundario; }
.mockup-btn-row { display: flex; gap: 10px; }
.mockup-btn { padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 0.9rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
.mockup-btn-primary { background: $color-primario; color: #fff; }
.mockup-btn-out { background: rgba(0,60,88,0.08); color: $color-primario; border: 1px solid rgba(0,60,88,0.2); }
.mockup-btn-sm { padding: 6px 12px; font-size: 0.82rem; }
.mockup-btn-excel { background: #e8f5e9; color: #1b5e20; border: 1px solid #a5d6a7; }
.mockup-btn-pdf { background: #ffebee; color: #b71c1c; border: 1px solid #ef9a9a; }
.mockup-input-sm { background: #fff; border: 1px solid $color-borde; border-radius: 6px; padding: 6px 10px; font-size: 0.85rem; text-align: center; }

.instruccion-indicator { display: flex; align-items: center; gap: 10px; background: rgba(0,60,88,0.06); border: 1px solid rgba(0,60,88,0.14); border-radius: 10px; padding: 10px 16px; }
.instruccion-icon-circle { display: flex; align-items: center; justify-content: center; color: $color-primario; font-size: 1.4rem; flex: 0 0 auto; }
.instruccion-texto { font-size: 1rem; color: $color-primario; line-height: 1.8; strong { font-weight: 700; } }
.instruccion-badge { display: inline-flex; align-items: center; gap: 5px; padding: 2px 9px; border-radius: 6px; font-size: 0.88rem; font-weight: 700; vertical-align: middle; }
.instruccion-badge--agregar { background: $color-exito; color: #fff; }
.instruccion-badge--quitar { background: $color-peligro; color: #fff; }

.filtros-panel-mockup { display: flex; gap: 12px; flex-wrap: wrap; background: $color-claro; border-radius: 10px; padding: 12px; border: 1px solid $color-borde; }
.filtro-mockup { display: flex; flex-direction: column; gap: 4px; }
.filtro-buscar-mockup { flex: 2; min-width: 160px; }
.filtro-label-mockup { font-size: 0.75rem; font-weight: 600; color: $color-primario; text-transform: uppercase; }
.mockup-select { background: #fff; border: 1px solid $color-borde; border-radius: 8px; padding: 8px 12px; font-size: 0.85rem; display: flex; align-items: center; justify-content: space-between; gap: 8px; color: $color-texto-principal; min-width: 120px; }

.prestaciones-grid-mockup { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.prestaciones-panel-mockup { background: $color-claro; border-radius: 12px; padding: 14px; border: 1px solid $color-borde; }
.panel-title-mockup { font-weight: 700; color: $color-primario; margin-bottom: 10px; font-size: 0.95rem; }
.prestaciones-lista-mockup { display: flex; flex-direction: column; gap: 8px; }
.prestacion-item-mockup { display: flex; align-items: center; justify-content: space-between; gap: 10px; background: #fff; border: 1px solid $color-borde; border-radius: 8px; padding: 8px 10px; }
.prestacion-seleccionada-mockup { background: rgba(0,60,88,0.04); border-color: rgba(0,60,88,0.2); }
.prestacion-info-mockup { flex: 1; display: flex; flex-direction: column; gap: 2px; }
.prestacion-codigo-mockup { font-size: 0.75rem; font-weight: 700; color: $color-primario; }
.prestacion-nombre-mockup { font-size: 0.85rem; color: $color-texto-principal; }
.accion-mockup { width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; color: #fff; flex: 0 0 auto; }
.accion-agregar-mockup { background: $color-exito; }
.accion-quitar-mockup { background: $color-peligro; }
.lista-vacia-mockup { font-size: 0.82rem; color: $color-texto-secundario; padding: 8px 0; }
.acciones-finales-mockup { display: flex; align-items: center; justify-content: space-between; gap: 12px; background: $color-claro; border-radius: 10px; padding: 12px 16px; border: 1px solid $color-borde; }
.acciones-resumen-mockup { font-size: 0.9rem; color: $color-texto-secundario; }

.mockup-params-table { overflow-x: auto; }
.mockup-params-head { display: grid; background: #e9f1f6; padding: 8px 12px; border-radius: 8px 8px 0 0; font-size: 0.78rem; font-weight: 700; color: $color-primario; gap: 8px; }
.mockup-params-head-real { grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr; }
.mockup-params-row { display: grid; background: $color-claro; padding: 8px 12px; border: 1px solid $color-borde; border-top: none; border-radius: 0 0 8px 8px; gap: 8px; align-items: center; font-size: 0.85rem; }
.mockup-params-row-real { grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr; }
.mockup-row-name { font-weight: 600; color: $color-texto-principal; }

.mockup-calc-toggle-row { display: flex; flex-direction: column; gap: 6px; }
.mockup-calc-toggle { display: flex; align-items: center; gap: 10px; padding: 10px 16px; border: 1.5px solid $color-primario; border-radius: 999px; background: rgba(0,60,88,0.06); color: $color-primario; font-weight: 700; font-size: 0.93rem; max-width: 400px; cursor: pointer; }
.mockup-calc-icono { font-size: 1rem; }
.mockup-calc-texto { flex: 1; }
.mockup-calc-chevron { font-size: 0.8rem; opacity: 0.6; }
.mockup-calc-desc { font-size: 0.85rem; color: $color-texto-secundario; margin: 0; padding-left: 4px; }

.mockup-resultados { display: flex; flex-direction: column; gap: 14px; }
.mockup-banner-res { background: $color-primario; border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; }
.mockup-metric-val { font-size: 1.6rem; font-weight: 700; color: #fff; margin-right: 8px; }
.mockup-metric-lab { font-size: 0.85rem; color: rgba(255,255,255,0.7); }
.mockup-res-block { background: $color-claro; border-radius: 10px; padding: 12px 16px; border: 1px solid $color-borde; }
.mockup-section-title { font-weight: 700; color: $color-primario; font-size: 0.9rem; margin-bottom: 10px; }
.mockup-pill-container { display: flex; flex-wrap: wrap; gap: 8px; }
.mockup-pill { display: flex; align-items: center; gap: 6px; background: #eef5f9; border: 1px solid $color-borde; border-radius: 999px; padding: 6px 14px; font-size: 0.85rem; color: $color-primario; }
.mockup-acc-grid { display: flex; flex-direction: column; gap: 8px; }
.mockup-accordion { display: flex; align-items: center; justify-content: space-between; background: #fff; border: 1px solid $color-borde; border-radius: 8px; padding: 10px 14px; font-size: 0.9rem; font-weight: 600; color: $color-primario; cursor: pointer; }
.mockup-accordion-small { font-size: 0.85rem; }
.acc-num { font-weight: 400; color: $color-texto-secundario; }
.dropdown-chev { font-size: 0.8rem; opacity: 0.6; }
.mockup-export-row { display: flex; gap: 10px; }

.mockup-table { display: flex; flex-direction: column; border: 1px solid $color-borde; border-radius: 10px; overflow: hidden; }
.mockup-table-head { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; background: #e9f1f6; padding: 8px 12px; font-size: 0.78rem; font-weight: 700; color: $color-primario; gap: 8px; }
.mockup-table-row { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; padding: 10px 12px; gap: 8px; align-items: center; border-top: 1px solid $color-borde; font-size: 0.85rem; }
.mockup-actions { display: flex; gap: 6px; }
.mockup-chip { background: rgba(0,60,88,0.08); color: $color-primario; border-radius: 999px; padding: 4px 10px; font-size: 0.78rem; font-weight: 600; }

.mockup-action-cards { display: flex; gap: 16px; flex-wrap: wrap; }
.mockup-action-card { display: flex; flex-direction: column; gap: 8px; background: $color-claro; border-radius: 12px; padding: 16px; border: 1px solid $color-borde; max-width: 260px; p { margin: 0; font-size: 0.85rem; color: $color-texto-secundario; } }

.sub-title-mockup { font-size: 1rem; font-weight: 700; color: $color-primario; margin: 8px 0 4px; display: flex; align-items: center; gap: 8px; }

.manual-navbar-guide { background: #fff; border-radius: 16px; padding: 28px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; }
.guide-title { font-size: 1.2rem; font-weight: 700; color: $color-primario; margin: 0 0 8px; display: flex; align-items: center; gap: 8px; }
.guide-desc { margin: 0 0 20px; color: $color-texto-secundario; }
.navbar-guide-row { display: flex; align-items: flex-start; gap: 32px; flex-wrap: wrap; }
.navbar-guide-item { display: flex; flex-direction: column; gap: 8px; p { margin: 0; font-size: 0.85rem; color: $color-texto-secundario; } }
.mockup-session { display: flex; align-items: center; gap: 8px; background: rgba(0,60,88,0.06); border: 1.5px solid rgba(0,60,88,0.18); border-radius: 999px; padding: 6px 14px 6px 10px; color: $color-primario; font-size: 0.88rem; font-weight: 600; }
.mockup-btn-logout { background: none; border: none; color: $color-primario; cursor: pointer; font-size: 0.95rem; opacity: 0.7; }
/* Pestañas */
.tabs-manual { display: flex; justify-content: center; gap: 40px; border-bottom: 1px solid $color-borde; }
.tab-manual {
  background: none; border: none; cursor: pointer;
  display: flex; flex-direction: column; align-items: center; gap: 6px;
  padding: 8px 4px 12px; margin-bottom: -1px;
  color: $color-texto-secundario; font-size: 1rem; font-weight: 500;
  border-bottom: 3px solid transparent;
  i { font-size: 1.25rem; }
  &:hover { color: $color-primario; }
  &.activa { color: $color-primario; border-bottom-color: $color-primario; font-weight: 600; }
  &:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(42,159,214,0.3); border-radius: 6px; }
}

/* Video */
.video-card { background: #fff; border-radius: 16px; padding: 16px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; display: flex; flex-direction: column; gap: 14px; }
.video-wrap { position: relative; width: 100%; aspect-ratio: 16 / 9; border-radius: 10px; overflow: hidden; background: #0b1f2a; }
.video-wrap iframe, .video-wrap video { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
.video-placeholder { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; color: rgba(255,255,255,0.7); font-weight: 600; i { font-size: 3.5rem; opacity: 0.6; } }
.video-desc { margin: 0; text-align: center; color: $color-texto-secundario; }
</style> 