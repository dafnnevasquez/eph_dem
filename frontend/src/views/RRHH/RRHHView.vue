<template>
  <AppLayout>
    <div class="rrhh-page">
      <section class="hero hero-compact">
        <div class="hero-bg"></div>
        <div class="hero-content">
          <div class="hero-tag">MÓDULO EPHDEM</div>
          <h1 class="hero-title">Estudio de Preinversión Hospitalaria</h1>
          <p class="hero-sub">Ingresa la dotación de recursos humanos disponibles por recinto.</p>
        </div>
      </section>

      <main class="rrhh-content">
        <header class="rrhh-header">
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
          <h2 class="section-title">Dotación de Recursos Humanos</h2>
          <div class="proyecto-activo-badge">
            <span class="badge-label">Proyecto en edición</span>
            <span class="badge-name">{{ nombreProyectoActivo }}</span>
          </div>
          <div class="instruccion-indicator">
            <span class="instruccion-icon-circle"><i class="fa-solid fa-circle-info"></i></span>
            <span class="instruccion-texto">
              En cada recinto, aprieta los perfiles que trabajan ahí y completa cuántas personas hay,
              su jornada semanal, las semanas efectivas al año y qué porcentaje de su jornada dedican
              a este equipo. Usa "Aplicar a todo" para poner el mismo valor en todas las filas.
              Si el perfil que necesitas no está, aprieta "+ Perfil nuevo".
            </span>
          </div>
        </header>

        <section class="rrhh-panel">
          <template v-for="recinto in recintos" :key="recinto.id">
            <div class="recinto-card">
              <div class="recinto-title recinto-title-toggle" @click="toggleRecinto(recinto.id)">
                <span><i class="fa-solid fa-hospital-user"></i> {{ recinto.nombre }}</span>
                <i class="fa-solid" :class="abiertos[recinto.id] ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
              </div>

              <div v-show="abiertos[recinto.id]">
                <!-- Perfiles: un clic agrega la fila, otro clic la quita -->
                <div class="perfiles-chips">
                  <button
                    v-for="c in categorias"
                    :key="c.id"
                    type="button"
                    class="chip-perfil"
                    :class="{ activo: tieneGrupo(recinto.id, c.nombre) }"
                    :aria-pressed="tieneGrupo(recinto.id, c.nombre)"
                    @click="togglePerfil(recinto.id, c.nombre)"
                  >{{ c.nombre }}</button>
                  <button type="button" class="chip-perfil chip-nuevo" @click="agregarPerfilNuevo(recinto.id)">
                    <i class="fa-solid fa-plus"></i> Perfil nuevo
                  </button>
                </div>

                <div class="tabla-scroll">
                  <table class="tabla-rrhh">
                    <thead>
                      <tr>
                        <th>Perfil</th>
                        <th>Cantidad de personas</th>
                        <th>Jornada semanal (hrs)</th>
                        <th>Semanas efectivas al año</th>
                        <th>% de jornada para este equipo</th>
                        <th>Horas disponibles al año</th>
                        <th></th>
                      </tr>
                      <tr v-if="dotacion[recinto.id].length > 0" class="fila-aplicar">
                        <th class="aplicar-etiqueta">Mismo valor para todos</th>
                        <th>
                          <div class="aplicar-celda">
                            <input v-model.number="masivo[recinto.id].personas" type="number" min="0" step="1" class="input-dotacion input-mini" placeholder="0" />
                            <button type="button" class="btn-aplicar" @click="aplicarATodos(recinto.id, 'personas')">Aplicar a todo</button>
                          </div>
                        </th>
                        <th>
                          <div class="aplicar-celda">
                            <select v-model.number="masivo[recinto.id].jornada" class="input-dotacion input-mini">
                              <option v-for="h in jornadasSemanales" :key="h" :value="h">{{ h }}</option>
                            </select>
                            <button type="button" class="btn-aplicar" @click="aplicarATodos(recinto.id, 'jornada')">Aplicar a todo</button>
                          </div>
                        </th>
                        <th>
                          <div class="aplicar-celda">
                            <input v-model.number="masivo[recinto.id].semanas" type="number" min="1" max="52" step="0.5" class="input-dotacion input-mini" placeholder="48" />
                            <button type="button" class="btn-aplicar" @click="aplicarATodos(recinto.id, 'semanas')">Aplicar a todo</button>
                          </div>
                        </th>
                        <th>
                          <div class="aplicar-celda">
                            <input v-model.number="masivo[recinto.id].porcentaje" type="number" min="1" max="100" step="1" class="input-dotacion input-mini" placeholder="100" />
                            <button type="button" class="btn-aplicar" @click="aplicarATodos(recinto.id, 'porcentaje')">Aplicar a todo</button>
                          </div>
                        </th>
                        <th></th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      <tr v-if="dotacion[recinto.id].length === 0">
                        <td colspan="7" class="fila-vacia">Elige los perfiles de este recinto con los botones de arriba.</td>
                      </tr>
                      <tr v-for="(g, i) in dotacion[recinto.id]" :key="g.uid">
                        <td class="td-perfil">{{ g.tipo }}</td>
                        <td><input v-model.number="g.personas" type="number" min="0" step="1" class="input-dotacion" placeholder="0" /></td>
                        <td>
                          <select v-model.number="g.jornada" class="input-dotacion">
                            <option v-for="h in jornadasSemanales" :key="h" :value="h">{{ h }}</option>
                          </select>
                        </td>
                        <td><input v-model.number="g.semanas" type="number" min="1" max="52" step="0.5" class="input-dotacion" placeholder="48" /></td>
                        <td><input v-model.number="g.porcentaje" type="number" min="1" max="100" step="1" class="input-dotacion" placeholder="100" /></td>
                        <td class="td-calculado"><span class="chip-minutos">{{ formatoHoras(horasGrupo(g)) }}</span></td>
                        <td><button class="btn-mini btn-quitar" type="button" title="Quitar este perfil" @click="quitarGrupo(recinto.id, i)"><i class="fa-solid fa-trash"></i></button></td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>

          </template>

        </section>

        <section class="acciones-finales">
          <button class="btn-secundario" @click="router.push(`/resultados/${proyectoId}`)">
            <i class="fa-solid fa-arrow-left"></i> Volver a Resultados
          </button>
          <div class="acciones-derecha">
            <button class="btn-principal" type="button" @click="guardarYContinuar(true)">
              Guardar y seguir <i class="fa-solid fa-arrow-right"></i>
            </button>
          </div>
        </section>
      </main>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppLayout from '@/layouts/AppLayout.vue'

