import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import AppLayout from '@/layouts/AppLayout.vue';
const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const filtros = ref({ texto: '', area: '', subarea: '' });
const prestaciones = ref([]);
const seleccionadas = ref([]);
const isLoading = ref(false);
const nombreProyectoActivo = ref('');
function normalizarTexto(valor) {
    return String(valor ?? '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
}
const opcionesArea = computed(() => [...new Set(prestaciones.value.map(p => p.area))]);
const opcionesSubarea = [
    'consultas y atencion medica',
    'consultas por otros profesionales de la salud',
    'educacion de grupo',
    'visitas domiciliarias',
    'miscelaneos',
    'actividad compin',
    'telemedicina',
    'teleinterconsulta (telemedicina)',
];
const prestacionesFiltradas = computed(() => {
    const selIds = new Set(seleccionadas.value.map(p => p.ID_PRESTACION));
    const texto = normalizarTexto(filtros.value.texto);
    return prestaciones.value.filter(p => {
        if (selIds.has(p.ID_PRESTACION))
            return false;
        if (filtros.value.area && p.area !== filtros.value.area)
            return false;
        if (filtros.value.subarea && p.subarea !== filtros.value.subarea)
            return false;
        if (texto) {
            const codigo = normalizarTexto(p.cod_prestacion);
            const nombre = normalizarTexto(p.nombre_prestacion);
            if (!codigo.includes(texto) && !nombre.includes(texto))
                return false;
        }
        return true;
    });
});
async function cargarPrestaciones() {
    isLoading.value = true;
    try {
        const url = `${import.meta.env.VITE_API_BASE}/get/get_prestaciones_abierta.php`;
        const resp = await fetch(url);
        const json = await resp.json();
        if (json.ok)
            prestaciones.value = json.datos;
    }
    catch (e) {
        console.error('Error al cargar prestaciones:', e);
    }
    finally {
        isLoading.value = false;
    }
}
function agregarPrestacion(p) {
    if (seleccionadas.value.find(s => s.ID_PRESTACION === p.ID_PRESTACION))
        return;
    seleccionadas.value.push(p);
}
function quitarPrestacion(p) {
    seleccionadas.value = seleccionadas.value.filter(s => s.ID_PRESTACION !== p.ID_PRESTACION);
}
function limpiarSeleccion() { seleccionadas.value = []; }
function guardarYConfirmar() {
    if (seleccionadas.value.length === 0) {
        alert('Debes seleccionar al menos una prestación.');
        return;
    }
    localStorage.setItem('ephdem_prestaciones_abierta', JSON.stringify(seleccionadas.value));
    const proyectoId = route.params.proyectoId || localStorage.getItem('ephdem_proyecto_activo_abierta');
    if (proyectoId)
        router.push(`/parametros-abierta/${proyectoId}`);
    else
        router.push('/parametros-abierta');
}
function volverAtras() { router.back(); }
function cerrarSesion() { authStore.logout(); router.push('/login'); }
async function cargarDesdeServidor(proyectoId) {
    const usuarioId = authStore.usuarioId;
    try {
        const url = `${import.meta.env.VITE_API_BASE}/get/get_prestaciones_demanda_abierta.php?proyecto_id=${proyectoId}&usuario_id=${usuarioId}`;
        const resp = await fetch(url);
        const json = await resp.json();
        if (json.ok && Array.isArray(json.datos) && json.datos.length > 0) {
            seleccionadas.value = json.datos.map(p => ({
                ID_PRESTACION: p.ID_PRESTACION,
                cod_prestacion: p.cod_prestacion,
                nombre_prestacion: p.nombre_prestacion,
                area: p.area,
            }));
        }
    }
    catch (e) {
        console.error('Error al cargar prestaciones del servidor:', e);
    }
}
onMounted(async () => {
    nombreProyectoActivo.value = localStorage.getItem('ephdem_nombre_proyecto_activo_abierta') || 'Desconocido';
    await cargarPrestaciones();
    const proyectoId = route.params.proyectoId || localStorage.getItem('ephdem_proyecto_activo_abierta');
    if (proyectoId)
        await cargarDesdeServidor(proyectoId);
});
const __VLS_ctx = {
    ...{},
    ...{},
};
let __VLS_components;
let __VLS_intrinsics;
let __VLS_directives;
/** @type {__VLS_StyleScopedClasses['filtro']} */ ;
/** @type {__VLS_StyleScopedClasses['filtro']} */ ;
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
    [authStore, cerrarSesion, nombreProyectoActivo, filtros, filtros, opcionesArea,];
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
for (const [sub] of __VLS_vFor((__VLS_ctx.opcionesSubarea))) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.option, __VLS_intrinsics.option)({
        key: (sub),
        value: (sub),
    });
    (sub);
    // @ts-ignore
    [filtros, opcionesSubarea,];
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
            key: (prestacion.ID_PRESTACION),
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
        (prestacion.cod_prestacion);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "prestacion-nombre" },
        });
        /** @type {__VLS_StyleScopedClasses['prestacion-nombre']} */ ;
        (prestacion.nombre_prestacion);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "prestacion-area" },
        });
        /** @type {__VLS_StyleScopedClasses['prestacion-area']} */ ;
        (prestacion.area);
        __VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
            ...{ onClick: (...[$event]) => {
                    if (!!(__VLS_ctx.isLoading))
                        return;
                    if (!!(__VLS_ctx.prestacionesFiltradas.length === 0))
                        return;
                    __VLS_ctx.agregarPrestacion(prestacion);
                    // @ts-ignore
                    [isLoading, prestacionesFiltradas, prestacionesFiltradas, agregarPrestacion,];
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
    ...{ class: "btn-limpiar-seleccion" },
    disabled: (__VLS_ctx.seleccionadas.length === 0),
});
/** @type {__VLS_StyleScopedClasses['btn-limpiar-seleccion']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
    ...{ class: "fa-solid fa-trash-can" },
});
/** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-trash-can']} */ ;
if (__VLS_ctx.seleccionadas.length === 0) {
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
    for (const [prestacion] of __VLS_vFor((__VLS_ctx.seleccionadas))) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            key: (prestacion.ID_PRESTACION),
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
        (prestacion.cod_prestacion);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "prestacion-nombre" },
        });
        /** @type {__VLS_StyleScopedClasses['prestacion-nombre']} */ ;
        (prestacion.nombre_prestacion);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "prestacion-area" },
        });
        /** @type {__VLS_StyleScopedClasses['prestacion-area']} */ ;
        (prestacion.area);
        __VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
            ...{ onClick: (...[$event]) => {
                    if (!!(__VLS_ctx.seleccionadas.length === 0))
                        return;
                    __VLS_ctx.quitarPrestacion(prestacion);
                    // @ts-ignore
                    [limpiarSeleccion, seleccionadas, seleccionadas, seleccionadas, quitarPrestacion,];
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
(__VLS_ctx.seleccionadas.length);
__VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
    ...{ onClick: (__VLS_ctx.guardarYConfirmar) },
    ...{ class: "btn-confirmar" },
    disabled: (__VLS_ctx.seleccionadas.length === 0),
});
/** @type {__VLS_StyleScopedClasses['btn-confirmar']} */ ;
// @ts-ignore
[seleccionadas, seleccionadas, guardarYConfirmar,];
var __VLS_3;
// @ts-ignore
[];
const __VLS_export = (await import('vue')).defineComponent({});
export default {};
