import { ref, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import AppLayout from '@/layouts/AppLayout.vue';
const router = useRouter();
const route = useRoute();
const authStore = useAuthStore();
const nombreProyectoActivo = ref('');
const proyectoId = ref(null);
// Categorías de personal (provisional — vendrán de BD)
const categorias = ref([
    { id: 1, nombre: 'Médico Cirujano', descripcion: 'Realiza los procedimientos quirúrgicos', ratioEquipos: 1 },
    { id: 2, nombre: 'Anestesista', descripcion: 'Administra la anestesia durante el procedimiento', ratioEquipos: 1 },
    { id: 3, nombre: 'Arsenalera', descripcion: 'Asiste en pabellón y maneja el instrumental', ratioEquipos: 2 },
    { id: 4, nombre: 'Enfermera/o', descripcion: 'Asistencia clínica en el recinto', ratioEquipos: 3 },
    { id: 5, nombre: 'Técnico Paramédico', descripcion: 'Apoyo técnico en procedimientos', ratioEquipos: 3 },
]);
// Recintos con equipamiento calculado (provisional — vendrán de los resultados)
const recintos = ref([
    { id: 1, nombre: 'Cubículo UTI', equiposRequeridos: 0 },
    { id: 2, nombre: 'Cubículo UCI', equiposRequeridos: 0 },
    { id: 3, nombre: 'Pabellón menor', equiposRequeridos: 0 },
    { id: 4, nombre: 'Pabellón mayor', equiposRequeridos: 0 },
]);
// Dotación ingresada por el usuario
const dotacion = ref({
    1: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 },
    2: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 },
    3: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 },
    4: { 1: 0, 2: 0, 3: 0, 4: 0, 5: 0 },
});
function equiposPorPersonal(recintoId, categoriaId) {
    const cat = categorias.value.find(c => c.id === categoriaId);
    const personal = dotacion.value[recintoId]?.[categoriaId] ?? 0;
    if (!cat || personal <= 0)
        return 0;
    return personal * cat.ratioEquipos;
}
function estadoBadge(recintoId, categoriaId, equiposRequeridos) {
    const capacidad = equiposPorPersonal(recintoId, categoriaId);
    if (capacidad === 0)
        return 'badge-sin-datos';
    if (capacidad >= equiposRequeridos)
        return 'badge-ok';
    return 'badge-insuficiente';
}
function estadoTexto(recintoId, categoriaId, equiposRequeridos) {
    const capacidad = equiposPorPersonal(recintoId, categoriaId);
    if (capacidad === 0)
        return 'Sin datos';
    if (capacidad >= equiposRequeridos)
        return 'Suficiente';
    return 'Insuficiente';
}
function guardarYContinuar() {
    localStorage.setItem('ephdem_rrhh', JSON.stringify(dotacion.value));
    router.push(`/equipos-oportunidad/${proyectoId.value}`);
}
function volverAtras() {
    router.back();
}
function cerrarSesion() {
    authStore.logout();
    router.push('/login');
}
onMounted(() => {
    nombreProyectoActivo.value = localStorage.getItem('ephdem_nombre_proyecto_activo') || 'Desconocido';
    proyectoId.value = route.params.proyectoId || localStorage.getItem('ephdem_proyecto_activo');
});
const __VLS_ctx = {
    ...{},
    ...{},
};
let __VLS_components;
let __VLS_intrinsics;
let __VLS_directives;
/** @type {__VLS_StyleScopedClasses['tabla-rrhh']} */ ;
/** @type {__VLS_StyleScopedClasses['tabla-rrhh']} */ ;
/** @type {__VLS_StyleScopedClasses['tabla-rrhh']} */ ;
/** @type {__VLS_StyleScopedClasses['tabla-rrhh']} */ ;
const __VLS_0 = AppLayout || AppLayout;
// @ts-ignore
const __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0({}));
const __VLS_2 = __VLS_1({}, ...__VLS_functionalComponentArgsRest(__VLS_1));
var __VLS_5 = {};
const { default: __VLS_6 } = __VLS_3.slots;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "rrhh-page" },
});
/** @type {__VLS_StyleScopedClasses['rrhh-page']} */ ;
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
    ...{ class: "rrhh-content" },
});
/** @type {__VLS_StyleScopedClasses['rrhh-content']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.header, __VLS_intrinsics.header)({
    ...{ class: "rrhh-header" },
});
/** @type {__VLS_StyleScopedClasses['rrhh-header']} */ ;
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
    ...{ class: "rrhh-panel" },
});
/** @type {__VLS_StyleScopedClasses['rrhh-panel']} */ ;
for (const [recinto] of __VLS_vFor((__VLS_ctx.recintos))) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        key: (recinto.id),
        ...{ class: "recinto-card" },
    });
    /** @type {__VLS_StyleScopedClasses['recinto-card']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "recinto-title" },
    });
    /** @type {__VLS_StyleScopedClasses['recinto-title']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
        ...{ class: "fa-solid fa-hospital-user" },
    });
    /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
    /** @type {__VLS_StyleScopedClasses['fa-hospital-user']} */ ;
    (recinto.nombre);
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "tabla-scroll" },
    });
    /** @type {__VLS_StyleScopedClasses['tabla-scroll']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.table, __VLS_intrinsics.table)({
        ...{ class: "tabla-rrhh" },
    });
    /** @type {__VLS_StyleScopedClasses['tabla-rrhh']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.thead, __VLS_intrinsics.thead)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.tr, __VLS_intrinsics.tr)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.th, __VLS_intrinsics.th)({});
    __VLS_asFunctionalElement1(__VLS_intrinsics.tbody, __VLS_intrinsics.tbody)({});
    for (const [categoria] of __VLS_vFor((__VLS_ctx.categorias))) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.tr, __VLS_intrinsics.tr)({
            key: (categoria.id),
        });
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "categoria-nombre" },
        });
        /** @type {__VLS_StyleScopedClasses['categoria-nombre']} */ ;
        (categoria.nombre);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "categoria-hint" },
        });
        /** @type {__VLS_StyleScopedClasses['categoria-hint']} */ ;
        (categoria.descripcion);
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.input)({
            type: "number",
            min: "0",
            step: "1",
            ...{ class: "input-dotacion" },
            placeholder: "0",
        });
        (__VLS_ctx.dotacion[recinto.id][categoria.id]);
        /** @type {__VLS_StyleScopedClasses['input-dotacion']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)({
            ...{ class: "td-calculado" },
        });
        /** @type {__VLS_StyleScopedClasses['td-calculado']} */ ;
        (__VLS_ctx.equiposPorPersonal(recinto.id, categoria.id));
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)({
            ...{ class: "td-requerido" },
        });
        /** @type {__VLS_StyleScopedClasses['td-requerido']} */ ;
        (recinto.equiposRequeridos);
        __VLS_asFunctionalElement1(__VLS_intrinsics.td, __VLS_intrinsics.td)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
            ...{ class: "estado-badge" },
            ...{ class: (__VLS_ctx.estadoBadge(recinto.id, categoria.id, recinto.equiposRequeridos)) },
        });
        /** @type {__VLS_StyleScopedClasses['estado-badge']} */ ;
        (__VLS_ctx.estadoTexto(recinto.id, categoria.id, recinto.equiposRequeridos));
        // @ts-ignore
        [authStore, cerrarSesion, nombreProyectoActivo, recintos, categorias, dotacion, equiposPorPersonal, estadoBadge, estadoTexto,];
    }
    // @ts-ignore
    [];
}
__VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)({
    ...{ class: "acciones-finales" },
});
/** @type {__VLS_StyleScopedClasses['acciones-finales']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
    ...{ onClick: (...[$event]) => {
            __VLS_ctx.router.push(`/resultados/${__VLS_ctx.proyectoId}`);
            // @ts-ignore
            [router, proyectoId,];
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
    ...{ onClick: (__VLS_ctx.guardarYContinuar) },
    ...{ class: "btn-principal" },
});
/** @type {__VLS_StyleScopedClasses['btn-principal']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
    ...{ class: "fa-solid fa-arrow-right" },
});
/** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
/** @type {__VLS_StyleScopedClasses['fa-arrow-right']} */ ;
// @ts-ignore
[guardarYContinuar,];
var __VLS_3;
// @ts-ignore
[];
const __VLS_export = (await import('vue')).defineComponent({});
export default {};
