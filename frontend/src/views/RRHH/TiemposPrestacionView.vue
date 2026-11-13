<template>
  <AppLayout>
    <div class="tp-page">
      <section class="hero hero-compact">
        <div class="hero-bg"></div>
        <div class="hero-content">
          <div class="hero-tag">MÓDULO EPHDEM</div>
          <h1 class="hero-title">Estudio de Preinversión Hospitalaria</h1>
          <p class="hero-sub">Indica qué personal participa en cada prestación y cuánto tiempo le dedica.</p>
        </div>
      </section>

      <main class="tp-content">
        <header class="tp-header">
          <div class="nav-bar">
            <div class="nav-buttons">
              <button class="btn-back" type="button" @click="salir(`/rrhh/${proyectoId}`)"><i class="fa-solid fa-arrow-left"></i> Volver</button>
              <button class="btn-back" type="button" @click="salir('/inicio')"><i class="fa-solid fa-house-user"></i> Inicio</button>
            </div>
            <div class="session-badge">
              <i class="fa-solid fa-circle-user"></i>
              <span class="session-nombre">{{ authStore.correoUsuario }}</span>
              <button class="btn-logout" type="button" @click="cerrarSesion">
                <i class="fa-solid fa-right-from-bracket"></i>
              </button>
            </div>
          </div>
          <h2 class="section-title">Tiempos del personal por prestación</h2>
          <div class="proyecto-activo-badge">
            <span class="badge-label">Proyecto en edición</span>
            <span class="badge-name">{{ nombreProyectoActivo }}</span>
          </div>
          <div class="instruccion-indicator">
            <span class="instruccion-icon-circle"><i class="fa-solid fa-circle-info"></i></span>
            <span class="instruccion-texto">
              En cada prestación aprieta los perfiles que participan. <b>Permanente</b>: el perfil está durante
              toda la prestación. <b>Puntual</b>: interviene algunas veces; indica los minutos de cada intervención
              y cuántas veces al día. La dedicación (φ) no puede ser mayor que 1. Estos datos se comparten con
              los demás proyectos que tengan las mismas prestaciones.
            </span>
          </div>
        </header>

        <div v-if="cargando" class="aviso-estado">Cargando…</div>
        <div v-else-if="recintos.length === 0" class="aviso-estado">
          Este proyecto no tiene prestaciones seleccionadas. Elígelas primero en la pantalla de Prestaciones.
        </div>

        <section v-else class="tp-panel">
          <div v-for="recinto in recintos" :key="recinto.id" class="recinto-card">
            <div class="recinto-title recinto-title-toggle" @click="abiertos[recinto.id] = !abiertos[recinto.id]">
              <span><i class="fa-solid fa-hospital-user"></i> {{ recinto.nombre }}
                <span class="recinto-cuenta">{{ recinto.prestaciones.length }} {{ recinto.prestaciones.length === 1 ? 'prestación' : 'prestaciones' }}</span>
              </span>
              <i class="fa-solid" :class="abiertos[recinto.id] ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
            </div>

            <div v-show="abiertos[recinto.id]">
              <div v-if="recinto.perfilesRrhh.length === 0" class="aviso-rrhh">
                <i class="fa-solid fa-triangle-exclamation"></i>
                No ingresaste personal para este recinto en la pantalla de RRHH. Los perfiles que marques aquí
                se considerarán sin personal disponible.
              </div>

              <!-- Aplicar un perfil a todas las prestaciones del recinto -->
              <div class="masivo">
                <span class="masivo-titulo">Aplicar a todas las prestaciones de este recinto:</span>
                <div class="masivo-controles">
                  <select v-model="masivo[recinto.id].tipo" class="input-tp">
                    <option value="" disabled>Perfil</option>
                    <option v-for="nombre in perfilesOrdenados(recinto)" :key="nombre" :value="nombre">{{ nombre }}</option>
                  </select>
                  <div class="segmentado">
                    <button type="button" :class="{ activo: masivo[recinto.id].participacion === 'permanente' }"
                      @click="masivo[recinto.id].participacion = 'permanente'">Permanente</button>
                    <button type="button" :class="{ activo: masivo[recinto.id].participacion === 'puntual' }"
                      @click="masivo[recinto.id].participacion = 'puntual'">Puntual</button>
                  </div>
                  <template v-if="masivo[recinto.id].participacion === 'puntual'">
                    <label class="campo-corto">e <input v-model.number="masivo[recinto.id].e" type="number" min="0" step="1" class="input-tp input-num" /> min</label>
                    <label class="campo-corto">f <input v-model.number="masivo[recinto.id].f" type="number" min="0" step="1" class="input-tp input-num" /> veces/día</label>
                  </template>
                  <button type="button" class="btn-aplicar" @click="aplicarATodas(recinto)">Aplicar</button>
                  <button type="button" class="btn-quitar-todas" @click="quitarDeTodas(recinto)">Quitar de todas</button>
                </div>
              </div>

              <!-- Una tarjeta por prestación -->
              <div v-for="p in recinto.prestaciones" :key="p.id" class="prestacion">
                <div class="prestacion-cabecera">
                  <span class="prestacion-nombre">{{ p.nombre }}</span>
                  <span class="prestacion-datos">
                    <span v-if="p.codigo">Código {{ p.codigo }}</span>
                    <span>t = {{ formatoTiempo(p.tMin) }}</span>
                    <span>Demanda {{ formatoNumero(p.demanda) }}/año</span>
                  </span>
                </div>

                <div class="perfiles-chips">
                  <button
                    v-for="nombre in chipsDe(recinto, p)"
                    :key="nombre"
                    type="button"
                    class="chip-perfil"
                    :class="{ activo: filaDe(p, nombre), 'sin-rrhh': !enRrhh(recinto, nombre) }"
                    :title="enRrhh(recinto, nombre) ? '' : 'Este perfil no está en el personal de este recinto (pantalla RRHH)'"
                    :aria-pressed="!!filaDe(p, nombre)"
                    @click="togglePerfil(p, nombre)"
                  >{{ nombre }}</button>
                  <select v-if="otrosPerfiles(recinto, p).length" class="select-otro" value="" @change="agregarOtro(p, $event)">
                    <option value="" disabled>+ Otro perfil</option>
                    <option v-for="nombre in otrosPerfiles(recinto, p)" :key="nombre" :value="nombre">{{ nombre }}</option>
                  </select>
                </div>

                <div v-for="fila in p.perfiles" :key="fila.uid" class="fila-perfil">
                  <span class="fila-nombre">{{ fila.tipo }}</span>
                  <div class="segmentado">
                    <button type="button" :class="{ activo: fila.participacion === 'permanente' }"
                      @click="fila.participacion = 'permanente'">Permanente</button>
                    <button type="button" :class="{ activo: fila.participacion === 'puntual' }"
                      :disabled="!puedePuntual(p)"
                      :title="puedePuntual(p) ? '' : 'Esta prestación no tiene tiempo de procedimiento'"
                      @click="fila.participacion = 'puntual'">Puntual</button>
                  </div>
                  <template v-if="fila.participacion === 'puntual'">
                    <label class="campo-corto">e <input v-model.number="fila.e" type="number" min="0" step="1" class="input-tp input-num" /> min</label>
                    <label class="campo-corto">f <input v-model.number="fila.f" type="number" min="0" step="1" class="input-tp input-num" /> veces/día</label>
                  </template>
                  <span class="phi" :class="{ 'phi-error': fila.participacion === 'puntual' && !phiValido(phiDe(p, fila)) }"
                    title="Dedicación: fracción del tiempo de la prestación que ocupa este perfil">
                    φ = {{ formatoPhi(phiDe(p, fila)) }}
                  </span>
                  <button class="btn-quitar" type="button" title="Quitar este perfil" @click="togglePerfil(p, fila.tipo)">
                    <i class="fa-solid fa-trash"></i>
                  </button>
                </div>

                <div v-if="p.perfiles.length === 0" class="fila-vacia">Ningún perfil marcado.</div>
              </div>
            </div>
          </div>
        </section>

        <section v-if="resultado" class="resultado">
          <div class="resultado-cabecera">
            <h3 class="resultado-titulo">Resultado con restricción de RRHH</h3>
            <span v-if="haySinGuardar" class="sin-guardar">Hay cambios sin guardar: el resultado puede estar desactualizado</span>
          </div>
          <!-- Sección 1: equipamiento -->
          <div class="res-seccion">
            <h4 class="res-seccion-titulo">Equipamiento</h4>
            <div class="pastillas">
              <div v-for="t in tarjetasEquipos" :key="t.titulo" class="pastilla">
                <span class="pastilla-titulo">{{ t.titulo }}</span>
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
              <div class="pastilla pastilla-clara">
                <span class="pastilla-titulo">Personal que falta</span>
                <span class="pastilla-numero" :class="{ 'numero-alerta': totalFaltan > 0 }">{{ formatoNumero(totalFaltan) }}</span>
                <span class="pastilla-etiqueta">{{ totalFaltan === 1 ? 'persona' : 'personas' }} para cubrir toda la demanda</span>
                <ul v-if="brechas.length" class="pastilla-lista">
                  <li v-for="b in brechas" :key="b.id"><b>{{ b.nombre }}:</b> {{ b.texto }}</li>
                </ul>
              </div>
              <div class="pastilla pastilla-clara">
                <span class="pastilla-titulo">Prestaciones no cubiertas</span>
                <span class="pastilla-numero" :class="{ 'numero-alerta': noCubiertas > 0 }">{{ formatoNumero(noCubiertas) }}</span>
                <span class="pastilla-etiqueta">al año, mientras falte personal</span>
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
          <div v-for="r in resultado.recintos" :key="r.id" class="res-recinto">
            <div class="res-recinto-cabecera">
              <span class="res-recinto-nombre">{{ r.nombre }}</span>
              <span class="chip-alpha" :class="claseAlpha(r)">{{ textoAlpha(r) }}</span>
            </div>

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
          </div>

          <div v-if="tipo5Cambian.length" class="res-recinto">
            <div class="res-recinto-cabecera"><span class="res-recinto-nombre">Equipos por demanda que cambian</span></div>
            <div class="tabla-scroll">
              <table class="tabla-res">
                <thead><tr><th>Equipo</th><th>Sin restricción</th><th>Con restricción</th></tr></thead>
                <tbody>
                  <tr v-for="e in tipo5Cambian" :key="e.equipo_id">
                    <td>{{ e.nombre_equipo }}</td><td>{{ e.normal }}</td><td>{{ e.restringido }}</td>
                  </tr>
                </tbody>
              </table>
            </div>
            <p v-if="resultado.tipo5.length > tipo5Cambian.length" class="res-linea res-gris">
              {{ resultado.tipo5.length - tipo5Cambian.length === 1 ? 'El otro equipo tipo 5 no cambia.' : `Los otros ${resultado.tipo5.length - tipo5Cambian.length} equipos tipo 5 no cambian.` }}
            </p>
          </div>
            </div>
          </details>
        </section>

        <section class="acciones-finales">
          <button class="btn-secundario" type="button" @click="salir(`/rrhh/${proyectoId}`)">
            <i class="fa-solid fa-arrow-left"></i> Volver a RRHH
          </button>
          <div class="acciones-derecha">
            <span v-if="haySinGuardar" class="sin-guardar">Hay cambios sin guardar</span>
            <button class="btn-principal" type="button" :disabled="recintos.length === 0 || calculando" @click="guardar(true)">
              {{ calculando ? 'Calculando…' : 'Guardar y calcular' }} <i class="fa-solid fa-calculator"></i>
            </button>
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

