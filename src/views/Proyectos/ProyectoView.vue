<template>
  <AppLayout>
    <div class="proyectos-page">
      <section class="hero hero-compact">
        <div class="hero-bg"></div>
        <div class="hero-content">
          <div class="hero-tag">MODULO EPHDEM</div>
          <h1 class="hero-title">Estudio de Preinversión Hospitalaria</h1>
          <p class="hero-sub">Herramienta para la estimación de equipamiento médico necesario para satisfacer una demanda proyectada de prestaciones.</p>
        </div>
      </section>

      <main class="proyectos-content" id="PROYECTOS">
        <header class="proyectos-header">
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
          <h2 class="section-title">Mis proyectos</h2>
          <p class="section-subtitle">Visualiza y edita los proyectos asociados a tu usuario.</p>
        </header>

        <section class="proyectos-panel">
          <div class="panel-header">
            <div>
              <h3 class="panel-title">Listado de proyectos</h3>
              <p class="panel-hint">Fuente: tabla proyectos</p>
            </div>
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
              <button class="btn-primary" @click="toggleNuevoMenu">Nuevo proyecto</button>
              <div v-if="mostrarMenuNuevo" class="nuevo-menu">
                <button class="nuevo-menu-item" @click="seleccionarTipoProyecto('Atencion abierta')">Atención abierta</button>
                <button class="nuevo-menu-item" @click="seleccionarTipoProyecto('Atencion cerrada')">Atención cerrada</button>
              </div>
            </div>
          </div>

          <div v-if="cargando" class="lista-vacia">
            <i class="fa-solid fa-spinner fa-spin"></i> Cargando proyectos...
          </div>
          <div v-else-if="errorCarga" class="lista-vacia lista-error">
            <i class="fa-solid fa-triangle-exclamation"></i> {{ errorCarga }}
          </div>
          <div v-else-if="proyectos.length === 0" class="lista-vacia">
            <p>Aún no hay proyectos creados.</p>
            <p>Haz clic en Nuevo proyecto para comenzar.</p>
          </div>
          <div v-else class="proyectos-table">
            <div class="table-row table-head">
              <div>Nombre del proyecto</div>
              <div>Fecha de creación</div>
              <div>Tipo de proyecto</div>
              <div class="table-actions">Acciones</div>
            </div>
            <div v-for="proyecto in proyectosOrdenados" :key="proyecto.id" class="table-row">
              <div class="table-name">{{ proyecto.nombre_proyecto }}</div>
              <div>{{ proyecto.fecha_creacion }}</div>
              <div class="table-chip">{{ proyecto.tipo_proyecto }}</div>
              <div class="table-actions">
                <button class="btn-primary" @click="verProyecto(proyecto)">Ver</button>
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
const mostrarMenuNuevo = ref(false)
const proyectos = ref([])
const cargando = ref(false)
const errorCarga = ref('')
const ordenSeleccionado = ref('fecha_desc')

const proyectosOrdenados = computed(() => {
  const lista = [...proyectos.value]
  return lista.sort((a, b) => {
    if (ordenSeleccionado.value === 'alfabetico_asc') return (a.nombre_proyecto || '').localeCompare(b.nombre_proyecto || '')
    if (ordenSeleccionado.value === 'alfabetico_desc') return (b.nombre_proyecto || '').localeCompare(a.nombre_proyecto || '')
    if (ordenSeleccionado.value === 'fecha_asc') return (a.id || a.id_proyecto || 0) - (b.id || b.id_proyecto || 0)
    return (b.id || b.id_proyecto || 0) - (a.id || a.id_proyecto || 0)
  })
})

onMounted(async () => { await cargarProyectos() })

async function cargarProyectos() {
  const userId = authStore.usuarioId
  if (!userId) return
  cargando.value = true
  errorCarga.value = ''
  try {
    const url = `${import.meta.env.VITE_API_BASE}/get/get_proyectos.php?usuario_id=${userId}`
    const response = await fetch(url, { method: 'GET', credentials: 'same-origin' })
    const result = await response.json()
    if (result.ok && Array.isArray(result.datos)) {
      proyectos.value = result.datos
    } else {
      errorCarga.value = result.error ?? 'No se pudieron cargar los proyectos.'
    }
  } catch (error) {
    errorCarga.value = 'Error de conexión al cargar proyectos.'
  } finally {
    cargando.value = false
  }
}

function toggleNuevoMenu() { mostrarMenuNuevo.value = !mostrarMenuNuevo.value }

function seleccionarTipoProyecto(tipo) {
  mostrarMenuNuevo.value = false
  if (tipo === 'Atencion cerrada') { router.push('/crear-proyecto'); return }
  if (tipo === 'Atencion abierta') { router.push('/crear-proyecto-abierta'); return }
}

function verProyecto(proyecto) {
  const id = proyecto.id || proyecto.id_proyecto
  localStorage.setItem('ephdem_proyecto_activo', id)
  localStorage.setItem('ephdem_nombre_proyecto_activo', proyecto.nombre_proyecto)
  router.push(`/resultados/${id}`)
}

function volverAtras() { router.back() }

function cerrarSesion() {
  authStore.logout()
  router.push('/login')
}
</script>

