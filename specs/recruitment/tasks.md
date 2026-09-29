# Tasks — Bounded Context: Recruitment (ATS PoC)

Estado: **implementado y revisado**. Basado en `design.md` (aprobado). No se marca ninguna tarea como hecha hasta ejecutarla y verificarla (tests/`cache:clear`/petición real cuando aplique).

## 12. Code review post-implementación

Tras completar los bloques 1-11 se ejecutó `/code-review medium` sobre todo lo implementado (directriz añadida a `CLAUDE.md`: validación continua, no solo al final). Hallazgos y resolución:

- [x] **Oferta inexistente al aplicar**: `SubmitApplicationCommandHandler` no comprobaba que `jobId` existiera. Añadida comprobación (`JobPostingRepositoryInterface::findById`) que lanza `JobPostingNotFoundException` si no existe; ya mapeado a error 422/re-render por los controladores existentes. Test añadido.
- [x] **XSS almacenado** en `applications_list.html.twig`: el listado insertaba `fullName`/`email` del candidato (dato público, controlado por el atacante vía `/apply`) con `innerHTML` sin escapar. Reescrito para construir el DOM con `createElement`/`textContent`.
- [x] **CSRF ausente** en los formularios POST (`/apply`, `/jobs/new`, `/jobs/{id}/edit`, `/jobs/{id}/delete`): añadido `csrf_token('submit')` en las plantillas y `isCsrfTokenValid()` en los controladores (usando `AccessDeniedHttpException` de HttpKernel, no la de Security, para no disparar el firewall de autenticación). Tests migrados a envío vía formulario real (`Crawler::selectButton()->form()`) porque el CSRF de Symfony 7 usa un decorador same-origin ligado a cookie de respuesta, no un HMAC puro verificable fuera de un ciclo de petición real.
- [x] **500 en vez de 404** con UUID malformado en `/api/applications/{id}` y `/applications/{id}`: añadida restricción de ruta `requirements: ['id' => '[0-9a-fA-F-]{36}']` (mismo patrón que las rutas de `/jobs`) + captura de `InvalidArgumentException`.
- [x] **500 en vez de filtro ignorado** con `?status=valor-invalido`: `ApplicationStatus::from()` → `tryFrom()` en `ListApplicationsQueryHandler`.
- [x] **Reentrega de mensaje async duplica enriquecimiento**: `JobApplication::enrich()` no era idempotente. Añadido guard: si ya está `Enriched`, no-op (sin sobrescribir ni emitir `ApplicationEnriched` de nuevo). Test añadido.
- [x] **Race condition check-then-act** en `DeleteJobPostingCommandHandler` (borrar oferta justo cuando una candidatura para esa oferta está a mitad de guardarse): aceptado como riesgo documentado en `design.md` — bloqueo pesimista o FK a nivel de BD sería la solución de producción, desproporcionado para el alcance de este PoC.
- Un hallazgo candidato (paréntesis de precedencia OR/AND en `DoctrineJobApplicationRepository::search()`) fue verificado **refutado** por el propio agente de revisión: Doctrine envuelve automáticamente las partes con `OR`/`AND` en `Composite::processQueryPart` (fix DDC-1237).

**Resultado tras los fixes**: `vendor/bin/phpunit` → 61 tests, 130 assertions, 0 fallos (estable con `--order-by=random`). Verificado manualmente: `POST /jobs/{id}/delete` sin token → 403; `GET /api/applications/not-a-uuid` → 404 (antes 500); `GET /api/applications?status=bogus` → 200 (antes 500).

Leyenda: `[ref]` = ya existe como código de referencia de la sesión anterior (a revisar/ajustar, no a dar por bueno sin más); `[new]` = por escribir desde cero.

## 1. Domain — JobPosting (ampliación por R0)

