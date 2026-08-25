import { computed, onMounted, onBeforeUnmount, ref } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import AppLayout from '@/layouts/AppLayout.vue';
const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const STORAGE_KEY = 'ephdem_prestaciones_seleccionadas';
const PRESTACIONES_URL = `${import.meta.env.VITE_API_BASE}/get_prestaciones.php`;
const PRESTACIONES_PRIORITARIAS = [
    'Día Cama de Hospitalización Integral Adulto en Unidad de Cuidado Intensivo (U.C.I.)',
    'Día Cama de Hospitalización Integral Adulto en Unidad de Tratamiento Intermedio (U.T.I.)',
];
const filtros = ref({ area: '', texto: '', subarea: '', recinto: '' });
const prestaciones = ref([]);
const isLoading = ref(false);
const loadError = ref('');
const mostrarConsideraciones = ref(false);
const consideracionesRef = ref(null);
const seleccionadas = ref([]);
const nombreProyectoActivo = ref('');
function normalizarTexto(valor) {
    return String(valor ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
}
const opcionesArea = computed(() => [...new Set(prestaciones.value.map(p => p.area))]);
const opcionesSubarea = computed(() => {
    const base = filtros.value.area ? prestaciones.value.filter(p => p.area === filtros.value.area) : prestaciones.value;
    return [...new Set(base.map(p => p.subarea))];
});
const opcionesRecinto = computed(() => {
    const base = prestaciones.value.filter(p => {
        if (filtros.value.area && p.area !== filtros.value.area)
            return false;
        if (filtros.value.subarea && p.subarea !== filtros.value.subarea)
            return false;
        return true;
    });
    return [...new Set(base.map(p => p.recinto))];
});
const prestacionesFiltradas = computed(() => {
    const seleccionadasIds = new Set(seleccionadas.value.map(p => p.id));
    const texto = normalizarTexto(filtros.value.texto);
    return prestaciones.value.filter(p => {
        if (seleccionadasIds.has(p.id))
            return false;
        if (filtros.value.area && p.area !== filtros.value.area)
            return false;
        if (filtros.value.subarea && p.subarea !== filtros.value.subarea)
            return false;
        if (filtros.value.recinto && p.recinto !== filtros.value.recinto)
            return false;
        if (texto) {
            const codigo = normalizarTexto(p.codigo_fonasa);
            const nombre = normalizarTexto(p.nombre_prestacion);
            if (!codigo.includes(texto) && !nombre.includes(texto))
                return false;
        }
        return true;
    }).sort((a, b) => {
        const prioA = PRESTACIONES_PRIORITARIAS.findIndex(n => normalizarTexto(n) === normalizarTexto(a.nombre_prestacion));
        const prioB = PRESTACIONES_PRIORITARIAS.findIndex(n => normalizarTexto(n) === normalizarTexto(b.nombre_prestacion));
        if (prioA !== prioB) {
            if (prioA === -1)
                return 1;
            if (prioB === -1)
                return -1;
            return prioA - prioB;
        }
        return normalizarTexto(a.nombre_prestacion).localeCompare(normalizarTexto(b.nombre_prestacion), 'es', { sensitivity: 'base' });
    });
});
const prestacionesSeleccionadas = computed(() => seleccionadas.value);
onMounted(async () => {
    nombreProyectoActivo.value = localStorage.getItem('ephdem_nombre_proyecto_activo') || 'Desconocido';
    await cargarPrestaciones();
    if (route.params.proyectoId) {
        await cargarDesdeServidor(route.params.proyectoId);
    }
    else {
        cargarSeleccionadas();
    }
    document.addEventListener('pointerdown', cerrarConsideracionesSiCorresponde);
});
onBeforeUnmount(() => {
    document.removeEventListener('pointerdown', cerrarConsideracionesSiCorresponde);
});
async function cargarDesdeServidor(proyectoId) {
    try {
        const [resp, respBase] = await Promise.all([
            fetch(`${import.meta.env.VITE_API_BASE}/get_prestaciones_demanda.php?proyecto_id=${proyectoId}`),
            fetch(`${import.meta.env.VITE_API_BASE}/get_prestaciones.php`),
        ]);
        const json = await resp.json();
        const jsonBase = await respBase.json();
        if (!resp.ok || !json.ok) {
            alert(json.error || 'Error al cargar datos del proyecto.');
            return;
        }
        const tiempoMap = new Map();
        if (jsonBase?.ok && Array.isArray(jsonBase?.datos)) {
            for (const p of jsonBase.datos) {
                if (p.id_prestacion != null && p.tiempo_procedimiento != null)
                    tiempoMap.set(p.id_prestacion, p.tiempo_procedimiento);
            }
        }
        let tpOverrides = {};
        try {
            const rawOverrides = localStorage.getItem(`ephdem_tp_overrides_${proyectoId}`);
            if (rawOverrides)
                tpOverrides = JSON.parse(rawOverrides);
        }
        catch (_) { }
        const idsGuardados = new Set(json.datos.map(item => item.id_prestacion));
        seleccionadas.value = prestaciones.value.filter(p => idsGuardados.has(p.id));
        const params = json.datos.map(item => ({
            id: item.id_prestacion,
            demanda: item.valores?.demanda_anual ?? 0,
            diasAnuales: item.valores?.dias_laborales ?? item.defaults?.dias_laborales ?? 365,
            disponibilidad: item.valores ? (item.valores.disponibilidad * 100) : (item.defaults?.disponibilidad ? item.defaults.disponibilidad * 100 : 100),
            jornadaLaboral: item.valores?.jornada_efectiva ?? item.defaults?.jornada_efectiva ?? 24,
            tiempoProcedimiento: tpOverrides[item.id_prestacion] ?? tiempoMap.get(item.id_prestacion) ?? 60
        }));
        localStorage.setItem('ephdem_parametros_prestaciones', JSON.stringify(params));
        localStorage.setItem('ephdem_origen_edicion', 'prestaciones');
    }
    catch (e) {
        alert('Error de red al intentar cargar datos del proyecto.');
    }
}
async function cargarPrestaciones() {
    isLoading.value = true;
    loadError.value = '';
    try {
        const response = await fetch(PRESTACIONES_URL, { method: 'GET', credentials: 'same-origin' });
        if (!response.ok)
            throw new Error('No se pudieron cargar las prestaciones.');
        const payload = await response.json();
        if (!payload?.ok)
            throw new Error(payload?.error || 'Error al cargar prestaciones.');
        const rawPrestaciones = Array.isArray(payload?.datos) ? payload.datos : Array.isArray(payload?.datos?.prestaciones) ? payload.datos.prestaciones : [];
        prestaciones.value = rawPrestaciones.map(item => ({
            id: item?.id_prestacion ?? item?.id,
            codigo_fonasa: item?.codigo_fonasa ?? '',
            nombre_prestacion: item?.nombre_prestacion ?? '',
            tiempo_procedimiento: item?.tiempo_procedimiento ?? item?.tiempoProcedimiento ?? '',
            area: item?.area_hospitalaria ?? item?.area ?? '',
            subarea: item?.subarea_hospitalaria ?? item?.subarea ?? '',
            recinto: item?.nombre_recinto ?? item?.recinto ?? '',
        })).filter(item => item.id != null);
    }
    catch (error) {
        loadError.value = 'No se pudieron cargar las prestaciones. Intenta nuevamente.';
        prestaciones.value = [];
    }
    finally {
        isLoading.value = false;
    }
}
function cargarSeleccionadas() {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (!raw)
        return;
    try {
        const guardadas = JSON.parse(raw);
        if (!Array.isArray(guardadas))
            return;
        const idsGuardados = new Set(guardadas.map(item => item.id));
        seleccionadas.value = prestaciones.value.filter(p => idsGuardados.has(p.id));
    }
    catch (error) {
        localStorage.removeItem(STORAGE_KEY);
    }
}
function agregarPrestacion(prestacion) {
    if (seleccionadas.value.find(p => p.id === prestacion.id))
        return;
    seleccionadas.value.push(prestacion);
}
function quitarPrestacion(prestacion) { seleccionadas.value = seleccionadas.value.filter(p => p.id !== prestacion.id); }
function limpiarSeleccion() { seleccionadas.value = []; }
function guardarYConfirmar() {
    if (seleccionadas.value.length === 0) {
        alert('Debes seleccionar al menos una prestación.');
        return;
    }
    localStorage.setItem(STORAGE_KEY, JSON.stringify(seleccionadas.value));
    if (route.params.proyectoId) {
        router.push(`/parametros/${route.params.proyectoId}`);
    }
    else {
        router.push('/parametros');
    }
}
function volverAtras() { router.back(); }
function cerrarSesion() { authStore.logout(); router.push('/login'); }
function cerrarConsideracionesSiCorresponde(event) {
    const contenedor = consideracionesRef.value;
    if (!contenedor || !mostrarConsideraciones.value || contenedor.contains(event.target))
        return;
    mostrarConsideraciones.value = false;
}
const __VLS_ctx = {
    ...{},
    ...{},
};
let __VLS_components;
let __VLS_intrinsics;
let __VLS_directives;
/** @type {__VLS_StyleScopedClasses['filtro']} */ ;
/** @type {__VLS_StyleScopedClasses['filtro']} */ ;
/** @type {__VLS_StyleScopedClasses['filtros-panel']} */ ;
/** @type {__VLS_StyleScopedClasses['prestaciones-grid']} */ ;
/** @type {__VLS_StyleScopedClasses['acciones-finales']} */ ;
const __VLS_0 = AppLayout || AppLayout;
// @ts-ignore
const __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0({}));
const __VLS_2 = __VLS_1({}, ...__VLS_functionalComponentArgsRest(__VLS_1));
var __VLS_5 = {};
const { default: __VLS_6 } = __VLS_3.slots;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "prestaciones-page" },
});
/** @type {__VLS_StyleScopedClasses['prestaciones-page']} */ ;
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
    ...{ class: "prestaciones-content" },
});
/** @type {__VLS_StyleScopedClasses['prestaciones-content']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.header, __VLS_intrinsics.header)({
    ...{ class: "prestaciones-header" },
});
/** @type {__VLS_StyleScopedClasses['prestaciones-header']} */ ;
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
    ...{ class: "prestaciones-header-top" },
});
/** @type {__VLS_StyleScopedClasses['prestaciones-header-top']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)({
    ...{ class: "section-title" },
});
/** @type {__VLS_StyleScopedClasses['section-title']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ref: "consideracionesRef",
    ...{ class: "consideraciones-wrapper" },
});
/** @type {__VLS_StyleScopedClasses['consideraciones-wrapper']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
    ...{ onClick: (...[$event]) => {
            __VLS_ctx.mostrarConsideraciones = !__VLS_ctx.mostrarConsideraciones;
            // @ts-ignore
            [authStore, cerrarSesion, mostrarConsideraciones, mostrarConsideraciones,];
        } },
    type: "button",
    ...{ class: "consideraciones-toggle" },
    'aria-expanded': (__VLS_ctx.mostrarConsideraciones),
});
/** @type {__VLS_StyleScopedClasses['consideraciones-toggle']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
    ...{ class: "consideraciones-icono" },
});
/** @type {__VLS_StyleScopedClasses['consideraciones-icono']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
    ...{ class: "fa-solid fa-triangle-exclamation" },
});
/** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-triangle-exclamation']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
    ...{ class: "consideraciones-texto" },
});
/** @type {__VLS_StyleScopedClasses['consideraciones-texto']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)({
    ...{ class: "consideraciones-panel" },
    ...{ class: ({ 'is-open': __VLS_ctx.mostrarConsideraciones }) },
});
/** @type {__VLS_StyleScopedClasses['consideraciones-panel']} */ ;
/** @type {__VLS_StyleScopedClasses['is-open']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "consideraciones-contenido" },
});
__VLS_asFunctionalDirective(__VLS_directives.vShow, {})(null, { ...__VLS_directiveBindingRestFields, value: (__VLS_ctx.mostrarConsideraciones) }, null, null);
/** @type {__VLS_StyleScopedClasses['consideraciones-contenido']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.ol, __VLS_intrinsics.ol)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.li, __VLS_intrinsics.li)({});
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
    ...{ class: "instruccion-badge instruccion-badge--agregar" },
});
/** @type {__VLS_StyleScopedClasses['instruccion-badge']} */ ;
/** @type {__VLS_StyleScopedClasses['instruccion-badge--agregar']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
    ...{ class: "fa-solid fa-plus" },
});
/** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-plus']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
    ...{ class: "instruccion-badge instruccion-badge--quitar" },
});
/** @type {__VLS_StyleScopedClasses['instruccion-badge']} */ ;
/** @type {__VLS_StyleScopedClasses['instruccion-badge--quitar']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
    ...{ class: "fa-solid fa-xmark" },
});
/** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-xmark']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.strong, __VLS_intrinsics.strong)({});
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
    placeholder: "Código o nombre",
});
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "filtro" },
});
/** @type {__VLS_StyleScopedClasses['filtro']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.select, __VLS_intrinsics.select)({
    value: (__VLS_ctx.filtros.area),
});
__VLS_asFunctionalElement1(__VLS_intrinsics.option, __VLS_intrinsics.option)({
    value: "",
});
for (const [area] of __VLS_vFor((__VLS_ctx.opcionesArea))) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.option, __VLS_intrinsics.option)({
        key: (area),
        value: (area),
    });
    (area);
    // @ts-ignore
    [mostrarConsideraciones, mostrarConsideraciones, mostrarConsideraciones, nombreProyectoActivo, filtros, filtros, opcionesArea,];
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "filtro" },
});
/** @type {__VLS_StyleScopedClasses['filtro']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.select, __VLS_intrinsics.select)({
    value: (__VLS_ctx.filtros.subarea),
});
__VLS_asFunctionalElement1(__VLS_intrinsics.option, __VLS_intrinsics.option)({
    value: "",
});
for (const [subarea] of __VLS_vFor((__VLS_ctx.opcionesSubarea))) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.option, __VLS_intrinsics.option)({
        key: (subarea),
        value: (subarea),
    });
    (subarea);
    // @ts-ignore
    [filtros, opcionesSubarea,];
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "filtro" },
});
/** @type {__VLS_StyleScopedClasses['filtro']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.select, __VLS_intrinsics.select)({
    value: (__VLS_ctx.filtros.recinto),
});
__VLS_asFunctionalElement1(__VLS_intrinsics.option, __VLS_intrinsics.option)({
    value: "",
});
for (const [recinto] of __VLS_vFor((__VLS_ctx.opcionesRecinto))) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.option, __VLS_intrinsics.option)({
        key: (recinto),
        value: (recinto),
    });
    (recinto);
    // @ts-ignore
    [filtros, opcionesRecinto,];
}
__VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)({
    ...{ class: "prestaciones-grid" },
});
/** @type {__VLS_StyleScopedClasses['prestaciones-grid']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "prestaciones-panel" },
});
/** @type {__VLS_StyleScopedClasses['prestaciones-panel']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "panel-title" },
});
/** @type {__VLS_StyleScopedClasses['panel-title']} */ ;
if (__VLS_ctx.isLoading) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "lista-vacia" },
    });
    /** @type {__VLS_StyleScopedClasses['lista-vacia']} */ ;
}
else if (__VLS_ctx.loadError) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "lista-vacia" },
    });
    /** @type {__VLS_StyleScopedClasses['lista-vacia']} */ ;
    (__VLS_ctx.loadError);
}
else if (__VLS_ctx.prestacionesFiltradas.length === 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "lista-vacia" },
    });
    /** @type {__VLS_StyleScopedClasses['lista-vacia']} */ ;
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "prestaciones-lista" },
    });
    /** @type {__VLS_StyleScopedClasses['prestaciones-lista']} */ ;
    for (const [prestacion] of __VLS_vFor((__VLS_ctx.prestacionesFiltradas))) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            key: (prestacion.id),
            ...{ class: "prestacion-item" },
        });
        /** @type {__VLS_StyleScopedClasses['prestacion-item']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "prestacion-info" },
        });
        /** @type {__VLS_StyleScopedClasses['prestacion-info']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "prestacion-codigo" },
        });
        /** @type {__VLS_StyleScopedClasses['prestacion-codigo']} */ ;
        (prestacion.codigo_fonasa);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "prestacion-nombre" },
        });
        /** @type {__VLS_StyleScopedClasses['prestacion-nombre']} */ ;
        (prestacion.nombre_prestacion);
        __VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
            ...{ onClick: (...[$event]) => {
                    if (!!(__VLS_ctx.isLoading))
                        return;
                    if (!!(__VLS_ctx.loadError))
                        return;
                    if (!!(__VLS_ctx.prestacionesFiltradas.length === 0))
                        return;
                    __VLS_ctx.agregarPrestacion(prestacion);
                    // @ts-ignore
                    [isLoading, loadError, loadError, prestacionesFiltradas, prestacionesFiltradas, agregarPrestacion,];
                } },
            ...{ class: "accion accion-agregar" },
        });
        /** @type {__VLS_StyleScopedClasses['accion']} */ ;
        /** @type {__VLS_StyleScopedClasses['accion-agregar']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
            ...{ class: "fa-solid fa-plus" },
        });
        /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
        /** @type {__VLS_StyleScopedClasses['fa-plus']} */ ;
        // @ts-ignore
        [];
    }
}
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "prestaciones-panel" },
});
/** @type {__VLS_StyleScopedClasses['prestaciones-panel']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "panel-header-seleccionadas" },
});
/** @type {__VLS_StyleScopedClasses['panel-header-seleccionadas']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "panel-title" },
});
/** @type {__VLS_StyleScopedClasses['panel-title']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
    ...{ onClick: (__VLS_ctx.limpiarSeleccion) },
    type: "button",
    ...{ class: "btn-limpiar-seleccion" },
    disabled: (__VLS_ctx.prestacionesSeleccionadas.length === 0),
});
/** @type {__VLS_StyleScopedClasses['btn-limpiar-seleccion']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
    ...{ class: "limpiar-icono" },
});
/** @type {__VLS_StyleScopedClasses['limpiar-icono']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
    ...{ class: "fa-solid fa-trash-can" },
});
/** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-trash-can']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
    ...{ class: "limpiar-texto" },
});
/** @type {__VLS_StyleScopedClasses['limpiar-texto']} */ ;
if (__VLS_ctx.prestacionesSeleccionadas.length === 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "lista-vacia" },
    });
    /** @type {__VLS_StyleScopedClasses['lista-vacia']} */ ;
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "prestaciones-lista" },
    });
    /** @type {__VLS_StyleScopedClasses['prestaciones-lista']} */ ;
    for (const [prestacion] of __VLS_vFor((__VLS_ctx.prestacionesSeleccionadas))) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            key: (prestacion.id),
            ...{ class: "prestacion-item" },
        });
        /** @type {__VLS_StyleScopedClasses['prestacion-item']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "prestacion-info" },
        });
        /** @type {__VLS_StyleScopedClasses['prestacion-info']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "prestacion-codigo" },
        });
        /** @type {__VLS_StyleScopedClasses['prestacion-codigo']} */ ;
        (prestacion.codigo_fonasa);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "prestacion-nombre" },
        });
        /** @type {__VLS_StyleScopedClasses['prestacion-nombre']} */ ;
        (prestacion.nombre_prestacion);
        __VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
            ...{ onClick: (...[$event]) => {
                    if (!!(__VLS_ctx.prestacionesSeleccionadas.length === 0))
                        return;
                    __VLS_ctx.quitarPrestacion(prestacion);
                    // @ts-ignore
                    [limpiarSeleccion, prestacionesSeleccionadas, prestacionesSeleccionadas, prestacionesSeleccionadas, quitarPrestacion,];
                } },
            ...{ class: "accion accion-quitar" },
        });
        /** @type {__VLS_StyleScopedClasses['accion']} */ ;
        /** @type {__VLS_StyleScopedClasses['accion-quitar']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
            ...{ class: "fa-solid fa-xmark" },
        });
        /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
        /** @type {__VLS_StyleScopedClasses['fa-xmark']} */ ;
        // @ts-ignore
        [];
    }
}
__VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)({
    ...{ class: "acciones-finales" },
});
/** @type {__VLS_StyleScopedClasses['acciones-finales']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "acciones-resumen" },
});
/** @type {__VLS_StyleScopedClasses['acciones-resumen']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.b, __VLS_intrinsics.b)({});
(__VLS_ctx.prestacionesSeleccionadas.length);
__VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
    ...{ onClick: (__VLS_ctx.guardarYConfirmar) },
    ...{ class: "btn-confirmar" },
    disabled: (__VLS_ctx.prestacionesSeleccionadas.length === 0),
});
/** @type {__VLS_StyleScopedClasses['btn-confirmar']} */ ;
// @ts-ignore
[prestacionesSeleccionadas, prestacionesSeleccionadas, guardarYConfirmar,];
var __VLS_3;
// @ts-ignore
[];
const __VLS_export = (await import('vue')).defineComponent({});
export default {};