const router = useRouter()
const route = useRoute()
const authStore = useAuthStore()

const API = import.meta.env.VITE_API_BASE

const nombreProyectoActivo = ref('')
const proyectoId = ref(null)

// Perfiles de RRHH: vienen de la tabla EPHAC_Perfil_RRHH
const categorias = ref([])

const jornadasSemanales = [44, 33, 22, 11]

// Valores por defecto del modelo (semanas efectivas al año y % de jornada)
const SEMANAS_DEFECTO = 48
const PORCENTAJE_DEFECTO = 100

// Recintos (provisional — vendrán de los resultados)
const recintos = ref([
  { id: 1, nombre: 'Cubículo UTI' },
  { id: 2, nombre: 'Cubículo UCI' },
  { id: 3, nombre: 'Pabellón menor' },
  { id: 4, nombre: 'Pabellón mayor' },
])

// Grupos de RRHH por recinto
const dotacion = ref({ 1: [], 2: [], 3: [], 4: [] })

// Valores de la fila "Mismo valor para todos", por recinto
function valoresMasivos() {
  return { personas: null, jornada: 44, semanas: SEMANAS_DEFECTO, porcentaje: PORCENTAJE_DEFECTO }
}
const masivo = ref({ 1: valoresMasivos(), 2: valoresMasivos(), 3: valoresMasivos(), 4: valoresMasivos() })