- [x] 1.1 `[new]` VO `JobTitle` (no vacío, máx. 150) + `InvalidJobTitleException`.
- [x] 1.2 `[new]` VO `JobDescription` (no vacío, máx. 5000) + `InvalidJobDescriptionException`.
- [x] 1.3 `[ref→edit]` Agregado `JobPosting`: cambiar `title`/`description` de `string` a los VOs anteriores; añadir `update(JobTitle, JobDescription)`.
- [x] 1.4 `[new]` `JobPostingHasApplicationsException` (dominio, para R0.6).
- [x] 1.5 `[ref→edit]` `JobPostingRepositoryInterface`: añadir `save(JobPosting): void` y `delete(JobPosting): void`.
- [x] 1.6 `[ref→edit]` `JobApplicationRepositoryInterface`: añadir `existsByJobId(Uuid $jobId): bool` (para que `DeleteJobPostingCommandHandler` compruebe R0.6 sin que `JobPostingRepository` conozca `JobApplication`).

## 2. Domain — JobApplication (sin cambios de fondo)

- [x] 2.1 `[ref→revisar]` Revisado `JobApplication`, VOs, eventos — encajan con `requirements.md` R1-R4 sin cambios.

## 3. Application — casos de uso nuevos (JobPosting CRUD)

- [x] 3.1 `[new]` `CreateJobPostingCommand` + `CreateJobPostingCommandHandler`.
- [x] 3.2 `[new]` `UpdateJobPostingCommand` + `UpdateJobPostingCommandHandler` (404 vía `JobPostingNotFoundException` si no existe).
- [x] 3.3 `[new]` `DeleteJobPostingCommand` + `DeleteJobPostingCommandHandler` (usa `existsByJobId`; lanza `JobPostingHasApplicationsException` si aplica R0.6).
- [x] 3.4 `[new]` `GetJobPostingDetailQuery` + `GetJobPostingDetailQueryHandler`.
- [x] 3.5 `[ref→revisar]` `ListJobPostingsQuery`/Handler, `JobPostingDTO` — corregidos para usar `->value()` sobre los VOs nuevos. Verificado con `debug:container --tag=messenger.message_handler`: los 10 handlers de Recruitment están registrados en el bus correcto.

## 4. Application — casos de uso de candidatura (sin cambios de fondo)

- [x] 4.1 `[ref→revisar]` Revisados — sin cambios de fondo, encajan con R1-R4.

## 5. Infrastructure — persistencia

- [x] 5.1 `[new]` Tipos DBAL `JobTitleType`, `JobDescriptionType` (mismo patrón que `FullNameType`/`CvTextType`).
- [x] 5.2 `[ref→edit]` `JobPosting.orm.xml`: usar los nuevos tipos DBAL.
- [x] 5.3 `[ref→edit]` `DoctrineJobPostingRepository`: implementar `save()`/`delete()`.
- [x] 5.4 `[ref→edit]` `DoctrineJobApplicationRepository`: implementar `existsByJobId()` (`COUNT`).
- [x] 5.5 `[ref→revisar]` Tipos DBAL de `JobApplication` — revisado; `AiScoreType` tenía un choque de tipos de retorno con `IntegerType::convertToPHPValue(): ?int` (covarianza inválida hacia `?AiScore`), corregido extendiendo `Type` base en vez de `IntegerType`. Resto sin cambios. Registrados todos en `doctrine.yaml` `dbal.types` (paso no listado explícitamente en el plan, necesario para que Doctrine los resuelva) y cambiado el mapping ORM de `auto_mapping`/`src/Entity` al XML driver de `Recruitment`.
- [x] 5.6 `[ref→revisar]` `MockAiEnrichmentAdapter` — revisado, determinista y documentado como mock.
- [x] 5.7 Migración generada (`Version20260929170324`) y aplicada.
- [x] 5.8 `doctrine:schema:validate` OK (mapping + esquema en sync).

## 6. Infrastructure — Messenger / Shared (sin cambios de fondo)

- [x] 6.1 `[ref→revisar]` Revisados — sin cambios de fondo, confirmado por la compilación del contenedor y el registro correcto de los 10 handlers en sus buses.

