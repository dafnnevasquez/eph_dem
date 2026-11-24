<template>
  <!-- Botón de guardar: mientras "cargando" es true muestra un spinner, cambia el texto
       y queda deshabilitado (evita doble clic). Las clases y el @click pasan directo al botón. -->
  <button :type="type" class="boton-guardar" :disabled="cargando || disabled" :aria-busy="cargando">
    <template v-if="cargando">
      <i class="fa-solid fa-spinner fa-spin"></i> {{ textoCargando }}
    </template>
    <slot v-else />
  </button>
</template>

<script setup>
defineProps({
  cargando: { type: Boolean, default: false },
  textoCargando: { type: String, default: 'Guardando…' },
  disabled: { type: Boolean, default: false },
  type: { type: String, default: 'button' },
})
</script>

<style scoped>
/* Mientras guarda no se ve "apagado" como un botón deshabilitado normal */
.boton-guardar[aria-busy="true"] { opacity: .85 !important; cursor: progress !important; }
</style>