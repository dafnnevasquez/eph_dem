<template>
  <AppLayout>
    <div class="equipos-oportunidad-page">
      <section class="hero hero-compact">
        <div class="hero-bg"></div>
        <div class="hero-content">
          <div class="hero-tag">MÓDULO EPHDEM</div>
          <h1 class="hero-title">Estudio de Preinversión Hospitalaria</h1>
          <p class="hero-sub">Selecciona equipos adicionales no contemplados en el cálculo estándar.</p>
        </div>
      </section>

      <main class="eo-content">
        <header class="eo-header">
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
          <h2 class="section-title">Equipos de Oportunidad</h2>
          <div class="proyecto-activo-badge">
            <span class="badge-label">Proyecto en edición</span>
            <span class="badge-name">{{ nombreProyectoActivo }}</span>
          </div>
          <div class="instruccion-indicator">
            <span class="instruccion-icon-circle"><i class="fa-solid fa-circle-info"></i></span>
            <span class="instruccion-texto">
              Selecciona equipos adicionales del catálogo y define la cantidad requerida.
              Estos equipos se sumarán al resultado final del estudio.
            </span>
          </div>
        </header>

        <!-- Buscador -->
        <section class="filtros-panel">
          <div class="filtro">
            <label>Buscar equipo</label>
            <input v-model="filtroTexto" type="text" placeholder="Nombre del equipo" />
          </div>
        </section>

        <!-- Paneles -->
        <section class="equipos-grid">
          <!-- Catálogo -->
          <div class="equipos-panel">
            <div class="panel-title">Catálogo de equipos</div>
            <div v-if="isLoading" class="lista-vacia">Cargando equipos...</div>
            <div v-else-if="equiposFiltrados.length === 0" class="lista-vacia">No hay equipos disponibles.</div>
            <div v-else class="equipos-lista">
              <div v-for="equipo in equiposFiltrados" :key="equipo.id" class="equipo-item">
                <div class="equipo-info">
                  <div class="equipo-nombre">{{ equipo.nombre_equipo }}</div>
                </div>
                <button class="accion accion-agregar" @click="agregarEquipo(equipo)">
                  <i class="fa-solid fa-plus"></i>
                </button>
              </div>
            </div>
          </div>

          <!-- Seleccionados -->
          <div class="equipos-panel">
            <div class="panel-header-seleccionados">
              <div class="panel-title">Equipos seleccionados</div>
              <button class="btn-limpiar" :disabled="seleccionados.length === 0" @click="limpiarSeleccion">
                <i class="fa-solid fa-trash-can"></i> Limpiar
              </button>
            </div>
            <div v-if="seleccionados.length === 0" class="lista-vacia">Aún no has seleccionado equipos.</div>
            <div v-else class="equipos-lista">
              <div v-for="equipo in seleccionados" :key="equipo.id" class="equipo-item equipo-seleccionado">
                <div class="equipo-info">
                  <div class="equipo-nombre">{{ equipo.nombre_equipo }}</div>
                  <div class="equipo-cantidad-control">
                    <button class="btn-cantidad" @click="decrementarCantidad(equipo)"><i class="fa-solid fa-minus"></i></button>
                    <span class="cantidad-valor">{{ equipo.cantidad }}</span>
                    <button class="btn-cantidad" @click="incrementarCantidad(equipo)"><i class="fa-solid fa-plus"></i></button>
                  </div>
                </div>
                <button class="accion accion-quitar" @click="quitarEquipo(equipo)">
                  <i class="fa-solid fa-xmark"></i>
                </button>
              </div>
            </div>
          </div>
        </section>

        <!-- Resumen -->
        <section class="resumen-panel" v-if="seleccionados.length > 0">
          <div class="panel-title">Resumen de equipos de oportunidad</div>
          <div class="resumen-tabla">
            <div class="resumen-head"><span>Equipo</span><span class="resumen-cant">Cantidad</span></div>
            <div v-for="equipo in seleccionados" :key="equipo.id" class="resumen-row">
              <span>{{ equipo.nombre_equipo }}</span>
              <span class="resumen-cant">{{ equipo.cantidad }}</span>
            </div>
            <div class="resumen-total">
              <span><strong>Total equipos de oportunidad</strong></span>
              <span class="resumen-cant"><strong>{{ totalEquipos }}</strong></span>
            </div>
          </div>
        </section>

        <!-- Acciones -->
        <section class="acciones-finales">
          <button class="btn-secundario" @click="router.push(`/rrhh/${proyectoId}`)">
            <i class="fa-solid fa-arrow-left"></i> Volver a RRHH
          </button>
          <button class="btn-principal" :disabled="seleccionados.length === 0" @click="guardarYFinalizar">
            Guardar y ver resultado final <i class="fa-solid fa-flag-checkered"></i>
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

