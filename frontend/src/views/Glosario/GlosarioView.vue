<template>
  <AppLayout>
    <div class="glosario-page">
      <section class="hero hero-compact">
        <div class="hero-bg"></div>
        <div class="hero-content">
          <div class="hero-tag">MÓDULO EPHDEM</div>
          <h1 class="hero-title">Glosario EPH</h1>
          <p class="hero-sub">Términos y definiciones clave del Estudio de Preinversión Hospitalaria.</p>
        </div>
      </section>

      <main class="glosario-content">
        <header class="glosario-header">
          <div class="nav-bar">
            <div class="nav-buttons">
              <button class="btn-back" type="button" @click="router.back()"><i class="fa-solid fa-arrow-left"></i> Volver</button>
              <button class="btn-back" type="button" @click="router.push('/inicio')"><i class="fa-solid fa-house-user"></i> Inicio</button>
            </div>
            <div class="session-badge">
              <i class="fa-solid fa-circle-user"></i>
              <span class="session-nombre">{{ authStore.correoUsuario }}</span>
              <button class="btn-logout" type="button" title="Cerrar sesión" @click="cerrarSesion">
                <i class="fa-solid fa-right-from-bracket"></i>
              </button>
            </div>
          </div>

          <h2 class="section-title">Glosario EPH</h2>

          <div class="herramientas">
            <div class="filtro-buscar">
              <i class="fa-solid fa-magnifying-glass"></i>
              <input v-model="busqueda" type="text" placeholder="Buscar término o definición..." />
              <button v-if="busqueda" class="btn-limpiar" type="button" title="Borrar búsqueda" @click="busqueda = ''">
                <i class="fa-solid fa-xmark"></i>
              </button>
            </div>
            <div class="chips-categoria">
              <button type="button" class="chip" :class="{ activo: categoriaActiva === '' }" @click="categoriaActiva = ''">Todas</button>
              <button
                v-for="categoria in categorias"
                :key="categoria.nombre"
                type="button"
                class="chip"
                :class="{ activo: categoriaActiva === categoria.nombre }"
                @click="categoriaActiva = categoria.nombre"
              >
                <i :class="categoria.icono"></i> {{ categoria.nombre }}
              </button>
            </div>
            <div class="barra-acciones">
              <span class="contador">{{ terminosFiltrados.length }} {{ terminosFiltrados.length === 1 ? 'término' : 'términos' }}</span>
              <div class="acciones">
                <button type="button" class="btn-texto" @click="abrirTodos">
                  <i class="fa-regular fa-square-plus"></i> Abrir todos
                </button>
                <button type="button" class="btn-texto" @click="cerrarTodos">
                  <i class="fa-regular fa-square-minus"></i> Cerrar todos
                </button>
              </div>
            </div>
          </div>
        </header>

        <section v-for="categoria in categoriasVisibles" :key="categoria.nombre" class="categoria-section">
          <h3 class="categoria-titulo">
            <i :class="categoria.icono"></i>
            <span>{{ categoria.nombre }}</span>
            <span class="categoria-cuenta">{{ terminosPorCategoria(categoria.nombre).length }}</span>
          </h3>

          <div class="terminos-lista">
            <article
              v-for="termino in terminosPorCategoria(categoria.nombre)"
              :key="termino.termino"
              class="termino-card"
              :class="{ abierto: estaAbierto(termino) }"
            >
              <button
                type="button"
                class="termino-cabecera"
                :aria-expanded="estaAbierto(termino)"
                @click="alternar(termino)"
              >
                <i class="termino-toggle" :class="estaAbierto(termino) ? 'fa-regular fa-square-minus' : 'fa-regular fa-square-plus'"></i>
                <span class="termino-titulo">{{ termino.termino }}</span>
              </button>

              <div v-show="estaAbierto(termino)" class="termino-cuerpo">
                <p class="termino-definicion">{{ termino.definicion }}</p>
              </div>
            </article>
          </div>
        </section>

        <div v-if="terminosFiltrados.length === 0" class="lista-vacia">
          <i class="fa-regular fa-face-frown"></i>
          No se encontraron términos para «{{ busqueda }}».
        </div>
      </main>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppLayout from '@/layouts/AppLayout.vue'

const router = useRouter()
const authStore = useAuthStore()

const busqueda = ref('')
const categoriaActiva = ref('')   // '' = todas
const abiertos = ref({})          // términos abiertos, por nombre

const categorias = [
  { nombre: 'General', icono: 'fa-solid fa-book' },
  { nombre: 'Tipología de clases de equipos médicos', icono: 'fa-solid fa-layer-group' },
  { nombre: 'Atención cerrada', icono: 'fa-solid fa-hospital' },
  { nombre: 'Atención abierta', icono: 'fa-solid fa-user-doctor' },
]

