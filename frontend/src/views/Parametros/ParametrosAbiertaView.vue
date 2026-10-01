<template>
  <AppLayout>
    <div class="parametros-page">
      <section class="hero hero-compact">
        <div class="hero-bg"></div>
        <div class="hero-content">
          <div class="hero-tag">MÓDULO EPHDEM</div>
          <h1 class="hero-title">Estudio de Preinversión Hospitalaria</h1>
          <p class="hero-sub">Ingresa las variables de demanda para cada prestación seleccionada.</p>
        </div>
      </section>

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
              <button class="btn-logout" type="button" @click="cerrarSesion">
                <i class="fa-solid fa-right-from-bracket"></i>
              </button>
            </div>
          </div>
          <h2 class="section-title">Parámetros</h2>
          <div class="proyecto-activo-badge">
            <span class="badge-label">Proyecto en edición</span>
            <span class="badge-name">{{ nombreProyectoActivo }}</span>
          </div>
          <div class="instruccion-indicator">
            <span class="instruccion-icon-circle"><i class="fa-solid fa-circle-info"></i></span>
            <span class="instruccion-texto">
              Completa las variables para cada prestación. Ningún campo puede quedar en 0 o vacío.
            </span>
          </div>

        </header>

        <section v-if="filas.length === 0" class="panel-vacio">
          <p>No hay prestaciones seleccionadas.</p>
          <button class="btn-secundario" @click="irAPrestaciones">Volver a prestaciones</button>
        </section>

        <section v-else class="tabla-panel">
          <div class="tabla-scroll">
            <table class="tabla-parametros">
              <thead>
                <tr>
                  <th>Prestación</th>
                  <th>Demanda <span class="info-icon" :data-tooltip="infoTexts.demanda" @mouseenter="mostrarTooltip" @mouseleave="ocultarTooltip">i</span></th>
                  <th>Días al año disponibles <span class="info-icon" :data-tooltip="infoTexts.diasLaborales" @mouseenter="mostrarTooltip" @mouseleave="ocultarTooltip">i</span></th>
                  <th>Tiempo de procedimiento (min) <span class="info-icon" :data-tooltip="infoTexts.tiempo" @mouseenter="mostrarTooltip" @mouseleave="ocultarTooltip">i</span></th>
                  <th>N° simultáneas <span class="info-icon" :data-tooltip="infoTexts.nSimultaneas" @mouseenter="mostrarTooltip" @mouseleave="ocultarTooltip">i</span></th>
                  <th>Disponibilidad (%) <span class="info-icon" :data-tooltip="infoTexts.disponibilidad" @mouseenter="mostrarTooltip" @mouseleave="ocultarTooltip">i</span></th>
                  <th>Jornada laboral (hrs) <span class="info-icon" :data-tooltip="infoTexts.jornada" @mouseenter="mostrarTooltip" @mouseleave="ocultarTooltip">i</span></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="fila in filas" :key="fila.ID_PRESTACION">
                  <td>
                    <div class="td-codigo">{{ fila.cod_prestacion }}</div>
                    <div class="td-nombre">{{ fila.nombre_prestacion }}</div>
                  </td>
                  <td><input v-model.number="fila.demanda" type="number" min="0" step="1" :class="{ 'input-error': errores.has(`${fila.ID_PRESTACION}-demanda`) }" @input="calcularEEMM(fila)" /></td>
                  <td><input v-model.number="fila.diasLaborales" type="number" min="1" max="366" step="1" :class="{ 'input-error': errores.has(`${fila.ID_PRESTACION}-diasLaborales`) }" @input="calcularEEMM(fila)" /></td>
                  <td><input v-model.number="fila.tiempo" type="number" min="0" step="1" :class="{ 'input-error': errores.has(`${fila.ID_PRESTACION}-tiempo`) }" @input="calcularEEMM(fila)" /></td>
                  <td><input v-model.number="fila.nSimultaneas" type="number" min="1" step="1" :class="{ 'input-error': errores.has(`${fila.ID_PRESTACION}-nSimultaneas`) }" @input="calcularEEMM(fila)" /></td>
                  <td><input v-model.number="fila.disponibilidad" type="number" min="0" max="100" step="1" :class="{ 'input-error': errores.has(`${fila.ID_PRESTACION}-disponibilidad`) }" @input="calcularEEMM(fila)" /></td>
                  <td><input v-model.number="fila.jornada" type="number" min="0" max="24" step="0.5" :class="{ 'input-error': errores.has(`${fila.ID_PRESTACION}-jornada`) }" @input="calcularEEMM(fila)" /></td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="acciones-finales">
            <transition name="fade-error">
              <div v-if="errorValidacion" class="banner-error">
                <i class="fa-solid fa-triangle-exclamation"></i> {{ errorValidacion }}
              </div>
            </transition>
            <button class="btn-principal" @click="guardarYCalcular">
              <i class="fa-solid fa-calculator"></i> Guardar y calcular
            </button>
          </div>
        </section>
      </main>
    </div>

    <div v-if="tooltipPosicion.visible" class="tooltip-flotante" :class="{ 'tooltip-flotante--left': tooltipPosicion.abrirIzquierda }" :style="{ top: tooltipPosicion.top, left: tooltipPosicion.left }">
      <div class="tooltip-flecha"></div>
      <div class="tooltip-contenido">{{ tooltipPosicion.texto }}</div>
    </div>

  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppLayout from '@/layouts/AppLayout.vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const filas = ref([])