const API = import.meta.env.VITE_API_BASE

const nombreProyectoActivo = ref('')
const proyectoId = ref(null)

const categorias = ref([])   // catálogo de perfiles: [{ id, nombre }]
const recintos = ref([])     // Planilla A del proyecto, agrupada por recinto
const abiertos = ref({})     // recinto abierto o colapsado
const masivo = ref({})       // valores de "Aplicar a todas", por recinto
const cargando = ref(true)
const guardadoComo = ref('') // lo último guardado, para saber si hay cambios
const resultado = ref(null)   // resultado de la restricción (rrhh.php?action=calcular_restriccion)
const calculando = ref(false)

let uidSeq = 1

/* ---------- Orden y perfiles ---------- */

function posicionPerfil(nombre) {
  const i = categorias.value.findIndex(c => c.nombre === nombre)
  return i === -1 ? categorias.value.length : i
}
function ordenarNombres(nombres) {
  return [...new Set(nombres)].sort((a, b) => posicionPerfil(a) - posicionPerfil(b))
}
function enRrhh(recinto, nombre) { return recinto.perfilesRrhh.includes(nombre) }
function filaDe(p, nombre) { return p.perfiles.find(f => f.tipo === nombre) || null }

// Botones de una prestación: los perfiles de RRHH del recinto y los que ya tiene marcados
function chipsDe(recinto, p) {
  return ordenarNombres([...recinto.perfilesRrhh, ...p.perfiles.map(f => f.tipo)])
}
// Perfiles del catálogo que no tienen botón en esa prestación
function otrosPerfiles(recinto, p) {
  const ya = new Set(chipsDe(recinto, p))
  return categorias.value.map(c => c.nombre).filter(n => !ya.has(n))
}
// Para "Aplicar a todas": primero los del recinto, después el resto del catálogo
function perfilesOrdenados(recinto) {
  const resto = categorias.value.map(c => c.nombre).filter(n => !recinto.perfilesRrhh.includes(n))
  return [...ordenarNombres(recinto.perfilesRrhh), ...resto]
}

