<template>
  <AppLayout>
    <div class="parametros-page">
      <section class="hero hero-compact">
        <div class="hero-bg"></div>
        <div class="hero-content">
          <div class="hero-tag">MÓDULO EPHDEM</div>
          <h1 class="hero-title">Estudio de Preinversión Hospitalaria</h1>
          <p class="hero-sub">Configura los parámetros por prestación para calcular equipamiento médico.</p>
        </div>
      </section>

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
              <button type="button" class="calculadora-toggle" :aria-expanded="mostrarCalculadora" @click="mostrarCalculadora = !mostrarCalculadora">
                <span class="calculadora-icono"><i class="fa-solid fa-calculator"></i></span>
                <span class="calculadora-texto">Calculadora de días cama para UPC</span>
                <i class="fa-solid fa-chevron-down calculadora-chevron" :class="{ 'is-open': mostrarCalculadora }"></i>
              </button>
              <section class="calculadora-panel" :class="{ 'is-open': mostrarCalculadora }">
                <div v-show="mostrarCalculadora" class="calculadora-contenido">
                  <p class="calc-formula-hint"><i class="fa-solid fa-circle-info"></i> Días cama = (Coef. Técnico × PUAC ÷ 1000) × Promedio Estancia</p>
                  <div class="calc-form">
                    <div class="calc-fields">
                      <div class="calc-field">
                        <label>Coeficiente Técnico <span class="info-icon" :data-tooltip="infoTexts.calcCoeficienteTecnico" @mouseenter="mostrarTooltip" @mouseleave="ocultarTooltip">i</span></label>
                        <input v-model.number="calc.coeficienteTecnico" type="number" min="0" step="0.001" placeholder="0.000" />
                      </div>
                      <div class="calc-field">
                        <label>PUAC <span class="calc-field-hint">(÷ 1000)</span> <span class="info-icon" :data-tooltip="infoTexts.calcPuac" @mouseenter="mostrarTooltip" @mouseleave="ocultarTooltip">i</span></label>
                        <input v-model.number="calc.puac" type="number" min="0" step="1" placeholder="0" />
                      </div>
                      <div class="calc-field">
                        <label>Promedio Estancia (días) <span class="info-icon" :data-tooltip="infoTexts.calcPromedioEstancia" @mouseenter="mostrarTooltip" @mouseleave="ocultarTooltip">i</span></label>
                        <input v-model.number="calc.promedioEstancia" type="number" min="0" step="0.1" placeholder="0" />
                      </div>
                    </div>
                    <div class="calc-resultado">
                      <div class="calc-resultado-label">Días cama</div>
                      <div class="calc-resultado-valor">{{ diasCamaCalculados }}</div>
                    </div>
                  </div>
                  <div class="calc-footer">
                    <button type="button" class="calc-limpiar" @click="limpiarCalculadora"><i class="fa-solid fa-rotate-left"></i> Limpiar</button>
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
            <span class="instruccion-icon-circle"><i class="fa-solid fa-circle-info"></i></span>
            <span class="instruccion-texto">
              Completa las variables para cada prestación seleccionada. En el símbolo <span class="info-icon info-icon--demo" aria-hidden="true">i</span>
              podrás ver las características de cada parámetro. Para UPC, usa la calculadora para obtener los días cama.
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
                  <th>Demanda <span class="info-icon" :data-tooltip="infoTexts.demanda" @mouseenter="mostrarTooltip" @mouseleave="ocultarTooltip">i</span></th>
                  <th>Días al año disponibles <span class="info-icon" :data-tooltip="infoTexts.diasAnuales" @mouseenter="mostrarTooltip" @mouseleave="ocultarTooltip">i</span></th>
                  <th>Tiempo de procedimiento (min) <span class="info-icon" :data-tooltip="infoTexts.tiempoProcedimiento" @mouseenter="mostrarTooltip" @mouseleave="ocultarTooltip">i</span></th>
                  <th>Disponibilidad (%) <span class="info-icon" :data-tooltip="infoTexts.disponibilidad" @mouseenter="mostrarTooltip" @mouseleave="ocultarTooltip">i</span></th>
                  <th>Jornada laboral (hrs) <span class="info-icon" :data-tooltip="infoTexts.jornadaLaboral" @mouseenter="mostrarTooltip" @mouseleave="ocultarTooltip">i</span></th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="fila in filas" :key="fila.id">
                  <td>
                    <div class="prestacion-codigo">{{ fila.codigo_fonasa }}</div>
                    <div class="prestacion-nombre">{{ fila.nombre_prestacion }}</div>
                  </td>
                  <td><input v-model.number="fila.demanda" type="number" min="0" step="1" :class="{ 'input-error': erroresCeldas.has(`${fila.id}-demanda`) }" @input="limpiarError(fila.id, 'demanda')" /></td>
                  <td><input v-model.number="fila.diasAnuales" type="number" min="1" max="366" step="1" :class="{ 'input-error': erroresCeldas.has(`${fila.id}-diasAnuales`) }" @input="limpiarError(fila.id, 'diasAnuales')" /></td>
                  <td><input v-model.number="fila.tiempoProcedimiento" type="number" min="0" step="0.1" :class="{ 'input-error': erroresCeldas.has(`${fila.id}-tiempoProcedimiento`) }" @input="limpiarError(fila.id, 'tiempoProcedimiento')" /></td>
                  <td><input v-model.number="fila.disponibilidad" type="number" min="0" max="100" step="0.1" :class="{ 'input-error': erroresCeldas.has(`${fila.id}-disponibilidad`) }" @input="limpiarError(fila.id, 'disponibilidad')" /></td>
                  <td><input v-model.number="fila.jornadaLaboral" type="number" min="0" max="24" step="0.1" :class="{ 'input-error': erroresCeldas.has(`${fila.id}-jornadaLaboral`) }" @input="limpiarError(fila.id, 'jornadaLaboral')" /></td>
                </tr>
              </tbody>
            </table>
          </div>
          <div class="acciones-finales">
            <transition name="fade-error">
              <div v-if="errorValidacion" class="banner-error" role="alert">
                <i class="fa-solid fa-triangle-exclamation"></i> {{ errorValidacion }}
              </div>
            </transition>
            <button class="btn-principal" @click="guardarYCalcular">Guardar y calcular</button>
          </div>
        </section>
      </main>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppLayout from '@/layouts/AppLayout.vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()
