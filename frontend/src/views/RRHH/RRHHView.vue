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
              Por cada recinto, agrega grupos de personal: cuántas personas, su jornada semanal
              y cuántos minutos interactúan con el equipo en el procedimiento.
            </span>
          </div>
        </header>

       <section class="rrhh-panel">
          <div v-for="recinto in recintos" :key="recinto.id" class="recinto-card">
            <div class="recinto-title">
              <i class="fa-solid fa-hospital-user"></i>
              {{ recinto.nombre }}
            </div>

            <div class="acciones-recinto">
              <button class="btn-mini" type="button" @click="agregarGrupo(recinto.id)">
                <i class="fa-solid fa-plus"></i> Agregar grupo
              </button>
              <button class="btn-mini" type="button" @click="todosJornada(recinto.id, 44)">
                Todos 44 h
              </button>
            </div>

            <div class="tabla-scroll">
              <table class="tabla-rrhh">
                <thead>
                  <tr>
                    <th>Tipo de RRHH</th>
                    <th>Cantidad de personas</th>
                    <th>Jornada semanal (hrs)</th>
                    <th>Minutos de interacción con el equipo</th>
                    <th>Minutos semanales</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-if="dotacion[recinto.id].length === 0">
                    <td colspan="6" class="fila-vacia">Sin grupos. Usa "Agregar grupo".</td>
                  </tr>
                  <tr v-for="(g, i) in dotacion[recinto.id]" :key="g.uid">
                    <td>
                      <select v-model="g.tipo" class="input-dotacion">
                        <option v-for="c in categorias" :key="c.id" :value="c.nombre">{{ c.nombre }}</option>
                      </select>
                    </td>
                    <td><input v-model.number="g.personas" type="number" min="0" step="1" class="input-dotacion" placeholder="0" /></td>
                    <td>
                      <select v-model.number="g.jornada" class="input-dotacion">
                        <option v-for="h in jornadasSemanales" :key="h" :value="h">{{ h }}</option>
                      </select>
                    </td>
                    <td><input v-model.number="g.minInteraccion" type="number" min="0" step="1" class="input-dotacion" placeholder="0" /></td>
                    <td class="td-calculado"><span class="chip-minutos">{{ minutosGrupo(g) }}</span></td>
                    <td><button class="btn-mini btn-quitar" type="button" @click="quitarGrupo(recinto.id, i)"><i class="fa-solid fa-trash"></i></button></td>
                  </tr>
                </tbody>
              </table>
            </div>
            <div class="total-recinto">
              <span class="total-recinto-label">Total del recinto</span>
              <span class="total-recinto-valor">{{ totalRecinto(recinto.id) }}</span>
              <span class="total-recinto-unidad">min/semana</span>
            </div>
          </div>

          <div class="total-general">
            Total general: <strong>{{ totalGeneral() }}</strong> min/semana
          </div>
        </section>

        <section class="acciones-finales">
          <button class="btn-secundario" @click="router.push(`/resultados/${proyectoId}`)">
            <i class="fa-solid fa-arrow-left"></i> Volver a Resultados
          </button>
          <button class="btn-principal" @click="guardarYContinuar">
            Guardar <i class="fa-solid fa-floppy-disk"></i>
          </button>
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

const nombreProyectoActivo = ref('')
const proyectoId = ref(null)

// Tipos de RRHH (provisional — vendrán de BD)
const categorias = ref([
  { id: 1, nombre: 'Médico Cirujano' },
  { id: 2, nombre: 'Anestesista' },
  { id: 3, nombre: 'Arsenalera' },
  { id: 4, nombre: 'Enfermera/o' },
  { id: 5, nombre: 'Técnico Paramédico' },
])

const jornadasSemanales = [44, 33, 22, 11]

// Recintos (provisional — vendrán de los resultados)
const recintos = ref([
  { id: 1, nombre: 'Cubículo UTI' },
  { id: 2, nombre: 'Cubículo UCI' },
  { id: 3, nombre: 'Pabellón menor' },
  { id: 4, nombre: 'Pabellón mayor' },
])

// Grupos de RRHH por recinto
const dotacion = ref({ 1: [], 2: [], 3: [], 4: [] })

let uidSeq = 1
function nuevoGrupo() {
  return { uid: uidSeq++, tipo: categorias.value[0].nombre, personas: 0, jornada: 44, minInteraccion: 0 }
}

function agregarGrupo(recintoId) { dotacion.value[recintoId].push(nuevoGrupo()) }
function quitarGrupo(recintoId, i) { dotacion.value[recintoId].splice(i, 1) }
function todosJornada(recintoId, h) { dotacion.value[recintoId].forEach(g => { g.jornada = h }) }

// personas × horas semanales × 60
function minutosGrupo(g) { return (Number(g.personas) || 0) * (Number(g.jornada) || 0) * 60 }
function totalRecinto(recintoId) { return dotacion.value[recintoId].reduce((s, g) => s + minutosGrupo(g), 0) }
function totalGeneral() { return recintos.value.reduce((s, r) => s + totalRecinto(r.id), 0) }