function nuevaFila(nombre) {
  return { uid: uidSeq++, tipo: nombre, participacion: 'permanente', e: null, f: null }
}
function agregarFila(p, nombre) {
  const i = p.perfiles.findIndex(f => posicionPerfil(f.tipo) > posicionPerfil(nombre))
  const fila = nuevaFila(nombre)
  if (i === -1) p.perfiles.push(fila)
  else p.perfiles.splice(i, 0, fila)
  return fila
}
function togglePerfil(p, nombre) {
  const i = p.perfiles.findIndex(f => f.tipo === nombre)
  if (i === -1) agregarFila(p, nombre)
  else p.perfiles.splice(i, 1)
}
function agregarOtro(p, evento) {
  const nombre = evento.target.value
  evento.target.value = ''
  if (nombre && !filaDe(p, nombre)) agregarFila(p, nombre)
}

/* ---------- Dedicación φ (R3a a R3c del modelo) ---------- */

function puedePuntual(p) { return Number(p.tMin) > 0 }

// φ = τ / t, con τ = e · f · max(1, t / 24) (todo en horas). Permanente: φ = 1.
function phiDe(p, fila) {
  if (fila.participacion === 'permanente') return 1
  const tH = Number(p.tMin) / 60
  const e = Number(fila.e)
  const f = Number(fila.f)
  if (!(tH > 0) || !(e > 0) || !(f > 0)) return null
  const tau = (e / 60) * f * Math.max(1, tH / 24)
  return tau / tH
}
function phiValido(phi) { return phi !== null && phi > 0 && phi <= 1 + 1e-9 }

