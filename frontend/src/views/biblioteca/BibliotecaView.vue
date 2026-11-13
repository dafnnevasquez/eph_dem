<template>
  <AppLayout>
    <div class="biblioteca-page">
      <section class="hero hero-compact">
        <div class="hero-bg"></div>
        <div class="hero-content">
          <div class="hero-tag">MÓDULO EPHDEM</div>
          <h1 class="hero-title">Biblioteca EPH</h1>
          <p class="hero-sub">Libros, guías y documentos de referencia sobre Estudios de Preinversión Hospitalaria.</p>
        </div>
      </section>

      <main class="biblioteca-content">
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

        <div class="filtro-buscar">
          <input v-model="busqueda" type="text" placeholder="Buscar por título, autor o tema..." />
        </div>

        <section class="libros-grid">
          <article v-for="libro in librosFiltrados" :key="libro.archivo" class="libro-card">
            <div class="libro-icon"><i class="fa-solid fa-file-pdf"></i></div>
            <div class="libro-info">
              <h3 class="libro-titulo">{{ libro.titulo }}</h3>
              <div class="libro-meta">
                <span>{{ libro.autor }}</span>
                <span v-if="libro.anio" class="libro-anio">{{ libro.anio }}</span>
              </div>
              <p v-if="libro.descripcion" class="libro-desc">{{ libro.descripcion }}</p>
            </div>
            <a class="btn-ver" :href="DOCS_URL + libro.archivo" target="_blank" rel="noopener">
              <i class="fa-solid fa-arrow-up-right-from-square"></i> Ver / Descargar
            </a>
          </article>

          <div v-if="librosFiltrados.length === 0" class="lista-vacia">No se encontraron documentos.</div>
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

// Carpeta del servidor donde están los PDF.
const DOCS_URL = '/__v2/modulo_eph/docs/'

// ⚠️ DATOS DE EJEMPLO: reemplazar por los libros reales.
// "archivo" es el nombre exacto del PDF subido a DOCS_URL.
const libros = [
  {
    titulo: 'Guía de referencia para estudios de preinversión hospitalaria',
    autor: 'Banco Interamericano de Desarrollo (BID) · Washington, D.C.',
    anio: 2018,
    descripcion: 'Guía del BID con la metodología de referencia para elaborar estudios de preinversión hospitalaria. DOI: 10.18235/0002110',
    archivo: 'Guia_de_referencia_para_estudios_de_preinversion_hospitalaria_es.pdf',
  }
]

const librosFiltrados = computed(() => {
  const texto = busqueda.value.toLowerCase().trim()
  if (!texto) return libros
  return libros.filter(l =>
    l.titulo.toLowerCase().includes(texto) ||
    l.autor.toLowerCase().includes(texto) ||
    (l.descripcion || '').toLowerCase().includes(texto)
  )
})

function cerrarSesion() {
  authStore.logout()
  router.push('/login')
}
</script>

<style lang="scss" scoped>
@import '@/assets/styles/variables';

.biblioteca-page { background: $color-fondo; flex: 1; }
.biblioteca-content { max-width: 1000px; margin: 32px auto 72px auto; padding: 0 24px; display: flex; flex-direction: column; gap: 24px; }

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

.filtro-buscar input { width: 100%; padding: 10px 14px; border: 1px solid $color-borde; border-radius: 10px; font-size: 0.95rem; color: $color-texto-principal; box-sizing: border-box; }

.libros-grid { display: flex; flex-direction: column; gap: 14px; }
.libro-card { display: flex; align-items: center; gap: 18px; background: #fff; border: 1px solid $color-borde; border-radius: 14px; padding: 18px 22px; box-shadow: 0 6px 16px $color-sombra-suave; }
.libro-icon { font-size: 2rem; color: #b71c1c; flex: 0 0 auto; }
.libro-info { flex: 1; min-width: 0; }
.libro-titulo { margin: 0 0 4px; font-size: 1.05rem; font-weight: 700; color: $color-primario; }
.libro-meta { display: flex; gap: 10px; align-items: center; font-size: 0.85rem; color: $color-texto-secundario; }
.libro-anio { background: rgba(0,60,88,0.08); color: $color-primario; border-radius: 999px; padding: 2px 10px; font-weight: 600; font-size: 0.78rem; }
.libro-desc { margin: 8px 0 0; font-size: 0.88rem; color: $color-texto-secundario; line-height: 1.5; }
.btn-ver { flex: 0 0 auto; display: inline-flex; align-items: center; gap: 6px; background: $color-primario; color: #fff; border-radius: 999px; padding: 8px 16px; font-weight: 600; font-size: 0.88rem; text-decoration: none; &:hover { background: mix(#fff, $color-primario, 8%); } }
.lista-vacia { color: $color-texto-secundario; padding: 16px 20px; text-align: center; background: #fff; border-radius: 14px; border: 1px solid $color-borde; }

@media (max-width: 640px) {
  .libro-card { flex-direction: column; align-items: flex-start; }
}
</style>