## 7. UI — gestión de ofertas (nuevo, Web/Twig)

- [x] 7.1 `[new]` `JobPostingRequest` (title, description) con constraints de Symfony Validator.
- [x] 7.2 `[new]` `JobPostingListPageController` (`GET /jobs`).
- [x] 7.3 `[new]` `JobPostingDetailPageController` (`GET /jobs/{id}`, `{id}` restringido a patrón UUID para no colisionar con `/jobs/new`).
- [x] 7.4 `[new]` `CreateJobPostingPageController` (`GET/POST /jobs/new`).
- [x] 7.5 `[new]` `EditJobPostingPageController` (`GET/POST /jobs/{id}/edit`).
- [x] 7.6 `[new]` `DeleteJobPostingPageController` (`POST /jobs/{id}/delete`, flash de error si `JobPostingHasApplicationsException`/`JobPostingNotFoundException`).
- [x] 7.7 `[new]` Plantillas Twig: `jobs_list.html.twig`, `job_form.html.twig` (compartida crear/editar), `job_detail.html.twig`.
- [x] 7.8 `[ref→edit]` `base.html.twig`: enlace "Gestionar ofertas" + flashes de tipo `error` (antes solo `success`). Verificado con `debug:router`: 5 rutas de `/jobs*` sin colisión.

## 8. UI — candidaturas (sin cambios de fondo, completar lo pendiente)

- [x] 8.1 `[ref→revisar]` REST controllers — verificados con peticiones reales (ver más abajo).
- [x] 8.2 `[ref→revisar]` Web controllers — verificados con peticiones reales.
- [x] 8.3 `[ref→revisar]` `apply.html.twig`, `applications_list.html.twig` — verificadas funcionalmente vía flujo real.
- [x] 8.4 `[new]` `application_detail.html.twig` — escrita (candidato, CV completo, resumen/score IA o mensaje "procesando", estado, fechas).
- [x] 8.5 `[ref→revisar]` `assets/styles/app.css` — reutilizado sin cambios para las páginas de ofertas (mismas clases `.card`, `.form-group`, `table`).

**Verificación end-to-end real (curl, Docker levantado):**
1. `POST /jobs/new` → crea oferta → `GET /api/jobs` la devuelve.
2. `POST /apply` con esa oferta → 302 a `/applications/{id}` → `GET /api/applications/{id}` devuelve `status: received`, `aiScore: null` (enriquecimiento aún no procesado, confirma R1.4 async).
3. `php bin/console messenger:consume async --limit=1` → consume `ApplicationSubmitted`, ejecuta `EnrichApplicationCommand`, log confirma ambos handlers invocados.
4. `GET /api/applications/{id}` tras consumir → `status: enriched`, `aiScore: 36`, `aiSummary` relleno (R2.1, R2.2).
5. `GET /api/applications`, `?status=enriched`, `?search=ana` → todos devuelven el resultado esperado (R3.2, R3.3).
6. `GET /applications/{id}` (Web) → 200; `GET /applications/{uuid-inexistente}` → 404 (R4.3).
7. `POST /jobs/{id}/delete` sobre oferta con candidatura asociada → bloqueado (302 con flash de error, la oferta sigue en `GET /api/jobs`) — confirma R0.6.

## 9. Configuración

- [x] 9.1 `[ref→revisar]` `services.yaml` — sin alias nuevos necesarios, confirmado por `debug:autowiring` y las pruebas end-to-end.
- [x] 9.2 `[ref→revisar]` `.env`/`docker-compose.yml` — marcadores Flex siguen vacíos, `DATABASE_URL` apunta a MySQL, `MESSENGER_TRANSPORT_DSN` a `doctrine://default` (tabla `messenger_messages` creada por la migración); confirmado por el flujo async real.

## 10. Tests

