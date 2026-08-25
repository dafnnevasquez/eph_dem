import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { useAuthStore } from '@/stores/auth';
import AppLayout from '@/layouts/AppLayout.vue';
const router = useRouter();
const authStore = useAuthStore();
const formulario = ref({ email: '', password: '' });
const errores = ref({ email: '', password: '' });
const errorGeneral = ref('');
const cargando = ref(false);
const mostrarPassword = ref(false);
const loginExitoso = ref(false);
function validar() {
    errores.value = { email: '', password: '' };
    let valido = true;
    if (!formulario.value.email.trim()) {
        errores.value.email = 'El correo es obligatorio.';
        valido = false;
    }
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formulario.value.email)) {
        errores.value.email = 'Ingresa un correo válido.';
        valido = false;
    }
    if (!formulario.value.password) {
        errores.value.password = 'La contraseña es obligatoria.';
        valido = false;
    }
    else if (formulario.value.password.length < 4) {
        errores.value.password = 'La contraseña debe tener al menos 4 caracteres.';
        valido = false;
    }
    return valido;
}
async function iniciarSesion() {
    errorGeneral.value = '';
    if (!validar())
        return;
    cargando.value = true;
    try {
        await authStore.login(formulario.value.email, formulario.value.password);
        loginExitoso.value = true;
        setTimeout(() => { router.push('/inicio'); }, 1800);
    }
    catch (error) {
        errorGeneral.value = error instanceof Error ? error.message : 'Error de conexión. Inténtalo nuevamente.';
    }
    finally {
        cargando.value = false;
    }
}
const __VLS_ctx = {
    ...{},
    ...{},
};
let __VLS_components;
let __VLS_intrinsics;
let __VLS_directives;
/** @type {__VLS_StyleScopedClasses['form-input']} */ ;
/** @type {__VLS_StyleScopedClasses['login-card']} */ ;
/** @type {__VLS_StyleScopedClasses['login-content']} */ ;
const __VLS_0 = AppLayout || AppLayout;
// @ts-ignore
const __VLS_1 = __VLS_asFunctionalComponent1(__VLS_0, new __VLS_0({}));
const __VLS_2 = __VLS_1({}, ...__VLS_functionalComponentArgsRest(__VLS_1));
var __VLS_5 = {};
const { default: __VLS_6 } = __VLS_3.slots;
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
    ...{ class: "login-content" },
});
/** @type {__VLS_StyleScopedClasses['login-content']} */ ;
__VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
    ...{ class: "login-card" },
});
/** @type {__VLS_StyleScopedClasses['login-card']} */ ;
let __VLS_7;
/** @ts-ignore @type { | typeof __VLS_components.transition | typeof __VLS_components.Transition | typeof __VLS_components.transition | typeof __VLS_components.Transition} */
transition;
// @ts-ignore
const __VLS_8 = __VLS_asFunctionalComponent1(__VLS_7, new __VLS_7({
    name: "card-fade",
    mode: "out-in",
}));
const __VLS_9 = __VLS_8({
    name: "card-fade",
    mode: "out-in",
}, ...__VLS_functionalComponentArgsRest(__VLS_8));
const { default: __VLS_12 } = __VLS_10.slots;
if (__VLS_ctx.loginExitoso) {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "login-success" },
        key: "success",
    });
    /** @type {__VLS_StyleScopedClasses['login-success']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "success-icon" },
    });
    /** @type {__VLS_StyleScopedClasses['success-icon']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
        ...{ class: "fa-solid fa-circle-check" },
    });
    /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
    /** @type {__VLS_StyleScopedClasses['fa-circle-check']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({
        ...{ class: "success-msg" },
    });
    /** @type {__VLS_StyleScopedClasses['success-msg']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({
        ...{ class: "success-sub" },
    });
    /** @type {__VLS_StyleScopedClasses['success-sub']} */ ;
    (__VLS_ctx.formulario.email);
}
else {
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        key: "form",
    });
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "login-card-header" },
    });
    /** @type {__VLS_StyleScopedClasses['login-card-header']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "login-icon" },
    });
    /** @type {__VLS_StyleScopedClasses['login-icon']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
        ...{ class: "fa-solid fa-user-lock" },
    });
    /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
    /** @type {__VLS_StyleScopedClasses['fa-user-lock']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.h2, __VLS_intrinsics.h2)({
        ...{ class: "login-title" },
    });
    /** @type {__VLS_StyleScopedClasses['login-title']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.p, __VLS_intrinsics.p)({
        ...{ class: "login-subtitle" },
    });
    /** @type {__VLS_StyleScopedClasses['login-subtitle']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.form, __VLS_intrinsics.form)({
        ...{ onSubmit: (__VLS_ctx.iniciarSesion) },
        ...{ class: "login-form" },
    });
    /** @type {__VLS_StyleScopedClasses['login-form']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "form-group" },
    });
    /** @type {__VLS_StyleScopedClasses['form-group']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)({
        for: "login-email",
        ...{ class: "form-label" },
    });
    /** @type {__VLS_StyleScopedClasses['form-label']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
        ...{ class: "fa-solid fa-envelope" },
    });
    /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
    /** @type {__VLS_StyleScopedClasses['fa-envelope']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.input)({
        id: "login-email",
        type: "email",
        ...{ class: "form-input" },
        ...{ class: ({ 'form-input--error': __VLS_ctx.errores.email }) },
        placeholder: "correo@sigem-uv.cl",
        autocomplete: "email",
        required: true,
    });
    (__VLS_ctx.formulario.email);
    /** @type {__VLS_StyleScopedClasses['form-input']} */ ;
    /** @type {__VLS_StyleScopedClasses['form-input--error']} */ ;
    if (__VLS_ctx.errores.email) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
            ...{ class: "form-error" },
        });
        /** @type {__VLS_StyleScopedClasses['form-error']} */ ;
        (__VLS_ctx.errores.email);
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "form-group" },
    });
    /** @type {__VLS_StyleScopedClasses['form-group']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.label, __VLS_intrinsics.label)({
        for: "login-password",
        ...{ class: "form-label" },
    });
    /** @type {__VLS_StyleScopedClasses['form-label']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
        ...{ class: "fa-solid fa-lock" },
    });
    /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
    /** @type {__VLS_StyleScopedClasses['fa-lock']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "input-password-wrapper" },
    });
    /** @type {__VLS_StyleScopedClasses['input-password-wrapper']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.input)({
        id: "login-password",
        type: (__VLS_ctx.mostrarPassword ? 'text' : 'password'),
        ...{ class: "form-input" },
        ...{ class: ({ 'form-input--error': __VLS_ctx.errores.password }) },
        placeholder: "••••••••",
        autocomplete: "current-password",
        required: true,
    });
    (__VLS_ctx.formulario.password);
    /** @type {__VLS_StyleScopedClasses['form-input']} */ ;
    /** @type {__VLS_StyleScopedClasses['form-input--error']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
        ...{ onClick: (...[$event]) => {
                if (!!(__VLS_ctx.loginExitoso))
                    return;
                __VLS_ctx.mostrarPassword = !__VLS_ctx.mostrarPassword;
                // @ts-ignore
                [loginExitoso, formulario, formulario, formulario, iniciarSesion, errores, errores, errores, errores, mostrarPassword, mostrarPassword, mostrarPassword,];
            } },
        type: "button",
        ...{ class: "btn-toggle-password" },
        title: (__VLS_ctx.mostrarPassword ? 'Ocultar contraseña' : 'Mostrar contraseña'),
    });
    /** @type {__VLS_StyleScopedClasses['btn-toggle-password']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
        ...{ class: (__VLS_ctx.mostrarPassword ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye') },
    });
    if (__VLS_ctx.errores.password) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
            ...{ class: "form-error" },
        });
        /** @type {__VLS_StyleScopedClasses['form-error']} */ ;
        (__VLS_ctx.errores.password);
    }
    if (__VLS_ctx.errorGeneral) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
            ...{ class: "login-error-general" },
        });
        /** @type {__VLS_StyleScopedClasses['login-error-general']} */ ;
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
            ...{ class: "fa-solid fa-circle-exclamation" },
        });
        /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
        /** @type {__VLS_StyleScopedClasses['fa-circle-exclamation']} */ ;
        (__VLS_ctx.errorGeneral);
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "login-acciones" },
    });
    /** @type {__VLS_StyleScopedClasses['login-acciones']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.button, __VLS_intrinsics.button)({
        type: "submit",
        ...{ class: "btn-ingresar" },
        disabled: (__VLS_ctx.cargando),
    });
    /** @type {__VLS_StyleScopedClasses['btn-ingresar']} */ ;
    if (__VLS_ctx.cargando) {
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
            ...{ class: "fa-solid fa-spinner fa-spin" },
        });
        /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
        /** @type {__VLS_StyleScopedClasses['fa-spinner']} */ ;
        /** @type {__VLS_StyleScopedClasses['fa-spin']} */ ;
    }
    else {
        __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({});
        __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
            ...{ class: "fa-solid fa-arrow-right" },
        });
        /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
        /** @type {__VLS_StyleScopedClasses['fa-arrow-right']} */ ;
    }
    __VLS_asFunctionalElement1(__VLS_intrinsics.div, __VLS_intrinsics.div)({
        ...{ class: "login-links" },
    });
    /** @type {__VLS_StyleScopedClasses['login-links']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.a, __VLS_intrinsics.a)({
        href: "https://www.sigem-uv.cl/__v2/admin_sigem/sigem_recuperar.php",
        target: "_blank",
        ...{ class: "login-link" },
    });
    /** @type {__VLS_StyleScopedClasses['login-link']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
        ...{ class: "fa-solid fa-key" },
    });
    /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
    /** @type {__VLS_StyleScopedClasses['fa-key']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.span, __VLS_intrinsics.span)({
        ...{ class: "login-link-sep" },
    });
    /** @type {__VLS_StyleScopedClasses['login-link-sep']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.a, __VLS_intrinsics.a)({
        href: "https://www.sigem-uv.cl/__v2/admin_sigem/creacion_cuenta.php",
        target: "_blank",
        ...{ class: "login-link" },
    });
    /** @type {__VLS_StyleScopedClasses['login-link']} */ ;
    __VLS_asFunctionalElement1(__VLS_intrinsics.i, __VLS_intrinsics.i)({
        ...{ class: "fa-solid fa-user-plus" },
    });
    /** @type {__VLS_StyleScopedClasses['fa-solid']} */ ;
    /** @type {__VLS_StyleScopedClasses['fa-user-plus']} */ ;
}
// @ts-ignore
[errores, errores, mostrarPassword, mostrarPassword, errorGeneral, errorGeneral, cargando, cargando,];
var __VLS_10;
// @ts-ignore
[];
var __VLS_3;
// @ts-ignore
[];
const __VLS_export = (await import('vue')).defineComponent({});
export default {};
