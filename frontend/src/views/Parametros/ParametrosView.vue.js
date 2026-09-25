import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import AppLayout from '@/layouts/AppLayout.vue';
const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const PRESTACIONES_STORAGE_KEY = 'ephdem_prestaciones_seleccionadas';
const PARAMETROS_STORAGE_KEY = 'ephdem_parametros_prestaciones';
const MINUTOS_POR_HORA = 60;
const infoTexts = {
    demanda: 'Cantidad de atenciones proyectadas para esta prestación en el período.',
    diasAnuales: 'Número de días disponibles al año para operar. Máximo 366 días.',
    tiempoProcedimiento: 'Minutos que dura el procedimiento.',
    disponibilidad: 'Porcentaje de disponibilidad real del equipo para esta prestación.',
    jornadaLaboral: 'Horas efectivas de operación al día. En atención cerrada normalmente 24.',
    calcCoeficienteTecnico: 'Número de camas UCI o UTI por cada 1.000 habitantes.',
    calcPuac: 'Población Usuaria Asignada al Centro (PUAC).',
    calcPromedioEstancia: 'Promedio de días que un paciente ocupa una cama.',
};
const filas = ref([]);
const tooltipPosicion = ref({ top: '0px', left: '0px', visible: false, texto: '', abrirIzquierda: false });
const erroresCeldas = ref(new Set());
const errorValidacion = ref('');
const nombreProyectoActivo = ref('');
const mostrarCalculadora = ref(false);
const calculadoraRef = ref(null);
const calc = ref({ coeficienteTecnico: null, puac: null, promedioEstancia: null });
const diasCamaCalculados = computed(() => {
    const ct = Number(calc.value.coeficienteTecnico);
    const puac = Number(calc.value.puac);
    const pe = Number(calc.value.promedioEstancia);
    if (!Number.isFinite(ct) || !Number.isFinite(puac) || !Number.isFinite(pe))
        return '—';
    const result = (ct * puac / 1000) * pe;
    return Number.isFinite(result) ? Math.ceil(result) : '—';
});
function mostrarTooltip(event) {
    const span = event.target;
    const rect = span.getBoundingClientRect();
    const TOOLTIP_WIDTH = 240;
    const abrirIzquierda = (rect.left + TOOLTIP_WIDTH + 16) > window.innerWidth;
    tooltipPosicion.value = { top: `${rect.bottom + 8}px`, left: abrirIzquierda ? `${rect.right - TOOLTIP_WIDTH}px` : `${rect.left}px`, visible: true, texto: span.getAttribute('data-tooltip'), abrirIzquierda };
}
function ocultarTooltip() { tooltipPosicion.value.visible = false; }
function crearFila(prestacion, parametrosGuardados) {
    return {
        id: prestacion.id,
        codigo_fonasa: prestacion.codigo_fonasa,
        nombre_prestacion: prestacion.nombre_prestacion,
        demanda: parametrosGuardados?.demanda ?? 0,
        diasAnuales: parametrosGuardados?.diasAnuales ?? 365,
        tiempoProcedimiento: parametrosGuardados?.tiempoProcedimiento ?? prestacion.tiempo_procedimiento ?? MINUTOS_POR_HORA,
        disponibilidad: parametrosGuardados?.disponibilidad ?? 100,
        jornadaLaboral: parametrosGuardados?.jornadaLaboral ?? 24,
    };
}
function cargarDatos() {
    const rawPrestaciones = localStorage.getItem(PRESTACIONES_STORAGE_KEY);
    if (!rawPrestaciones)
        return;
    const rawParametros = localStorage.getItem(PARAMETROS_STORAGE_KEY);
    try {
        const prestaciones = JSON.parse(rawPrestaciones);
        if (!Array.isArray(prestaciones))
            return;
        const parametrosGuardados = rawParametros ? JSON.parse(rawParametros) : [];
        const parametrosMap = new Map(Array.isArray(parametrosGuardados) ? parametrosGuardados.map(item => [item.id, item]) : []);
        filas.value = prestaciones.map(prestacion => crearFila(prestacion, parametrosMap.get(prestacion.id)));
    }
    catch (error) {
        localStorage.removeItem(PRESTACIONES_STORAGE_KEY);
        localStorage.removeItem(PARAMETROS_STORAGE_KEY);
        alert('No se pudieron cargar las prestaciones/parametros guardados.');
    }
}
const CAMPOS_REQUERIDOS = ['demanda', 'diasAnuales', 'tiempoProcedimiento', 'disponibilidad', 'jornadaLaboral'];
function validarFilas() {
    const errores = new Set();
    for (const fila of filas.value) {
        for (const campo of CAMPOS_REQUERIDOS) {
            const val = fila[campo];
            if (val === null || val === undefined || val === '' || Number(val) <= 0)
                errores.add(`${fila.id}-${campo}`);
        }
        if (Number(fila.diasAnuales) > 366)
            errores.add(`${fila.id}-diasAnuales`);
        if (Number(fila.disponibilidad) > 100)
            errores.add(`${fila.id}-disponibilidad`);
        if (Number(fila.jornadaLaboral) > 24)
            errores.add(`${fila.id}-jornadaLaboral`);
    }
    erroresCeldas.value = errores;
    return errores.size === 0;
}
function limpiarError(id, campo) {
    erroresCeldas.value.delete(`${id}-${campo}`);
    erroresCeldas.value = new Set(erroresCeldas.value);
    if (erroresCeldas.value.size === 0)
        errorValidacion.value = '';
}
async function guardarYCalcular() {
    if (!validarFilas()) {
        errorValidacion.value = 'Hay celdas vacías, con valor 0, o con valores fuera de rango. Revisa los campos marcados en rojo.';
        return;
    }
    errorValidacion.value = '';
    const idProyectoActual = route.params.proyectoId || localStorage.getItem('ephdem_proyecto_activo');
    if (!idProyectoActual) {
        alert('No hay un proyecto activo.');
        router.push('/crear-proyecto');
        return;
    }
    localStorage.setItem(PARAMETROS_STORAGE_KEY, JSON.stringify(filas.value));
    const tpOverrides = {};
    for (const fila of filas.value)
        tpOverrides[fila.id] = fila.tiempoProcedimiento;
    localStorage.setItem(`ephdem_tp_overrides_${idProyectoActual}`, JSON.stringify(tpOverrides));
    const payload = {
        proyecto_id: Number(idProyectoActual),
        filas: filas.value.map(fila => ({
            prestacion_id: fila.id,
            demanda_anual: Number(fila.demanda),
            dias_laborales: Number(fila.diasAnuales),
            disponibilidad: Number(fila.disponibilidad) / 100,
            jornada_efectiva: Number(fila.jornadaLaboral),
        })),
    };
    try {
        const resp = await fetch(`${import.meta.env.VITE_API_BASE}/calcular_demanda.php`, { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify(payload) });
        const data = await resp.json();
        if (!data.ok) {
            alert('Error al calcular: ' + (data.error || ''));
            return;
        }
        localStorage.setItem('ephdem_resultado_calculo', JSON.stringify(data.datos));
        router.push(`/resultados/${idProyectoActual}`);
    }
    catch (error) {
        alert('No se pudo conectar con el servidor de cálculo.');
    }
}
function volverAtras() { router.back(); setTimeout(() => window.scrollTo({ top: 0, left: 0, behavior: 'auto' }), 0); }
function irAPrestaciones() {
    localStorage.setItem(PARAMETROS_STORAGE_KEY, JSON.stringify(filas.value));
    if (route.params.proyectoId) {
        router.push(`/prestaciones/${route.params.proyectoId}`);
    }
    else {
        router.push('/prestaciones');
    }
}
function cerrarSesion() { authStore.logout(); router.push('/login'); }
function limpiarCalculadora() { calc.value = { coeficienteTecnico: null, puac: null, promedioEstancia: null }; }
function cerrarCalculadoraSiCorresponde(event) {
    const contenedor = calculadoraRef.value;
    if (!contenedor || !mostrarCalculadora.value)
        return;
    if (!contenedor.contains(event.target))
        mostrarCalculadora.value = false;
}
async function cargarDesdeServidor(proyectoId) {
    try {
        const [respDemanda, respPrestaciones] = await Promise.all([
            fetch(`${import.meta.env.VITE_API_BASE}/get/get_prestaciones_demanda.php?proyecto_id=${proyectoId}`),
            fetch(`${import.meta.env.VITE_API_BASE}/get/get_prestaciones.php`),
        ]);
        const json = await respDemanda.json();
        const jsonPrestaciones = await respPrestaciones.json();
        if (!respDemanda.ok || !json.ok) {
            alert(json.error || 'Error al cargar datos del proyecto.');
            router.push('/proyectos');
            return;
        }
        const tiempoMap = new Map();
        if (jsonPrestaciones?.ok && Array.isArray(jsonPrestaciones?.datos)) {
            for (const p of jsonPrestaciones.datos) {
                if (p.id_prestacion != null && p.tiempo_procedimiento != null)
                    tiempoMap.set(p.id_prestacion, p.tiempo_procedimiento);
            }
        }
        let tpOverrides = {};
        try {
            const raw = localStorage.getItem(`ephdem_tp_overrides_${proyectoId}`);
            if (raw)
                tpOverrides = JSON.parse(raw);
        }
        catch (_) { }
        filas.value = json.datos.map(item => {
            const vals = item.valores;
            const defs = item.defaults || {};
            const tiempoProcedimiento = tpOverrides[item.id_prestacion] ?? tiempoMap.get(item.id_prestacion) ?? MINUTOS_POR_HORA;
            return {
                id: item.id_prestacion, codigo_fonasa: item.codigo_fonasa || '', nombre_prestacion: item.nombre_prestacion,
                demanda: vals?.demanda_anual ?? 0,
                diasAnuales: vals ? vals.dias_laborales : (defs.dias_laborales ?? 365),
                tiempoProcedimiento,
                disponibilidad: vals ? (vals.disponibilidad * 100) : (defs.disponibilidad ? defs.disponibilidad * 100 : 100),
                jornadaLaboral: vals ? vals.jornada_efectiva : (defs.jornada_efectiva ?? 24)
            };
        });
    }
    catch (e) {
        alert('Error de red al intentar cargar datos del proyecto.');
    }
}
onMounted(() => {
    nombreProyectoActivo.value = localStorage.getItem('ephdem_nombre_proyecto_activo') || 'Desconocido';
    if (route.params.proyectoId) {
        if (localStorage.getItem('ephdem_origen_edicion') === 'prestaciones') {
            localStorage.removeItem('ephdem_origen_edicion');
            cargarDatos();
        }
        else {
            cargarDesdeServidor(route.params.proyectoId);
        }
    }
    else {
        cargarDatos();
    }
    document.addEventListener('pointerdown', cerrarCalculadoraSiCorresponde);
});
onBeforeUnmount(() => { document.removeEventListener('pointerdown', cerrarCalculadoraSiCorresponde); });
const __VLS_ctx = {
    ...{},
    ...{},
};
let __VLS_components;
let __VLS_intrinsics;
let __VLS_directives;
/** @type {__VLS_StyleScopedClasses['is-open']} */ ;
/** @type {__VLS_StyleScopedClasses['panel-vacio']} */ ;
/** @type {__VLS_StyleScopedClasses['tabla-parametros']} */ ;
/** @type {__VLS_StyleScopedClasses['tabla-parametros']} */ ;
/** @type {__VLS_StyleScopedClasses['tabla-parametros']} */ ;
/** @type {__VLS_StyleScopedClasses['tabla-parametros']} */ ;
/** @type {__VLS_StyleScopedClasses['tabla-parametros']} */ ;
/** @type {__VLS_StyleScopedClasses['tooltip-flecha']} */ ;
const __VLS_0 = AppLayout || AppLayout;
// @ts-ignore
const __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0({}));
const __VLS_2 = __VLS_1({}, ...__VLS_functionalComponentArgsRest(__VLS_1));
var __VLS_5 = {};
const { default: __VLS_6 } = __VLS_3.slots;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "parametros-page" },
});
/** @type {__VLS_StyleScopedClasses['parametros-page']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)({
    ...{ class: "hero hero-compact" },
});
/** @type {__VLS_StyleScopedClasses['hero']} */ ;
/** @type {__VLS_StyleScopedClasses['hero-compact']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "hero-bg" },
});
/** @type {__VLS_StyleScopedClasses['hero-bg']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "hero-content" },
});
/** @type {__VLS_StyleScopedClasses['hero-content']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "hero-tag" },
});
/** @type {__VLS_StyleScopedClasses['hero-tag']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h1, __VLS_intrinsics.h1)({
    ...{ class: "hero-title" },
});
/** @type {__VLS_StyleScopedClasses['hero-title']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({
    ...{ class: "hero-sub" },
});
/** @type {__VLS_StyleScopedClasses['hero-sub']} */ ;
if (__VLS_ctx.tooltipPosicion.visible) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "tooltip-flotante" },
        ...{ class: ({ 'tooltip-flotante--left': __VLS_ctx.tooltipPosicion.abrirIzquierda }) },
        ...{ style: ({ top: __VLS_ctx.tooltipPosicion.top, left: __VLS_ctx.tooltipPosicion.left }) },
    });
    /** @type {__VLS_StyleScopedClasses['tooltip-flotante']} */ ;
    /** @type {__VLS_StyleScopedClasses['tooltip-flotante--left']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "tooltip-contenido" },
    });
    /** @type {__VLS_StyleScopedClasses['tooltip-contenido']} */ ;
    (__VLS_ctx.tooltipPosicion.texto);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "tooltip-flecha" },
    });
    /** @type {__VLS_StyleScopedClasses['tooltip-flecha']} */ ;
}
__VLS_asFunctionalElement1(__VLS_intrinsics.main, __VLS_intrinsics.main)({
    ...{ class: "parametros-content" },
});
/** @type {__VLS_StyleScopedClasses['parametros-content']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.header, __VLS_intrinsics.header)({
    ...{ class: "parametros-header" },
});
/** @type {__VLS_StyleScopedClasses['parametros-header']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "nav-bar" },
});
/** @type {__VLS_StyleScopedClasses['nav-bar']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "nav-buttons" },
});
/** @type {__VLS_StyleScopedClasses['nav-buttons']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
    ...{ onClick: (__VLS_ctx.volverAtras) },
    ...{ class: "btn-back" },
    type: "button",
});
/** @type {__VLS_StyleScopedClasses['btn-back']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
    ...{ class: "fa-solid fa-arrow-left" },
});
/** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-arrow-left']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
    ...{ onClick: (...[$event]) => {
            __VLS_ctx.router.push('/inicio');
            // @ts-ignore
            [tooltipPosicion, tooltipPosicion, tooltipPosicion, tooltipPosicion, tooltipPosicion, volverAtras, router,];
        } },
    ...{ class: "btn-back" },
    type: "button",
});
/** @type {__VLS_StyleScopedClasses['btn-back']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
    ...{ class: "fa-solid fa-house-user" },
});
/** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-house-user']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "nav-divider" },
});
/** @type {__VLS_StyleScopedClasses['nav-divider']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
    ...{ onClick: (__VLS_ctx.irAPrestaciones) },
    ...{ class: "btn-back" },
    type: "button",
});
/** @type {__VLS_StyleScopedClasses['btn-back']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
    ...{ class: "fa-solid fa-list-check" },
});
/** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-list-check']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "session-badge" },
});
/** @type {__VLS_StyleScopedClasses['session-badge']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
    ...{ class: "fa-solid fa-circle-user" },
});
/** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-circle-user']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
    ...{ class: "session-nombre" },
});
/** @type {__VLS_StyleScopedClasses['session-nombre']} */ ;
(__VLS_ctx.authStore.correoUsuario);
__VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
    ...{ onClick: (__VLS_ctx.cerrarSesion) },
    ...{ class: "btn-logout" },
    type: "button",
    title: "Cerrar sesión",
});
/** @type {__VLS_StyleScopedClasses['btn-logout']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
    ...{ class: "fa-solid fa-right-from-bracket" },
});
/** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-right-from-bracket']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "parametros-header-top" },
});
/** @type {__VLS_StyleScopedClasses['parametros-header-top']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)({
    ...{ class: "section-title" },
});
/** @type {__VLS_StyleScopedClasses['section-title']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "calculadora-wrapper" },
    ref: "calculadoraRef",
});
/** @type {__VLS_StyleScopedClasses['calculadora-wrapper']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
    ...{ onClick: (...[$event]) => {
            __VLS_ctx.mostrarCalculadora = !__VLS_ctx.mostrarCalculadora;
            // @ts-ignore
            [irAPrestaciones, authStore, cerrarSesion, mostrarCalculadora, mostrarCalculadora,];
        } },
    type: "button",
    ...{ class: "calculadora-toggle" },
    'aria-expanded': (__VLS_ctx.mostrarCalculadora),
});
/** @type {__VLS_StyleScopedClasses['calculadora-toggle']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
    ...{ class: "calculadora-icono" },
});
/** @type {__VLS_StyleScopedClasses['calculadora-icono']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
    ...{ class: "fa-solid fa-calculator" },
});
/** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-calculator']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
    ...{ class: "calculadora-texto" },
});
/** @type {__VLS_StyleScopedClasses['calculadora-texto']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
    ...{ class: "fa-solid fa-chevron-down calculadora-chevron" },
    ...{ class: ({ 'is-open': __VLS_ctx.mostrarCalculadora }) },
});
/** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-chevron-down']} */ ;
/** @type {__VLS_StyleScopedClasses['calculadora-chevron']} */ ;
/** @type {__VLS_StyleScopedClasses['is-open']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)({
    ...{ class: "calculadora-panel" },
    ...{ class: ({ 'is-open': __VLS_ctx.mostrarCalculadora }) },
});
/** @type {__VLS_StyleScopedClasses['calculadora-panel']} */ ;
/** @type {__VLS_StyleScopedClasses['is-open']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "calculadora-contenido" },
});
__VLS_asFunctionalDirective(__VLS_directives.vShow, {})(null, { ...__VLS_directiveBindingRestFields, value: (__VLS_ctx.mostrarCalculadora) }, null, null);
/** @type {__VLS_StyleScopedClasses['calculadora-contenido']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({
    ...{ class: "calc-formula-hint" },
});
/** @type {__VLS_StyleScopedClasses['calc-formula-hint']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
    ...{ class: "fa-solid fa-circle-info" },
});
/** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-circle-info']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "calc-form" },
});
/** @type {__VLS_StyleScopedClasses['calc-form']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "calc-fields" },
});
/** @type {__VLS_StyleScopedClasses['calc-fields']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "calc-field" },
});
/** @type {__VLS_StyleScopedClasses['calc-field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
    ...{ onMouseenter: (__VLS_ctx.mostrarTooltip) },
    ...{ onMouseleave: (__VLS_ctx.ocultarTooltip) },
    ...{ class: "info-icon" },
    'data-tooltip': (__VLS_ctx.infoTexts.calcCoeficienteTecnico),
});
/** @type {__VLS_StyleScopedClasses['info-icon']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.input)({
    type: "number",
    min: "0",
    step: "0.001",
    placeholder: "0.000",
});
(__VLS_ctx.calc.coeficienteTecnico);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "calc-field" },
});
/** @type {__VLS_StyleScopedClasses['calc-field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
    ...{ class: "calc-field-hint" },
});
/** @type {__VLS_StyleScopedClasses['calc-field-hint']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
    ...{ onMouseenter: (__VLS_ctx.mostrarTooltip) },
    ...{ onMouseleave: (__VLS_ctx.ocultarTooltip) },
    ...{ class: "info-icon" },
    'data-tooltip': (__VLS_ctx.infoTexts.calcPuac),
});
/** @type {__VLS_StyleScopedClasses['info-icon']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.input)({
    type: "number",
    min: "0",
    step: "1",
    placeholder: "0",
});
(__VLS_ctx.calc.puac);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "calc-field" },
});
/** @type {__VLS_StyleScopedClasses['calc-field']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
    ...{ onMouseenter: (__VLS_ctx.mostrarTooltip) },
    ...{ onMouseleave: (__VLS_ctx.ocultarTooltip) },
    ...{ class: "info-icon" },
    'data-tooltip': (__VLS_ctx.infoTexts.calcPromedioEstancia),
});
/** @type {__VLS_StyleScopedClasses['info-icon']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.input)({
    type: "number",
    min: "0",
    step: "0.1",
    placeholder: "0",
});
(__VLS_ctx.calc.promedioEstancia);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "calc-resultado" },
});
/** @type {__VLS_StyleScopedClasses['calc-resultado']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "calc-resultado-label" },
});
/** @type {__VLS_StyleScopedClasses['calc-resultado-label']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "calc-resultado-valor" },
});
/** @type {__VLS_StyleScopedClasses['calc-resultado-valor']} */ ;
(__VLS_ctx.diasCamaCalculados);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "calc-footer" },
});
/** @type {__VLS_StyleScopedClasses['calc-footer']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
    ...{ onClick: (__VLS_ctx.limpiarCalculadora) },
    type: "button",
    ...{ class: "calc-limpiar" },
});
/** @type {__VLS_StyleScopedClasses['calc-limpiar']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
    ...{ class: "fa-solid fa-rotate-left" },
});
/** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-rotate-left']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "proyecto-activo-badge" },
});
/** @type {__VLS_StyleScopedClasses['proyecto-activo-badge']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
    ...{ class: "badge-label" },
});
/** @type {__VLS_StyleScopedClasses['badge-label']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
    ...{ class: "badge-name" },
});
/** @type {__VLS_StyleScopedClasses['badge-name']} */ ;
(__VLS_ctx.nombreProyectoActivo);
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "instruccion-indicator" },
});
/** @type {__VLS_StyleScopedClasses['instruccion-indicator']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
    ...{ class: "instruccion-icon-circle" },
});
/** @type {__VLS_StyleScopedClasses['instruccion-icon-circle']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
    ...{ class: "fa-solid fa-circle-info" },
});
/** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-circle-info']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
    ...{ class: "instruccion-texto" },
});
/** @type {__VLS_StyleScopedClasses['instruccion-texto']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
    ...{ class: "info-icon info-icon--demo" },
    'aria-hidden': "true",
});
/** @type {__VLS_StyleScopedClasses['info-icon']} */ ;
/** @type {__VLS_StyleScopedClasses['info-icon--demo']} */ ;
if (__VLS_ctx.filas.length === 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)({
        ...{ class: "panel-vacio" },
    });
    /** @type {__VLS_StyleScopedClasses['panel-vacio']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
        ...{ onClick: (...[$event]) => {
                if (!(__VLS_ctx.filas.length === 0))
                    return;
                __VLS_ctx.router.push('/prestaciones');
                // @ts-ignore
                [router, mostrarCalculadora, mostrarCalculadora, mostrarCalculadora, mostrarCalculadora, mostrarTooltip, mostrarTooltip, mostrarTooltip, ocultarTooltip, ocultarTooltip, ocultarTooltip, infoTexts, infoTexts, infoTexts, calc, calc, calc, diasCamaCalculados, limpiarCalculadora, nombreProyectoActivo, filas,];
            } },
        ...{ class: "btn-secundario" },
    });
    /** @type {__VLS_StyleScopedClasses['btn-secundario']} */ ;
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)({
        ...{ class: "tabla-panel" },
    });
    /** @type {__VLS_StyleScopedClasses['tabla-panel']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "tabla-scroll" },
    });
    /** @type {__VLS_StyleScopedClasses['tabla-scroll']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.table, __VLS_intrinsics.table)({
        ...{ class: "tabla-parametros" },
    });
    /** @type {__VLS_StyleScopedClasses['tabla-parametros']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.thead, __VLS_intrinsics.thead)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.tr, __VLS_intrinsics.tr)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
        ...{ onMouseenter: (__VLS_ctx.mostrarTooltip) },
        ...{ onMouseleave: (__VLS_ctx.ocultarTooltip) },
        ...{ class: "info-icon" },
        'data-tooltip': (__VLS_ctx.infoTexts.demanda),
    });
    /** @type {__VLS_StyleScopedClasses['info-icon']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
        ...{ onMouseenter: (__VLS_ctx.mostrarTooltip) },
        ...{ onMouseleave: (__VLS_ctx.ocultarTooltip) },
        ...{ class: "info-icon" },
        'data-tooltip': (__VLS_ctx.infoTexts.diasAnuales),
    });
    /** @type {__VLS_StyleScopedClasses['info-icon']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
        ...{ onMouseenter: (__VLS_ctx.mostrarTooltip) },
        ...{ onMouseleave: (__VLS_ctx.ocultarTooltip) },
        ...{ class: "info-icon" },
        'data-tooltip': (__VLS_ctx.infoTexts.tiempoProcedimiento),
    });
    /** @type {__VLS_StyleScopedClasses['info-icon']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
        ...{ onMouseenter: (__VLS_ctx.mostrarTooltip) },
        ...{ onMouseleave: (__VLS_ctx.ocultarTooltip) },
        ...{ class: "info-icon" },
        'data-tooltip': (__VLS_ctx.infoTexts.disponibilidad),
    });
    /** @type {__VLS_StyleScopedClasses['info-icon']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
        ...{ onMouseenter: (__VLS_ctx.mostrarTooltip) },
        ...{ onMouseleave: (__VLS_ctx.ocultarTooltip) },
        ...{ class: "info-icon" },
        'data-tooltip': (__VLS_ctx.infoTexts.jornadaLaboral),
    });
    /** @type {__VLS_StyleScopedClasses['info-icon']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.tbody, __VLS_intrinsics.tbody)({});
    for (const [fila] of __VLS_vFor((__VLS_ctx.filas))) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.tr, __VLS_intrinsics.tr)({
            key: (fila.id),
        });
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "prestacion-codigo" },
        });
        /** @type {__VLS_StyleScopedClasses['prestacion-codigo']} */ ;
        (fila.codigo_fonasa);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "prestacion-nombre" },
        });
        /** @type {__VLS_StyleScopedClasses['prestacion-nombre']} */ ;
        (fila.nombre_prestacion);
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.input)({
            ...{ onInput: (...[$event]) => {
                    if (!!(__VLS_ctx.filas.length === 0))
                        return;
                    __VLS_ctx.limpiarError(fila.id, 'demanda');
                    // @ts-ignore
                    [mostrarTooltip, mostrarTooltip, mostrarTooltip, mostrarTooltip, mostrarTooltip, ocultarTooltip, ocultarTooltip, ocultarTooltip, ocultarTooltip, ocultarTooltip, infoTexts, infoTexts, infoTexts, infoTexts, infoTexts, filas, limpiarError,];
                } },
            type: "number",
            min: "0",
            step: "1",
            ...{ class: ({ 'input-error': __VLS_ctx.erroresCeldas.has(`${fila.id}-demanda`) }) },
        });
        (fila.demanda);
        /** @type {__VLS_StyleScopedClasses['input-error']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.input)({
            ...{ onInput: (...[$event]) => {
                    if (!!(__VLS_ctx.filas.length === 0))
                        return;
                    __VLS_ctx.limpiarError(fila.id, 'diasAnuales');
                    // @ts-ignore
                    [limpiarError, erroresCeldas,];
                } },
            type: "number",
            min: "1",
            max: "366",
            step: "1",
            ...{ class: ({ 'input-error': __VLS_ctx.erroresCeldas.has(`${fila.id}-diasAnuales`) }) },
        });
        (fila.diasAnuales);
        /** @type {__VLS_StyleScopedClasses['input-error']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.input)({
            ...{ onInput: (...[$event]) => {
                    if (!!(__VLS_ctx.filas.length === 0))
                        return;
                    __VLS_ctx.limpiarError(fila.id, 'tiempoProcedimiento');
                    // @ts-ignore
                    [limpiarError, erroresCeldas,];
                } },
            type: "number",
            min: "0",
            step: "0.1",
            ...{ class: ({ 'input-error': __VLS_ctx.erroresCeldas.has(`${fila.id}-tiempoProcedimiento`) }) },
        });
        (fila.tiempoProcedimiento);
        /** @type {__VLS_StyleScopedClasses['input-error']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.input)({
            ...{ onInput: (...[$event]) => {
                    if (!!(__VLS_ctx.filas.length === 0))
                        return;
                    __VLS_ctx.limpiarError(fila.id, 'disponibilidad');
                    // @ts-ignore
                    [limpiarError, erroresCeldas,];
                } },
            type: "number",
            min: "0",
            max: "100",
            step: "0.1",
            ...{ class: ({ 'input-error': __VLS_ctx.erroresCeldas.has(`${fila.id}-disponibilidad`) }) },
        });
        (fila.disponibilidad);
        /** @type {__VLS_StyleScopedClasses['input-error']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.input)({
            ...{ onInput: (...[$event]) => {
                    if (!!(__VLS_ctx.filas.length === 0))
                        return;
                    __VLS_ctx.limpiarError(fila.id, 'jornadaLaboral');
                    // @ts-ignore
                    [limpiarError, erroresCeldas,];
                } },
            type: "number",
            min: "0",
            max: "24",
            step: "0.1",
            ...{ class: ({ 'input-error': __VLS_ctx.erroresCeldas.has(`${fila.id}-jornadaLaboral`) }) },
        });
        (fila.jornadaLaboral);
        /** @type {__VLS_StyleScopedClasses['input-error']} */ ;
        // @ts-ignore
        [erroresCeldas,];
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "acciones-finales" },
    });
    /** @type {__VLS_StyleScopedClasses['acciones-finales']} */ ;
    let __VLS_7;
    /** @ts-ignore @type { | typeof __VLS_components.transition | typeof __VLS_components.Transition | typeof __VLS_components.transition | typeof __VLS_components.Transition} */
    transition;
    // @ts-ignore
    const __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7({
        name: "fade-error",
    }));
    const __VLS_9 = __VLS_8({
        name: "fade-error",
    }, ...__VLS_functionalComponentArgsRest(__VLS_8));
    const { default: __VLS_12 } = __VLS_10.slots;
    if (__VLS_ctx.errorValidacion) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "banner-error" },
            role: "alert",
        });
        /** @type {__VLS_StyleScopedClasses['banner-error']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
            ...{ class: "fa-solid fa-triangle-exclamation" },
        });
        /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
        /** @type {__VLS_StyleScopedClasses['fa-triangle-exclamation']} */ ;
        (__VLS_ctx.errorValidacion);
    }
    // @ts-ignore
    [errorValidacion, errorValidacion,];
    var __VLS_10;
    __VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
        ...{ onClick: (__VLS_ctx.guardarYCalcular) },
        ...{ class: "btn-principal" },
    });
    /** @type {__VLS_StyleScopedClasses['btn-principal']} */ ;
}
// @ts-ignore
[guardarYCalcular,];
var __VLS_3;
// @ts-ignore
[];
const __VLS_export = (await import('vue')).defineComponent({});
export default {};
