import { ref, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import AppLayout from '@/layouts/AppLayout.vue';
const router = useRouter();
const authStore = useAuthStore();
const formulario = ref({ nombreProyecto: '' });
const proyectosPrevios = ref([]);
const cargandoProyectos = ref(true);
const ordenSeleccionado = ref('fecha_desc');
const proyectosPreviosOrdenados = computed(() => {
    const lista = [...proyectosPrevios.value];
    return lista.sort((a, b) => {
        if (ordenSeleccionado.value === 'alfabetico_asc')
            return (a.nombre_proyecto || '').localeCompare(b.nombre_proyecto || '');
        if (ordenSeleccionado.value === 'alfabetico_desc')
            return (b.nombre_proyecto || '').localeCompare(a.nombre_proyecto || '');
        if (ordenSeleccionado.value === 'fecha_asc')
            return (a.id || a.id_proyecto || 0) - (b.id || b.id_proyecto || 0);
        return (b.id || b.id_proyecto || 0) - (a.id || a.id_proyecto || 0);
    });
});
onMounted(async () => { await cargarProyectosPrevios(); });
async function cargarProyectosPrevios() {
    const userId = authStore.usuarioId;
    if (!userId)
        return;
    cargandoProyectos.value = true;
    try {
        const url = `${import.meta.env.VITE_API_BASE}/get/get_proyectos.php?usuario_id=${userId}`;
        const response = await fetch(url, { method: 'GET', credentials: 'same-origin' });
        const result = await response.json();
        if (result.ok && Array.isArray(result.datos))
            proyectosPrevios.value = result.datos;
    }
    catch (error) {
        console.error('Error al cargar proyectos previos:', error);
    }
    finally {
        cargandoProyectos.value = false;
    }
}
async function guardarProyecto() {
    const { nombreProyecto } = formulario.value;
    if (!nombreProyecto.trim()) {
        alert('Por favor, ingresa un nombre para el proyecto.');
        return;
    }
    const userId = authStore.usuarioId;
    if (!userId) {
        alert('No hay sesión activa.');
        router.push('/login');
        return;
    }
    try {
        const url = `${import.meta.env.VITE_API_BASE}/crear_proyecto.php`;
        const response = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nombre_proyecto: nombreProyecto, tipo_proyecto: 'Atención cerrada', usuario_id: userId })
        });
        const result = await response.json();
        if (!response.ok || !result.ok) {
            alert(result.error || 'Error al crear el proyecto.');
            return;
        }
        localStorage.removeItem('ephdem_prestaciones_seleccionadas');
        localStorage.removeItem('ephdem_parametros_prestaciones');
        localStorage.removeItem('ephdem_resultado_calculo');
        localStorage.setItem('ephdem_proyecto_activo', result.datos.id_proyecto);
        localStorage.setItem('ephdem_nombre_proyecto_activo', nombreProyecto);
        router.push('/prestaciones');
    }
    catch (error) {
        alert('Ocurrió un error de red al intentar comunicarse con el servidor.');
    }
}
function cancelar() { volverAtras(); }
function volverAtras() { router.back(); setTimeout(() => window.scrollTo({ top: 0, left: 0, behavior: 'auto' }), 0); }
function cerrarSesion() { authStore.logout(); router.push('/login'); }
function verProyecto(proyecto) {
    const id = proyecto.id || proyecto.id_proyecto;
    localStorage.setItem('ephdem_proyecto_activo', id);
    localStorage.setItem('ephdem_nombre_proyecto_activo', proyecto.nombre_proyecto);
    router.push(`/resultados/${id}`);
}
const __VLS_ctx = {
    ...{},
    ...{},
};
let __VLS_components;
let __VLS_intrinsics;
let __VLS_directives;
const __VLS_0 = AppLayout || AppLayout;
// @ts-ignore
const __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0({}));
const __VLS_2 = __VLS_1({}, ...__VLS_functionalComponentArgsRest(__VLS_1));
var __VLS_5 = {};
const { default: __VLS_6 } = __VLS_3.slots;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "creacion-proyecto-page" },
});
/** @type {__VLS_StyleScopedClasses['creacion-proyecto-page']} */ ;
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
    ...{ class: "creacion-content" },
});
/** @type {__VLS_StyleScopedClasses['creacion-content']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.header, __VLS_intrinsics.header)({
    ...{ class: "creacion-header" },
});
/** @type {__VLS_StyleScopedClasses['creacion-header']} */ ;
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
__VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)({
    ...{ class: "section-title" },
});
/** @type {__VLS_StyleScopedClasses['section-title']} */ ;
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
__VLS_asFunctionalElement1(__VLS_intrinsics.strong, __VLS_intrinsics.strong)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)({
    ...{ class: "formulario-panel" },
});
/** @type {__VLS_StyleScopedClasses['formulario-panel']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.form, __VLS_intrinsics.form)({
    ...{ onSubmit: (__VLS_ctx.guardarProyecto) },
    ...{ class: "formulario" },
});
/** @type {__VLS_StyleScopedClasses['formulario']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "form-group" },
});
/** @type {__VLS_StyleScopedClasses['form-group']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)({
    for: "nombre-proyecto",
    ...{ class: "form-label" },
});
/** @type {__VLS_StyleScopedClasses['form-label']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.input)({
    id: "nombre-proyecto",
    value: (__VLS_ctx.formulario.nombreProyecto),
    type: "text",
    ...{ class: "form-input" },
    placeholder: "Ingresa el nombre del proyecto",
    required: true,
});
/** @type {__VLS_StyleScopedClasses['form-input']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "acciones-formulario" },
});
/** @type {__VLS_StyleScopedClasses['acciones-formulario']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
    type: "submit",
    ...{ class: "btn-principal" },
});
/** @type {__VLS_StyleScopedClasses['btn-principal']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
    ...{ onClick: (__VLS_ctx.cancelar) },
    type: "button",
    ...{ class: "btn-secundario" },
});
/** @type {__VLS_StyleScopedClasses['btn-secundario']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)({
    ...{ class: "proyectos-panel" },
});
/** @type {__VLS_StyleScopedClasses['proyectos-panel']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "panel-header" },
});
/** @type {__VLS_StyleScopedClasses['panel-header']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.h3, __VLS_intrinsics.h3)({
    ...{ class: "panel-title" },
});
/** @type {__VLS_StyleScopedClasses['panel-title']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "panel-actions" },
});
/** @type {__VLS_StyleScopedClasses['panel-actions']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "sort-control" },
});
/** @type {__VLS_StyleScopedClasses['sort-control']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)({});
__VLS_asFunctionalElement1(__VLS_intrinsics.select, __VLS_intrinsics.select)({
    value: (__VLS_ctx.ordenSeleccionado),
    ...{ class: "sort-select" },
});
/** @type {__VLS_StyleScopedClasses['sort-select']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.option, __VLS_intrinsics.option)({
    value: "fecha_desc",
});
__VLS_asFunctionalElement1(__VLS_intrinsics.option, __VLS_intrinsics.option)({
    value: "fecha_asc",
});
__VLS_asFunctionalElement1(__VLS_intrinsics.option, __VLS_intrinsics.option)({
    value: "alfabetico_asc",
});
__VLS_asFunctionalElement1(__VLS_intrinsics.option, __VLS_intrinsics.option)({
    value: "alfabetico_desc",
});
if (__VLS_ctx.cargandoProyectos) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "proyectos-estado" },
    });
    /** @type {__VLS_StyleScopedClasses['proyectos-estado']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
        ...{ class: "fa-solid fa-spinner fa-spin" },
    });
    /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
    /** @type {__VLS_StyleScopedClasses['fa-spinner']} */ ;
    /** @type {__VLS_StyleScopedClasses['fa-spin']} */ ;
}
else if (__VLS_ctx.proyectosPrevios.length === 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "proyectos-estado" },
    });
    /** @type {__VLS_StyleScopedClasses['proyectos-estado']} */ ;
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "proyectos-table" },
    });
    /** @type {__VLS_StyleScopedClasses['proyectos-table']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "table-row table-head" },
    });
    /** @type {__VLS_StyleScopedClasses['table-row']} */ ;
    /** @type {__VLS_StyleScopedClasses['table-head']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "table-actions" },
    });
    /** @type {__VLS_StyleScopedClasses['table-actions']} */ ;
    for (const [proyecto] of __VLS_vFor((__VLS_ctx.proyectosPreviosOrdenados))) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            key: (proyecto.id),
            ...{ class: "table-row" },
        });
        /** @type {__VLS_StyleScopedClasses['table-row']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "table-name" },
        });
        /** @type {__VLS_StyleScopedClasses['table-name']} */ ;
        (proyecto.nombre_proyecto);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({});
        (proyecto.fecha_creacion);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "table-chip" },
        });
        /** @type {__VLS_StyleScopedClasses['table-chip']} */ ;
        (proyecto.tipo_proyecto);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "table-actions" },
        });
        /** @type {__VLS_StyleScopedClasses['table-actions']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
            ...{ onClick: (...[$event]) => {
                    if (!!(__VLS_ctx.cargandoProyectos))
                        return;
                    if (!!(__VLS_ctx.proyectosPrevios.length === 0))
                        return;
                    __VLS_ctx.verProyecto(proyecto);
                    // @ts-ignore
                    [authStore, cerrarSesion, guardarProyecto, formulario, cancelar, ordenSeleccionado, cargandoProyectos, proyectosPrevios, proyectosPreviosOrdenados, verProyecto,];
                } },
            ...{ class: "btn-primary" },
            type: "button",
        });
        /** @type {__VLS_StyleScopedClasses['btn-primary']} */ ;
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
