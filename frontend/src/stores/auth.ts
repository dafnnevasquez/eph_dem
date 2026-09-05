import { defineStore } from 'pinia'
import { ref, computed } from 'vue'

const STORAGE_KEY = 'ephdem_sesion'
const API_BASE = import.meta.env.VITE_API_BASE

export interface UsuarioSesion {
  id_usuario: number
  nombre: string
  correo: string
}

export const useAuthStore = defineStore('auth', () => {
  // --- Estado ---
  const usuario = ref<UsuarioSesion | null>(cargarSesionGuardada())

  // --- Getters ---
  const estaAutenticado = computed(() => usuario.value !== null)
  const usuarioId = computed(() => usuario.value?.id_usuario ?? null)
  const nombreUsuario = computed(() => usuario.value?.nombre ?? '')
  const correoUsuario = computed(() => usuario.value?.correo ?? '')

  // --- Acciones ---
  async function login(correo: string, contrasena: string): Promise<void> {
    const url = `${API_BASE}/login.php`
    const response = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ correo, contrasena }),
    })

    const data = await response.json()

    if (!data.ok) {
      throw new Error(data.error ?? 'Credenciales incorrectas.')
    }

    const sesion: UsuarioSesion = {
      id_usuario: data.datos.id_usuario,
      nombre: data.datos.nombre,
      correo: data.datos.correo,
    }

    usuario.value = sesion
    localStorage.setItem(STORAGE_KEY, JSON.stringify(sesion))
  }

  function logout(): void {
    usuario.value = null
    localStorage.removeItem(STORAGE_KEY)
    // Limpiar datos de sesión del proyecto activo también
    localStorage.removeItem('ephdem_proyecto_activo')
    localStorage.removeItem('ephdem_proyectos')
    localStorage.removeItem('ephdem_prestaciones_seleccionadas')
    localStorage.removeItem('ephdem_parametros_prestaciones')
  }

  // --- Helpers privados ---
  function cargarSesionGuardada(): UsuarioSesion | null {
    try {
      const raw = localStorage.getItem(STORAGE_KEY)
      if (!raw) return null
      const parsed = JSON.parse(raw)
      if (parsed?.id_usuario && parsed?.correo) return parsed as UsuarioSesion
      return null
    } catch {
      return null
    }
  }

  return {
    usuario,
    estaAutenticado,
    usuarioId,
    nombreUsuario,
    correoUsuario,
    login,
    logout,
  }
})