const nombreProyectoActivo = ref('')
const proyectoId = ref(null)
const filtroTexto = ref('')
const isLoading = ref(false)
const equipos = ref([])
const seleccionados = ref([])

const equiposFiltrados = computed(() => {
  const texto = filtroTexto.value.toLowerCase().trim()
  const selIds = new Set(seleccionados.value.map(e => e.id))
  return equipos.value
    .filter(e => !selIds.has(e.id))
    .filter(e => !texto || e.nombre_equipo.toLowerCase().includes(texto))
})

const totalEquipos = computed(() => seleccionados.value.reduce((acc, e) => acc + e.cantidad, 0))

async function cargarEquipos() {
  isLoading.value = true
  try {
    const url = `${import.meta.env.VITE_API_BASE}/get/get_prestaciones.php`
    // Por ahora usamos el mismo catálogo de equipos disponible
    // Cuando esté la BD conectada se llamará a un endpoint específico de equipos
    const resp = await fetch(`${import.meta.env.VITE_API_BASE}/get/get_prestaciones.php`)
    const json = await resp.json()
    // Provisional: cargamos prestaciones como placeholder
    // Se reemplazará por un endpoint de equipos
    equipos.value = [
      { id: 1, nombre_equipo: 'Carro de paro completo' },
      { id: 2, nombre_equipo: 'Monitor multiparámetros' },
      { id: 3, nombre_equipo: 'Bomba de infusión volumétrica' },
      { id: 4, nombre_equipo: 'Desfibrilador' },
      { id: 5, nombre_equipo: 'Ventilador mecánico' },
      { id: 6, nombre_equipo: 'Aspirador quirúrgico' },
      { id: 7, nombre_equipo: 'Lámpara cialítica' },
      { id: 8, nombre_equipo: 'Mesa quirúrgica' },
      { id: 9, nombre_equipo: 'Electrobisturí' },
      { id: 10, nombre_equipo: 'Camilla de traslado' },
    ]
  } catch (e) {
    console.error('Error al cargar equipos:', e)
  } finally {
    isLoading.value = false
  }
}

function agregarEquipo(equipo) {
  if (seleccionados.value.find(e => e.id === equipo.id)) return
  seleccionados.value.push({ ...equipo, cantidad: 1 })
}

function quitarEquipo(equipo) {
  seleccionados.value = seleccionados.value.filter(e => e.id !== equipo.id)
}

function limpiarSeleccion() {
  seleccionados.value = []
}

function incrementarCantidad(equipo) {
  const e = seleccionados.value.find(e => e.id === equipo.id)
  if (e) e.cantidad++
}

function decrementarCantidad(equipo) {
  const e = seleccionados.value.find(e => e.id === equipo.id)
  if (e && e.cantidad > 1) e.cantidad--
}

function guardarYFinalizar() {
  localStorage.setItem('ephdem_equipos_oportunidad', JSON.stringify(seleccionados.value))
  router.push(`/resultados/${proyectoId.value}`)
}

function volverAtras() { router.back() }
function cerrarSesion() { authStore.logout(); router.push('/login') }

