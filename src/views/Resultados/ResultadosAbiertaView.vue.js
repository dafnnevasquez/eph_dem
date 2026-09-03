import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import AppLayout from '@/layouts/AppLayout.vue';
const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const cargando = ref(true);
const error = ref(null);
const nombreProyecto = ref('Proyecto seleccionado');
const proyectoIdActivo = ref(null);
const equiposSummary = ref({});
const recintoSummary = ref({});
const prestaciones = ref([]);
const abiertos = ref({});
const resumenEquiposAbierto = ref(true);
const resumenRecintosAbierto = ref(true);
const totalEquipos = computed(() => Object.values(equiposSummary.value).reduce((a, b) => a + b, 0));
function togglePrestacion(id) {
    abiertos.value = { ...abiertos.value, [id]: !abiertos.value[id] };
}
function aplicarDatos(datos) {
    proyectoIdActivo.value = datos.proyecto_id ?? null;
    nombreProyecto.value = datos.nombre_proyecto ?? localStorage.getItem('ephdem_nombre_proyecto_activo_abierta') ?? 'Desconocido';
    equiposSummary.value = datos.equipos_summary ?? {};
    recintoSummary.value = datos.recinto_summary ?? {};
    prestaciones.value = datos.prestaciones ?? [];
    cargando.value = false;
}
function cargarDesdeLocalStorage() {
    const raw = localStorage.getItem('ephdem_resultado_abierta');
    if (!raw) {
        error.value = 'No hay resultados disponibles.';
        cargando.value = false;
        return;
    }
    try {
        const parsed = JSON.parse(raw);
        aplicarDatos(parsed);
    }
    catch (e) {
        error.value = 'Error al leer los resultados.';
        cargando.value = false;
    }
}
async function cargarDesdeServidor(proyectoId) {
    const usuarioId = authStore.usuarioId;
    try {
        const url = `${import.meta.env.VITE_API_BASE}/get/obtener_resultados_abierta.php?proyecto_id=${proyectoId}&usuario_id=${usuarioId}`;
        const resp = await fetch(url);
        const json = await resp.json();
        if (!json.ok) {
            error.value = json.error || 'Error al cargar resultados.';
            cargando.value = false;
        }
        else {
            aplicarDatos(json.datos);
        }
    }
    catch (e) {
        error.value = 'Error de red.';
        cargando.value = false;
    }
}
function editarParametros() { router.push(`/parametros-abierta/${proyectoIdActivo.value}`); }
function modificarPrestaciones() { router.push(`/prestaciones-abierta/${proyectoIdActivo.value}`); }
function volverAtras() { router.back(); }
function cerrarSesion() { authStore.logout(); router.push('/login'); }
function exportarExcel() {
    if (!proyectoIdActivo.value) {
        alert('No se pudo identificar el proyecto.');
        return;
    }
    window.open(`${import.meta.env.VITE_API_BASE}/generar/generar_xls_abierta.php?id=${proyectoIdActivo.value}&usuario_id=${authStore.usuarioId}`, '_blank');
}
function exportarPdf() {
    if (!proyectoIdActivo.value) {
        alert('No se pudo identificar el proyecto.');
        return;
    }
    window.open(`${import.meta.env.VITE_API_BASE}/generar/generar_pdf_abierta.php?id=${proyectoIdActivo.value}&usuario_id=${authStore.usuarioId}`, '_blank');
}
onMounted(() => {
    proyectoIdActivo.value = route.params.proyectoId || localStorage.getItem('ephdem_proyecto_activo_abierta');
    if (route.params.proyectoId)
        cargarDesdeServidor(route.params.proyectoId);
    else
        cargarDesdeLocalStorage();
});
const __VLS_ctx = {
    ...{},
    ...{},
};
let __VLS_components;
let __VLS_intrinsics;
let __VLS_directives;
/** @type {__VLS_StyleScopedClasses['row-total']} */ ;
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
    __VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
        ...{ onClick: (__VLS_ctx.editarParametros) },
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
    (__VLS_ctx.totalEquipos);
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
        ...{ class: "metric-label" },
    });
    /** @type {__VLS_StyleScopedClasses['metric-label']} */ ;
}
if (!__VLS_ctx.cargando && !__VLS_ctx.error) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)({
        ...{ class: "resumen-panel" },
    });
    /** @type {__VLS_StyleScopedClasses['resumen-panel']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ onClick: (...[$event]) => {
                if (!(!__VLS_ctx.cargando && !__VLS_ctx.error))
                    return;
                __VLS_ctx.resumenEquiposAbierto = !__VLS_ctx.resumenEquiposAbierto;
                // @ts-ignore
                [proyectoIdActivo, proyectoIdActivo, proyectoIdActivo, editarParametros, modificarPrestaciones, authStore, cerrarSesion, exportarExcel, exportarPdf, nombreProyecto, cargando, cargando, error, error, error, totalEquipos, resumenEquiposAbierto, resumenEquiposAbierto,];
            } },
        ...{ class: "panel-title panel-title-toggle" },
    });
    /** @type {__VLS_StyleScopedClasses['panel-title']} */ ;
    /** @type {__VLS_StyleScopedClasses['panel-title-toggle']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
        ...{ class: "fa-solid" },
        ...{ class: (__VLS_ctx.resumenEquiposAbierto ? 'fa-chevron-up' : 'fa-chevron-down') },
    });
    /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "resumen-list" },
    });
    __VLS_asFunctionalDirective(__VLS_directives.vShow, {})(null, { ...__VLS_directiveBindingRestFields, value: (__VLS_ctx.resumenEquiposAbierto) }, null, null);
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
    if (Object.keys(__VLS_ctx.equiposSummary).length === 0) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "lista-vacia" },
        });
        /** @type {__VLS_StyleScopedClasses['lista-vacia']} */ ;
    }
    for (const [cantidad, equipo] of __VLS_vFor((__VLS_ctx.equiposSummary))) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            key: (equipo),
            ...{ class: "resumen-row" },
        });
        /** @type {__VLS_StyleScopedClasses['resumen-row']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "equipo-nombre" },
        });
        /** @type {__VLS_StyleScopedClasses['equipo-nombre']} */ ;
        (equipo);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "row-total" },
        });
        /** @type {__VLS_StyleScopedClasses['row-total']} */ ;
        (cantidad);
        // @ts-ignore
        [resumenEquiposAbierto, resumenEquiposAbierto, equiposSummary, equiposSummary,];
    }
}
if (!__VLS_ctx.cargando && !__VLS_ctx.error && Object.keys(__VLS_ctx.recintoSummary).length > 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)({
        ...{ class: "resumen-panel" },
    });
    /** @type {__VLS_StyleScopedClasses['resumen-panel']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ onClick: (...[$event]) => {
                if (!(!__VLS_ctx.cargando && !__VLS_ctx.error && Object.keys(__VLS_ctx.recintoSummary).length > 0))
                    return;
                __VLS_ctx.resumenRecintosAbierto = !__VLS_ctx.resumenRecintosAbierto;
                // @ts-ignore
                [cargando, error, recintoSummary, resumenRecintosAbierto, resumenRecintosAbierto,];
            } },
        ...{ class: "panel-title panel-title-toggle" },
    });
    /** @type {__VLS_StyleScopedClasses['panel-title']} */ ;
    /** @type {__VLS_StyleScopedClasses['panel-title-toggle']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
        ...{ class: "fa-solid" },
        ...{ class: (__VLS_ctx.resumenRecintosAbierto ? 'fa-chevron-up' : 'fa-chevron-down') },
    });
    /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "resumen-list" },
    });
    __VLS_asFunctionalDirective(__VLS_directives.vShow, {})(null, { ...__VLS_directiveBindingRestFields, value: (__VLS_ctx.resumenRecintosAbierto) }, null, null);
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
    for (const [cantidad, recinto] of __VLS_vFor((__VLS_ctx.recintoSummary))) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            key: (recinto),
            ...{ class: "resumen-row" },
        });
        /** @type {__VLS_StyleScopedClasses['resumen-row']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "equipo-nombre" },
        });
        /** @type {__VLS_StyleScopedClasses['equipo-nombre']} */ ;
        (recinto);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "row-total" },
        });
        /** @type {__VLS_StyleScopedClasses['row-total']} */ ;
        (cantidad);
        // @ts-ignore
        [recintoSummary, resumenRecintosAbierto, resumenRecintosAbierto,];
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
    for (const [prestacion] of __VLS_vFor((__VLS_ctx.prestaciones))) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            key: (prestacion.ID_PRESTACION),
            ...{ class: "recinto-card" },
        });
        /** @type {__VLS_StyleScopedClasses['recinto-card']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ onClick: (...[$event]) => {
                    if (!(!__VLS_ctx.cargando && !__VLS_ctx.error))
                        return;
                    __VLS_ctx.togglePrestacion(prestacion.ID_PRESTACION);
                    // @ts-ignore
                    [cargando, error, prestaciones, togglePrestacion,];
                } },
            ...{ class: "recinto-title recinto-title-toggle" },
        });
        /** @type {__VLS_StyleScopedClasses['recinto-title']} */ ;
        /** @type {__VLS_StyleScopedClasses['recinto-title-toggle']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        (prestacion.COD_PRESTACION || prestacion.ID_PRESTACION);
        (prestacion.NOMBRE_PRESTACION || 'Prestación');
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
            ...{ class: "recinto-count" },
        });
        /** @type {__VLS_StyleScopedClasses['recinto-count']} */ ;
        (prestacion.REQUERIMIENTO);
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
            ...{ class: "fa-solid" },
            ...{ class: (__VLS_ctx.abiertos[prestacion.ID_PRESTACION] ? 'fa-chevron-up' : 'fa-chevron-down') },
        });
        /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "recinto-body" },
        });
        __VLS_asFunctionalDirective(__VLS_directives.vShow, {})(null, { ...__VLS_directiveBindingRestFields, value: (__VLS_ctx.abiertos[prestacion.ID_PRESTACION]) }, null, null);
        /** @type {__VLS_StyleScopedClasses['recinto-body']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "tabla-mini" },
        });
        /** @type {__VLS_StyleScopedClasses['tabla-mini']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "tabla-mini-head" },
            ...{ style: {} },
        });
        /** @type {__VLS_StyleScopedClasses['tabla-mini-head']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
            ...{ class: "tabla-mini-cantidad" },
        });
        /** @type {__VLS_StyleScopedClasses['tabla-mini-cantidad']} */ ;
        for (const [eq] of __VLS_vFor((prestacion.EQUIPOS))) {
            __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
                key: (eq.EQUIPO + eq.RECINTO),
                ...{ class: "tabla-mini-row" },
                ...{ style: {} },
            });
            /** @type {__VLS_StyleScopedClasses['tabla-mini-row']} */ ;
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
            (eq.EQUIPO);
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
            (eq.TIPO_EQUIPO);
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
            (eq.RECINTO);
            __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
                ...{ class: "tabla-mini-cantidad" },
            });
            /** @type {__VLS_StyleScopedClasses['tabla-mini-cantidad']} */ ;
            (eq.CANTIDAD);
            // @ts-ignore
            [abiertos, abiertos,];
        }
        // @ts-ignore
        [];
    }
}
// @ts-ignore
[];
var __VLS_3;
// @ts-ignore
[];
const __VLS_export = (await import('vue')).defineComponent({});
export default {};