const terminos = [
// GENERALES
  { categoria: 'General', termino: 'EPH', definicion: 'Estudio de Preinversión Hospitalaria. Análisis técnico, económico y social que identifica las brechas de un establecimiento de salud, define el tamaño del proyecto y selecciona la mejor alternativa para postular a financiamiento.' },
  { categoria: 'General', termino: 'SIGEM-UV', definicion: 'Sistema de Información y Gestión de Equipos Médicos de la Universidad de Valparaíso; plataforma institucional que aloja el módulo EPHDEM.' },
  { categoria: 'General', termino: 'EPHDEM', definicion: 'Módulo de la plataforma SIGEM-UV que calcula el equipamiento médico necesario para un EPH, cruzando la demanda de prestaciones con los equipos y recintos asociados.' },
  { categoria: 'General', termino: 'Arancel MAI', definicion: 'Arancel de la Modalidad de Atención Institucional de FONASA que codifica y valoriza las prestaciones de la red pública de salud.' },
  { categoria: 'General', termino: 'Demanda', definicion: 'Cantidad de prestaciones médicas que requiere una población en un período determinado (por lo general, un año).' },
  { categoria: 'General', termino: 'Demanda compartida', definicion: 'Equipamiento utilizado por prestaciones de dos o más recintos base, por lo que su cantidad total no puede dividirse individualmente por recinto.' },
  { categoria: 'General', termino: 'Disponibilidad', definicion: 'Proporción de tiempo en que un equipo o recinto está operativo para su uso, expresada como un decimal entre 0 y 1.' },
  { categoria: 'General', termino: 'EEMM', definicion: 'Siglas correspondientes a Equipamiento Médico / Equipos Médicos' },
  { categoria: 'General', termino: 'Jornada efectiva', definicion: 'Horas diarias reales de funcionamiento usadas para calcular la capacidad operativa.' },
  { categoria: 'General', termino: 'Prestación', definicion: 'Atención, examen o procedimiento médico (como consultas o cirugías) identificado por un código de arancel, con un tiempo de duración y un recinto asociados.' },
  { categoria: 'General', termino: 'Producción Sanitaria', definicion: 'Cantidad total de prestaciones realizadas por un establecimiento de salud en un período determinado.' },
  { categoria: 'General', termino: 'Requerimiento de Equipamiento', definicion: 'Cantidad de unidades de un equipo necesarias para cubrir la demanda proyectada de una prestación.' },
  { categoria: 'General', termino: 'Tiempo de procedimiento', definicion: 'Duración en minutos de una prestación.' },

  // TIPOS DE EQUIPAMIENTO
  { categoria: 'Tipología de clases de equipos médicos', termino: 'Tipología', definicion: 'Clasificación de equipamiento médico según su relación con la demanda, en base a esta clasificación se estructura el modelo matemático del módulo' },
  { categoria: 'Tipología de clases de equipos médicos', termino: 'Tipo 1', definicion: 'Un equipo por unidad o servicio hospitalario' },
  { categoria: 'Tipología de clases de equipos médicos', termino: 'Tipo 2', definicion: 'Un equipo por módulo de atención (box, cubículo, cama, puesto o sala de operaciones).' },
  { categoria: 'Tipología de clases de equipos médicos', termino: 'Tipo 3', definicion: 'Al menos 1 equipo por unidad o servicio hospitalario; cifras superiores dependen de la demanda.' },
  { categoria: 'Tipología de clases de equipos médicos', termino: 'Tipo 4', definicion: 'Al menos 1 equipo por módulo de atención; cifras superiores dependen de la demanda.' },
  { categoria: 'Tipología de clases de equipos médicos', termino: 'Tipo 5', definicion: 'La existencia depende de una evaluación de oferta/demanda. Para equipos de alto costo de adquisición, operación y mantención.' },
  { categoria: 'Tipología de clases de equipos médicos', termino: 'Tipo 6', definicion: 'Dimensionamiento según guía o norma correspondiente.' },

  // ATENCIÓN CERRADA
  { categoria: 'Atención cerrada', termino: 'Atención Cerrada', definicion: 'Modalidad de atención de salud que requiere la hospitalización del paciente para recibir y administrar las prestaciones necesarias.' },
  { categoria: 'Atención cerrada', termino: 'UPC', definicion: 'Unidad de Paciente Crítico. Unidad clínica destinada a la atención de pacientes en riesgo vital, que comprende las Unidades de Cuidados Intensivos (UCI) y de Tratamiento Intermedio (UTI).' },
  { categoria: 'Atención cerrada', termino: 'Boxes / cubículos', definicion: 'Módulos de atención individuales de las Unidades de Paciente Crítico (UPC).' },
  { categoria: 'Atención cerrada', termino: 'UCI / UTI', definicion: 'Unidad de Cuidados Intensivos y Unidad de Tratamiento Intermedio, respectivamente. Son los componentes que conforman la Unidad de Paciente Crítico (UPC).' },
  { categoria: 'Atención cerrada', termino: 'URPA', definicion: 'Unidad de Recuperación Post-Anestésica.' },
  { categoria: 'Atención cerrada', termino: 'Día Cama', definicion: 'Unidad de medida que corresponde a la disponibilidad y ocupación de una cama censable hospitalaria durante 24 horas para la atención de un paciente.' },
  { categoria: 'Atención cerrada', termino: 'Kit de Recinto', definicion: 'Conjunto de equipos y su cantidad base asociados por defecto a un recinto estándar que todo recinto de un tipo dado posee, con independencia de la demanda específica o del equipamiento que pueda requerir cada prestación particular.' },
  { categoria: 'Atención cerrada', termino: 'Pabellón mayor / menor', definicion: 'Recintos quirúrgicos diferenciados por complejidad del procedimiento y tipo de anestesia.' },
  { categoria: 'Atención cerrada', termino: 'Pabellón Quirúrgico', definicion: 'Recinto destinado a la realización de intervenciones quirúrgicas.' },
  { categoria: 'Atención cerrada', termino: 'Piso normativo', definicion: 'Cantidad mínima de un equipo garantizada por criterios de dotación fija, con independencia de la demanda calculada.' },
  { categoria: 'Atención cerrada', termino: 'Recinto base', definicion: 'Recinto donde se ejecuta una prestación y del cual esta hereda el kit de equipamiento correspondiente.' },
  { categoria: 'Atención cerrada', termino: 'Recinto Estándar', definicion: 'Catálogo de tipos de recintos hospitalarios considerados por el sistema (por ejemplo, cubículo UCI, cubículo UTI o pabellón), a los cuales se asocia un kit de equipamiento base.' },


  // ATENCIÓN ABIERTA
  { categoria: 'Atención abierta', termino: 'Atención Abierta', definicion: 'Modalidad de atención de salud que no requiere la hospitalización del paciente para recibir y administrar las prestaciones necesarias.' },
  { categoria: 'Atención abierta', termino: 'Cartera de servicios', definicion: 'Definición del conjunto de acciones preventivas, curativas, de rehabilitación y cuidados paliativos que oferta un determinado establecimiento.' },
  { categoria: 'Atención abierta', termino: 'Consulta médica', definicion: 'Prestación ambulatoria donde un profesional de salud evalúa, diagnostica o trata a un paciente sin requerir hospitalización.' },
  { categoria: 'Atención abierta', termino: 'Días laborales', definicion: 'Número de días efectivos de funcionamiento del establecimiento en un año. Puede ser 260 días hábiles o 365 días corridos.' },
  { categoria: 'Atención abierta', termino: 'N° de prestaciones simultáneas', definicion: 'Cantidad de procedimientos de un mismo tipo que pueden realizarse en paralelo con el mismo equipo.' },
]

