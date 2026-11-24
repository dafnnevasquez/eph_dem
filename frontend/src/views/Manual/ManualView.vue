<template>
  <AppLayout>
    <div class="manual-page">
      <section class="hero hero-compact">
        <div class="hero-bg"></div>
        <div class="hero-content">
          <div class="hero-tag">MÓDULO EPHDEM</div>
          <h1 class="hero-title">Manual de Usuario</h1>
          <p class="hero-sub">Todo lo que necesitas para usar EPHDEM, paso a paso.</p>
        </div>
      </section>

      <main class="manual-content">
        <div class="nav-bar">
          <div class="nav-buttons">
            <button class="btn-back" type="button" @click="volverAtras"><i class="fa-solid fa-arrow-left"></i> Volver</button>
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

        <!-- PESTAÑAS -->
        <nav class="tabs-manual" role="tablist">
          <button
            v-for="t in pestanas"
            :key="t.id"
            type="button"
            role="tab"
            class="tab-manual"
            :class="{ activa: pestana === t.id }"
            :aria-selected="pestana === t.id"
            @click="pestana = t.id"
          >
            <i :class="t.icon"></i>
            <span>{{ t.label }}</span>
          </button>
        </nav>

        <!-- ===================== PESTAÑA LECTURA ===================== -->
        <template v-if="pestana === 'lectura'">

          <!-- INTRODUCCIÓN -->
          <section class="manual-intro">
            <div class="intro-icon"><i class="fa-solid fa-book-open"></i></div>
            <div>
              <h2 class="intro-title">¿Para qué sirve EPHDEM?</h2>
              <p class="intro-desc">
                EPHDEM calcula cuántos equipos médicos necesita un hospital y revisa si el personal alcanza para usarlos.
                Tú ingresas los datos del hospital y el sistema hace los cálculos. Haz los pasos <strong>en orden</strong>: cada paso usa los datos del anterior.
              </p>
              <div class="intro-partes">
                <div class="intro-parte">
                  <span class="intro-parte-num">Pasos 1 a 4</span>
                  <span><strong>Equipamiento:</strong> cuántos equipos y recintos necesita el hospital según su demanda.</span>
                </div>
                <div class="intro-parte">
                  <span class="intro-parte-num intro-parte-num-rrhh">Pasos 5 a 7</span>
                  <span><strong>Recursos humanos:</strong> si el personal disponible alcanza para usar esos equipos y, si no alcanza, cuánto falta.</span>
                </div>
              </div>
            </div>
          </section>

          <!-- FLUJO -->
          <section class="flujo-row">
            <div class="flujo-step" :class="{ activo: slide === i }" v-for="(step, i) in flujoGeneral" :key="step.id" @click="irA(i)">
              <div class="flujo-num" :class="{ 'flujo-num-rrhh': step.rrhh }">{{ i + 1 }}</div>
              <div class="flujo-icon"><i :class="step.icon"></i></div>
              <div class="flujo-label flujo-label-link">{{ step.label }}</div>
              <div v-if="i < flujoGeneral.length - 1" class="flujo-arrow"><i class="fa-solid fa-chevron-right"></i></div>
            </div>
          </section>

          <!-- ============ CARRUSEL DE PASOS ============ -->
          <section class="carrusel" aria-roledescription="carrusel" aria-label="Pasos para usar EPHDEM">
            <div class="carrusel-marco">
              <button class="carrusel-flecha izquierda" type="button" aria-label="Paso anterior" :disabled="slide === 0" @click="anterior"><i class="fa-solid fa-chevron-left"></i></button>
              <button class="carrusel-flecha derecha" type="button" aria-label="Paso siguiente" :disabled="slide === flujoGeneral.length - 1" @click="siguiente"><i class="fa-solid fa-chevron-right"></i></button>

              <Transition :name="direccion" mode="out-in">

                <!-- PASO 1 -->
                <article v-if="slide === 0" key="paso-1" id="paso-1" class="slide">
                  <div class="slide-tag">Parte 1 · Equipamiento</div>
                  <div class="paso-header">
                    <div class="paso-num">Paso 1</div>
                    <div class="paso-icon"><i class="fa-solid fa-plus-circle"></i></div>
                    <div>
                      <h3 class="paso-title">Crea un proyecto</h3>
                      <p class="paso-desc">Un proyecto es el lugar donde se guarda tu estudio. Ponle un nombre fácil de reconocer, por ejemplo: <em>"Hospital UV 2026"</em>.</p>
                    </div>
                  </div>
                  <div class="paso-cols">
                    <div class="paso-body">
                      <div class="mockup-nuevo-row">
                        <div class="mockup-btn-nuevo"><i class="fa-solid fa-plus"></i> Nuevo proyecto</div>
                        <div class="mockup-submenu">
                          <div class="mockup-submenu-item mockup-submenu-active">Atención cerrada <i class="fa-solid fa-arrow-right"></i></div>
                          <div class="mockup-submenu-item">Atención abierta</div>
                        </div>
                      </div>
                      <div class="mockup-form-proyecto">
                        <div class="mockup-label">Nombre del proyecto</div>
                        <div class="mockup-input"><span>Hospital UV 2026</span></div>
                        <div class="mockup-btn-row">
                          <div class="mockup-btn mockup-btn-primary">Guardar</div>
                          <div class="mockup-btn mockup-btn-out">Cancelar</div>
                        </div>
                      </div>
                    </div>
                    <div class="paso-tips">
                      <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Para empezar, haz clic en <strong>Nuevo proyecto</strong> y elige <strong>Atención cerrada</strong> o <strong>Atención abierta</strong>.</div>
                      <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Cada proyecto necesita un nombre distinto. Si repites uno que ya usaste, el sistema te avisa.</div>
                      <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Haz clic en <strong>Guardar</strong> una sola vez. Mientras se guarda, el botón dice <em>"Guardando…"</em>.</div>
                      <div class="tip"><i class="fa-solid fa-lock tip-icon"></i> El proyecto queda guardado en tu cuenta. Nadie más puede verlo.</div>
                    </div>
                  </div>
                </article>

                <!-- PASO 2 -->
                <article v-else-if="slide === 1" key="paso-2" id="paso-2" class="slide">
                  <div class="slide-tag">Parte 1 · Equipamiento</div>
                  <div class="paso-header">
                    <div class="paso-num">Paso 2</div>
                    <div class="paso-icon"><i class="fa-solid fa-list-check"></i></div>
                    <div>
                      <h3 class="paso-title">Elige las prestaciones</h3>
                      <p class="paso-desc">Las prestaciones son las atenciones y procedimientos que hará el hospital, por ejemplo una cirugía o un día de hospitalización en UCI. Elige solo las que necesitas para tu estudio.</p>
                    </div>
                  </div>
                  <div class="paso-body">
                    <div class="instruccion-indicator">
                      <span class="instruccion-icon-circle"><i class="fa-solid fa-circle-info"></i></span>
                      <span class="instruccion-texto">
                        Aprieta <span class="instruccion-badge instruccion-badge--agregar"><i class="fa-solid fa-plus"></i></span> para agregar una prestación y
                        <span class="instruccion-badge instruccion-badge--quitar"><i class="fa-solid fa-xmark"></i></span> para quitarla.
                        Cuando termines, aprieta <strong>Guardar y confirmar</strong>.
                      </span>
                    </div>
                    <div class="filtros-panel-mockup">
                      <div class="filtro-mockup filtro-buscar-mockup">
                        <div class="filtro-label-mockup">Buscar</div>
                        <div class="mockup-input"><span class="mockup-placeholder">Código o nombre</span></div>
                      </div>
                      <div class="filtro-mockup">
                        <div class="filtro-label-mockup">Área</div>
                        <div class="mockup-select"><span>Todas</span> <i class="fa-solid fa-chevron-down"></i></div>
                      </div>
                      <div class="filtro-mockup">
                        <div class="filtro-label-mockup">Subárea</div>
                        <div class="mockup-select"><span>Todas</span> <i class="fa-solid fa-chevron-down"></i></div>
                      </div>
                      <div class="filtro-mockup">
                        <div class="filtro-label-mockup">Recinto</div>
                        <div class="mockup-select"><span>Todos</span> <i class="fa-solid fa-chevron-down"></i></div>
                      </div>
                    </div>
                    <div class="prestaciones-grid-mockup">
                      <div class="prestaciones-panel-mockup">
                        <div class="panel-title-mockup panel-title-fila">
                          <span>Disponibles</span>
                        </div>
                        <div class="prestaciones-lista-mockup">
                          <div class="prestacion-item-mockup">
                            <div class="prestacion-info-mockup">
                              <div class="prestacion-codigo-mockup">201201</div>
                              <div class="prestacion-nombre-mockup">Día Cama de Hospitalización Integral Adulto en Unidad de Cuidado Intensivo (U.C.I.)</div>
                            </div>
                            <div class="accion-mockup accion-agregar-mockup"><i class="fa-solid fa-plus"></i></div>
                          </div>
                          <div class="prestacion-item-mockup">
                            <div class="prestacion-info-mockup">
                              <div class="prestacion-codigo-mockup">1803001</div>
                              <div class="prestacion-nombre-mockup">Absceso ano rectal complejo, tratamiento quirúrgico</div>
                            </div>
                            <div class="accion-mockup accion-agregar-mockup"><i class="fa-solid fa-plus"></i></div>
                          </div>
                        </div>
                      </div>
                      <div class="prestaciones-panel-mockup">
                        <div class="panel-title-mockup panel-title-fila">
                          <span>Seleccionadas</span>
                          <span class="mockup-botones-panel">
                            <span class="mockup-btn-pill-borde mockup-btn-pill-verde"><i class="fa-solid fa-plus"></i> Agregar todas (265)</span>
                            <span class="mockup-btn-pill-borde mockup-btn-pill-rojo"><i class="fa-solid fa-trash"></i> Limpiar selección</span>
                          </span>
                        </div>
                        <div class="prestaciones-lista-mockup">
                          <div class="prestacion-item-mockup">
                            <div class="prestacion-info-mockup">
                              <div class="prestacion-codigo-mockup">1803002</div>
                              <div class="prestacion-nombre-mockup">Absceso anorrectal simple, trat. quir.</div>
                            </div>
                            <div class="accion-mockup accion-quitar-mockup"><i class="fa-solid fa-xmark"></i></div>
                          </div>
                          <div class="prestacion-item-mockup">
                            <div class="prestacion-info-mockup">
                              <div class="prestacion-codigo-mockup">1802053</div>
                              <div class="prestacion-nombre-mockup">Apendicectomía y/o dren. absceso apendicular (proc. aut.)</div>
                            </div>
                            <div class="accion-mockup accion-quitar-mockup"><i class="fa-solid fa-xmark"></i></div>
                          </div>
                        </div>
                      </div>
                    </div>
                    <div class="acciones-finales-mockup">
                      <div class="acciones-resumen-mockup">Prestaciones seleccionadas: <b>2</b></div>
                      <div class="mockup-btn mockup-btn-primary">Guardar y confirmar</div>
                    </div>
                  </div>
                  <div class="paso-tips">
                    <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Puedes buscar una prestación por su nombre o por su código FONASA, y filtrar por área, subárea o recinto.</div>
                    <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> ¿Quieres todas las de la lista? Aprieta <strong>Agregar todas</strong>; el número indica cuántas se van a agregar.</div>
                    <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Limpiar selección</strong> quita todas las prestaciones seleccionadas para empezar de nuevo.</div>
                    <div class="tip"><i class="fa-solid fa-triangle-exclamation tip-icon tip-warn"></i> ¿Vas a usar UCI o UTI? Agrega <strong>"Día Cama UCI"</strong> o <strong>"Día Cama UTI"</strong>. Sin eso, el sistema no puede calcular los cubículos.</div>
                  </div>
                </article>

                <!-- PASO 3 -->
                <article v-else-if="slide === 2" key="paso-3" id="paso-3" class="slide">
                  <div class="slide-tag">Parte 1 · Equipamiento</div>
                  <div class="paso-header">
                    <div class="paso-num">Paso 3</div>
                    <div class="paso-icon"><i class="fa-solid fa-sliders"></i></div>
                    <div>
                      <h3 class="paso-title">Ingresa los parámetros</h3>
                      <p class="paso-desc">Para cada prestación llena una fila con los datos del hospital. Con estos números el sistema sabe cuánto se va a usar cada recinto.</p>
                    </div>
                  </div>
                  <div class="paso-body">
                    <div class="mockup-calc-toggle-row">
                      <div class="mockup-calc-toggle">
                        <span class="mockup-calc-icono"><i class="fa-solid fa-calculator"></i></span>
                        <span class="mockup-calc-texto">Calculadora Atencion Cerrada Pabellones</span>
                        <i class="fa-solid fa-chevron-down mockup-calc-chevron"></i>
                      </div>
                      <p class="mockup-calc-desc">¿No sabes cuántos días cama poner en UCI o UTI? La calculadora te ayuda con el coeficiente técnico, la población que atiende el hospital y la estadía promedio.</p>
                    </div>
                    <div class="mockup-params-table">
                      <div class="mockup-params-head mockup-params-head-real">
                        <span>Prestación</span>
                        <span>Demanda</span>
                        <span>Días al año disponibles</span>
                        <span>Tiempo procedimiento (min)</span>
                        <span>Disponibilidad (%)</span>
                        <span>Jornada laboral (hrs)</span>
                      </div>
                      <div class="mockup-params-row mockup-params-row-real mockup-params-row-mid mockup-fila-aplicar">
                        <span class="mockup-aplicar-etiqueta">Mismo valor para todos</span>
                        <span class="mockup-aplicar-celda"><div class="mockup-input-sm"><span class="mockup-placeholder">—</span></div><span class="mockup-btn-aplicar">Aplicar</span></span>
                        <span class="mockup-aplicar-celda"><div class="mockup-input-sm"><span>250</span></div><span class="mockup-btn-aplicar">Aplicar</span></span>
                        <span class="mockup-aplicar-celda"><div class="mockup-input-sm"><span class="mockup-placeholder">—</span></div><span class="mockup-btn-aplicar">Aplicar</span></span>
                        <span class="mockup-aplicar-celda"><div class="mockup-input-sm"><span>85</span></div><span class="mockup-btn-aplicar">Aplicar</span></span>
                        <span class="mockup-aplicar-celda"><div class="mockup-input-sm"><span>8</span></div><span class="mockup-btn-aplicar">Aplicar</span></span>
                      </div>
                      <div class="mockup-params-row mockup-params-row-real mockup-params-row-mid">
                        <span class="mockup-row-name">Colecistectomía por videolaparoscopía, proc. completo</span>
                        <span><div class="mockup-input-sm">450</div></span>
                        <span><div class="mockup-input-sm">250</div></span>
                        <span><div class="mockup-input-sm">60</div></span>
                        <span><div class="mockup-input-sm">85</div></span>
                        <span><div class="mockup-input-sm">8</div></span>
                      </div>
                      <div class="mockup-params-row mockup-params-row-real">
                        <span class="mockup-row-name">Día Cama UCI</span>
                        <span><div class="mockup-input-sm">2.900</div></span>
                        <span><div class="mockup-input-sm">365</div></span>
                        <span><div class="mockup-input-sm mockup-input-bloqueado"><i class="fa-solid fa-lock"></i> 1440</div></span>
                        <span><div class="mockup-input-sm">85</div></span>
                        <span><div class="mockup-input-sm">24</div></span>
                      </div>
                    </div>
                    <div class="mockup-btn-row mockup-btn-row-fin">
                      <div class="mockup-btn mockup-btn-primary"><i class="fa-solid fa-calculator"></i> Calcular y ver Resultados</div>
                    </div>
                  </div>
                  <div class="paso-tips">
                    <!-- Izquierda: qué significa cada columna, en el mismo orden de la tabla -->
                    <div class="tips-col">
                      <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Demanda:</strong> cantidad de atenciones proyectadas para esta prestación al año.</div>
                      <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Días al año disponibles:</strong> n° de días disponibles al año para operar.</div>
                      <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Tiempo procedimiento (min):</strong> minutos sugeridos por procedimiento, puedes cambiarlo. El cambio se guarda solo en este proyecto.</div>
                      <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Disponibilidad (%):</strong> porcentaje de disponibilidad real del equipo para esta prestación.</div>
                      <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Jornada laboral (hrs):</strong> horas efectivas de operación al día.</div>
                    </div>
                    <!-- Derecha: avisos y atajos -->
                    <div class="tips-col">
                      <div class="tip"><i class="fa-solid fa-lock tip-icon"></i> En <strong>Día Cama UCI</strong> y <strong>Día Cama UTI</strong> el tiempo queda fijo en <strong>1440 min</strong> (24 horas), porque una cama se ocupa el día completo.</div>
                      <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> ¿Un dato es igual para todas las prestaciones? Escríbelo 1 sola vez en la fila de arriba y haz clic en <strong>Aplicar</strong>.</div>
                      <div class="tip"><i class="fa-solid fa-triangle-exclamation tip-icon tip-warn"></i> No dejes casillas vacías ni en 0. Si falta algo, el sistema te avisa.</div>
                    </div>
                  </div>
                </article>

                <!-- PASO 4 -->
                <article v-else-if="slide === 3" key="paso-4" id="paso-4" class="slide">
                  <div class="slide-tag">Parte 1 · Equipamiento</div>
                  <div class="paso-header">
                    <div class="paso-num">Paso 4</div>
                    <div class="paso-icon"><i class="fa-solid fa-chart-bar"></i></div>
                    <div>
                      <h3 class="paso-title">Mira los resultados de equipamiento</h3>
                      <p class="paso-desc">Aquí ves cuántos recintos y equipos necesita el hospital, separados por recinto. Este resultado supone que hay todo el personal necesario; en los pasos 5 a 7 revisas si de verdad alcanza.</p>
                    </div>
                  </div>
                  <div class="paso-body">
                    <div class="mockup-res-top">
                      <div>
                        <div class="mockup-res-titulo">Resultados</div>
                        <div class="mockup-res-sub">Resumen de equipamiento por recinto.</div>
                      </div>
                      <div class="mockup-btn-row">
                        <div class="mockup-btn mockup-btn-excel"><i class="fa-solid fa-file-excel"></i> Excel</div>
                        <div class="mockup-btn mockup-btn-pdf"><i class="fa-solid fa-file-pdf"></i> PDF</div>
                      </div>
                    </div>
                    <div class="mockup-banner-res">
                      <div>
                        <div class="mockup-banner-titulo">Proyecto</div>
                        <div class="mockup-banner-sub">Hospital UV 2026</div>
                      </div>
                      <div><span class="mockup-metric-val">202</span><span class="mockup-metric-lab">Equipos (total)</span></div>
                    </div>
                    <div class="mockup-res-block">
                      <div class="mockup-section-title">Recintos requeridos</div>
                      <div class="mockup-pill-container">
                        <div class="mockup-pill">Pabellón mayor <strong>3</strong></div>
                        <div class="mockup-pill">Pabellón menor <strong>1</strong></div>
                        <div class="mockup-pill">Cubículo UCI <strong>6</strong></div>
                        <div class="mockup-pill">Cubículo UTI <strong>4</strong></div>
                      </div>
                    </div>
                    <div class="mockup-res-block mockup-res-blanco">
                      <div class="mockup-label">Buscar equipo</div>
                      <div class="mockup-input"><span class="mockup-placeholder">Nombre de equipo</span></div>
                    </div>
                    <div class="mockup-resumen-barra">
                      <span>Resumen de equipos necesarios (total)</span>
                      <span class="mockup-vistas">
                        <i class="fa-solid fa-bars"></i>
                        <i class="fa-solid fa-table-cells-large"></i>
                        <i class="fa-solid fa-table-cells activo"></i>
                        <i class="fa-solid fa-chevron-down dropdown-chev"></i>
                      </span>
                    </div>
                    <div class="mockup-res-block mockup-res-blanco">
                      <div class="mockup-section-title">Desglose de equipamiento</div>
                      <div class="mockup-desglose">
                        <div class="mockup-accordion mockup-acc-recinto"><span>Cubículo UTI <span class="acc-num">(23)</span></span><i class="fa-solid fa-chevron-down dropdown-chev"></i></div>
                        <div class="mockup-accordion mockup-acc-recinto"><span>Pabellón menor <span class="acc-num">(20)</span></span><i class="fa-solid fa-chevron-down dropdown-chev"></i></div>
                        <div class="mockup-accordion mockup-acc-recinto"><span>Cubículo UCI <span class="acc-num">(32)</span></span><i class="fa-solid fa-chevron-down dropdown-chev"></i></div>
                        <div class="mockup-accordion mockup-acc-recinto"><span>Pabellón mayor <span class="acc-num">(42)</span></span><i class="fa-solid fa-chevron-down dropdown-chev"></i></div>
                      </div>
                      <div class="mockup-desglose mockup-desglose-extra">
                        <div class="mockup-accordion mockup-acc-normativa"><span><i class="fa-solid fa-file-medical"></i> Equipamiento por normativa y/o guías <span class="acc-num">(50)</span></span><i class="fa-solid fa-chevron-down dropdown-chev"></i></div>
                        <div class="mockup-accordion mockup-acc-urpa"><span><i class="fa-solid fa-bed-pulse"></i> URPA (Sala de recuperación post-anestésica) <span class="acc-num">(14)</span></span><i class="fa-solid fa-chevron-down dropdown-chev"></i></div>
                        <div class="mockup-accordion mockup-acc-especifico"><span><i class="fa-solid fa-stethoscope"></i> Equipamiento específico de prestaciones <span class="acc-num">(21)</span></span><i class="fa-solid fa-chevron-down dropdown-chev"></i></div>
                      </div>
                    </div>
                  </div>
                  <div class="paso-tips">
                    <div class="tips-col">
                      <div class="tip"><i class="fa-solid fa-download tip-icon"></i> Arriba, a la derecha, están los botones <strong>Excel</strong> y <strong>PDF</strong> para descargar el informe.</div>
                      <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Recintos requeridos:</strong> cuántos pabellones y cubículos necesita el hospital.</div>
                      <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Buscar equipo:</strong> escribe un nombre para filtrar el resumen.</div>
                      <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Resumen de equipos necesarios:</strong> el total de cada equipo. Con los íconos de la derecha lo ves como lista o en mosaico.</div>
                    </div>
                    <div class="tips-col">
                      <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Desglose de equipamiento:</strong> haz clic en un recinto para ver sus equipos. El número entre paréntesis es la cantidad de equipos.</div>
                      <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Abajo, en colores, están el <strong>equipamiento por normativa y/o guías</strong>, la <strong>URPA</strong> y el <strong>equipamiento específico de prestaciones</strong>.</div>
                      <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Si cambias los parámetros o las prestaciones, vuelve a apretar <strong>Calcular</strong> para actualizar los resultados.</div>
                      <div class="tip"><i class="fa-solid fa-arrow-right tip-icon"></i> Para seguir con el personal, aprieta <strong>RRHH</strong> en la barra de arriba o en <em>¿Qué sigue?</em> al final de la página.</div>
                    </div>
                  </div>
                </article>

                <!-- PASO 5 -->
                <article v-else-if="slide === 4" key="paso-5" id="paso-5" class="slide slide-rrhh">
                  <div class="slide-tag">Parte 2 · Recursos humanos</div>
                  <div class="paso-header">
                    <div class="paso-num paso-num-rrhh">Paso 5</div>
                    <div class="paso-icon paso-icon-rrhh"><i class="fa-solid fa-id-badge"></i></div>
                    <div>
                      <h3 class="paso-title">Ingresa el personal de cada recinto</h3>
                      <p class="paso-desc">Para cada recinto indica qué perfiles trabajan ahí, cuántas personas hay y cuánto tiempo trabajan. Solo aparecen los recintos de las prestaciones que elegiste en el paso 2.</p>
                    </div>
                  </div>
                  <div class="rrhh-intro">
                    <i class="fa-solid fa-people-group rrhh-intro-icon"></i>
                    <p class="rrhh-intro-desc">
                      <strong>¿Para qué sirve esta parte?</strong> Un equipo solo sirve si hay alguien que lo use: un pabellón necesita cirujano/a, anestesiólogo/a y arsenalera/o.
                      Con el personal que ingreses, el sistema calcula qué parte de la demanda alcanza a cubrir, cuántos equipos se pueden usar realmente y cuántas personas faltan.
                    </p>
                  </div>
                  <div class="paso-body">
                    <div class="mockup-recinto">
                      <div class="mockup-accordion mockup-accordion-abierto">
                        <span><i class="fa-solid fa-hospital-user"></i> Pabellón mayor</span>
                        <i class="fa-solid fa-chevron-up dropdown-chev"></i>
                      </div>
                      <div class="mockup-chips">
                        <span class="mockup-chip-perfil activo">Cirujano/a</span>
                        <span class="mockup-chip-perfil activo">Anestesiólogo/a</span>
                        <span class="mockup-chip-perfil">Arsenalera/o</span>
                        <span class="mockup-chip-perfil activo">Enfermera/o</span>
                        <span class="mockup-chip-perfil">TENS</span>
                        <span class="mockup-chip-perfil mockup-chip-nuevo"><i class="fa-solid fa-plus"></i> Perfil nuevo</span>
                      </div>
                      <div class="mockup-params-table">
                        <div class="mockup-params-head mockup-grid-rrhh">
                          <span>Perfil</span>
                          <span>Cantidad de personas</span>
                          <span>Jornada semanal (hrs)</span>
                          <span>Semanas efectivas al año</span>
                          <span>% de jornada para este equipo</span>
                          <span>Horas disponibles al año</span>
                        </div>
                        <div class="mockup-params-row mockup-grid-rrhh mockup-params-row-mid mockup-fila-aplicar">
                          <span class="mockup-aplicar-etiqueta">Mismo valor para todos</span>
                          <span class="mockup-aplicar-celda"><div class="mockup-input-sm"><span class="mockup-placeholder">0</span></div><span class="mockup-btn-aplicar">Aplicar</span></span>
                          <span class="mockup-aplicar-celda"><div class="mockup-input-sm"><span>44</span></div><span class="mockup-btn-aplicar">Aplicar</span></span>
                          <span class="mockup-aplicar-celda"><div class="mockup-input-sm"><span>48</span></div><span class="mockup-btn-aplicar">Aplicar</span></span>
                          <span class="mockup-aplicar-celda"><div class="mockup-input-sm"><span>100</span></div><span class="mockup-btn-aplicar">Aplicar</span></span>
                          <span></span>
                        </div>
                        <div class="mockup-params-row mockup-grid-rrhh mockup-params-row-mid">
                          <span class="mockup-row-name">Cirujano/a</span>
                          <span><div class="mockup-input-sm">2</div></span>
                          <span><div class="mockup-input-sm">44</div></span>
                          <span><div class="mockup-input-sm">48</div></span>
                          <span><div class="mockup-input-sm">100</div></span>
                          <span><div class="mockup-calculado">4.224</div></span>
                        </div>
                        <div class="mockup-params-row mockup-grid-rrhh mockup-params-row-mid">
                          <span class="mockup-row-name">Anestesiólogo/a</span>
                          <span><div class="mockup-input-sm">1</div></span>
                          <span><div class="mockup-input-sm">44</div></span>
                          <span><div class="mockup-input-sm">48</div></span>
                          <span><div class="mockup-input-sm">100</div></span>
                          <span><div class="mockup-calculado">2.112</div></span>
                        </div>
                        <div class="mockup-params-row mockup-grid-rrhh">
                          <span class="mockup-row-name">Enfermera/o</span>
                          <span><div class="mockup-input-sm">3</div></span>
                          <span><div class="mockup-input-sm">44</div></span>
                          <span><div class="mockup-input-sm">48</div></span>
                          <span><div class="mockup-input-sm">100</div></span>
                          <span><div class="mockup-calculado">6.336</div></span>
                        </div>
                      </div>
                    </div>
                    <div class="mockup-btn-row mockup-btn-row-fin">
                      <div class="mockup-btn mockup-btn-primary">Guardar y seguir <i class="fa-solid fa-arrow-right"></i></div>
                    </div>
                  </div>
                  <div class="paso-tips">
                    <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Aprieta los perfiles que trabajan en el recinto para agregar su fila; si lo vuelves a apretar, se quita. ¿Falta un perfil? Usa <strong>+ Perfil nuevo</strong>.</div>
                    <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Los recintos aparecen abiertos. Haz clic en el nombre de uno para cerrarlo.</div>
                    <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Jornada semanal:</strong> horas a la semana de cada persona. Se elige entre 44, 33, 22 y 11.</div>
                    <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Semanas efectivas al año:</strong> las que trabaja de verdad, descontando vacaciones y permisos. Por ejemplo, 48.</div>
                    <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>% de jornada para este equipo:</strong> si una persona reparte su tiempo entre dos recintos, pon, por ejemplo, 50 en cada uno.</div>
                    <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Horas disponibles al año</strong> se calcula sola: personas × jornada × semanas × %.</div>
                    <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> ¿Mismo valor para todos los perfiles? Escríbelo en la fila <strong>Mismo valor para todos</strong> y aprieta <strong>Aplicar</strong>.</div>
                    <div class="tip"><i class="fa-solid fa-triangle-exclamation tip-icon tip-warn"></i> Si no aparece ningún recinto, vuelve al paso 2 y revisa que elegiste prestaciones.</div>
                  </div>
                </article>

                <!-- PASO 6 -->
                <article v-else-if="slide === 5" key="paso-6" id="paso-6" class="slide slide-rrhh">
                  <div class="slide-tag">Parte 2 · Recursos humanos</div>
                  <div class="paso-header">
                    <div class="paso-num paso-num-rrhh">Paso 6</div>
                    <div class="paso-icon paso-icon-rrhh"><i class="fa-solid fa-user-clock"></i></div>
                    <div>
                      <h3 class="paso-title">Indica cuánto participa cada perfil en cada prestación</h3>
                      <p class="paso-desc">No todo el personal está presente durante toda la prestación. Para cada prestación marca los perfiles que participan y si lo hacen todo el tiempo o solo una parte.</p>
                    </div>
                  </div>
                  <div class="paso-body">
                    <div class="modo-cards">
                      <div class="modo-card">
                        <div class="modo-chip modo-chip-perm"><i class="fa-solid fa-infinity"></i> Permanente</div>
                        <p>Está <strong>durante toda la prestación</strong>. Por ejemplo, el cirujano durante una cirugía.</p>
                      </div>
                      <div class="modo-card">
                        <div class="modo-chip modo-chip-punt"><i class="fa-solid fa-stopwatch"></i> Puntual</div>
                        <p>Participa <strong>solo en algunos momentos</strong>. Por ejemplo, un médico que pasa visita en UCI algunas veces al día.</p>
                      </div>
                    </div>

                    <div class="mockup-recinto">
                      <div class="mockup-accordion mockup-accordion-abierto">
                        <span><i class="fa-solid fa-hospital-user"></i> Pabellón mayor recuperación <span class="acc-num mockup-tp-cuenta">4 prestaciones</span></span>
                        <i class="fa-solid fa-chevron-up dropdown-chev"></i>
                      </div>
                      <div class="mockup-tp-masivo">
                        <div class="mockup-tp-masivo-titulo">Aplicar a todas las prestaciones de este recinto: <i class="fa-solid fa-circle-info"></i></div>
                        <div class="mockup-tp-controles">
                          <div class="mockup-select"><span>Cirujano/a</span> <i class="fa-solid fa-chevron-down"></i></div>
                          <div class="mockup-segmento"><span class="activo">Permanente</span><span>Puntual</span></div>
                          <div class="mockup-btn mockup-btn-primary mockup-btn-sm">Aplicar</div>
                          <div class="mockup-btn mockup-btn-out mockup-btn-sm mockup-btn-suave">Quitar de todas</div>
                        </div>
                      </div>
                      <div class="mockup-tp-prestacion">
                        <div class="mockup-tp-cabecera">
                          <span class="mockup-tp-titulo">Apendicectomía y/o dren. absceso apendicular (proc. aut.)</span>
                          <span class="mockup-tp-datos">Código FONASA 1802053 &nbsp; t = 2,7 h <i class="fa-solid fa-circle-info"></i> &nbsp; Demanda 700/año</span>
                        </div>
                        <div class="mockup-chips mockup-chips-tp">
                          <span class="mockup-chip-perfil activo">Cirujano/a</span>
                          <span class="mockup-chip-perfil activo">Anestesiólogo/a</span>
                          <span class="mockup-chip-perfil activo">Enfermera/o</span>
                          <span class="mockup-chip-perfil activo">TENS</span>
                          <span class="mockup-chip-perfil activo">Arsenalera/o</span>
                          <span class="mockup-chip-perfil mockup-chip-nuevo">+ Otro perfil <i class="fa-solid fa-chevron-down"></i></span>
                        </div>
                        <div class="mockup-tp-fila">
                          <span class="mockup-tp-nombre">Cirujano/a</span>
                          <div class="mockup-segmento"><span class="activo">Permanente</span><span>Puntual</span></div>
                          <span class="mockup-calculado mockup-phi">φ = 1,00</span>
                          <i class="fa-solid fa-circle-info mockup-tp-info"></i>
                          <i class="fa-solid fa-trash mockup-tp-quitar"></i>
                        </div>
                        <div class="mockup-tp-fila">
                          <span class="mockup-tp-nombre">Anestesiólogo/a</span>
                          <div class="mockup-segmento"><span class="activo">Permanente</span><span>Puntual</span></div>
                          <span class="mockup-calculado mockup-phi">φ = 1,00</span>
                          <i class="fa-solid fa-circle-info mockup-tp-info"></i>
                          <i class="fa-solid fa-trash mockup-tp-quitar"></i>
                        </div>
                        <div class="mockup-tp-fila">
                          <span class="mockup-tp-nombre">Enfermera/o</span>
                          <div class="mockup-segmento"><span>Permanente</span><span class="activo">Puntual</span></div>
                          <span class="mockup-ef">e <span class="mockup-input-sm">20</span> min &nbsp; f <span class="mockup-input-sm">3</span> veces/día</span><span class="mockup-calculado mockup-phi">φ = 0,38</span>
                          <i class="fa-solid fa-circle-info mockup-tp-info"></i>
                          <i class="fa-solid fa-trash mockup-tp-quitar"></i>
                        </div>
                        <div class="mockup-tp-fila">
                          <span class="mockup-tp-nombre">TENS</span>
                          <div class="mockup-segmento"><span class="activo">Permanente</span><span>Puntual</span></div>
                          <span class="mockup-calculado mockup-phi">φ = 1,00</span>
                          <i class="fa-solid fa-circle-info mockup-tp-info"></i>
                          <i class="fa-solid fa-trash mockup-tp-quitar"></i>
                        </div>
                        <div class="mockup-tp-fila">
                          <span class="mockup-tp-nombre">Arsenalera/o</span>
                          <div class="mockup-segmento"><span class="activo">Permanente</span><span>Puntual</span></div>
                          <span class="mockup-calculado mockup-phi">φ = 1,00</span>
                          <i class="fa-solid fa-circle-info mockup-tp-info"></i>
                          <i class="fa-solid fa-trash mockup-tp-quitar"></i>
                        </div>
                      </div>
                    </div>

                    <div class="mockup-btn-row mockup-btn-row-fin">
                      <div class="mockup-btn mockup-btn-primary">Guardar y ver resultados <i class="fa-solid fa-arrow-right"></i></div>
                    </div>
                  </div>
                  <div class="paso-tips">
                    <div class="tips-col">
                      <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> En cada prestación aprieta los perfiles que participan. Cada uno aparece en una fila.</div>
                      <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Permanente:</strong> el sistema cuenta todo el tiempo de la prestación (φ = 1). No tienes que llenar más datos.</div>
                      <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Puntual:</strong> completa <strong>e</strong>, los minutos que dura cada intervención, y <strong>f</strong>, cuántas veces al día interviene. Por ejemplo, 20 minutos, 3 veces.</div>
                      <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>t</strong> es lo que dura la prestación. Aparece arriba a la derecha, junto al código FONASA y la demanda.</div>
                      <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>φ</strong> es la parte de ese tiempo en que la persona está ocupada. Va de 0 a 1; si da más de 1 se marca en rojo y debes revisar e y f.</div>
                    </div>
                    <div class="tips-col">
                      <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Aplicar a todas las prestaciones de este recinto:</strong> elige el perfil y su participación y aprieta <strong>Aplicar</strong>. Con <strong>Quitar de todas</strong> lo sacas de todas las prestaciones.</div>
                      <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>+ Otro perfil</strong> agrega un perfil que no ingresaste en el paso 5. Aparece con borde naranjo, porque ese recinto no tiene personal de ese perfil.</div>
                      <div class="tip"><i class="fa-solid fa-trash tip-icon"></i> El basurero quita el perfil de esa prestación.</div>
                      <div class="tip"><i class="fa-solid fa-circle-question tip-icon"></i> ¿Dudas con algún dato? Haz clic en el ícono <i class="fa-solid fa-circle-info"></i> para ver una explicación.</div>
                    </div>
                  </div>
                </article>

                <!-- PASO 7 -->
                <article v-else-if="slide === 6" key="paso-7" id="paso-7" class="slide slide-rrhh">
                  <div class="slide-tag">Parte 2 · Recursos humanos</div>
                  <div class="paso-header">
                    <div class="paso-num paso-num-rrhh">Paso 7</div>
                    <div class="paso-icon paso-icon-rrhh"><i class="fa-solid fa-scale-balanced"></i></div>
                    <div>
                      <h3 class="paso-title">Mira los resultados con el personal disponible</h3>
                      <p class="paso-desc">Aquí ves cuánto equipamiento se puede usar con el personal que ingresaste y cuántas personas faltan para cubrir toda la demanda.</p>
                    </div>
                  </div>
                  <div class="paso-body">
                    <div class="mockup-kpis">
                      <div class="mockup-kpi">
                        <span class="mockup-kpi-lab">Recintos</span>
                        <span class="mockup-kpi-val">3 <span class="mockup-kpi-flecha">→</span> 2</span>
                      </div>
                      <div class="mockup-kpi">
                        <span class="mockup-kpi-lab">Equipamiento médico</span>
                        <span class="mockup-kpi-val">120 <span class="mockup-kpi-flecha">→</span> 98</span>
                      </div>
                      <div class="mockup-kpi mockup-kpi-alerta">
                        <span class="mockup-kpi-lab">Personal que falta</span>
                        <span class="mockup-kpi-val">1 <small>Anestesiólogo/a</small></span>
                      </div>
                      <div class="mockup-kpi mockup-kpi-alerta">
                        <span class="mockup-kpi-lab">Prestaciones no cubiertas</span>
                        <span class="mockup-kpi-val">126 <small>al año</small></span>
                      </div>
                    </div>

                    <div class="mockup-table">
                      <div class="mockup-table-head mockup-grid-equipos">
                        <span>Equipo</span><span>Sin restricción</span><span>Con restricción</span><span>Diferencia</span>
                      </div>
                      <div class="mockup-table-row mockup-grid-equipos">
                        <span class="mockup-row-name">Máquina de anestesia</span><span>3</span><span>2</span><span class="mockup-dif">−1</span>
                      </div>
                      <div class="mockup-table-row mockup-grid-equipos">
                        <span class="mockup-row-name">Mesa quirúrgica</span><span>3</span><span>2</span><span class="mockup-dif">−1</span>
                      </div>
                      <div class="mockup-table-row mockup-grid-equipos">
                        <span class="mockup-row-name">Camilla URPA</span><span>6</span><span>4</span><span class="mockup-dif">−2</span>
                      </div>
                    </div>
                    <div class="mockup-btn-row">
                      <div class="mockup-btn mockup-btn-excel"><i class="fa-solid fa-file-excel"></i> Excel</div>
                      <div class="mockup-btn mockup-btn-pdf"><i class="fa-solid fa-file-pdf"></i> PDF</div>
                    </div>
                  </div>
                  <div class="paso-tips">
                    <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Cada tarjeta compara dos números: la <strong>necesidad tecnológica</strong> (la del paso 4) y lo que se puede usar <strong>con el personal actual</strong>.</div>
                    <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Personal que falta:</strong> cuántas personas habría que sumar, por perfil, para cubrir toda la demanda.</div>
                    <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Prestaciones no cubiertas:</strong> atenciones al año que no se alcanzan a hacer con el personal actual.</div>
                    <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> En <strong>Ver detalle por recinto y equipo</strong> aparece <strong>α</strong>, la parte de la demanda que se alcanza a cubrir, y el perfil que limita. <em>Sin restricción</em> quiere decir que el personal alcanza.</div>
                    <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Las camillas de URPA también se ajustan, porque dependen de los pabellones.</div>
                    <div class="tip"><i class="fa-solid fa-lightbulb tip-icon tip-warn"></i> Prueba cambiar el personal en el paso 5 y vuelve a ver los resultados. Así sabes cuánta gente contratar para cubrir toda la demanda.</div>
                  </div>
                </article>

                <!-- PASO 8 -->
                <article v-else-if="slide === 7" key="paso-8" id="paso-8" class="slide">
                  <div class="slide-tag">Tus proyectos</div>
                  <div class="paso-header">
                    <div class="paso-num">Paso 8</div>
                    <div class="paso-icon"><i class="fa-solid fa-folder-open"></i></div>
                    <div>
                      <h3 class="paso-title">Vuelve a tus proyectos</h3>
                      <p class="paso-desc">Tus proyectos quedan guardados y puedes abrirlos cuando quieras. También puedes cambiarles las prestaciones, los datos o el personal sin empezar de cero.</p>
                    </div>
                  </div>
                  <div class="paso-body">
                    <div class="mockup-table">
                      <div class="mockup-table-head">
                        <span>Nombre del proyecto</span><span>Fecha</span><span>Tipo</span><span>Acciones</span>
                      </div>
                      <div class="mockup-table-row">
                        <span class="mockup-row-name">Hospital UV 2026</span>
                        <span>12/06/2026</span>
                        <span><div class="mockup-chip">Atención cerrada</div></span>
                        <span class="mockup-actions"><div class="mockup-btn mockup-btn-sm mockup-btn-primary">Ver</div></span>
                      </div>
                    </div>
                    <div class="mockup-action-cards">
                      <div class="mockup-action-card">
                        <div class="mockup-btn mockup-btn-primary"><i class="fa-solid fa-sliders"></i> Editar parámetros</div>
                        <p>Cambia los números del estudio sin empezar de cero.</p>
                      </div>
                      <div class="mockup-action-card">
                        <div class="mockup-btn mockup-btn-primary"><i class="fa-solid fa-list-check"></i> Modificar prestaciones</div>
                        <p>Agrega o quita prestaciones y vuelve a calcular.</p>
                      </div>
                      <div class="mockup-action-card">
                        <div class="mockup-btn mockup-btn-primary paso-num-rrhh"><i class="fa-solid fa-user-nurse"></i> RRHH</div>
                        <p>Cambia el personal o sus tiempos y mira cómo cambian los resultados.</p>
                      </div>
                    </div>
                  </div>
                  <div class="paso-tips">
                    <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Aprieta <strong>Ver</strong> para abrir un proyecto. Puedes ordenarlos por fecha o nombre y filtrarlos por tipo de atención.</div>
                    <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Desde los resultados puedes volver a cambiar los datos, las prestaciones o el personal.</div>
                    <div class="tip"><i class="fa-solid fa-triangle-exclamation tip-icon tip-warn"></i> Si cambias las prestaciones, revisa también el personal (paso 5) y los tiempos (paso 6): pueden aparecer recintos o prestaciones nuevas.</div>
                    <div class="tip"><i class="fa-solid fa-lock tip-icon"></i> Solo tú ves tus proyectos, porque están en tu cuenta.</div>
                  </div>
                </article>
              </Transition>
            </div>

            <div class="carrusel-puntos" role="tablist" aria-label="Elegir paso">
              <button
                v-for="(step, i) in flujoGeneral"
                :key="step.id"
                type="button"
                role="tab"
                class="punto"
                :class="{ activo: slide === i }"
                :aria-selected="slide === i"
                :aria-label="`Paso ${i + 1}: ${step.label}`"
                @click="irA(i)"
              ></button>
            </div>
          </section>

          <!-- NAVEGACIÓN Y PREGUNTAS FRECUENTES, LADO A LADO -->
          <div class="manual-extra">
            <section class="manual-navbar-guide">
              <h3 class="guide-title"><i class="fa-solid fa-compass"></i> Cómo moverte por la página</h3>
              <p class="guide-desc">Arriba de cada pantalla tienes estos botones:</p>
              <div class="navbar-guide-row">
                <div class="navbar-guide-item">
                  <div class="mockup-btn mockup-btn-primary mockup-btn-pill"><i class="fa-solid fa-arrow-left"></i> Volver</div>
                  <p>Te lleva a la pantalla anterior.</p>
                </div>
                <div class="navbar-guide-item">
                  <div class="mockup-btn mockup-btn-primary mockup-btn-pill"><i class="fa-solid fa-house-user"></i> Inicio</div>
                  <p>Te lleva a la pantalla de inicio.</p>
                </div>
                <div class="navbar-guide-item">
                  <div class="mockup-session">
                    <i class="fa-solid fa-circle-user"></i>
                    <span>usuario@uv.cl</span>
                    <i class="fa-solid fa-right-from-bracket mockup-btn-logout"></i>
                  </div>
                  <p>Tu cuenta. El ícono <i class="fa-solid fa-right-from-bracket"></i> cierra la sesión.</p>
                </div>
              </div>
            </section>

            <section class="manual-navbar-guide">
              <h3 class="guide-title"><i class="fa-solid fa-circle-question"></i> Preguntas frecuentes</h3>
              <div class="faq-lista">
                <details class="faq-item" v-for="(f, i) in preguntas" :key="i">
                  <summary>{{ f.p }}</summary>
                  <p>{{ f.r }}</p>
                </details>
              </div>
            </section>
          </div>

        </template>

        <!-- ===================== PESTAÑA VIDEO ===================== -->
        <section v-else class="video-card">
          <div class="video-wrap">
            <iframe
              v-if="VIDEO_YOUTUBE_ID"
              :src="`https://www.youtube-nocookie.com/embed/${VIDEO_YOUTUBE_ID}?rel=0`"
              title="Manual de usuario EPHDEM"
              frameborder="0"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowfullscreen
            ></iframe>
            <video v-else-if="VIDEO_MP4" :src="VIDEO_MP4" controls preload="metadata"></video>
            <div v-else class="video-placeholder">
              <i class="fa-solid fa-circle-play"></i>
              <span>Video del manual disponible próximamente</span>
            </div>
          </div>
          <p class="video-desc">Recorre en video los pasos para usar EPHDEM, desde crear un proyecto hasta descargar los resultados.</p>
        </section>

      </main>
    </div>
  </AppLayout>