/* ---------- Aplicar a todas / quitar de todas ---------- */

function aplicarATodas(recinto) {
  const m = masivo.value[recinto.id]
  if (!m.tipo) { alert('Elige primero el perfil.'); return }
  if (m.participacion === 'puntual' && !(Number(m.e) > 0 && Number(m.f) > 0)) {
    alert('Para "Puntual" indica los minutos (e) y las veces al día (f).')
    return
  }
  let omitidas = 0
  for (const p of recinto.prestaciones) {
    if (m.participacion === 'puntual' && !puedePuntual(p)) { omitidas++; continue }
    const fila = filaDe(p, m.tipo) || agregarFila(p, m.tipo)
    fila.participacion = m.participacion
    fila.e = m.participacion === 'puntual' ? Number(m.e) : null
    fila.f = m.participacion === 'puntual' ? Number(m.f) : null
  }
  if (omitidas > 0) {
    alert(`Se omitieron ${omitidas} prestaciones sin tiempo de procedimiento: solo admiten "Permanente".`)
  }
}

function quitarDeTodas(recinto) {
  const m = masivo.value[recinto.id]
  if (!m.tipo) { alert('Elige primero el perfil.'); return }
  if (!window.confirm(`¿Quitar «${m.tipo}» de todas las prestaciones de «${recinto.nombre}»?`)) return
  for (const p of recinto.prestaciones) {
    const i = p.perfiles.findIndex(f => f.tipo === m.tipo)
    if (i !== -1) p.perfiles.splice(i, 1)
  }
}

