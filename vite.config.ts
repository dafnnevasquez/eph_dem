import { fileURLToPath, URL } from 'node:url'


import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

// https://vite.dev/config/
export default defineConfig({
  base: '/__v2/modulo_eph/pruebas/',
  plugins: [
    vue(),
    vueDevTools(),
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url))
    },
  },
  css: {
    preprocessorOptions: {
      scss: {
        additionalData: `@import "@/assets/styles/main.scss";`
      }
    }
  },
  server: {
    proxy: {
      '/ajax': {
        target: 'https://www.sigem-uv.cl/__v2/modulo_eph',
        changeOrigin: true,
        secure: true
      }
    }
  }
})

