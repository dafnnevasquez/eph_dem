import { fileURLToPath, URL } from 'node:url'
import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'

export default defineConfig({
  base: '/__v2/modulo_eph/front/',
  plugins: [
    vue(),
    vueDevTools(),
  ],
  build: {
    rollupOptions: {
      output: {
        chunkFileNames: 'assets/js/[name]-[hash].js',
        entryFileNames: 'assets/js/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          const ext = assetInfo.name?.split('.').pop()
          if (['png', 'jpg', 'jpeg', 'gif', 'svg', 'ico'].includes(ext ?? '')) {
            return 'assets/img/[name]-[hash][extname]'
          }
          if (ext === 'css') {
            return 'assets/css/[name]-[hash][extname]'
          }
          return 'assets/[name]-[hash][extname]'
        }
      }
    }
  },
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
