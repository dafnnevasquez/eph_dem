<template>
  <div class="page-layout">
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
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSIGEMUV" aria-controls="navbarSIGEMUV" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarSIGEMUV">
        <ul class="navbar-nav ms-auto mb-2 mb-md-0">
          <li class="nav-item">
            <a class="nav-link d-flex align-items-center" href="https://sigem-uv.cl/__v2/#MODULOS" target="_blank"><i class="fa fa-th me-2"></i>Módulos</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="https://sigem-uv.cl/__v2/#PROYECTOS" target="_blank">Proyectos</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="https://sigem-uv.cl/__v2/nosotros.php" target="_blank">Nosotros</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="#footer">Contacto</a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="./admin_sigem/sigem_login.php?m=CPANEL&link=../endesarrollo.php"><i class="fa fa-cog"></i></a>
          </li>
        </ul>
      </div>
    </div>
  </nav>

  <div class="proyectos-page">
    <!-- HERO COMPACTO -->
    <section class="hero hero-compact">
      <div class="hero-bg"></div>
      <div class="hero-content">
        <div class="hero-tag">MODULO EPHDEM</div>
        <h1 class="hero-title">Estudio de Preinversion Hospitalaria</h1>
        <p class="hero-sub">Herramienta para la estimacion de equipamiento medico necesario para satisfacer una demanda proyectada de prestaciones.</p>
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
              <button class="nuevo-menu-item" @click="seleccionarTipoProyecto('Atencion abierta')">Atencion abierta</button>
              <button class="nuevo-menu-item" @click="seleccionarTipoProyecto('Atencion cerrada')">Atencion cerrada</button>
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
            <div>Fecha de creacion</div>
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

  <!-- BOTTOM BAR: Informacion de contacto -->
  <footer class="sigem-bottomline" id="footer">
    <div class="sigem-bottomline-content">
      <div class="sigem-bottomline-left">
        <img src="https://sigem-uv.cl/_general/logos/LOGO_SIGEM-UV_HORIZONTAL-BLANCO.png" alt="SIGEM-UV" height="48" />
      </div>
      <div class="sigem-bottomline-center">
        <div>Gral. Cruz 222, Valparaiso ::: +56 32 2603662</div>
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
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

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
    if (ordenSeleccionado.value === 'alfabetico_asc') {
      return (a.nombre_proyecto || '').localeCompare(b.nombre_proyecto || '')
    } else if (ordenSeleccionado.value === 'alfabetico_desc') {
      return (b.nombre_proyecto || '').localeCompare(a.nombre_proyecto || '')
    } else if (ordenSeleccionado.value === 'fecha_asc') {
      return (a.id || a.id_proyecto || 0) - (b.id || b.id_proyecto || 0)
    } else {
      return (b.id || b.id_proyecto || 0) - (a.id || a.id_proyecto || 0)
    }
  })
})

onMounted(async () => {
  await cargarProyectos()
})

async function cargarProyectos() {
  const userId = authStore.usuarioId
  if (!userId) return

  cargando.value = true
  errorCarga.value = ''
  try {
    const url = `${import.meta.env.VITE_API_BASE}/get_proyectos.php?usuario_id=${userId}`
    const response = await fetch(url, { method: 'GET', credentials: 'same-origin' })
    const result = await response.json()

    if (result.ok && Array.isArray(result.datos)) {
      proyectos.value = result.datos
    } else {
      errorCarga.value = result.error ?? 'No se pudieron cargar los proyectos.'
    }
  } catch (error) {
    console.error('Error al cargar proyectos:', error)
    errorCarga.value = 'Error de conexión al cargar proyectos.'
  } finally {
    cargando.value = false
  }
}

function toggleNuevoMenu() {
  mostrarMenuNuevo.value = !mostrarMenuNuevo.value
}

function seleccionarTipoProyecto(tipo) {
  mostrarMenuNuevo.value = false
  if (tipo === 'Atencion cerrada') {
    router.push('/crear-proyecto')
    return
  }
  alert(`Nuevo proyecto: ${tipo}`)
}

function verProyecto(proyecto) {
  const id = proyecto.id || proyecto.id_proyecto
  localStorage.setItem('ephdem_proyecto_activo', id)
  localStorage.setItem('ephdem_nombre_proyecto_activo', proyecto.nombre_proyecto)
  router.push(`/resultados/${id}`)
}

function editarProyecto(proyecto) {
  alert(`Editando: ${proyecto.nombre_proyecto}`)
}

function volverAtras() {
  router.back()
}

