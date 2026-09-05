<template>
  <AppLayout>
    <div class="prestaciones-page">
      <section class="hero hero-compact">
        <div class="hero-bg"></div>
        <div class="hero-content">
          <div class="hero-tag">MÓDULO EPHDEM</div>
          <h1 class="hero-title">Estudio de Preinversión Hospitalaria</h1>
          <p class="hero-sub">Selecciona las prestaciones de atención abierta para tu proyecto.</p>
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
              <button class="btn-logout" type="button" @click="cerrarSesion">
                <i class="fa-solid fa-right-from-bracket"></i>
              </button>
            </div>
          </div>
          <h2 class="section-title">Prestaciones — Atención Abierta</h2>
          <div class="proyecto-activo-badge">
            <span class="badge-label">Proyecto en edición</span>
            <span class="badge-name">{{ nombreProyectoActivo }}</span>
          </div>
          <div class="instruccion-indicator">
            <span class="instruccion-icon-circle"><i class="fa-solid fa-circle-info"></i></span>
            <span class="instruccion-texto">
              Selecciona las prestaciones MAI de FONASA usando
              <span class="instruccion-badge instruccion-badge--agregar"><i class="fa-solid fa-plus"></i></span>
              para agregar y
              <span class="instruccion-badge instruccion-badge--quitar"><i class="fa-solid fa-xmark"></i></span>
              para quitar. Luego presiona <strong>Guardar y confirmar</strong>.
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
              <option v-for="sub in opcionesSubarea" :key="sub" :value="sub">{{ sub }}</option>
            </select>
          </div>
        </section>

        <section class="prestaciones-grid">
          <div class="prestaciones-panel">
            <div class="panel-title">Disponibles</div>
            <div v-if="isLoading" class="lista-vacia">Cargando prestaciones...</div>
            <div v-else-if="prestacionesFiltradas.length === 0" class="lista-vacia">No hay prestaciones disponibles.</div>
            <div v-else class="prestaciones-lista">
              <div v-for="prestacion in prestacionesFiltradas" :key="prestacion.ID_PRESTACION" class="prestacion-item">
                <div class="prestacion-info">
                  <div class="prestacion-codigo">{{ prestacion.cod_prestacion }}</div>
                  <div class="prestacion-nombre">{{ prestacion.nombre_prestacion }}</div>
                  <div class="prestacion-area">{{ prestacion.area }}</div>
                </div>
                <button class="accion accion-agregar" @click="agregarPrestacion(prestacion)">
                  <i class="fa-solid fa-plus"></i>
                </button>
              </div>
            </div>
          </div>

          <div class="prestaciones-panel">
            <div class="panel-header-seleccionadas">
              <div class="panel-title">Seleccionadas</div>
              <button class="btn-limpiar-seleccion" :disabled="seleccionadas.length === 0" @click="limpiarSeleccion">
                <i class="fa-solid fa-trash-can"></i> Limpiar
              </button>
            </div>
            <div v-if="seleccionadas.length === 0" class="lista-vacia">Aún no has seleccionado prestaciones.</div>
            <div v-else class="prestaciones-lista">
              <div v-for="prestacion in seleccionadas" :key="prestacion.ID_PRESTACION" class="prestacion-item">
                <div class="prestacion-info">
                  <div class="prestacion-codigo">{{ prestacion.cod_prestacion }}</div>
                  <div class="prestacion-nombre">{{ prestacion.nombre_prestacion }}</div>
                  <div class="prestacion-area">{{ prestacion.area }}</div>
                </div>
                <button class="accion accion-quitar" @click="quitarPrestacion(prestacion)">
                  <i class="fa-solid fa-xmark"></i>
                </button>
              </div>
            </div>
          </div>
        </section>

        <section class="acciones-finales">
          <div class="acciones-resumen">Prestaciones seleccionadas: <b>{{ seleccionadas.length }}</b></div>
          <button class="btn-confirmar" :disabled="seleccionadas.length === 0" @click="guardarYConfirmar">
            Guardar y confirmar
          </button>
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

const filtros = ref({ texto: '', area: '', subarea: '' })
const prestaciones = ref([])
const seleccionadas = ref([])
const isLoading = ref(false)
const nombreProyectoActivo = ref('')