// Recintos abiertos o colapsados
const abiertos = ref({ 1: false, 2: false, 3: false, 4: false })
function toggleRecinto(id) {
  abiertos.value[id] = !abiertos.value[id]
}

let uidSeq = 1

// ¿El recinto ya tiene una fila para este perfil?
function tieneGrupo(recintoId, nombre) {
  return dotacion.value[recintoId].some(o => o.tipo === nombre)
}

// Posición del perfil en el catálogo (las filas siguen el orden de los botones)
function posicionPerfil(nombre) {
  const i = categorias.value.findIndex(c => c.nombre === nombre)
  return i === -1 ? categorias.value.length : i
}

function nuevaFila(nombre) {
  return {
    uid: uidSeq++,
    tipo: nombre,
    personas: 0,
    jornada: 44,
    semanas: SEMANAS_DEFECTO,
    porcentaje: PORCENTAJE_DEFECTO,
  }
}

// Agrega la fila del perfil respetando el orden de los botones
function agregarFila(recintoId, nombre) {
  const filas = dotacion.value[recintoId]
  const pos = posicionPerfil(nombre)
  const i = filas.findIndex(o => posicionPerfil(o.tipo) > pos)
  if (i === -1) filas.push(nuevaFila(nombre))
  else filas.splice(i, 0, nuevaFila(nombre))
}

// Un clic en un perfil: si no está, agrega su fila; si está, la quita
function togglePerfil(recintoId, nombre) {
  const filas = dotacion.value[recintoId]
  const i = filas.findIndex(o => o.tipo === nombre)
  if (i === -1) {
    agregarFila(recintoId, nombre)
    return
  }
  quitarGrupo(recintoId, i)
}

// "Aplicar a todo": copia el valor de la fila de arriba en todas las filas del recinto
function aplicarATodos(recintoId, campo) {
  const valor = masivo.value[recintoId][campo]
  if (valor === null || valor === '' || Number.isNaN(Number(valor))) {
    alert('Escribe primero el valor que quieres aplicar.')
    return
  }
  dotacion.value[recintoId].forEach(g => { g[campo] = Number(valor) })
}

// Quita la fila; si ya tiene personas ingresadas pide confirmación
function quitarGrupo(recintoId, i) {
  const g = dotacion.value[recintoId][i]
  if ((Number(g.personas) || 0) > 0 && !window.confirm(`¿Quitar «${g.tipo}» de este recinto? Se pierden sus datos.`)) return
  dotacion.value[recintoId].splice(i, 1)
}

// Horas disponibles al año = personas × horas semanales × semanas efectivas × (% / 100)
function horasGrupo(g) {
  return (Number(g.personas) || 0) * (Number(g.jornada) || 0) * (Number(g.semanas) || 0) * ((Number(g.porcentaje) || 0) / 100)
}
function formatoHoras(h) { return Math.round(h).toLocaleString('es-CL') }

// Carga la lista de perfiles desde la base
async function cargarPerfiles() {
  const uid = authStore.usuarioId
  if (!uid) return
  try {
    const resp = await fetch(`${API}/rrhh.php?action=perfiles&usuario_id=${uid}`)
    const data = await resp.json()
    if (!resp.ok || !data.ok) {
      alert(data.error || 'No se pudo cargar la lista de perfiles.')
      return
    }
    categorias.value = data.datos.perfiles
  } catch (e) {
    alert('Error de conexión al cargar la lista de perfiles.')
  }
}

