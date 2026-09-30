# Design — Bounded Context: Recruitment (ATS PoC)

Estado: **aprobado e implementado** (ver `tasks.md` para el detalle de ejecución).

## Decisiones de arquitectura (con el porqué)

| Decisión | Elegido | Alternativa descartada | Por qué |
|---|---|---|---|
| Bounded context | Un único BC `Recruitment` con dos agregados (`JobPosting`, `JobApplication`) | Dos BCs separados (`JobCatalog` + `Applications`) | Para el alcance del PoC ambos agregados comparten el mismo lenguaje ubicuo y ciclo de vida (una oferta solo existe para recibir candidaturas); separarlos añadiría un evento de integración cross-context sin beneficio real aquí. Documentado como trade-off consciente. |
| CQRS | Command/Query bus vía Symfony Messenger (3 buses: `command.bus`, `query.bus`, `event.bus`) | Servicios de aplicación simples | Ya decidido y en marcha; el ejercicio pide explícitamente CQRS/eventos y hay varios casos de uso (5 comandos, 6 queries). |
| Persistencia | Mapping Doctrine XML en Infrastructure + tipos DBAL custom por Value Object | Atributos `#[ORM\...]` en el dominio | Dominio 100% libre de Doctrine; ya implementado (`UuidType`, `FullNameType`, `EmailType`, `PhoneType`, `CvTextType`, `AiScoreType`, enum nativo para `ApplicationStatus`). |
| Enriquecimiento IA | Puerto `AiEnrichmentPort` en `Domain/Service`, adaptador mock determinista en Infrastructure | Llamada real a un LLM | Restricción explícita del enunciado: mock, sin API real. |
| Async | Evento de dominio `ApplicationSubmitted` enrutado a transporte `async` sobre **RabbitMQ** (`symfony/amqp-messenger`), consumido por un worker dedicado en background (`messenger-worker`, `restart: unless-stopped`) | Doctrine transport (elección inicial del PoC) / Redis | Petición explícita del usuario de tener gestión de eventos y procesamiento en background con un broker real, no solo una tabla de BD como cola. Se revierte la decisión inicial (documentada abajo) de evitar infraestructura extra: el coste de un contenedor más se acepta a cambio de un broker de colas de verdad y un worker que no requiere arrancarse a mano. |
| Borrado de oferta con candidaturas | Bloqueado con excepción de dominio | Cascade delete / permitir huérfanos | Decisión registrada en requirements (R0.6): invariante de integridad referencial a nivel de dominio, no solo FK de BD. |
| Descripción de puesto en `/apply` (R5) | Embeber en el propio HTML el mapa `id → description` (ya cargado por `ApplyPageController` vía `ListJobPostingsQuery`) como `data-*-value` JSON, leído por un Stimulus controller | Endpoint `fetch` a `/api/jobs` en cada cambio de selección | Los datos ya están en memoria en la misma request que renderiza `/apply`; añadir una llamada de red para algo que el servidor ya tiene sería sobre-ingeniería (KISS). Sin Live Components instalados, Stimulus es el mecanismo de interactividad ya disponible en el stack. |

## Modelo de dominio

### Agregado `JobPosting`
- Identidad: `Uuid id`.
- Atributos: `title` (VO `JobTitle`, no vacío, máx. 150), `description` (VO `JobDescription`, no vacío, máx. 5000).
- Invariante nueva (R0.7): título y descripción no vacíos → ya cubierta si se modelan como VOs en lugar de `string` plano (cambio respecto al código de referencia existente, que los tenía como `string` — se corrige en la fase de implementación).
- Métodos: `create()`, `update(title, description)`.
- No emite eventos de dominio (no hay ningún caso de uso asíncrono que dependa de su ciclo de vida).

### Agregado `JobApplication` (sin cambios respecto al código de referencia)
- Igual que lo ya implementado: VOs `FullName`, `Email`, `Phone`, `CvText`, `ApplicationStatus`, `AiScore`; eventos `ApplicationSubmitted` y `ApplicationEnriched`; método `enrich()`.

## Casos de uso (Application layer)

### Comandos (`command.bus`)
1. `CreateJobPostingCommand` → crea `JobPosting`.
2. `UpdateJobPostingCommand` → edita título/descripción.
3. `DeleteJobPostingCommand` → elimina si no tiene candidaturas asociadas (si las tiene, lanza `JobPostingHasApplicationsException`).
4. `SubmitApplicationCommand` → ya implementado.
5. `EnrichApplicationCommand` → ya implementado, disparado por el event handler asíncrono.

### Queries (`query.bus`)
1. `ListJobPostingsQuery` → ya implementado.
2. `GetJobPostingDetailQuery` → nuevo, detalle de una oferta.
3. `ListApplicationsQuery` → ya implementado (filtros status/jobId/search).
4. `GetApplicationDetailQuery` → ya implementado.

### Event handlers (`event.bus`, async)
- `EnrichApplicationOnApplicationSubmitted` → ya implementado (llama a `AiEnrichmentPort`, despacha `EnrichApplicationCommand`).

## Puertos e Infraestructura