/* ---------- Formato ---------- */

function formatoNumero(n) { return n === null || n === undefined ? '—' : Number(n).toLocaleString('es-CL') }
function formatoTiempo(tMin) {
  const t = Number(tMin)
  if (!(t > 0)) return 'sin tiempo'
  if (t < 60) return `${t.toLocaleString('es-CL')} min`
  return `${(t / 60).toLocaleString('es-CL', { maximumFractionDigits: 1 })} h`
}
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
// Resumen: personal que falta por recinto, prestaciones no cubiertas y perfiles sin personal
const brechas = computed(() => {
  if (!resultado.value) return []
  return resultado.value.recintos
    .map(r => ({
      id: r.id,
      nombre: r.nombre,
      texto: r.por_perfil.filter(p => p.brecha > 0).map(p => `${formatoNumero(p.brecha)} ${p.perfil}`).join(', '),
    }))
    .filter(b => b.texto !== '')
})
const tarjetasEquipos = computed(() => {
  if (!resultado.value) return []
  const t = resultado.value.totales
  return [
    { titulo: 'Pabellones y boxes', normal: t.modulos_normal, restringido: t.modulos_restringido },
    { titulo: 'Equipos por demanda', normal: t.tipo5_normal, restringido: t.tipo5_restringido },
  ]
})
const totalFaltan = computed(() => (resultado.value
  ? resultado.value.recintos.reduce((s, r) => s + r.por_perfil.reduce((a, p) => a + (p.brecha > 0 ? p.brecha : 0), 0), 0)
  : 0))
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
const tipo5Cambian = computed(() => (resultado.value ? resultado.value.tipo5.filter(e => e.restringido !== e.normal) : []))

function formatoPhi(phi) {
  return phi === null ? '—' : phi.toLocaleString('es-CL', { minimumFractionDigits: 2, maximumFractionDigits: 2 })
}

/* ---------- Guardar ---------- */

function filasParaGuardar() {
  const filas = []
  for (const r of recintos.value) {
    for (const p of r.prestaciones) {
      for (const f of p.perfiles) {
        filas.push({
          prestacion_id: p.id,
          tipo_rrhh: f.tipo,
          participacion: f.participacion,
          e_minutos: f.participacion === 'puntual' ? Number(f.e) || null : null,
          f_veces_dia: f.participacion === 'puntual' ? Number(f.f) || null : null,
        })
      }
    }
  }
  return filas
}

const haySinGuardar = computed(() => !cargando.value && JSON.stringify(filasParaGuardar()) !== guardadoComo.value)

function primerError() {
  for (const r of recintos.value) {
    for (const p of r.prestaciones) {
      for (const f of p.perfiles) {
        if (f.participacion !== 'puntual') continue
        if (!(Number(f.e) > 0) || !(Number(f.f) > 0)) {
          return `En «${p.nombre}», «${f.tipo}»: faltan los minutos (e) o las veces al día (f).`
        }
        const phi = phiDe(p, f)
        if (!phiValido(phi)) {
          return `En «${p.nombre}», «${f.tipo}»: la dedicación queda en ${formatoPhi(phi)} y no puede ser mayor que 1.`
        }
      }
    }
  }
  return null
}

let guardando = false
async function guardar(calcular = false) {
  const uid = authStore.usuarioId
  if (guardando) return
  if (!proyectoId.value || !uid) { alert('No hay un proyecto activo o una sesión iniciada.'); return }
  const error = primerError()
  if (error) { alert(error); return }

  const filas = filasParaGuardar()
  guardando = true
  try {
    const resp = await fetch(`${API}/rrhh.php?action=planilla_a_guardar`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ proyecto_id: Number(proyectoId.value), usuario_id: uid, filas }),
    })
    const data = await resp.json()
    if (!resp.ok || !data.ok) {
      alert(data.error || 'No se pudo guardar.')
      return
    }
    guardadoComo.value = JSON.stringify(filas)
    if (calcular) await calcularResultado()
    else alert('Tiempos por prestación guardados correctamente.')
  } catch (e) {
    alert('Error de conexión al guardar.')
  } finally {
    guardando = false
  }
}

