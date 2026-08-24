<template>
  <AppLayout>
    <div class="creacion-proyecto-page">
      <section class="hero hero-compact">
        <div class="hero-bg"></div>
        <div class="hero-content">
          <div class="hero-tag">MÓDULO EPHDEM</div>
          <h1 class="hero-title">Estudio de Preinversión Hospitalaria</h1>
          <p class="hero-sub">Crea un nuevo proyecto de estudio de preinversión hospitalaria.</p>
        </div>
      </section>

      <main class="creacion-content">
        <header class="creacion-header">
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
          <h2 class="section-title">Crear Proyecto</h2>
          <div class="instruccion-indicator">
            <span class="instruccion-icon-circle"><i class="fa-solid fa-circle-info"></i></span>
            <span class="instruccion-texto">Ingresa el nombre del proyecto y presiona <strong>Guardar</strong> para registrarlo en el sistema.</span>
          </div>
        </header>

        <section class="formulario-panel">
          <form @submit.prevent="guardarProyecto" class="formulario">
            <div class="form-group">
              <label for="nombre-proyecto" class="form-label">Nombre del proyecto</label>
              <input
                id="nombre-proyecto"
                v-model="formulario.nombreProyecto"
                type="text"
                class="form-input"
                placeholder="Ingresa el nombre del proyecto"
                required
              />
            </div>
            <div class="acciones-formulario">
              <button type="submit" class="btn-principal">Guardar</button>
              <button type="button" class="btn-secundario" @click="cancelar">Cancelar</button>
            </div>
          </form>
        </section>

        <section class="proyectos-panel">
          <div class="panel-header">
            <h3 class="panel-title">Mis proyectos</h3>
            <div class="panel-actions">
              <div class="sort-control">
                <label>Ordenar por:</label>
                <select v-model="ordenSeleccionado" class="sort-select">
                  <option value="fecha_desc">Más recientes primero</option>
                  <option value="fecha_asc">Más antiguos primero</option>
                  <option value="alfabetico_asc">Alfabético (A-Z)</option>
                  <option value="alfabetico_desc">Alfabético (Z-A)</option>
                </select>
              </div>
            </div>
          </div>
          <div v-if="cargandoProyectos" class="proyectos-estado">
            <i class="fa-solid fa-spinner fa-spin"></i> Cargando proyectos...
          </div>
          <div v-else-if="proyectosPrevios.length === 0" class="proyectos-estado">
            Aún no tienes proyectos creados.
          </div>
          <div v-else class="proyectos-table">
            <div class="table-row table-head">
              <div>Nombre del proyecto</div>
              <div>Fecha de creación</div>
              <div>Tipo de proyecto</div>
              <div class="table-actions">Acciones</div>
            </div>
            <div v-for="proyecto in proyectosPreviosOrdenados" :key="proyecto.id" class="table-row">
              <div class="table-name">{{ proyecto.nombre_proyecto }}</div>
              <div>{{ proyecto.fecha_creacion }}</div>
              <div class="table-chip">{{ proyecto.tipo_proyecto }}</div>
              <div class="table-actions">
                <button class="btn-primary" type="button" @click="verProyecto(proyecto)">Ver</button>
              </div>
            </div>
          </div>
        </section>
      </main>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppLayout from '@/layouts/AppLayout.vue'

const router = useRouter()
const authStore = useAuthStore()
const formulario = ref({ nombreProyecto: '' })
const proyectosPrevios = ref([])
const cargandoProyectos = ref(true)
const ordenSeleccionado = ref('fecha_desc')

const proyectosPreviosOrdenados = computed(() => {
  const lista = [...proyectosPrevios.value]
  return lista.sort((a, b) => {
    if (ordenSeleccionado.value === 'alfabetico_asc') return (a.nombre_proyecto || '').localeCompare(b.nombre_proyecto || '')
    if (ordenSeleccionado.value === 'alfabetico_desc') return (b.nombre_proyecto || '').localeCompare(a.nombre_proyecto || '')
    if (ordenSeleccionado.value === 'fecha_asc') return (a.id || a.id_proyecto || 0) - (b.id || b.id_proyecto || 0)
    return (b.id || b.id_proyecto || 0) - (a.id || a.id_proyecto || 0)
  })
})

onMounted(async () => { await cargarProyectosPrevios() })

async function cargarProyectosPrevios() {
  const userId = authStore.usuarioId
  if (!userId) return
  cargandoProyectos.value = true
  try {
    const url = `${import.meta.env.VITE_API_BASE}/get_proyectos.php?usuario_id=${userId}`
    const response = await fetch(url, { method: 'GET', credentials: 'same-origin' })
    const result = await response.json()
    if (result.ok && Array.isArray(result.datos)) proyectosPrevios.value = result.datos
  } catch (error) {
    console.error('Error al cargar proyectos previos:', error)
  } finally {
    cargandoProyectos.value = false
  }
}

async function guardarProyecto() {
  const { nombreProyecto } = formulario.value
  if (!nombreProyecto.trim()) { alert('Por favor, ingresa un nombre para el proyecto.'); return }
  const userId = authStore.usuarioId
  if (!userId) { alert('No hay sesión activa.'); router.push('/login'); return }
  try {
    const url = `${import.meta.env.VITE_API_BASE}/crear_proyecto.php`
    const response = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ nombre_proyecto: nombreProyecto, tipo_proyecto: 'Atención cerrada', usuario_id: userId })
    })
    const result = await response.json()
    if (!response.ok || !result.ok) { alert(result.error || 'Error al crear el proyecto.'); return }
    localStorage.removeItem('ephdem_prestaciones_seleccionadas')
    localStorage.removeItem('ephdem_parametros_prestaciones')
    localStorage.removeItem('ephdem_resultado_calculo')
    localStorage.setItem('ephdem_proyecto_activo', result.datos.id_proyecto)
    localStorage.setItem('ephdem_nombre_proyecto_activo', nombreProyecto)
    router.push('/prestaciones')
  } catch (error) {
    alert('Ocurrió un error de red al intentar comunicarse con el servidor.')
  }
}

