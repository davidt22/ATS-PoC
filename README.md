# ATS PoC — Recruitment

Micro Applicant Tracking System: un candidato aplica a una oferta pegando su CV como texto plano, y un reclutador explora las candidaturas recibidas. El enriquecimiento con IA (resumen + puntuación de relevancia) se procesa de forma asíncrona y está **simulado (mock)** — no se llama a ningún LLM real.

Las especificaciones completas (requisitos, diseño y desglose de tareas verificado) están en [`specs/recruitment/`](specs/recruitment/).

## Cómo levantar el proyecto

Requisitos: Docker + Docker Compose.

```bash
make init
```

Esto construye las imágenes, levanta PHP-FPM + Nginx + MySQL + Adminer, instala dependencias, crea la base de datos y ejecuta las migraciones (también las de la base de datos de test). La aplicación queda disponible en **http://localhost:8080**.

Otros comandos:

```bash
make up       # levantar contenedores ya construidos
make down     # parar y eliminar contenedores
make shell    # shell dentro del contenedor PHP
make migrate  # ejecutar migraciones pendientes
make test     # ejecutar la suite de tests
make worker   # consumir el transporte asíncrono (enriquecimiento IA)
```

Adminer (inspección de la BD) queda disponible en **http://localhost:8081** (sistema: MySQL, servidor: `database`, usuario: `app`, contraseña: `!ChangeMe!`, BD: `viterbit`).

### Procesar el enriquecimiento con IA

Al enviar una candidatura, el enriquecimiento se encola de forma asíncrona (no bloquea la respuesta). Para que se procese, el worker de Messenger debe estar corriendo:

```bash
make worker
```

o, en una terminal separada, dejarlo corriendo en bucle:

```bash
docker compose exec php php bin/console messenger:consume async -vv
```

## Cómo ejecutar los tests

```bash
make test
```

59 tests entre unitarios (dominio, casos de uso con fakes en memoria), de integración (repositorios Doctrine contra una base de datos MySQL de test real) y funcionales (`WebTestCase`, peticiones HTTP reales contra las rutas de la aplicación).

## Decisiones de arquitectura

Resumen — el detalle y el porqué de cada una está en [`specs/recruitment/design.md`](specs/recruitment/design.md):

- **DDD + Hexagonal + CQRS**: un único bounded context `Recruitment` (agregados `JobPosting` y `JobApplication`) estructurado en capas `Domain / Application / Infrastructure / UI`, más `Shared/` para código transversal (bus de comandos/queries/eventos, reloj). Comandos y queries se despachan vía Symfony Messenger en tres buses (`command.bus`, `query.bus`, `event.bus`).
- **Dominio libre de framework**: las entidades y Value Objects no dependen de Symfony ni de Doctrine. La persistencia usa mapping XML (no atributos `#[ORM\...]`) y tipos DBAL propios que convierten entre columnas y Value Objects.
- **Invariantes como Value Objects**: `FullName`, `Email`, `Phone`, `CvText`, `AiScore`, `JobTitle`, `JobDescription` validan sus propias reglas en el constructor — el formulario web valida por UX, pero el dominio se protege a sí mismo independientemente del punto de entrada.
- **Eventos de dominio reales**: el agregado `JobApplication` registra `ApplicationSubmitted` al crearse; ese evento se enruta a un transporte asíncrono (Doctrine transport, tabla `messenger_messages`) y un manejador separado (`EnrichApplicationOnApplicationSubmitted`) llama al puerto de IA y despacha `EnrichApplicationCommand` para actualizar la candidatura una vez completado.
- **Enriquecimiento IA mockeado**: `AiEnrichmentPort` es un puerto de dominio; `MockAiEnrichmentAdapter` lo implementa de forma determinista (resumen = excerpt del CV, puntuación = solapamiento de palabras clave entre el CV y el puesto), sin llamar a ningún proveedor externo, tal y como pide el enunciado.
- **Bloqueo de borrado de ofertas con candidaturas**: eliminar una oferta que ya tiene candidaturas asociadas lanza una excepción de dominio (`JobPostingHasApplicationsException`) en vez de permitir referencias huérfanas.
- **Reglas de dependencia**: `Domain` no depende de `Infrastructure`/`UI`; `Application` solo depende de `Domain` y de `Shared\Application\Bus`; `Infrastructure` implementa los puertos de `Domain`; `UI` solo llama a `Application`.

## Endpoints

### Páginas web

| Método | Ruta | Descripción |
|---|---|---|
| GET/POST | `/apply` | Formulario para aplicar a una oferta |
| GET | `/applications` | Listado de candidaturas con filtro/búsqueda en tiempo real (JS + API) |
| GET | `/applications/{id}` | Detalle de una candidatura (CV completo, resumen y puntuación IA) |
| GET | `/jobs` | Listado de ofertas |
| GET/POST | `/jobs/new` | Crear oferta |
| GET/POST | `/jobs/{id}/edit` | Editar oferta |
| POST | `/jobs/{id}/delete` | Eliminar oferta (bloqueado si tiene candidaturas) |
| GET | `/jobs/{id}` | Detalle de una oferta |

### API JSON

```bash
# Crear una oferta (vía la página web; no hay endpoint REST dedicado, ver design.md)

# Listar ofertas
curl http://localhost:8080/api/jobs

# Aplicar a una oferta
curl -X POST http://localhost:8080/api/applications \
  -H "Content-Type: application/json" \
  -d '{
        "jobId": "<uuid-de-la-oferta>",
        "fullName": "Ana García",
        "email": "ana@example.com",
        "phone": "+34600111222",
        "notes": "",
        "cvText": "Desarrolladora backend con 5 años de experiencia en PHP y Symfony..."
      }'
# → 201 { "id": "<uuid-de-la-candidatura>" }

# Listar candidaturas (más reciente primero), con filtros opcionales
curl "http://localhost:8080/api/applications"
curl "http://localhost:8080/api/applications?status=enriched"
curl "http://localhost:8080/api/applications?jobId=<uuid>"
curl "http://localhost:8080/api/applications?search=ana"

# Detalle de una candidatura
curl http://localhost:8080/api/applications/<uuid>
```