/* ---------- Resultado con restricción ---------- */

async function calcularResultado() {
  const uid = authStore.usuarioId
  if (!proyectoId.value || !uid) return
  calculando.value = true
  try {
    const resp = await fetch(`${API}/rrhh.php?action=calcular_restriccion&proyecto_id=${Number(proyectoId.value)}&usuario_id=${uid}`)
    const data = await resp.json()
    if (!resp.ok || !data.ok) { alert(data.error || 'No se pudo calcular la restricción.'); return }
    resultado.value = data.datos
  } catch (e) {
    alert('Error de conexión al calcular la restricción.')
  } finally {
    calculando.value = false
  }
}

/* ---------- Carga ---------- */

async function cargarPerfiles() {
  const uid = authStore.usuarioId
  if (!uid) return
  try {
    const resp = await fetch(`${API}/rrhh.php?action=perfiles&usuario_id=${uid}`)
    const data = await resp.json()
    if (!resp.ok || !data.ok) { alert(data.error || 'No se pudo cargar la lista de perfiles.'); return }
    categorias.value = data.datos.perfiles
  } catch (e) {
    alert('Error de conexión al cargar la lista de perfiles.')
  }
}

async function cargarPlanilla() {
  const uid = authStore.usuarioId
  if (!proyectoId.value || !uid) return
  try {
    const resp = await fetch(`${API}/rrhh.php?action=planilla_a&proyecto_id=${Number(proyectoId.value)}&usuario_id=${uid}`)
    const data = await resp.json()
    if (!resp.ok || !data.ok) { alert(data.error || 'No se pudieron cargar las prestaciones.'); return }
    const lista = data.datos.recintos.map(r => ({
      id: r.id,
      nombre: r.nombre,
      perfilesRrhh: r.perfiles_rrhh,
      prestaciones: r.prestaciones.map(p => ({
        id: p.id,
        codigo: p.codigo,
        nombre: p.nombre,
        tMin: p.t_min,
        demanda: p.demanda,
        perfiles: [...p.perfiles]
          .sort((a, b) => posicionPerfil(a.tipo_rrhh) - posicionPerfil(b.tipo_rrhh))
          .map(f => ({ uid: uidSeq++, tipo: f.tipo_rrhh, participacion: f.participacion, e: f.e_minutos, f: f.f_veces_dia })),
      })),
    }))
    for (const r of lista) {
      abiertos.value[r.id] = true
      masivo.value[r.id] = { tipo: r.perfilesRrhh[0] || '', participacion: 'permanente', e: null, f: null }
    }
    recintos.value = lista
    guardadoComo.value = JSON.stringify(filasParaGuardar())
  } catch (e) {
    alert('Error de conexión al cargar las prestaciones.')
  }
}

/* ---------- Navegación ---------- */

function salir(destino) {
  if (haySinGuardar.value && !window.confirm('Hay cambios sin guardar. ¿Salir de todas formas?')) return
  router.push(destino)
}
function cerrarSesion() {
  if (haySinGuardar.value && !window.confirm('Hay cambios sin guardar. ¿Cerrar sesión de todas formas?')) return
  authStore.logout()
  router.push('/login')
}

