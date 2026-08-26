# EPHDEM — Estudio de Preinversión Hospitalaria

Módulo web para la estimación de equipamiento médico necesario para satisfacer una demanda proyectada de prestaciones de salud hospitalaria (atención cerrada). Desarrollado en el marco del ecosistema SIGEM-UV de la Universidad de Valparaíso.

---

## Stack Tecnológico

| Capa | Tecnología |
|---|---|
| Frontend | Vue 3 + TypeScript + Vite |
| Estado global | Pinia |
| Enrutamiento | Vue Router (hash history) |
| Estilos | SCSS + Bootstrap 5 |
| Backend | PHP 8 (organizado en Controllers y Services) |
| Base de datos | MySQL (SIGEM-UV) |

---

## Estructura del Repositorio
eph_dem/
├── src/ ← Frontend Vue 3
│ ├── layouts/ ← AppLayout.vue (topbar + footer compartido)
│ ├── components/ ← Componentes reutilizables
│ ├── views/ ← Vistas por módulo
│ │ ├── Inicio/
│ │ ├── Login/
│ │ ├── Manual/
│ │ ├── Proyectos/
│ │ ├── CreacionProyecto/
│ │ ├── Prestaciones/
│ │ ├── Parametros/
│ │ └── Resultados/
│ ├── stores/ ← Pinia (auth)
│ └── router/ ← Rutas protegidas
│
├── ajax/ ← Backend PHP
│ ├── controllers/ ← Lógica de cada endpoint
│ │ ├── AuthController.php
│ │ ├── ProyectoController.php
│ │ ├── PrestacionController.php
│ │ └── DemandaController.php
│ ├── services/ ← Lógica de cálculo de equipamiento
│ │ ├── PabellonesBoxesService.php
│ │ ├── EquipamientoKitService.php
│ │ ├── EquipamientoTipo5Service.php
│ │ ├── EquipamientoTipo6Service.php
│ │ ├── EquipamientoAgregadorService.php
│ │ ├── EquipamientoVistasService.php
│ │ └── UrpaService.php
│ ├── helpers/ ← Utilidades compartidas
│ │ └── Response.php
│ ├── login.php ← Puntos de entrada (endpoints)
│ ├── get_proyectos.php
│ ├── crear_proyecto.php
│ ├── get_prestaciones.php
│ ├── get_prestaciones_demanda.php
│ ├── calcular_demanda.php
│ ├── obtener_resultados_proyecto.php
│ ├── generar_xls_cerrada.php
│ └── generar_pdf_cerrada.php
│
├── produccion/ ← Copia de referencia del código en producción
├── eph_dem_backend/ ← Backend (PHP puro en ajax/)
└── README.md


---

## Flujo del Usuario

1. **Login** → autenticación con cuenta SIGEM-UV
2. **Crear proyecto** → nombre del estudio
3. **Prestaciones** → selección de prestaciones FONASA
4. **Parámetros** → demanda, disponibilidad, jornada, tiempo de procedimiento
5. **Resultados** → equipamiento calculado por recinto + exportación Excel/PDF
6. **Dotación RRHH** *(en desarrollo)* → restricción de equipamiento según personal disponible
7. **Equipos de Oportunidad** *(en desarrollo)* → equipos adicionales no estándar

---

## Instalación Frontend

```bash
npm install
npm run dev
```

Variables de entorno necesarias (`.env.development`):
VITE_API_BASE=/ajax


---

## Arquitectura Backend PHP

El backend sigue el patrón **Controller → Service**:

- Los **Controllers** reciben la petición HTTP, validan los datos y devuelven la respuesta JSON
- Los **Services** contienen la lógica de negocio (cálculo de equipamiento)
- El **helper Response** estandariza todas las respuestas JSON

Los archivos PHP en la raíz de `ajax/` son puntos de entrada que delegan al controller correspondiente.

---

## Ramas

| Rama | Contenido |
|---|---|
| `main` | Código estable y funcional |
| `develop` | Rama de desarrollo |

---

## Autores

**Dafnne Vásquez Villalón**  
Proyecto de Título(PIB) — Universidad de Valparaíso  
Escuela de Ingeniería Biomédica · SIGEM-UV · 2026