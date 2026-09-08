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
              <button class="btn-logout" type="button" @click="cerrarSesion">
                <i class="fa-solid fa-right-from-bracket"></i>
              </button>
            </div>
          </div>
          <h2 class="section-title">Glosario EPH</h2>
          <div class="filtro-buscar">
            <input v-model="busqueda" type="text" placeholder="Buscar término..." />
          </div>
        </header>

        <section class="terminos-panel">
        <div v-for="categoria in categorias" :key="categoria">
            <div class="categoria-titulo">{{ categoria }}</div>
            <div v-if="terminosPorCategoria(categoria).length === 0" class="lista-vacia">No se encontraron términos.</div>
            <div v-for="termino in terminosPorCategoria(categoria)" :key="termino.termino" class="termino-card">
            <div class="termino-titulo">{{ termino.termino }}</div>
            <div class="termino-definicion">{{ termino.definicion }}</div>
            </div>
        </div>
        <div v-if="terminosFiltrados.length === 0" class="lista-vacia">No se encontraron términos.</div>
        </section>
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
const categorias = ['General', 'Tipos de equipamiento', 'Atención cerrada', 'Atención abierta']


const terminos = [
  // GENERALES
  { categoria: 'General', termino: 'EPH', definicion: 'Estudio de Preinversión Hospitalaria. Desarrollo sistematizado y en profundidad de la detección y solución de las brechas y objetivos de gestión de un establecimiento de salud en su contexto de red, que define el tamaño nominal del proyecto y permite comparar alternativas de solución hasta seleccionar la más rentable técnica, económica y socialmente, para postulación a financiamiento.' },
  { categoria: 'General', termino: 'SIGEM-UV', definicion: 'Sistema de Información y Gestión de Equipos Médicos de la Universidad de Valparaíso, plataforma institucional en la cual se aloja el módulo EPHDEM.' },
  { categoria: 'General', termino: 'EPHDEM', definicion: 'Módulo de demanda de la plataforma SIGEM-UV destinado al cálculo del equipamiento médico requerido en un Estudio de Preinversión Hospitalaria (EPH), en función de la demanda de prestaciones y su relación con los equipos y recintos asociados.' },
  { categoria: 'General', termino: 'Arancel MAI', definicion: 'Arancel de la Modalidad de Atención Institucional de FONASA, que codifica y valoriza las prestaciones del sistema público de salud.' },
  { categoria: 'General', termino: 'Demanda', definicion: 'Número de prestaciones que una población determinada requiere, social y técnicamente, en un período establecido (generalmente considerado como 1 año en el marco de este proyecto).' },
  { categoria: 'General', termino: 'Demanda compartida', definicion: 'Equipamiento cuya demanda proviene de prestaciones de más de un recinto base y cuya cantidad entera, por tanto, no puede repartirse entre recintos.' },
  { categoria: 'General', termino: 'Disponibilidad', definicion: 'Proporción del tiempo en que una unidad de equipamiento o de recinto está efectivamente disponible para uso. Se expresa como decimal en el rango [0,1].' },
  { categoria: 'General', termino: 'EEMM', definicion: 'Equipos y equipamiento médico. Designa también la fórmula de estimación de su demanda empleada en este trabajo.' },
  { categoria: 'General', termino: 'Jornada efectiva', definicion: 'Horas diarias de funcionamiento efectivo consideradas para el cálculo de capacidad.' },
  { categoria: 'General', termino: 'Prestación', definicion: 'Cualquier atención, servicio o procedimiento médico que recibe una persona para cuidar su bienestar (como consultas, exámenes y cirugías). Está identificada por un código del arancel, con un tiempo de procedimiento y un recinto asociados.' },
  { categoria: 'General', termino: 'Producción Sanitaria', definicion: 'Número de prestaciones realizadas por un establecimiento asistencial en un período de tiempo determinado.' },
  { categoria: 'General', termino: 'Requerimiento de Equipamiento', definicion: 'Cantidad de unidades de un equipo determinado que resulta necesaria para satisfacer la demanda proyectada de una prestación.' },
  { categoria: 'General', termino: 'Tiempo de procedimiento (TP)', definicion: 'Duración en minutos de una prestación.' },

  // TIPOS DE EQUIPAMIENTO
  { categoria: 'Tipos de equipamiento', termino: 'Tipo 1', definicion: 'Un equipo por unidad o servicio hospitalario, independiente de la demanda.' },
  { categoria: 'Tipos de equipamiento', termino: 'Tipo 2', definicion: 'Un equipo por módulo de atención (box, cama, sala).' },
  { categoria: 'Tipos de equipamiento', termino: 'Tipo 3', definicion: 'Al menos 1 equipo por unidad o servicio hospitalario; cifras superiores dependen de la demanda.' },
  { categoria: 'Tipos de equipamiento', termino: 'Tipo 4', definicion: 'Al menos 1 equipo por módulo de atención; cifras superiores dependen de la demanda.' },
  { categoria: 'Tipos de equipamiento', termino: 'Tipo 5', definicion: 'La existencia depende de una evaluación de oferta/demanda. Para equipos de alto costo de adquisición, operación y mantención.' },
  { categoria: 'Tipos de equipamiento', termino: 'Tipo 6', definicion: 'Dimensionamiento según guía o norma técnica correspondiente.' },

  // ATENCIÓN CERRADA
  { categoria: 'Atención cerrada', termino: 'Atención Cerrada', definicion: 'Modalidad de atención de salud que requiere la hospitalización del paciente para recibir y administrar las prestaciones necesarias.' },
  { categoria: 'Atención cerrada', termino: 'Boxes / cubículos', definicion: 'Módulos de atención individuales de las Unidades de Paciente Crítico.' },
  { categoria: 'Atención cerrada', termino: 'Día Cama', definicion: 'Unidad de medida que corresponde a la disponibilidad y ocupación de una cama censable hospitalaria durante 24 horas para la atención de un paciente.' },
  { categoria: 'Atención cerrada', termino: 'Kit de Recinto', definicion: 'Conjunto de equipos y su cantidad base asociados por defecto a un recinto estándar que todo recinto de un tipo dado posee, con independencia de la demanda específica o del equipamiento que pueda requerir cada prestación particular.' },
  { categoria: 'Atención cerrada', termino: 'Pabellón mayor / menor', definicion: 'Recintos quirúrgicos diferenciados por complejidad del procedimiento y tipo de anestesia.' },
  { categoria: 'Atención cerrada', termino: 'Pabellón Quirúrgico', definicion: 'Recinto destinado a la realización de intervenciones quirúrgicas.' },
  { categoria: 'Atención cerrada', termino: 'Piso normativo', definicion: 'Cantidad mínima de un equipo garantizada por criterios de dotación fija, con independencia de la demanda calculada.' },
  { categoria: 'Atención cerrada', termino: 'Recinto base', definicion: 'Recinto donde se ejecuta una prestación y del cual esta hereda el kit de equipamiento correspondiente.' },
  { categoria: 'Atención cerrada', termino: 'Recinto Estándar', definicion: 'Catálogo de tipos de recintos hospitalarios considerados por el sistema (por ejemplo, cubículo UCI, cubículo UTI o pabellón), a los cuales se asocia un kit de equipamiento base.' },
  { categoria: 'Atención cerrada', termino: 'UCI / UTI', definicion: 'Unidad de Cuidados Intensivos y Unidad de Tratamiento Intermedio, respectivamente. Son los componentes que conforman la Unidad de Paciente Crítico.' },
  { categoria: 'Atención cerrada', termino: 'UPC', definicion: 'Unidad de Paciente Crítico. Unidad clínica destinada a la atención de pacientes en riesgo vital, que comprende las Unidades de Cuidados Intensivos (UCI) y de Tratamiento Intermedio (UTI).' },
  { categoria: 'Atención cerrada', termino: 'URPA', definicion: 'Unidad de Recuperación Post-Anestésica.' },

  // ATENCIÓN ABIERTA
  { categoria: 'Atención abierta', termino: 'Atención Abierta', definicion: 'Modalidad de atención de salud que no requiere la hospitalización del paciente para recibir y administrar las prestaciones necesarias.' },
  { categoria: 'Atención abierta', termino: 'Cartera de servicios', definicion: 'Definición del conjunto de acciones preventivas, curativas, de rehabilitación y cuidados paliativos que oferta un determinado establecimiento.' },
  { categoria: 'Atención abierta', termino: 'Consulta médica', definicion: 'Prestación ambulatoria donde un profesional de salud evalúa, diagnostica o trata a un paciente sin requerir hospitalización.' },
  { categoria: 'Atención abierta', termino: 'Días laborales', definicion: 'Número de días efectivos de funcionamiento del establecimiento en un año. Puede ser 260 días hábiles o 365 días corridos.' },
  { categoria: 'Atención abierta', termino: 'N° de prestaciones simultáneas', definicion: 'Cantidad de procedimientos de un mismo tipo que pueden realizarse en paralelo con el mismo equipo.' },
  { categoria: 'Atención abierta', termino: 'Tiempo de procedimiento', definicion: 'Duración estimada en minutos de una prestación ambulatoria, utilizada en la fórmula EEMM.' },
]
const terminosFiltrados = computed(() => {
  const texto = busqueda.value.toLowerCase().trim()
  if (!texto) return terminos
  return terminos.filter(t =>
    t.termino.toLowerCase().includes(texto) ||
    t.definicion.toLowerCase().includes(texto)
  )
})