const errores = ref(new Set())
const errorValidacion = ref('')
const nombreProyectoActivo = ref('')

function calcularEEMM(fila) {
  const { demanda, tiempo, diasLaborales, nSimultaneas, disponibilidad, jornada } = fila
  if (demanda > 0 && tiempo > 0 && diasLaborales > 0 && nSimultaneas > 0 && disponibilidad > 0 && jornada > 0) {
    const demandaDiaria    = demanda / diasLaborales
    const procHora         = 60 / tiempo
    const capDiaria        = procHora * jornada
    const capConDisp       = capDiaria * (disponibilidad / 100)
    fila.eemm = demandaDiaria / (capConDisp * nSimultaneas)
  } else {
    fila.eemm = null
  }
}

function setDiasLaborales(dias) {
  filas.value.forEach(f => { f.diasLaborales = dias; calcularEEMM(f) })
}

async function cargarDatos() {
  const raw = localStorage.getItem('ephdem_prestaciones_abierta')
  if (!raw) return
  try {
    const prestaciones = JSON.parse(raw)
    const proyectoId = route.params.proyectoId || localStorage.getItem('ephdem_proyecto_activo_abierta')
    const usuarioId = authStore.usuarioId

    // Cargar parámetros guardados del servidor
    let parametrosGuardados = {}
    if (proyectoId) {
      try {
        const url = `${import.meta.env.VITE_API_BASE}/get/get_prestaciones_demanda_abierta.php?proyecto_id=${proyectoId}&usuario_id=${usuarioId}`
        const resp = await fetch(url)
        const json = await resp.json()
        if (json.ok && Array.isArray(json.datos)) {
          json.datos.forEach(p => {
            parametrosGuardados[p.ID_PRESTACION] = p
          })
        }
      } catch (e) {}
    }

    filas.value = prestaciones.map(p => {
      const guardado = parametrosGuardados[p.ID_PRESTACION]
      return {
        ID_PRESTACION:     p.ID_PRESTACION,
        cod_prestacion:    p.cod_prestacion,
        nombre_prestacion: p.nombre_prestacion,
        area:              p.area,
        demanda:           guardado?.demanda       ?? 0,
        tiempo:            guardado?.tiempo        ?? p.tiempo_procedimiento ?? 0,
        diasLaborales:     guardado?.diasLaborales ?? 260,
        nSimultaneas:      guardado?.nSimultaneas  ?? 1,
        disponibilidad:    guardado?.disponibilidad ?? 100,
        jornada:           guardado?.jornada        ?? 7,
        eemm:              null,
      }
    })
    filas.value.forEach(f => calcularEEMM(f))
  } catch (e) {
    console.error('Error al cargar prestaciones:', e)
  }
}

