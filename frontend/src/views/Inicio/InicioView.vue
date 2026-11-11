<template>
  <AppLayout>

    <!-- HERO -->
    <section class="hero">
      <div class="hero-bg"></div>
      <div class="hero-content">
        <div class="hero-logos">
          <div class="hero-logo-side hero-logo-side--left">
            <img src="@/assets/img/logo-sigem-horizontal-blanco.png" alt="Logo SIGEM-UV" class="hero-logo logo-sigem" />
          </div>
          <div class="logo-sep"></div>
          <div class="hero-logo-side hero-logo-side--right">
            <img src="@/assets/img/Logo-Claro-EphDEM.png" alt="Logo EphDEM" class="hero-logo logo-ephdem" />
          </div>
        </div>
        <div class="hero-tag">Módulo EphDEM</div>
        <h1 class="hero-title">Estudio de Preinversión Hospitalaria</h1>
        <p class="hero-sub">
          Herramienta para la estimación de equipamiento médico necesario para satisfacer una demanda proyectada de prestaciones.
        </p>
      </div>
    </section>

    <!-- TARJETAS -->
    <div class="content">
      <div class="section-title">¡Bienvenidos! Selecciona un módulo para comenzar</div>
      <div class="cards cards-primary">

        <div class="card-column">
          <div class="card card-primary" @click="irMisProyectos">
            <div class="card-icon">🗒️</div>
            <div class="card-title">Mis proyectos</div>
            <div class="card-desc">Revisa, edita y administra los proyectos creados en el sistema.</div>
            <div class="card-hint">Abrir listado</div>
          </div>
        </div>

        <div class="card-column">
          <div class="card card-primary" @click="toggleNuevoProyecto">
            <div class="card-icon card-icon-plus">✚</div>
            <div class="card-title">Nuevo proyecto</div>
            <div class="card-desc">Comienza un nuevo estudio seleccionando el tipo de atención.</div>
            <div class="card-hint">Elegir tipo</div>
          </div>

          <transition name="nuevo-menu">
            <div v-if="mostrarOpcionesNuevo" class="cards cards-inline cards-inline-float">
              <div class="card card-primary card-mini" @click="iniciarAtencionAbierta">
                <div class="card-title">Atención abierta</div>
              </div>
              <div class="card card-primary card-mini" @click="iniciarAtencionCerrada">
                <div class="card-title">Atención cerrada</div>
              </div>
            </div>
          </transition>
        </div>

      </div>
      <p class="manual-link-hint">
      <i class="fa-solid fa-book-open"></i> Revisa el siguiente material para conocer sobre EPH y la aplicación
    </p>
    <div class="material-buttons">
      <button class="btn-material" @click="router.push('/manual')">
        <i class="fa-solid fa-book"></i> Manual de usuario
      </button>
      <button class="btn-material" @click="router.push('/glosario')">
        <i class="fa-solid fa-list"></i> Glosario EPH
      </button>
    </div>  
  </div>

    <!-- Aviso de bienvenida (lateral derecho) -->
    <transition name="aviso">
      <aside v-if="mostrarAviso" class="aviso-bienvenida" role="status">
        <button class="aviso-cerrar" type="button" aria-label="Cerrar aviso" @click="mostrarAviso = false">
          <i class="fa-solid fa-xmark"></i>
        </button>
        <div class="aviso-cabecera">
          <span class="aviso-icono"><i class="fa-solid fa-hospital"></i></span>
          <h2 class="aviso-titulo">¡Bienvenid@ a EPHDEM!</h2>
        </div>
        <p>
          Este módulo es una herramienta para estimar el equipamiento médico necesario.
          Puedes crear un proyecto de <b>atención abierta</b> o <b>atención cerrada</b> y estimar el equipamiento según cada prestación.
        </p>
        <button class="aviso-manual" type="button" @click="router.push('/manual')">
          <i class="fa-solid fa-book"></i> Ver manual de usuario
        </button>
      </aside>
    </transition>


  </AppLayout>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import AppLayout from '@/layouts/AppLayout.vue'

const router = useRouter()
const mostrarOpcionesNuevo = ref(false)

// Aviso de bienvenida: entra desde la derecha al abrir la página
const mostrarAviso = ref(false)
onMounted(() => {
  setTimeout(() => { mostrarAviso.value = true }, 400)
})

function toggleNuevoProyecto() {
  mostrarOpcionesNuevo.value = !mostrarOpcionesNuevo.value
}
function irMisProyectos() {
  router.push('/proyectos')
}
function iniciarAtencionAbierta() {
  router.push('/crear-proyecto-abierta')
}
function iniciarAtencionCerrada() {
  router.push('/crear-proyecto')
}
</script>

<style lang="scss" scoped>
@import '@/assets/styles/variables';