onMounted(async () => {
  nombreProyectoActivo.value = localStorage.getItem('ephdem_nombre_proyecto_activo') || 'Desconocido'
  proyectoId.value = route.params.proyectoId || localStorage.getItem('ephdem_proyecto_activo')
  await cargarPerfiles()
  await cargarPlanilla()
  cargando.value = false
  // Si ya hay tiempos guardados, muestra el resultado de inmediato
  if (filasParaGuardar().length > 0) await calcularResultado()
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

.tp-page { background: $color-fondo; flex: 1; }
.tp-content { max-width: 1480px; margin: 32px auto 48px auto; padding: 0 20px; display: flex; flex-direction: column; gap: 24px; }

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

.aviso-estado { background: #fff; border: 1px solid $color-borde; border-radius: 12px; padding: 24px; text-align: center; color: $color-texto-secundario; }

.tp-panel { display: flex; flex-direction: column; gap: 20px; }

/* Tarjeta de recinto */
.recinto-card { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; }
.recinto-title { display: flex; align-items: center; gap: 10px; background: #fff; padding: 14px 20px; font-size: 1.1rem; font-weight: 700; color: $color-primario; border-bottom: 1px solid #e5e7eb; }
.recinto-title-toggle { justify-content: space-between; cursor: pointer; user-select: none; &:hover { background: #e4eff6; } }
.recinto-cuenta { font-size: 0.8rem; font-weight: 600; color: rgba(0,60,88,0.6); margin-left: 8px; }

.aviso-rrhh { margin: 12px 20px 0; padding: 10px 14px; border-radius: 8px; background: #fff7e6; border: 1px solid #f5c26b; color: #8a5300; font-size: 0.9rem; i { margin-right: 6px; } }

/* Aplicar a todas */
.masivo { margin: 12px 20px; padding: 12px 14px; border-radius: 10px; background: #eef5fa; border: 1px solid #d3e4f0; display: flex; flex-direction: column; gap: 8px; }
.masivo-titulo { font-size: 0.85rem; font-weight: 700; color: $color-primario; }
.masivo-controles { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }

/* Prestación */
.prestacion { margin: 0 20px 14px; padding: 12px 14px; border: 1px solid #e3ebf2; border-radius: 10px; background: #fbfdfe; }
.prestacion-cabecera { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 6px 16px; margin-bottom: 8px; }
.prestacion-nombre { font-weight: 700; color: $color-primario; }
.prestacion-datos { display: flex; flex-wrap: wrap; gap: 12px; font-size: 0.82rem; color: rgba(0,60,88,0.7); }

/* Botones de perfiles */
.perfiles-chips { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 8px; }
.chip-perfil {
  background: #fff; color: $color-primario; border: 1.5px solid #7fc8e8; border-radius: 999px;
  padding: 5px 13px; font-size: 0.85rem; font-weight: 600; cursor: pointer;
  &:hover { background: #e4eff6; }
  &.activo { background: $color-primario; color: #fff; border-color: $color-primario; }
  &.sin-rrhh { border-style: dashed; border-color: #f0a830; }
  &.sin-rrhh.activo { background: #b86e00; border-color: #b86e00; }
  &:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(42,159,214,0.3); }
}
.select-otro { border: 1.5px dashed #7fc8e8; border-radius: 999px; padding: 5px 10px; font-size: 0.85rem; color: rgba(0,60,88,0.75); background: #fff; cursor: pointer; }

/* Fila de un perfil marcado */
.fila-perfil { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 14px; padding: 8px 0; border-top: 1px dashed #e3ebf2; }
.fila-nombre { min-width: 180px; font-weight: 600; color: $color-primario; }
.fila-vacia { font-size: 0.85rem; color: $color-texto-secundario; }

.segmentado {
  display: inline-flex; border: 1.5px solid #7fc8e8; border-radius: 8px; overflow: hidden;
  button { background: #fff; color: $color-primario; border: none; padding: 6px 12px; font-size: 0.85rem; font-weight: 600; cursor: pointer; }
  button + button { border-left: 1.5px solid #7fc8e8; }
  button.activo { background: $color-primario; color: #fff; }
  button:disabled { color: #b0bec5; cursor: not-allowed; }
}

.campo-corto { display: inline-flex; align-items: center; gap: 6px; font-size: 0.85rem; color: rgba(0,60,88,0.8); }
.input-tp { padding: 6px 10px; border: 1.5px solid #7fc8e8; border-radius: 8px; font-size: 0.9rem; background: #fff; &:focus { outline: none; border-color: #2a9fd6; box-shadow: 0 0 0 3px rgba(42,159,214,0.15); } }
.input-num { width: 76px; text-align: center; }

.phi { font-variant-numeric: tabular-nums; font-weight: 700; color: $color-primario; background: #ddeaf4; border: 1.5px solid #b8d0ef; border-radius: 8px; padding: 5px 10px; font-size: 0.85rem; }
.phi-error { color: #b71c1c; background: #fdecea; border-color: #f5a3a3; }

.btn-aplicar { background: $color-primario; color: #fff; border: none; border-radius: 8px; padding: 7px 14px; font-weight: 700; font-size: 0.85rem; cursor: pointer; &:hover { opacity: 0.9; } }
.btn-quitar-todas { background: transparent; color: rgba(0,60,88,0.75); border: 1px solid rgba(0,60,88,0.2); border-radius: 8px; padding: 7px 12px; font-size: 0.82rem; cursor: pointer; &:hover { color: #c62828; border-color: #e7a1a1; } }
.btn-quitar { margin-left: auto; background: transparent; border: none; color: #9ca3af; padding: 6px 10px; border-radius: 8px; cursor: pointer; &:hover { color: #c62828; background: rgba(198,40,40,0.08); } }

/* Resultado */
.resultado { background: #fff; border-radius: 16px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; padding: 20px; display: flex; flex-direction: column; gap: 16px; }
.resultado-cabecera { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 8px; }
.resultado-titulo { margin: 0; font-size: 1.2rem; font-weight: 700; color: $color-primario; }
.resultado-nota { margin: 0; font-size: 0.88rem; color: $color-texto-secundario; }

.res-seccion { display: flex; flex-direction: column; gap: 10px; padding-top: 14px; border-top: 1px solid $color-borde; }
.res-seccion-titulo { margin: 0; font-size: 0.82rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.6px; color: rgba(0,60,88,0.65); }
.pastillas { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
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
.numero-alerta { color: #b86e00; }
.pastilla-lista { margin: 4px 0 0; padding: 0; list-style: none; font-size: 0.88rem; li { margin: 2px 0; } }
.detalle { border-top: 1px solid $color-borde; padding-top: 12px; }
.detalle summary { cursor: pointer; font-weight: 700; color: $color-primario; font-size: 0.92rem; }
.detalle-contenido { display: flex; flex-direction: column; gap: 12px; margin-top: 12px; }

.res-recinto { border: 1px solid #e3ebf2; border-radius: 12px; padding: 14px 16px; display: flex; flex-direction: column; gap: 8px; }
.res-recinto-cabecera { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; }
.res-recinto-nombre { font-weight: 700; color: $color-primario; font-size: 1.02rem; }
.res-linea { margin: 0; font-size: 0.92rem; color: $color-primario; }
.res-gris { color: $color-texto-secundario; }
.chip-alpha { border-radius: 999px; padding: 4px 12px; font-size: 0.82rem; font-weight: 700; }
.alpha-ok { background: #e6f4ea; color: #1e6b34; }
.alpha-baja { background: #fff1db; color: #8a5300; }
.alpha-cero { background: #fdecea; color: #b71c1c; }
.aviso-en-resultado { margin: 0; }

.tabla-scroll { overflow-x: auto; }
.tabla-res { width: 100%; min-width: 640px; border-collapse: collapse; font-size: 0.88rem; }
.tabla-res th { background: #ddeaf4; color: $color-primario; font-weight: 700; padding: 8px 10px; text-align: center; }
.tabla-res td { padding: 8px 10px; border-bottom: 1px solid $color-borde; text-align: center; font-variant-numeric: tabular-nums; color: $color-primario; }
.tabla-res td:first-child, .tabla-res th:first-child { text-align: left; }
.tabla-res .fila-limita td { background: #fff7e6; font-weight: 700; }

.acciones-finales { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; background: #fff; border-radius: 14px; padding: 16px 20px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; }
.acciones-derecha { display: flex; align-items: center; gap: 12px; }
.sin-guardar { font-size: 0.85rem; color: #b86e00; font-weight: 600; }
.btn-principal { background: $color-primario; color: #fff; border: none; border-radius: 10px; padding: 12px 20px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; &:hover { opacity: 0.9; } &:disabled { opacity: 0.5; cursor: not-allowed; } }
.btn-secundario { background: rgba(0,60,88,0.08); color: $color-primario; border: 1px solid rgba(0,60,88,0.2); border-radius: 10px; padding: 12px 20px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; &:hover { background: rgba(0,60,88,0.14); } }
</style>