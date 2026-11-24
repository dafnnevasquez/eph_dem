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
              Tú ingresas los datos del hospital y el sistema hace los cálculos.
            </p>
            <div class="intro-partes">
              <div class="intro-parte">
                <span class="intro-parte-num">Pasos 1 a 4</span>
                <span><strong>Equipamiento:</strong> cuántos equipos y recintos necesita el hospital según su demanda.</span>
              </div>
              <div class="intro-parte">
                <span class="intro-parte-num">Pasos 5 a 7</span>
                <span><strong>Recursos humanos:</strong> si el personal disponible alcanza para usar esos equipos y, si no alcanza, cuánto falta.</span>
              </div>
            </div>
            <p class="intro-desc intro-nota">Haz los pasos <strong>en orden</strong>: cada paso usa los datos del anterior.</p>
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
                  <div class="mockup-btn-row" style="margin-top:14px">
                    <div class="mockup-btn mockup-btn-primary">Guardar</div>
                    <div class="mockup-btn mockup-btn-out">Cancelar</div>
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
                    <div class="mockup-input"><span class="mockup-placeholder">Escribe el nombre o código FONASA</span></div>
                  </div>
                  <div class="filtro-mockup">
                    <div class="filtro-label-mockup">Área</div>
                    <div class="mockup-select"><span>UPC</span> <i class="fa-solid fa-chevron-down"></i></div>
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
                    <div class="panel-title-mockup">Disponibles</div>
                    <div class="prestaciones-lista-mockup">
                      <div class="prestacion-item-mockup">
                        <div class="prestacion-info-mockup">
                          <div class="prestacion-codigo-mockup">1802081</div>
                          <div class="prestacion-nombre-mockup">Colecistectomía por videolaparoscopía, proc. completo</div>
                        </div>
                        <div class="accion-mockup accion-agregar-mockup"><i class="fa-solid fa-plus"></i></div>
                      </div>
                      <div class="prestacion-item-mockup">
                        <div class="prestacion-info-mockup">
                          <div class="prestacion-codigo-mockup">1802053</div>
                          <div class="prestacion-nombre-mockup">Apendicectomía y/o dren. absceso apendicular (proc. aut.)</div>
                        </div>
                        <div class="accion-mockup accion-agregar-mockup"><i class="fa-solid fa-plus"></i></div>
                      </div>
                    </div>
                  </div>
                  <div class="prestaciones-panel-mockup">
                    <div class="panel-title-mockup">Seleccionadas</div>
                    <div class="prestaciones-lista-mockup">
                      <div class="prestacion-item-mockup prestacion-seleccionada-mockup">
                        <div class="prestacion-info-mockup">
                          <div class="prestacion-codigo-mockup">1802081</div>
                          <div class="prestacion-nombre-mockup">Colecistectomía por videolaparoscopía, proc. completo</div>
                        </div>
                        <div class="accion-mockup accion-quitar-mockup"><i class="fa-solid fa-xmark"></i></div>
                      </div>
                      <div class="lista-vacia-mockup">Agrega más prestaciones desde el panel izquierdo.</div>
                    </div>
                  </div>
                </div>
                <div class="acciones-finales-mockup">
                  <div class="acciones-resumen-mockup">Prestaciones seleccionadas: <b>1</b></div>
                  <div class="mockup-btn mockup-btn-primary">Guardar y confirmar</div>
                </div>
                <div class="paso-tips">
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Puedes buscar una prestación por su nombre o por su código FONASA, y filtrar por área, subárea o recinto.</div>
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> ¿Quieres todas las de la lista? Aprieta <strong>Agregar todas</strong> y se seleccionan de una vez.</div>
                  <div class="tip"><i class="fa-solid fa-triangle-exclamation tip-icon tip-warn"></i> ¿Vas a usar UCI o UTI? Agrega <strong>"Día Cama UCI"</strong> o <strong>"Día Cama UTI"</strong>. Sin eso, el sistema no puede calcular los cubículos.</div>
                </div>
              </div>
              </article>
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
                <div class="mockup-params-table">
                  <div class="mockup-params-head mockup-params-head-real">
                    <span>Prestación</span>
                    <span>Demanda</span>
                    <span>Días al año disponibles</span>
                    <span>Tiempo procedimiento (min)</span>
                    <span>Disponibilidad (%)</span>
                    <span>Jornada laboral (hrs)</span>
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
                <div class="paso-tips">
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Demanda:</strong> cuántas veces al año se va a hacer la prestación.</div>
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Días al año disponibles:</strong> cuántos días al año funciona el recinto.</div>
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Tiempo procedimiento (min):</strong> cuántos minutos dura el procedimiento. Viene un valor sugerido, pero puedes cambiarlo. El cambio se guarda solo en este proyecto.</div>
                  <div class="tip"><i class="fa-solid fa-lock tip-icon"></i> En <strong>Día Cama UCI</strong> y <strong>Día Cama UTI</strong> el tiempo queda fijo en <strong>1440 min</strong> (24 horas), porque una cama se ocupa el día completo. Por eso la casilla aparece bloqueada.</div>
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Disponibilidad (%):</strong> qué porcentaje del tiempo el equipo está disponible para usarse.</div>
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Jornada laboral (hrs):</strong> cuántas horas al día funciona el recinto.</div>
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> ¿Un dato es igual para todas las prestaciones? Escríbelo una sola vez en la fila de arriba y haz clic en <strong>Aplicar</strong>.</div>
                  <div class="tip"><i class="fa-solid fa-triangle-exclamation tip-icon tip-warn"></i> No dejes casillas vacías ni en 0. Si falta algo, el sistema te avisa.</div>
                </div>
                <div class="mockup-calc-toggle-row">
                  <div class="mockup-calc-toggle">
                    <span class="mockup-calc-icono"><i class="fa-solid fa-calculator"></i></span>
                    <span class="mockup-calc-texto">Calculadora Atencion Cerrada Pabellones</span>
                    <i class="fa-solid fa-chevron-down mockup-calc-chevron"></i>
                  </div>
                  <p class="mockup-calc-desc">¿No sabes cuántos días cama poner en UCI o UTI? Esta calculadora te ayuda. Solo pide el coeficiente técnico, la población que atiende el hospital y cuántos días se queda en promedio un paciente.</p>
                </div>
                <div class="mockup-btn-row">
                  <div class="mockup-btn mockup-btn-primary"><i class="fa-solid fa-calculator"></i> Calcular y ver Resultados</div>
                </div>
              </div>
              </article>
              <article v-else-if="slide === 3" key="paso-4" id="paso-4" class="slide">
                <div class="slide-tag">Parte 1 · Equipamiento</div>
                <div class="paso-header">
                  <div class="paso-num">Paso 4</div>
                  <div class="paso-icon"><i class="fa-solid fa-chart-bar"></i></div>
                  <div>
                    <h3 class="paso-title">Mira los resultados de equipamiento</h3>
                    <p class="paso-desc">Aquí ves cuántos recintos y equipos necesita el hospital, separados por recinto (UCI, UTI, pabellón, etc.). Este resultado supone que hay todo el personal necesario. En los pasos 5 a 7 revisas si de verdad alcanza.</p>
                  </div>
                </div>
                <div class="paso-body">
                <div class="mockup-resultados">
                  <div class="mockup-banner-res">
                    <div><span class="mockup-metric-val">244</span><span class="mockup-metric-lab">Equipos en total</span></div>
                  </div>
                  <div class="mockup-res-block">
                    <div class="mockup-section-title">Recintos que necesitan equipamiento</div>
                    <div class="mockup-pill-container">
                      <div class="mockup-pill">Cubículo UTI <strong>1</strong></div>
                      <div class="mockup-pill">Cubículo UCI <strong>1</strong></div>
                    </div>
                  </div>
                  <div class="mockup-res-block">
                    <div class="mockup-acc-grid">
                      <div class="mockup-accordion mockup-accordion-small">
                        <span>Cubículo UTI <span class="acc-num">(20 equipos)</span></span>
                        <i class="fa-solid fa-chevron-down dropdown-chev"></i>
                      </div>
                      <div class="mockup-accordion mockup-accordion-small">
                        <span>Cubículo UCI <span class="acc-num">(26 equipos)</span></span>
                        <i class="fa-solid fa-chevron-down dropdown-chev"></i>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="mockup-export-row" style="margin-top:16px">
                  <div class="mockup-btn mockup-btn-excel"><i class="fa-solid fa-file-excel"></i> Descargar Excel</div>
                  <div class="mockup-btn mockup-btn-pdf"><i class="fa-solid fa-file-pdf"></i> Descargar PDF</div>
                </div>
                <div class="paso-tips">
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Haz clic en un recinto para ver la lista de sus equipos.</div>
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Descarga el informe en Excel o PDF para guardarlo o compartirlo.</div>
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Si cambias los parámetros o las prestaciones, vuelve a apretar <strong>Calcular</strong> para actualizar los resultados.</div>
                </div>
              </div>
              </article>
              <article v-else-if="slide === 4" key="paso-5" id="paso-5" class="slide slide-rrhh">
                <div class="slide-tag">Parte 2 · Recursos humanos</div>
                <div class="paso-header">
                  <div class="paso-num paso-num-rrhh">Paso 5</div>
                  <div class="paso-icon paso-icon-rrhh"><i class="fa-solid fa-id-badge"></i></div>
                  <div>
                    <h3 class="paso-title">Ingresa el personal de cada recinto</h3>
                    <p class="paso-desc">Para cada recinto de tu proyecto indica cuántas personas de cada perfil trabajan ahí y cuánto tiempo trabajan. Solo aparecen los recintos que usan las prestaciones que elegiste en el paso 2.</p>
                  </div>
                </div>
                <div class="rrhh-intro">
              <div class="rrhh-intro-icon"><i class="fa-solid fa-people-group"></i></div>
              <div>
                <h3 class="rrhh-intro-title">¿Para qué sirve esta parte?</h3>
                <p class="rrhh-intro-desc">
                  Un equipo médico solo sirve si hay alguien que lo use. Por ejemplo, un pabellón necesita cirujano/a, anestesiólogo/a y arsenalera/o.
                  Si falta personal, el hospital no puede usar todos sus equipos y algunas prestaciones quedan sin atender.
                </p>
                <p class="rrhh-intro-desc">
                  En estos pasos ingresas el personal que tiene el hospital y cuánto tiempo participa cada uno en cada prestación.
                  El sistema calcula qué parte de la demanda alcanza a cubrir ese personal, cuántos equipos se pueden usar realmente y cuántas personas faltan.
                </p>
              </div>
            </div>
                <div class="paso-body">
                <div class="mockup-acc-grid">
                  <div class="mockup-accordion mockup-accordion-abierto">
                    <span><i class="fa-solid fa-hospital"></i> Pabellón mayor</span>
                    <i class="fa-solid fa-chevron-up dropdown-chev"></i>
                  </div>
                  <div class="mockup-params-table mockup-tabla-anidada">
                    <div class="mockup-params-head mockup-grid-rrhh">
                      <span>Perfil</span>
                      <span>N° de personas</span>
                      <span>Jornada semanal (h)</span>
                      <span>Semanas al año</span>
                      <span>% de jornada en el recinto</span>
                    </div>
                    <div class="mockup-params-row mockup-grid-rrhh mockup-params-row-mid">
                      <span class="mockup-row-name">Cirujano/a</span>
                      <span><div class="mockup-input-sm">2</div></span>
                      <span><div class="mockup-input-sm">44</div></span>
                      <span><div class="mockup-input-sm">48</div></span>
                      <span><div class="mockup-input-sm">100</div></span>
                    </div>
                    <div class="mockup-params-row mockup-grid-rrhh mockup-params-row-mid">
                      <span class="mockup-row-name">Anestesiólogo/a</span>
                      <span><div class="mockup-input-sm">1</div></span>
                      <span><div class="mockup-input-sm">44</div></span>
                      <span><div class="mockup-input-sm">48</div></span>
                      <span><div class="mockup-input-sm">100</div></span>
                    </div>
                    <div class="mockup-params-row mockup-grid-rrhh">
                      <span class="mockup-row-name">Enfermera/o</span>
                      <span><div class="mockup-input-sm">3</div></span>
                      <span><div class="mockup-input-sm">44</div></span>
                      <span><div class="mockup-input-sm">48</div></span>
                      <span><div class="mockup-input-sm">100</div></span>
                    </div>
                  </div>
                  <div class="mockup-accordion mockup-accordion-small">
                    <span><i class="fa-solid fa-bed-pulse"></i> Cubículo UCI</span>
                    <i class="fa-solid fa-chevron-down dropdown-chev"></i>
                  </div>
                </div>
                <div class="mockup-btn-row">
                  <div class="mockup-btn mockup-btn-primary"><i class="fa-solid fa-floppy-disk"></i> Guardar</div>
                </div>
                <div class="paso-tips">
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Los recintos aparecen cerrados. Haz clic en uno para abrirlo y llenar su personal.</div>
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>N° de personas:</strong> cuántas personas de ese perfil trabajan en el recinto.</div>
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Jornada semanal:</strong> cuántas horas a la semana trabaja cada persona. Se elige entre 44, 33, 22 y 11 horas.</div>
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Semanas al año:</strong> cuántas semanas trabaja de verdad cada persona, descontando vacaciones y permisos. Por ejemplo, 48.</div>
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>% de jornada en el recinto:</strong> qué parte de su jornada trabaja en este recinto. Si una persona reparte su tiempo entre dos recintos, pon, por ejemplo, 50 en cada uno.</div>
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> ¿Varios recintos tienen el mismo personal? Usa la carga masiva para llenarlos de una vez.</div>
                  <div class="tip"><i class="fa-solid fa-triangle-exclamation tip-icon tip-warn"></i> Si no aparece ningún recinto, vuelve al paso 2 y revisa que elegiste prestaciones.</div>
                </div>
              </div>
              </article>
              <article v-else-if="slide === 5" key="paso-6" id="paso-6" class="slide slide-rrhh">
                <div class="slide-tag">Parte 2 · Recursos humanos</div>
                <div class="paso-header">
                  <div class="paso-num paso-num-rrhh">Paso 6</div>
                  <div class="paso-icon paso-icon-rrhh"><i class="fa-solid fa-user-clock"></i></div>
                  <div>
                    <h3 class="paso-title">Indica cuánto participa cada perfil en cada prestación</h3>
                    <p class="paso-desc">No todo el personal está presente durante toda la prestación. Aquí indicas, para cada prestación y cada perfil, si participa todo el tiempo o solo una parte.</p>
                  </div>
                </div>
                <div class="paso-body">
                <div class="modo-cards">
                  <div class="modo-card">
                    <div class="modo-chip modo-chip-perm"><i class="fa-solid fa-infinity"></i> Permanente</div>
                    <p>La persona está <strong>durante toda la prestación</strong>. Por ejemplo, el cirujano durante una cirugía.</p>
                  </div>
                  <div class="modo-card">
                    <div class="modo-chip modo-chip-punt"><i class="fa-solid fa-stopwatch"></i> Puntual</div>
                    <p>La persona participa <strong>solo en algunos momentos</strong>. Por ejemplo, un médico que pasa visita a un paciente de UCI algunas veces al día.</p>
                  </div>
                </div>

                <div class="mockup-params-table">
                  <div class="mockup-params-head mockup-grid-tiempos">
                    <span>Perfil</span>
                    <span>Tipo</span>
                    <span>Minutos por vez (e)</span>
                    <span>Veces al día (f)</span>
                    <span>Tiempo prestación (t)</span>
                    <span>Fracción (φ)</span>
                  </div>
                  <div class="mockup-params-row mockup-grid-tiempos mockup-params-row-mid">
                    <span class="mockup-row-name">Cirujano/a</span>
                    <span><div class="mockup-segmento"><span class="activo">Permanente</span><span>Puntual</span></div></span>
                    <span class="mockup-celda-vacia">—</span>
                    <span class="mockup-celda-vacia">—</span>
                    <span>2 h</span>
                    <span><strong>100%</strong></span>
                  </div>
                  <div class="mockup-params-row mockup-grid-tiempos">
                    <span class="mockup-row-name">Enfermera/o</span>
                    <span><div class="mockup-segmento"><span>Permanente</span><span class="activo">Puntual</span></div></span>
                    <span><div class="mockup-input-sm">20</div></span>
                    <span><div class="mockup-input-sm">3</div></span>
                    <span>2 h</span>
                    <span><strong>50%</strong></span>
                  </div>
                </div>

                <div class="mockup-btn-row">
                  <div class="mockup-btn mockup-btn-out">Aplicar a todas</div>
                  <div class="mockup-btn mockup-btn-primary"><i class="fa-solid fa-chart-column"></i> Guardar y ver resultados</div>
                </div>

                <div class="paso-tips">
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Permanente:</strong> el sistema cuenta todo el tiempo de la prestación. No tienes que llenar más datos.</div>
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Puntual:</strong> indica cuántos minutos dura cada intervención (<strong>e</strong>) y cuántas veces al día interviene (<strong>f</strong>). Por ejemplo, 20 minutos, 3 veces.</div>
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>t</strong> es lo que dura la prestación, y sale del tiempo de procedimiento del paso 3. <strong>φ</strong> es la parte de ese tiempo en que la persona está ocupada. Nunca supera el 100%.</div>
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> ¿Un perfil participa igual en todas las prestaciones? Llénalo una vez y aprieta <strong>Aplicar a todas</strong>.</div>
                  <div class="tip"><i class="fa-solid fa-circle-question tip-icon"></i> ¿Dudas con algún dato? Haz clic en el ícono <i class="fa-solid fa-circle-info"></i> junto a cada título para ver una explicación.</div>
                </div>
              </div>
              </article>
              <article v-else-if="slide === 6" key="paso-7" id="paso-7" class="slide slide-rrhh">
                <div class="slide-tag">Parte 2 · Recursos humanos</div>
                <div class="paso-header">
                  <div class="paso-num paso-num-rrhh">Paso 7</div>
                  <div class="paso-icon paso-icon-rrhh"><i class="fa-solid fa-scale-balanced"></i></div>
                  <div>
                    <h3 class="paso-title">Mira los resultados con el personal disponible</h3>
                    <p class="paso-desc">Aquí ves cuánto de la demanda alcanza a cubrir el personal que ingresaste, cuántos equipos se pueden usar realmente y cuántas personas faltan para cubrirla completa.</p>
                  </div>
                </div>
                <div class="paso-body">
                <div class="mockup-resultados">
                  <div class="mockup-pill-container">
                    <div class="mockup-kpi">
                      <span class="mockup-kpi-val">72%</span>
                      <span class="mockup-kpi-lab">Demanda que se alcanza a cubrir <i class="fa-solid fa-circle-info"></i></span>
                    </div>
                    <div class="mockup-kpi">
                      <span class="mockup-kpi-val">Anestesiólogo/a</span>
                      <span class="mockup-kpi-lab">Perfil que limita <i class="fa-solid fa-circle-info"></i></span>
                    </div>
                    <div class="mockup-kpi mockup-kpi-alerta">
                      <span class="mockup-kpi-val">1 persona</span>
                      <span class="mockup-kpi-lab">Personal que falta <i class="fa-solid fa-circle-info"></i></span>
                    </div>
                    <div class="mockup-kpi mockup-kpi-alerta">
                      <span class="mockup-kpi-val">126</span>
                      <span class="mockup-kpi-lab">Prestaciones al año sin cubrir <i class="fa-solid fa-circle-info"></i></span>
                    </div>
                  </div>

                  <div class="mockup-table">
                    <div class="mockup-table-head mockup-grid-equipos">
                      <span>Equipo</span><span>Sin restricción</span><span>Con personal disponible</span><span>Diferencia</span>
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
                </div>
                <div class="mockup-export-row">
                  <div class="mockup-btn mockup-btn-excel"><i class="fa-solid fa-file-excel"></i> Descargar Excel</div>
                  <div class="mockup-btn mockup-btn-pdf"><i class="fa-solid fa-file-pdf"></i> Descargar PDF</div>
                </div>
                <div class="paso-tips">
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Demanda que se alcanza a cubrir:</strong> si es 100%, el personal alcanza para toda la demanda. Si es menor, el personal solo alcanza para esa parte.</div>
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Perfil que limita:</strong> el perfil al que le falta más tiempo. Ese perfil decide cuánto se puede cubrir en el recinto.</div>
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Personal que falta:</strong> cuántas personas más de ese perfil se necesitan para cubrir toda la demanda.</div>
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> <strong>Tabla de equipos:</strong> compara los equipos del paso 4 (sin restricción) con los que se pueden usar con el personal actual. Las camillas de URPA también se ajustan, porque dependen de los pabellones.</div>
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Descarga el informe en Excel o PDF con todo el detalle por recinto, perfil y equipo.</div>
                  <div class="tip"><i class="fa-solid fa-lightbulb tip-icon tip-warn"></i> Prueba cambiar el personal en el paso 5 y vuelve a ver los resultados. Así puedes saber cuánta gente contratar para cubrir toda la demanda.</div>
                </div>
              </div>
              </article>
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
                <div class="paso-tips">
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Aprieta <strong>Ver</strong> para abrir un proyecto y mirar sus resultados.</div>
                  <div class="tip"><i class="fa-solid fa-circle-info tip-icon"></i> Desde los resultados puedes volver a cambiar los datos, las prestaciones o el personal.</div>
                  <div class="tip"><i class="fa-solid fa-lock tip-icon"></i> Solo tú ves tus proyectos, porque están en tu cuenta.</div>
                </div>
                <h4 class="sub-title-mockup"><i class="fa-solid fa-pen-to-square"></i> ¿Necesitas cambiar algo?</h4>
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
                    <div class="mockup-btn mockup-btn-primary"><i class="fa-solid fa-user-doctor"></i> Recursos humanos</div>
                    <p>Cambia el personal o sus tiempos y mira cómo cambian los resultados.</p>
                  </div>
                </div>
                <div class="paso-tips">
                  <div class="tip"><i class="fa-solid fa-triangle-exclamation tip-icon tip-warn"></i> Si cambias las prestaciones, revisa también el personal (paso 5) y los tiempos (paso 6): pueden aparecer recintos o prestaciones nuevas.</div>
                </div>
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

        <!-- BARRA DE NAVEGACIÓN -->
        <section class="manual-navbar-guide">
          <h3 class="guide-title"><i class="fa-solid fa-compass"></i> Cómo moverte por la página</h3>
          <p class="guide-desc">Arriba de cada pantalla tienes estos botones:</p>
          <div class="navbar-guide-row">
            <div class="navbar-guide-item">
              <div class="mockup-btn mockup-btn-primary"><i class="fa-solid fa-arrow-left"></i> Volver</div>
              <p>Te lleva a la pantalla anterior.</p>
            </div>
            <div class="navbar-guide-item">
              <div class="mockup-btn mockup-btn-primary"><i class="fa-solid fa-house-user"></i> Inicio</div>
              <p>Te lleva a la pantalla de inicio.</p>
            </div>
            <div class="navbar-guide-item">
              <div class="mockup-session">
                <i class="fa-solid fa-circle-user"></i>
                <span>usuario@uv.cl</span>
                <div class="mockup-btn-logout"><i class="fa-solid fa-right-from-bracket"></i></div>
              </div>
              <p>Muestra con qué cuenta entraste. El ícono <i class="fa-solid fa-right-from-bracket"></i> cierra tu sesión.</p>
            </div>
          </div>
        </section>

        <!-- PREGUNTAS FRECUENTES -->
        <section class="manual-navbar-guide">
          <h3 class="guide-title"><i class="fa-solid fa-circle-question"></i> Preguntas frecuentes</h3>
          <div class="faq-lista">
            <details class="faq-item" v-for="(f, i) in preguntas" :key="i">
              <summary>{{ f.p }}</summary>
              <p>{{ f.r }}</p>
            </details>
          </div>
        </section>

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
  { icon: 'fa-solid fa-plus-circle',     label: 'Crear proyecto',        id: 'paso-1' },
  { icon: 'fa-solid fa-list-check',      label: 'Elegir prestaciones',   id: 'paso-2' },
  { icon: 'fa-solid fa-sliders',         label: 'Completar datos',       id: 'paso-3' },
  { icon: 'fa-solid fa-chart-bar',       label: 'Ver equipamiento',      id: 'paso-4' },
  { icon: 'fa-solid fa-id-badge',        label: 'Ingresar personal',     id: 'paso-5', rrhh: true },
  { icon: 'fa-solid fa-user-clock',      label: 'Tiempos del personal',  id: 'paso-6', rrhh: true },
  { icon: 'fa-solid fa-scale-balanced',  label: 'Resultados con personal', id: 'paso-7', rrhh: true },
  { icon: 'fa-solid fa-folder-open',     label: 'Mis proyectos',         id: 'paso-8' },
]

