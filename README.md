# WhatsApp Bot — Gestión de Citas

Sistema web de gestión de citas que permite a empresas administrar sus agendamientos de manera eficiente. El sistema está diseñado para ser multi-tenant, donde cada empresa tiene su propio espacio aislado para gestionar clientes, citas y horarios de atención.

## Características

- **Multi-tenant**: cada empresa opera en su propio espacio aislado.
- **Gestión de citas**: agendar, confirmar, cancelar y marcar como completadas.
- **Gestión de clientes**: registro automático desde WhatsApp o manual vía API.
- **Horarios de atención**: configuración por día de la semana por empresa.
- **Bot de WhatsApp**: los clientes agendan y cancelan citas por WhatsApp.
- **Panel de administración web**: con soporte completo de accesibilidad (WCAG 2.1).
- **API REST**: endpoints para integraciones externas.

## Requisitos

- Node.js 18+
- npm 9+

## Instalación

```bash
# Clona el repositorio
git clone https://github.com/AdanCamacho83/WhatsApp-bot.git
cd WhatsApp-bot

# Instala dependencias
npm install

# Copia y configura variables de entorno
cp .env.example .env
# Edita .env con tus valores
```

## Ejecución

```bash
# Producción
npm start

# Desarrollo (con recarga automática, Node.js 18+)
npm run dev
```

Abre `http://localhost:3000` en tu navegador para acceder al panel de administración.

## Pruebas

```bash
npm test
```

## API REST

Base URL: `http://localhost:3000/api`

### Empresas

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/empresas` | Listar empresas |
| `POST` | `/empresas` | Crear empresa |
| `GET` | `/empresas/:id` | Obtener empresa |
| `PUT` | `/empresas/:id` | Actualizar empresa |
| `DELETE` | `/empresas/:id` | Eliminar empresa |
| `GET` | `/empresas/:id/horarios` | Ver horarios |
| `PUT` | `/empresas/:id/horarios` | Actualizar horarios |

### Clientes

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/empresas/:id/clientes` | Listar clientes |
| `POST` | `/empresas/:id/clientes` | Crear cliente |
| `GET` | `/empresas/:id/clientes/:cId` | Obtener cliente |
| `PUT` | `/empresas/:id/clientes/:cId` | Actualizar cliente |
| `DELETE` | `/empresas/:id/clientes/:cId` | Eliminar cliente |

### Citas

| Método | Ruta | Descripción |
|--------|------|-------------|
| `GET` | `/empresas/:id/citas` | Listar citas (filtros: `?date=` `?status=`) |
| `POST` | `/empresas/:id/citas` | Crear cita |
| `GET` | `/empresas/:id/citas/:aId` | Obtener cita |
| `PUT` | `/empresas/:id/citas/:aId` | Actualizar cita |
| `PATCH` | `/empresas/:id/citas/:aId/estado` | Cambiar estado |
| `DELETE` | `/empresas/:id/citas/:aId` | Eliminar cita |

## Comandos del Bot (WhatsApp)

### Clientes
| Comando | Descripción |
|---------|-------------|
| `hola` / `menú` | Ver menú principal |
| `agendar` | Iniciar flujo de agendamiento |
| `mis citas` | Ver citas pendientes |
| `cancelar cita` | Ver citas cancelables |
| `cancelar #ID` | Cancelar cita por ID |

### Empresas (número registrado)
| Comando | Descripción |
|---------|-------------|
| `citas hoy` | Ver citas del día |
| `próximas citas` | Ver próximas citas |
| `ayuda` | Ver menú de empresa |

## Estructura del Proyecto

```
WhatsApp-bot/
├── index.js              # Punto de entrada
├── src/
│   ├── app.js            # Configuración Express
│   ├── bot/
│   │   └── messageHandler.js   # Lógica del bot
│   ├── db/
│   │   ├── database.js         # Inicialización SQLite
│   │   ├── tenantRepository.js
│   │   ├── clientRepository.js
│   │   └── appointmentRepository.js
│   ├── routes/
│   │   ├── tenants.js
│   │   ├── clients.js
│   │   └── appointments.js
│   └── public/           # Panel de administración web
│       ├── index.html
│       ├── css/styles.css
│       └── js/app.js
├── tests/
│   ├── repositories.test.js
│   └── api.test.js
└── data/                 # Base de datos SQLite (generada automáticamente)
```

## Accesibilidad

El panel de administración cumple con los criterios WCAG 2.1 nivel AA:

- **Skip link** para saltar al contenido principal.
- Todos los elementos interactivos tienen tamaños de toque mínimos de 44×44 px.
- Indicadores de foco visibles en todos los elementos interactivos.
- Roles ARIA apropiados (`role="banner"`, `role="main"`, `role="navigation"`, etc.).
- Tablas con `<caption>` y atributos `scope` en encabezados.
- Formularios con etiquetas `<label>` asociadas, `aria-required`, `aria-describedby`.
- Regiones de estado con `role="status"` y `aria-live="polite"`.
- Soporte para modo de alto contraste (`forced-colors: active`).

## Licencia

ISC