function guardarYContinuar() {
  localStorage.setItem('ephdem_rrhh', JSON.stringify(dotacion.value))
  alert('Dotación de RRHH guardada correctamente.')
}

function volverAtras() {
  router.back()
}

function cerrarSesion() {
  authStore.logout()
  router.push('/login')
}

onMounted(() => {
  nombreProyectoActivo.value = localStorage.getItem('ephdem_nombre_proyecto_activo') || 'Desconocido'
  proyectoId.value = route.params.proyectoId || localStorage.getItem('ephdem_proyecto_activo')

  // Recuperar lo guardado, si tiene el formato nuevo (arreglos por recinto)
  try {
    const raw = localStorage.getItem('ephdem_rrhh')
    if (raw) {
      const guardado = JSON.parse(raw)
      const valido = [1, 2, 3, 4].every(id => Array.isArray(guardado[id]))
      if (valido) {
        dotacion.value = guardado
        const maxUid = Math.max(0, ...Object.values(guardado).flat().map(g => g.uid || 0))
        uidSeq = maxUid + 1
      }
    }
  } catch (e) { /* formato antiguo o corrupto: se ignora */ }
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
.acciones-recinto { display: flex; gap: 8px; padding: 12px 20px; }

/* Tabla a ancho completo de la tarjeta */
.tabla-scroll { overflow-x: auto; border-top: 1px solid #d1d5db; }
.tabla-rrhh { width: 100%; min-width: 900px; table-layout: fixed; border-collapse: separate; border-spacing: 0; }
.tabla-rrhh th { background: #ddeaf4; color: $color-primario; font-size: 0.85rem; font-weight: 700; padding: 12px 16px; text-align: left; }
.tabla-rrhh td { padding: 12px 16px; border-bottom: 1px solid $color-borde; font-size: 0.9rem; vertical-align: middle; }
.tabla-rrhh tbody tr:nth-child(odd) td { background: #f8fbfd; }
.tabla-rrhh tbody tr:nth-child(even) td { background: #f0f6fb; }
.tabla-rrhh tbody tr:last-child td { border-bottom: none; }

/* Anchos de columna: tipo, personas, jornada, min. interacción, min. semanales, quitar */
.tabla-rrhh th:nth-child(1) { width: 21%; text-align: center; }
.tabla-rrhh th:nth-child(2) { width: 18%; text-align: center; }
.tabla-rrhh th:nth-child(3) { width: 19%; text-align: center; }
.tabla-rrhh th:nth-child(4) { width: 20%; text-align: center; }
.tabla-rrhh th:nth-child(5) { width: 15%; text-align: center; }
.tabla-rrhh th:nth-child(6) { width: 7%; }

/* Total del recinto: cuadro azul oscuro abajo a la derecha */
.total-recinto { display: flex; justify-content: flex-end; align-items: center; padding: 14px 20px 18px; }
.total-recinto-label,
.total-recinto-valor,
.total-recinto-unidad { background: $color-primario; color: #fff; padding: 10px 0; }
.total-recinto-label { padding-left: 18px; font-size: 0.95rem; font-weight: 600; border-radius: 10px 0 0 10px; }
.total-recinto-valor { padding: 10px 6px 10px 12px; font-size: 0.95rem; font-weight: 700; font-variant-numeric: tabular-nums; }
.total-recinto-unidad { padding-right: 18px; font-size: 0.95rem; font-weight: 400; color: rgba(255,255,255,0.85); border-radius: 0 10px 10px 0; }

.btn-mini { background: rgba(0,60,88,0.08); color: $color-primario; border: 1px solid rgba(0,60,88,0.2); border-radius: 8px; padding: 6px 12px; font-weight: 600; font-size: 0.85rem; cursor: pointer; &:hover { background: rgba(0,60,88,0.16); } }
.btn-mini.btn-quitar { background: transparent; border-color: transparent; color: #9ca3af; &:hover { color: #c62828; background: rgba(198,40,40,0.08); } }
.fila-vacia { text-align: center; color: $color-texto-secundario; padding: 14px; }
.total-general { text-align: right; font-size: 1.05rem; color: $color-primario; padding: 8px 4px; }

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
.acciones-finales { display: flex; align-items: center; justify-content: space-between; background: #fff; border-radius: 14px; padding: 16px 20px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; }
.btn-principal { background: $color-primario; color: #fff; border: none; border-radius: 10px; padding: 12px 20px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; &:hover { opacity: 0.9; } }
.btn-secundario { background: rgba(0,60,88,0.08); color: $color-primario; border: 1px solid rgba(0,60,88,0.2); border-radius: 10px; padding: 12px 20px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; &:hover { background: rgba(0,60,88,0.14); } }
</style>