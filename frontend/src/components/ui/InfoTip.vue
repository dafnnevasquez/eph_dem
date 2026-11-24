<template>
  <span ref="raiz" class="infotip">
    <button
      type="button"
      class="infotip-btn"
      :class="{ 'infotip-btn--claro': claro }"
      :aria-expanded="abierto"
      :aria-label="titulo ? `Qué significa: ${titulo}` : 'Más información'"
      @click.stop="abierto = !abierto"
    >i</button>
    <span v-if="abierto" class="infotip-pop" :class="{ 'infotip-pop--izq': izquierda }" role="tooltip">
      <strong v-if="titulo" class="infotip-titulo">{{ titulo }}</strong>
      <span class="infotip-texto"><slot /></span>
    </span>
  </span>
</template>

<script setup>
// Ícono "i" que abre una ventanita con una explicación.
// Uso: <InfoTip titulo="Permanente">El perfil está durante toda la prestación.</InfoTip>
//   claro     -> ícono blanco, para fondos oscuros (pastillas azules)
//   izquierda -> la ventanita se abre hacia la izquierda (cerca del borde derecho)
import { ref, onMounted, onBeforeUnmount } from 'vue'

defineProps({
  titulo: { type: String, default: '' },
  claro: { type: Boolean, default: false },
  izquierda: { type: Boolean, default: false },
})

const abierto = ref(false)
const raiz = ref(null)

function cerrarSiAfuera(e) {
  if (abierto.value && raiz.value && !raiz.value.contains(e.target)) abierto.value = false
}
function cerrarConEscape(e) {
  if (e.key === 'Escape') abierto.value = false
}
onMounted(() => {
  document.addEventListener('pointerdown', cerrarSiAfuera)
  document.addEventListener('keydown', cerrarConEscape)
})
onBeforeUnmount(() => {
  document.removeEventListener('pointerdown', cerrarSiAfuera)
  document.removeEventListener('keydown', cerrarConEscape)
})
</script>

<style lang="scss" scoped>
@import '@/assets/styles/variables';

.infotip { position: relative; display: inline-flex; vertical-align: middle; margin-left: 6px; }
.infotip-btn {
  width: 18px; height: 18px; padding: 0; border: none; border-radius: 50%;
  display: inline-flex; align-items: center; justify-content: center;
  background: rgba(0,60,88,0.14); color: $color-primario;
  font-size: 0.72rem; font-weight: 700; font-family: Georgia, serif; font-style: italic; line-height: 1; cursor: pointer;
  &:hover { background: rgba(0,60,88,0.24); }
  &:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(42,159,214,0.35); }
}
.infotip-btn--claro { background: rgba(255,255,255,0.22); color: #fff; &:hover { background: rgba(255,255,255,0.35); } }

.infotip-pop {
  position: absolute; top: calc(100% + 8px); left: -10px; z-index: 60;
  width: 290px; padding: 10px 12px;
  background: #fff; border: 1.5px solid $color-primario; border-radius: 10px;
  box-shadow: 0 10px 24px rgba(0,30,45,0.18);
  display: flex; flex-direction: column; gap: 4px;
  text-align: left; white-space: normal; text-transform: none; letter-spacing: normal;
}
.infotip-pop--izq { left: auto; right: -10px; }
.infotip-titulo { font-size: 0.88rem; font-weight: 700; color: $color-primario; }
.infotip-texto { font-size: 0.84rem; font-weight: 500; line-height: 1.5; color: $color-texto-principal; }
</style>