<template>
  <AppLayout>
    <div class="resultados-page">
      <section class="hero hero-compact">
        <div class="hero-bg"></div>
        <div class="hero-content">
          <div class="hero-tag">MÓDULO EPHDEM</div>
          <h1 class="hero-title">Estudio de Preinversión Hospitalaria</h1>
          <p class="hero-sub">Resumen de equipamiento calculado — Atención Abierta.</p>
        </div>
      </section>

      <main class="resultados-content">
        <header class="resultados-header">
          <div class="nav-bar">
            <div class="nav-buttons">
              <button class="btn-back" type="button" @click="volverAtras"><i class="fa-solid fa-arrow-left"></i> Volver</button>
              <button class="btn-back" type="button" @click="router.push('/inicio')"><i class="fa-solid fa-house-user"></i> Inicio</button>
              <div class="nav-divider" v-if="proyectoIdActivo"></div>
              <button class="btn-back" type="button" @click="editarParametros" v-if="proyectoIdActivo"><i class="fa-solid fa-sliders"></i> Editar parámetros</button>
              <button class="btn-back" type="button" @click="modificarPrestaciones" v-if="proyectoIdActivo"><i class="fa-solid fa-list-check"></i> Modificar prestaciones</button>
            </div>
            <div class="session-badge">
              <i class="fa-solid fa-circle-user"></i>
              <span class="session-nombre">{{ authStore.correoUsuario }}</span>
              <button class="btn-logout" type="button" @click="cerrarSesion">
                <i class="fa-solid fa-right-from-bracket"></i>
              </button>
            </div>
          </div>
          <div class="title-actions-row">
            <div>
              <h2 class="section-title">Resultados — Atención Abierta</h2>
              <p class="section-subtitle">Resumen de equipamiento por prestación y recinto.</p>
            </div>
            <div class="header-actions">
              <button class="btn-export btn-export-excel" type="button" @click="exportarExcel"><i class="fa-solid fa-file-excel"></i> Excel</button>
              <button class="btn-export btn-export-pdf" type="button" @click="exportarPdf"><i class="fa-solid fa-file-pdf"></i> PDF</button>
            </div>
          </div>
        </header>

        <!-- Banner resumen -->
        <section class="resumen-banner">
          <div class="banner-left">
            <h3>Proyecto</h3>
            <p class="banner-sub">{{ nombreProyecto }}</p>
          </div>
          <div class="banner-total" v-if="cargando"><span class="metric-label">Cargando...</span></div>
          <div class="banner-total" v-else-if="error"><span class="metric-label" style="color:#ffaaaa">{{ error }}</span></div>
          <div class="banner-total" v-else>
            <span class="metric-value">{{ totalEquipos }}</span>
            <span class="metric-label">Equipos (total)</span>
          </div>
        </section>

        <!-- Resumen de equipos -->
        <section class="resumen-panel" v-if="!cargando && !error">
          <div class="panel-title">Resumen de equipos requeridos</div>
          <div class="resumen-list">
            <div class="resumen-row resumen-row-head">
              <div>Equipo</div>
              <div class="row-total">Total</div>
            </div>
            <div v-if="Object.keys(equiposSummary).length === 0" class="lista-vacia">Sin equipos calculados.</div>
            <div v-for="(cantidad, equipo) in equiposSummary" :key="equipo" class="resumen-row">
              <div class="equipo-nombre">{{ equipo }}</div>
              <div class="row-total">{{ cantidad }}</div>
            </div>
          </div>
        </section>

        <!-- Resumen de recintos -->
        <section class="resumen-panel" v-if="!cargando && !error && Object.keys(recintoSummary).length > 0">
          <div class="panel-title">Resumen por recinto</div>
          <div class="resumen-list">
            <div class="resumen-row resumen-row-head">
              <div>Recinto</div>
              <div class="row-total">Requerimiento</div>
            </div>
            <div v-for="(cantidad, recinto) in recintoSummary" :key="recinto" class="resumen-row">
              <div class="equipo-nombre">{{ recinto }}</div>
              <div class="row-total">{{ cantidad }}</div>
            </div>
          </div>
        </section>

        <!-- Detalle por prestación -->
        <section class="desglose-panel" v-if="!cargando && !error">
          <div class="panel-title">Detalle por prestación</div>
          <div v-for="prestacion in prestaciones" :key="prestacion.ID_PRESTACION" class="recinto-card">
            <div class="recinto-title recinto-title-toggle" @click="togglePrestacion(prestacion.ID_PRESTACION)">
              <span>{{ prestacion.COD_PRESTACION || prestacion.ID_PRESTACION }} — {{ prestacion.NOMBRE_PRESTACION || 'Prestación' }}
                <span class="recinto-count">(EEMM: {{ prestacion.REQUERIMIENTO }})</span>
              </span>
              <i class="fa-solid" :class="abiertos[prestacion.ID_PRESTACION] ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </div>
            <div class="recinto-body" v-show="abiertos[prestacion.ID_PRESTACION]">
              <div class="tabla-mini">
                <div class="tabla-mini-head" style="grid-template-columns: 2fr 1fr 1fr 80px">
                  <span>Equipo</span><span>Tipo</span><span>Recinto</span><span class="tabla-mini-cantidad">Cant.</span>
                </div>
                <div v-for="eq in prestacion.EQUIPOS" :key="eq.EQUIPO + eq.RECINTO" class="tabla-mini-row" style="grid-template-columns: 2fr 1fr 1fr 80px">
                  <span>{{ eq.EQUIPO }}</span>
                  <span>{{ eq.TIPO_EQUIPO }}</span>
                  <span>{{ eq.RECINTO }}</span>
                  <span class="tabla-mini-cantidad">{{ eq.CANTIDAD }}</span>
                </div>
              </div>
            </div>
          </div>
        </section>
      </main>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppLayout from '@/layouts/AppLayout.vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const cargando = ref(true)