function cancelar() { volverAtras() }
function volverAtras() { router.back(); setTimeout(() => window.scrollTo({ top: 0, left: 0, behavior: 'auto' }), 0) }
function cerrarSesion() { authStore.logout(); router.push('/login') }
function verProyecto(proyecto) {
  const id = proyecto.id || proyecto.id_proyecto
  localStorage.setItem('ephdem_proyecto_activo', id)
  localStorage.setItem('ephdem_nombre_proyecto_activo', proyecto.nombre_proyecto)
  router.push(`/resultados/${id}`)
}
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

.creacion-proyecto-page { background: $color-fondo; flex: 1; }
.creacion-content { width: 100%; max-width: 1100px; margin: 32px auto 72px auto; padding: 0 20px; display: flex; flex-direction: column; gap: 24px; }

.nav-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
.nav-buttons { display: flex; gap: 10px; }
.session-badge {
  display: flex; align-items: center; gap: 8px; padding: 6px 14px 6px 10px;
  background: rgba(0,60,88,0.06); border: 1.5px solid rgba(0,60,88,0.18);
  border-radius: 999px; color: $color-primario; font-size: 0.88rem; font-weight: 600;
}
.session-nombre { white-space: nowrap; }
.btn-logout { background: none; border: none; color: $color-primario; cursor: pointer; padding: 2px 4px; opacity: 0.7; &:hover { opacity: 1; color: #c62828; } }
.btn-back {
  background: $color-primario; color: #fff; border: 1px solid $color-primario;
  border-radius: 999px; padding: 6px 12px; font-weight: 600; cursor: pointer;
  &:hover { background: mix(#fff, $color-primario, 6%); }
}
.section-title { font-size: 1.6rem; font-weight: 700; color: $color-primario; margin: 0 0 6px; }

.instruccion-indicator { display: flex; align-items: center; gap: 10px; margin-top: 14px; background: rgba(0,60,88,0.06); border: 1px solid rgba(0,60,88,0.14); border-radius: 10px; padding: 10px 16px; }
.instruccion-icon-circle { display: flex; align-items: center; justify-content: center; color: $color-primario; font-size: 1.4rem; }
.instruccion-texto { font-size: 1.1rem; color: $color-primario; line-height: 1.45; strong { font-weight: 700; } }

.formulario-panel { background: #fff; border-radius: 16px; padding: 32px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; }
.formulario { display: flex; flex-direction: column; gap: 24px; }
.form-group { display: flex; flex-direction: column; gap: 8px; }
.form-label { font-size: 1rem; font-weight: 600; color: $color-primario; }
.form-input {
  padding: 12px 14px; border: 1px solid $color-borde; border-radius: 8px;
  font-size: 1rem; color: $color-texto-principal;
  &:focus { outline: none; border-color: $color-primario; box-shadow: 0 0 0 3px rgba(0,60,88,0.1); }
  &::placeholder { color: $color-texto-secundario; }
}
.acciones-formulario { display: flex; gap: 12px; justify-content: flex-end; margin-top: 12px; }
.btn-principal { background: $color-primario; color: #fff; border: none; border-radius: 10px; padding: 10px 24px; font-weight: 700; cursor: pointer; &:hover { background: mix(#fff, $color-primario, 8%); } }
.btn-secundario { background: rgba(0,60,88,0.12); color: $color-primario; border: 1px solid rgba(0,60,88,0.2); border-radius: 10px; padding: 10px 24px; font-weight: 700; cursor: pointer; &:hover { background: rgba(0,60,88,0.18); } }

.proyectos-panel { background: #fff; border-radius: 16px; padding: 32px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; }
.panel-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 20px; }
.panel-actions { display: flex; justify-content: flex-end; }
.sort-control { display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: $color-texto-secundario; }
.sort-select { padding: 6px 12px; border: 1px solid $color-borde; border-radius: 8px; font-size: 0.9rem; background: #fff; cursor: pointer; &:focus { border-color: $color-primario; outline: none; } }
.panel-title { font-size: 1.25rem; font-weight: 700; color: $color-primario; margin: 0; }

.proyectos-table { display: flex; flex-direction: column; gap: 10px; }
.table-row { display: grid; grid-template-columns: 2.2fr 1fr 1fr 1.2fr; gap: 16px; align-items: center; padding: 14px 12px; border-radius: 12px; background: $color-claro; border: 1px solid $color-borde; }
.table-head { background: #e9f1f6; font-weight: 600; color: $color-primario; }
.table-name { font-weight: 600; color: $color-texto-principal; }
.table-chip { display: inline-flex; align-items: center; justify-content: center; padding: 6px 12px; border-radius: 999px; background: rgba(0,60,88,0.08); color: $color-primario; font-weight: 600; font-size: 0.85rem; }
.table-actions { display: flex; gap: 10px; justify-content: flex-end; }
.btn-primary { background: $color-primario; color: #fff; border: none; border-radius: 8px; padding: 8px 14px; font-weight: 600; cursor: pointer; &:hover { opacity: 0.85; } }

.proyectos-estado { padding: 20px; text-align: center; color: $color-texto-secundario; font-size: 0.95rem; display: flex; align-items: center; justify-content: center; gap: 8px; }
</style>