- `JobPostingRepositoryInterface`: se añade `save()` y `delete()` (ya tenía `findAll()`/`findById()`), necesarios para el CRUD.
- Para R0.6, el repositorio expone `hasApplicationsFor(Uuid $jobId): bool` (o el `DeleteJobPostingCommandHandler` inyecta también `JobApplicationRepositoryInterface` y usa un `existsForJob()` — se decide en tasks.md cuál queda más limpio sin duplicar responsabilidad).
- `DoctrineJobPostingRepository`: añade `save()`/`delete()`, mapping XML sin cambios de forma (se añaden las columnas si los VOs cambian de `string` a `JobTitle`/`JobDescription`, requiere sus propios `DoctrineType`).

## UI — rutas

### Web (Twig, ya implementadas, sin cambios)
- `GET /apply`, `POST /apply`
- `GET /applications`
- `GET /applications/{id}`

### Web — nuevas para gestión de ofertas
- `GET /jobs` — listado de ofertas (con acciones editar/eliminar).
- `GET /jobs/new`, `POST /jobs/new` — crear.
- `GET /jobs/{id}/edit`, `POST /jobs/{id}/edit` — editar.
- `POST /jobs/{id}/delete` — eliminar (con confirmación simple; si falla por R0.6, mensaje flash de error).

### REST (JSON, consumidas por el frontend JS del listado de candidaturas)
- `POST /api/applications`, `GET /api/applications`, `GET /api/applications/{id}` — ya implementadas.
- `GET /api/jobs` — ya implementada.
- Sin nuevos endpoints REST para el CRUD de ofertas (se gestiona vía formularios Twig server-rendered, más simple/KISS que duplicar en JSON sin un consumidor real).

## UI — descripción dinámica de puesto en `/apply` (R5)

- `templates/recruitment/apply.html.twig`: el contenedor del select `#jobId` lleva `data-controller="job-description"` y `data-job-description-descriptions-value="{{ ... }}"` con el JSON `{ [jobId]: description }` de `jobPostings` (ya disponibles en el controlador, sin query adicional). El `<select>` añade `data-job-description-target="select"` y `data-action="change->job-description#update"`; justo debajo se añade `<div data-job-description-target="output">`.
- `assets/controllers/job_description_controller.js` (nuevo, autodescubierto por convención de nombre de Symfony Stimulus Bundle, sin tocar `controllers.json` que solo lista paquetes de terceros): en `connect()` y en `update()` (evento `change` del select) lee `descriptionsValue[select.value]` y actualiza el `textContent` del target `output`, vaciándolo si no hay selección (R5.3).
- No se usa Live Components (no instalado) ni AJAX: es la opción más simple dado que el servidor ya sirvió los datos.

## Infraestructura — cola de mensajes (RabbitMQ)

- `Dockerfile`: añade la extensión nativa `amqp` (paquete Alpine `rabbitmq-c-dev` + `pecl install amqp`), necesaria para que Symfony Messenger hable con RabbitMQ (`symfony/amqp-messenger`).
- `docker-compose.yml`: nuevo servicio `rabbitmq` (imagen `rabbitmq:3.13-management-alpine`, puertos `5672` AMQP y `15672` UI de gestión) y nuevo servicio `messenger-worker` (misma imagen que `php`, comando `messenger:consume async -vv`, `restart: unless-stopped`) que reemplaza la ejecución manual anterior (`make worker` ejecutando el comando a mano).
- `.env`: `MESSENGER_TRANSPORT_DSN=amqp://guest:guest@rabbitmq:5672/%2f/messages`.
- `.env.test`: se fija explícitamente `MESSENGER_TRANSPORT_DSN=doctrine://default`, para que los tests no dependan de que RabbitMQ esté arriba y alcanzable — mismo criterio ya aplicado en `EnrichmentAsyncFlowTest` (el mecanismo de transporte/cola es responsabilidad del framework, no algo que esta aplicación deba probar contra infraestructura real).
- `config/packages/messenger.yaml` no cambia: el routing de `ApplicationSubmitted` al transporte `async` ya era agnóstico del DSN.

## Reglas de dependencia (recordatorio, sin cambios)

`Domain` no depende de Symfony/Doctrine (salvo tipos DBAL en Infrastructure) ni de `Infrastructure`/`UI`. `Application` solo depende de `Domain` y de `Shared\Application\Bus`. `Infrastructure` implementa los puertos de `Domain`. `UI` solo llama a `Application` (comandos/queries), nunca a `Domain\Model` ni `Infrastructure` directamente.

## Riesgos / trade-offs conscientes

- Convertir `JobPosting.title`/`description` de `string` a VOs (`JobTitle`/`JobDescription`) obliga a tocar el mapping XML y añadir 2 tipos DBAL más — coste pequeño, gana consistencia con R0.7 y con el resto del agregado `JobApplication` (todas las invariantes de texto ya son VOs).
- No hay paginación en `ListJobPostingsQuery` ni `ListApplicationsQuery` — aceptado en requirements como fuera de alcance para el volumen de un PoC.
- `DeleteJobPostingCommandHandler` comprueba `existsByJobId()` y luego borra sin bloqueo pesimista: si `SubmitApplicationCommand` para esa misma oferta está en vuelo justo entre ambos pasos, en teoría podría colarse una candidatura huérfana. Aceptado conscientemente para el PoC — mitigarlo con un lock pesimista o una constraint `FOREIGN KEY` a nivel de BD sería la solución de producción, pero añade complejidad desproporcionada para el volumen/concurrencia de este ejercicio.