<style lang="scss" scoped>
@import '@/assets/styles/variables';

.hero {
  background: $color-secundario;
  position: relative; padding: 38px 48px; overflow: hidden; text-align: center;
}
.hero-compact { padding: 28px 48px; }
.hero-bg { position: absolute; inset: 0; background: url('@/assets/img/mac.jpg') center/cover no-repeat; }
.hero-content { position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; gap: 10px; }
.hero-tag { font-size: 12px; color: rgba(255,255,255,0.35); letter-spacing: 2.5px; text-transform: uppercase; }
.hero-title { font-size: 26px; font-weight: 500; color: #fff; margin: 0; }
.hero-sub { font-size: 14px; color: rgba(255,255,255,0.6); max-width: 700px; line-height: 1.5; margin: 0; }

.proyectos-page { background: $color-fondo; flex: 1; }
.proyectos-content { max-width: 1100px; margin: 32px auto 72px auto; padding: 0 24px; display: flex; flex-direction: column; gap: 24px; }
.proyectos-header { text-align: left; }

.nav-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
.nav-buttons { display: flex; gap: 10px; }
.session-badge {
  display: flex; align-items: center; gap: 8px; padding: 6px 14px 6px 10px;
  background: rgba(0,60,88,0.06); border: 1.5px solid rgba(0,60,88,0.18);
  border-radius: 999px; color: $color-primario; font-size: 0.88rem; font-weight: 600;
}
.session-nombre { white-space: nowrap; }
.btn-logout {
  background: none; border: none; color: $color-primario; cursor: pointer;
  padding: 2px 4px; font-size: 0.95rem; opacity: 0.7;
  &:hover { opacity: 1; color: #c62828; }
}
.btn-back {
  background: $color-primario; color: #fff; border: 1px solid $color-primario;
  border-radius: 999px; padding: 6px 12px; font-weight: 600; cursor: pointer;
  &:hover { background: mix(#fff, $color-primario, 6%); }
}
.section-title { font-size: 1.6rem; font-weight: 700; color: $color-primario; margin: 0 0 6px; }
.section-subtitle { margin: 0; color: $color-texto-secundario; }

.proyectos-panel {
  background: #fff; border-radius: 16px; padding: 24px;
  box-shadow: 0 10px 22px $color-sombra-suave; border: 1px solid $color-borde;
}
.panel-header { display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 18px; }
.panel-actions { position: relative; display: flex; align-items: center; gap: 16px; }
.sort-control { display: flex; align-items: center; gap: 8px; font-size: 0.9rem; color: $color-texto-secundario; }
.sort-select {
  padding: 6px 12px; border: 1px solid $color-borde; border-radius: 8px;
  font-size: 0.9rem; color: $color-texto-principal; background: #fff; cursor: pointer;
  &:focus { border-color: $color-primario; outline: none; }
}
.nuevo-menu {
  position: absolute; top: calc(100% + 10px); right: 0;
  display: flex; flex-direction: column; gap: 8px;
  background: #fff; border: 1px solid $color-borde; border-radius: 12px;
  padding: 10px; box-shadow: 0 10px 20px rgba(0,30,45,0.18); min-width: 200px; z-index: 5;
}
.nuevo-menu-item {
  text-align: left; border: 1px solid rgba(0,60,88,0.12); background: rgba(0,60,88,0.04);
  color: $color-primario; font-weight: 600; padding: 10px 12px; border-radius: 10px; cursor: pointer;
  &:hover { background: rgba(0,60,88,0.12); }
}
.panel-title { margin: 0; font-size: 1.2rem; color: $color-texto-principal; }
.panel-hint { margin: 4px 0 0; font-size: 0.9rem; color: $color-texto-secundario; }

.btn-primary {
  background: $color-primario; color: #fff; border: none; border-radius: 10px;
  padding: 10px 18px; font-weight: 600; cursor: pointer;
  &:hover { background: mix(#fff, $color-primario, 8%); }
}

.lista-vacia {
  background: $color-claro; padding: 32px; border-radius: 12px;
  text-align: center; color: $color-texto-secundario; border: 1px dashed $color-borde;
  display: flex; flex-direction: column; align-items: center; gap: 6px;
}
.lista-error { background: rgba(229,57,53,0.05); border-color: rgba(229,57,53,0.4); color: #c62828; flex-direction: row; }

.proyectos-table { display: flex; flex-direction: column; gap: 10px; }
.table-row {
  display: grid; grid-template-columns: 2.2fr 1fr 1fr 1.2fr;
  gap: 16px; align-items: center; padding: 14px 12px;
  border-radius: 12px; background: $color-claro; border: 1px solid $color-borde;
}
.table-head { background: #e9f1f6; font-weight: 600; color: $color-primario; }
.table-name { font-weight: 600; color: $color-texto-principal; }
.table-chip {
  display: inline-flex; align-items: center; justify-content: center;
  padding: 6px 12px; border-radius: 999px;
  background: rgba(0,60,88,0.08); color: $color-primario; font-weight: 600; font-size: 0.85rem;
}
.table-actions { display: flex; gap: 10px; justify-content: flex-end; }
</style>