</template>

<script setup>
import { ref, onMounted, onBeforeUnmount } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import AppLayout from '@/layouts/AppLayout.vue'

const router = useRouter()
const authStore = useAuthStore()

// Pestañas del manual
const pestanas = [
  { id: 'lectura', label: 'Lectura', icon: 'fa-solid fa-book-open' },
  { id: 'video',   label: 'Video',   icon: 'fa-solid fa-circle-play' },
]
const pestana = ref('lectura')

// Video del manual: completar UNA de las dos opciones
const VIDEO_YOUTUBE_ID = ''  // solo el ID de YouTube, ej: 'AbC123xYz'
const VIDEO_MP4 = ''         // o un .mp4 del servidor, ej: '/__v2/modulo_eph/docs/manual.mp4'

const flujoGeneral = [
  { icon: 'fa-solid fa-plus-circle',     label: 'Crear proyecto',          id: 'paso-1' },
  { icon: 'fa-solid fa-list-check',      label: 'Elegir prestaciones',     id: 'paso-2' },
  { icon: 'fa-solid fa-sliders',         label: 'Completar datos',         id: 'paso-3' },
  { icon: 'fa-solid fa-chart-bar',       label: 'Ver equipamiento',        id: 'paso-4' },
  { icon: 'fa-solid fa-id-badge',        label: 'Ingresar personal',       id: 'paso-5', rrhh: true },
  { icon: 'fa-solid fa-user-clock',      label: 'Tiempos del personal',    id: 'paso-6', rrhh: true },
  { icon: 'fa-solid fa-scale-balanced',  label: 'Resultados con personal', id: 'paso-7', rrhh: true },
  { icon: 'fa-solid fa-folder-open',     label: 'Mis proyectos',           id: 'paso-8' },
]