// Crea un perfil nuevo en la base; devuelve {id, nombre} o null si falla
async function crearPerfil(nombre) {
  const uid = authStore.usuarioId
  try {
    const resp = await fetch(`${API}/rrhh.php?action=perfil_agregar`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ usuario_id: uid, nombre }),
    })
    const data = await resp.json()
    if (!resp.ok || !data.ok) {
      alert(data.error || 'No se pudo agregar el perfil.')
      return null
    }
    const perfil = data.datos.perfil
    if (!categorias.value.some(c => c.id === perfil.id)) categorias.value.push(perfil)
    if (!data.datos.creado) alert(`El perfil «${perfil.nombre}» ya existía; se usará ese.`)
    return perfil
  } catch (e) {
    alert('Error de conexión al agregar el perfil.')
    return null
  }
}

// "+ Perfil nuevo": pide el nombre, lo crea en el catálogo y agrega su fila
async function agregarPerfilNuevo(recintoId) {
  const nombre = window.prompt('Nombre del perfil nuevo (por ejemplo: Técnico/a de esterilización):')
  if (nombre === null) return
  const perfil = await crearPerfil(nombre)
  if (!perfil) return
  if (tieneGrupo(recintoId, perfil.nombre)) {
    alert(`El perfil «${perfil.nombre}» ya está en este recinto.`)
    return
  }
  agregarFila(recintoId, perfil.nombre)
}

// Carga la dotación guardada del proyecto
async function cargarDotacion() {
  const uid = authStore.usuarioId
  if (!proyectoId.value || !uid) return
  try {
    const resp = await fetch(`${API}/rrhh.php?action=obtener&proyecto_id=${Number(proyectoId.value)}&usuario_id=${uid}`)
    const data = await resp.json()
    if (!resp.ok || !data.ok) {
      alert(data.error || 'No se pudo cargar la dotación de RRHH.')
      return
    }
    const nueva = { 1: [], 2: [], 3: [], 4: [] }
    for (const g of data.datos.grupos) {
      if (!nueva[g.recinto_id]) continue
      nueva[g.recinto_id].push({
        uid: uidSeq++,
        tipo: g.tipo_rrhh,
        personas: g.cantidad_personas,
        jornada: g.jornada_semanal,
        semanas: g.semanas_efectivas,
        porcentaje: Math.round(g.fraccion_jornada * 1000) / 10,
      })
    }
    dotacion.value = nueva
  } catch (e) {
    alert('Error de conexión al cargar la dotación de RRHH.')
  }
}

// Guarda la dotación de todos los recintos.
// Con seguir = true pasa a la pantalla de tiempos por prestación.
let guardando = false
async function guardarYContinuar(seguir = false) {
  const uid = authStore.usuarioId
  if (guardando) return
  if (!proyectoId.value || !uid) {
    alert('No hay un proyecto activo o una sesión iniciada.')
    return
  }
  const grupos = []
  for (const r of recintos.value) {
    for (const g of dotacion.value[r.id]) {
      const semanas = Number(g.semanas)
      const porcentaje = Number(g.porcentaje)
      if (!(semanas > 0 && semanas <= 52)) {
        alert(`Las semanas efectivas de «${g.tipo}» en «${r.nombre}» deben estar entre 1 y 52.`)
        return
      }
      if (!(porcentaje > 0 && porcentaje <= 100)) {
        alert(`El % de jornada de «${g.tipo}» en «${r.nombre}» debe estar entre 1 y 100.`)
        return
      }
      grupos.push({
        recinto_id: r.id,
        tipo_rrhh: g.tipo,
        cantidad_personas: Number(g.personas) || 0,
        jornada_semanal: Number(g.jornada),
        semanas_efectivas: semanas,
        fraccion_jornada: Math.round(porcentaje * 10) / 1000,
      })
    }
  }
  guardando = true
  try {
    const resp = await fetch(`${API}/rrhh.php?action=guardar`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ proyecto_id: Number(proyectoId.value), usuario_id: uid, grupos }),
    })
    const data = await resp.json()
    if (!resp.ok || !data.ok) {
      alert(data.error || 'No se pudo guardar la dotación de RRHH.')
      return
    }
    if (seguir) router.push(`/tiempos-prestacion/${proyectoId.value}`)
    else alert('Dotación de RRHH guardada correctamente.')
  } catch (e) {
    alert('Error de conexión al guardar la dotación de RRHH.')
  } finally {
    guardando = false
  }
}