function cerrarSesion() {
  authStore.logout()
  router.push('/login')
}
</script>


<style lang="scss" scoped>
@import '@/assets/styles/variables';

// --- SIGEM-UV TOPBAR 1 ---
.sigem-topbar1 {
  width: 100%;
  background: #003c58;
  display: flex;
  align-items: center;
  justify-content: center;
  position: relative;
  padding: 10px 0 10px 0;
  min-height: 56px;
  z-index: 1001;
}
.sigem-topbar1-center {
  display: flex;
  align-items: center;
  gap: 32px;
  position: absolute;
  left: 50%;
  transform: translateX(-50%);
}
.sigem-topbar1-logo {
  opacity: 0.8;
  transition: opacity 0.2s;
  &:hover { opacity: 1; }
}
.sigem-topbar1-acceder {
  color: #fff;
  font-weight: 500;
  text-decoration: none;
  font-size: 1.08rem;
  margin-right: 32px;
  cursor: pointer;
  position: absolute;
  right: 0;
  background: none !important;
  border: none !important;
  padding: 0 !important;
  border-radius: 0 !important;
  transition: none !important;
}

// --- SIGEM-UV TOPBAR 2 ---
.sigem-topbar2 {
  position: sticky;
  top: 0;
  width: 100%;
  background: $color-blanco;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
  z-index: 1000;
}

// --- HERO ---
.hero {
  background: $color-secundario;
  position: relative;
  padding: 38px 48px;
  overflow: hidden;
  text-align: center;
}
.hero-compact {
  padding: 28px 48px;
}
.hero-bg {
  position: absolute;
  inset: 0;
  background: url('@/assets/img/mac.jpg') center/cover no-repeat;
  opacity: 1;
  filter: none;
}
.hero-content {
  position: relative;
  z-index: 2;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 10px;
}
.hero-tag {
  font-size: 12px;
  color: rgba($color-blanco, 0.35);
  letter-spacing: 2.5px;
  text-transform: uppercase;
}
.hero-title {
  font-size: 26px;
  font-weight: 500;
  color: $color-blanco;
  line-height: 1.25;
  margin: 0;
}
.hero-sub {
  font-size: 14px;
  color: rgba($color-blanco, 0.6);
  max-width: 700px;
  line-height: 1.5;
  margin: 0;
}

