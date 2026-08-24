<template>
	<div class="login-layout">
		<!-- TOP BAR 1: Logos institucionales -->
		<div class="sigem-topbar1">
			<div class="sigem-topbar1-center">
				<a href="https://uv.cl" target="_blank"><img src="https://sigem-uv.cl/_general/logos/uv_blanco.png" alt="UV" height="36" class="sigem-topbar1-logo" /></a>
				<a href="https://biomedica.uv.cl/" target="_blank"><img src="https://sigem-uv.cl/_general/logos/biomedica_logo.png" alt="Biomedica" height="36" class="sigem-topbar1-logo" /></a>
			</div>
			<a href="#" class="sigem-topbar1-acceder">Acceder</a>
		</div>

		<!-- TOP BAR 2: Bootstrap navbar -->
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

		<div class="login-page">
			<!-- HERO: Banner con imagen de fondo -->
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

					<!-- ESTADO ÉXITO -->
					<transition name="card-fade" mode="out-in">
						<div v-if="loginExitoso" class="login-success" key="success">
							<div class="success-icon">
								<i class="fa-solid fa-circle-check"></i>
							</div>
							<p class="success-msg">Sesión iniciada</p>
							<p class="success-sub">{{ formulario.email }}</p>
						</div>

						<!-- FORMULARIO -->
						<div v-else key="form">
							<div class="login-card-header">
								<div class="login-icon">
									<i class="fa-solid fa-user-lock"></i>
								</div>
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
									<i class="fa-solid fa-circle-exclamation"></i>
									{{ errorGeneral }}
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
									<a
										href="https://www.sigem-uv.cl/__v2/admin_sigem/sigem_recuperar.php"
										target="_blank"
										class="login-link"
									>
										<i class="fa-solid fa-key"></i> Olvidé mi contraseña
									</a>
									<span class="login-link-sep">·</span>
									<a
										href="https://www.sigem-uv.cl/__v2/admin_sigem/creacion_cuenta.php"
										target="_blank"
										class="login-link"
									>
										<i class="fa-solid fa-user-plus"></i> Crear cuenta
									</a>
								</div>
							</form>
						</div>
					</transition>

				</div>
			</main>
		</div>

		<!-- BOTTOM BAR: Información de contacto -->
		<footer class="sigem-bottomline" id="footer">
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
	</div>
</template>

<script setup>
import { ref } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'

const router = useRouter()
const authStore = useAuthStore()

const formulario = ref({
	email: '',
	password: '',
})

const errores = ref({
	email: '',
	password: '',
})

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
		setTimeout(() => {
			router.push('/inicio')
		}, 1800)
	} catch (error) {
		errorGeneral.value = error instanceof Error
			? error.message
			: 'Error de conexión. Inténtalo nuevamente.'
	} finally {
		cargando.value = false
	}
}
</script>


<style lang="scss" scoped>
@import '@/assets/styles/variables';

