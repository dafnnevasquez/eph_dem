import { ref, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import AppLayout from '@/layouts/AppLayout.vue';
const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const nombreProyectoActivo = ref('');
const proyectoId = ref(null);
const filtroTexto = ref('');
const isLoading = ref(false);
const equipos = ref([]);
const seleccionados = ref([]);
const equiposFiltrados = computed(() => {
    const texto = filtroTexto.value.toLowerCase().trim();
    const selIds = new Set(seleccionados.value.map(e => e.id));
    return equipos.value
        .filter(e => !selIds.has(e.id))
        .filter(e => !texto || e.nombre_equipo.toLowerCase().includes(texto));
});
const totalEquipos = computed(() => seleccionados.value.reduce((acc, e) => acc + e.cantidad, 0));
async function cargarEquipos() {
    isLoading.value = true;
    try {
        const url = `${import.meta.env.VITE_API_BASE}/get/get_prestaciones.php`;
        // Por ahora usamos el mismo catálogo de equipos disponible
        // Cuando esté la BD conectada se llamará a un endpoint específico de equipos
        const resp = await fetch(`${import.meta.env.VITE_API_BASE}/get/get_prestaciones.php`);
        const json = await resp.json();
        // Provisional: cargamos prestaciones como placeholder
        // Se reemplazará por un endpoint de equipos
        equipos.value = [
            { id: 1, nombre_equipo: 'Carro de paro completo' },
            { id: 2, nombre_equipo: 'Monitor multiparámetros' },
            { id: 3, nombre_equipo: 'Bomba de infusión volumétrica' },
            { id: 4, nombre_equipo: 'Desfibrilador' },
            { id: 5, nombre_equipo: 'Ventilador mecánico' },
            { id: 6, nombre_equipo: 'Aspirador quirúrgico' },
            { id: 7, nombre_equipo: 'Lámpara cialítica' },
            { id: 8, nombre_equipo: 'Mesa quirúrgica' },
            { id: 9, nombre_equipo: 'Electrobisturí' },
            { id: 10, nombre_equipo: 'Camilla de traslado' },
        ];
    }
    catch (e) {
        console.error('Error al cargar equipos:', e);
    }
    finally {
        isLoading.value = false;
    }
}
function agregarEquipo(equipo) {
    if (seleccionados.value.find(e => e.id === equipo.id))
        return;
    seleccionados.value.push({ ...equipo, cantidad: 1 });
}
function quitarEquipo(equipo) {
    seleccionados.value = seleccionados.value.filter(e => e.id !== equipo.id);
}
function limpiarSeleccion() {
    seleccionados.value = [];
}
function incrementarCantidad(equipo) {
    const e = seleccionados.value.find(e => e.id === equipo.id);
    if (e)
        e.cantidad++;
}
function decrementarCantidad(equipo) {
    const e = seleccionados.value.find(e => e.id === equipo.id);
    if (e && e.cantidad > 1)
        e.cantidad--;
}
function guardarYFinalizar() {
    localStorage.setItem('ephdem_equipos_oportunidad', JSON.stringify(seleccionados.value));
    router.push(`/resultados/${proyectoId.value}`);
}
function volverAtras() { router.back(); }
function cerrarSesion() { authStore.logout(); router.push('/login'); }
onMounted(async () => {
    nombreProyectoActivo.value = localStorage.getItem('ephdem_nombre_proyecto_activo') || 'Desconocido';
    proyectoId.value = route.params.proyectoId || localStorage.getItem('ephdem_proyecto_activo');
    await cargarEquipos();
});
const __VLS_ctx = {
    ...{},
    ...{},
};
let __VLS_components;
let __VLS_intrinsics;
let __VLS_directives;
/** @type {__VLS_StyleScopedClasses['filtro']} */ ;
/** @type {__VLS_StyleScopedClasses['equipos-grid']} */ ;
const __VLS_0 = AppLayout || AppLayout;
// @ts-ignore
const __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0({}));
const __VLS_2 = __VLS_1({}, ...__VLS_functionalComponentArgsRest(__VLS_1));
var __VLS_5 = {};
const { default: __VLS_6 } = __VLS_3.slots;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "equipos-oportunidad-page" },
});
/** @type {__VLS_StyleScopedClasses['equipos-oportunidad-page']} */ ;
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
    ...{ class: "eo-content" },
});
/** @type {__VLS_StyleScopedClasses['eo-content']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.header, __VLS_intrinsics.header)({
    ...{ class: "eo-header" },
});
/** @type {__VLS_StyleScopedClasses['eo-header']} */ ;
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
__VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)({
    ...{ class: "filtros-panel" },
});
/** @type {__VLS_StyleScopedClasses['filtros-panel']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "filtro" },
});
/** @type {__VLS_StyleScopedClasses['filtro']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.input)({
    value: (__VLS_ctx.filtroTexto),
    type: "text",
    placeholder: "Nombre del equipo",
});
__VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)({
    ...{ class: "equipos-grid" },
});
/** @type {__VLS_StyleScopedClasses['equipos-grid']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "equipos-panel" },
});
/** @type {__VLS_StyleScopedClasses['equipos-panel']} */ ;
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
else if (__VLS_ctx.equiposFiltrados.length === 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "lista-vacia" },
    });
    /** @type {__VLS_StyleScopedClasses['lista-vacia']} */ ;
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "equipos-lista" },
    });
    /** @type {__VLS_StyleScopedClasses['equipos-lista']} */ ;
    for (const [equipo] of __VLS_vFor((__VLS_ctx.equiposFiltrados))) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            key: (equipo.id),
            ...{ class: "equipo-item" },
        });
        /** @type {__VLS_StyleScopedClasses['equipo-item']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "equipo-info" },
        });
        /** @type {__VLS_StyleScopedClasses['equipo-info']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "equipo-nombre" },
        });
        /** @type {__VLS_StyleScopedClasses['equipo-nombre']} */ ;
        (equipo.nombre_equipo);
        __VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
            ...{ onClick: (...[$event]) => {
                    if (!!(__VLS_ctx.isLoading))
                        return;
                    if (!!(__VLS_ctx.equiposFiltrados.length === 0))
                        return;
                    __VLS_ctx.agregarEquipo(equipo);
                    // @ts-ignore
                    [authStore, cerrarSesion, nombreProyectoActivo, filtroTexto, isLoading, equiposFiltrados, equiposFiltrados, agregarEquipo,];
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
    ...{ class: "equipos-panel" },
});
/** @type {__VLS_StyleScopedClasses['equipos-panel']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "panel-header-seleccionados" },
});
/** @type {__VLS_StyleScopedClasses['panel-header-seleccionados']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "panel-title" },
});
/** @type {__VLS_StyleScopedClasses['panel-title']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
    ...{ onClick: (__VLS_ctx.limpiarSeleccion) },
    ...{ class: "btn-limpiar" },
    disabled: (__VLS_ctx.seleccionados.length === 0),
});
/** @type {__VLS_StyleScopedClasses['btn-limpiar']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
    ...{ class: "fa-solid fa-trash-can" },
});
/** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-trash-can']} */ ;
if (__VLS_ctx.seleccionados.length === 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "lista-vacia" },
    });
    /** @type {__VLS_StyleScopedClasses['lista-vacia']} */ ;
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "equipos-lista" },
    });
    /** @type {__VLS_StyleScopedClasses['equipos-lista']} */ ;
    for (const [equipo] of __VLS_vFor((__VLS_ctx.seleccionados))) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            key: (equipo.id),
            ...{ class: "equipo-item equipo-seleccionado" },
        });
        /** @type {__VLS_StyleScopedClasses['equipo-item']} */ ;
        /** @type {__VLS_StyleScopedClasses['equipo-seleccionado']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "equipo-info" },
        });
        /** @type {__VLS_StyleScopedClasses['equipo-info']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "equipo-nombre" },
        });
        /** @type {__VLS_StyleScopedClasses['equipo-nombre']} */ ;
        (equipo.nombre_equipo);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "equipo-cantidad-control" },
        });
        /** @type {__VLS_StyleScopedClasses['equipo-cantidad-control']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
            ...{ onClick: (...[$event]) => {
                    if (!!(__VLS_ctx.seleccionados.length === 0))
                        return;
                    __VLS_ctx.decrementarCantidad(equipo);
                    // @ts-ignore
                    [limpiarSeleccion, seleccionados, seleccionados, seleccionados, decrementarCantidad,];
                } },
            ...{ class: "btn-cantidad" },
        });
        /** @type {__VLS_StyleScopedClasses['btn-cantidad']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
            ...{ class: "fa-solid fa-minus" },
        });
        /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
        /** @type {__VLS_StyleScopedClasses['fa-minus']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
            ...{ class: "cantidad-valor" },
        });
        /** @type {__VLS_StyleScopedClasses['cantidad-valor']} */ ;
        (equipo.cantidad);
        __VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
            ...{ onClick: (...[$event]) => {
                    if (!!(__VLS_ctx.seleccionados.length === 0))
                        return;
                    __VLS_ctx.incrementarCantidad(equipo);
                    // @ts-ignore
                    [incrementarCantidad,];
                } },
            ...{ class: "btn-cantidad" },
        });
        /** @type {__VLS_StyleScopedClasses['btn-cantidad']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
            ...{ class: "fa-solid fa-plus" },
        });
        /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
        /** @type {__VLS_StyleScopedClasses['fa-plus']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
            ...{ onClick: (...[$event]) => {
                    if (!!(__VLS_ctx.seleccionados.length === 0))
                        return;
                    __VLS_ctx.quitarEquipo(equipo);
                    // @ts-ignore
                    [quitarEquipo,];
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
if (__VLS_ctx.seleccionados.length > 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)({
        ...{ class: "resumen-panel" },
    });
    /** @type {__VLS_StyleScopedClasses['resumen-panel']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "panel-title" },
    });
    /** @type {__VLS_StyleScopedClasses['panel-title']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "resumen-tabla" },
    });
    /** @type {__VLS_StyleScopedClasses['resumen-tabla']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "resumen-head" },
    });
    /** @type {__VLS_StyleScopedClasses['resumen-head']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
        ...{ class: "resumen-cant" },
    });
    /** @type {__VLS_StyleScopedClasses['resumen-cant']} */ ;
    for (const [equipo] of __VLS_vFor((__VLS_ctx.seleccionados))) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            key: (equipo.id),
            ...{ class: "resumen-row" },
        });
        /** @type {__VLS_StyleScopedClasses['resumen-row']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        (equipo.nombre_equipo);
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
            ...{ class: "resumen-cant" },
        });
        /** @type {__VLS_StyleScopedClasses['resumen-cant']} */ ;
        (equipo.cantidad);
        // @ts-ignore
        [seleccionados, seleccionados,];
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "resumen-total" },
    });
    /** @type {__VLS_StyleScopedClasses['resumen-total']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.strong, __VLS_intrinsics.strong)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
        ...{ class: "resumen-cant" },
    });
    /** @type {__VLS_StyleScopedClasses['resumen-cant']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.strong, __VLS_intrinsics.strong)({});
    (__VLS_ctx.totalEquipos);
}
__VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)({
    ...{ class: "acciones-finales" },
});
/** @type {__VLS_StyleScopedClasses['acciones-finales']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
    ...{ onClick: (...[$event]) => {
            __VLS_ctx.router.push(`/rrhh/${__VLS_ctx.proyectoId}`);
            // @ts-ignore
            [router, totalEquipos, proyectoId,];
        } },
    ...{ class: "btn-secundario" },
});
/** @type {__VLS_StyleScopedClasses['btn-secundario']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
    ...{ class: "fa-solid fa-arrow-left" },
});
/** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-arrow-left']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
    ...{ onClick: (__VLS_ctx.guardarYFinalizar) },
    ...{ class: "btn-principal" },
    disabled: (__VLS_ctx.seleccionados.length === 0),
});
/** @type {__VLS_StyleScopedClasses['btn-principal']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
    ...{ class: "fa-solid fa-flag-checkered" },
});
/** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-flag-checkered']} */ ;
// @ts-ignore
[seleccionados, guardarYFinalizar,];
var __VLS_3;
// @ts-ignore
[];
const __VLS_export = (await import('vue')).defineComponent({});
export default {};
