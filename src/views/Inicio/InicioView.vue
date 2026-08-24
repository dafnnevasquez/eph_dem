<template>

  <!-- TOP BAR 1: Logos institucionales -->
  <div class="sigem-topbar1">
    <div class="sigem-topbar1-center">
      <a href="https://uv.cl" target="_blank"><img src="https://sigem-uv.cl/_general/logos/uv_blanco.png" alt="UV" height="36" class="sigem-topbar1-logo" /></a>
      <a href="https://biomedica.uv.cl/" target="_blank"><img src="https://sigem-uv.cl/_general/logos/biomedica_logo.png" alt="Biomedica" height="36" class="sigem-topbar1-logo" /></a>
    </div>
    <a href="#" class="sigem-topbar1-acceder">Acceder</a>
  </div>

  <!-- TOP BAR 2: Bootstrap navbar idéntica a legacy -->
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

  <div class="inicio-page">

    <!-- HERO -->
    <section class="hero">
      <div class="hero-bg"></div>
      <div class="hero-content">
        <div class="hero-logos">
          <!-- Logo SIGEM Horizontal -->
          <div class="hero-logo-side hero-logo-side--left">
            <img src="@/assets/img/logo-sigem-horizontal-blanco.png" alt="Logo SIGEM-UV" class="hero-logo logo-sigem" />
          </div>
          <div class="logo-sep"></div>
          <!-- Logo EphDEM -->
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

        <!-- Mis proyectos -->
        <div class="card-column">
          <div class="card card-primary" @click="irMisProyectos">
            <div class="card-icon" aria-hidden="true">🗒️</div>
            <div class="card-title">Mis proyectos</div>
            <div class="card-desc">
              Revisa, edita y administra los proyectos creados en el sistema.
            </div>
            <div class="card-hint">Abrir listado</div>
          </div>
        </div>

        <!-- Nuevo proyecto -->
        <div class="card-column">
          <div class="card card-primary" @click="toggleNuevoProyecto">
            <div class="card-icon card-icon-plus" aria-hidden="true">✚</div>
            <div class="card-title">Nuevo proyecto</div>
            <div class="card-desc">
              Comienza un nuevo estudio seleccionando el tipo de atención.
            </div>
            <div class="card-hint">Elegir tipo</div>
          </div>

          <transition name="nuevo-menu">
            <div v-if="mostrarOpcionesNuevo" class="cards cards-inline cards-inline-float">
              <!-- Atención Abierta -->
              <div class="card card-primary card-mini" @click="iniciarAtencionAbierta">
                <div class="card-title">Atención abierta</div>
              </div>

              <!-- Atención Cerrada -->
              <div class="card card-primary card-mini" @click="iniciarAtencionCerrada">
                <div class="card-title">Atención cerrada</div>
              </div>
            </div>
          </transition>
        </div>

      </div>
      <p class="manual-link-hint">
        <i class="fa-solid fa-book"></i> Revisa el <a class="link-manual" href="#" @click.prevent="router.push('/manual')"><strong><u>manual de usuario</u></strong></a> para conocer el flujo completo de la aplicación.
      </p>
    </div>

  </div>

  <!-- BOTTOM BAR: Información de contacto -->
  <footer class="sigem-bottomline">
    <div class="sigem-bottomline-content">
      <div class="sigem-bottomline-left">
        <img src="https://sigem-uv.cl/_general/logos/LOGO_SIGEM-UV_HORIZONTAL-BLANCO.png" alt="SIGEM-UV" height="48" />
      </div>
      <div class="sigem-bottomline-center">
        <div>Gral. Cruz 222, Valparaíso ::: +56 32 2603662</div>
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
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'

const router = useRouter()
const mostrarOpcionesNuevo = ref(false)

function toggleNuevoProyecto() {
  mostrarOpcionesNuevo.value = !mostrarOpcionesNuevo.value
}

function irMisProyectos() {
  router.push('/proyectos')
}

function iniciarAtencionAbierta() {
  // Por ahora, mostraremos una alerta indicando que no está implementado.
  alert('El módulo de Atención Abierta no está implementado en esta versión.')
}

function iniciarAtencionCerrada() {
  // Redirigir a la siguiente vista
  router.push('/crear-proyecto')
}
</script>

<style lang="scss" scoped>

// --- SIGEM-UV TOPBAR 1 (UNIFICADO) ---
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