async function cargarDesdeServidor(proyectoId) {
  const usuarioId = authStore.usuarioId
  try {
    const url = `${import.meta.env.VITE_API_BASE}/get/get_prestaciones_demanda_abierta.php?proyecto_id=${proyectoId}&usuario_id=${usuarioId}`
    const resp = await fetch(url)
    const json = await resp.json()
    if (!json.ok || !Array.isArray(json.datos) || json.datos.length === 0) {
      cargarDatos()
      return
    }
    filas.value = json.datos.map(p => ({
      ID_PRESTACION:     p.ID_PRESTACION,
      cod_prestacion:    p.cod_prestacion,
      nombre_prestacion: p.nombre_prestacion,
      area:              p.area,
      demanda:           p.demanda,
      tiempo:            p.tiempo,
      diasLaborales:     p.diasLaborales,
      nSimultaneas:      p.nSimultaneas,
      disponibilidad:    p.disponibilidad,
      jornada:           p.jornada,
      eemm:              null,
    }))
    filas.value.forEach(f => calcularEEMM(f))
  } catch (e) {
    console.error('Error al cargar desde servidor:', e)
    cargarDatos()
  }
}

const infoTexts = {
  demanda: 'Cantidad de atenciones proyectadas para esta prestación en el período.',
  diasLaborales: 'Número de días disponibles al año para operar. Máximo 366 días.',
  tiempo: 'Minutos que dura el procedimiento.',
  disponibilidad: 'Porcentaje de disponibilidad real del equipo para esta prestación.',
  jornada: 'Horas efectivas de operación al día.',
  nSimultaneas: 'Cantidad de prestaciones simultáneas.',
}

function validar() {
  const errs = new Set()
  for (const f of filas.value) {
    if (!f.demanda || f.demanda <= 0) errs.add(`${f.ID_PRESTACION}-demanda`)
    if (!f.tiempo || f.tiempo <= 0) errs.add(`${f.ID_PRESTACION}-tiempo`)
    if (!f.diasLaborales || f.diasLaborales <= 0) errs.add(`${f.ID_PRESTACION}-diasLaborales`)
    if (!f.nSimultaneas || f.nSimultaneas <= 0) errs.add(`${f.ID_PRESTACION}-nSimultaneas`)
    if (!f.disponibilidad || f.disponibilidad <= 0) errs.add(`${f.ID_PRESTACION}-disponibilidad`)
  }
  errores.value = errs
  return errs.size === 0
}