function terminosPorCategoria(cat) {
  return terminosFiltrados.value.filter(t => t.categoria === cat)
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
.glosario-content { max-width: 900px; margin: 32px auto 72px auto; padding: 0 24px; display: flex; flex-direction: column; gap: 24px; }

.nav-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
.nav-buttons { display: flex; gap: 10px; }
.session-badge { display: flex; align-items: center; gap: 8px; padding: 6px 14px 6px 10px; background: rgba(0,60,88,0.06); border: 1.5px solid rgba(0,60,88,0.18); border-radius: 999px; color: $color-primario; font-size: 0.88rem; font-weight: 600; }
.session-nombre { white-space: nowrap; }
.btn-logout { background: none; border: none; color: $color-primario; cursor: pointer; padding: 2px 4px; opacity: 0.7; }
.btn-back { background: $color-primario; color: #fff; border: 1px solid $color-primario; border-radius: 999px; padding: 6px 12px; font-weight: 600; cursor: pointer; }
.section-title { font-size: 1.6rem; font-weight: 700; color: $color-primario; margin: 0 0 6px; }

.filtro-buscar input { width: 100%; padding: 10px 14px; border: 1px solid $color-borde; border-radius: 10px; font-size: 0.95rem; color: $color-texto-principal; }

.terminos-panel { display: flex; flex-direction: column; gap: 12px; }
.termino-card { background: #fff; border-radius: 12px; padding: 16px 20px; border: 1px solid $color-borde; box-shadow: 0 2px 8px $color-sombra-suave; }
.termino-titulo { font-size: 1.05rem; font-weight: 700; color: $color-primario; margin-bottom: 6px; }
.termino-definicion { font-size: 0.9rem; color: $color-texto-secundario; line-height: 1.6; }
.lista-vacia { color: $color-texto-secundario; padding: 20px; text-align: center; }

.categoria-titulo { font-size: 1.2rem; font-weight: 700; color: $color-primario; margin: 24px 0 12px; padding-bottom: 6px; border-bottom: 2px solid $color-primario; }
</style>