<template>
  <AppLayout>
    <div class="creacion-proyecto-page">
      <section class="hero hero-compact">
        <div class="hero-bg"></div>
        <div class="hero-content">
          <div class="hero-tag">MÓDULO EPHDEM</div>
          <h1 class="hero-title">Estudio de Preinversión Hospitalaria</h1>
          <p class="hero-sub">Crea un nuevo proyecto de estudio de preinversión — Atención Abierta.</p>
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
              <button class="btn-logout" type="button" @click="cerrarSesion">
                <i class="fa-solid fa-right-from-bracket"></i>
              </button>
            </div>
          </div>
          <h2 class="section-title">Crear Proyecto — Atención Abierta</h2>
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
                :class="{ 'form-input--error': errorNombre }"
                placeholder="Ingresa el nombre del proyecto"
                required
              />
              <span v-if="errorNombre" class="form-error">{{ errorNombre }}</span>
            </div>
            <div class="acciones-formulario">
              <button type="submit" class="btn-principal" :disabled="cargando">
                <span v-if="cargando"><i class="fa-solid fa-spinner fa-spin"></i> Guardando...</span>
                <span v-else>Guardar</span>
              </button>
              <button type="button" class="btn-secundario" @click="cancelar">Cancelar</button>
            </div>
          </form>
        </section>

        <section class="proyectos-panel">
          <div class="panel-header">
            <h3 class="panel-title">Mis proyectos de Atención Abierta</h3>
          </div>
          <div v-if="cargandoProyectos" class="proyectos-estado">
            <i class="fa-solid fa-spinner fa-spin"></i> Cargando proyectos...
          </div>
          <div v-else-if="proyectos.length === 0" class="proyectos-estado">
            Aún no tienes proyectos de atención abierta creados.
          </div>
          <div v-else class="proyectos-table">
            <div class="table-row table-head">
              <div>Nombre del proyecto</div>
              <div>Fecha de creación</div>
              <div class="table-actions">Acciones</div>
            </div>
            <div v-for="proyecto in proyectos" :key="proyecto.ID_PROYECCION" class="table-row">
              <div class="table-name">{{ proyecto.NOMBRE_PROYECCION }}</div>
              <div>{{ proyecto.FECHA_CREACION }}</div>
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
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppLayout from '@/layouts/AppLayout.vue'

const router = useRouter()
const authStore = useAuthStore()
const formulario = ref({ nombreProyecto: '' })
const errorNombre = ref('')
const cargando = ref(false)
const proyectos = ref([])
const cargandoProyectos = ref(true)

onMounted(async () => {
  await cargarProyectos()
})

async function cargarProyectos() {
  const userId = authStore.usuarioId
  if (!userId) return
  cargandoProyectos.value = true
  try {
    const url = `${import.meta.env.VITE_API_BASE}/get/get_proyectos_abierta.php?usuario_id=${userId}`
    const response = await fetch(url)
    const result = await response.json()
    if (result.ok && Array.isArray(result.datos)) {
      proyectos.value = result.datos
    }
  } catch (error) {
    console.error('Error al cargar proyectos:', error)
  } finally {
    cargandoProyectos.value = false
  }
}

async function guardarProyecto() {
  errorNombre.value = ''
  const { nombreProyecto } = formulario.value
  if (!nombreProyecto.trim()) {
    errorNombre.value = 'El nombre del proyecto es obligatorio.'
    return
  }
  const userId = authStore.usuarioId
  if (!userId) { router.push('/login'); return }

  cargando.value = true
  try {
    const url = `${import.meta.env.VITE_API_BASE}/crear_proyecto_abierta.php`
    const response = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ nombre_proyecto: nombreProyecto, usuario_id: userId })
    })
    const result = await response.json()
    if (!result.ok) {
      errorNombre.value = result.error || 'Error al crear el proyecto.'
      return
    }
    localStorage.setItem('ephdem_proyecto_activo_abierta', result.datos.id_proyeccion)
    localStorage.setItem('ephdem_nombre_proyecto_activo_abierta', nombreProyecto)
    router.push('/prestaciones-abierta')
  } catch (error) {
    errorNombre.value = 'Error de conexión al servidor.'
  } finally {
    cargando.value = false
  }
}