onMounted(async () => {
  nombreProyectoActivo.value = localStorage.getItem('ephdem_nombre_proyecto_activo') || 'Desconocido'
  proyectoId.value = route.params.proyectoId || localStorage.getItem('ephdem_proyecto_activo')
  await cargarEquipos()
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

.equipos-oportunidad-page { background: $color-fondo; flex: 1; }
.eo-content { max-width: 1200px; margin: 32px auto 72px auto; padding: 0 24px; display: flex; flex-direction: column; gap: 24px; }

.nav-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
.nav-buttons { display: flex; gap: 10px; }
.session-badge { display: flex; align-items: center; gap: 8px; padding: 6px 14px 6px 10px; background: rgba(0,60,88,0.06); border: 1.5px solid rgba(0,60,88,0.18); border-radius: 999px; color: $color-primario; font-size: 0.88rem; font-weight: 600; }
.session-nombre { white-space: nowrap; }
.btn-logout { background: none; border: none; color: $color-primario; cursor: pointer; padding: 2px 4px; opacity: 0.7; &:hover { opacity: 1; color: #c62828; } }
.btn-back { background: $color-primario; color: #fff; border: 1px solid $color-primario; border-radius: 999px; padding: 6px 12px; font-weight: 600; cursor: pointer; &:hover { background: mix(#fff, $color-primario, 6%); } }

.section-title { font-size: 1.6rem; font-weight: 700; color: $color-primario; margin: 0 0 6px; }
.proyecto-activo-badge { display: inline-flex; align-items: center; align-self: flex-start; background: rgba(0,60,88,0.05); border-radius: 6px; padding: 6px 12px; border: 1px solid rgba(0,60,88,0.1); }
.badge-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: rgba(0,60,88,0.6); margin-right: 8px; }
.badge-name { font-size: 0.95rem; font-weight: 700; color: $color-primario; }

.instruccion-indicator { display: flex; align-items: center; gap: 10px; background: rgba(0,60,88,0.06); border: 1px solid rgba(0,60,88,0.14); border-radius: 10px; padding: 10px 16px; }
.instruccion-icon-circle { color: $color-primario; font-size: 1.4rem; flex: 0 0 auto; }
.instruccion-texto { font-size: 1rem; color: $color-primario; line-height: 1.6; }

.filtros-panel { background: #fff; border-radius: 16px; padding: 18px 20px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; }
.filtro { display: flex; flex-direction: column; gap: 6px; font-weight: 600; color: $color-primario; max-width: 400px; }
.filtro input { padding: 10px 12px; border-radius: 10px; border: 1px solid $color-borde; font-size: 0.95rem; }

.equipos-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 22px; }
.equipos-panel { background: #fff; border-radius: 18px; padding: 20px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; min-height: 320px; display: flex; flex-direction: column; }
.panel-header-seleccionados { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.panel-title { font-size: 1.1rem; font-weight: 700; color: $color-primario; margin-bottom: 14px; }
.btn-limpiar { display: flex; align-items: center; gap: 6px; padding: 6px 12px; border: 1.5px solid #dc2626; border-radius: 999px; background: rgba(239,68,68,0.08); color: #991b1b; font-weight: 700; font-size: 0.82rem; cursor: pointer; &:disabled { opacity: 0.4; cursor: not-allowed; } }

.equipos-lista { display: flex; flex-direction: column; gap: 10px; max-height: 400px; overflow-y: auto; }
.equipo-item { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 10px 14px; border-radius: 10px; background: $color-claro; border: 1px solid $color-borde; }
.equipo-seleccionado { background: rgba(0,60,88,0.04); border-color: rgba(0,60,88,0.2); }
.equipo-info { flex: 1; display: flex; flex-direction: column; gap: 6px; }
.equipo-nombre { font-size: 0.9rem; font-weight: 600; color: $color-texto-principal; }
.equipo-cantidad-control { display: flex; align-items: center; gap: 8px; }
.btn-cantidad { width: 24px; height: 24px; border-radius: 50%; border: 1.5px solid $color-primario; background: #fff; color: $color-primario; font-size: 0.75rem; cursor: pointer; display: flex; align-items: center; justify-content: center; &:hover { background: $color-primario; color: #fff; } }
.cantidad-valor { font-size: 1rem; font-weight: 700; color: $color-primario; min-width: 24px; text-align: center; }

.accion { width: 32px; height: 32px; border-radius: 8px; border: none; color: #fff; font-weight: 700; cursor: pointer; display: flex; align-items: center; justify-content: center; flex: 0 0 auto; }
.accion-agregar { background: $color-exito; }
.accion-quitar { background: $color-peligro; }
.lista-vacia { color: $color-texto-secundario; font-size: 0.9rem; padding: 20px; text-align: center; background: $color-claro; border-radius: 10px; border: 1px dashed $color-borde; }

.resumen-panel { background: #fff; border-radius: 16px; padding: 20px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; }
.resumen-tabla { display: flex; flex-direction: column; border: 1px solid $color-borde; border-radius: 10px; overflow: hidden; }
.resumen-head { display: grid; grid-template-columns: 1fr 80px; background: #e9f1f6; padding: 10px 14px; font-size: 0.82rem; font-weight: 700; color: $color-primario; text-transform: uppercase; }
.resumen-row { display: grid; grid-template-columns: 1fr 80px; padding: 10px 14px; border-top: 1px solid $color-borde; font-size: 0.9rem; }
.resumen-total { display: grid; grid-template-columns: 1fr 80px; padding: 12px 14px; border-top: 2px solid $color-primario; background: rgba(0,60,88,0.04); font-size: 0.95rem; }
.resumen-cant { text-align: right; }

.acciones-finales { display: flex; align-items: center; justify-content: space-between; background: #fff; border-radius: 14px; padding: 16px 20px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; }
.btn-principal { background: $color-primario; color: #fff; border: none; border-radius: 10px; padding: 12px 20px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; &:hover { opacity: 0.9; } &:disabled { opacity: 0.5; cursor: not-allowed; } }
.btn-secundario { background: rgba(0,60,88,0.08); color: $color-primario; border: 1px solid rgba(0,60,88,0.2); border-radius: 10px; padding: 12px 20px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; &:hover { background: rgba(0,60,88,0.14); } }

@media (max-width: 900px) {
  .equipos-grid { grid-template-columns: 1fr; }
}
</style>