const error = ref(null)
const nombreProyecto = ref('Proyecto seleccionado')
const proyectoIdActivo = ref(null)
const equiposSummary = ref({})
const recintoSummary = ref({})
const prestaciones = ref([])
const abiertos = ref({})

const totalEquipos = computed(() => Object.values(equiposSummary.value).reduce((a, b) => a + b, 0))

function togglePrestacion(id) {
  abiertos.value = { ...abiertos.value, [id]: !abiertos.value[id] }
}

function aplicarDatos(datos) {
  proyectoIdActivo.value = datos.proyecto_id ?? null
  nombreProyecto.value   = datos.nombre_proyecto ?? localStorage.getItem('ephdem_nombre_proyecto_activo_abierta') ?? 'Desconocido'
  equiposSummary.value   = datos.equipos_summary ?? {}
  recintoSummary.value   = datos.recinto_summary ?? {}
  prestaciones.value     = datos.prestaciones ?? []
  cargando.value = false
}

function cargarDesdeLocalStorage() {
  const raw = localStorage.getItem('ephdem_resultado_abierta')
  if (!raw) { error.value = 'No hay resultados disponibles.'; cargando.value = false; return }
  try {
    const parsed = JSON.parse(raw)
    aplicarDatos(parsed)
  } catch (e) {
    error.value = 'Error al leer los resultados.'
    cargando.value = false
  }
}

async function cargarDesdeServidor(proyectoId) {
  const usuarioId = authStore.usuarioId
  try {
    const url = `${import.meta.env.VITE_API_BASE}/get/obtener_resultados_abierta.php?proyecto_id=${proyectoId}&usuario_id=${usuarioId}`
    const resp = await fetch(url)
    const json = await resp.json()
    if (!json.ok) { error.value = json.error || 'Error al cargar resultados.'; cargando.value = false }
    else { aplicarDatos(json.datos) }
  } catch (e) {
    error.value = 'Error de red.'
    cargando.value = false
  }
}

function editarParametros() { router.push(`/parametros-abierta/${proyectoIdActivo.value}`) }
function modificarPrestaciones() { router.push(`/prestaciones-abierta/${proyectoIdActivo.value}`) }
function volverAtras() { router.back() }
function cerrarSesion() { authStore.logout(); router.push('/login') }

function exportarExcel() {
  if (!proyectoIdActivo.value) { alert('No se pudo identificar el proyecto.'); return }
  window.open(`${import.meta.env.VITE_API_BASE}/generar/generar_xls_abierta.php?id=${proyectoIdActivo.value}`, '_blank')
}

function exportarPdf() {
  if (!proyectoIdActivo.value) { alert('No se pudo identificar el proyecto.'); return }
  window.open(`${import.meta.env.VITE_API_BASE}/generar/generar_pdf_abierta.php?id=${proyectoIdActivo.value}`, '_blank')
}

onMounted(() => {
  proyectoIdActivo.value = route.params.proyectoId || localStorage.getItem('ephdem_proyecto_activo_abierta')
  if (route.params.proyectoId) cargarDesdeServidor(route.params.proyectoId)
  else cargarDesdeLocalStorage()
})
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