function volverAtras() {
  router.back()
}

function cerrarSesion() {
  authStore.logout()
  router.push('/login')
}

onMounted(async () => {
  nombreProyectoActivo.value = localStorage.getItem('ephdem_nombre_proyecto_activo') || 'Desconocido'
  proyectoId.value = route.params.proyectoId || localStorage.getItem('ephdem_proyecto_activo')
  // Primero los perfiles, para que la dotación guardada tenga su lista de opciones
  await cargarPerfiles()
  await cargarDotacion()
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

.rrhh-page { background: $color-fondo; flex: 1; }
.rrhh-content { max-width: 1480px; margin: 32px auto 48px auto; padding: 0 20px; display: flex; flex-direction: column; gap: 24px; }

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

.rrhh-panel { display: flex; flex-direction: column; gap: 20px; }

/* Tarjeta de recinto */
.recinto-card { background: #fff; border-radius: 12px; border: 1px solid #e5e7eb; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); overflow: hidden; }
.recinto-title { display: flex; align-items: center; gap: 10px; background: #fff; padding: 14px 20px; font-size: 1.1rem; font-weight: 700; color: $color-primario; border-bottom: 1px solid #e5e7eb; }
/* Botones de perfiles */
.perfiles-chips { display: flex; flex-wrap: wrap; gap: 8px; padding: 14px 20px; }
.chip-perfil {
  background: #fff; color: $color-primario; border: 1.5px solid #7fc8e8; border-radius: 999px;
  padding: 6px 14px; font-size: 0.88rem; font-weight: 600; cursor: pointer;
  &:hover { background: #e4eff6; }
  &.activo { background: $color-primario; color: #fff; border-color: $color-primario; }
  &:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(42,159,214,0.3); }
}
.chip-nuevo { border-style: dashed; color: rgba(0,60,88,0.75); }

/* Fila "Mismo valor para todos" */
.tabla-rrhh .fila-aplicar th { background: #eef5fa; padding: 8px 12px; font-weight: 600; }
.aplicar-etiqueta { font-size: 0.8rem; color: rgba(0,60,88,0.75); text-align: center; }
.aplicar-celda { display: flex; gap: 6px; align-items: center; }
.input-dotacion.input-mini { padding: 6px 8px; font-size: 0.85rem; min-width: 0; }
.btn-aplicar {
  flex: 0 0 auto; background: rgba(0,60,88,0.08); color: $color-primario; border: 1px solid rgba(0,60,88,0.2);
  border-radius: 8px; padding: 6px 8px; font-size: 0.78rem; font-weight: 600; cursor: pointer; white-space: nowrap;
  &:hover { background: rgba(0,60,88,0.16); }
}
.td-perfil { text-align: center; font-weight: 700; color: $color-primario; }

.recinto-title-toggle { justify-content: space-between; cursor: pointer; user-select: none; &:hover { background: #e4eff6; } }

/* Tabla a ancho completo de la tarjeta */
.tabla-scroll { overflow-x: auto; border-top: 1px solid #d1d5db; }
.tabla-rrhh { width: 100%; min-width: 1250px; table-layout: fixed; border-collapse: separate; border-spacing: 0; }
.tabla-rrhh th { background: #ddeaf4; color: $color-primario; font-size: 0.85rem; font-weight: 700; padding: 12px 16px; text-align: left; }
.tabla-rrhh td { padding: 12px 16px; border-bottom: 1px solid $color-borde; font-size: 0.9rem; vertical-align: middle; }
.tabla-rrhh tbody tr:nth-child(odd) td { background: #f8fbfd; }
.tabla-rrhh tbody tr:nth-child(even) td { background: #f0f6fb; }
.tabla-rrhh tbody tr:last-child td { border-bottom: none; }

/* Anchos de columna: perfil, personas, jornada, semanas, % jornada, horas al año, quitar */
.tabla-rrhh th:nth-child(1) { width: 22%; text-align: center; }
.tabla-rrhh th:nth-child(2) { width: 12%; text-align: center; }
.tabla-rrhh th:nth-child(3) { width: 13%; text-align: center; }
.tabla-rrhh th:nth-child(4) { width: 14%; text-align: center; }
.tabla-rrhh th:nth-child(5) { width: 15%; text-align: center; }
.tabla-rrhh th:nth-child(6) { width: 17%; text-align: center; }
.tabla-rrhh th:nth-child(7) { width: 7%; }

.btn-mini { background: rgba(0,60,88,0.08); color: $color-primario; border: 1px solid rgba(0,60,88,0.2); border-radius: 8px; padding: 6px 12px; font-weight: 600; font-size: 0.85rem; cursor: pointer; &:hover { background: rgba(0,60,88,0.16); } }
.btn-mini.btn-quitar { background: transparent; border-color: transparent; color: #9ca3af; &:hover { color: #c62828; background: rgba(198,40,40,0.08); } }
.fila-vacia { text-align: center; color: $color-texto-secundario; padding: 14px; }

.resumen-final { background: #fff; border-radius: 16px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; padding: 20px; display: flex; flex-direction: column; gap: 18px; }
//.resumen-cabecera { display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
//.resumen-titulo { margin: 0; font-size: 1.1rem; font-weight: 700; color: $color-primario; }
//.total-general { display: flex; justify-content: flex-end; align-items: center; }
.calculos-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.pastilla-grande { background: $color-primario; color: #fff; border-radius: 16px; padding: 22px 24px; display: flex; flex-direction: column; align-items: center; gap: 6px; text-align: center; }
.pastilla-grande-titulo { font-size: 1rem; font-weight: 600; }
.pastilla-grande-valor { font-size: 2.2rem; font-weight: 700; font-variant-numeric: tabular-nums; line-height: 1.1; }
.pastilla-grande-sub { font-size: 0.85rem; color: rgba(255,255,255,0.85); }
@media (max-width: 700px) { .calculos-grid { grid-template-columns: 1fr; } }



/* Casillas */
.input-dotacion {
  width: 100%;
  padding: 10px 12px;
  border: 1.5px solid #7fc8e8;
  border-radius: 8px;
  font-size: 0.95rem;
  text-align: center;
  background: #fff;
  box-sizing: border-box;
  &:focus { outline: none; border-color: #2a9fd6; box-shadow: 0 0 0 3px rgba(42,159,214,0.15); }
}

.tabla-rrhh td select.input-dotacion { text-align: center; text-align-last: center; }

.td-calculado { text-align: center; }
.chip-minutos {
  display: block;
  padding: 10px 12px;
  box-sizing: border-box;
  text-align: center;
  background: #ddeaf4;
  color: $color-primario;
  border: 1.5px solid #b8d0ef;
  border-radius: 8px;
  font-size: 0.95rem;
  font-weight: 700;
  font-variant-numeric: tabular-nums;
}
.acciones-derecha { display: flex; align-items: center; gap: 10px; }
.acciones-finales { display: flex; align-items: center; justify-content: space-between; background: #fff; border-radius: 14px; padding: 16px 20px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; }
.btn-principal { background: $color-primario; color: #fff; border: none; border-radius: 10px; padding: 12px 20px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; &:hover { opacity: 0.9; } }
.btn-secundario { background: rgba(0,60,88,0.08); color: $color-primario; border: 1px solid rgba(0,60,88,0.2); border-radius: 10px; padding: 12px 20px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; &:hover { background: rgba(0,60,88,0.14); } }
</style>