// --- SIGEM-UV TOPBAR 1 ---
.sigem-topbar1 {
	width: 100%;
	background: #003c58;
	display: flex;
	align-items: center;
	justify-content: center;
	position: relative;
	padding: 10px 0;
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

// --- SIGEM-UV TOPBAR 2 ---
.sigem-topbar2 {
	position: sticky;
	top: 0;
	width: 100%;
	background: $color-blanco;
	box-shadow: 0 2px 8px rgba(0,0,0,0.15);
	z-index: 1000;
}

// --- LAYOUT ---
.login-layout {
	min-height: 100vh;
	display: flex;
	flex-direction: column;
}
.login-page {
	background: $color-fondo;
	flex: 1;
	display: flex;
	flex-direction: column;
}

// --- HERO ---
.hero {
	background: $color-secundario;
	position: relative;
	padding: 38px 48px;
	overflow: hidden;
	text-align: center;
}
.hero-compact {
	padding: 28px 48px;
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
	gap: 10px;
}
.hero-tag {
	font-size: 12px;
	color: rgba($color-blanco, 0.35);
	letter-spacing: 2.5px;
	text-transform: uppercase;
}
.hero-title {
	font-size: 26px;
	font-weight: 500;
	color: $color-blanco;
	line-height: 1.25;
	margin: 0;
}
.hero-sub {
	font-size: 14px;
	color: rgba($color-blanco, 0.6);
	max-width: 700px;
	line-height: 1.5;
	margin: 0;
}

// --- CONTENIDO LOGIN ---
.login-content {
	display: flex;
	justify-content: center;
	align-items: flex-start;
	padding: 48px 24px 72px;
	flex: 1;
}

.login-card {
	background: $color-blanco;
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

.login-card-header {
	text-align: center;
	margin-bottom: 36px;
}

.login-icon {
	width: 64px;
	height: 64px;
	border-radius: 50%;
	background: rgba($color-primario, 0.08);
	display: flex;
	align-items: center;
	justify-content: center;
	margin: 0 auto 20px;
	font-size: 1.6rem;
	color: $color-primario;
	transition: background 0.2s;

	&:hover {
		background: rgba($color-primario, 0.14);
	}
}

.login-title {
	font-size: 1.6rem;
	font-weight: 700;
	color: $color-primario;
	margin: 0 0 6px;
}

.login-subtitle {
	font-size: 0.95rem;
	color: $color-texto-secundario;
	margin: 0;
}

// --- FORMULARIO ---
.login-form {
	display: flex;
	flex-direction: column;
	gap: 22px;
}

.form-group {
	display: flex;
	flex-direction: column;
	gap: 8px;
}

.form-label {
	font-size: 0.9rem;
	font-weight: 600;
	color: $color-primario;
	display: flex;
	align-items: center;
	gap: 6px;

	i {
		font-size: 0.85rem;
		opacity: 0.7;
	}
}

.form-input {
	padding: 13px 16px;
	border: 1.5px solid $color-borde;
	border-radius: 10px;
	font-size: 1rem;
	color: $color-texto-principal;
	font-weight: 500;
	transition: border-color 0.2s ease, box-shadow 0.2s ease;
	background: $color-blanco;
	width: 100%;

	&:focus {
		outline: none;
		border-color: $color-primario;
		box-shadow: 0 0 0 3px rgba(0, 60, 88, 0.1);
	}

	&::placeholder {
		color: $color-texto-secundario;
		opacity: 0.6;
	}

	&--error {
		border-color: #e53935;
		&:focus {
			box-shadow: 0 0 0 3px rgba(229, 57, 53, 0.12);
		}
	}
}

.input-password-wrapper {
	position: relative;

	.form-input {
		padding-right: 50px;
	}
}

.btn-toggle-password {
	position: absolute;
	right: 14px;
	top: 50%;
	transform: translateY(-50%);
	background: none;
	border: none;
	color: $color-texto-secundario;
	cursor: pointer;
	padding: 4px;
	font-size: 1rem;
	transition: color 0.2s;

	&:hover {
		color: $color-primario;
	}
}

.form-error {
	font-size: 0.82rem;
	color: #e53935;
	display: flex;
	align-items: center;
	gap: 4px;
}

.login-error-general {
	background: rgba(229, 57, 53, 0.07);
	border: 1px solid rgba(229, 57, 53, 0.25);
	border-radius: 10px;
	padding: 12px 16px;
	color: #c62828;
	font-size: 0.9rem;
	display: flex;
	align-items: center;
	gap: 8px;
}

.login-acciones {
	margin-top: 8px;
}

.login-links {
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 10px;
	margin-top: 20px;
	flex-wrap: wrap;
}
.login-link {
	display: inline-flex;
	align-items: center;
	gap: 5px;
	font-size: 0.85rem;
	font-weight: 500;
	color: $color-texto-secundario;
	text-decoration: none;
	transition: color 0.18s;
	i { font-size: 0.8rem; opacity: 0.75; }
	&:hover {
		color: $color-primario;
		i { opacity: 1; }
	}
}
.login-link-sep {
	color: $color-borde;
	font-size: 1.1rem;
	line-height: 1;
}

.btn-ingresar {
	width: 100%;
	padding: 14px;
	background: $color-primario;
	color: $color-blanco;
	border: none;
	border-radius: 12px;
	font-size: 1.05rem;
	font-weight: 700;
	cursor: pointer;
	transition: background 0.2s ease, transform 0.15s ease, box-shadow 0.2s ease;
	display: flex;
	align-items: center;
	justify-content: center;
	gap: 8px;

	&:hover:not(:disabled) {
		background: mix($color-blanco, $color-primario, 10%);
		transform: translateY(-1px);
		box-shadow: 0 6px 18px rgba(0, 60, 88, 0.25);
	}

	&:active:not(:disabled) {
		transform: translateY(0);
	}

	&:disabled {
		opacity: 0.7;
		cursor: not-allowed;
	}
}

// --- BOTTOMBAR ---
.sigem-bottomline {
	width: 100%;
	background: #003c58;
	color: #fff;
	padding: 0;
}
.sigem-bottomline-content {
	display: flex;
	align-items: center;
	justify-content: space-between;
	max-width: 1400px;
	margin: 0 auto;
	padding: 24px 40px;
	gap: 24px;
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

@media (max-width: 600px) {
	.login-card {
		padding: 32px 24px;
	}
	.login-content {
		padding: 32px 16px 48px;
	}
}
// ─── ÉXITO EN TARJETA ───────────────────────────────────────
.login-success {
	display: flex;
	flex-direction: column;
	align-items: center;
	justify-content: center;
	padding: 48px 32px;
	gap: 10px;
	text-align: center;
}
.success-icon {
	font-size: 2.6rem;
	color: #1a9e5c;
	animation: check-in 0.4s ease both;
}
.success-msg {
	margin: 6px 0 0;
	font-size: 1.1rem;
	font-weight: 700;
	color: $color-primario;
}
.success-sub {
	margin: 0;
	font-size: 0.88rem;
	color: $color-texto-secundario;
}

.card-fade-enter-active { transition: opacity 0.25s ease; }
.card-fade-leave-active { transition: opacity 0.2s ease; }
.card-fade-enter-from, .card-fade-leave-to { opacity: 0; }

@keyframes check-in {
	from { transform: scale(0.6); opacity: 0; }
	to   { transform: scale(1);   opacity: 1; }
}
</style>
