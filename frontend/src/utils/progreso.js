// Barra de progreso global: se enciende sola al cambiar de pantalla
// y mientras haya peticiones al servidor (fetch) pendientes.
// No es un porcentaje real (el navegador no sabe cuánto tarda el servidor):
// avanza rápido al principio, se frena cerca del 90 % y se completa al terminar.
import { reactive } from 'vue'

export const progreso = reactive({ visible: false, ancho: 0 })

let pendientes = 0          // peticiones y navegaciones en curso
let navegando = false       // hay un cambio de pantalla en curso
let tGoteo = null           // intervalo que hace avanzar la barra
let tMostrar = null         // espera antes de mostrar (evita parpadeos en respuestas rápidas)
let tCompletar = null       // espera antes de completar (por si enseguida parte otra petición)
let tOcultar = null
let tSeguridad = null       // si algo queda colgado, se apaga sola

const RETARDO_MOSTRAR = 150   // ms
const RETARDO_COMPLETAR = 120 // ms
const MAXIMO = 45000          // ms

function avanzar() {
  progreso.ancho += (90 - progreso.ancho) * 0.08
}

function arrancar() {
  clearTimeout(tCompletar)
  clearTimeout(tOcultar)
  pendientes++
  if (pendientes > 1) return
  clearTimeout(tSeguridad)
  tSeguridad = setTimeout(reiniciar, MAXIMO)
  if (progreso.visible) return // ya se está mostrando: sigue avanzando
  tMostrar = setTimeout(() => {
    progreso.ancho = 8
    progreso.visible = true
    clearInterval(tGoteo)
    tGoteo = setInterval(avanzar, 200)
  }, RETARDO_MOSTRAR)
}

function terminar() {
  if (pendientes === 0) return
  pendientes--
  if (pendientes > 0) return
  tCompletar = setTimeout(completar, RETARDO_COMPLETAR)
}

function completar() {
  clearTimeout(tMostrar)
  clearInterval(tGoteo)
  clearTimeout(tSeguridad)
  if (!progreso.visible) return
  progreso.ancho = 100
  tOcultar = setTimeout(() => {
    progreso.visible = false
    progreso.ancho = 0
  }, 350)
}

function reiniciar() {
  pendientes = 0
  navegando = false
  completar()
}

let instalado = false

// Se llama una vez (desde AppLayout). Envuelve fetch y engancha el router.
export function instalarProgreso(router) {
  if (instalado) return
  instalado = true

  const fetchOriginal = window.fetch.bind(window)
  window.fetch = (...args) => {
    arrancar()
    return fetchOriginal(...args).finally(terminar)
  }

  if (router) {
    router.beforeEach(() => {
      if (!navegando) { navegando = true; arrancar() }
    })
    router.afterEach(() => {
      if (navegando) { navegando = false; terminar() }
    })
    router.onError(() => {
      if (navegando) { navegando = false; terminar() }
    })
  }
}