const preguntas = [
  { p: '¿Por qué en el paso 7 hay menos equipos que en el paso 4?',
    r: 'El paso 4 calcula los equipos suponiendo que hay todo el personal necesario. El paso 7 usa el personal que ingresaste: si no alcanza para cubrir toda la demanda, tampoco se pueden usar todos los equipos.' },
  { p: '¿Qué significa «Sin restricción» en un recinto?',
    r: 'Que el personal de ese recinto alcanza para toda la demanda (α = 1), así que no se pierde ningún equipo.' },
  { p: '¿Por qué no puedo cambiar el tiempo de Día Cama UCI o UTI?',
    r: 'Porque una cama se ocupa el día completo. El tiempo queda fijo en 1440 minutos (24 horas) para que el cálculo de cubículos sea correcto.' },
  { p: 'Cambié el tiempo de procedimiento, ¿afecta a otros proyectos?',
    r: 'No. Cada proyecto guarda sus propios tiempos. El cambio solo se aplica al proyecto en el que estás.' },
  { p: 'No me aparece ningún recinto en Recursos humanos, ¿qué pasó?',
    r: 'Los recintos salen de las prestaciones que elegiste. Revisa el paso 2, guarda las prestaciones, calcula en el paso 3 y vuelve a entrar.' },
  { p: 'Me equivoqué en un dato, ¿tengo que empezar de nuevo?',
    r: 'No. Abre el proyecto desde Mis proyectos, cambia el dato y vuelve a calcular. Todo lo demás se mantiene.' },
]