// --- CONTENIDO ---
.page-layout {
  min-height: 100vh;
  flex: 1;
  display: flex;
  flex-direction: column;
}
.proyectos-page {
  background: $color-fondo;
  flex: 1;
}
.proyectos-content {
  max-width: 1100px;
  margin: 32px auto 72px auto;
  padding: 0 24px;
  display: flex;
  flex-direction: column;
  gap: 24px;
}
.proyectos-header {
  text-align: left;
}
.nav-bar {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  margin-bottom: 10px;
}
.nav-buttons {
  display: flex;
  gap: 10px;
  align-self: flex-start;
  margin-bottom: 0;
}
.session-badge {
  display: flex;
  align-items: center;
  gap: 8px;
  padding: 6px 14px 6px 10px;
  background: rgba(0, 60, 88, 0.06);
  border: 1.5px solid rgba(0, 60, 88, 0.18);
  border-radius: 999px;
  color: $color-primario;
  font-size: 0.88rem;
  font-weight: 600;
  i { font-size: 1rem; }
}
.session-nombre {
  white-space: nowrap;
}
.btn-logout {
  background: none;
  border: none;
  color: $color-primario;
  cursor: pointer;
  padding: 2px 4px;
  font-size: 0.95rem;
  opacity: 0.7;
  transition: opacity 0.2s, color 0.2s;
  &:hover { opacity: 1; color: #c62828; }
}
.btn-back {
  align-self: flex-start;
  background: $color-primario;
  color: $color-blanco;
  border: 1px solid $color-primario;
  border-radius: 999px;
  padding: 6px 12px;
  font-weight: 600;
  cursor: pointer;
  margin-bottom: 10px;
  transition: background 0.2s ease, border 0.2s ease;

  &:hover {
    background: mix($color-blanco, $color-primario, 6%);
    border-color: mix($color-blanco, $color-primario, 6%);
  }
}
.section-title {
  font-size: 1.6rem;
  font-weight: 700;
  color: $color-primario;
  margin: 0 0 6px 0;
}
.section-subtitle {
  margin: 0;
  color: $color-texto-secundario;
}

.proyectos-panel {
  background: $color-blanco;
  border-radius: 16px;
  padding: 24px;
  box-shadow: 0 10px 22px $color-sombra-suave;
  border: 1px solid $color-borde;
}
.panel-header {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 16px;
  margin-bottom: 18px;
}
  .panel-actions {
    position: relative;
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 16px;
  }
  .sort-control {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.9rem;
    color: $color-texto-secundario;
  }
  .sort-select {
    padding: 6px 12px;
    border: 1px solid $color-borde;
    border-radius: 8px;
    font-family: 'Inter', sans-serif;
    font-size: 0.9rem;
    color: $color-texto-principal;
    background-color: $color-blanco;
    cursor: pointer;
    outline: none;
    transition: border-color 0.2s;
    &:focus {
      border-color: $color-primario;
    }
  }
  .nuevo-menu {
  position: absolute;
  top: calc(100% + 10px);
  right: 0;
  display: flex;
  flex-direction: column;
  gap: 8px;
  background: $color-blanco;
  border: 1px solid $color-borde;
  border-radius: 12px;
  padding: 10px;
  box-shadow: 0 10px 20px rgba(0, 30, 45, 0.18);
  min-width: 200px;
  z-index: 5;
}
.nuevo-menu-item {
  text-align: left;
  border: 1px solid rgba(0, 60, 88, 0.12);
  background: rgba(0, 60, 88, 0.04);
  color: $color-primario;
  font-weight: 600;
  padding: 10px 12px;
  border-radius: 10px;
  cursor: pointer;
  transition: background 0.2s ease, border 0.2s ease;

  &:hover {
    background: rgba(0, 60, 88, 0.12);
    border-color: rgba(0, 60, 88, 0.3);
  }
}
.panel-title {
  margin: 0;
  font-size: 1.2rem;
  color: $color-texto-principal;
}
.panel-hint {
  margin: 4px 0 0 0;
  font-size: 0.9rem;
  color: $color-texto-secundario;
}

.btn-primary {
  background: $color-primario;
  color: $color-blanco;
  border: none;
  border-radius: 10px;
  padding: 10px 18px;
  font-weight: 600;
  cursor: pointer;
  transition: background 0.2s ease;

  &:hover {
    background: mix($color-blanco, $color-primario, 8%);
  }
}
.btn-secondary {
  background: $color-secundario;
  color: $color-blanco;
  border: none;
  border-radius: 8px;
  padding: 8px 14px;
  font-weight: 600;
  cursor: pointer;
}
.btn-outline {
  background: none;
  color: $color-primario;
  border: 1px solid $color-primario;
  border-radius: 8px;
  padding: 8px 14px;
  font-weight: 600;
  cursor: pointer;
}

.lista-vacia {
  background: $color-claro;
  padding: 32px;
  border-radius: 12px;
  text-align: center;
  color: $color-texto-secundario;
  border: 1px dashed $color-borde;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 6px;
}

.lista-error {
  background: rgba(229, 57, 53, 0.05);
  border: 1px dashed rgba(229, 57, 53, 0.4);
  color: #c62828;
  flex-direction: row;
  gap: 10px;
}


.proyectos-table {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.table-row {
  display: grid;
  grid-template-columns: 2.2fr 1fr 1fr 1.2fr;
  gap: 16px;
  align-items: center;
  padding: 14px 12px;
  border-radius: 12px;
  background: $color-claro;
  border: 1px solid $color-borde;
}
.table-head {
  background: #e9f1f6;
  font-weight: 600;
  color: $color-primario;
}
.table-name {
  font-weight: 600;
  color: $color-texto-principal;
}
.table-chip {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  padding: 6px 12px;
  border-radius: 999px;
  background: rgba(0, 60, 88, 0.08);
  color: $color-primario;
  font-weight: 600;
  font-size: 0.85rem;
}
.table-actions {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
}

// --- SIGEM-UV BOTTOM BAR ---
.sigem-bottomline {
  width: 100%;
  background: #003c58;
  color: #fff;
  padding: 0;
  margin-top: 48px;
}
.sigem-bottomline-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  max-width: 1400px;
  margin: 0 auto;
  padding: 12px 24px;
  flex-wrap: wrap;
}
.sigem-bottomline-left,
.sigem-bottomline-right {
  flex: 0 0 auto;
  display: flex;
  align-items: center;
}
.sigem-bottomline-center {
  flex: 1 1 400px;
  text-align: center;
  font-size: 1.05rem;
}
.sigem-bottomline-social {
  margin: 8px 0;
  display: flex;
  gap: 10px;
  justify-content: center;
}
.sigem-bottomline-copy {
  font-size: 0.95rem;
  opacity: 0.8;
  margin-top: 4px;
}
</style>