const preguntas = [
  { p: '¿Por qué en el paso 7 hay menos equipos que en el paso 4?',
    r: 'El paso 4 calcula los equipos suponiendo que hay todo el personal necesario. El paso 7 usa el personal que ingresaste: si no alcanza para cubrir toda la demanda, tampoco se pueden usar todos los equipos.' },
  { p: '¿Qué hago si un recinto aparece al 100%?',
    r: 'Nada: significa que el personal de ese recinto alcanza para toda la demanda y no se pierde ningún equipo.' },
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

.manual-page { background: $color-fondo; flex: 1; }
.manual-content { width: 100%; max-width: none; margin: 32px 0 72px; padding: 0 40px; box-sizing: border-box; display: flex; flex-direction: column; gap: 32px; }

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

.manual-intro { display: flex; align-items: flex-start; gap: 20px; background: #fff; border-radius: 16px; padding: 24px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; }
.intro-icon { font-size: 2rem; color: $color-primario; flex: 0 0 auto; }
.intro-title { font-size: 1.4rem; font-weight: 700; color: $color-primario; margin: 0 0 8px; }
.intro-desc { margin: 0; color: $color-texto-secundario; line-height: 1.6; }

.flujo-row { display: flex; align-items: center; justify-content: center; gap: 8px; flex-wrap: wrap; background: #fff; border-radius: 16px; padding: 20px 24px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; }
.flujo-step { display: flex; align-items: center; gap: 8px; cursor: pointer; }
.flujo-num { width: 28px; height: 28px; border-radius: 50%; background: $color-primario; color: #fff; font-weight: 700; font-size: 0.85rem; display: flex; align-items: center; justify-content: center; flex: 0 0 auto; }
.flujo-icon { font-size: 1.1rem; color: $color-primario; }
.flujo-label { font-size: 0.9rem; font-weight: 600; color: $color-primario; }
.flujo-label-link { &:hover { text-decoration: underline; } }
.flujo-arrow { color: $color-borde; font-size: 0.8rem; }

.paso-card { background: #fff; border-radius: 16px; padding: 28px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; display: flex; flex-direction: column; gap: 20px; }
.paso-num { background: $color-primario; color: #fff; border-radius: 8px; padding: 4px 12px; font-weight: 700; font-size: 0.85rem; flex: 0 0 auto; }
.paso-icon { font-size: 1.8rem; color: $color-primario; flex: 0 0 auto; }
.paso-title { font-size: 1.2rem; font-weight: 700; color: $color-primario; margin: 0 0 6px; }
.paso-desc { margin: 0; color: $color-texto-secundario; line-height: 1.6; }
.paso-body { display: flex; flex-direction: column; gap: 16px; }
.paso-tips { display: flex; flex-direction: column; gap: 8px; }
.tip { position: relative; padding-left: 24px; font-size: 0.9rem; color: $color-texto-secundario; line-height: 1.5; }
.tip-icon { position: absolute; left: 0; top: 3px; color: $color-primario; }
.tip-warn { color: #d97706; }

// Mockups
.mockup-nuevo-row { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; }
.mockup-btn-nuevo { background: $color-primario; color: #fff; border-radius: 10px; padding: 10px 18px; font-weight: 700; font-size: 0.95rem; display: inline-flex; align-items: center; gap: 8px; }
.mockup-submenu { display: flex; flex-direction: column; gap: 8px; background: #fff; border: 1px solid $color-borde; border-radius: 12px; padding: 10px; box-shadow: 0 8px 20px rgba(0,30,45,0.12); }
.mockup-submenu-item { padding: 8px 12px; border-radius: 8px; font-size: 0.9rem; font-weight: 600; color: $color-texto-secundario; cursor: pointer; }
.mockup-submenu-active { background: rgba(0,60,88,0.08); color: $color-primario; }
.mockup-form-proyecto { display: flex; flex-direction: column; gap: 10px; background: $color-claro; border-radius: 12px; padding: 16px; border: 1px solid $color-borde; max-width: 400px; }
.mockup-label { font-size: 0.85rem; font-weight: 600; color: $color-primario; }
.mockup-input { background: #fff; border: 1.5px solid $color-borde; border-radius: 8px; padding: 10px 14px; font-size: 0.95rem; color: $color-texto-principal; }
.mockup-placeholder { color: $color-texto-secundario; }
.mockup-btn-row { display: flex; gap: 10px; }
.mockup-btn { padding: 8px 16px; border-radius: 8px; font-weight: 700; font-size: 0.9rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; }
.mockup-btn-primary { background: $color-primario; color: #fff; }
.mockup-btn-out { background: rgba(0,60,88,0.08); color: $color-primario; border: 1px solid rgba(0,60,88,0.2); }
.mockup-btn-sm { padding: 6px 12px; font-size: 0.82rem; }
.mockup-btn-excel { background: #e8f5e9; color: #1b5e20; border: 1px solid #a5d6a7; }
.mockup-btn-pdf { background: #ffebee; color: #b71c1c; border: 1px solid #ef9a9a; }
.mockup-input-sm { background: #fff; border: 1px solid $color-borde; border-radius: 6px; padding: 6px 10px; font-size: 0.85rem; text-align: center; }

.instruccion-indicator { display: flex; align-items: center; gap: 10px; background: rgba(0,60,88,0.06); border: 1px solid rgba(0,60,88,0.14); border-radius: 10px; padding: 10px 16px; }
.instruccion-icon-circle { display: flex; align-items: center; justify-content: center; color: $color-primario; font-size: 1.4rem; flex: 0 0 auto; }
.instruccion-texto { font-size: 1rem; color: $color-primario; line-height: 1.8; strong { font-weight: 700; } }
.instruccion-badge { display: inline-flex; align-items: center; gap: 5px; padding: 2px 9px; border-radius: 6px; font-size: 0.88rem; font-weight: 700; vertical-align: middle; }
.instruccion-badge--agregar { background: $color-exito; color: #fff; }
.instruccion-badge--quitar { background: $color-peligro; color: #fff; }

.filtros-panel-mockup { display: flex; gap: 12px; flex-wrap: wrap; background: $color-claro; border-radius: 10px; padding: 12px; border: 1px solid $color-borde; }
.filtro-mockup { display: flex; flex-direction: column; gap: 4px; }
.filtro-buscar-mockup { flex: 2; min-width: 160px; }
.filtro-label-mockup { font-size: 0.75rem; font-weight: 600; color: $color-primario; text-transform: uppercase; }
.mockup-select { background: #fff; border: 1px solid $color-borde; border-radius: 8px; padding: 8px 12px; font-size: 0.85rem; display: flex; align-items: center; justify-content: space-between; gap: 8px; color: $color-texto-principal; min-width: 120px; }

.prestaciones-grid-mockup { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
.prestaciones-panel-mockup { background: $color-claro; border-radius: 12px; padding: 14px; border: 1px solid $color-borde; }
.panel-title-mockup { font-weight: 700; color: $color-primario; margin-bottom: 10px; font-size: 0.95rem; }
.prestaciones-lista-mockup { display: flex; flex-direction: column; gap: 8px; }
.prestacion-item-mockup { display: flex; align-items: center; justify-content: space-between; gap: 10px; background: #fff; border: 1px solid $color-borde; border-radius: 8px; padding: 8px 10px; }
.prestacion-seleccionada-mockup { background: rgba(0,60,88,0.04); border-color: rgba(0,60,88,0.2); }
.prestacion-info-mockup { flex: 1; display: flex; flex-direction: column; gap: 2px; }
.prestacion-codigo-mockup { font-size: 0.75rem; font-weight: 700; color: $color-primario; }
.prestacion-nombre-mockup { font-size: 0.85rem; color: $color-texto-principal; }
.accion-mockup { width: 28px; height: 28px; border-radius: 6px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 0.85rem; color: #fff; flex: 0 0 auto; }
.accion-agregar-mockup { background: $color-exito; }
.accion-quitar-mockup { background: $color-peligro; }
.lista-vacia-mockup { font-size: 0.82rem; color: $color-texto-secundario; padding: 8px 0; }
.acciones-finales-mockup { display: flex; align-items: center; justify-content: space-between; gap: 12px; background: $color-claro; border-radius: 10px; padding: 12px 16px; border: 1px solid $color-borde; }
.acciones-resumen-mockup { font-size: 0.9rem; color: $color-texto-secundario; }

.mockup-params-table { overflow-x: auto; }
.mockup-params-head { display: grid; background: #e9f1f6; padding: 8px 12px; border-radius: 8px 8px 0 0; font-size: 0.78rem; font-weight: 700; color: $color-primario; gap: 8px; }
.mockup-params-head-real { grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr; }
.mockup-params-row { display: grid; background: $color-claro; padding: 8px 12px; border: 1px solid $color-borde; border-top: none; border-radius: 0 0 8px 8px; gap: 8px; align-items: center; font-size: 0.85rem; }
.mockup-params-row-real { grid-template-columns: 2fr 1fr 1fr 1fr 1fr 1fr; }
.mockup-row-name { font-weight: 600; color: $color-texto-principal; }

.mockup-calc-toggle-row { display: flex; flex-direction: column; gap: 6px; }
.mockup-calc-toggle { display: flex; align-items: center; gap: 10px; padding: 10px 16px; border: 1.5px solid $color-primario; border-radius: 999px; background: rgba(0,60,88,0.06); color: $color-primario; font-weight: 700; font-size: 0.93rem; max-width: 400px; cursor: pointer; }
.mockup-calc-icono { font-size: 1rem; }
.mockup-calc-texto { flex: 1; }
.mockup-calc-chevron { font-size: 0.8rem; opacity: 0.6; }
.mockup-calc-desc { font-size: 0.85rem; color: $color-texto-secundario; margin: 0; padding-left: 4px; }

.mockup-resultados { display: flex; flex-direction: column; gap: 14px; }
.mockup-banner-res { background: $color-primario; border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 16px; }
.mockup-metric-val { font-size: 1.6rem; font-weight: 700; color: #fff; margin-right: 8px; }
.mockup-metric-lab { font-size: 0.85rem; color: rgba(255,255,255,0.7); }
.mockup-res-block { background: $color-claro; border-radius: 10px; padding: 12px 16px; border: 1px solid $color-borde; }
.mockup-section-title { font-weight: 700; color: $color-primario; font-size: 0.9rem; margin-bottom: 10px; }
.mockup-pill-container { display: flex; flex-wrap: wrap; gap: 8px; }
.mockup-pill { display: flex; align-items: center; gap: 6px; background: #eef5f9; border: 1px solid $color-borde; border-radius: 999px; padding: 6px 14px; font-size: 0.85rem; color: $color-primario; }
.mockup-acc-grid { display: flex; flex-direction: column; gap: 8px; }
.mockup-accordion { display: flex; align-items: center; justify-content: space-between; background: #fff; border: 1px solid $color-borde; border-radius: 8px; padding: 10px 14px; font-size: 0.9rem; font-weight: 600; color: $color-primario; cursor: pointer; }
.mockup-accordion-small { font-size: 0.85rem; }
.acc-num { font-weight: 400; color: $color-texto-secundario; }
.dropdown-chev { font-size: 0.8rem; opacity: 0.6; }
.mockup-export-row { display: flex; gap: 10px; }

.mockup-table { display: flex; flex-direction: column; border: 1px solid $color-borde; border-radius: 10px; overflow: hidden; }
.mockup-table-head { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; background: #e9f1f6; padding: 8px 12px; font-size: 0.78rem; font-weight: 700; color: $color-primario; gap: 8px; }
.mockup-table-row { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; padding: 10px 12px; gap: 8px; align-items: center; border-top: 1px solid $color-borde; font-size: 0.85rem; }
.mockup-actions { display: flex; gap: 6px; }
.mockup-chip { background: rgba(0,60,88,0.08); color: $color-primario; border-radius: 999px; padding: 4px 10px; font-size: 0.78rem; font-weight: 600; }

.mockup-action-cards { display: flex; gap: 16px; flex-wrap: wrap; }
.mockup-action-card { display: flex; flex-direction: column; gap: 8px; background: $color-claro; border-radius: 12px; padding: 16px; border: 1px solid $color-borde; max-width: 260px; p { margin: 0; font-size: 0.85rem; color: $color-texto-secundario; } }

.sub-title-mockup { font-size: 1rem; font-weight: 700; color: $color-primario; margin: 8px 0 4px; display: flex; align-items: center; gap: 8px; }

.manual-navbar-guide { background: #fff; border-radius: 16px; padding: 28px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; }
.guide-title { font-size: 1.2rem; font-weight: 700; color: $color-primario; margin: 0 0 8px; display: flex; align-items: center; gap: 8px; }
.guide-desc { margin: 0 0 20px; color: $color-texto-secundario; }
.navbar-guide-row { display: flex; align-items: flex-start; gap: 32px; flex-wrap: wrap; }
.navbar-guide-item { display: flex; flex-direction: column; gap: 8px; p { margin: 0; font-size: 0.85rem; color: $color-texto-secundario; } }
.mockup-session { display: flex; align-items: center; gap: 8px; background: rgba(0,60,88,0.06); border: 1.5px solid rgba(0,60,88,0.18); border-radius: 999px; padding: 6px 14px 6px 10px; color: $color-primario; font-size: 0.88rem; font-weight: 600; }
.mockup-btn-logout { background: none; border: none; color: $color-primario; cursor: pointer; font-size: 0.95rem; opacity: 0.7; }
/* Pestañas */
.tabs-manual { display: flex; justify-content: center; gap: 40px; border-bottom: 1px solid $color-borde; }
.tab-manual {
  background: none; border: none; cursor: pointer;
  display: flex; flex-direction: column; align-items: center; gap: 6px;
  padding: 8px 4px 12px; margin-bottom: -1px;
  color: $color-texto-secundario; font-size: 1rem; font-weight: 500;
  border-bottom: 3px solid transparent;
  i { font-size: 1.25rem; }
  &:hover { color: $color-primario; }
  &.activa { color: $color-primario; border-bottom-color: $color-primario; font-weight: 600; }
  &:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(42,159,214,0.3); border-radius: 6px; }
}

/* Video */
.video-card { background: #fff; border-radius: 16px; padding: 16px; border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave; display: flex; flex-direction: column; gap: 14px; }
.video-wrap { position: relative; width: 100%; aspect-ratio: 16 / 9; border-radius: 10px; overflow: hidden; background: #0b1f2a; }
.video-wrap iframe, .video-wrap video { position: absolute; inset: 0; width: 100%; height: 100%; border: 0; }
.video-placeholder { position: absolute; inset: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 12px; color: rgba(255,255,255,0.7); font-weight: 600; i { font-size: 3.5rem; opacity: 0.6; } }
.video-desc { margin: 0; text-align: center; color: $color-texto-secundario; }
/* Partes del manual */
.intro-partes { display: flex; flex-direction: column; gap: 8px; margin: 14px 0 10px; }
.intro-parte { display: flex; align-items: baseline; gap: 12px; color: $color-texto-secundario; line-height: 1.5; }
.intro-parte-num { flex: 0 0 auto; background: rgba(0,60,88,0.08); color: $color-primario; border-radius: 6px; padding: 2px 10px; font-size: 0.8rem; font-weight: 700; }
.intro-nota { margin-top: 4px; }
.flujo-num-rrhh { background: $color-secundario; }
.parte-titulo { display: flex; align-items: center; gap: 10px; margin-top: 8px; font-size: 1.05rem; font-weight: 700; color: $color-primario; text-transform: uppercase; letter-spacing: 1px; i { font-size: 1.2rem; } &::after { content: ''; flex: 1; height: 1px; background: $color-borde; } }
.parte-titulo-rrhh { color: $color-secundario; }

/* Recursos humanos */
.rrhh-intro { display: flex; align-items: flex-start; gap: 20px; background: rgba(0,60,88,0.04); border-radius: 16px; padding: 24px; border: 1px dashed rgba(0,60,88,0.25); }
.rrhh-intro-icon { font-size: 2rem; color: $color-secundario; flex: 0 0 auto; }
.rrhh-intro-title { font-size: 1.15rem; font-weight: 700; color: $color-primario; margin: 0 0 8px; }
.rrhh-intro-desc { margin: 0 0 8px; color: $color-texto-secundario; line-height: 1.6; &:last-child { margin-bottom: 0; } }
.paso-card-rrhh { border-left: 4px solid $color-secundario; }
.paso-num-rrhh { background: $color-secundario; }
.paso-icon-rrhh { color: $color-secundario; }

.mockup-params-row-mid { border-radius: 0; }
.mockup-input-bloqueado { background: #eef1f3; color: $color-texto-secundario; display: inline-flex; align-items: center; gap: 6px; i { font-size: 0.75rem; } }
.mockup-accordion-abierto { border-color: rgba(0,60,88,0.3); background: rgba(0,60,88,0.04); i { margin-right: 6px; } }
.mockup-tabla-anidada { margin: 0 0 4px 16px; }
.mockup-grid-rrhh { grid-template-columns: 1.6fr 1fr 1fr 1fr 1.2fr; }
.mockup-grid-tiempos { grid-template-columns: 1.4fr 1.8fr 1fr 1fr 1fr 0.8fr; }
.mockup-grid-equipos { grid-template-columns: 2fr 1fr 1.4fr 1fr; }
.mockup-celda-vacia { color: $color-texto-secundario; opacity: 0.6; }
.mockup-segmento { display: inline-flex; border: 1px solid $color-borde; border-radius: 999px; overflow: hidden; font-size: 0.78rem; font-weight: 600; span { padding: 4px 10px; color: $color-texto-secundario; background: #fff; } .activo { background: $color-primario; color: #fff; } }

.modo-cards { display: flex; gap: 16px; flex-wrap: wrap; }
.modo-card { flex: 1; min-width: 240px; background: $color-claro; border: 1px solid $color-borde; border-radius: 12px; padding: 14px 16px; display: flex; flex-direction: column; gap: 8px; p { margin: 0; font-size: 0.9rem; color: $color-texto-secundario; line-height: 1.5; } }
.modo-chip { align-self: flex-start; display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; padding: 4px 12px; font-size: 0.85rem; font-weight: 700; }
.modo-chip-perm { background: rgba(0,60,88,0.1); color: $color-primario; }
.modo-chip-punt { background: #fff4e5; color: #b45309; }

.mockup-kpi { flex: 1; min-width: 160px; display: flex; flex-direction: column; gap: 4px; background: #fff; border: 1px solid $color-borde; border-radius: 12px; padding: 12px 16px; }
.mockup-kpi-val { font-size: 1.3rem; font-weight: 700; color: $color-primario; }
.mockup-kpi-lab { font-size: 0.82rem; color: $color-texto-secundario; i { margin-left: 4px; color: $color-primario; opacity: 0.7; } }
.mockup-kpi-alerta { border-color: #f5c08a; background: #fffaf3; .mockup-kpi-val { color: #b45309; } }
.mockup-dif { color: #b45309; font-weight: 700; }

/* Preguntas frecuentes */
.faq-lista { display: flex; flex-direction: column; gap: 8px; }
.faq-item { background: $color-claro; border: 1px solid $color-borde; border-radius: 10px; padding: 12px 16px;
  summary { cursor: pointer; font-weight: 600; color: $color-primario; }
  p { margin: 10px 0 0; color: $color-texto-secundario; line-height: 1.6; font-size: 0.9rem; }
  &[open] summary { margin-bottom: 2px; }
}
/* ===================== Carrusel de pasos ===================== */
.flujo-step { padding: 4px 6px; border-radius: 8px; transition: background .2s; }
.flujo-step.activo { background: rgba(0,60,88,0.08); }
.flujo-step.activo .flujo-label { text-decoration: underline; text-underline-offset: 4px; }

.carrusel { display: flex; flex-direction: column; align-items: center; gap: 18px; }
.carrusel-marco { position: relative; width: 100%; }

.slide {
  background: #fff; border-radius: 16px; padding: 28px 56px;
  border: 1px solid $color-borde; box-shadow: 0 10px 22px $color-sombra-suave;
  display: flex; flex-direction: column; gap: 20px;
}
.slide-rrhh { border-left: 4px solid $color-secundario; }

/* Encabezado del paso (mismo estilo de antes) */
.slide-tag { font-size: .78rem; font-weight: 700; letter-spacing: 1.5px; text-transform: uppercase; color: $color-texto-secundario; }
.slide-rrhh .slide-tag { color: $color-secundario; }
.paso-header { display: flex; align-items: flex-start; gap: 16px; }

/* Flechas circulares */
.carrusel-flecha {
  position: absolute; top: 90px; z-index: 5; width: 46px; height: 46px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
  background: #fff; border: 1px solid $color-borde; color: $color-primario; font-size: 1rem;
  box-shadow: 0 4px 14px rgba(0,30,45,.18); cursor: pointer; transition: transform .15s, box-shadow .15s, opacity .15s;
  &.izquierda { left: -16px; }
  &.derecha { right: -16px; }
  &:hover:not(:disabled) { transform: scale(1.07); box-shadow: 0 6px 18px rgba(0,30,45,.25); }
  &:disabled { opacity: .35; cursor: default; }
  &:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(42,159,214,0.4); }
}

/* Barra de puntos */
.carrusel-puntos { display: flex; gap: 10px; }
.punto {
  width: 30px; height: 6px; padding: 0; border: none; border-radius: 999px; cursor: pointer;
  background: #d4dbe1; transition: width .25s, background .25s;
  &.activo { width: 46px; background: $color-primario; }
  &:hover:not(.activo) { background: #b9c4cd; }
  &:focus-visible { outline: none; box-shadow: 0 0 0 3px rgba(42,159,214,0.4); }
}

/* Animación entre pasos */
.slide-izq-enter-active, .slide-izq-leave-active,
.slide-der-enter-active, .slide-der-leave-active { transition: opacity .22s ease, transform .22s ease; }
.slide-izq-enter-from { opacity: 0; transform: translateX(40px); }
.slide-izq-leave-to   { opacity: 0; transform: translateX(-40px); }
.slide-der-enter-from { opacity: 0; transform: translateX(-40px); }
.slide-der-leave-to   { opacity: 0; transform: translateX(40px); }
@media (prefers-reduced-motion: reduce) {
  .slide-izq-enter-active, .slide-izq-leave-active, .slide-der-enter-active, .slide-der-leave-active { transition: none; }
}

@media (max-width: 760px) {
  .slide { padding: 22px 28px; }
  .carrusel-flecha { top: 60px; &.izquierda { left: -10px; } &.derecha { right: -10px; } }
}
</style>