function normalizar(texto) {
  return texto.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '')
}

// Filtra por búsqueda (sin importar tildes) y por categoría; ordena alfabéticamente
const terminosFiltrados = computed(() => {
  const texto = normalizar(busqueda.value.trim())
  return terminos
    .filter(t => !categoriaActiva.value || t.categoria === categoriaActiva.value)
    .filter(t => !texto || normalizar(t.termino).includes(texto) || normalizar(t.definicion).includes(texto))
    .sort((a, b) => a.termino.localeCompare(b.termino, 'es'))
})

function terminosPorCategoria(cat) {
  return terminosFiltrados.value.filter(t => t.categoria === cat)
}

const categoriasVisibles = computed(() =>
  categorias.filter(c => terminosPorCategoria(c.nombre).length > 0)
)

// Mientras se busca, los resultados se muestran abiertos
function estaAbierto(t) {
  return busqueda.value.trim() !== '' || !!abiertos.value[t.termino]
}
function alternar(t) {
  if (busqueda.value.trim() !== '') return
  abiertos.value[t.termino] = !abiertos.value[t.termino]
}
function abrirTodos() {
  terminosFiltrados.value.forEach(t => { abiertos.value[t.termino] = true })
}
function cerrarTodos() {
  abiertos.value = {}
  busqueda.value = ''
}

