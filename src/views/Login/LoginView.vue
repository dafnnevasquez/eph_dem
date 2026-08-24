<template>
  <AppLayout>
    <!-- HERO -->
    <section class="hero hero-compact">
      <div class="hero-bg"></div>
      <div class="hero-content">
        <div class="hero-tag">MODULO EPHDEM</div>
        <h1 class="hero-title">Estudio de Preinversión Hospitalaria</h1>
        <p class="hero-sub">Accede con tu cuenta institucional para gestionar proyectos de preinversión.</p>
      </div>
    </section>

    <main class="login-content">
      <div class="login-card">
        <transition name="card-fade" mode="out-in">
          <!-- ESTADO ÉXITO -->
          <div v-if="loginExitoso" class="login-success" key="success">
            <div class="success-icon"><i class="fa-solid fa-circle-check"></i></div>
            <p class="success-msg">Sesión iniciada</p>
            <p class="success-sub">{{ formulario.email }}</p>
          </div>

          <!-- FORMULARIO -->
          <div v-else key="form">
            <div class="login-card-header">
              <div class="login-icon"><i class="fa-solid fa-user-lock"></i></div>
              <h2 class="login-title">Iniciar sesión</h2>
              <p class="login-subtitle">Ingresa tus credenciales para continuar</p>
            </div>
            <form @submit.prevent="iniciarSesion" class="login-form">
              <!-- Email -->
              <div class="form-group">
                <label for="login-email" class="form-label">
                  <i class="fa-solid fa-envelope"></i> Correo electrónico
                </label>
                <input
                  id="login-email"
                  v-model="formulario.email"
                  type="email"
                  class="form-input"
                  :class="{ 'form-input--error': errores.email }"
                  placeholder="correo@sigem-uv.cl"
                  autocomplete="email"
                  required
                />
                <span v-if="errores.email" class="form-error">{{ errores.email }}</span>
              </div>
              <!-- Contraseña -->
              <div class="form-group">
                <label for="login-password" class="form-label">
                  <i class="fa-solid fa-lock"></i> Contraseña
                </label>
                <div class="input-password-wrapper">
                  <input
                    id="login-password"
                    v-model="formulario.password"
                    :type="mostrarPassword ? 'text' : 'password'"
                    class="form-input"
                    :class="{ 'form-input--error': errores.password }"
                    placeholder="••••••••"
                    autocomplete="current-password"
                    required
                  />
                  <button
                    type="button"
                    class="btn-toggle-password"
                    @click="mostrarPassword = !mostrarPassword"
                    :title="mostrarPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'"
                  >
                    <i :class="mostrarPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye'"></i>
                  </button>
                </div>
                <span v-if="errores.password" class="form-error">{{ errores.password }}</span>
              </div>
              <!-- Error general -->
              <div v-if="errorGeneral" class="login-error-general">
                <i class="fa-solid fa-circle-exclamation"></i> {{ errorGeneral }}
              </div>
              <!-- Acciones -->
              <div class="login-acciones">
                <button type="submit" class="btn-ingresar" :disabled="cargando">
                  <span v-if="cargando"><i class="fa-solid fa-spinner fa-spin"></i> Verificando...</span>
                  <span v-else>Ingresar <i class="fa-solid fa-arrow-right"></i></span>
                </button>
              </div>
              <!-- Links secundarios -->
              <div class="login-links">
                <a href="https://www.sigem-uv.cl/__v2/admin_sigem/sigem_recuperar.php" target="_blank" class="login-link">
                  <i class="fa-solid fa-key"></i> Olvidé mi contraseña
                </a>
                <span class="login-link-sep">·</span>
                <a href="https://www.sigem-uv.cl/__v2/admin_sigem/creacion_cuenta.php" target="_blank" class="login-link">
                  <i class="fa-solid fa-user-plus"></i> Crear cuenta
                </a>
              </div>
            </form>
          </div>
        </transition>
      </div>
    </main>
  </AppLayout>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppLayout from '@/layouts/AppLayout.vue'

const router = useRouter()
const authStore = useAuthStore()

const formulario = ref({ email: '', password: '' })
const errores = ref({ email: '', password: '' })
const errorGeneral = ref('')
const cargando = ref(false)
const mostrarPassword = ref(false)
const loginExitoso = ref(false)

function validar() {
  errores.value = { email: '', password: '' }
  let valido = true
  if (!formulario.value.email.trim()) {
    errores.value.email = 'El correo es obligatorio.'
    valido = false
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formulario.value.email)) {
    errores.value.email = 'Ingresa un correo válido.'
    valido = false
  }
  if (!formulario.value.password) {
    errores.value.password = 'La contraseña es obligatoria.'
    valido = false
  } else if (formulario.value.password.length < 4) {
    errores.value.password = 'La contraseña debe tener al menos 4 caracteres.'
    valido = false
  }
  return valido
}

