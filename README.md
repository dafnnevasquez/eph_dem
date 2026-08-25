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
| Backend API | Laravel 13 (PHP 8.5) |
| Base de datos | MySQL (SIGEM-UV) |
| Autenticación | Laravel Sanctum |

---

## Estructura del Repositorio
eph_dem/ ← Frontend Vue (rama main)
├── src/
│ ├── layouts/ ← AppLayout.vue (topbar + footer compartido)
│ ├── components/ ← Componentes reutilizables
│ ├── views/ ← Vistas por módulo
│ ├── stores/ ← Pinia (auth)
│ └── router/ ← Rutas protegidas
├── ajax/ ← Scripts PHP legacy (SIGEM-UV)
└── eph_dem_backend/ ← Backend Laravel (rama backend)
├── app/Http/Controllers/
├── routes/api.php
└── .env

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

## Instalación Backend

```bash
cd eph_dem_backend
composer install
cp .env.example .env
php artisan key:generate
```

Configura la conexión a MySQL en `.env`:
DB_CONNECTION=mysql
DB_HOST=
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=
PHP_LEGACY_URL=https://sigem-uv.cl/__v2/modulo_eph/ajax

---

## Ramas

| Rama | Contenido |
|---|---|
| `main` | Frontend Vue 3 |
| `backend` | Backend Laravel 13 |

---

## Autor
Dafnne Vásquez Villalón
EPH DEM: "Escalamiento del módulo"
Proyecto de Título (PIB) — Universidad de Valparaíso  
Escuela de Ingeniería Biomédica · SIGEM-UV · 2026