// Carrusel de pasos
const slide = ref(0)
const direccion = ref('slide-izq') // sentido de la animación

function irA(i) {
  if (i < 0 || i >= flujoGeneral.length || i === slide.value) return
  direccion.value = i > slide.value ? 'slide-izq' : 'slide-der'
  slide.value = i
}
function siguiente() { irA(slide.value + 1) }
function anterior() { irA(slide.value - 1) }

// Flechas del teclado (solo en la pestaña Lectura y fuera de campos de texto)
function onTecla(e) {
  if (pestana.value !== 'lectura') return
  const tag = (e.target && e.target.tagName) || ''
  if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return
  if (e.key === 'ArrowRight') siguiente()
  else if (e.key === 'ArrowLeft') anterior()
}
onMounted(() => window.addEventListener('keydown', onTecla))
onBeforeUnmount(() => window.removeEventListener('keydown', onTecla))

function volverAtras() {
  router.back()
  setTimeout(() => window.scrollTo({ top: 0, left: 0, behavior: 'auto' }), 0)
}

function cerrarSesion() {
  authStore.logout()
  router.push('/login')
}
</script>

<style lang="scss" scoped>
@import '@/assets/styles/variables';

/* ===================== Página ===================== */
.manual-page { background: $color-fondo; flex: 1; }
.manual-content { width: 100%; max-width: 1320px; margin: 28px auto 56px; padding: 0 24px; box-sizing: border-box; display: flex; flex-direction: column; gap: 20px; }

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

