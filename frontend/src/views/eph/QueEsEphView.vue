<template>
  <AppLayout>
    <div class="queeseph-page">
      <section class="hero hero-compact">
        <div class="hero-bg"></div>
        <div class="hero-content">
          <div class="hero-tag">MÓDULO EPHDEM</div>
          <h1 class="hero-title">¿Qué es EPH?</h1>
          <p class="hero-sub">Conoce en pocos minutos qué es un Estudio de Preinversión Hospitalaria.</p>
        </div>
      </section>

      <main class="queeseph-content">
        <div class="nav-bar">
          <div class="nav-buttons">
            <button class="btn-back" type="button" @click="router.back()"><i class="fa-solid fa-arrow-left"></i> Volver</button>
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

        <section class="video-card">
          <div class="video-wrap">
            <iframe
              v-if="VIDEO_YOUTUBE_ID"
              :src="`https://www.youtube-nocookie.com/embed/${VIDEO_YOUTUBE_ID}?rel=0`"
              title="¿Qué es EPH?"
              frameborder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowfullscreen
            ></iframe>
            <video v-else-if="VIDEO_MP4" :src="VIDEO_MP4" controls preload="metadata"></video>
            <div v-else class="video-placeholder">
              <i class="fa-solid fa-circle-play"></i>
              <span>Video disponible próximamente</span>
            </div>
          </div>
        </section>

        <section class="texto-card">
          <div class="texto-icon"><i class="fa-solid fa-hospital"></i></div>
          <div>
            <h2 class="texto-title">Estudio de Preinversión Hospitalaria</h2>
            <p class="texto-desc">
              Un EPH es el estudio que detecta las brechas de un establecimiento de salud dentro de su red,
              define el tamaño del proyecto y compara alternativas de solución hasta elegir la más conveniente
              en lo técnico, económico y social, para postular a financiamiento.
            </p>
            <p class="texto-desc">
              EPHDEM apoya una parte de ese estudio: estimar el <strong>equipamiento médico</strong> que necesita
              el hospital a partir de la demanda de prestaciones.
            </p>
            <div class="texto-acciones">
              <button class="btn-sec" type="button" @click="router.push('/glosario')">
                <i class="fa-solid fa-book"></i> Ver glosario
              </button>
              <button class="btn-sec" type="button" @click="router.push('/biblioteca')">
                <i class="fa-solid fa-book-bookmark"></i> Ir a la biblioteca
              </button>
            </div>
          </div>
        </section>
      </main>
    </div>
  </AppLayout>
</template>

<script setup>
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppLayout from '@/layouts/AppLayout.vue'

const router = useRouter()
const authStore = useAuthStore()

// ⚠️ Completar UNA de las dos opciones:
// YouTube: solo el ID. Ej: en https://www.youtube.com/watch?v=AbC123xYz el ID es 'AbC123xYz'
const VIDEO_YOUTUBE_ID = ''
// o un .mp4 subido al servidor. Ej: '/__v2/modulo_eph/docs/que-es-eph.mp4'
const VIDEO_MP4 = ''

function cerrarSesion() {
  authStore.logout()
  router.push('/login')
}
</script>

<style lang="scss" scoped>
@import '@/assets/styles/variables';

.queeseph-page { background: $color-fondo; flex: 1; }
.queeseph-content { max-width: 900px; margin: 32px auto 72px auto; padding: 0 24px; display: flex; flex-direction: column; gap: 24px; }

.hero { background: $color-secundario; position: relative; padding: 38px 48px; overflow: hidden; text-align: center; }
.hero-compact { padding: 28px 48px; }
.hero-bg { position: absolute; inset: 0; background: url('@/assets/img/mac.jpg') center/cover no-repeat; }
.hero-content { position: relative; z-index: 2; display: flex; flex-direction: column; align-items: center; gap: 10px; }
.hero-tag { font-size: 12px; color: rgba(255,255,255,0.35); letter-spacing: 2.5px; text-transform: uppercase; }
.hero-title { font-size: 26px; font-weight: 500; color: #fff; margin: 0; }
.hero-sub { font-size: 14px; color: rgba(255,255,255,0.6); max-width: 700px; line-height: 1.5; margin: 0; }

.nav-bar { display: flex; align-items: center; justify-content: space-between; gap: 12px; }
.nav-buttons { display: flex; gap: 10px; }
.session-badge { display: flex; align-items: center; gap: 8px; padding: 6px 14px 6px 10px; background: rgba(0,60,88,0.06); border: 1.5px solid rgba(0,60,88,0.18); border-radius: 999px; color: $color-primario; font-size: 0.88rem; font-weight: 600; }
.session-nombre { white-space: nowrap; }
.btn-logout { background: none; border: none; color: $color-primario; cursor: pointer; padding: 2px 4px; opacity: 0.7; &:hover { opacity: 1; color: #c62828; } }
.btn-back { background: $color-primario; color: #fff; border: 1px solid $color-primario; border-radius: 999px; padding: 6px 12px; font-weight: 600; cursor: pointer; &:hover { background: mix(#fff, $color-primario, 6%); } }

.video-card { background: #fff; border-radius: 16px; padding: 16px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; }
.video-wrap { position: relative; width: 100%; aspect-ratio: 16 / 9; border-radius: 10px; overflow: hidden; background: #0b1f2a; }
.video-wrap iframe, .video-wrap video { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
.video-placeholder { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; color: rgba(255,255,255,0.7); font-weight: 600; i { font-size: 3.5rem; opacity: 0.6; } }

.texto-card { display: flex; align-items: flex-start; gap: 20px; background: #fff; border-radius: 16px; padding: 24px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; }
.texto-icon { font-size: 2rem; color: $color-primario; flex: 0 0 auto; }
.texto-title { font-size: 1.3rem; font-weight: 700; color: $color-primario; margin: 0 0 8px; }
.texto-desc { margin: 0 0 10px; color: $color-texto-secundario; line-height: 1.6; }
.texto-acciones { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 6px; }
.btn-sec { display: inline-flex; align-items: center; gap: 6px; background: rgba(0,60,88,0.08); color: $color-primario; border: 1px solid rgba(0,60,88,0.2); border-radius: 999px; padding: 7px 14px; font-weight: 600; cursor: pointer; &:hover { background: rgba(0,60,88,0.14); } }
</style>