async function guardarYCalcular() {
  if (!validar()) {
    errorValidacion.value = 'Hay campos vacíos o con valor 0. Revisa los campos marcados en rojo.'
    return
  }
  errorValidacion.value = ''

  const proyectoId = route.params.proyectoId || localStorage.getItem('ephdem_proyecto_activo_abierta')
  const userId = authStore.usuarioId

  const payload = {
    proyecto_id: Number(proyectoId),
    usuario_id: userId,
    filas: filas.value.map(f => ({
      prestacion_id:  f.ID_PRESTACION,
      demanda_anual:  f.demanda,
      tiempo_proc:    f.tiempo,
      dias_laborales: f.diasLaborales,
      n_simultaneas:  f.nSimultaneas,
      disponibilidad: f.disponibilidad / 100,
      jornada:        f.jornada,
      requerimiento:  f.eemm ?? 0,
    }))
  }

  try {
    const resp = await fetch(`${import.meta.env.VITE_API_BASE}/calcular_demanda_abierta.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(payload)
    })
    const data = await resp.json()
    if (!data.ok) { alert('Error al calcular: ' + (data.error || '')); return }
    localStorage.setItem('ephdem_resultado_abierta', JSON.stringify(data.datos))
    localStorage.removeItem('ephdem_prestaciones_abierta')
    localStorage.removeItem('ephdem_prestaciones_abierta_pid')
    router.push(`/resultados-abierta/${proyectoId}`)
  } catch (e) {
    alert('No se pudo conectar con el servidor.')
  }
}

function irAPrestaciones() {
  const proyectoId = route.params.proyectoId || localStorage.getItem('ephdem_proyecto_activo_abierta')
  if (proyectoId) router.push(`/prestaciones-abierta/${proyectoId}`)
  else router.push('/prestaciones-abierta')
}

function volverAtras() { router.back() }
function cerrarSesion() { authStore.logout(); router.push('/login') }

onMounted(async () => {
  nombreProyectoActivo.value = localStorage.getItem('ephdem_nombre_proyecto_activo_abierta') || 'Desconocido'
  const proyectoId = route.params.proyectoId || localStorage.getItem('ephdem_proyecto_activo_abierta')
  const rawLocal = localStorage.getItem('ephdem_prestaciones_abierta')
  const pidLocal = localStorage.getItem('ephdem_prestaciones_abierta_pid')
  if (rawLocal && proyectoId && String(pidLocal) === String(proyectoId)) {
    await cargarDatos()
  } else if (proyectoId) {
    await cargarDesdeServidor(proyectoId)
  }
})

const tooltipPosicion = ref({ top: '0px', left: '0px', visible: false, texto: '', abrirIzquierda: false })

function mostrarTooltip(event) {
  const span = event.target
  const rect = span.getBoundingClientRect()
  const TOOLTIP_WIDTH = 240
  const abrirIzquierda = (rect.left + TOOLTIP_WIDTH + 16) > window.innerWidth
  tooltipPosicion.value = { top: `${rect.bottom + 8}px`, left: abrirIzquierda ? `${rect.right - TOOLTIP_WIDTH}px` : `${rect.left}px`, visible: true, texto: span.getAttribute('data-tooltip'), abrirIzquierda }
}
function ocultarTooltip() { tooltipPosicion.value.visible = false }

</script>

<style lang="scss" scoped>
@import '@/assets/styles/variables';

.hero { background: $color-secundario; position: relative; padding: 38px 48px; overflow: hidden; text-align: center; }
.hero-compact { padding: 28px 48px; }
.hero-bg { position: absolute; inset: 0; background: url('@/assets/img/mac.jpg') center/cover no-repeat; }
.hero-content { position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; gap: 10px; }
.hero-tag { font-size: 12px; color: rgba(255,255,255,0.35); letter-spacing: 2.5px; text-transform: uppercase; }
.hero-title { font-size: 26px; font-weight: 500; color: #fff; margin: 0; }
.hero-sub { font-size: 14px; color: rgba(255,255,255,0.6); max-width: 700px; line-height: 1.5; margin: 0; }

.parametros-page { background: $color-fondo; flex: 1; }
.parametros-content { max-width: 1480px; margin: 32px auto 72px auto; padding: 0 20px; display: flex; flex-direction: column; gap: 24px; }

.nav-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
.nav-buttons { display: flex; gap: 12px; align-items: center; }
.nav-divider { width: 1px; height: 24px; background: #cbd5e1; }
.session-badge { display: flex; align-items: center; gap: 8px; padding: 6px 14px 6px 10px; background: rgba(0,60,88,0.06); border: 1.5px solid rgba(0,60,88,0.18); border-radius: 999px; color: $color-primario; font-size: 0.88rem; font-weight: 600; }
.session-nombre { white-space: nowrap; }
.btn-logout { background: none; border: none; color: $color-primario; cursor: pointer; padding: 2px 4px; opacity: 0.7; }
.btn-back { background: $color-primario; color: #fff; border: 1px solid $color-primario; border-radius: 999px; padding: 6px 12px; font-weight: 600; cursor: pointer; margin-bottom: 10px; }

.section-title { font-size: 1.6rem; font-weight: 700; color: $color-primario; margin: 0 0 6px; }
.proyecto-activo-badge { display: inline-flex; align-items: center; align-self: flex-start; background: rgba(0,60,88,0.05); border-radius: 6px; padding: 6px 12px; border: 1px solid rgba(0,60,88,0.1); }
.badge-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: rgba(0,60,88,0.6); margin-right: 8px; }
.badge-name { font-size: 0.95rem; font-weight: 700; color: $color-primario; }
.instruccion-indicator { display: flex; align-items: center; gap: 10px; background: rgba(0,60,88,0.06); border: 1px solid rgba(0,60,88,0.14); border-radius: 10px; padding: 10px 16px; }
.instruccion-icon-circle { color: $color-primario; font-size: 1.4rem; flex: 0 0 auto; }
.instruccion-texto { font-size: 1rem; color: $color-primario; line-height: 1.6; }

.botones-dias { display: flex; gap: 10px; margin-top: 8px; }
.btn-dias { background: rgba(0,60,88,0.08); color: $color-primario; border: 1.5px solid rgba(0,60,88,0.2); border-radius: 999px; padding: 6px 14px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; font-size: 0.88rem; &:hover { background: rgba(0,60,88,0.14); } }

.tabla-panel, .panel-vacio { background: #fff; border-radius: 16px; padding: 20px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; }
.panel-vacio { display: flex; align-items: center; justify-content: space-between; gap: 14px; }
.tabla-scroll { overflow-x: auto; }
.tabla-parametros { width: 100%; min-width: 1100px; border-collapse: separate; border-spacing: 0; }
.tabla-parametros th { background: #e9f1f6; color: $color-primario; font-size: 0.82rem; font-weight: 700; padding: 10px 8px; text-align: left; }
.tabla-parametros td { padding: 10px 8px; border-bottom: 1px solid $color-borde; vertical-align: middle; }
.tabla-parametros td input, .tabla-parametros td select { width: 100%; padding: 7px 8px; border: 1px solid $color-borde; border-radius: 8px; font-size: 0.88rem; }
.tabla-parametros td input.input-error { border-color: #e53935; background: #fff5f5; }
.td-codigo { font-size: 0.85rem; font-weight: 700; color: $color-primario; }
.td-nombre { font-size: 0.95rem; font-weight: 500; color: $color-texto-principal;}
.td-area { font-size: 0.78rem; color: $color-texto-secundario; white-space: nowrap; }
.td-eemm { font-weight: 700; color: $color-primario; text-align: center; }
.info-icon { display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; margin-left: 6px; border-radius: 50%; background: rgba(0,60,88,0.14); color: $color-primario; font-size: 0.72rem; font-weight: 700; cursor: help; }
.tooltip-flotante { position: fixed; z-index: 10000; pointer-events: none; }
.tooltip-contenido { width: 240px; padding: 10px 14px; background: $color-primario; color: #fff; font-size: 0.85rem; font-weight: 500; border-radius: 8px; text-align: center; line-height: 1.5; box-shadow: 0 6px 16px rgba(0,0,0,0.2); }
.tooltip-flecha { position: absolute; top: -7px; left: 7px; width: 0; height: 0; border-left: 7px solid transparent; border-right: 7px solid transparent; border-bottom: 7px solid $color-primario; }
.tooltip-flotante--left .tooltip-flecha { left: auto; right: 7px; }

.acciones-finales { margin-top: 16px; display: flex; flex-direction: column; align-items: flex-end; gap: 10px; }
.banner-error { display: flex; align-items: center; gap: 10px; background: #fff5f5; border: 1.5px solid #e53935; color: #c62828; border-radius: 10px; padding: 10px 16px; font-size: 0.88rem; font-weight: 600; width: 100%; }
.fade-error-enter-active, .fade-error-leave-active { transition: opacity 0.25s; }
.fade-error-enter-from, .fade-error-leave-to { opacity: 0; }
.btn-principal { background: $color-primario; color: #fff; border: none; border-radius: 10px; padding: 12px 20px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; }
.btn-secundario { background: rgba(0,60,88,0.12); color: $color-primario; border: none; border-radius: 10px; padding: 10px 18px; font-weight: 700; cursor: pointer; }
</style>