function normalizarTexto(valor) {
  return String(valor ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim()
}

const opcionesArea = computed(() => [...new Set(prestaciones.value.map(p => p.area))])
const opcionesSubarea = [
  'consultas y atencion medica',
  'consultas por otros profesionales de la salud',
  'educacion de grupo',
  'visitas domiciliarias',
  'miscelaneos',
  'actividad compin',
  'telemedicina',
  'teleinterconsulta (telemedicina)',
]

const prestacionesFiltradas = computed(() => {
  const selIds = new Set(seleccionadas.value.map(p => p.ID_PRESTACION))
  const texto = normalizarTexto(filtros.value.texto)
  return prestaciones.value.filter(p => {
    if (selIds.has(p.ID_PRESTACION)) return false
    if (filtros.value.area && p.area !== filtros.value.area) return false
    if (filtros.value.subarea && p.subarea !== filtros.value.subarea) return false
    if (texto) {
      const codigo = normalizarTexto(p.cod_prestacion)
      const nombre = normalizarTexto(p.nombre_prestacion)
      if (!codigo.includes(texto) && !nombre.includes(texto)) return false
    }
    return true
  })
})

async function cargarPrestaciones() {
  isLoading.value = true
  try {
    const url = `${import.meta.env.VITE_API_BASE}/get/get_prestaciones_abierta.php`
    const resp = await fetch(url)
    const json = await resp.json()
    if (json.ok) prestaciones.value = json.datos
  } catch (e) {
    console.error('Error al cargar prestaciones:', e)
  } finally {
    isLoading.value = false
  }
}

function agregarPrestacion(p) {
  if (seleccionadas.value.find(s => s.ID_PRESTACION === p.ID_PRESTACION)) return
  seleccionadas.value.push(p)
}

function quitarPrestacion(p) {
  seleccionadas.value = seleccionadas.value.filter(s => s.ID_PRESTACION !== p.ID_PRESTACION)
}

function limpiarSeleccion() { seleccionadas.value = [] }

function guardarYConfirmar() {
  if (seleccionadas.value.length === 0) { alert('Debes seleccionar al menos una prestación.'); return }
  localStorage.setItem('ephdem_prestaciones_abierta', JSON.stringify(seleccionadas.value))
  const proyectoId = route.params.proyectoId || localStorage.getItem('ephdem_proyecto_activo_abierta')
  if (proyectoId) router.push(`/parametros-abierta/${proyectoId}`)
  else router.push('/parametros-abierta')
}

function volverAtras() { router.back() }
function cerrarSesion() { authStore.logout(); router.push('/login') }

async function cargarDesdeServidor(proyectoId) {
  const usuarioId = authStore.usuarioId
  try {
    const url = `${import.meta.env.VITE_API_BASE}/get/get_prestaciones_demanda_abierta.php?proyecto_id=${proyectoId}&usuario_id=${usuarioId}`
    const resp = await fetch(url)
    const json = await resp.json()
    if (json.ok && Array.isArray(json.datos) && json.datos.length > 0) {
      seleccionadas.value = json.datos.map(p => ({
        ID_PRESTACION:     p.ID_PRESTACION,
        cod_prestacion:    p.cod_prestacion,
        nombre_prestacion: p.nombre_prestacion,
        area:              p.area,
      }))
    }
  } catch (e) {
    console.error('Error al cargar prestaciones del servidor:', e)
  }
}

onMounted(async () => {
  nombreProyectoActivo.value = localStorage.getItem('ephdem_nombre_proyecto_activo_abierta') || 'Desconocido'
  await cargarPrestaciones()
  const proyectoId = route.params.proyectoId || localStorage.getItem('ephdem_proyecto_activo_abierta')
  if (proyectoId) await cargarDesdeServidor(proyectoId)
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

.prestaciones-page { background: $color-fondo; flex: 1; }
.prestaciones-content { max-width: 1200px; margin: 32px auto 72px auto; padding: 0 24px; display: flex; flex-direction: column; gap: 24px; }

.nav-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
.nav-buttons { display: flex; gap: 10px; }
.session-badge { display: flex; align-items: center; gap: 8px; padding: 6px 14px 6px 10px; background: rgba(0,60,88,0.06); border: 1.5px solid rgba(0,60,88,0.18); border-radius: 999px; color: $color-primario; font-size: 0.88rem; font-weight: 600; }
.session-nombre { white-space: nowrap; }
.btn-logout { background: none; border: none; color: $color-primario; cursor: pointer; padding: 2px 4px; opacity: 0.7; }
.btn-back { background: $color-primario; color: #fff; border: 1px solid $color-primario; border-radius: 999px; padding: 6px 12px; font-weight: 600; cursor: pointer; }
.section-title { font-size: 1.6rem; font-weight: 700; color: $color-primario; margin: 0 0 6px; }
.proyecto-activo-badge { display: inline-flex; align-items: center; align-self: flex-start; background: rgba(0,60,88,0.05); border-radius: 6px; padding: 6px 12px; border: 1px solid rgba(0,60,88,0.1); }
.badge-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: rgba(0,60,88,0.6); margin-right: 8px; }
.badge-name { font-size: 0.95rem; font-weight: 700; color: $color-primario; }
.instruccion-indicator { display: flex; align-items: center; gap: 10px; background: rgba(0,60,88,0.06); border: 1px solid rgba(0,60,88,0.14); border-radius: 10px; padding: 10px 16px; }
.instruccion-icon-circle { color: $color-primario; font-size: 1.4rem; flex: 0 0 auto; }
.instruccion-texto { font-size: 1rem; color: $color-primario; line-height: 1.8; strong { font-weight: 700; } }
.instruccion-badge { display: inline-flex; align-items: center; gap: 5px; padding: 2px 9px; border-radius: 6px; font-size: 0.88rem; font-weight: 700; vertical-align: middle; }
.instruccion-badge--agregar { background: $color-exito; color: #fff; }
.instruccion-badge--quitar { background: $color-peligro; color: #fff; }

.filtros-panel { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 16px; background: #fff; border-radius: 16px; padding: 18px 20px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; }
.filtro { display: flex; flex-direction: column; gap: 6px; font-weight: 600; color: $color-primario; }
.filtro select, .filtro input { padding: 10px 12px; border-radius: 10px; border: 1px solid $color-borde; font-size: 0.95rem; }

.prestaciones-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 22px; }
.prestaciones-panel { background: #fff; border-radius: 18px; padding: 20px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; min-height: 320px; display: flex; flex-direction: column; }
.panel-header-seleccionadas { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
.panel-title { font-size: 1.1rem; font-weight: 700; color: $color-primario; margin-bottom: 14px; }
.btn-limpiar-seleccion { display: flex; align-items: center; gap: 6px; padding: 6px 12px; border: 1.5px solid #dc2626; border-radius: 999px; background: rgba(239,68,68,0.08); color: #991b1b; font-weight: 700; font-size: 0.82rem; cursor: pointer; &:disabled { opacity: 0.4; cursor: not-allowed; } }

.prestaciones-lista { display: flex; flex-direction: column; gap: 10px; max-height: 400px; overflow-y: auto; }
.prestacion-item { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 14px; border-radius: 10px; background: $color-claro; border: 1px solid $color-borde; }
.prestacion-info { flex: 1; display: flex; flex-direction: column; gap: 2px; }
.prestacion-codigo { font-size: 0.82rem; font-weight: 700; color: $color-primario; }
.prestacion-nombre { font-size: 0.9rem; font-weight: 500; color: $color-texto-principal; }
.prestacion-area { font-size: 0.75rem; color: $color-texto-secundario; }
.accion { width: 32px; height: 32px; border-radius: 8px; border: none; color: #fff; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; flex: 0 0 auto; }
.accion-agregar { background: $color-exito; }
.accion-quitar { background: $color-peligro; }
.lista-vacia { color: $color-texto-secundario; padding: 20px; text-align: center; background: $color-claro; border-radius: 10px; border: 1px dashed $color-borde; }

.acciones-finales { display: flex; align-items: center; justify-content: space-between; background: #fff; border-radius: 14px; padding: 14px 18px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; }
.acciones-resumen { color: $color-texto-principal; font-weight: 500; }
.btn-confirmar { background: $color-primario; color: #fff; border: none; border-radius: 10px; padding: 10px 18px; font-weight: 700; cursor: pointer; &:disabled { opacity: 0.55; cursor: not-allowed; } }
</style>