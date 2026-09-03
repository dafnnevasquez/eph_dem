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
              Ingresa la cantidad de profesionales disponibles por recinto y categoría.
              El sistema usará estos datos para ajustar el equipamiento calculado.
            </span>
          </div>
        </header>

        <section class="rrhh-panel">
          <div v-for="recinto in recintos" :key="recinto.id" class="recinto-card">
            <div class="recinto-title">
              <i class="fa-solid fa-hospital-user"></i>
              {{ recinto.nombre }}
            </div>
            <div class="tabla-scroll">
              <table class="tabla-rrhh">
                <thead>
                  <tr>
                    <th>Categoría de Personal</th>
                    <th>Dotación disponible</th>
                    <th>Equipos que puede operar</th>
                    <th>Equipos requeridos</th>
                    <th>Estado</th>
                  </tr>
                </thead>
                <tbody>
                  <tr v-for="categoria in categorias" :key="categoria.id">
                    <td>
                      <div class="categoria-nombre">{{ categoria.nombre }}</div>
                      <div class="categoria-hint">{{ categoria.descripcion }}</div>
                    </td>
                    <td>
                      <input
                        v-model.number="dotacion[recinto.id][categoria.id]"
                        type="number"
                        min="0"
                        step="1"
                        class="input-dotacion"
                        placeholder="0"
                      />
                    </td>
                    <td class="td-calculado">
                      {{ equiposPorPersonal(recinto.id, categoria.id) }}
                    </td>
                    <td class="td-requerido">
                      {{ recinto.equiposRequeridos }}
                    </td>
                    <td>
                      <span class="estado-badge" :class="estadoBadge(recinto.id, categoria.id, recinto.equiposRequeridos)">
                        {{ estadoTexto(recinto.id, categoria.id, recinto.equiposRequeridos) }}
                      </span>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
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

// Categorías de personal (provisional — vendrán de BD)
const categorias = ref([
  { id: 1, nombre: 'Médico Cirujano', descripcion: 'Realiza los procedimientos quirúrgicos', ratioEquipos: 1 },
  { id: 2, nombre: 'Anestesista', descripcion: 'Administra la anestesia durante el procedimiento', ratioEquipos: 1 },
  { id: 3, nombre: 'Arsenalera', descripcion: 'Asiste en pabellón y maneja el instrumental', ratioEquipos: 2 },
  { id: 4, nombre: 'Enfermera/o', descripcion: 'Asistencia clínica en el recinto', ratioEquipos: 3 },
  { id: 5, nombre: 'Técnico Paramédico', descripcion: 'Apoyo técnico en procedimientos', ratioEquipos: 3 },
])

// Recintos con equipamiento calculado (provisional — vendrán de los resultados)
const recintos = ref([
  { id: 1, nombre: 'Cubículo UTI', equiposRequeridos: 0 },
  { id: 2, nombre: 'Cubículo UCI', equiposRequeridos: 0 },
  { id: 3, nombre: 'Pabellón menor', equiposRequeridos: 0 },
  { id: 4, nombre: 'Pabellón mayor', equiposRequeridos: 0 },
])

// Dotación ingresada por el usuario
const dotacion = ref({
  1: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 },
  2: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 },
  3: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 },
  4: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 },
})

function equiposPorPersonal(recintoId, categoriaId) {
  const cat = categorias.value.find(c => c.id === categoriaId)
  const personal = dotacion.value[recintoId]?.[categoriaId] ?? 0
  if (!cat || personal <= 0) return 0
  return personal * cat.ratioEquipos
}

function estadoBadge(recintoId, categoriaId, equiposRequeridos) {
  const capacidad = equiposPorPersonal(recintoId, categoriaId)
  if (capacidad === 0) return 'badge-sin-datos'
  if (capacidad >= equiposRequeridos) return 'badge-ok'
  return 'badge-insuficiente'
}

function estadoTexto(recintoId, categoriaId, equiposRequeridos) {
  const capacidad = equiposPorPersonal(recintoId, categoriaId)
  if (capacidad === 0) return 'Sin datos'
  if (capacidad >= equiposRequeridos) return 'Suficiente'
  return 'Insuficiente'
}

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
.rrhh-content { max-width: 1200px; margin: 32px auto 72px auto; padding: 0 24px; display: flex; flex-direction: column; gap: 24px; }

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

.recinto-card { background: #fff; border-radius: 16px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; overflow: hidden; }
.recinto-title { display: flex; align-items: center; gap: 10px; background: #eef5f9; padding: 14px 20px; font-size: 1.1rem; font-weight: 700; color: $color-primario; border-bottom: 1px solid $color-borde; }

.tabla-scroll { overflow-x: auto; }
.tabla-rrhh { width: 100%; border-collapse: collapse; }
.tabla-rrhh th { background: #e9f1f6; color: $color-primario; font-size: 0.85rem; font-weight: 700; padding: 10px 14px; text-align: left; }
.tabla-rrhh td { padding: 12px 14px; border-bottom: 1px solid $color-borde; font-size: 0.9rem; vertical-align: middle; }
.tabla-rrhh tr:last-child td { border-bottom: none; }
.tabla-rrhh tr:nth-child(even) td { background: #f8fbfd; }

.categoria-nombre { font-weight: 600; color: $color-texto-principal; }
.categoria-hint { font-size: 0.78rem; color: $color-texto-secundario; margin-top: 2px; }

.input-dotacion { width: 80px; padding: 8px 10px; border: 1.5px solid $color-borde; border-radius: 8px; font-size: 0.95rem; text-align: center; &:focus { outline: none; border-color: $color-primario; } }

.td-calculado { font-weight: 700; color: $color-primario; text-align: center; }
.td-requerido { text-align: center; color: $color-texto-secundario; }

.estado-badge { display: inline-flex; align-items: center; padding: 4px 12px; border-radius: 999px; font-size: 0.82rem; font-weight: 700; }
.badge-ok { background: rgba(26,158,92,0.12); color: #1a9e5c; }
.badge-insuficiente { background: rgba(197,40,40,0.1); color: #c62828; }
.badge-sin-datos { background: rgba(0,0,0,0.06); color: $color-texto-secundario; }

.acciones-finales { display: flex; align-items: center; justify-content: space-between; background: #fff; border-radius: 14px; padding: 16px 20px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; }
.btn-principal { background: $color-primario; color: #fff; border: none; border-radius: 10px; padding: 12px 20px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; &:hover { opacity: 0.9; } }
.btn-secundario { background: rgba(0,60,88,0.08); color: $color-primario; border: 1px solid rgba(0,60,88,0.2); border-radius: 10px; padding: 12px 20px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; &:hover { background: rgba(0,60,88,0.14); } }
</style>