const PRESTACIONES_STORAGE_KEY = 'ephdem_prestaciones_seleccionadas'
const PARAMETROS_STORAGE_KEY = 'ephdem_parametros_prestaciones'
const MINUTOS_POR_HORA = 60

const infoTexts = {
  demanda: 'Cantidad de atenciones proyectadas para esta prestación en el período.',
  diasAnuales: 'Número de días disponibles al año para operar. Máximo 366 días.',
  tiempoProcedimiento: 'Minutos que dura el procedimiento.',
  disponibilidad: 'Porcentaje de disponibilidad real del equipo para esta prestación.',
  jornadaLaboral: 'Horas efectivas de operación al día. En atención cerrada normalmente 24.',
  calcCoeficienteTecnico: 'Número de camas UCI o UTI por cada 1.000 habitantes.',
  calcPuac: 'Población Usuaria Asignada al Centro (PUAC).',
  calcPromedioEstancia: 'Promedio de días que un paciente ocupa una cama.',
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
  const abrirIzquierda = (rect.left + TOOLTIP_WIDTH + 16) > window.innerWidth
  tooltipPosicion.value = { top: `${rect.bottom + 8}px`, left: abrirIzquierda ? `${rect.right - TOOLTIP_WIDTH}px` : `${rect.left}px`, visible: true, texto: span.getAttribute('data-tooltip'), abrirIzquierda }
}
function ocultarTooltip() { tooltipPosicion.value.visible = false }

