import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import AppLayout from '@/layouts/AppLayout.vue';
const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const filas = ref([]);
const errores = ref(new Set());
const errorValidacion = ref('');
const nombreProyectoActivo = ref('');
function calcularEEMM(fila) {
    const { demanda, tiempo, diasLaborales, nSimultaneas, disponibilidad, jornada } = fila;
    if (demanda > 0 && tiempo > 0 && diasLaborales > 0 && nSimultaneas > 0 && disponibilidad > 0 && jornada > 0) {
        const demandaDiaria = demanda / diasLaborales;
        const procHora = 60 / tiempo;
        const capDiaria = procHora * jornada;
        const capConDisp = capDiaria * (disponibilidad / 100);
        fila.eemm = demandaDiaria / (capConDisp * nSimultaneas);
    }
    else {
        fila.eemm = null;
    }
}
function setDiasLaborales(dias) {
    filas.value.forEach(f => { f.diasLaborales = dias; calcularEEMM(f); });
}
async function cargarDatos() {
    const raw = localStorage.getItem('ephdem_prestaciones_abierta');
    if (!raw)
        return;
    try {
        const prestaciones = JSON.parse(raw);
        const proyectoId = route.params.proyectoId || localStorage.getItem('ephdem_proyecto_activo_abierta');
        const usuarioId = authStore.usuarioId;
        // Cargar parámetros guardados del servidor
        let parametrosGuardados = {};
        if (proyectoId) {
            try {
                const url = `${import.meta.env.VITE_API_BASE}/get/get_prestaciones_demanda_abierta.php?proyecto_id=${proyectoId}&usuario_id=${usuarioId}`;
                const resp = await fetch(url);
                const json = await resp.json();
                if (json.ok && Array.isArray(json.datos)) {
                    json.datos.forEach(p => {
                        parametrosGuardados[p.ID_PRESTACION] = p;
                    });
                }
            }
            catch (e) { }
        }
        filas.value = prestaciones.map(p => {
            const guardado = parametrosGuardados[p.ID_PRESTACION];
            return {
                ID_PRESTACION: p.ID_PRESTACION,
                cod_prestacion: p.cod_prestacion,
                nombre_prestacion: p.nombre_prestacion,
                area: p.area,
                demanda: guardado?.demanda ?? 0,
                tiempo: guardado?.tiempo ?? 0,
                diasLaborales: guardado?.diasLaborales ?? 260,
                nSimultaneas: guardado?.nSimultaneas ?? 1,
                disponibilidad: guardado?.disponibilidad ?? 100,
                jornada: guardado?.jornada ?? 7,
                eemm: null,
            };
        });
        filas.value.forEach(f => calcularEEMM(f));
    }
    catch (e) {
        console.error('Error al cargar prestaciones:', e);
    }
}
async function cargarDesdeServidor(proyectoId) {
    const usuarioId = authStore.usuarioId;
    try {
        const url = `${import.meta.env.VITE_API_BASE}/get/get_prestaciones_demanda_abierta.php?proyecto_id=${proyectoId}&usuario_id=${usuarioId}`;
        const resp = await fetch(url);
        const json = await resp.json();
        if (!json.ok || !Array.isArray(json.datos) || json.datos.length === 0) {
            cargarDatos();
            return;
        }
        filas.value = json.datos.map(p => ({
            ID_PRESTACION: p.ID_PRESTACION,
            cod_prestacion: p.cod_prestacion,
            nombre_prestacion: p.nombre_prestacion,
            area: p.area,
            demanda: p.demanda,
            tiempo: p.tiempo,
            diasLaborales: p.diasLaborales,
            nSimultaneas: p.nSimultaneas,
            disponibilidad: p.disponibilidad,
            jornada: p.jornada,
            eemm: null,
        }));
        filas.value.forEach(f => calcularEEMM(f));
    }
    catch (e) {
        console.error('Error al cargar desde servidor:', e);
        cargarDatos();
    }
}
function validar() {
    const errs = new Set();
    for (const f of filas.value) {
        if (!f.demanda || f.demanda <= 0)
            errs.add(`${f.ID_PRESTACION}-demanda`);
        if (!f.tiempo || f.tiempo <= 0)
            errs.add(`${f.ID_PRESTACION}-tiempo`);
        if (!f.diasLaborales || f.diasLaborales <= 0)
            errs.add(`${f.ID_PRESTACION}-diasLaborales`);
        if (!f.nSimultaneas || f.nSimultaneas <= 0)
            errs.add(`${f.ID_PRESTACION}-nSimultaneas`);
        if (!f.disponibilidad || f.disponibilidad <= 0)
            errs.add(`${f.ID_PRESTACION}-disponibilidad`);
    }
    errores.value = errs;
    return errs.size === 0;
}
async function guardarYCalcular() {
    if (!validar()) {
        errorValidacion.value = 'Hay campos vacíos o con valor 0. Revisa los campos marcados en rojo.';
        return;
    }
    errorValidacion.value = '';
    const proyectoId = route.params.proyectoId || localStorage.getItem('ephdem_proyecto_activo_abierta');
    const userId = authStore.usuarioId;
    const payload = {
        proyecto_id: Number(proyectoId),
        usuario_id: userId,
        filas: filas.value.map(f => ({
            prestacion_id: f.ID_PRESTACION,
            demanda_anual: f.demanda,
            tiempo_proc: f.tiempo,
            dias_laborales: f.diasLaborales,
            n_simultaneas: f.nSimultaneas,
            disponibilidad: f.disponibilidad / 100,
            jornada: f.jornada,
            requerimiento: f.eemm ?? 0,
        }))
    };
    try {
        const resp = await fetch(`${import.meta.env.VITE_API_BASE}/calcular_demanda_abierta.php`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const data = await resp.json();
        if (!data.ok) {
            alert('Error al calcular: ' + (data.error || ''));
            return;
        }
        localStorage.setItem('ephdem_resultado_abierta', JSON.stringify(data.datos));
        router.push(`/resultados-abierta/${proyectoId}`);
    }
    catch (e) {
        alert('No se pudo conectar con el servidor.');
    }
}
function irAPrestaciones() {
    const proyectoId = route.params.proyectoId || localStorage.getItem('ephdem_proyecto_activo_abierta');
    if (proyectoId)
        router.push(`/prestaciones-abierta/${proyectoId}`);
    else
        router.push('/prestaciones-abierta');
}
function volverAtras() { router.back(); }
function cerrarSesion() { authStore.logout(); router.push('/login'); }
onMounted(async () => {
    nombreProyectoActivo.value = localStorage.getItem('ephdem_nombre_proyecto_activo_abierta') || 'Desconocido';
    const proyectoId = route.params.proyectoId || localStorage.getItem('ephdem_proyecto_activo_abierta');
    const rawLocal = localStorage.getItem('ephdem_prestaciones_abierta');
    if (rawLocal) {
        await cargarDatos();
    }
    else if (proyectoId) {
        await cargarDesdeServidor(proyectoId);
    }
});
const __VLS_ctx = {
    ...{},
    ...{},
};
let __VLS_components;
let __VLS_intrinsics;
let __VLS_directives;
/** @type {__VLS_StyleScopedClasses['panel-vacio']} */ ;
/** @type {__VLS_StyleScopedClasses['tabla-parametros']} */ ;
/** @type {__VLS_StyleScopedClasses['tabla-parametros']} */ ;
/** @type {__VLS_StyleScopedClasses['tabla-parametros']} */ ;
/** @type {__VLS_StyleScopedClasses['tabla-parametros']} */ ;
/** @type {__VLS_StyleScopedClasses['tabla-parametros']} */ ;
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
            [volverAtras, router,];
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
});
/** @type {__VLS_StyleScopedClasses['btn-logout']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
    ...{ class: "fa-solid fa-right-from-bracket" },
});
/** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-right-from-bracket']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)({
    ...{ class: "section-title" },
});
/** @type {__VLS_StyleScopedClasses['section-title']} */ ;
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
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "botones-dias" },
});
/** @type {__VLS_StyleScopedClasses['botones-dias']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
    ...{ onClick: (...[$event]) => {
            __VLS_ctx.setDiasLaborales(260);
            // @ts-ignore
            [irAPrestaciones, authStore, cerrarSesion, nombreProyectoActivo, setDiasLaborales,];
        } },
    ...{ class: "btn-dias" },
    type: "button",
});
/** @type {__VLS_StyleScopedClasses['btn-dias']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
    ...{ class: "fa-solid fa-calendar-days" },
});
/** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-calendar-days']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
    ...{ onClick: (...[$event]) => {
            __VLS_ctx.setDiasLaborales(365);
            // @ts-ignore
            [setDiasLaborales,];
        } },
    ...{ class: "btn-dias" },
    type: "button",
});
/** @type {__VLS_StyleScopedClasses['btn-dias']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
    ...{ class: "fa-solid fa-calendar" },
});
/** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-calendar']} */ ;
if (__VLS_ctx.filas.length === 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)({
        ...{ class: "panel-vacio" },
    });
    /** @type {__VLS_StyleScopedClasses['panel-vacio']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
        ...{ onClick: (__VLS_ctx.irAPrestaciones) },
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
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.tbody, __VLS_intrinsics.tbody)({});
    for (const [fila] of __VLS_vFor((__VLS_ctx.filas))) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.tr, __VLS_intrinsics.tr)({
            key: (fila.ID_PRESTACION),
        });
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)({
            ...{ class: "td-codigo" },
        });
        /** @type {__VLS_StyleScopedClasses['td-codigo']} */ ;
        (fila.cod_prestacion);
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)({
            ...{ class: "td-nombre" },
        });
        /** @type {__VLS_StyleScopedClasses['td-nombre']} */ ;
        (fila.nombre_prestacion);
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)({
            ...{ class: "td-area" },
        });
        /** @type {__VLS_StyleScopedClasses['td-area']} */ ;
        (fila.area);
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.input)({
            ...{ onInput: (...[$event]) => {
                    if (!!(__VLS_ctx.filas.length === 0))
                        return;
                    __VLS_ctx.calcularEEMM(fila);
                    // @ts-ignore
                    [irAPrestaciones, filas, filas, calcularEEMM,];
                } },
            type: "number",
            min: "0",
            step: "1",
            ...{ class: ({ 'input-error': __VLS_ctx.errores.has(`${fila.ID_PRESTACION}-demanda`) }) },
        });
        (fila.demanda);
        /** @type {__VLS_StyleScopedClasses['input-error']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.input)({
            ...{ onInput: (...[$event]) => {
                    if (!!(__VLS_ctx.filas.length === 0))
                        return;
                    __VLS_ctx.calcularEEMM(fila);
                    // @ts-ignore
                    [calcularEEMM, errores,];
                } },
            type: "number",
            min: "0",
            step: "0.1",
            ...{ class: ({ 'input-error': __VLS_ctx.errores.has(`${fila.ID_PRESTACION}-tiempo`) }) },
        });
        (fila.tiempo);
        /** @type {__VLS_StyleScopedClasses['input-error']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.input)({
            ...{ onInput: (...[$event]) => {
                    if (!!(__VLS_ctx.filas.length === 0))
                        return;
                    __VLS_ctx.calcularEEMM(fila);
                    // @ts-ignore
                    [calcularEEMM, errores,];
                } },
            type: "number",
            min: "1",
            max: "366",
            step: "1",
            ...{ class: ({ 'input-error': __VLS_ctx.errores.has(`${fila.ID_PRESTACION}-diasLaborales`) }) },
        });
        (fila.diasLaborales);
        /** @type {__VLS_StyleScopedClasses['input-error']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.input)({
            ...{ onInput: (...[$event]) => {
                    if (!!(__VLS_ctx.filas.length === 0))
                        return;
                    __VLS_ctx.calcularEEMM(fila);
                    // @ts-ignore
                    [calcularEEMM, errores,];
                } },
            type: "number",
            min: "1",
            step: "1",
            ...{ class: ({ 'input-error': __VLS_ctx.errores.has(`${fila.ID_PRESTACION}-nSimultaneas`) }) },
        });
        (fila.nSimultaneas);
        /** @type {__VLS_StyleScopedClasses['input-error']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.input)({
            ...{ onInput: (...[$event]) => {
                    if (!!(__VLS_ctx.filas.length === 0))
                        return;
                    __VLS_ctx.calcularEEMM(fila);
                    // @ts-ignore
                    [calcularEEMM, errores,];
                } },
            type: "number",
            min: "0",
            max: "100",
            step: "0.1",
            ...{ class: ({ 'input-error': __VLS_ctx.errores.has(`${fila.ID_PRESTACION}-disponibilidad`) }) },
        });
        (fila.disponibilidad);
        /** @type {__VLS_StyleScopedClasses['input-error']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.select, __VLS_intrinsics.select)({
            ...{ onChange: (...[$event]) => {
                    if (!!(__VLS_ctx.filas.length === 0))
                        return;
                    __VLS_ctx.calcularEEMM(fila);
                    // @ts-ignore
                    [calcularEEMM, errores,];
                } },
            value: (fila.jornada),
        });
        for (const [j] of __VLS_vFor((12))) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.option, __VLS_intrinsics.option)({
                key: (j),
                value: (j),
            });
            (j);
            // @ts-ignore
            [];
        }
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)({
            ...{ class: "td-eemm" },
        });
        /** @type {__VLS_StyleScopedClasses['td-eemm']} */ ;
        (fila.eemm !== null ? fila.eemm.toFixed(3) : '—');
        // @ts-ignore
        [];
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
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
        ...{ class: "fa-solid fa-calculator" },
    });
    /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
    /** @type {__VLS_StyleScopedClasses['fa-calculator']} */ ;
}
// @ts-ignore
[guardarYCalcular,];
var __VLS_3;
// @ts-ignore
[];
const __VLS_export = (await import('vue')).defineComponent({});
export default {};