/* ===================== Pestañas ===================== */
.tabs-manual { display: flex; justify-content: center; gap: 40px; border-bottom: 1px solid $color-borde; }
.tab-manual {
  background: none; border: none; cursor: pointer;
  display: flex; flex-direction: column; align-items: center; gap: 4px;
  padding: 4px 4px 10px; margin-bottom: -1px;
  color: $color-texto-secundario; font-size: 0.95rem; font-weight: 500;
  border-bottom: 3px solid transparent;
  i { font-size: 1.15rem; }
  &:hover { color: $color-primario; }
  &.activa { color: $color-primario; border-bottom-color: $color-primario; font-weight: 600; }
  &:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(42,159,214,0.3); border-radius: 6px; }
}

/* ===================== Introducción ===================== */
.manual-intro { display: flex; align-items: flex-start; gap: 16px; background: #fff; border-radius: 14px; padding: 18px 22px; border: 1px solid $color-borde; box-shadow: 0 8px 18px $color-sombra-suave; }
.intro-icon { font-size: 1.6rem; color: $color-primario; flex: 0 0 auto; }
.intro-title { font-size: 1.2rem; font-weight: 700; color: $color-primario; margin: 0 0 6px; }
.intro-desc { margin: 0; color: $color-texto-secundario; line-height: 1.55; font-size: 0.93rem; }
.intro-partes { display: flex; flex-wrap: wrap; gap: 8px 24px; margin-top: 10px; }
.intro-parte { display: flex; align-items: baseline; gap: 10px; color: $color-texto-secundario; line-height: 1.45; font-size: 0.9rem; flex: 1 1 320px; }
.intro-parte-num { flex: 0 0 auto; background: rgba(0,60,88,0.08); color: $color-primario; border-radius: 6px; padding: 2px 10px; font-size: 0.78rem; font-weight: 700; }
.intro-parte-num-rrhh { color: $color-secundario; }

/* ===================== Flujo ===================== */
.flujo-row { display: flex; align-items: center; justify-content: center; gap: 4px; flex-wrap: wrap; background: #fff; border-radius: 14px; padding: 10px 14px; border: 1px solid $color-borde; box-shadow: 0 8px 18px $color-sombra-suave; }
.flujo-step { display: flex; align-items: center; gap: 6px; cursor: pointer; padding: 4px 6px; border-radius: 8px; transition: background .2s; }
.flujo-step.activo { background: rgba(0,60,88,0.08); }
.flujo-step.activo .flujo-label { text-decoration: underline; text-underline-offset: 4px; }
.flujo-num { width: 24px; height: 24px; border-radius: 50%; background: $color-primario; color: #fff; font-weight: 700; font-size: 0.78rem; display: flex; align-items: center; justify-content: center; flex: 0 0 auto; }
.flujo-num-rrhh { background: $color-secundario; }
.flujo-icon { font-size: 0.95rem; color: $color-primario; }
.flujo-label { font-size: 0.82rem; font-weight: 600; color: $color-primario; }
.flujo-label-link { &:hover { text-decoration: underline; } }
.flujo-arrow { color: $color-borde; font-size: 0.7rem; margin-left: 2px; }

/* ===================== Carrusel ===================== */
.carrusel { display: flex; flex-direction: column; align-items: center; gap: 14px; }
.carrusel-marco { position: relative; width: 100%; }

.slide {
  background: #fff; border-radius: 14px; padding: 22px 44px;
  border: 1px solid $color-borde; box-shadow: 0 8px 18px $color-sombra-suave;
  display: flex; flex-direction: column; gap: 16px;
}
.slide-rrhh { border-left: 4px solid $color-secundario; }
.slide-tag { font-size: .74rem; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: $color-texto-secundario; margin-bottom: -6px; }
.slide-rrhh .slide-tag { color: $color-secundario; }

.carrusel-flecha {
  position: absolute; top: 64px; z-index: 5; width: 40px; height: 40px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  background: #fff; border: 1px solid $color-borde; color: $color-primario; font-size: 0.9rem;
  box-shadow: 0 4px 14px rgba(0,30,45,.18); cursor: pointer; transition: transform .15s, box-shadow .15s, opacity .15s;
  &.izquierda { left: -16px; }
  &.derecha { right: -16px; }
  &:hover:not(:disabled) { transform: scale(1.07); box-shadow: 0 6px 18px rgba(0,30,45,.25); }
  &:disabled { opacity: .35; cursor: default; }
  &:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(42,159,214,0.4); }
}

.carrusel-puntos { display: flex; gap: 8px; }
.punto {
  width: 26px; height: 5px; padding: 0; border: none; border-radius: 999px; cursor: pointer;
  background: #d4dbe1; transition: width .25s, background .25s;
  &.activo { width: 40px; background: $color-primario; }
  &:hover:not(.activo) { background: #b9c4cd; }
  &:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(42,159,214,0.4); }
}

.slide-izq-enter-active, .slide-izq-leave-active,
.slide-der-enter-active, .slide-der-leave-active { transition: opacity .22s ease, transform .22s ease; }
.slide-izq-enter-from { opacity: 0; transform: translateX(40px); }
.slide-izq-leave-to   { opacity: 0; transform: translateX(-40px); }
.slide-der-enter-from { opacity: 0; transform: translateX(-40px); }
.slide-der-leave-to   { opacity: 0; transform: translateX(40px); }
@media (prefers-reduced-motion: reduce) {
  .slide-izq-enter-active, .slide-izq-leave-active, .slide-der-enter-active, .slide-der-leave-active { transition: none; }
}

/* ===================== Encabezado, cuerpo y consejos del paso ===================== */
.paso-header { display: flex; align-items: flex-start; gap: 14px; }
.paso-num { background: $color-primario; color: #fff; border-radius: 8px; padding: 3px 10px; font-weight: 700; font-size: 0.8rem; flex: 0 0 auto; }
.paso-num-rrhh { background: $color-secundario; }
.paso-icon { font-size: 1.5rem; color: $color-primario; flex: 0 0 auto; }
.paso-icon-rrhh { color: $color-secundario; }
.paso-title { font-size: 1.1rem; font-weight: 700; color: $color-primario; margin: 0 0 4px; }
.paso-desc { margin: 0; color: $color-texto-secundario; line-height: 1.55; font-size: 0.92rem; }
.paso-body { display: flex; flex-direction: column; gap: 12px; min-width: 0; }

/* Pasos con imagen a la izquierda y consejos a la derecha */
.paso-cols { display: grid; grid-template-columns: minmax(0, 1.1fr) minmax(0, 1fr); gap: 24px; align-items: start; }

/* Consejos en dos columnas (una sola cuando van al lado de la imagen) */
.paso-tips { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px 28px; padding-top: 14px; border-top: 1px dashed $color-borde; }
.paso-cols .paso-tips { grid-template-columns: 1fr; padding-top: 0; border-top: none; }
.tips-col { display: flex; flex-direction: column; gap: 8px; }
.tip { position: relative; padding-left: 22px; font-size: 0.86rem; color: $color-texto-secundario; line-height: 1.5; }
.tip-icon { position: absolute; left: 0; top: 3px; color: $color-primario; }
.tip-warn { color: #d97706; }


/* ===================== Mockups ===================== */
.mockup-nuevo-row { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.mockup-btn-nuevo { background: $color-primario; color: #fff; border-radius: 10px; padding: 9px 16px; font-weight: 700; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; }
.mockup-submenu { display: flex; flex-direction: column; gap: 6px; background: #fff; border: 1px solid $color-borde; border-radius: 12px; padding: 8px; box-shadow: 0 8px 20px rgba(0,30,45,0.12); }
.mockup-submenu-item { padding: 6px 12px; border-radius: 8px; font-size: 0.86rem; font-weight: 600; color: $color-texto-secundario; }
.mockup-submenu-active { background: rgba(0,60,88,0.08); color: $color-primario; }
.mockup-form-proyecto { display: flex; flex-direction: column; gap: 8px; background: $color-claro; border-radius: 12px; padding: 14px; border: 1px solid $color-borde; }
.mockup-label { font-size: 0.82rem; font-weight: 600; color: $color-primario; }
.mockup-input { background: #fff; border: 1.5px solid $color-borde; border-radius: 8px; padding: 8px 12px; font-size: 0.9rem; color: $color-texto-principal; }
.mockup-placeholder { color: $color-texto-secundario; }

.mockup-btn-row { display: flex; gap: 10px; flex-wrap: wrap; }
.mockup-btn-row-fin { justify-content: flex-end; }
.mockup-btn { padding: 7px 14px; border-radius: 8px; font-weight: 700; font-size: 0.86rem; display: inline-flex; align-items: center; gap: 6px; white-space: nowrap; }
.mockup-btn-primary { background: $color-primario; color: #fff; }
.mockup-btn-out { background: rgba(0,60,88,0.08); color: $color-primario; border: 1px solid rgba(0,60,88,0.2); }
.mockup-btn-sm { padding: 5px 12px; font-size: 0.8rem; }
.mockup-btn-pill { border-radius: 999px; padding: 6px 12px; }
.mockup-btn-excel { background: #fff; color: #1e8e5a; border: 1px solid rgba(30,142,90,0.35); }
.mockup-btn-pdf { background: #fff; color: #d5431c; border: 1px solid $color-borde; }
.mockup-input-sm { background: #fff; border: 1px solid #7fc8e8; border-radius: 6px; padding: 5px 8px; font-size: 0.82rem; text-align: center; }
.mockup-input-bloqueado { background: #eef1f3; border-color: $color-borde; color: $color-texto-secundario; display: inline-flex; align-items: center; justify-content: center; gap: 6px; width: 100%; box-sizing: border-box; i { font-size: 0.72rem; } }
.mockup-calculado { background: #ddeaf4; border: 1px solid #b8d0ef; border-radius: 6px; padding: 5px 8px; font-size: 0.82rem; font-weight: 700; color: $color-primario; text-align: center; font-variant-numeric: tabular-nums; }

.instruccion-indicator { display: flex; align-items: center; gap: 10px; background: rgba(0,60,88,0.06); border: 1px solid rgba(0,60,88,0.14); border-radius: 10px; padding: 8px 14px; }
.instruccion-icon-circle { display: flex; align-items: center; justify-content: center; color: $color-primario; font-size: 1.2rem; flex: 0 0 auto; }
.instruccion-texto { font-size: 0.92rem; color: $color-primario; line-height: 1.7; strong { font-weight: 700; } }
.instruccion-badge { display: inline-flex; align-items: center; gap: 5px; padding: 1px 8px; border-radius: 6px; font-size: 0.82rem; font-weight: 700; vertical-align: middle; }
.instruccion-badge--agregar { background: #2f7d6d; color: #fff; }
.instruccion-badge--quitar { background: #a4262c; color: #fff; }

.filtros-panel-mockup { display: flex; gap: 10px; flex-wrap: wrap; background: $color-claro; border-radius: 10px; padding: 10px 12px; border: 1px solid $color-borde; }
.filtro-mockup { display: flex; flex-direction: column; gap: 4px; }
.filtro-buscar-mockup { flex: 2; min-width: 160px; }
.filtro-label-mockup { font-size: 0.72rem; font-weight: 600; color: $color-primario; text-transform: uppercase; }
.mockup-select { background: #fff; border: 1px solid $color-borde; border-radius: 8px; padding: 7px 12px; font-size: 0.84rem; display: flex; align-items: center; justify-content: space-between; gap: 8px; color: $color-texto-principal; min-width: 110px; }

.prestaciones-grid-mockup { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.prestaciones-panel-mockup { background: $color-claro; border-radius: 12px; padding: 12px; border: 1px solid $color-borde; }
.panel-title-mockup { font-weight: 700; color: $color-primario; margin-bottom: 8px; font-size: 0.9rem; }
.panel-title-fila { display: flex; align-items: center; justify-content: space-between; gap: 8px; }
.mockup-botones-panel { display: inline-flex; gap: 6px; flex-wrap: wrap; justify-content: flex-end; }
.mockup-btn-pill-borde { display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; padding: 3px 10px; font-size: 0.76rem; font-weight: 700; background: #fff; white-space: nowrap; i { font-size: 0.65rem; border-radius: 50%; width: 16px; height: 16px; display: inline-flex; align-items: center; justify-content: center; } }
.mockup-btn-pill-verde { color: #1f5e4f; border: 1px solid #7cc9a4; i { background: #dcf3e6; } }
.mockup-btn-pill-rojo { color: #a4262c; border: 1px solid #e57b7f; i { background: #fde4e4; } }
.prestaciones-lista-mockup { display: flex; flex-direction: column; gap: 6px; }
.prestacion-item-mockup { display: flex; align-items: center; justify-content: space-between; gap: 10px; background: #fff; border: 1px solid $color-borde; border-radius: 8px; padding: 6px 10px; }
.prestacion-info-mockup { flex: 1; display: flex; flex-direction: column; gap: 2px; }
.prestacion-codigo-mockup { font-size: 0.72rem; font-weight: 700; color: $color-primario; }
.prestacion-nombre-mockup { font-size: 0.82rem; color: $color-texto-principal; }
.accion-mockup { width: 26px; height: 26px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-size: 0.8rem; color: #fff; flex: 0 0 auto; }
.accion-agregar-mockup { background: #2f7d6d; }
.accion-quitar-mockup { background: #a4262c; }
.acciones-finales-mockup { display: flex; align-items: center; justify-content: space-between; gap: 12px; background: $color-claro; border-radius: 10px; padding: 10px 14px; border: 1px solid $color-borde; }
.acciones-resumen-mockup { font-size: 0.88rem; color: $color-texto-secundario; }

.mockup-params-table { overflow-x: auto; }
.mockup-params-head { display: grid; background: #e9f1f6; padding: 7px 12px; border-radius: 8px 8px 0 0; font-size: 0.74rem; font-weight: 700; color: $color-primario; gap: 8px; align-items: end; }
.mockup-params-row { display: grid; background: $color-claro; padding: 7px 12px; border: 1px solid $color-borde; border-top: none; border-radius: 0 0 8px 8px; gap: 8px; align-items: center; font-size: 0.84rem; }
.mockup-params-row-mid { border-radius: 0; }
.mockup-params-head-real, .mockup-params-row-real { grid-template-columns: 1.5fr 1fr 1.1fr 1.25fr 1fr 1fr; }
.mockup-params-head-real span { white-space: nowrap; }
.mockup-grid-rrhh { grid-template-columns: 1.4fr 0.8fr 1fr 1fr 1fr 1fr; }
.mockup-row-name { font-weight: 600; color: $color-texto-principal; }
.mockup-params-row.mockup-fila-aplicar { background: #eef5fa; }
.mockup-aplicar-etiqueta { font-size: 0.76rem; font-weight: 600; color: rgba(0,60,88,0.75); }
.mockup-aplicar-celda { display: flex; align-items: center; gap: 4px; .mockup-input-sm { flex: 1; min-width: 0; padding: 4px 6px; font-size: 0.78rem; } }
.mockup-btn-aplicar { flex: 0 0 auto; background: rgba(0,60,88,0.08); color: $color-primario; border: 1px solid rgba(0,60,88,0.2); border-radius: 6px; padding: 3px 6px; font-size: 0.7rem; font-weight: 600; }
.mockup-celda-vacia { color: $color-texto-secundario; opacity: 0.6; text-align: center; }

.mockup-calc-toggle-row { display: flex; flex-direction: column; gap: 4px; }
.mockup-calc-toggle { display: flex; align-items: center; gap: 10px; padding: 8px 14px; border: 1.5px solid $color-primario; border-radius: 999px; background: rgba(0,60,88,0.06); color: $color-primario; font-weight: 700; font-size: 0.88rem; max-width: 380px; }
.mockup-calc-icono { font-size: 0.95rem; }
.mockup-calc-texto { flex: 1; }
.mockup-calc-chevron { font-size: 0.75rem; opacity: 0.6; }
.mockup-calc-desc { font-size: 0.82rem; color: $color-texto-secundario; margin: 0; padding-left: 4px; line-height: 1.45; }

.mockup-banner-res { background: $color-primario; border-radius: 12px; padding: 12px 18px; display: flex; align-items: center; justify-content: space-between; gap: 10px; }
.mockup-banner-titulo { color: #fff; font-weight: 700; font-size: 0.95rem; }
.mockup-banner-sub { color: rgba(255,255,255,0.75); font-size: 0.8rem; }
.mockup-res-top { display: flex; align-items: flex-end; justify-content: space-between; gap: 12px; flex-wrap: wrap; }
.mockup-res-titulo { font-size: 1.15rem; font-weight: 700; color: $color-primario; }
.mockup-res-sub { font-size: 0.82rem; color: $color-texto-secundario; }
.mockup-res-blanco { background: #fff; }
.mockup-resumen-barra { display: flex; align-items: center; justify-content: space-between; background: #ddeaf4; border-radius: 10px; padding: 8px 14px; font-weight: 700; font-size: 0.88rem; color: $color-primario; }
.mockup-vistas { display: inline-flex; align-items: center; gap: 10px; i { opacity: 0.5; } .activo { opacity: 1; background: $color-primario; color: #fff; border-radius: 6px; padding: 4px; } .dropdown-chev { opacity: 0.6; } }
.mockup-desglose { display: grid; grid-template-columns: 1fr 1fr; gap: 8px 12px; }
.mockup-desglose-extra { margin-top: 14px; }
.mockup-accordion.mockup-acc-recinto { background: #eef5f9; border-color: #e3ebf2; }
.mockup-accordion.mockup-acc-normativa { background: #fff6ec; border-color: #fde3c4; border-left: 4px solid #f59e0b; color: #9a4a00; }
.mockup-accordion.mockup-acc-urpa { background: #e6f7fa; border-color: #c3eaf1; border-left: 4px solid #06b6d4; color: #0e6170; }
.mockup-accordion.mockup-acc-especifico { background: #f5f0ff; border-color: #e3d8fb; border-left: 4px solid #8b5cf6; color: #5b21b6; }
.mockup-accordion.mockup-acc-normativa, .mockup-accordion.mockup-acc-urpa, .mockup-accordion.mockup-acc-especifico { span > i { margin-right: 6px; } }
.mockup-metric-val { font-size: 1.4rem; font-weight: 700; color: #fff; margin-right: 8px; }
.mockup-metric-lab { font-size: 0.82rem; color: rgba(255,255,255,0.75); }
.mockup-res-block { background: $color-claro; border-radius: 10px; padding: 10px 14px; border: 1px solid $color-borde; }
.mockup-section-title { font-weight: 700; color: $color-primario; font-size: 0.86rem; margin-bottom: 8px; }
.mockup-pill-container { display: flex; flex-wrap: wrap; gap: 8px; }
.mockup-pill { display: flex; align-items: center; gap: 6px; background: #eef5f9; border: 1px solid $color-borde; border-radius: 999px; padding: 4px 12px; font-size: 0.82rem; color: $color-primario; }
.mockup-acc-grid { display: flex; flex-direction: column; gap: 6px; }
.mockup-accordion { display: flex; align-items: center; justify-content: space-between; background: #fff; border: 1px solid $color-borde; border-radius: 8px; padding: 8px 12px; font-size: 0.86rem; font-weight: 600; color: $color-primario; }
.mockup-accordion-abierto { border-color: rgba(0,60,88,0.3); background: rgba(0,60,88,0.04); border-radius: 8px 8px 0 0; i:first-child { margin-right: 6px; } }
.acc-num { font-weight: 400; color: $color-texto-secundario; }
.dropdown-chev { font-size: 0.75rem; opacity: 0.6; }

/* Recinto abierto del paso 5 */
.mockup-recinto { border: 1px solid $color-borde; border-radius: 10px; overflow: hidden; .mockup-accordion-abierto { border: none; border-bottom: 1px solid $color-borde; border-radius: 0; } .mockup-params-head { border-radius: 0; } .mockup-params-row { border-left: none; border-right: none; } .mockup-params-row:last-child { border-bottom: none; border-radius: 0; } }
.mockup-chips { display: flex; flex-wrap: wrap; gap: 6px; padding: 10px 12px; }
.mockup-chip-perfil { background: #fff; color: $color-primario; border: 1.5px solid #7fc8e8; border-radius: 999px; padding: 3px 11px; font-size: 0.8rem; font-weight: 600; &.activo { background: $color-primario; color: #fff; border-color: $color-primario; } }
.mockup-chip-nuevo { border-style: dashed; color: rgba(0,60,88,0.75); i { font-size: 0.7rem; } }

.mockup-segmento { display: inline-flex; border: 1px solid #7fc8e8; border-radius: 8px; overflow: hidden; font-size: 0.76rem; font-weight: 600; span { padding: 3px 9px; color: $color-primario; background: #fff; } span + span { border-left: 1px solid #7fc8e8; } .activo { background: $color-primario; color: #fff; } }

/* Tiempos por prestación (paso 6) */
.mockup-tp-cuenta { font-size: 0.76rem; margin-left: 8px; }
.mockup-tp-masivo { margin: 10px 12px; padding: 10px 12px; border-radius: 10px; background: #eef5fa; border: 1px solid #d3e4f0; display: flex; flex-direction: column; gap: 8px; }
.mockup-tp-masivo-titulo { font-size: 0.82rem; font-weight: 700; color: $color-primario; i { opacity: 0.6; margin-left: 4px; } }
.mockup-tp-controles { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; .mockup-select { min-width: 160px; } }
.mockup-btn-suave { font-weight: 500; color: rgba(0,60,88,0.75); background: transparent; }
.mockup-tp-prestacion { margin: 0 12px 12px; padding: 10px 12px; border: 1px solid #e3ebf2; border-radius: 10px; background: #fbfdfe; }
.mockup-tp-cabecera { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 4px 16px; margin-bottom: 6px; }
.mockup-tp-titulo { font-weight: 700; color: $color-primario; font-size: 0.9rem; }
.mockup-tp-datos { font-size: 0.76rem; color: rgba(0,60,88,0.7); i { opacity: 0.6; } }
.mockup-chips-tp { padding: 0 0 6px; .mockup-chip-nuevo i { font-size: 0.6rem; margin-left: 4px; } }
.mockup-tp-fila { display: flex; align-items: center; gap: 10px; padding: 6px 0; border-top: 1px dashed #e3ebf2; font-size: 0.82rem; }
.mockup-tp-nombre { min-width: 140px; font-weight: 600; color: $color-primario; }
.mockup-ef { display: inline-flex; align-items: center; gap: 5px; color: rgba(0,60,88,0.8); .mockup-input-sm { min-width: 36px; padding: 3px 6px; } }
.mockup-phi { padding: 3px 9px; }
.mockup-tp-info { color: $color-primario; opacity: 0.45; font-size: 0.8rem; }
.mockup-tp-quitar { margin-left: auto; color: #9ca3af; font-size: 0.8rem; }

.modo-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.modo-card { background: $color-claro; border: 1px solid $color-borde; border-radius: 10px; padding: 10px 14px; display: flex; align-items: center; gap: 12px; p { margin: 0; font-size: 0.86rem; color: $color-texto-secundario; line-height: 1.45; } }
.modo-chip { flex: 0 0 auto; display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; padding: 4px 12px; font-size: 0.8rem; font-weight: 700; }
.modo-chip-perm { background: rgba(0,60,88,0.1); color: $color-primario; }
.modo-chip-punt { background: #fff4e5; color: #b45309; }

.mockup-kpis { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 10px; }
.mockup-kpi { display: flex; flex-direction: column; gap: 2px; background: $color-primario; color: #fff; border-radius: 12px; padding: 10px 14px; }
.mockup-kpi-lab { font-size: 0.78rem; opacity: 0.85; font-weight: 600; }
.mockup-kpi-val { font-size: 1.25rem; font-weight: 700; font-variant-numeric: tabular-nums; small { font-size: 0.74rem; font-weight: 600; opacity: 0.85; } }
.mockup-kpi-flecha { opacity: 0.6; font-weight: 400; margin: 0 2px; }
.mockup-kpi-alerta { background: #eef5fa; color: $color-primario; border: 1px solid #d3e4f0; .mockup-kpi-val { color: #b86e00; } }

.mockup-table { display: flex; flex-direction: column; border: 1px solid $color-borde; border-radius: 10px; overflow: hidden; }
.mockup-table-head { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; background: #e9f1f6; padding: 7px 12px; font-size: 0.74rem; font-weight: 700; color: $color-primario; gap: 8px; }
.mockup-table-row { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; padding: 8px 12px; gap: 8px; align-items: center; border-top: 1px solid $color-borde; font-size: 0.84rem; }
.mockup-grid-equipos { grid-template-columns: 2fr 1fr 1fr 1fr; }
.mockup-dif { color: #b45309; font-weight: 700; }
.mockup-actions { display: flex; gap: 6px; }
.mockup-chip { display: inline-block; background: rgba(0,60,88,0.08); color: $color-primario; border-radius: 999px; padding: 3px 10px; font-size: 0.76rem; font-weight: 600; }

.mockup-action-cards { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
.mockup-action-card { display: flex; flex-direction: column; align-items: flex-start; gap: 6px; background: $color-claro; border-radius: 10px; padding: 12px 14px; border: 1px solid $color-borde; p { margin: 0; font-size: 0.82rem; color: $color-texto-secundario; line-height: 1.45; } }

/* Llamado de la parte de RRHH (paso 5) */
.rrhh-intro { display: flex; align-items: flex-start; gap: 12px; background: rgba(0,60,88,0.04); border-radius: 10px; padding: 10px 14px; border: 1px dashed rgba(0,60,88,0.25); }
.rrhh-intro-icon { font-size: 1.3rem; color: $color-secundario; flex: 0 0 auto; margin-top: 2px; }
.rrhh-intro-desc { margin: 0; color: $color-texto-secundario; line-height: 1.5; font-size: 0.88rem; strong { color: $color-primario; } }

/* ===================== Navegación y preguntas frecuentes ===================== */
.manual-extra { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.5fr); gap: 20px; align-items: start; }
.manual-navbar-guide { background: #fff; border-radius: 14px; padding: 18px 22px; border: 1px solid $color-borde; box-shadow: 0 8px 18px $color-sombra-suave; }
.guide-title { font-size: 1.05rem; font-weight: 700; color: $color-primario; margin: 0 0 10px; display: flex; align-items: center; gap: 8px; }
.guide-desc { margin: 0 0 12px; color: $color-texto-secundario; font-size: 0.88rem; }
.navbar-guide-row { display: flex; flex-direction: column; gap: 10px; }
.navbar-guide-item { display: flex; align-items: center; gap: 12px; p { margin: 0; font-size: 0.84rem; color: $color-texto-secundario; } }
.mockup-session { display: inline-flex; align-items: center; gap: 6px; background: rgba(0,60,88,0.06); border: 1.5px solid rgba(0,60,88,0.18); border-radius: 999px; padding: 4px 10px 4px 8px; color: $color-primario; font-size: 0.8rem; font-weight: 600; white-space: nowrap; }
.mockup-btn-logout { opacity: 0.7; margin-left: 2px; }

.faq-lista { display: flex; flex-direction: column; gap: 6px; }
.faq-item { background: $color-claro; border: 1px solid $color-borde; border-radius: 10px; padding: 9px 14px;
  summary { cursor: pointer; font-weight: 600; color: $color-primario; font-size: 0.9rem; }
  p { margin: 8px 0 0; color: $color-texto-secundario; line-height: 1.55; font-size: 0.86rem; }
}

/* ===================== Video ===================== */
.video-card { background: #fff; border-radius: 14px; padding: 16px; border: 1px solid $color-borde; box-shadow: 0 8px 18px $color-sombra-suave; display: flex; flex-direction: column; gap: 14px; }
.video-wrap { position: relative; width: 100%; aspect-ratio: 16 / 9; border-radius: 10px; overflow: hidden; background: #0b1f2a; }
.video-wrap iframe, .video-wrap video { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
.video-placeholder { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; color: rgba(255,255,255,0.7); font-weight: 600; i { font-size: 3.5rem; opacity: 0.6; } }
.video-desc { margin: 0; text-align: center; color: $color-texto-secundario; }

/* ===================== Pantallas angostas ===================== */
@media (max-width: 900px) {
  .paso-cols, .manual-extra, .modo-cards, .mockup-desglose { grid-template-columns: 1fr; }
  .paso-tips { grid-template-columns: 1fr; }
  .mockup-kpis { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .mockup-action-cards { grid-template-columns: 1fr; }
}
@media (max-width: 760px) {
  .slide { padding: 20px 26px; }
  .carrusel-flecha { top: 54px; &.izquierda { left: -10px; } &.derecha { right: -10px; } }
}
</style>