function crearFila(prestacion, parametrosGuardados) {
  return {
    id: prestacion.id,
    codigo_fonasa: prestacion.codigo_fonasa,
    nombre_prestacion: prestacion.nombre_prestacion,
    demanda: parametrosGuardados?.demanda ?? 0,
    diasAnuales: parametrosGuardados?.diasAnuales ?? 365,
    tiempoProcedimiento: parametrosGuardados?.tiempoProcedimiento ?? prestacion.tiempo_procedimiento ?? MINUTOS_POR_HORA,
    disponibilidad: parametrosGuardados?.disponibilidad ?? 100,
    jornadaLaboral: parametrosGuardados?.jornadaLaboral ?? 24,
  }
}

function cargarDatos() {
  const rawPrestaciones = localStorage.getItem(PRESTACIONES_STORAGE_KEY)
  if (!rawPrestaciones) return
  const rawParametros = localStorage.getItem(PARAMETROS_STORAGE_KEY)
  try {
    const prestaciones = JSON.parse(rawPrestaciones)
    if (!Array.isArray(prestaciones)) return
    const parametrosGuardados = rawParametros ? JSON.parse(rawParametros) : []
    const parametrosMap = new Map(Array.isArray(parametrosGuardados) ? parametrosGuardados.map(item => [item.id, item]) : [])
    filas.value = prestaciones.map(prestacion => crearFila(prestacion, parametrosMap.get(prestacion.id)))
  } catch (error) {
    localStorage.removeItem(PRESTACIONES_STORAGE_KEY)
    localStorage.removeItem(PARAMETROS_STORAGE_KEY)
    alert('No se pudieron cargar las prestaciones/parametros guardados.')
  }
}

const CAMPOS_REQUERIDOS = ['demanda', 'diasAnuales', 'tiempoProcedimiento', 'disponibilidad', 'jornadaLaboral']