// --- SIGEM-UV TOPBAR 2 (BLANCA) ---
.sigem-topbar2 {
  position: sticky;
  top: 0;
  width: 100%;
  background: $color-blanco;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
  z-index: 1000;
}
.sigem-topbar2-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  max-width: 1400px;
  margin: 0 auto;
  padding: 0 24px;
  height: 48px;
}
.sigem-navbar-brand img {
  height: 28px;
}
.sigem-navbar-menu {
  display: flex;
  gap: 32px;
}
.sigem-navbar-menu-right {
  margin-left: auto;
}
.sigem-navbar-link {
  color: #003c58;
  font-weight: 500;
  text-decoration: none;
  font-size: 1.08rem;
  display: flex;
  align-items: center;
  gap: 6px;
  transition: color 0.2s;
  &:hover { color: #43708a; }
}

// --- TARJETAS PRIMARIAS (HOME) ---
.cards-primary {
  margin-top: 8px;
  align-items: flex-start;
  gap: 60px;
}
.card-column {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 18px;
  width: 300px;
  position: relative;
}
.cards-inline {
  display: flex;
  flex-direction: row;
  gap: 14px;
  justify-content: center;
  width: 100%;
}
.cards-inline-float {
  position: absolute;
  left: 50%;
  top: calc(100% + 10px);
  transform: translateX(-50%);
  width: max-content;
  z-index: 2;
}
.nuevo-menu-enter-active,
.nuevo-menu-leave-active {
  transition: opacity 0.22s ease, transform 0.22s ease;
}
.nuevo-menu-enter-from,
.nuevo-menu-leave-to {
  opacity: 0;
  transform: translateX(-50%) translateY(-8px) scale(0.98);
}
.nuevo-menu-enter-to,
.nuevo-menu-leave-from {
  opacity: 1;
  transform: translateX(-50%) translateY(0) scale(1);
}
.card-primary {
  background: $color-primario;
  color: $color-blanco;
  border: 1px solid rgba(255, 255, 255, 0.15);
  border-top: 4px solid rgba(255, 255, 255, 0.25);
  box-shadow: 0 12px 26px rgba(0, 30, 45, 0.25);
}
.card-icon {
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto 12px auto;
  font-size: 2rem;
  color: $color-blanco;
  line-height: 1;
}
.card-icon-plus {
  font-size: 2.4rem;
  font-weight: 700;
}
.card-primary .card-title {
  color: $color-blanco;
}
.card-primary .card-desc {
  color: rgba(255, 255, 255, 0.82);
}
.card-primary .card-hint {
  color: rgba(255, 255, 255, 0.7);
  text-transform: uppercase;
  letter-spacing: 0.08em;
}
.card-primary:hover {
  transform: translateY(-10px);
  box-shadow: 0 18px 32px rgba(0, 30, 45, 0.35);
  border-top-color: rgba(255, 255, 255, 0.5);
}
.card-mini {
  width: 160px;
  padding: 14px 12px;
}
.card-mini .card-title {
  margin: 0;
  font-size: 1rem;
}

// --- SIGEM-UV TOPBAR 1 (Acceder) ---
.sigem-topbar1 {
  width: 100%;
  background: #003c58;
  display: flex;
  justify-content: center;
  align-items: center;
  gap: 32px;
  padding: 2px 0 2px 0;
  z-index: 1001;
  position: relative;
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
  margin-left: 32px;
  padding: 6px 18px;
  border-radius: 18px;
  background: rgba(255,255,255,0.08);
  transition: background 0.2s, color 0.2s;
  &:hover {
    background: #ffd700;
    color: #003c58;
  }
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
/* stylelint-disable-next-line scss/at-import-partial-extension */
@import '@/assets/styles/variables';

/* HERO */
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
  opacity: 1;
  filter: none;
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
  gap: 0;
  margin: 0 auto 24px auto;
  height: 160px;
  width: 100%;
}

.hero-logo-side {
  flex: 1;
  display: flex;
  align-items: center;
}
.hero-logo-side--left  { justify-content: flex-end;   padding-right: 40px; }
.hero-logo-side--right { justify-content: flex-start;  padding-left: 40px; }


.hero-logo {
  max-height: 100%;
  object-fit: contain;
  transition: transform 0.3s ease;
  &:hover {
    transform: scale(1.05);
  }
}
.logo-ephdem {
  width: 160px;
}
.logo-sigem {
  width: 280px;
  filter: drop-shadow(0 0 12px rgba(0, 0, 0, 0.7));
}

.logo-sep {
  width: 2px;
  height: 80px;
  background: rgba($color-blanco, 0.15);
}
.hero-tag {
  font-size: 12px;
  color: rgba($color-blanco, 0.35);
  letter-spacing: 2.5px;
  text-transform: uppercase;
}
.hero-title {
  font-size: 28px;
  font-weight: 500;
  color: $color-blanco;
  line-height: 1.25;
  margin: 0;
}
.hero-sub {
  font-size: 14px;
  color: rgba($color-blanco, 0.6);
  max-width: 550px;
  line-height: 1.5;
}

/* TARJETAS */
.content {
  padding: 48px 24px 200px;
}
.section-title {
  text-align: center;
  font-size: 1rem; /* Reducido desde 1.5rem */
  font-weight: 600;
  color: $color-texto-principal;
  margin-bottom: 32px;
}

/* Los estilos de .cards y .card ahora están en _cards.scss y se aplican globalmente */

.manual-link-hint {
  text-align: center;
  margin-top: 120px;
  font-size: 0.95rem;
  color: $color-texto-secundario;
}
.link-manual {
  color: $color-primario;
  text-decoration: underline;
  font-weight: 700;
  cursor: pointer;
  transition: color 0.2s;
  &:hover { color: darken(#003c58, 10%); }
}
</style>