function cerrarSesion() {
  authStore.logout()
  router.push('/login')
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

.glosario-page { background: $color-fondo; flex: 1; }
.glosario-content { max-width: 960px; margin: 32px auto 72px auto; padding: 0 24px; display: flex; flex-direction: column; gap: 28px; }

.nav-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
.nav-buttons { display: flex; gap: 10px; }
.session-badge { display: flex; align-items: center; gap: 8px; padding: 6px 14px 6px 10px; background: rgba(0,60,88,0.06); border: 1.5px solid rgba(0,60,88,0.18); border-radius: 999px; color: $color-primario; font-size: 0.88rem; font-weight: 600; }
.session-nombre { white-space: nowrap; }
.btn-logout { background: none; border: none; color: $color-primario; cursor: pointer; padding: 2px 4px; opacity: 0.7; &:hover { opacity: 1; color: #c62828; } }
.btn-back { background: $color-primario; color: #fff; border: 1px solid $color-primario; border-radius: 999px; padding: 6px 12px; font-weight: 600; cursor: pointer; }
.section-title { font-size: 1.6rem; font-weight: 700; color: $color-primario; margin: 0 0 14px; }

/* Búsqueda y filtros */
.herramientas { display: flex; flex-direction: column; gap: 14px; }
.filtro-buscar {
  position: relative; display: flex; align-items: center;
  > i { position: absolute; left: 14px; color: $color-texto-secundario; font-size: .9rem; }
  input { width: 100%; box-sizing: border-box; padding: 12px 40px 12px 40px; background: #fff; border: 1px solid $color-borde; border-radius: 12px; font-size: 0.95rem; color: $color-texto-principal;
    &:focus { outline: none; border-color: $color-primario; box-shadow: 0 0 0 3px rgba(0,60,88,0.12); } }
}
.btn-limpiar { position: absolute; right: 8px; background: none; border: none; color: $color-texto-secundario; cursor: pointer; padding: 6px; &:hover { color: $color-primario; } }
.chips-categoria { display: flex; flex-wrap: wrap; gap: 8px; }
.chip {
  display: inline-flex; align-items: center; gap: 6px; padding: 6px 14px; border-radius: 999px; cursor: pointer;
  background: #fff; border: 1px solid $color-borde; color: $color-primario; font-size: .85rem; font-weight: 600;
  &:hover { background: rgba(0,60,88,0.06); }
  &.activo { background: $color-primario; border-color: $color-primario; color: #fff; }
}
.barra-acciones { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.contador { font-size: .85rem; color: $color-texto-secundario; }
.acciones { display: flex; gap: 14px; }
.btn-texto { background: none; border: none; padding: 0; cursor: pointer; color: $color-primario; font-size: .85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; &:hover { text-decoration: underline; } }

/* Categorías */
.categoria-section { display: flex; flex-direction: column; gap: 12px; }
.categoria-titulo {
  display: flex; align-items: center; gap: 10px; margin: 0; padding-bottom: 8px;
  font-size: 1.1rem; font-weight: 700; color: $color-primario; border-bottom: 2px solid $color-borde;
}
.categoria-cuenta { margin-left: auto; background: rgba(0,60,88,0.08); color: $color-primario; border-radius: 999px; padding: 2px 10px; font-size: .78rem; font-weight: 700; }

/* Tarjetas de términos */
.terminos-lista { display: flex; flex-direction: column; gap: 12px; }
.termino-card {
  background: #fff; border-radius: 12px; border: 1px solid transparent;
  box-shadow: 0 2px 8px $color-sombra-suave; overflow: hidden; transition: border-color .15s, box-shadow .15s;
  &:hover { box-shadow: 0 4px 14px $color-sombra-suave; }
  &.abierto { border-color: $color-borde; }
}
.termino-cabecera {
  width: 100%; display: flex; align-items: center; gap: 16px; padding: 16px 20px;
  background: none; border: none; cursor: pointer; text-align: left; font: inherit;
  &:focus-visible { outline: none; box-shadow: inset 0 0 0 3px rgba(42,159,214,0.3); }
}
.termino-toggle { color: $color-primario; font-size: 1.05rem; opacity: .75; flex: 0 0 auto; }
.termino-titulo { font-size: 1.05rem; font-weight: 700; color: $color-texto-principal; }
.termino-card.abierto .termino-titulo { color: $color-primario; }
.termino-cuerpo { padding: 0 20px 18px 56px; }
.termino-definicion { margin: 0; font-size: .92rem; color: $color-texto-secundario; line-height: 1.65; }

.lista-vacia { display: flex; align-items: center; justify-content: center; gap: 10px; color: $color-texto-secundario; padding: 28px 20px; background: #fff; border-radius: 12px; box-shadow: 0 2px 8px $color-sombra-suave; }
</style>