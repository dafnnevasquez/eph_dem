<template>
  <div class="barra-progreso" :class="{ visible: progreso.visible }" aria-hidden="true">
    <div class="barra-progreso-relleno" :style="{ width: progreso.ancho + '%' }"></div>
  </div>
</template>

<script setup>
import { progreso } from '@/utils/progreso'
</script>

<style lang="scss" scoped>
.barra-progreso {
  position: fixed; top: 0; left: 0; right: 0; height: 3px; z-index: 3000;
  pointer-events: none; opacity: 0; transition: opacity .3s ease;
  &.visible { opacity: 1; }
}
.barra-progreso-relleno {
  height: 100%; background: #2a9fd6; box-shadow: 0 0 8px rgba(42,159,214,.7);
  border-radius: 0 3px 3px 0; transition: width .2s ease;
}
</style>