.resultados-page { background: $color-fondo; flex: 1; }
.resultados-content { max-width: 1200px; margin: 32px auto 72px auto; padding: 0 20px; display: flex; flex-direction: column; gap: 24px; }

.nav-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.nav-buttons { display: flex; gap: 12px; align-items: center; }
.nav-divider { width: 1px; height: 24px; background: #cbd5e1; margin: 0 4px; }
.btn-back { background: $color-primario; color: #fff; border: 1px solid $color-primario; border-radius: 999px; padding: 6px 12px; font-weight: 600; cursor: pointer; margin-bottom: 6px; }
.session-badge { display: flex; align-items: center; gap: 8px; padding: 6px 14px 6px 10px; background: rgba(0,60,88,0.06); border: 1.5px solid rgba(0,60,88,0.18); border-radius: 999px; color: $color-primario; font-size: 0.88rem; font-weight: 600; }
.session-nombre { white-space: nowrap; }
.btn-logout { background: none; border: none; color: $color-primario; cursor: pointer; padding: 2px 4px; opacity: 0.7; }

.title-actions-row { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; }
.header-actions { display: flex; gap: 10px; }
.section-title { font-size: 1.6rem; font-weight: 700; color: $color-primario; margin: 0 0 4px; }
.section-subtitle { margin: 0; color: $color-texto-secundario; }

.btn-export { background: #fff; border: 1px solid $color-borde; border-radius: 12px; padding: 10px 14px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; }
.btn-export-excel { color: #1e8e5a; border-color: rgba(30,142,90,0.35); }
.btn-export-pdf { color: #d5431c; }

.resumen-banner { background: $color-primario; border-radius: 18px; padding: 22px 24px; display: flex; align-items: center; justify-content: space-between; gap: 18px; box-shadow: 0 10px 22px rgba(0,60,88,0.25); }
.banner-left h3 { margin: 0; font-size: 1.3rem; font-weight: 700; color: #fff; }
.banner-sub { margin: 4px 0 0; font-size: 0.95rem; color: rgba(255,255,255,0.75); }
.banner-total { display: flex; align-items: baseline; gap: 10px; color: #fff; }
.metric-value { font-size: 1.6rem; font-weight: 700; }
.metric-label { font-size: 0.85rem; color: rgba(255,255,255,0.7); }

.resumen-panel, .desglose-panel { background: #fff; border-radius: 16px; padding: 20px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; }
.panel-title { font-weight: 700; color: $color-primario; margin-bottom: 16px; }
.resumen-list { display: flex; flex-direction: column; border: 1px solid $color-borde; border-radius: 12px; overflow: hidden; }
.resumen-row { background: #fff; padding: 10px 16px; display: grid; grid-template-columns: 1fr 70px; gap: 16px; align-items: center; border-bottom: 1px solid $color-borde; &:last-child { border-bottom: none; } }
.resumen-row-head { background: #ddeaf4; font-weight: 700; color: $color-primario; }
.row-total { font-size: 1.2rem; font-weight: 700; color: $color-primario; text-align: right; }
.equipo-nombre { font-weight: 600; color: $color-primario; }

.recinto-card { border-radius: 12px; border: 1px solid $color-borde; box-shadow: 0 2px 8px $color-sombra-suave; overflow: hidden; background: #fff; margin-bottom: 14px; }
.recinto-title { font-weight: 700; color: $color-primario; background: #eef5f9; padding: 10px 14px; }
.recinto-title-toggle { display: flex; align-items: center; justify-content: space-between; cursor: pointer; &:hover { background: #ddeaf4; } i { font-size: 0.85rem; opacity: 0.7; } }
.recinto-body { border-top: 1px solid $color-borde; }
.recinto-count { font-weight: 500; color: $color-texto-secundario; }
.tabla-mini { display: flex; flex-direction: column; }
.tabla-mini-head { display: grid; background: #eef5f9; padding: 8px 12px; font-size: 0.78rem; font-weight: 700; color: $color-primario; text-transform: uppercase; }
.tabla-mini-row { display: grid; align-items: center; padding: 8px 12px; border-bottom: 1px solid $color-borde; font-size: 0.88rem; &:last-child { border-bottom: none; } }
.tabla-mini-cantidad { text-align: right; }
.lista-vacia { color: $color-texto-secundario; padding: 16px; text-align: center; }
</style>