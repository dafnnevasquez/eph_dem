<template>
  <!-- Capa de espera para cálculos largos. Aparece solo si la espera pasa de 300 ms,
       para no parpadear cuando el servidor responde rápido. -->
  <Teleport to="body">
    <Transition name="espera">
      <div v-if="mostrar" class="espera-capa" role="status" aria-live="polite">
        <div class="espera-caja">
          <div class="espera-logo">
            <svg class="espera-anillo" viewBox="0 0 120 120" aria-hidden="true">
              <circle cx="60" cy="60" r="54" class="anillo-fondo" />
              <circle cx="60" cy="60" r="54" class="anillo-giro" />
            </svg>
            <!-- El latido va en el contenedor y el cambio de ícono en el <i>, para que no se estorben -->
            <span class="espera-latido">
              <Transition name="icono" mode="out-in" :duration="250">
                <i :key="indice" class="espera-icono" :class="iconoActual" aria-hidden="true"></i>
              </Transition>
            </span>
          </div>
          <div class="espera-titulo">{{ titulo }}</div>
          <Transition name="mensaje" mode="out-in">
            <div :key="indice" class="espera-mensaje">{{ listaMensajes[indice] }}</div>
          </Transition>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<script setup>
import { ref, computed, watch, onBeforeUnmount } from 'vue'

const props = defineProps({
  visible: { type: Boolean, default: false },
  titulo: { type: String, default: 'Calculando…' },
  // Forma simple: un mensaje fijo con su ícono en el centro del círculo
  mensaje: { type: String, default: '' },
  icono: { type: String, default: '' },
  // Forma con rotación (opcional): varios mensajes, cada uno con su ícono
  mensajes: { type: Array, default: () => ['Procesando los datos del proyecto…'] },
  // Un ícono por mensaje: el ícono N acompaña al mensaje N y cambian juntos.
  // Si hay menos íconos que mensajes, se vuelven a usar desde el primero.
  iconos: {
    type: Array,
    default: () => [
      'fa-solid fa-hospital',
      'fa-solid fa-bed-pulse',
      'fa-solid fa-user-doctor',
      'fa-solid fa-stethoscope',
      'fa-solid fa-syringe',
      'fa-solid fa-heart-pulse',
    ],
  },
})

const mostrar = ref(false)
const indice = ref(0)
let tMostrar = null
let tRotar = null

const listaMensajes = computed(() => (props.mensaje ? [props.mensaje] : props.mensajes))
const listaIconos = computed(() => (props.icono ? [props.icono] : props.iconos))
// El índice recorre los mensajes; el ícono sigue al mensaje
const iconoActual = computed(() => listaIconos.value[indice.value % listaIconos.value.length])

function encender() {
  clearTimeout(tMostrar)
  tMostrar = setTimeout(() => {
    indice.value = 0
    mostrar.value = true
    clearInterval(tRotar)
    if (listaMensajes.value.length > 1) {
      tRotar = setInterval(() => { indice.value = (indice.value + 1) % listaMensajes.value.length }, 1800)
    }
  }, 300)
}
function apagar() {
  clearTimeout(tMostrar)
  clearInterval(tRotar)
  mostrar.value = false
}

watch(() => props.visible, v => (v ? encender() : apagar()), { immediate: true })
onBeforeUnmount(apagar)
</script>

<style lang="scss" scoped>
@import '@/assets/styles/variables';

.espera-capa {
  position: fixed; inset: 0; z-index: 2500; display: flex; align-items: center; justify-content: center;
  background: rgba(255, 255, 255, 0.72); backdrop-filter: blur(6px); -webkit-backdrop-filter: blur(6px);
}
.espera-caja { display: flex; flex-direction: column; align-items: center; gap: 14px; padding: 24px; text-align: center; color: $color-primario; }
.espera-logo { position: relative; width: 140px; height: 140px; display: flex; align-items: center; justify-content: center; }
.espera-anillo { position: absolute; inset: 0; width: 100%; height: 100%; animation: girar 1.4s linear infinite; }
.anillo-fondo { fill: none; stroke: rgba(0,60,88,.12); stroke-width: 4; }
.anillo-giro { fill: none; stroke: #2a9fd6; stroke-width: 4; stroke-linecap: round; stroke-dasharray: 90 250; }
.espera-latido { display: inline-flex; animation: latir 1.6s ease-in-out infinite; }
.espera-icono { font-size: 3rem; color: $color-primario; }
.espera-titulo { font-size: 1.15rem; font-weight: 700; }
.espera-mensaje { font-size: .95rem; color: $color-texto-secundario; min-height: 1.4em; }

@keyframes girar { to { transform: rotate(360deg); } }
@keyframes latir { 0%, 100% { transform: scale(1); opacity: .9; } 50% { transform: scale(1.08); opacity: 1; } }

.espera-enter-active, .espera-leave-active { transition: opacity .25s ease; }
.espera-enter-from, .espera-leave-to { opacity: 0; }
.icono-enter-active, .icono-leave-active { transition: opacity .25s ease, transform .25s ease; }
.icono-enter-from { opacity: 0; transform: scale(.6); }
.icono-leave-to { opacity: 0; transform: scale(1.3); }
.mensaje-enter-active, .mensaje-leave-active { transition: opacity .25s ease, transform .25s ease; }
.mensaje-enter-from { opacity: 0; transform: translateY(6px); }
.mensaje-leave-to { opacity: 0; transform: translateY(-6px); }

@media (prefers-reduced-motion: reduce) {
  .espera-anillo, .espera-latido { animation: none; }
}
</style>