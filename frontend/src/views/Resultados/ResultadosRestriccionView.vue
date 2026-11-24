<template>
  <AppLayout>
    <div class="rr-page">
      <section class="hero hero-compact">
        <div class="hero-bg"></div>
        <div class="hero-content">
          <div class="hero-tag">MÓDULO EPHDEM</div>
          <h1 class="hero-title">Estudio de Preinversión Hospitalaria</h1>
          <p class="hero-sub">Equipamiento y personal con la restricción de recursos humanos.</p>
        </div>
      </section>

      <main class="rr-content">
        <PantallaEspera
          :visible="cargando"
          titulo="Calculando la restricción de personal"
          mensaje="Revisando la dotación de cada recinto…"
          icono="fa-solid fa-user-nurse"
        />
        <header class="rr-header">
          <div class="nav-bar">
            <div class="nav-buttons">
              <button class="btn-back" type="button" @click="router.back()"><i class="fa-solid fa-arrow-left"></i> Volver</button>
              <button class="btn-back" type="button" @click="router.push('/inicio')"><i class="fa-solid fa-house-user"></i> Inicio</button>
              <div class="nav-divider"></div>
              <button class="btn-back" type="button" @click="router.push(`/rrhh/${proyectoId}`)"><i class="fa-solid fa-user-nurse"></i> Editar personal</button>
              <button class="btn-back" type="button" @click="router.push(`/tiempos-prestacion/${proyectoId}`)"><i class="fa-solid fa-clock"></i> Editar tiempos</button>
              <button class="btn-back" type="button" @click="router.push(`/resultados/${proyectoId}`)"><i class="fa-solid fa-chart-bar"></i> Resultados sin restricción</button>
            </div>
            <div class="session-badge">
              <i class="fa-solid fa-circle-user"></i>
              <span class="session-nombre">{{ authStore.correoUsuario }}</span>
              <button class="btn-logout" type="button" @click="cerrarSesion" title="Cerrar sesión">
                <i class="fa-solid fa-right-from-bracket"></i>
              </button>
            </div>
          </div>
          <div class="title-actions-row">
            <div>
              <h2 class="section-title">Resultados con restricción de RRHH</h2>
              <p class="section-subtitle">Cuánto equipamiento se puede usar con el personal disponible y cuánto personal falta.</p>
            </div>
            <div class="header-actions">
              <button class="btn-export btn-export-excel" type="button" :disabled="!resultado" @click="exportar('xls')"><i class="fa-solid fa-file-excel"></i> Excel</button>
              <button class="btn-export btn-export-pdf" type="button" :disabled="!resultado" @click="exportar('pdf')"><i class="fa-solid fa-file-pdf"></i> PDF</button>
            </div>
          </div>
          <div class="proyecto-activo-badge">
            <span class="badge-label">Proyecto</span>
            <span class="badge-name">{{ nombreProyectoActivo }}</span>
          </div>
        </header>

        <div v-if="cargando" class="aviso-estado"><i class="fa-solid fa-spinner fa-spin"></i> Calculando…</div>
        <div v-else-if="error" class="aviso-estado aviso-error">{{ error }}</div>

        <section v-else-if="resultado" class="resultado">
          <!-- Sección 1: equipamiento -->
          <div class="res-seccion">
            <h4 class="res-seccion-titulo">Equipamiento
              <InfoTip titulo="Necesidad tecnológica → Con el personal actual">El primer número es lo que exige la demanda (el mismo de la pantalla Resultados). El segundo es lo que realmente se puede usar con el personal que ingresaste: si falta personal, sobran equipos.</InfoTip>
            </h4>
            <div class="pastillas">
              <div v-for="(t, i) in tarjetasEquipos" :key="t.titulo" class="pastilla">
                <span class="pastilla-titulo">{{ t.titulo }}<InfoTip :titulo="t.titulo" claro :izquierda="i === tarjetasEquipos.length - 1">{{ t.info }}</InfoTip></span>
                <div class="pastilla-valores">
                  <div class="pastilla-dato">
                    <span class="pastilla-numero">{{ t.normal }}</span>
                    <span class="pastilla-etiqueta">Necesidad tecnológica</span>
                  </div>
                  <span class="pastilla-flecha" aria-hidden="true">→</span>
                  <div class="pastilla-dato">
                    <span class="pastilla-numero" :class="{ 'numero-baja': t.restringido < t.normal }">{{ t.restringido }}</span>
                    <span class="pastilla-etiqueta">Con el personal actual</span>
                  </div>
                </div>
              </div>
            </div>
            <p class="resultado-nota"></p>
          </div>

          <!-- Sección 2: personal -->
          <div class="res-seccion">
            <h4 class="res-seccion-titulo">Personal</h4>
            <div class="pastillas">
              <div class="pastilla pastilla-clara pastilla-compacta">
                <span class="pastilla-titulo">Personal que falta<InfoTip titulo="Personal que falta">Cuántas personas habría que sumar, por perfil, para cubrir toda la demanda. Se calcula como el mínimo necesario (horas requeridas ÷ horas que aporta una persona al año) menos las personas que ya hay.</InfoTip></span>
                <div class="falta-lista">
                  <template v-if="faltantes.length">
                    <div v-for="f in faltantes" :key="f.clave" class="falta-linea">
                      <span class="falta-numero">{{ formatoNumero(f.cantidad) }}</span>
                      <span class="falta-perfil">{{ f.perfil }}</span>
                      <span class="falta-recinto">{{ f.recinto }}</span>
                    </div>
                  </template>
                  <div v-else class="falta-linea">
                    <span class="falta-numero falta-numero-ok">0</span>
                    <span class="falta-perfil">El personal alcanza para toda la demanda</span>
                  </div>
                </div>
              </div>
              <div class="pastilla pastilla-clara pastilla-compacta">
                <span class="pastilla-titulo">Prestaciones no cubiertas<InfoTip titulo="Prestaciones no cubiertas" izquierda>Atenciones al año que no se alcanzan a realizar con el personal actual: (1 − α) × demanda anual de cada recinto. En UCI y UTI suma días cama y otras prestaciones.</InfoTip></span>
                <div class="falta-linea falta-centro">
                  <span class="falta-numero falta-numero-grande" :class="{ 'falta-numero-ok': noCubiertas === 0 }">{{ formatoNumero(noCubiertas) }}</span>
                  <span class="falta-perfil">al año</span>
                </div>
              </div>
            </div>
            <div v-for="a in sinPersonal" :key="a.id" class="aviso-rrhh aviso-en-resultado">
              <i class="fa-solid fa-triangle-exclamation"></i>
              {{ a.texto }}
            </div>
          </div>

          <details class="detalle">
            <summary>Ver detalle por recinto y equipo</summary>
            <div class="detalle-contenido">
              <div class="detalle-acciones">
                <button type="button" class="btn-texto" @click="abrirTodos"><i class="fa-regular fa-square-plus"></i> Abrir todos</button>
                <button type="button" class="btn-texto" @click="cerrarTodos"><i class="fa-regular fa-square-minus"></i> Cerrar todos</button>
              </div>

              <!-- Un bloque por recinto -->
              <div v-for="r in resultado.recintos" :key="r.id" class="res-recinto">
                <div class="res-recinto-cabecera res-toggle" @click="alternar('r' + r.id)">
                  <span class="res-recinto-nombre">
                    <i class="fa-solid res-chevron" :class="estaAbierto('r' + r.id) ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    {{ r.nombre }}
                  </span>
                  <span class="chip-alpha-wrap" @click.stop>
                    <span class="chip-alpha" :class="claseAlpha(r)">{{ textoAlpha(r) }}</span>
                    <InfoTip titulo="α (factor alcanzable)" izquierda>Fracción de la demanda que el personal alcanza a cubrir en este recinto, entre 0 y 1. Se toma el perfil más escaso: si las enfermeras alcanzan para el 60 % y los TENS para el 80 %, α = 0,60.</InfoTip>
                  </span>
                </div>

                <div v-show="estaAbierto('r' + r.id)" class="res-recinto-cuerpo">
                  <p v-if="!r.con_datos_planilla_a" class="res-linea res-gris">
                    Sin tiempos cargados en este recinto: no se aplica restricción.
                  </p>
                  <template v-else>
                    <p class="res-linea">
                      <template v-if="r.alpha >= 1">El personal alcanza para toda la demanda.</template>
                      <template v-else>
                        El personal alcanza para el <b>{{ formatoPorcentaje(r.alpha) }}</b> de la demanda.
                        <span v-if="r.perfil_limitante">Limita: <b>{{ r.perfil_limitante }}</b>.</span>
                      </template>
                    </p>
                    <p v-if="r.prestaciones_no_cubiertas > 0.5" class="res-linea">
                      Prestaciones no cubiertas: <b>{{ formatoNumero(Math.round(r.prestaciones_no_cubiertas)) }}</b> al año.
                    </p>
                  </template>
                  <p v-if="r.modulos" class="res-linea">
                    {{ r.modulos.tipo === 'boxes' ? 'Boxes' : 'Pabellones' }}:
                    <b>{{ r.modulos.normal }}</b> sin restricción → <b>{{ r.modulos.restringido }}</b> con restricción
                  </p>

                  <div v-if="r.por_perfil.length" class="tabla-scroll">
                    <table class="tabla-res">
                      <thead>
                        <tr>
                          <th>Perfil</th>
                          <th>Horas requeridas al año</th>
                          <th>Horas disponibles al año</th>
                          <th>Suficiencia</th>
                          <th>Personas</th>
                          <th>Mínimo necesario</th>
                          <th>Faltan</th>
                        </tr>
                      </thead>
                      <tbody>
                        <tr v-for="p in r.por_perfil" :key="p.perfil" :class="{ 'fila-limita': p.perfil === r.perfil_limitante && r.alpha < 1 }">
                          <td>{{ p.perfil }}</td>
                          <td>{{ formatoNumero(Math.round(p.q)) }}</td>
                          <td>{{ formatoNumero(Math.round(p.a)) }}</td>
                          <td>{{ p.rho === null ? '—' : (p.rho >= 1 ? 'Alcanza' : formatoPorcentaje(p.rho)) }}</td>
                          <td>{{ formatoNumero(p.n) }}</td>
                          <td>{{ p.n_min === null ? '—' : formatoNumero(p.n_min) }}</td>
                          <td>{{ p.brecha === null ? '—' : formatoNumero(p.brecha) }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  <p v-if="r.por_perfil.length" class="res-leyenda">
                    Suficiencia = horas disponibles ÷ horas requeridas («Alcanza» si es 100 % o más).
                    Mínimo necesario = horas requeridas ÷ horas que aporta una persona al año, redondeado hacia arriba.
                  </p>
                </div>
              </div>

              <!-- Equipamiento completo: todos los tipos -->
              <div v-if="equipamiento.length" class="res-recinto">
                <div class="res-recinto-cabecera res-toggle" @click="alternar('equipos')">
                  <span class="res-recinto-nombre">
                    <i class="fa-solid res-chevron" :class="estaAbierto('equipos') ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    Equipamiento
                    <span class="res-cuenta">{{ equipamientoCambian.length }} {{ equipamientoCambian.length === 1 ? 'cambia' : 'cambian' }} de {{ equipamiento.length }}</span>
                  </span>
                  <label v-show="estaAbierto('equipos')" class="check-todos" @click.stop>
                    <input v-model="mostrarTodos" type="checkbox" /> Mostrar todos los equipos
                  </label>
                </div>
                <div v-show="estaAbierto('equipos')" class="res-recinto-cuerpo">
                  <p v-if="!mostrarTodos && equipamientoCambian.length === 0" class="res-linea res-gris">
                    Ningún equipo cambia con el personal actual.
                  </p>
                  <div v-if="equiposVisibles.length" class="tabla-scroll">
                    <table class="tabla-res">
                      <thead><tr><th>Equipo</th><th>Tipo</th><th>Sin restricción</th><th>Con restricción</th></tr></thead>
                      <tbody>
                        <tr v-for="e in equiposVisibles" :key="e.equipo_id" :class="{ 'fila-cambia': e.restringido !== e.normal }">
                          <td>{{ e.nombre_equipo }}</td>
                          <td>{{ e.tipo ? `Tipo ${e.tipo}` : '—' }}</td>
                          <td>{{ e.normal }}</td>
                          <td>{{ e.restringido }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  <p v-if="!mostrarTodos && equipamientoCambian.length > 0 && equipamiento.length > equipamientoCambian.length" class="res-linea res-gris">
                    {{ textoNoCambian(equipamiento.length - equipamientoCambian.length) }}
                  </p>
                </div>
              </div>

              <!-- URPA -->
              <div v-if="urpa && urpa.normal.pabellones > 0" class="res-recinto">
                <div class="res-recinto-cabecera res-toggle" @click="alternar('urpa')">
                  <span class="res-recinto-nombre">
                    <i class="fa-solid res-chevron" :class="estaAbierto('urpa') ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                    URPA (recuperación post-anestésica)
                  </span>
                </div>
                <div v-show="estaAbierto('urpa')" class="res-recinto-cuerpo">
                  <p class="res-linea">
                    Camillas: <b>{{ urpa.normal.camillas }}</b> sin restricción → <b>{{ urpa.restringido.camillas }}</b> con restricción ·
                    Salas: <b>{{ urpa.normal.salas }}</b> → <b>{{ urpa.restringido.salas }}</b>
                  </p>
                  <div v-if="urpaCambian.length" class="tabla-scroll">
                    <table class="tabla-res">
                      <thead><tr><th>Equipo</th><th>Tipo</th><th>Sin restricción</th><th>Con restricción</th></tr></thead>
                      <tbody>
                        <tr v-for="e in urpaCambian" :key="e.equipo_id" class="fila-cambia">
                          <td>{{ e.nombre_equipo }}</td>
                          <td>{{ e.tipo ? `Tipo ${e.tipo}` : '—' }}</td>
                          <td>{{ e.normal }}</td>
                          <td>{{ e.restringido }}</td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                  <p v-else class="res-linea res-gris">El equipamiento de la URPA no cambia.</p>
                </div>
              </div>
            </div>
          </details>
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
import InfoTip from '@/components/ui/InfoTip.vue'
import PantallaEspera from '@/components/ui/PantallaEspera.vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const API = import.meta.env.VITE_API_BASE

const nombreProyectoActivo = ref('')
const proyectoId = ref(null)
const resultado = ref(null)      // rrhh.php?action=calcular_restriccion
const cargando = ref(true)
const error = ref('')
const mostrarTodos = ref(false)  // tabla de equipamiento: todos o solo los que cambian
const cerrados = ref({})         // bloques del detalle contraídos (por defecto, todos abiertos)

/* ---------- Formato ---------- */

function formatoNumero(n) { return n === null || n === undefined ? '—' : Number(n).toLocaleString('es-CL') }
function formatoPorcentaje(x) {
  return `${(x * 100).toLocaleString('es-CL', { maximumFractionDigits: 0 })} %`
}
function textoAlpha(r) {
  if (!r.con_datos_planilla_a || r.alpha >= 1) return 'Sin restricción'
  return `α = ${r.alpha.toLocaleString('es-CL', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`
}
function claseAlpha(r) {
  if (!r.con_datos_planilla_a || r.alpha >= 1) return 'alpha-ok'
  return r.alpha <= 0 ? 'alpha-cero' : 'alpha-baja'
}

/* ---------- Resumen ---------- */

// Una línea por perfil que falta, con su recinto: "1 Anestesiólogo/a · Pabellón mayor"
const faltantes = computed(() => {
  if (!resultado.value) return []
  return resultado.value.recintos.flatMap(r => r.por_perfil
    .filter(p => p.brecha > 0)
    .map(p => ({ clave: `${r.id}-${p.perfil}`, cantidad: p.brecha, perfil: p.perfil, recinto: r.nombre })))
})
const tarjetasEquipos = computed(() => {
  if (!resultado.value) return []
  const t = resultado.value.totales
  const tarjetas = [
    { titulo: 'Recintos', normal: t.modulos_normal, restringido: t.modulos_restringido,
      info: 'Actualmente contamos con pabellones y cubículos UCI/UTI. Con restricción = techo(α × la fracción de módulos que pide la demanda) en cada recinto.' },
  ]
  if (t.equipos_normal !== undefined) {
    tarjetas.push({ titulo: 'Equipamiento Médico', normal: t.equipos_normal, restringido: t.equipos_restringido,
      info: 'Suma de todas las unidades de equipos (tipos 1 a 6). El kit, los equipos por módulo y la norma UPC se recalculan con los módulos restringidos; los de demanda, con α.' })
  } else {
    tarjetas.push({ titulo: 'Equipos por demanda', normal: t.tipo5_normal, restringido: t.tipo5_restringido,
      info: 'Equipos tipo 5, calculados por demanda.' })
  }
  const u = resultado.value.urpa
  if (u && u.normal.camillas > 0) {
    tarjetas.push({ titulo: 'Camillas URPA', normal: u.normal.camillas, restringido: u.restringido.camillas,
      info: 'Camillas de recuperación post-anestésica: 2 por pabellón, máximo 12 por sala.' })
  }
  return tarjetas
})
const noCubiertas = computed(() => (resultado.value
  ? Math.round(resultado.value.recintos.reduce((s, r) => s + (r.prestaciones_no_cubiertas || 0), 0))
  : 0))
const sinPersonal = computed(() => {
  if (!resultado.value) return []
  return resultado.value.recintos
    .filter(r => r.perfiles_sin_datos.length > 0)
    .map(r => ({
      id: r.id,
      texto: `${r.perfiles_sin_datos.join(', ')} participa en prestaciones de «${r.nombre}» pero no tiene personal en RRHH: ese recinto queda sin capacidad hasta que lo agregues.`,
    }))
})

/* ---------- Equipamiento completo y URPA ---------- */

const equipamiento = computed(() => (resultado.value && resultado.value.equipamiento) || [])
const equipamientoCambian = computed(() => equipamiento.value.filter(e => e.restringido !== e.normal))
const equiposVisibles = computed(() => (mostrarTodos.value ? equipamiento.value : equipamientoCambian.value))
const urpa = computed(() => (resultado.value && resultado.value.urpa) || null)
const urpaCambian = computed(() => (urpa.value ? urpa.value.equipos.filter(e => e.restringido !== e.normal) : []))
function textoNoCambian(n) {
  return n === 1 ? 'El otro equipo no cambia.' : `Los otros ${n} equipos no cambian.`
}

/* ---------- Abrir y cerrar bloques del detalle ---------- */

function estaAbierto(clave) { return !cerrados.value[clave] }
function alternar(clave) { cerrados.value[clave] = !cerrados.value[clave] }
function abrirTodos() { cerrados.value = {} }
function cerrarTodos() {
  const todos = { equipos: true, urpa: true }
  for (const r of (resultado.value ? resultado.value.recintos : [])) todos['r' + r.id] = true
  cerrados.value = todos
}

/* ---------- Carga y descargas ---------- */

async function calcularResultado() {
  const uid = authStore.usuarioId
  if (!proyectoId.value || !uid) { error.value = 'No hay un proyecto activo o una sesión iniciada.'; cargando.value = false; return }
  cargando.value = true
  error.value = ''
  try {
    const resp = await fetch(`${API}/rrhh.php?action=calcular_restriccion&proyecto_id=${Number(proyectoId.value)}&usuario_id=${uid}`, { cache: 'no-store' })
    const data = await resp.json()
    if (!resp.ok || !data.ok) { error.value = data.error || 'No se pudo calcular la restricción.'; return }
    resultado.value = data.datos
  } catch (e) {
    error.value = 'Error de conexión al calcular la restricción.'
  } finally {
    cargando.value = false
  }
}

// Abre la descarga en otra pestaña (mismo mecanismo que la pantalla Resultados)
function exportar(tipo) {
  const uid = authStore.usuarioId
  if (!proyectoId.value || !uid) { alert('No se pudo identificar el proyecto activo.'); return }
  const archivo = tipo === 'pdf' ? 'generar_pdf_restriccion.php' : 'generar_xls_restriccion.php'
  window.open(`${API}/generar/${archivo}?proyecto_id=${Number(proyectoId.value)}&usuario_id=${uid}`, '_blank')
}

function cerrarSesion() {
  authStore.logout()
  router.push('/login')
}

onMounted(() => {
  nombreProyectoActivo.value = localStorage.getItem('ephdem_nombre_proyecto_activo') || 'Desconocido'
  proyectoId.value = route.params.proyectoId || localStorage.getItem('ephdem_proyecto_activo')
  calcularResultado()
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

.nav-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 10px; }
.nav-buttons { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
.nav-divider { width: 1px; height: 24px; background-color: #cbd5e1; margin: 0 4px; }
.session-badge { display: flex; align-items: center; gap: 8px; padding: 6px 14px 6px 10px; background: rgba(0,60,88,0.06); border: 1.5px solid rgba(0,60,88,0.18); border-radius: 999px; color: $color-primario; font-size: 0.88rem; font-weight: 600; }
.session-nombre { white-space: nowrap; }
.btn-logout { background: none; border: none; color: $color-primario; cursor: pointer; padding: 2px 4px; opacity: 0.7; &:hover { opacity: 1; color: #c62828; } }
.btn-back { background: $color-primario; color: #fff; border: 1px solid $color-primario; border-radius: 999px; padding: 6px 12px; font-weight: 600; cursor: pointer; &:hover { background: mix(#fff, $color-primario, 6%); } }

.rr-page { background: $color-fondo; flex: 1; }
.rr-content { max-width: 1480px; margin: 32px auto 48px auto; padding: 0 20px; display: flex; flex-direction: column; gap: 24px; }
.rr-header { display: flex; flex-direction: column; gap: 10px; }

.section-title { font-size: 1.6rem; font-weight: 700; color: $color-primario; margin: 0 0 4px; }
.section-subtitle { margin: 0; color: $color-texto-secundario; }
.title-actions-row { display: flex; align-items: flex-end; justify-content: space-between; gap: 16px; flex-wrap: wrap; }
.header-actions { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
.btn-export { background: #fff; border: 1px solid $color-borde; border-radius: 12px; padding: 10px 14px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 6px 16px rgba(0,60,88,0.08); &:disabled { opacity: 0.5; cursor: not-allowed; } }
.btn-export-excel { color: #1e8e5a; border-color: rgba(30,142,90,0.35); }
.btn-export-pdf { color: #d5431c; }
.proyecto-activo-badge { display: inline-flex; align-items: center; align-self: flex-start; background: rgba(0,60,88,0.05); border-radius: 6px; padding: 6px 12px; border: 1px solid rgba(0,60,88,0.1); }
.badge-label { font-size: 0.75rem; font-weight: 600; text-transform: uppercase; color: rgba(0,60,88,0.6); margin-right: 8px; }
.badge-name { font-size: 0.95rem; font-weight: 700; color: $color-primario; }

.aviso-estado { background: #fff; border: 1px solid $color-borde; border-radius: 12px; padding: 24px; text-align: center; color: $color-texto-secundario; display: flex; align-items: center; justify-content: center; gap: 8px; }
.aviso-error { color: #b71c1c; border-color: #f5a3a3; background: #fdecea; }
.aviso-rrhh { padding: 10px 14px; border-radius: 8px; background: #fff7e6; border: 1px solid #f5c26b; color: #8a5300; font-size: 0.9rem; i { margin-right: 6px; } }
.aviso-en-resultado { margin: 0; }

/* Resultado */
.resultado { background: #fff; border-radius: 16px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; padding: 20px; display: flex; flex-direction: column; gap: 16px; }

.res-seccion { display: flex; flex-direction: column; gap: 10px; padding-top: 14px; border-top: 1px solid $color-borde; }
.res-seccion-titulo { margin: 0; font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: rgba(0,60,88,0.65); }
.pastillas { display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 16px; }
@media (max-width: 700px) { .pastillas { grid-template-columns: 1fr; } }
.pastilla { background: $color-primario; color: #fff; border-radius: 16px; padding: 18px 20px; display: flex; flex-direction: column; align-items: center; gap: 8px; text-align: center; }
.pastilla-clara { background: #eef5fa; color: $color-primario; border: 1px solid #d3e4f0; }
.pastilla-titulo { font-size: 1rem; font-weight: 700; }
.pastilla-valores { display: flex; align-items: center; justify-content: center; gap: 18px; }
.pastilla-dato { display: flex; flex-direction: column; align-items: center; gap: 2px; min-width: 110px; }
.pastilla-numero { font-size: 2.2rem; font-weight: 700; line-height: 1.1; font-variant-numeric: tabular-nums; }
.pastilla-etiqueta { font-size: 0.8rem; opacity: 0.85; }
.pastilla-flecha { font-size: 1.6rem; opacity: 0.7; }
.numero-baja { color: #ffc266; }
/* Tarjetas de personal: título arriba a la izquierda y contenido centrado */
.pastilla-compacta { align-items: flex-start; text-align: left; padding: 14px 18px; gap: 4px; }
.falta-linea { display: flex; align-items: baseline; flex-wrap: wrap; gap: 4px 8px; }
.falta-numero { font-size: 1.6rem; font-weight: 700; line-height: 1.2; color: #b86e00; font-variant-numeric: tabular-nums; }
.falta-numero-ok { color: #1e6b34; }
.falta-perfil { font-size: 0.95rem; font-weight: 700; color: $color-primario; }
.falta-recinto { font-size: 0.78rem; font-weight: 600; color: rgba(0,60,88,0.7); background: rgba(0,60,88,0.07); border-radius: 999px; padding: 2px 10px; }
.falta-lista { flex: 1; align-self: stretch; display: grid; justify-content: center; align-content: center; gap: 4px; }
.falta-centro { flex: 1; align-self: stretch; justify-content: center; align-items: center; }
.falta-numero-grande { font-size: 2.6rem; line-height: 1; }

/* Detalle por recinto y equipo */
.detalle { border-top: 1px solid $color-borde; padding-top: 12px; }
.detalle summary { cursor: pointer; font-weight: 700; color: $color-primario; font-size: 0.92rem; }
.detalle-contenido { display: flex; flex-direction: column; gap: 12px; margin-top: 12px; }
.detalle-acciones { display: flex; justify-content: flex-end; gap: 14px; }
.btn-texto { background: none; border: none; padding: 0; cursor: pointer; color: $color-primario; font-family: inherit; font-size: .85rem; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; &:hover { text-decoration: underline; } }

.res-recinto { border: 1px solid #e3ebf2; border-radius: 12px; padding: 14px 16px; display: flex; flex-direction: column; gap: 8px; }
.res-recinto-cabecera { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; }
.res-recinto-nombre { font-weight: 700; color: $color-primario; font-size: 1.02rem; }
.res-recinto-cuerpo { display: flex; flex-direction: column; gap: 8px; }
.res-toggle { cursor: pointer; user-select: none; border-radius: 8px; padding: 2px 4px; margin: -2px -4px; &:hover { background: #f3f8fb; } }
.res-chevron { font-size: .8rem; opacity: .6; margin-right: 6px; width: 12px; }
.res-cuenta { margin-left: 8px; font-size: .8rem; font-weight: 600; color: rgba(0,60,88,0.6); }
.res-linea { margin: 0; font-size: 0.92rem; color: $color-primario; }
.res-gris { color: $color-texto-secundario; }
.res-leyenda { margin: 0; font-size: 0.8rem; color: $color-texto-secundario; }

.chip-alpha-wrap { display: inline-flex; align-items: center; }
.chip-alpha { border-radius: 999px; padding: 4px 12px; font-size: 0.82rem; font-weight: 700; }
.alpha-ok { background: #e6f4ea; color: #1e6b34; }
.alpha-baja { background: #fff1db; color: #8a5300; }
.alpha-cero { background: #fdecea; color: #b71c1c; }

.tabla-scroll { overflow-x: auto; }
.tabla-res { width: 100%; min-width: 640px; border-collapse: collapse; font-size: 0.88rem; }
.tabla-res th { background: #ddeaf4; color: $color-primario; font-weight: 700; padding: 8px 10px; text-align: center; }
.tabla-res td { padding: 8px 10px; border-bottom: 1px solid $color-borde; text-align: center; font-variant-numeric: tabular-nums; color: $color-primario; }
.tabla-res td:first-child, .tabla-res th:first-child { text-align: left; }
.tabla-res .fila-limita td { background: #fff7e6; font-weight: 700; }
.tabla-res .fila-cambia td:last-child { color: #b86e00; font-weight: 700; }
.check-todos { display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; font-weight: 600; color: rgba(0,60,88,0.8); cursor: pointer; }
</style>