function verProyecto(proyecto) {
  localStorage.setItem('ephdem_proyecto_activo_abierta', proyecto.ID_PROYECCION)
  localStorage.setItem('ephdem_nombre_proyecto_activo_abierta', proyecto.NOMBRE_PROYECCION)
  router.push(`/resultados-abierta/${proyecto.ID_PROYECCION}`)
}

function cancelar() { volverAtras() }
function volverAtras() { router.back() }
function cerrarSesion() { authStore.logout(); router.push('/login') }
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
.creacion-content { max-width: 1100px; margin: 32px auto 72px auto; padding: 0 20px; display: flex; flex-direction: column; gap: 24px; }

.nav-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
.nav-buttons { display: flex; gap: 10px; }
.session-badge { display: flex; align-items: center; gap: 8px; padding: 6px 14px 6px 10px; background: rgba(0,60,88,0.06); border: 1.5px solid rgba(0,60,88,0.18); border-radius: 999px; color: $color-primario; font-size: 0.88rem; font-weight: 600; }
.session-nombre { white-space: nowrap; }
.btn-logout { background: none; border: none; color: $color-primario; cursor: pointer; padding: 2px 4px; opacity: 0.7; &:hover { opacity: 1; color: #c62828; } }
.btn-back { background: $color-primario; color: #fff; border: 1px solid $color-primario; border-radius: 999px; padding: 6px 12px; font-weight: 600; cursor: pointer; &:hover { background: mix(#fff, $color-primario, 6%); } }

.section-title { font-size: 1.6rem; font-weight: 700; color: $color-primario; margin: 0 0 6px; }
.instruccion-indicator { display: flex; align-items: center; gap: 10px; background: rgba(0,60,88,0.06); border: 1px solid rgba(0,60,88,0.14); border-radius: 10px; padding: 10px 16px; }
.instruccion-icon-circle { color: $color-primario; font-size: 1.4rem; }
.instruccion-texto { font-size: 1rem; color: $color-primario; line-height: 1.5; strong { font-weight: 700; } }

.formulario-panel { background: #fff; border-radius: 16px; padding: 32px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; }
.formulario { display: flex; flex-direction: column; gap: 24px; }
.form-group { display: flex; flex-direction: column; gap: 8px; }
.form-label { font-size: 1rem; font-weight: 600; color: $color-primario; }
.form-input { padding: 12px 14px; border: 1px solid $color-borde; border-radius: 8px; font-size: 1rem; color: $color-texto-principal; &:focus { outline: none; border-color: $color-primario; } &--error { border-color: #e53935; } }
.form-error { font-size: 0.82rem; color: #e53935; }
.acciones-formulario { display: flex; gap: 12px; justify-content: flex-end; }
.btn-principal { background: $color-primario; color: #fff; border: none; border-radius: 10px; padding: 10px 24px; font-weight: 700; cursor: pointer; &:disabled { opacity: 0.6; cursor: not-allowed; } }
.btn-secundario { background: rgba(0,60,88,0.12); color: $color-primario; border: 1px solid rgba(0,60,88,0.2); border-radius: 10px; padding: 10px 24px; font-weight: 700; cursor: pointer; }

.proyectos-panel { background: #fff; border-radius: 16px; padding: 32px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; }
.panel-header { margin-bottom: 20px; }
.panel-title { font-size: 1.25rem; font-weight: 700; color: $color-primario; margin: 0; }
.proyectos-table { display: flex; flex-direction: column; gap: 10px; }
.table-row { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 16px; align-items: center; padding: 14px 12px; border-radius: 12px; background: $color-claro; border: 1px solid $color-borde; }
.table-head { background: #e9f1f6; font-weight: 600; color: $color-primario; }
.table-name { font-weight: 600; color: $color-texto-principal; }
.table-actions { display: flex; gap: 10px; justify-content: flex-end; }
.btn-primary { background: $color-primario; color: #fff; border: none; border-radius: 8px; padding: 8px 14px; font-weight: 600; cursor: pointer; }
.proyectos-estado { padding: 20px; text-align: center; color: $color-texto-secundario; display: flex; align-items: center; justify-content: center; gap: 8px; }
</style>