function validarFilas() {
  const errores = new Set()
  for (const fila of filas.value) {
    for (const campo of CAMPOS_REQUERIDOS) {
      const val = fila[campo]
      if (val === null || val === undefined || val === '' || Number(val) <= 0) errores.add(`${fila.id}-${campo}`)
    }
    if (Number(fila.diasAnuales) > 366) errores.add(`${fila.id}-diasAnuales`)
    if (Number(fila.disponibilidad) > 100) errores.add(`${fila.id}-disponibilidad`)
    if (Number(fila.jornadaLaboral) > 24) errores.add(`${fila.id}-jornadaLaboral`)
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
  if (!validarFilas()) { errorValidacion.value = 'Hay celdas vacías, con valor 0, o con valores fuera de rango. Revisa los campos marcados en rojo.'; return }
  errorValidacion.value = ''
  const idProyectoActual = route.params.proyectoId || localStorage.getItem('ephdem_proyecto_activo')
  if (!idProyectoActual) { alert('No hay un proyecto activo.'); router.push('/crear-proyecto'); return }
  localStorage.setItem(PARAMETROS_STORAGE_KEY, JSON.stringify(filas.value))
  const tpOverrides = {}
  for (const fila of filas.value) tpOverrides[fila.id] = fila.tiempoProcedimiento
  localStorage.setItem(`ephdem_tp_overrides_${idProyectoActual}`, JSON.stringify(tpOverrides))
  const payload = {
    proyecto_id: Number(idProyectoActual),
    filas: filas.value.map(fila => ({
      prestacion_id: fila.id,
      demanda_anual: Number(fila.demanda),
      dias_laborales: Number(fila.diasAnuales),
      disponibilidad: Number(fila.disponibilidad) / 100,
      jornada_efectiva: Number(fila.jornadaLaboral),
    })),
  }
  try {
    const resp = await fetch(`${import.meta.env.VITE_API_BASE}/calcular_demanda.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) })
    const data = await resp.json()
    if (!data.ok) { alert('Error al calcular: ' + (data.error || '')); return }
    localStorage.setItem('ephdem_resultado_calculo', JSON.stringify(data.datos))
    router.push('/resultados')
  } catch (error) {
    alert('No se pudo conectar con el servidor de cálculo.')
  }
}

function volverAtras() { router.back(); setTimeout(() => window.scrollTo({ top: 0, left: 0, behavior: 'auto' }), 0) }
function irAPrestaciones() {
  localStorage.setItem(PARAMETROS_STORAGE_KEY, JSON.stringify(filas.value))
  if (route.params.proyectoId) { router.push(`/prestaciones/${route.params.proyectoId}`) } else { router.push('/prestaciones') }
}
function cerrarSesion() { authStore.logout(); router.push('/login') }
function limpiarCalculadora() { calc.value = { coeficienteTecnico: null, puac: null, promedioEstancia: null } }
function cerrarCalculadoraSiCorresponde(event) {
  const contenedor = calculadoraRef.value
  if (!contenedor || !mostrarCalculadora.value) return
  if (!contenedor.contains(event.target)) mostrarCalculadora.value = false
}

async function cargarDesdeServidor(proyectoId) {
  try {
    const [respDemanda, respPrestaciones] = await Promise.all([
      fetch(`${import.meta.env.VITE_API_BASE}/get_prestaciones_demanda.php?proyecto_id=${proyectoId}`),
      fetch(`${import.meta.env.VITE_API_BASE}/get_prestaciones.php`),
    ])
    const json = await respDemanda.json()
    const jsonPrestaciones = await respPrestaciones.json()
    if (!respDemanda.ok || !json.ok) { alert(json.error || 'Error al cargar datos del proyecto.'); router.push('/proyectos'); return }
    const tiempoMap = new Map()
    if (jsonPrestaciones?.ok && Array.isArray(jsonPrestaciones?.datos)) {
      for (const p of jsonPrestaciones.datos) {
        if (p.id_prestacion != null && p.tiempo_procedimiento != null) tiempoMap.set(p.id_prestacion, p.tiempo_procedimiento)
      }
    }
    let tpOverrides = {}
    try { const raw = localStorage.getItem(`ephdem_tp_overrides_${proyectoId}`); if (raw) tpOverrides = JSON.parse(raw) } catch (_) {}
    filas.value = json.datos.map(item => {
      const vals = item.valores
      const defs = item.defaults || {}
      const tiempoProcedimiento = tpOverrides[item.id_prestacion] ?? tiempoMap.get(item.id_prestacion) ?? MINUTOS_POR_HORA
      return {
        id: item.id_prestacion, codigo_fonasa: item.codigo_fonasa || '', nombre_prestacion: item.nombre_prestacion,
        demanda: vals?.demanda_anual ?? 0,
        diasAnuales: vals ? vals.dias_laborales : (defs.dias_laborales ?? 365),
        tiempoProcedimiento,
        disponibilidad: vals ? (vals.disponibilidad * 100) : (defs.disponibilidad ? defs.disponibilidad * 100 : 100),
        jornadaLaboral: vals ? vals.jornada_efectiva : (defs.jornada_efectiva ?? 24)
      }
    })
  } catch (e) { alert('Error de red al intentar cargar datos del proyecto.') }
}

onMounted(() => {
  nombreProyectoActivo.value = localStorage.getItem('ephdem_nombre_proyecto_activo') || 'Desconocido'
  if (route.params.proyectoId) {
    if (localStorage.getItem('ephdem_origen_edicion') === 'prestaciones') { localStorage.removeItem('ephdem_origen_edicion'); cargarDatos() }
    else { cargarDesdeServidor(route.params.proyectoId) }
  } else { cargarDatos() }
  document.addEventListener('pointerdown', cerrarCalculadoraSiCorresponde)
})
onBeforeUnmount(() => { document.removeEventListener('pointerdown', cerrarCalculadoraSiCorresponde) })
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
.nav-divider { width: 1px; height: 24px; background-color: #cbd5e1; margin: 0 4px; }
.session-badge { display: flex; align-items: center; gap: 8px; padding: 6px 14px 6px 10px; background: rgba(0,60,88,0.06); border: 1.5px solid rgba(0,60,88,0.18); border-radius: 999px; color: $color-primario; font-size: 0.88rem; font-weight: 600; }
.session-nombre { white-space: nowrap; }
.btn-logout { background: none; border: none; color: $color-primario; cursor: pointer; padding: 2px 4px; opacity: 0.7; &:hover { opacity: 1; color: #c62828; } }
.btn-back { background: $color-primario; color: #fff; border: 1px solid $color-primario; border-radius: 999px; padding: 6px 12px; font-weight: 600; cursor: pointer; margin-bottom: 10px; &:hover { background: mix(#fff, $color-primario, 6%); } }

.parametros-header-top { display: flex; align-items: center; justify-content: space-between; gap: 16px; }
.section-title { font-size: 1.6rem; font-weight: 700; color: $color-primario; margin: 0; }
.proyecto-activo-badge { display: inline-flex; align-items: center; align-self: flex-start; background: rgba(0,60,88,0.05); border-radius: 6px; padding: 6px 12px; margin-top: 6px; border: 1px solid rgba(0,60,88,0.1); }
.badge-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: rgba(0,60,88,0.6); margin-right: 8px; }
.badge-name { font-size: 0.95rem; font-weight: 700; color: $color-primario; }
.instruccion-indicator { display: flex; align-items: center; gap: 10px; margin-top: 6px; background: rgba(0,60,88,0.06); border: 1px solid rgba(0,60,88,0.14); border-radius: 10px; padding: 10px 16px; }
.instruccion-icon-circle { display: flex; align-items: center; justify-content: center; color: $color-primario; font-size: 1.4rem; flex: 0 0 auto; }
.instruccion-texto { font-size: 1.05rem; color: $color-primario; line-height: 1.8; strong { font-weight: 700; } }
.info-icon { display: inline-flex; align-items: center; justify-content: center; width: 16px; height: 16px; margin-left: 6px; border-radius: 50%; background: rgba(0,60,88,0.14); color: $color-primario; font-size: 0.72rem; font-weight: 700; cursor: help; }
.info-icon--demo { pointer-events: none; cursor: default; vertical-align: middle; }

.calculadora-wrapper { position: relative; display: flex; flex-direction: column; align-items: flex-end; flex: 0 0 auto; }
.calculadora-toggle { display: flex; align-items: center; gap: 10px; padding: 10px 16px; border: 1.5px solid $color-primario; border-radius: 999px; background: rgba(0,60,88,0.06); color: $color-primario; font-weight: 700; font-size: 0.93rem; cursor: pointer; white-space: nowrap; &:hover { background: rgba(0,60,88,0.12); } }
.calculadora-chevron { font-size: 0.8rem; transition: transform 0.25s ease; &.is-open { transform: rotate(180deg); } }
.calculadora-panel { position: absolute; top: calc(100% + 10px); right: 0; width: min(620px, 100vw - 32px); z-index: 100; pointer-events: none; &.is-open { pointer-events: auto; } }
.calculadora-contenido { background: #fff; border: 1.5px solid $color-primario; border-radius: 16px; padding: 20px; box-shadow: 0 8px 28px rgba(0,60,88,0.14); display: flex; flex-direction: column; gap: 16px; }
.calc-formula-hint { margin: 0; font-size: 0.82rem; color: $color-primario; background: rgba(0,60,88,0.07); padding: 8px 12px; border-radius: 8px; display: flex; align-items: center; gap: 8px; font-weight: 600; }
.calc-form { display: flex; align-items: flex-end; gap: 16px; }
.calc-fields { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; flex: 1 1 auto; }
.calc-field { display: flex; flex-direction: column; gap: 4px; label { font-size: 0.75rem; font-weight: 700; color: $color-primario; display: flex; align-items: center; gap: 4px; } input { padding: 8px 10px; border: 1.5px solid $color-borde; border-radius: 8px; font-size: 0.95rem; font-weight: 600; width: 100%; &:focus { outline: none; border-color: $color-primario; } } }
.calc-field-hint { font-size: 0.72rem; color: $color-texto-secundario; font-weight: 600; }
.calc-resultado { display: flex; flex-direction: column; align-items: center; gap: 4px; background: $color-primario; border-radius: 12px; padding: 10px 20px; min-width: 110px; }
.calc-resultado-label { font-size: 0.72rem; font-weight: 700; color: rgba(255,255,255,0.7); white-space: nowrap; }
.calc-resultado-valor { font-size: 1.6rem; font-weight: 800; color: #fff; }
.calc-footer { display: flex; justify-content: flex-end; }
.calc-limpiar { background: none; border: 1px solid $color-borde; color: $color-texto-secundario; border-radius: 8px; padding: 6px 12px; font-size: 0.82rem; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 6px; &:hover { background: rgba(0,60,88,0.06); color: $color-primario; border-color: $color-primario; } }

.panel-vacio, .tabla-panel { background: #fff; border-radius: 16px; padding: 20px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; }
.panel-vacio { display: flex; align-items: center; justify-content: space-between; gap: 14px; }
.tabla-scroll { overflow-x: auto; }
.tabla-parametros { width: 100%; min-width: 1200px; border-collapse: separate; border-spacing: 0; }
.tabla-parametros th, .tabla-parametros td { padding: 12px 10px; border-bottom: 1px solid $color-borde; text-align: left; vertical-align: middle; }
.tabla-parametros th { background: #e9f1f6; color: $color-primario; font-size: 0.9rem; font-weight: 700; position: sticky; top: 0; }
.tabla-parametros td input { width: 100%; padding: 8px 10px; border: 1px solid $color-borde; border-radius: 8px; background: #fff; font-weight: 500; color: $color-texto-principal; transition: border-color 0.2s ease; }
.tabla-parametros td input.input-error { border-color: #e53935; background: #fff5f5; box-shadow: 0 0 0 2px rgba(229,57,53,0.18); animation: shake 0.35s ease; }

@keyframes shake {
  0% { transform: translateX(0); } 20% { transform: translateX(-5px); } 40% { transform: translateX(5px); }
  60% { transform: translateX(-4px); } 80% { transform: translateX(4px); } 100% { transform: translateX(0); }
}

.prestacion-codigo { font-size: 0.85rem; font-weight: 700; color: $color-primario; }
.prestacion-nombre { font-size: 0.95rem; font-weight: 500; color: $color-texto-principal; }

.tooltip-flotante { position: fixed; z-index: 10000; pointer-events: none; }
.tooltip-contenido { width: 240px; padding: 10px 14px; background: $color-primario; color: #fff; font-size: 0.85rem; font-weight: 500; border-radius: 8px; text-align: center; line-height: 1.5; box-shadow: 0 6px 16px rgba(0,0,0,0.2); }
.tooltip-flecha { position: absolute; top: -7px; left: 7px; width: 0; height: 0; border-left: 7px solid transparent; border-right: 7px solid transparent; border-bottom: 7px solid $color-primario; }
.tooltip-flotante--left .tooltip-flecha { left: auto; right: 7px; }

.acciones-finales { margin-top: 16px; display: flex; flex-direction: column; align-items: flex-end; gap: 10px; }
.banner-error { display: flex; align-items: center; gap: 10px; background: #fff5f5; border: 1.5px solid #e53935; color: #c62828; border-radius: 10px; padding: 10px 16px; font-size: 0.88rem; font-weight: 600; width: 100%; max-width: 600px; }
.fade-error-enter-active, .fade-error-leave-active { transition: opacity 0.25s ease, transform 0.25s ease; }
.fade-error-enter-from, .fade-error-leave-to { opacity: 0; transform: translateY(-6px); }
.btn-principal { background: $color-primario; color: #fff; border: none; border-radius: 10px; padding: 10px 18px; font-weight: 700; cursor: pointer; }
.btn-secundario { background: rgba(0,60,88,0.12); color: $color-primario; border: none; border-radius: 10px; padding: 10px 18px; font-weight: 700; cursor: pointer; }
</style>