.hero {
  background: $color-secundario;
  position: relative;
  padding: 52px 48px;
  overflow: hidden;
  text-align: center;
}
.hero-bg {
  position: absolute;
  inset: 0;
  background: url('@/assets/img/mac.jpg') center/cover no-repeat;
}
.hero-content {
  position: relative;
  z-index: 2;
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 14px;
}
.hero-logos {
  display: flex;
  align-items: center;
  justify-content: center;
  height: 160px;
  width: 100%;
}
.hero-logo-side {
  flex: 1;
  display: flex;
  align-items: center;
}
.hero-logo-side--left  { justify-content: flex-end;  padding-right: 40px; }
.hero-logo-side--right { justify-content: flex-start; padding-left: 40px; }
.hero-logo {
  max-height: 100%;
  object-fit: contain;
  transition: transform 0.3s ease;
  &:hover { transform: scale(1.05); }
}
.logo-ephdem { width: 160px; }
.logo-sigem  { width: 280px; filter: drop-shadow(0 0 12px rgba(0,0,0,0.7)); }
.logo-sep    { width: 2px; height: 80px; background: rgba(255,255,255,0.15); }
.hero-tag    { font-size: 12px; color: rgba(255,255,255,0.35); letter-spacing: 2.5px; text-transform: uppercase; }
.hero-title  { font-size: 28px; font-weight: 500; color: #fff; margin: 0; }
.hero-sub    { font-size: 14px; color: rgba(255,255,255,0.6); max-width: 550px; line-height: 1.5; }

.content { padding: 48px 24px 200px; }
.section-title { text-align: center; font-size: 1rem; font-weight: 600; color: $color-texto-principal; margin-bottom: 32px; }

.cards-primary  { margin-top: 8px; align-items: flex-start; gap: 60px; }
.card-column    { display: flex; flex-direction: column; align-items: center; gap: 18px; width: 300px; position: relative; }
.cards-inline   { display: flex; flex-direction: row; gap: 14px; justify-content: center; width: 100%; }
.cards-inline-float { position: absolute; left: 50%; top: calc(100% + 10px); transform: translateX(-50%); width: max-content; z-index: 2; }

.card-primary {
  background: $color-primario;
  color: #fff;
  border: 1px solid rgba(255,255,255,0.15);
  border-top: 4px solid rgba(255,255,255,0.25);
  box-shadow: 0 12px 26px rgba(0,30,45,0.25);
  &:hover { transform: translateY(-10px); box-shadow: 0 18px 32px rgba(0,30,45,0.35); }
  .card-title { color: #fff; }
  .card-desc  { color: rgba(255,255,255,0.82); }
  .card-hint  { color: rgba(255,255,255,0.7); text-transform: uppercase; letter-spacing: 0.08em; }
}
.card-icon      { font-size: 2rem; margin: 0 auto 12px; }
.card-icon-plus { font-size: 2.4rem; font-weight: 700; }
.card-mini      { width: 160px; padding: 14px 12px; .card-title { margin: 0; font-size: 1rem; } }

.nuevo-menu-enter-active, .nuevo-menu-leave-active { transition: opacity 0.22s ease, transform 0.22s ease; }
.nuevo-menu-enter-from, .nuevo-menu-leave-to { opacity: 0; transform: translateX(-50%) translateY(-8px) scale(0.98); }
.nuevo-menu-enter-to, .nuevo-menu-leave-from { opacity: 1; transform: translateX(-50%) translateY(0) scale(1); }

.manual-link-hint { text-align: center; margin-top: 120px; font-size: 0.95rem; color: $color-texto-secundario; }
.material-buttons { display: flex; justify-content: center; gap: 14px; margin-top: 16px; }
.btn-material { background: $color-primario; color: #fff; border: none; border-radius: 10px; padding: 10px 20px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px; font-size: 0.95rem; &:hover { opacity: 0.85; } }

/* Aviso de bienvenida lateral */
.aviso-bienvenida {
  position: fixed; top: 110px; right: 24px; z-index: 50; width: min(360px, calc(100vw - 32px));
  background: #fff; border: 1px solid $color-borde; border-left: 6px solid $color-primario; border-radius: 14px;
  padding: 20px 22px 18px; box-shadow: 0 16px 36px rgba(0,30,45,0.22);
  p { margin: 0 0 14px; color: $color-texto-principal; font-size: 0.95rem; line-height: 1.55; }
  b { color: $color-primario; }
}
.aviso-cabecera { display: flex; align-items: center; gap: 12px; margin-bottom: 10px; padding-right: 22px; }
.aviso-icono {
  flex: 0 0 auto; width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
  background: rgba(0,60,88,0.08); color: $color-primario; font-size: 1.15rem;
}
.aviso-titulo { margin: 0; font-size: 1.2rem; font-weight: 700; color: $color-primario; }
.aviso-cerrar {
  position: absolute; top: 10px; right: 10px; background: none; border: none; color: #9ca3af;
  width: 30px; height: 30px; border-radius: 8px; cursor: pointer; font-size: 1rem;
  &:hover { background: rgba(0,60,88,0.08); color: $color-primario; }
}
.aviso-manual {
  background: $color-primario; color: #fff; border: none; border-radius: 10px; padding: 9px 16px;
  font-weight: 700; font-size: 0.9rem; cursor: pointer; display: inline-flex; align-items: center; gap: 8px;
  &:hover { opacity: 0.88; }
}
.aviso-enter-active, .aviso-leave-active { transition: transform 0.35s ease, opacity 0.35s ease; }
.aviso-enter-from, .aviso-leave-to { transform: translateX(110%); opacity: 0; }
@media (max-width: 600px) { .aviso-bienvenida { top: auto; bottom: 16px; right: 16px; } }
</style>
