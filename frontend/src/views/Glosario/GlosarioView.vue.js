import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import AppLayout from '@/layouts/AppLayout.vue';
const router = useRouter();
const authStore = useAuthStore();
const busqueda = ref('');
const categorias = ['General', 'Tipos de equipamiento', 'Atención cerrada', 'Atención abierta'];
const terminos = [
    // GENERALES
    { categoria: 'General', termino: 'EPH', definicion: 'Estudio de Preinversión Hospitalaria. Desarrollo sistematizado y en profundidad de la detección y solución de las brechas y objetivos de gestión de un establecimiento de salud en su contexto de red, que define el tamaño nominal del proyecto y permite comparar alternativas de solución hasta seleccionar la más rentable técnica, económica y socialmente, para postulación a financiamiento.' },
    { categoria: 'General', termino: 'SIGEM-UV', definicion: 'Sistema de Información y Gestión de Equipos Médicos de la Universidad de Valparaíso, plataforma institucional en la cual se aloja el módulo EPHDEM.' },
    { categoria: 'General', termino: 'EPHDEM', definicion: 'Módulo de demanda de la plataforma SIGEM-UV destinado al cálculo del equipamiento médico requerido en un Estudio de Preinversión Hospitalaria (EPH), en función de la demanda de prestaciones y su relación con los equipos y recintos asociados.' },
    { categoria: 'General', termino: 'Arancel MAI', definicion: 'Arancel de la Modalidad de Atención Institucional de FONASA, que codifica y valoriza las prestaciones del sistema público de salud.' },
    { categoria: 'General', termino: 'Demanda', definicion: 'Número de prestaciones que una población determinada requiere, social y técnicamente, en un período establecido (generalmente considerado como 1 año en el marco de este proyecto).' },
    { categoria: 'General', termino: 'Demanda compartida', definicion: 'Equipamiento cuya demanda proviene de prestaciones de más de un recinto base y cuya cantidad entera, por tanto, no puede repartirse entre recintos.' },
    { categoria: 'General', termino: 'Disponibilidad', definicion: 'Proporción del tiempo en que una unidad de equipamiento o de recinto está efectivamente disponible para uso. Se expresa como decimal en el rango [0,1].' },
    { categoria: 'General', termino: 'EEMM', definicion: 'Equipos y equipamiento médico. Designa también la fórmula de estimación de su demanda empleada en este trabajo.' },
    { categoria: 'General', termino: 'Jornada efectiva', definicion: 'Horas diarias de funcionamiento efectivo consideradas para el cálculo de capacidad.' },
    { categoria: 'General', termino: 'Prestación', definicion: 'Cualquier atención, servicio o procedimiento médico que recibe una persona para cuidar su bienestar (como consultas, exámenes y cirugías). Está identificada por un código del arancel, con un tiempo de procedimiento y un recinto asociados.' },
    { categoria: 'General', termino: 'Producción Sanitaria', definicion: 'Número de prestaciones realizadas por un establecimiento asistencial en un período de tiempo determinado.' },
    { categoria: 'General', termino: 'Requerimiento de Equipamiento', definicion: 'Cantidad de unidades de un equipo determinado que resulta necesaria para satisfacer la demanda proyectada de una prestación.' },
    { categoria: 'General', termino: 'Tiempo de procedimiento (TP)', definicion: 'Duración en minutos de una prestación.' },
    // TIPOS DE EQUIPAMIENTO
    { categoria: 'Tipos de equipamiento', termino: 'Tipo 1', definicion: 'Un equipo por unidad o servicio hospitalario, independiente de la demanda.' },
    { categoria: 'Tipos de equipamiento', termino: 'Tipo 2', definicion: 'Un equipo por módulo de atención (box, cama, sala).' },
    { categoria: 'Tipos de equipamiento', termino: 'Tipo 3', definicion: 'Al menos 1 equipo por unidad o servicio hospitalario; cifras superiores dependen de la demanda.' },
    { categoria: 'Tipos de equipamiento', termino: 'Tipo 4', definicion: 'Al menos 1 equipo por módulo de atención; cifras superiores dependen de la demanda.' },
    { categoria: 'Tipos de equipamiento', termino: 'Tipo 5', definicion: 'La existencia depende de una evaluación de oferta/demanda. Para equipos de alto costo de adquisición, operación y mantención.' },
    { categoria: 'Tipos de equipamiento', termino: 'Tipo 6', definicion: 'Dimensionamiento según guía o norma técnica correspondiente.' },
    // ATENCIÓN CERRADA
    { categoria: 'Atención cerrada', termino: 'Atención Cerrada', definicion: 'Modalidad de atención de salud que requiere la hospitalización del paciente para recibir y administrar las prestaciones necesarias.' },
    { categoria: 'Atención cerrada', termino: 'Boxes / cubículos', definicion: 'Módulos de atención individuales de las Unidades de Paciente Crítico.' },
    { categoria: 'Atención cerrada', termino: 'Día Cama', definicion: 'Unidad de medida que corresponde a la disponibilidad y ocupación de una cama censable hospitalaria durante 24 horas para la atención de un paciente.' },
    { categoria: 'Atención cerrada', termino: 'Kit de Recinto', definicion: 'Conjunto de equipos y su cantidad base asociados por defecto a un recinto estándar que todo recinto de un tipo dado posee, con independencia de la demanda específica o del equipamiento que pueda requerir cada prestación particular.' },
    { categoria: 'Atención cerrada', termino: 'Pabellón mayor / menor', definicion: 'Recintos quirúrgicos diferenciados por complejidad del procedimiento y tipo de anestesia.' },
    { categoria: 'Atención cerrada', termino: 'Pabellón Quirúrgico', definicion: 'Recinto destinado a la realización de intervenciones quirúrgicas.' },
    { categoria: 'Atención cerrada', termino: 'Piso normativo', definicion: 'Cantidad mínima de un equipo garantizada por criterios de dotación fija, con independencia de la demanda calculada.' },
    { categoria: 'Atención cerrada', termino: 'Recinto base', definicion: 'Recinto donde se ejecuta una prestación y del cual esta hereda el kit de equipamiento correspondiente.' },
    { categoria: 'Atención cerrada', termino: 'Recinto Estándar', definicion: 'Catálogo de tipos de recintos hospitalarios considerados por el sistema (por ejemplo, cubículo UCI, cubículo UTI o pabellón), a los cuales se asocia un kit de equipamiento base.' },
    { categoria: 'Atención cerrada', termino: 'UCI / UTI', definicion: 'Unidad de Cuidados Intensivos y Unidad de Tratamiento Intermedio, respectivamente. Son los componentes que conforman la Unidad de Paciente Crítico.' },
    { categoria: 'Atención cerrada', termino: 'UPC', definicion: 'Unidad de Paciente Crítico. Unidad clínica destinada a la atención de pacientes en riesgo vital, que comprende las Unidades de Cuidados Intensivos (UCI) y de Tratamiento Intermedio (UTI).' },
    { categoria: 'Atención cerrada', termino: 'URPA', definicion: 'Unidad de Recuperación Post-Anestésica.' },
    // ATENCIÓN ABIERTA
    { categoria: 'Atención abierta', termino: 'Atención Abierta', definicion: 'Modalidad de atención de salud que no requiere la hospitalización del paciente para recibir y administrar las prestaciones necesarias.' },
    { categoria: 'Atención abierta', termino: 'Cartera de servicios', definicion: 'Definición del conjunto de acciones preventivas, curativas, de rehabilitación y cuidados paliativos que oferta un determinado establecimiento.' },
    { categoria: 'Atención abierta', termino: 'Consulta médica', definicion: 'Prestación ambulatoria donde un profesional de salud evalúa, diagnostica o trata a un paciente sin requerir hospitalización.' },
    { categoria: 'Atención abierta', termino: 'Días laborales', definicion: 'Número de días efectivos de funcionamiento del establecimiento en un año. Puede ser 260 días hábiles o 365 días corridos.' },
    { categoria: 'Atención abierta', termino: 'N° de prestaciones simultáneas', definicion: 'Cantidad de procedimientos de un mismo tipo que pueden realizarse en paralelo con el mismo equipo.' },
    { categoria: 'Atención abierta', termino: 'Tiempo de procedimiento', definicion: 'Duración estimada en minutos de una prestación ambulatoria, utilizada en la fórmula EEMM.' },
];
const terminosFiltrados = computed(() => {
    const texto = busqueda.value.toLowerCase().trim();
    if (!texto)
        return terminos;
    return terminos.filter(t => t.termino.toLowerCase().includes(texto) ||
        t.definicion.toLowerCase().includes(texto));
});
function terminosPorCategoria(cat) {
    return terminosFiltrados.value.filter(t => t.categoria === cat);
}
function cerrarSesion() {
    authStore.logout();
    router.push('/login');
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
    ...{ class: "glosario-page" },
});
/** @type {__VLS_StyleScopedClasses['glosario-page']} */ ;
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
    ...{ class: "glosario-content" },
});
/** @type {__VLS_StyleScopedClasses['glosario-content']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.header, __VLS_intrinsics.header)({
    ...{ class: "glosario-header" },
});
/** @type {__VLS_StyleScopedClasses['glosario-header']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "nav-bar" },
});
/** @type {__VLS_StyleScopedClasses['nav-bar']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "nav-buttons" },
});
/** @type {__VLS_StyleScopedClasses['nav-buttons']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
    ...{ onClick: (...[$event]) => {
            __VLS_ctx.router.back();
            // @ts-ignore
            [router,];
        } },
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
            [router,];
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
    ...{ class: "filtro-buscar" },
});
/** @type {__VLS_StyleScopedClasses['filtro-buscar']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.input)({
    value: (__VLS_ctx.busqueda),
    type: "text",
    placeholder: "Buscar término...",
});
__VLS_asFunctionalElement1(__VLS_intrinsics.section, __VLS_intrinsics.section)({
    ...{ class: "terminos-panel" },
});
/** @type {__VLS_StyleScopedClasses['terminos-panel']} */ ;
for (const [categoria] of __VLS_vFor((__VLS_ctx.categorias))) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        key: (categoria),
    });
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "categoria-titulo" },
    });
    /** @type {__VLS_StyleScopedClasses['categoria-titulo']} */ ;
    (categoria);
    if (__VLS_ctx.terminosPorCategoria(categoria).length === 0) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "lista-vacia" },
        });
        /** @type {__VLS_StyleScopedClasses['lista-vacia']} */ ;
    }
    for (const [termino] of __VLS_vFor((__VLS_ctx.terminosPorCategoria(categoria)))) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            key: (termino.termino),
            ...{ class: "termino-card" },
        });
        /** @type {__VLS_StyleScopedClasses['termino-card']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "termino-titulo" },
        });
        /** @type {__VLS_StyleScopedClasses['termino-titulo']} */ ;
        (termino.termino);
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "termino-definicion" },
        });
        /** @type {__VLS_StyleScopedClasses['termino-definicion']} */ ;
        (termino.definicion);
        // @ts-ignore
        [authStore, cerrarSesion, busqueda, categorias, terminosPorCategoria, terminosPorCategoria,];
    }
    // @ts-ignore
    [];
}
if (__VLS_ctx.terminosFiltrados.length === 0) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "lista-vacia" },
    });
    /** @type {__VLS_StyleScopedClasses['lista-vacia']} */ ;
}
// @ts-ignore
[terminosFiltrados,];
var __VLS_3;
// @ts-ignore
[];
const __VLS_export = (await import('vue')).defineComponent({});
export default {};
