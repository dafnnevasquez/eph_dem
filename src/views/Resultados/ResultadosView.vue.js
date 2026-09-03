import { ref, computed, watch } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import AppLayout from '@/layouts/AppLayout.vue';
const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const urpaRaw = ref(null);
const nombreProyecto = ref('Proyecto seleccionado');
const proyectoIdActivo = ref(null);
const cargando = ref(true);
const error = ref(null);
const resumenAbierto = ref(true);
const vistaResumen = ref('lista');
const equipos = ref([]);
const porRecinto = ref({});
const demandaCompartidaRaw = ref([]);
const pabellonesPorRecintoRaw = ref({});
const boxesPorRecintoRaw = ref({});
const filtros = ref({ texto: '', recinto: '' });
const recintoAbierto = ref({});
const subnormaAbierto = ref({});
const normativaAbierta = ref(false);
const prestacionesAbierta = ref(false);
const urpaAbierta = ref(false);
function normalizar(str) {
    return (str || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase();
}
const equiposConsolidados = computed(() => {
    const base = equipos.value || [];
    const urpa = urpaRaw.value?.equipos || [];
    if (urpa.length === 0)
        return base;
    const map = new Map();
    base.forEach(e => map.set(e.equipo_id, { ...e }));
    urpa.forEach(e => {
        if (map.has(e.equipo_id)) {
            map.get(e.equipo_id).cantidad += e.cantidad;
        }
        else
            map.set(e.equipo_id, { equipo_id: e.equipo_id, nombre_equipo: e.nombre_equipo, cantidad: e.cantidad, origenes: { urpa: true } });
    });
    return Array.from(map.values());
});
const totalUnidadesEquipos = computed(() => equiposConsolidados.value.reduce((acc, e) => acc + (e.cantidad || 0), 0));
const pabellonesPorRecinto = computed(() => Object.entries(pabellonesPorRecintoRaw.value).map(([id, info]) => ({ id, nombre: nombreRecinto(id), cantidad: info.pabellones })));
const boxesPorRecinto = computed(() => Object.entries(boxesPorRecintoRaw.value).map(([id, info]) => ({ id, nombre: nombreRecinto(id), cantidad: info.boxes })));
function nombreRecinto(id) {
    const mapa = { 1: 'Cubículo UTI', 2: 'Cubículo UCI', 3: 'Pabellón menor', 4: 'Pabellón mayor' };
    return mapa[id] || ('Recinto ' + id);
}
const resumenEquipos = computed(() => {
    const texto = normalizar(filtros.value.texto.trim());
    return equiposConsolidados.value.filter(e => (e.cantidad || 0) > 0).filter(e => !texto || normalizar(e.nombre_equipo).includes(texto));
});
const recintosAgrupados = computed(() => {
    const texto = normalizar(filtros.value.texto.trim());
    const coincide = item => !texto || normalizar(item.nombre_equipo).includes(texto);
    const origenMap = Object.fromEntries(equipos.value.map(e => [e.equipo_id, e.origenes || {}]));
    const esPropioDel = item => { const orig = origenMap[item.equipo_id] || {}; return (orig.kit ?? 0) > 0; };
    return Object.entries(porRecinto.value)
        .map(([id, info]) => ({
        id,
        nombre: info.nombre_recinto || nombreRecinto(id),
        items: (info.equipos || []).filter(item => coincide(item) && esPropioDel(item)),
        estacionEnfermeria: (info.estacion_enfermeria || []).filter(coincide),
    }))
        .filter(r => r.items.length > 0 || r.estacionEnfermeria.length > 0)
        .filter(r => !filtros.value.recinto || r.nombre === filtros.value.recinto);
});
const demandaCompartida = computed(() => {
    const texto = normalizar(filtros.value.texto.trim());
    return demandaCompartidaRaw.value.filter(item => !texto || normalizar(item.nombre_equipo).includes(texto));
});
const equiposNormativa = computed(() => {
    const texto = normalizar(filtros.value.texto.trim());
    return equipos.value.filter(e => (e.cantidad || 0) > 0).filter(e => { const orig = e.origenes || {}; return ('norma_upc' in orig) || ('tipo2_relacion' in orig); }).filter(e => !texto || normalizar(e.nombre_equipo).includes(texto));
});
const equiposPrestaciones = computed(() => {
    const texto = normalizar(filtros.value.texto.trim());
    return equipos.value.filter(e => (e.cantidad || 0) > 0).filter(e => { const orig = e.origenes || {}; return ('demanda' in orig); }).filter(e => !texto || normalizar(e.nombre_equipo).includes(texto));
});
function toggleRecinto(id) { recintoAbierto.value = { ...recintoAbierto.value, [id]: !recintoAbierto.value[id] }; }
function toggleSubNorma(id) { subnormaAbierto.value = { ...subnormaAbierto.value, [id]: !subnormaAbierto.value[id] }; }
function aplicarDatos(datos) {
    error.value = null;
    proyectoIdActivo.value = datos.proyecto_id ?? null;
    equipos.value = datos.equipamiento?.equipos ?? [];
    porRecinto.value = datos.equipamiento?.por_recinto ?? {};
    demandaCompartidaRaw.value = datos.equipamiento?.demanda_compartida ?? [];
    pabellonesPorRecintoRaw.value = datos.pabellones?.pabellones_por_recinto ?? {};
    boxesPorRecintoRaw.value = datos.boxes?.por_recinto ?? {};
    urpaRaw.value = datos.urpa ?? null;
    if (datos.nombre_proyecto) {
        nombreProyecto.value = datos.nombre_proyecto;
        localStorage.setItem('ephdem_nombre_proyecto_activo', datos.nombre_proyecto);
    }
    else {
        nombreProyecto.value = localStorage.getItem('ephdem_nombre_proyecto_activo') || 'Desconocido';
    }
    if (datos.proyecto_id)
        localStorage.setItem('ephdem_proyecto_activo', datos.proyecto_id);
    cargando.value = false;
}
// Lee el resultado recién calculado desde localStorage. Devuelve true si lo aplicó.
// Si se indica proyectoId, sólo lo usa cuando el resultado guardado corresponde a ese proyecto
// (evita mostrar el cálculo de otro proyecto que haya quedado en localStorage).
function aplicarDesdeLocalStorageSiCorresponde(proyectoId) {
    const raw = localStorage.getItem('ephdem_resultado_calculo');
    if (!raw)
        return false;
    try {
        const parsed = JSON.parse(raw);
        const datos = parsed.datos ? parsed.datos : parsed;
        if (proyectoId && String(datos.proyecto_id) !== String(proyectoId))
            return false;
        aplicarDatos(datos);
        // Ya se consumió: se limpia para que una futura visita a este proyecto use el servidor.
        localStorage.removeItem('ephdem_resultado_calculo');
        return true;
    }
    catch (e) {
        return false;
    }
}
function cargarDesdeLocalStorage() {
    if (aplicarDesdeLocalStorageSiCorresponde(null))
        return;
    error.value = 'No hay resultados disponibles.';
    cargando.value = false;
}
async function cargarDesdeServidor(proyectoId) {
    // El cálculo recién hecho en ParametrosView (guardado en localStorage) es más reciente
    // que lo que tenga persistido el servidor, así que se prioriza si coincide con el proyecto.
    const rawDebug = localStorage.getItem('ephdem_resultado_calculo');
    console.log('[ResultadosView] cargarDesdeServidor - proyectoId de la ruta:', proyectoId, '| localStorage presente:', !!rawDebug, '| proyecto_id en localStorage:', rawDebug ? JSON.parse(rawDebug)?.proyecto_id ?? JSON.parse(rawDebug)?.datos?.proyecto_id : null);
    if (aplicarDesdeLocalStorageSiCorresponde(proyectoId)) {
        console.log('[ResultadosView] rama: localStorage aplicado');
        return;
    }
    console.log('[ResultadosView] rama: fetch al servidor');
    const usuarioId = authStore.usuarioId;
    if (!usuarioId) {
        error.value = 'No hay sesión activa.';
        cargando.value = false;
        return;
    }
    try {
        const url = `${import.meta.env.VITE_API_BASE}/get/obtener_resultados_proyecto.php?proyecto_id=${proyectoId}&usuario_id=${usuarioId}`;
        const resp = await fetch(url, { cache: 'no-store' });
        const json = await resp.json();
        if (!resp.ok || !json.ok) {
            error.value = json.error || 'Error al cargar resultados.';
            cargando.value = false;
        }
        else {
            aplicarDatos(json.datos);
        }
    }
    catch (e) {
        error.value = 'Error de red al intentar cargar resultados.';
        cargando.value = false;
    }
}
watch(() => route.params.proyectoId, (newId) => {
    console.log('[Watch] proyectoId cambió a:', newId);
    cargando.value = true;
    if (newId)
        cargarDesdeServidor(newId);
    else
        cargarDesdeLocalStorage();
}, { immediate: true, flush: 'post' });
function volverAParametros() { router.push(`/parametros/${proyectoIdActivo.value}`); }
function modificarPrestaciones() { router.push(`/prestaciones/${proyectoIdActivo.value}`); }
function volverAtras() { localStorage.removeItem('ephdem_resultado_calculo'); router.back(); setTimeout(() => window.scrollTo({ top: 0, left: 0, behavior: 'auto' }), 0); }
function cerrarSesion() { authStore.logout(); router.push('/login'); }
function exportarExcel() {
    if (!proyectoIdActivo.value) {
        alert('No se pudo identificar el proyecto activo.');
        return;
    }
    const nombre = encodeURIComponent(nombreProyecto.value || 'Proyecto');
    window.open(`https://sigem-uv.cl/__v2/modulo_eph/ajax/generar/generar_xls_cerrada.php?proyecto_id=${proyectoIdActivo.value}&nombre=${nombre}`, '_blank');
}
function exportarPdf() {
    if (!proyectoIdActivo.value) {
        alert('No se pudo identificar el proyecto activo.');
        return;
    }
    const nombre = encodeURIComponent(nombreProyecto.value || 'Proyecto');
    window.open(`https://sigem-uv.cl/__v2/modulo_eph/ajax/generar/generar_pdf_cerrada.php?proyecto_id=${proyectoIdActivo.value}&nombre=${nombre}`, '_blank');
}
function equiposNoImplementado() {
    alert('Equipos de Oportunidad no está implementado en esta versión.');
}
const __VLS_ctx = {
    ...{},
    ...{},
};
let __VLS_components;
let __VLS_intrinsics;
let __VLS_directives;
/** @type {__VLS_StyleScopedClasses['filtro']} */ ;
/** @type {__VLS_StyleScopedClasses['panel-title-toggle']} */ ;
/** @type {__VLS_StyleScopedClasses['resumen-list']} */ ;
/** @type {__VLS_StyleScopedClasses['resumen-row']} */ ;
/** @type {__VLS_StyleScopedClasses['resumen-list']} */ ;
/** @type {__VLS_StyleScopedClasses['resumen-row']} */ ;
/** @type {__VLS_StyleScopedClasses['resumen-row-head']} */ ;
/** @type {__VLS_StyleScopedClasses['resumen-row-head']} */ ;
/** @type {__VLS_StyleScopedClasses['resumen-banner']} */ ;
/** @type {__VLS_StyleScopedClasses['recintos-grid']} */ ;
/** @type {__VLS_StyleScopedClasses['pasos-grid']} */ ;
const __VLS_0 = AppLayout || AppLayout;
// @ts-ignore
const __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0({}));
const __VLS_2 = __VLS_1({}, ...__VLS_functionalComponentArgsRest(__VLS_1));
var __VLS_5 = {};
const { default: __VLS_6 } = __VLS_3.slots;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "resultados-page" },
});
/** @type {__VLS_StyleScopedClasses['resultados-page']} */ ;
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
    ...{ class: "resultados-content" },
});
/** @type {__VLS_StyleScopedClasses['resultados-content']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.header, __VLS_intrinsics.header)({
    ...{ class: "resultados-header" },
});
/** @type {__VLS_StyleScopedClasses['resultados-header']} */ ;
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
if (__VLS_ctx.proyectoIdActivo) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "nav-divider" },
    });
    /** @type {__VLS_StyleScopedClasses['nav-divider']} */ ;
}
if (__VLS_ctx.proyectoIdActivo) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
        ...{ class: "fase-label" },
    });
    /** @type {__VLS_StyleScopedClasses['fase-label']} */ ;
}
if (__VLS_ctx.proyectoIdActivo) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
        ...{ onClick: (__VLS_ctx.volverAParametros) },
        ...{ class: "btn-back" },
        type: "button",
    });
    /** @type {__VLS_StyleScopedClasses['btn-back']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
        ...{ class: "fa-solid fa-sliders" },
    });
    /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
    /** @type {__VLS_StyleScopedClasses['fa-sliders']} */ ;
}
if (__VLS_ctx.proyectoIdActivo) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
        ...{ onClick: (__VLS_ctx.modificarPrestaciones) },
        ...{ class: "btn-back" },
        type: "button",
    });
    /** @type {__VLS_StyleScopedClasses['btn-back']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
        ...{ class: "fa-solid fa-list-check" },
    });
    /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
    /** @type {__VLS_StyleScopedClasses['fa-list-check']} */ ;
}
if (__VLS_ctx.proyectoIdActivo) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "nav-divider" },
    });
    /** @type {__VLS_StyleScopedClasses['nav-divider']} */ ;
}
if (__VLS_ctx.proyectoIdActivo) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
        ...{ class: "fase-label" },
    });
    /** @type {__VLS_StyleScopedClasses['fase-label']} */ ;
}
if (__VLS_ctx.proyectoIdActivo) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
        ...{ onClick: (() => { console.log('proyectoId:', __VLS_ctx.proyectoIdActivo); __VLS_ctx.router.push(`/rrhh/${__VLS_ctx.proyectoIdActivo}`); }) },
        ...{ class: "btn-fase2" },
        type: "button",
    });
    /** @type {__VLS_StyleScopedClasses['btn-fase2']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
        ...{ class: "fa-solid fa-user-nurse" },
    });
    /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
    /** @type {__VLS_StyleScopedClasses['fa-user-nurse']} */ ;
}
if (__VLS_ctx.proyectoIdActivo) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "nav-divider" },
    });
    /** @type {__VLS_StyleScopedClasses['nav-divider']} */ ;
}
if (__VLS_ctx.proyectoIdActivo) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
        ...{ class: "fase-label" },
    });
    /** @type {__VLS_StyleScopedClasses['fase-label']} */ ;
}
if (__VLS_ctx.proyectoIdActivo) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
        ...{ onClick: (__VLS_ctx.equiposNoImplementado) },
        ...{ class: "btn-fase3" },
        type: "button",
    });
    /** @type {__VLS_StyleScopedClasses['btn-fase3']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
        ...{ class: "fa-solid fa-boxes-stacked" },
    });
    /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
    /** @type {__VLS_StyleScopedClasses['fa-boxes-stacked']} */ ;
}
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
    ...{ class: "title-actions-row" },
});
/** @type {__VLS_StyleScopedClasses['title-actions-row']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)({
    ...{ class: "section-title" },
});
/** @type {__VLS_StyleScopedClasses['section-title']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({
    ...{ class: "section-subtitle" },
});
/** @type {__VLS_StyleScopedClasses['section-subtitle']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "header-actions" },
});
/** @type {__VLS_StyleScopedClasses['header-actions']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
    ...{ onClick: (__VLS_ctx.exportarExcel) },
    ...{ class: "btn-export btn-export-excel" },
    type: "button",
});
/** @type {__VLS_StyleScopedClasses['btn-export']} */ ;
/** @type {__VLS_StyleScopedClasses['btn-export-excel']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
    ...{ class: "fa-solid fa-file-excel" },
});
/** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-file-excel']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
    ...{ onClick: (__VLS_ctx.exportarPdf) },
    ...{ class: "btn-export btn-export-pdf" },
    type: "button",
});
/** @type {__VLS_StyleScopedClasses['btn-export']} */ ;
/** @type {__VLS_StyleScopedClasses['btn-export-pdf']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
    ...{ class: "fa-solid fa-file-pdf" },
});
/** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-file-pdf']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)({
    ...{ class: "resumen-banner" },
});
/** @type {__VLS_StyleScopedClasses['resumen-banner']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "banner-left" },
});
/** @type {__VLS_StyleScopedClasses['banner-left']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({
    ...{ class: "banner-sub" },
});
/** @type {__VLS_StyleScopedClasses['banner-sub']} */ ;
(__VLS_ctx.nombreProyecto);
if (__VLS_ctx.cargando) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "banner-total" },
    });
    /** @type {__VLS_StyleScopedClasses['banner-total']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
        ...{ class: "metric-label" },
    });
    /** @type {__VLS_StyleScopedClasses['metric-label']} */ ;
}
else if (__VLS_ctx.error) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "banner-total" },
    });
    /** @type {__VLS_StyleScopedClasses['banner-total']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
        ...{ class: "metric-label" },
        ...{ style: {} },
    });
    /** @type {__VLS_StyleScopedClasses['metric-label']} */ ;
    (__VLS_ctx.error);
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "banner-total" },
    });
    /** @type {__VLS_StyleScopedClasses['banner-total']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
        ...{ class: "metric-value" },
    });
    /** @type {__VLS_StyleScopedClasses['metric-value']} */ ;
    (__VLS_ctx.totalUnidadesEquipos);
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
        ...{ class: "metric-label" },
    });
    /** @type {__VLS_StyleScopedClasses['metric-label']} */ ;
}
if (!__VLS_ctx.cargando && !__VLS_ctx.error && (__VLS_ctx.pabellonesPorRecinto.length || __VLS_ctx.boxesPorRecinto.length)) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)({
        ...{ class: "recintos-conteo-panel" },
    });
    /** @type {__VLS_StyleScopedClasses['recintos-conteo-panel']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "panel-title" },
    });
    /** @type {__VLS_StyleScopedClasses['panel-title']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "conteo-chips" },
    });
    /** @type {__VLS_StyleScopedClasses['conteo-chips']} */ ;
    for (const [rec] of __VLS_vFor((__VLS_ctx.pabellonesPorRecinto))) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            key: ('pab-' + rec.id),
            ...{ class: "conteo-chip" },
        });
        /** @type {__VLS_StyleScopedClasses['conteo-chip']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
            ...{ class: "chip-nombre" },
        });
        /** @type {__VLS_StyleScopedClasses['chip-nombre']} */ ;
        (rec.nombre);
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
            ...{ class: "chip-valor" },
        });
        /** @type {__VLS_StyleScopedClasses['chip-valor']} */ ;
        (rec.cantidad);
        // @ts-ignore
        [router, proyectoIdActivo, proyectoIdActivo, proyectoIdActivo, proyectoIdActivo, proyectoIdActivo, proyectoIdActivo, proyectoIdActivo, proyectoIdActivo, proyectoIdActivo, proyectoIdActivo, proyectoIdActivo, proyectoIdActivo, volverAParametros, modificarPrestaciones, equiposNoImplementado, authStore, cerrarSesion, exportarExcel, exportarPdf, nombreProyecto, cargando, cargando, error, error, error, totalUnidadesEquipos, pabellonesPorRecinto, pabellonesPorRecinto, boxesPorRecinto,];
    }
    for (const [rec] of __VLS_vFor((__VLS_ctx.boxesPorRecinto))) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            key: ('box-' + rec.id),
            ...{ class: "conteo-chip" },
        });
        /** @type {__VLS_StyleScopedClasses['conteo-chip']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
            ...{ class: "chip-nombre" },
        });
        /** @type {__VLS_StyleScopedClasses['chip-nombre']} */ ;
        (rec.nombre);
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
            ...{ class: "chip-valor" },
        });
        /** @type {__VLS_StyleScopedClasses['chip-valor']} */ ;
        (rec.cantidad);
        // @ts-ignore
        [boxesPorRecinto,];
    }
}
if (!__VLS_ctx.cargando && !__VLS_ctx.error) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)({
        ...{ class: "filtros-panel" },
    });
    /** @type {__VLS_StyleScopedClasses['filtros-panel']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "filtro filtro-buscar" },
    });
    /** @type {__VLS_StyleScopedClasses['filtro']} */ ;
    /** @type {__VLS_StyleScopedClasses['filtro-buscar']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.input)({
        value: (__VLS_ctx.filtros.texto),
        type: "text",
        placeholder: "Nombre de equipo",
    });
}
if (!__VLS_ctx.cargando && !__VLS_ctx.error) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)({
        ...{ class: "resumen-panel" },
        ...{ class: ({ 'resumen-panel-cerrado': !__VLS_ctx.resumenAbierto }) },
    });
    /** @type {__VLS_StyleScopedClasses['resumen-panel']} */ ;
    /** @type {__VLS_StyleScopedClasses['resumen-panel-cerrado']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ onClick: (...[$event]) => {
                if (!(!__VLS_ctx.cargando && !__VLS_ctx.error))
                    return;
                __VLS_ctx.resumenAbierto = !__VLS_ctx.resumenAbierto;
                // @ts-ignore
                [cargando, cargando, error, error, filtros, resumenAbierto, resumenAbierto, resumenAbierto,];
            } },
        ...{ class: "panel-title panel-title-toggle" },
    });
    /** @type {__VLS_StyleScopedClasses['panel-title']} */ ;
    /** @type {__VLS_StyleScopedClasses['panel-title-toggle']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ onClick: () => { } },
        ...{ class: "vista-toggle-group" },
    });
    /** @type {__VLS_StyleScopedClasses['vista-toggle-group']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
        ...{ onClick: (...[$event]) => {
                if (!(!__VLS_ctx.cargando && !__VLS_ctx.error))
                    return;
                __VLS_ctx.vistaResumen = 'lista';
                // @ts-ignore
                [vistaResumen,];
            } },
        ...{ class: "vista-btn" },
        ...{ class: ({ 'vista-btn-active': __VLS_ctx.vistaResumen === 'lista' }) },
    });
    /** @type {__VLS_StyleScopedClasses['vista-btn']} */ ;
    /** @type {__VLS_StyleScopedClasses['vista-btn-active']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.svg, __VLS_intrinsics.svg)({
        width: "18",
        height: "18",
        viewBox: "0 0 18 18",
        fill: "none",
    });
    __VLS_asFunctionalElement1(__VLS_intrinsics.rect)({
        x: "1",
        y: "3",
        width: "16",
        height: "2.5",
        rx: "1.2",
        fill: "currentColor",
    });
    __VLS_asFunctionalElement1(__VLS_intrinsics.rect)({
        x: "1",
        y: "7.75",
        width: "16",
        height: "2.5",
        rx: "1.2",
        fill: "currentColor",
    });
    __VLS_asFunctionalElement1(__VLS_intrinsics.rect)({
        x: "1",
        y: "12.5",
        width: "16",
        height: "2.5",
        rx: "1.2",
        fill: "currentColor",
    });
    __VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
        ...{ onClick: (...[$event]) => {
                if (!(!__VLS_ctx.cargando && !__VLS_ctx.error))
                    return;
                __VLS_ctx.vistaResumen = 'mosaico2';
                // @ts-ignore
                [vistaResumen, vistaResumen,];
            } },
        ...{ class: "vista-btn" },
        ...{ class: ({ 'vista-btn-active': __VLS_ctx.vistaResumen === 'mosaico2' }) },
    });
    /** @type {__VLS_StyleScopedClasses['vista-btn']} */ ;
    /** @type {__VLS_StyleScopedClasses['vista-btn-active']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.svg, __VLS_intrinsics.svg)({
        width: "18",
        height: "18",
        viewBox: "0 0 18 18",
        fill: "none",
    });
    __VLS_asFunctionalElement1(__VLS_intrinsics.rect)({
        x: "1",
        y: "1",
        width: "7",
        height: "7",
        rx: "1.5",
        fill: "currentColor",
    });
    __VLS_asFunctionalElement1(__VLS_intrinsics.rect)({
        x: "10",
        y: "1",
        width: "7",
        height: "7",
        rx: "1.5",
        fill: "currentColor",
    });
    __VLS_asFunctionalElement1(__VLS_intrinsics.rect)({
        x: "1",
        y: "10",
        width: "7",
        height: "7",
        rx: "1.5",
        fill: "currentColor",
    });
    __VLS_asFunctionalElement1(__VLS_intrinsics.rect)({
        x: "10",
        y: "10",
        width: "7",
        height: "7",
        rx: "1.5",
        fill: "currentColor",
    });
    __VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
        ...{ onClick: (...[$event]) => {
                if (!(!__VLS_ctx.cargando && !__VLS_ctx.error))
                    return;
                __VLS_ctx.vistaResumen = 'mosaico3';
                // @ts-ignore
                [vistaResumen, vistaResumen,];
            } },
        ...{ class: "vista-btn" },
        ...{ class: ({ 'vista-btn-active': __VLS_ctx.vistaResumen === 'mosaico3' }) },
    });
    /** @type {__VLS_StyleScopedClasses['vista-btn']} */ ;
    /** @type {__VLS_StyleScopedClasses['vista-btn-active']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.svg, __VLS_intrinsics.svg)({
        width: "18",
        height: "18",
        viewBox: "0 0 18 18",
        fill: "none",
    });
    __VLS_asFunctionalElement1(__VLS_intrinsics.rect)({
        x: "1",
        y: "1",
        width: "4.5",
        height: "7",
        rx: "1.2",
        fill: "currentColor",
    });
    __VLS_asFunctionalElement1(__VLS_intrinsics.rect)({
        x: "6.75",
        y: "1",
        width: "4.5",
        height: "7",
        rx: "1.2",
        fill: "currentColor",
    });
    __VLS_asFunctionalElement1(__VLS_intrinsics.rect)({
        x: "12.5",
        y: "1",
        width: "4.5",
        height: "7",
        rx: "1.2",
        fill: "currentColor",
    });
    __VLS_asFunctionalElement1(__VLS_intrinsics.rect)({
        x: "1",
        y: "10",
        width: "4.5",
        height: "7",
        rx: "1.2",
        fill: "currentColor",
    });
    __VLS_asFunctionalElement1(__VLS_intrinsics.rect)({
        x: "6.75",
        y: "10",
        width: "4.5",
        height: "7",
        rx: "1.2",
        fill: "currentColor",
    });
    __VLS_asFunctionalElement1(__VLS_intrinsics.rect)({
        x: "12.5",
        y: "10",
        width: "4.5",
        height: "7",
        rx: "1.2",
        fill: "currentColor",
    });
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
        ...{ onClick: (...[$event]) => {
                if (!(!__VLS_ctx.cargando && !__VLS_ctx.error))
                    return;
                __VLS_ctx.resumenAbierto = !__VLS_ctx.resumenAbierto;
                // @ts-ignore
                [resumenAbierto, resumenAbierto, vistaResumen,];
            } },
        ...{ class: "fa-solid vista-chevron" },
        ...{ class: (__VLS_ctx.resumenAbierto ? 'fa-chevron-up' : 'fa-chevron-down') },
    });
    /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
    /** @type {__VLS_StyleScopedClasses['vista-chevron']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "resumen-list" },
    });
    __VLS_asFunctionalDirective(__VLS_directives.vShow, {})(null, { ...__VLS_directiveBindingRestFields, value: (__VLS_ctx.resumenAbierto && __VLS_ctx.vistaResumen === 'lista') }, null, null);
    /** @type {__VLS_StyleScopedClasses['resumen-list']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "resumen-row resumen-row-head" },
    });
    /** @type {__VLS_StyleScopedClasses['resumen-row']} */ ;
    /** @type {__VLS_StyleScopedClasses['resumen-row-head']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "row-total" },
    });
    /** @type {__VLS_StyleScopedClasses['row-total']} */ ;
    if (__VLS_ctx.resumenEquipos.length === 0) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "lista-vacia" },
            ...{ style: {} },
        });
        /** @type {__VLS_StyleScopedClasses['lista-vacia']} */ ;
    }
    for (const [equipo] of __VLS_vFor((__VLS_ctx.resumenEquipos))) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            key: (equipo.equipo_id),
            ...{ class: "resumen-row" },
        });
        /** @type {__VLS_StyleScopedClasses['resumen-row']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "row-main" },
        });
        /** @type {__VLS_StyleScopedClasses['row-main']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "equipo-nombre" },
        });
        /** @type {__VLS_StyleScopedClasses['equipo-nombre']} */ ;
        (equipo.nombre_equipo);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "row-total" },
        });
        /** @type {__VLS_StyleScopedClasses['row-total']} */ ;
        (equipo.cantidad);
        // @ts-ignore
        [resumenAbierto, resumenAbierto, vistaResumen, resumenEquipos, resumenEquipos,];
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "resumen-mosaico" },
        ...{ class: (__VLS_ctx.vistaResumen === 'mosaico3' ? 'resumen-mosaico-3' : 'resumen-mosaico-2') },
    });
    __VLS_asFunctionalDirective(__VLS_directives.vShow, {})(null, { ...__VLS_directiveBindingRestFields, value: (__VLS_ctx.resumenAbierto && (__VLS_ctx.vistaResumen === 'mosaico2' || __VLS_ctx.vistaResumen === 'mosaico3')) }, null, null);
    /** @type {__VLS_StyleScopedClasses['resumen-mosaico']} */ ;
    if (__VLS_ctx.resumenEquipos.length === 0) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "lista-vacia" },
            ...{ style: {} },
        });
        /** @type {__VLS_StyleScopedClasses['lista-vacia']} */ ;
    }
    for (const [equipo] of __VLS_vFor((__VLS_ctx.resumenEquipos))) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            key: (equipo.equipo_id),
            ...{ class: "mosaic-card" },
        });
        /** @type {__VLS_StyleScopedClasses['mosaic-card']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "mosaic-header" },
        });
        /** @type {__VLS_StyleScopedClasses['mosaic-header']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "mosaic-nombre" },
        });
        /** @type {__VLS_StyleScopedClasses['mosaic-nombre']} */ ;
        (equipo.nombre_equipo);
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
            ...{ class: "mosaic-total" },
        });
        /** @type {__VLS_StyleScopedClasses['mosaic-total']} */ ;
        (equipo.cantidad);
        // @ts-ignore
        [resumenAbierto, vistaResumen, vistaResumen, vistaResumen, resumenEquipos, resumenEquipos,];
    }
}
if (!__VLS_ctx.cargando && !__VLS_ctx.error) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)({
        ...{ class: "desglose-panel" },
    });
    /** @type {__VLS_StyleScopedClasses['desglose-panel']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "panel-title" },
    });
    /** @type {__VLS_StyleScopedClasses['panel-title']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "desglose-section" },
    });
    /** @type {__VLS_StyleScopedClasses['desglose-section']} */ ;
    if (__VLS_ctx.recintosAgrupados.length === 0) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "lista-vacia" },
        });
        /** @type {__VLS_StyleScopedClasses['lista-vacia']} */ ;
    }
    else {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "recintos-grid" },
        });
        /** @type {__VLS_StyleScopedClasses['recintos-grid']} */ ;
        for (const [recinto] of __VLS_vFor((__VLS_ctx.recintosAgrupados))) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                key: (recinto.id),
                ...{ class: "recinto-card" },
            });
            /** @type {__VLS_StyleScopedClasses['recinto-card']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                ...{ onClick: (...[$event]) => {
                        if (!(!__VLS_ctx.cargando && !__VLS_ctx.error))
                            return;
                        if (!!(__VLS_ctx.recintosAgrupados.length === 0))
                            return;
                        __VLS_ctx.toggleRecinto(recinto.id);
                        // @ts-ignore
                        [cargando, error, recintosAgrupados, recintosAgrupados, toggleRecinto,];
                    } },
                ...{ class: "recinto-title recinto-title-toggle" },
            });
            /** @type {__VLS_StyleScopedClasses['recinto-title']} */ ;
            /** @type {__VLS_StyleScopedClasses['recinto-title-toggle']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
            (recinto.nombre);
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
                ...{ class: "recinto-count" },
            });
            /** @type {__VLS_StyleScopedClasses['recinto-count']} */ ;
            (recinto.items.length);
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
                ...{ class: "fa-solid" },
                ...{ class: (__VLS_ctx.recintoAbierto[recinto.id] ? 'fa-chevron-up' : 'fa-chevron-down') },
            });
            /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                ...{ class: "recinto-body" },
            });
            __VLS_asFunctionalDirective(__VLS_directives.vShow, {})(null, { ...__VLS_directiveBindingRestFields, value: (__VLS_ctx.recintoAbierto[recinto.id]) }, null, null);
            /** @type {__VLS_StyleScopedClasses['recinto-body']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                ...{ class: "tabla-mini" },
            });
            /** @type {__VLS_StyleScopedClasses['tabla-mini']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                ...{ class: "tabla-mini-head" },
            });
            /** @type {__VLS_StyleScopedClasses['tabla-mini-head']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
                ...{ class: "tabla-mini-cantidad" },
            });
            /** @type {__VLS_StyleScopedClasses['tabla-mini-cantidad']} */ ;
            for (const [item] of __VLS_vFor((recinto.items))) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                    key: (item.equipo_id),
                    ...{ class: "tabla-mini-row" },
                });
                /** @type {__VLS_StyleScopedClasses['tabla-mini-row']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
                (item.nombre_equipo);
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
                    ...{ class: "tabla-mini-cantidad" },
                });
                /** @type {__VLS_StyleScopedClasses['tabla-mini-cantidad']} */ ;
                (item.cantidad);
                // @ts-ignore
                [recintoAbierto, recintoAbierto,];
            }
            if (recinto.items.length === 0) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                    ...{ class: "tabla-mini-vacia" },
                });
                /** @type {__VLS_StyleScopedClasses['tabla-mini-vacia']} */ ;
            }
            if (recinto.estacionEnfermeria.length > 0) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                    ...{ class: "subrecinto-section" },
                });
                /** @type {__VLS_StyleScopedClasses['subrecinto-section']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                    ...{ onClick: (...[$event]) => {
                            if (!(!__VLS_ctx.cargando && !__VLS_ctx.error))
                                return;
                            if (!!(__VLS_ctx.recintosAgrupados.length === 0))
                                return;
                            if (!(recinto.estacionEnfermeria.length > 0))
                                return;
                            __VLS_ctx.toggleSubNorma(recinto.id);
                            // @ts-ignore
                            [toggleSubNorma,];
                        } },
                    ...{ class: "subrecinto-title" },
                });
                /** @type {__VLS_StyleScopedClasses['subrecinto-title']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
                    ...{ class: "fa-solid fa-kit-medical subrecinto-icon" },
                });
                /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
                /** @type {__VLS_StyleScopedClasses['fa-kit-medical']} */ ;
                /** @type {__VLS_StyleScopedClasses['subrecinto-icon']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
                    ...{ class: "subrecinto-badge" },
                });
                /** @type {__VLS_StyleScopedClasses['subrecinto-badge']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
                    ...{ class: "subrecinto-count" },
                });
                /** @type {__VLS_StyleScopedClasses['subrecinto-count']} */ ;
                (recinto.estacionEnfermeria.length);
                __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
                    ...{ class: "fa-solid subrecinto-chevron" },
                    ...{ class: (__VLS_ctx.subnormaAbierto[recinto.id] ? 'fa-chevron-up' : 'fa-chevron-down') },
                });
                /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
                /** @type {__VLS_StyleScopedClasses['subrecinto-chevron']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                    ...{ class: "subrecinto-body" },
                });
                __VLS_asFunctionalDirective(__VLS_directives.vShow, {})(null, { ...__VLS_directiveBindingRestFields, value: (__VLS_ctx.subnormaAbierto[recinto.id]) }, null, null);
                /** @type {__VLS_StyleScopedClasses['subrecinto-body']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                    ...{ class: "tabla-mini tabla-mini-upc" },
                });
                /** @type {__VLS_StyleScopedClasses['tabla-mini']} */ ;
                /** @type {__VLS_StyleScopedClasses['tabla-mini-upc']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                    ...{ class: "tabla-mini-head tabla-mini-head-upc" },
                });
                /** @type {__VLS_StyleScopedClasses['tabla-mini-head']} */ ;
                /** @type {__VLS_StyleScopedClasses['tabla-mini-head-upc']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
                    ...{ class: "tabla-mini-cantidad" },
                });
                /** @type {__VLS_StyleScopedClasses['tabla-mini-cantidad']} */ ;
                for (const [item] of __VLS_vFor((recinto.estacionEnfermeria))) {
                    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                        key: ('ee-' + item.equipo_id),
                        ...{ class: "tabla-mini-row tabla-mini-row-upc" },
                    });
                    /** @type {__VLS_StyleScopedClasses['tabla-mini-row']} */ ;
                    /** @type {__VLS_StyleScopedClasses['tabla-mini-row-upc']} */ ;
                    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
                    (item.nombre_equipo);
                    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
                        ...{ class: "tabla-mini-cantidad" },
                    });
                    /** @type {__VLS_StyleScopedClasses['tabla-mini-cantidad']} */ ;
                    (item.cantidad);
                    // @ts-ignore
                    [subnormaAbierto, subnormaAbierto,];
                }
            }
            // @ts-ignore
            [];
        }
    }
    if (__VLS_ctx.demandaCompartida.length > 0) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "desglose-section desglose-section-especifico" },
        });
        /** @type {__VLS_StyleScopedClasses['desglose-section']} */ ;
        /** @type {__VLS_StyleScopedClasses['desglose-section-especifico']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "desglose-title" },
        });
        /** @type {__VLS_StyleScopedClasses['desglose-title']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "recinto-table especifico-table" },
        });
        /** @type {__VLS_StyleScopedClasses['recinto-table']} */ ;
        /** @type {__VLS_StyleScopedClasses['especifico-table']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "recinto-title" },
        });
        /** @type {__VLS_StyleScopedClasses['recinto-title']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "tabla-mini" },
        });
        /** @type {__VLS_StyleScopedClasses['tabla-mini']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "tabla-mini-head tabla-mini-head-compartida" },
        });
        /** @type {__VLS_StyleScopedClasses['tabla-mini-head']} */ ;
        /** @type {__VLS_StyleScopedClasses['tabla-mini-head-compartida']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
            ...{ class: "tabla-mini-cantidad" },
        });
        /** @type {__VLS_StyleScopedClasses['tabla-mini-cantidad']} */ ;
        for (const [item] of __VLS_vFor((__VLS_ctx.demandaCompartida))) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                key: (item.equipo_id),
                ...{ class: "tabla-mini-row tabla-mini-row-compartida" },
            });
            /** @type {__VLS_StyleScopedClasses['tabla-mini-row']} */ ;
            /** @type {__VLS_StyleScopedClasses['tabla-mini-row-compartida']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
            (item.nombre_equipo);
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
                ...{ class: "compartida-recintos" },
            });
            /** @type {__VLS_StyleScopedClasses['compartida-recintos']} */ ;
            (item.recintos_involucrados.map(r => r.nombre_recinto).join(', '));
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
                ...{ class: "tabla-mini-cantidad" },
            });
            /** @type {__VLS_StyleScopedClasses['tabla-mini-cantidad']} */ ;
            (item.cantidad);
            // @ts-ignore
            [demandaCompartida, demandaCompartida,];
        }
        __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({
            ...{ class: "nota-recinto" },
        });
        /** @type {__VLS_StyleScopedClasses['nota-recinto']} */ ;
    }
    if (__VLS_ctx.equiposNormativa.length > 0 || __VLS_ctx.equiposPrestaciones.length > 0 || (__VLS_ctx.urpaRaw && __VLS_ctx.urpaRaw.nro_salas > 0)) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "recintos-grid recintos-grid-extra" },
        });
        /** @type {__VLS_StyleScopedClasses['recintos-grid']} */ ;
        /** @type {__VLS_StyleScopedClasses['recintos-grid-extra']} */ ;
        if (__VLS_ctx.equiposNormativa.length > 0) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                ...{ class: "recinto-card" },
            });
            /** @type {__VLS_StyleScopedClasses['recinto-card']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                ...{ onClick: (...[$event]) => {
                        if (!(!__VLS_ctx.cargando && !__VLS_ctx.error))
                            return;
                        if (!(__VLS_ctx.equiposNormativa.length > 0 || __VLS_ctx.equiposPrestaciones.length > 0 || (__VLS_ctx.urpaRaw && __VLS_ctx.urpaRaw.nro_salas > 0)))
                            return;
                        if (!(__VLS_ctx.equiposNormativa.length > 0))
                            return;
                        __VLS_ctx.normativaAbierta = !__VLS_ctx.normativaAbierta;
                        // @ts-ignore
                        [equiposNormativa, equiposNormativa, equiposPrestaciones, urpaRaw, urpaRaw, normativaAbierta, normativaAbierta,];
                    } },
                ...{ class: "recinto-title recinto-title-toggle recinto-title-normativa" },
            });
            /** @type {__VLS_StyleScopedClasses['recinto-title']} */ ;
            /** @type {__VLS_StyleScopedClasses['recinto-title-toggle']} */ ;
            /** @type {__VLS_StyleScopedClasses['recinto-title-normativa']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
                ...{ class: "fa-solid fa-file-medical" },
                ...{ style: {} },
            });
            /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
            /** @type {__VLS_StyleScopedClasses['fa-file-medical']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
                ...{ class: "recinto-count" },
            });
            /** @type {__VLS_StyleScopedClasses['recinto-count']} */ ;
            (__VLS_ctx.equiposNormativa.length);
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
                ...{ class: "fa-solid" },
                ...{ class: (__VLS_ctx.normativaAbierta ? 'fa-chevron-up' : 'fa-chevron-down') },
            });
            /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                ...{ class: "recinto-body" },
            });
            __VLS_asFunctionalDirective(__VLS_directives.vShow, {})(null, { ...__VLS_directiveBindingRestFields, value: (__VLS_ctx.normativaAbierta) }, null, null);
            /** @type {__VLS_StyleScopedClasses['recinto-body']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                ...{ class: "tabla-mini" },
            });
            /** @type {__VLS_StyleScopedClasses['tabla-mini']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                ...{ class: "tabla-mini-head" },
            });
            /** @type {__VLS_StyleScopedClasses['tabla-mini-head']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
                ...{ class: "tabla-mini-cantidad" },
            });
            /** @type {__VLS_StyleScopedClasses['tabla-mini-cantidad']} */ ;
            for (const [equipo] of __VLS_vFor((__VLS_ctx.equiposNormativa))) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                    key: (equipo.equipo_id),
                    ...{ class: "tabla-mini-row" },
                });
                /** @type {__VLS_StyleScopedClasses['tabla-mini-row']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
                (equipo.nombre_equipo);
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
                    ...{ class: "tabla-mini-cantidad" },
                });
                /** @type {__VLS_StyleScopedClasses['tabla-mini-cantidad']} */ ;
                (equipo.cantidad);
                // @ts-ignore
                [equiposNormativa, equiposNormativa, normativaAbierta, normativaAbierta,];
            }
        }
        if (__VLS_ctx.equiposPrestaciones.length > 0) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                ...{ class: "recinto-card" },
            });
            /** @type {__VLS_StyleScopedClasses['recinto-card']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                ...{ onClick: (...[$event]) => {
                        if (!(!__VLS_ctx.cargando && !__VLS_ctx.error))
                            return;
                        if (!(__VLS_ctx.equiposNormativa.length > 0 || __VLS_ctx.equiposPrestaciones.length > 0 || (__VLS_ctx.urpaRaw && __VLS_ctx.urpaRaw.nro_salas > 0)))
                            return;
                        if (!(__VLS_ctx.equiposPrestaciones.length > 0))
                            return;
                        __VLS_ctx.prestacionesAbierta = !__VLS_ctx.prestacionesAbierta;
                        // @ts-ignore
                        [equiposPrestaciones, prestacionesAbierta, prestacionesAbierta,];
                    } },
                ...{ class: "recinto-title recinto-title-toggle recinto-title-prestaciones" },
            });
            /** @type {__VLS_StyleScopedClasses['recinto-title']} */ ;
            /** @type {__VLS_StyleScopedClasses['recinto-title-toggle']} */ ;
            /** @type {__VLS_StyleScopedClasses['recinto-title-prestaciones']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
                ...{ class: "fa-solid fa-stethoscope" },
                ...{ style: {} },
            });
            /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
            /** @type {__VLS_StyleScopedClasses['fa-stethoscope']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
                ...{ class: "recinto-count" },
            });
            /** @type {__VLS_StyleScopedClasses['recinto-count']} */ ;
            (__VLS_ctx.equiposPrestaciones.length);
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
                ...{ class: "fa-solid" },
                ...{ class: (__VLS_ctx.prestacionesAbierta ? 'fa-chevron-up' : 'fa-chevron-down') },
            });
            /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                ...{ class: "recinto-body" },
            });
            __VLS_asFunctionalDirective(__VLS_directives.vShow, {})(null, { ...__VLS_directiveBindingRestFields, value: (__VLS_ctx.prestacionesAbierta) }, null, null);
            /** @type {__VLS_StyleScopedClasses['recinto-body']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                ...{ class: "tabla-mini" },
            });
            /** @type {__VLS_StyleScopedClasses['tabla-mini']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                ...{ class: "tabla-mini-head" },
            });
            /** @type {__VLS_StyleScopedClasses['tabla-mini-head']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
                ...{ class: "tabla-mini-cantidad" },
            });
            /** @type {__VLS_StyleScopedClasses['tabla-mini-cantidad']} */ ;
            for (const [equipo] of __VLS_vFor((__VLS_ctx.equiposPrestaciones))) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                    key: (equipo.equipo_id),
                    ...{ class: "tabla-mini-row" },
                });
                /** @type {__VLS_StyleScopedClasses['tabla-mini-row']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
                (equipo.nombre_equipo);
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
                    ...{ class: "tabla-mini-cantidad" },
                });
                /** @type {__VLS_StyleScopedClasses['tabla-mini-cantidad']} */ ;
                (equipo.cantidad);
                // @ts-ignore
                [equiposPrestaciones, equiposPrestaciones, prestacionesAbierta, prestacionesAbierta,];
            }
        }
        if (__VLS_ctx.urpaRaw && __VLS_ctx.urpaRaw.nro_salas > 0) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                ...{ class: "recinto-card" },
            });
            /** @type {__VLS_StyleScopedClasses['recinto-card']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                ...{ onClick: (...[$event]) => {
                        if (!(!__VLS_ctx.cargando && !__VLS_ctx.error))
                            return;
                        if (!(__VLS_ctx.equiposNormativa.length > 0 || __VLS_ctx.equiposPrestaciones.length > 0 || (__VLS_ctx.urpaRaw && __VLS_ctx.urpaRaw.nro_salas > 0)))
                            return;
                        if (!(__VLS_ctx.urpaRaw && __VLS_ctx.urpaRaw.nro_salas > 0))
                            return;
                        __VLS_ctx.urpaAbierta = !__VLS_ctx.urpaAbierta;
                        // @ts-ignore
                        [urpaRaw, urpaRaw, urpaAbierta, urpaAbierta,];
                    } },
                ...{ class: "recinto-title recinto-title-toggle recinto-title-urpa" },
            });
            /** @type {__VLS_StyleScopedClasses['recinto-title']} */ ;
            /** @type {__VLS_StyleScopedClasses['recinto-title-toggle']} */ ;
            /** @type {__VLS_StyleScopedClasses['recinto-title-urpa']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
                ...{ class: "fa-solid fa-bed-pulse" },
                ...{ style: {} },
            });
            /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
            /** @type {__VLS_StyleScopedClasses['fa-bed-pulse']} */ ;
            (__VLS_ctx.urpaRaw.nombre_recinto);
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
                ...{ class: "recinto-count" },
            });
            /** @type {__VLS_StyleScopedClasses['recinto-count']} */ ;
            (__VLS_ctx.urpaRaw.equipos.length);
            __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
                ...{ class: "fa-solid" },
                ...{ class: (__VLS_ctx.urpaAbierta ? 'fa-chevron-up' : 'fa-chevron-down') },
            });
            /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                ...{ class: "recinto-body" },
            });
            __VLS_asFunctionalDirective(__VLS_directives.vShow, {})(null, { ...__VLS_directiveBindingRestFields, value: (__VLS_ctx.urpaAbierta) }, null, null);
            /** @type {__VLS_StyleScopedClasses['recinto-body']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                ...{ style: {} },
            });
            __VLS_asFunctionalElement1(__VLS_intrinsics.strong, __VLS_intrinsics.strong)({});
            (__VLS_ctx.urpaRaw.nro_camillas);
            (__VLS_ctx.urpaRaw.nro_salas);
            (__VLS_ctx.urpaRaw.nro_pabellones);
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                ...{ class: "tabla-mini" },
            });
            /** @type {__VLS_StyleScopedClasses['tabla-mini']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                ...{ class: "tabla-mini-head" },
            });
            /** @type {__VLS_StyleScopedClasses['tabla-mini-head']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
                ...{ class: "tabla-mini-cantidad" },
            });
            /** @type {__VLS_StyleScopedClasses['tabla-mini-cantidad']} */ ;
            for (const [item] of __VLS_vFor((__VLS_ctx.urpaRaw.equipos))) {
                __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                    key: (item.equipo_id),
                    ...{ class: "tabla-mini-row" },
                });
                /** @type {__VLS_StyleScopedClasses['tabla-mini-row']} */ ;
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
                (item.nombre_equipo);
                __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
                    ...{ class: "tabla-mini-cantidad" },
                });
                /** @type {__VLS_StyleScopedClasses['tabla-mini-cantidad']} */ ;
                (item.cantidad);
                // @ts-ignore
                [urpaRaw, urpaRaw, urpaRaw, urpaRaw, urpaRaw, urpaRaw, urpaAbierta, urpaAbierta,];
            }
        }
    }
}
if (!__VLS_ctx.cargando && !__VLS_ctx.error && __VLS_ctx.proyectoIdActivo) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)({
        ...{ class: "siguientes-pasos" },
    });
    /** @type {__VLS_StyleScopedClasses['siguientes-pasos']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "pasos-titulo" },
    });
    /** @type {__VLS_StyleScopedClasses['pasos-titulo']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
        ...{ class: "fa-solid fa-circle-check" },
        ...{ style: {} },
    });
    /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
    /** @type {__VLS_StyleScopedClasses['fa-circle-check']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "pasos-grid" },
    });
    /** @type {__VLS_StyleScopedClasses['pasos-grid']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ onClick: (...[$event]) => {
                if (!(!__VLS_ctx.cargando && !__VLS_ctx.error && __VLS_ctx.proyectoIdActivo))
                    return;
                __VLS_ctx.router.push(`/rrhh/${__VLS_ctx.proyectoIdActivo}`);
                // @ts-ignore
                [router, proyectoIdActivo, proyectoIdActivo, cargando, error,];
            } },
        ...{ class: "paso-card" },
    });
    /** @type {__VLS_StyleScopedClasses['paso-card']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "paso-num" },
    });
    /** @type {__VLS_StyleScopedClasses['paso-num']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "paso-icono" },
    });
    /** @type {__VLS_StyleScopedClasses['paso-icono']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
        ...{ class: "fa-solid fa-user-nurse" },
    });
    /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
    /** @type {__VLS_StyleScopedClasses['fa-user-nurse']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "paso-info" },
    });
    /** @type {__VLS_StyleScopedClasses['paso-info']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "paso-nombre" },
    });
    /** @type {__VLS_StyleScopedClasses['paso-nombre']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "paso-desc" },
    });
    /** @type {__VLS_StyleScopedClasses['paso-desc']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
        ...{ class: "fa-solid fa-arrow-right paso-flecha" },
    });
    /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
    /** @type {__VLS_StyleScopedClasses['fa-arrow-right']} */ ;
    /** @type {__VLS_StyleScopedClasses['paso-flecha']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ onClick: (__VLS_ctx.equiposNoImplementado) },
        ...{ class: "paso-card" },
    });
    /** @type {__VLS_StyleScopedClasses['paso-card']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "paso-num" },
    });
    /** @type {__VLS_StyleScopedClasses['paso-num']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "paso-icono" },
    });
    /** @type {__VLS_StyleScopedClasses['paso-icono']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
        ...{ class: "fa-solid fa-boxes-stacked" },
    });
    /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
    /** @type {__VLS_StyleScopedClasses['fa-boxes-stacked']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "paso-info" },
    });
    /** @type {__VLS_StyleScopedClasses['paso-info']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "paso-nombre" },
    });
    /** @type {__VLS_StyleScopedClasses['paso-nombre']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "paso-desc" },
    });
    /** @type {__VLS_StyleScopedClasses['paso-desc']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
        ...{ class: "fa-solid fa-arrow-right paso-flecha" },
    });
    /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
    /** @type {__VLS_StyleScopedClasses['fa-arrow-right']} */ ;
    /** @type {__VLS_StyleScopedClasses['paso-flecha']} */ ;
}
// @ts-ignore
[equiposNoImplementado,];
var __VLS_3;
// @ts-ignore
[];
const __VLS_export = (await import('vue')).defineComponent({});
export default {};
