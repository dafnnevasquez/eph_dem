<template>
  <!-- TOP BAR 1: Logos institucionales -->
  <div class="sigem-topbar1">
    <div class="sigem-topbar1-center">
      <a href="https://uv.cl" target="_blank">
        <img src="https://sigem-uv.cl/_general/logos/uv_blanco.png" alt="UV" height="36" class="sigem-topbar1-logo" />
      </a>
      <a href="https://biomedica.uv.cl/" target="_blank">
        <img src="https://sigem-uv.cl/_general/logos/biomedica_logo.png" alt="Biomedica" height="36" class="sigem-topbar1-logo" />
      </a>
    </div>
    <a href="#" class="sigem-topbar1-acceder">Acceder</a>
  </div>

  <!-- TOP BAR 2: Navbar -->
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
            <a class="nav-link" href="https://sigem-uv.cl/__v2/#MODULOS" target="_blank">Módulos</a>
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
        </ul>
      </div>
    </div>
  </nav>

  <!-- CONTENIDO DE CADA VISTA -->
  <slot />

  <!-- FOOTER -->
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
          <a href="https://www.linkedin.com" target="_blank"><img src="https://sigem-uv.cl/_general/logos/icons8-linkedin-48.png" alt="LinkedIn" height="28"/></a>
          <a href="https://www.twitter.com" target="_blank"><img src="https://sigem-uv.cl/_general/logos/icons8-twitterx-50.png" alt="Twitter" height="28"/></a>
          <a href="https://www.instagram.com" target="_blank"><img src="https://sigem-uv.cl/_general/logos/icons8-instagram-48.png" alt="Instagram" height="28"/></a>
          <a href="https://www.facebook.com" target="_blank"><img src="https://sigem-uv.cl/_general/logos/icons8-facebook-48.png" alt="Facebook" height="28"/></a>
        </div>
        <div class="sigem-bottomline-copy">&copy; 2026 <b>SIGEM-UV</b> | Todos los Derechos Reservados</div>
      </div>
      <div class="sigem-bottomline-right">
        <img src="https://sigem-uv.cl/_general/logos/uv_blanco.png" alt="UV" height="48" />
      </div>
    </div>
  </footer>
</template>

<script setup>
// Sin lógica — solo estructura de layout
</script>

<style lang="scss" scoped>
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
  position: absolute;
  right: 32px;
}
.sigem-topbar2 {
  position: sticky;
  top: 0;
  width: 100%;
  background: #fff;
  box-shadow: 0 2px 8px rgba(0,0,0,0.15);
  z-index: 1000;
}
.sigem-bottomline {
  width: 100%;
  background: #003c58;
  color: #fff;
  margin-top: 48px;
}
.sigem-bottomline-content {
  display: flex;
  align-items: center;
  justify-content: space-between;
  max-width: 1400px;
  margin: 0 auto;
  padding: 12px 24px;
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
</style>