async function iniciarSesion() {
  errorGeneral.value = ''
  if (!validar()) return
  cargando.value = true
  try {
    await authStore.login(formulario.value.email, formulario.value.password)
    loginExitoso.value = true
    setTimeout(() => { router.push('/inicio') }, 1800)
  } catch (error) {
    errorGeneral.value = error instanceof Error ? error.message : 'Error de conexión. Inténtalo nuevamente.'
  } finally {
    cargando.value = false
  }
}
</script>

<style lang="scss" scoped>
@import '@/assets/styles/variables';

.hero {
  background: $color-secundario;
  position: relative;
  padding: 38px 48px;
  overflow: hidden;
  text-align: center;
}
.hero-compact { padding: 28px 48px; }
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
  gap: 10px;
}
.hero-tag { font-size: 12px; color: rgba(255,255,255,0.35); letter-spacing: 2.5px; text-transform: uppercase; }
.hero-title { font-size: 26px; font-weight: 500; color: #fff; margin: 0; }
.hero-sub { font-size: 14px; color: rgba(255,255,255,0.6); max-width: 700px; line-height: 1.5; margin: 0; }

.login-content {
  display: flex;
  justify-content: center;
  align-items: flex-start;
  padding: 48px 24px 72px;
  flex: 1;
}
.login-card {
  background: #fff;
  border-radius: 20px;
  padding: 48px 44px;
  border: 1px solid $color-borde;
  box-shadow: 0 16px 40px $color-sombra-suave;
  width: 100%;
  max-width: 460px;
  min-height: 480px;
  display: flex;
  flex-direction: column;
  justify-content: center;
}
.login-card-header { text-align: center; margin-bottom: 36px; }
.login-icon {
  width: 64px; height: 64px; border-radius: 50%;
  background: rgba($color-primario, 0.08);
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 20px; font-size: 1.6rem; color: $color-primario;
}
.login-title { font-size: 1.6rem; font-weight: 700; color: $color-primario; margin: 0 0 6px; }
.login-subtitle { font-size: 0.95rem; color: $color-texto-secundario; margin: 0; }
.login-form { display: flex; flex-direction: column; gap: 22px; }
.form-group { display: flex; flex-direction: column; gap: 8px; }
.form-label { font-size: 0.9rem; font-weight: 600; color: $color-primario; display: flex; align-items: center; gap: 6px; }
.form-input {
  padding: 13px 16px; border: 1.5px solid $color-borde; border-radius: 10px;
  font-size: 1rem; color: $color-texto-principal; width: 100%;
  &:focus { outline: none; border-color: $color-primario; box-shadow: 0 0 0 3px rgba(0,60,88,0.1); }
  &--error { border-color: #e53935; }
}
.input-password-wrapper { position: relative; .form-input { padding-right: 50px; } }
.btn-toggle-password {
  position: absolute; right: 14px; top: 50%; transform: translateY(-50%);
  background: none; border: none; color: $color-texto-secundario; cursor: pointer; font-size: 1rem;
}
.form-error { font-size: 0.82rem; color: #e53935; }
.login-error-general {
  background: rgba(229,57,53,0.07); border: 1px solid rgba(229,57,53,0.25);
  border-radius: 10px; padding: 12px 16px; color: #c62828; font-size: 0.9rem;
  display: flex; align-items: center; gap: 8px;
}
.login-acciones { margin-top: 8px; }
.btn-ingresar {
  width: 100%; padding: 14px; background: $color-primario; color: #fff;
  border: none; border-radius: 12px; font-size: 1.05rem; font-weight: 700;
  cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px;
  &:hover:not(:disabled) { transform: translateY(-1px); box-shadow: 0 6px 18px rgba(0,60,88,0.25); }
  &:disabled { opacity: 0.7; cursor: not-allowed; }
}
.login-links { display: flex; align-items: center; justify-content: center; gap: 10px; margin-top: 20px; }
.login-link { font-size: 0.85rem; font-weight: 500; color: $color-texto-secundario; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
.login-link-sep { color: $color-borde; }

.login-success { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 48px 32px; gap: 10px; text-align: center; }
.success-icon { font-size: 2.6rem; color: #1a9e5c; animation: check-in 0.4s ease both; }
.success-msg { margin: 6px 0 0; font-size: 1.1rem; font-weight: 700; color: $color-primario; }
.success-sub { margin: 0; font-size: 0.88rem; color: $color-texto-secundario; }

.card-fade-enter-active { transition: opacity 0.25s ease; }
.card-fade-leave-active { transition: opacity 0.2s ease; }
.card-fade-enter-from, .card-fade-leave-to { opacity: 0; }

@keyframes check-in {
  from { transform: scale(0.6); opacity: 0; }
  to   { transform: scale(1);   opacity: 1; }
}

@media (max-width: 600px) {
  .login-card { padding: 32px 24px; }
  .login-content { padding: 32px 16px 48px; }
}
</style>