- [x] 10.1 `[new]` Unit: VOs — `FullNameTest`, `EmailTest`, `PhoneTest`, `CvTextTest`, `AiScoreTest`, `JobTitleTest`, `JobDescriptionTest`.
- [x] 10.2 `[new]` Unit: `JobApplicationTest` (`create()` registra `ApplicationSubmitted`; `enrich()` cambia estado y registra `ApplicationEnriched`; `pullDomainEvents()` vacía la lista).
- [x] 10.3 `[new]` Unit: `JobPostingTest` (`create()`, `update()`).
- [x] 10.4 `[new]` Unit: `MockAiEnrichmentAdapterTest` (determinismo, score más alto con más solapamiento de keywords, límites 0-100, truncado del resumen).
- [x] 10.5 `[new]` Unit: `CommandHandler`s con fakes en memoria (`tests/Recruitment/Application/Fake/*`) — incluye el caso R0.6 bloqueado y el caso "not found" para cada handler relevante.
- [x] 10.6 `[new]` Integration (`KernelTestCase` + transacción rollback): `DoctrineJobApplicationRepositoryTest`, `DoctrineJobPostingRepositoryTest` contra la BD `viterbit_test` real — `search()` con filtros status/jobId/búsqueda insensible a mayúsculas, orden descendente, `existsByJobId()`.
- [x] 10.7 `[new]` Functional (`WebTestCase`): `ApplyFlowTest` (feliz + validación fallida), `ApplicationsListTest` (filtros/búsqueda vía `/api/applications`, 404 en detalle), `JobPostingCrudTest` (crear/listar/editar, borrado bloqueado y permitido).
- [x] 10.8 `[new]` **Decisión de diseño respecto al plan**: en vez de simular el transporte en memoria de Messenger, `EnrichmentAsyncFlowTest` invoca directamente el handler real (`EnrichApplicationOnApplicationSubmitted`) resuelto del contenedor contra la BD de test — es el mismo código que ejecuta `messenger:consume`; los mecanismos de transporte/cola son responsabilidad del framework, no de esta aplicación. El flujo con cola real ya se verificó manualmente con `messenger:consume async` contra Docker (ver bloque 8).

**Nota de aislamiento entre tests:** los tests `WebTestCase` hacen peticiones HTTP reales, lo que resetea la conexión de Doctrine entre requests y rompe una transacción abierta manualmente — así que limpian las tablas con `DELETE` (trait `CleansRecruitmentTables`) en vez de rollback. Los tests `KernelTestCase` puros sí pueden usar transacción+rollback, pero también limpian al inicio por si un test `WebTestCase` anterior dejó filas commiteadas. Verificado con `--order-by=random` x3 sin fallos.

**Resultado:** `vendor/bin/phpunit` → 59 tests, 126 assertions, 0 fallos.

## 11. Documentación y entrega

- [x] 11.1 `README.md` escrito y verificado contra el estado real del proyecto.
- [x] 11.2 `Makefile` (`init`, `up`, `down`, `shell`, `migrate`, `test`, `worker`) — `make init` probado dos veces (idempotente) desde cero, incluida la detección y limpieza de un `compose.override.yaml` que Symfony Flex regeneró (ver nota abajo).
- [ ] 11.3 Commit(s) — pendiente, solo cuando el usuario lo pida explícitamente.

**Incidencia detectada y corregida en este bloque:** `composer require`/`recipes:install` de Symfony Flex regenera un `compose.override.yaml` con un servicio `mailer` (Mailpit) y un override de `database` a Postgres, que Docker Compose fusiona automáticamente con `docker-compose.yml` si el fichero existe. Se eliminó (no aportaba nada a este stack) y se dejó documentado aquí para que, si una futura instalación de paquetes lo regenera, se sepa que es descartable sin más.

## Orden de ejecución propuesto

1 → 2 (revisión) → 5.1-5.6 → 5.7-5.8 (migración) → 3 → 4 (revisión) → 6 (revisión) → 7 → 8 → 9 (revisión) → 10 → 11.

Se ejecutará bloque a bloque, verificando cada uno (autoload, `cache:clear`, tests del bloque) antes de pasar al siguiente — no se implementará todo de un tirón.
