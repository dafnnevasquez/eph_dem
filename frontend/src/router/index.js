import { createRouter, createWebHashHistory } from 'vue-router';
import InicioView from '@/views/Inicio/InicioView.vue';
const router = createRouter({
    history: createWebHashHistory(),
    routes: [
        {
            path: '/',
            redirect: '/login',
        },
        {
            path: '/inicio',
            name: 'inicio',
            component: InicioView,
        },
        {
            path: '/login',
            name: 'login',
            component: () => import('@/views/Login/LoginView.vue'),
        },
        {
            path: '/proyectos',
            name: 'proyectos',
            component: () => import('@/views/Proyectos/ProyectoView.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/crear-proyecto',
            name: 'crear-proyecto',
            component: () => import('@/views/CreacionProyecto/CreacionProyectoView.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/prestaciones/:proyectoId?',
            name: 'prestaciones',
            component: () => import('@/views/Prestaciones/PrestacionesView.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/parametros/:proyectoId?',
            name: 'parametros',
            component: () => import('@/views/Parametros/ParametrosView.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/resultados/:proyectoId?',
            name: 'resultados',
            component: () => import('@/views/Resultados/ResultadosView.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/rrhh/:proyectoId?',
            name: 'rrhh',
            component: () => import('@/views/RRHH/RRHHView.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/equipos-oportunidad/:proyectoId?',
            name: 'equipos-oportunidad',
            component: () => import('@/views/EquiposOportunidad/EquiposOportunidadView.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/crear-proyecto-abierta',
            name: 'crear-proyecto-abierta',
            component: () => import('@/views/CreacionProyecto/CreacionProyectoAbiertaView.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/prestaciones-abierta/:proyectoId?',
            name: 'prestaciones-abierta',
            component: () => import('@/views/Prestaciones/PrestacionesAbiertaView.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/parametros-abierta/:proyectoId?',
            name: 'parametros-abierta',
            component: () => import('@/views/Parametros/ParametrosAbiertaView.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/resultados-abierta/:proyectoId?',
            name: 'resultados-abierta',
            component: () => import('@/views/Resultados/ResultadosAbiertaView.vue'),
            meta: { requiresAuth: true },
        },
        {
            path: '/manual',
            name: 'manual',
            component: () => import('@/views/Manual/ManualView.vue'),
            meta: { requiresAuth: true },
        },
        {
            // Redirige cualquier ruta desconocida a /login
            path: '/:pathMatch(.*)*',
            redirect: '/login',
        },
    ],
});
router.beforeEach((to) => {
    if (!to.meta.requiresAuth)
        return true;
    try {
        const raw = localStorage.getItem('ephdem_sesion');
        console.log('Guard - ephdem_sesion:', raw); // temporal
        if (raw) {
            const sesion = JSON.parse(raw);
            if (sesion?.id_usuario && sesion?.correo)
                return true;
        }
    }
    catch {
        // sesion corrupta
    }
    return { name: 'login' };
});
export default router;
