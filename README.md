# Gestor de Proyectos — Backend

Sistema web para la gestión inteligente de proyectos de software. Permite registrar insumos iniciales del proyecto, organizarlos y transformarlos en artefactos estructurados de gestión: requerimientos, historias de usuario, tareas y planeación.

---

## Objetivo general

Proveer una plataforma backend que centralice la información inicial de un proyecto de software y facilite su transformación en elementos accionables de gestión, con una estructura clara, trazable y escalable.

---

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Framework backend | Laravel 13 |
| Lenguaje | PHP 8.3 |
| Vistas | Blade |
| Base de datos | SQLite (desarrollo) |
| Estilos | Tailwind CSS |
| Frontend build | Vite |
| Autenticación | Laravel Breeze |
| Landing | React 19 + TypeScript + Vite 7 + Tailwind 4 |

---

## Estructura del repositorio

| Ruta | Contenido |
|---|---|
| `app/`, `routes/`, `resources/`, `public/` | Aplicación Laravel |
| `landing/` | Landing page en React, con su propio `package.json` y build |
| `Docs/` | Contexto local del proyecto; no se versiona |

La app y la landing tienen builds independientes: la raíz usa Vite 8 y la landing Vite 7.

---

## Módulos V1

- **Autenticación** — Registro, login, recuperación de contraseña y gestión de perfil con Laravel Breeze.
- **Proyectos** — Creación y gestión de proyectos vinculados a un usuario.
- **Insumos del proyecto** — Registro de entradas de información clasificadas por tipo.
- **Requerimientos** — Requerimientos funcionales y no funcionales asociados a cada proyecto.
- **Historias de usuario** — Historias estructuradas derivadas de los requerimientos.
- **Estados de tarea** — Catálogo de estados para el seguimiento de tareas.
- **Tareas** — Tareas vinculadas a proyectos, estados e historias de usuario.

---

## Instalación local

### Requisitos previos

- PHP 8.3+
- Composer
- Node.js 20+
- npm

### Pasos

```bash
# 1. Clonar el repositorio
git clone <url-del-repositorio>
cd gestor-proyectos-backend

# 2. Instalar dependencias PHP
composer install

# 3. Instalar dependencias JS
npm install

# 4. Copiar el archivo de entorno
cp .env.example .env

# 5. Generar la clave de la aplicación
php artisan key:generate

# 6. Crear la base de datos SQLite
touch database/database.sqlite
```

---

## Migraciones

```bash
# Ejecutar todas las migraciones
php artisan migrate

# Reiniciar migraciones desde cero
php artisan migrate:fresh
```

---

## Levantar el proyecto

```bash
# Compilar assets
npm run build

# Levantar el servidor de desarrollo
php artisan serve
```

El sistema quedará disponible en `http://localhost:8000`.

Para desarrollo con hot-reload:

```bash
npm run dev
```

### Landing page

La landing vive en `landing/` y se instala y ejecuta aparte:

```bash
# Instalar dependencias de la landing
npm run landing:install

# Desarrollo con hot-reload
npm run landing:dev

# Build de producción (sale en landing/dist)
npm run landing:build
```

---

## Roadmap — Siguientes módulos

- Generación automática de requerimientos a partir de insumos del proyecto
- Generación automática de historias de usuario desde requerimientos
- Generación de plan de tareas estructurado
- Panel de seguimiento por proyecto
- Exportación de artefactos en formato estructurado

---

## Licencia

Proyecto académico. Todos los derechos reservados.
