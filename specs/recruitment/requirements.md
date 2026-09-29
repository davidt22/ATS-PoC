# Requirements — Bounded Context: Recruitment (ATS PoC)

Estado: **aprobado**. Cambios posteriores requieren nueva aprobación antes de tocar diseño/código.

## Contexto

Micro-ATS (Applicant Tracking System) que permite a un candidato aplicar a una oferta pegando su CV como texto plano, y a un reclutador explorar las candidaturas recibidas. El enriquecimiento con IA (resumen + puntuación) es asíncrono y simulado (mock, sin LLM real).

## Glosario (ubicuous language)

- **JobPosting**: oferta de trabajo publicada (puesto).
- **JobApplication** (candidatura): solicitud de un candidato a un JobPosting, con su CV pegado como texto.
- **Enriquecimiento IA**: proceso asíncrono que genera `aiSummary` (resumen) y `aiScore` (0-100) sobre una JobApplication.
- **Estado (status)**: `received` (recién creada) → `enriched` (tras completar el enriquecimiento).

## Requisitos funcionales

### R0 — Gestión de ofertas (JobPosting)

- R0.1 EL SISTEMA DEBE permitir crear una oferta con título y descripción.
- R0.2 EL SISTEMA DEBE permitir listar las ofertas existentes.
- R0.3 EL SISTEMA DEBE permitir ver el detalle de una oferta.
- R0.4 EL SISTEMA DEBE permitir editar el título y la descripción de una oferta existente.
- R0.5 EL SISTEMA DEBE permitir eliminar una oferta.
- R0.6 SI una oferta tiene JobApplications asociadas, EL SISTEMA DEBE rechazar su eliminación (invariante de dominio, evita candidaturas huérfanas).
- R0.7 SI se intenta crear/editar una oferta con título o descripción vacíos, EL SISTEMA DEBE rechazarlo.

### R1 — Aplicar a una oferta

- R1.1 CUANDO un candidato visita la página de aplicación, EL SISTEMA DEBE mostrar el listado de puestos disponibles, un campo de nombre completo, email, teléfono (opcional), notas (opcional) y un textarea para el CV.
- R1.2 CUANDO el candidato envía el formulario con datos válidos, EL SISTEMA DEBE crear una JobApplication con `appliedAt` (fecha/hora automática) y estado inicial `received`.
- R1.3 CUANDO el candidato envía el formulario con datos inválidos (nombre vacío, email mal formado, CV vacío o demasiado corto), EL SISTEMA DEBE rechazar la creación y mostrar el motivo, sin persistir nada.
- R1.4 CUANDO se crea una JobApplication, EL SISTEMA DEBE encolar de forma asíncrona un proceso de enriquecimiento con IA (no debe bloquear la respuesta al candidato).
- R1.5 EL SISTEMA NO DEBE aceptar subida de archivos ni OCR — el CV es siempre texto plano pegado por el usuario.

### R2 — Enriquecimiento asíncrono con IA

- R2.1 CUANDO el enriquecimiento se completa, EL SISTEMA DEBE actualizar la JobApplication con un resumen del CV (`aiSummary`) y una puntuación de relevancia 0-100 (`aiScore`) respecto al puesto.
- R2.2 CUANDO se actualiza con el resultado de IA, EL SISTEMA DEBE cambiar el estado de la JobApplication a `enriched`.
- R2.3 EL SISTEMA DEBE simular (mock) la llamada al LLM — sin llamadas a una API real — de forma determinista y documentada como mock.
- R2.4 SI el enriquecimiento falla, EL SISTEMA DEBE reintentar según política de reintentos del transporte asíncrono, sin perder el mensaje (dead-letter/failed transport).

### R3 — Explorar candidaturas

- R3.1 CUANDO un reclutador visita la página de candidaturas, EL SISTEMA DEBE listarlas ordenadas de más reciente a más antigua por `appliedAt`.
- R3.2 EL SISTEMA DEBE permitir filtrar el listado por estado y por puesto en tiempo real (sin recargar la página completa).
- R3.3 EL SISTEMA DEBE permitir buscar por nombre del candidato o email en tiempo real.
- R3.4 EL listado DEBE mostrar, por cada candidatura: candidato, email, estado, puntuación IA (si existe) y fecha.

### R4 — Detalle de candidatura

- R4.1 CUANDO un reclutador abre el detalle de una JobApplication, EL SISTEMA DEBE mostrar: datos del candidato, puesto, texto completo del CV, resumen IA, puntuación IA, estado, `appliedAt` y `updatedAt`.
- R4.2 SI el enriquecimiento aún no se ha completado, EL SISTEMA DEBE mostrar el detalle igualmente, con resumen/puntuación vacíos y estado `received`.
- R4.3 SI la JobApplication solicitada no existe, EL SISTEMA DEBE devolver 404.

### R5 — Descripción del puesto en el formulario de aplicación

- R5.1 CUANDO el candidato selecciona un puesto en el desplegable de `/apply`, EL SISTEMA DEBE mostrar la descripción de ese puesto justo debajo del desplegable.
- R5.2 CUANDO el candidato cambia la selección a otro puesto, EL SISTEMA DEBE actualizar la descripción mostrada con la del nuevo puesto seleccionado, sin recargar la página.
- R5.3 SI no hay ningún puesto seleccionado, EL SISTEMA NO DEBE mostrar ninguna descripción.

## Requisitos no funcionales

- RNF1 Estructura en capas DDD + Hexagonal + CQRS/eventos, con reglas de dependencia documentadas (Domain no depende de Infra/UI; Application solo de Domain; Infra implementa puertos de Domain; UI solo llama a Application).
- RNF2 Tests unitarios (Domain/Application) y de integración (Infrastructure/UI) cubriendo: envío de candidatura (válida e inválida), filtrado/búsqueda, y el flujo de enriquecimiento asíncrono.
- RNF3 Código legible, sin sobre-ingeniería (KISS), cumpliendo SOLID y PSR-4/PSR-12.
- RNF4 Instrucciones simples para ejecutar en local (Docker), idealmente `make init`.
- RNF5 Documentación concisa (README) sin detalles de implementación internos.

## Fuera de alcance (explícito)

- Autenticación/autorización de reclutadores.
- Edición o transición manual de estado de la candidatura (p. ej. `rejected`, `hired`).
- Subida de ficheros / OCR.
- Integración real con un proveedor LLM.
- Paginación del listado de candidaturas (se asume volumen bajo para el PoC; se puede añadir después sin romper la interfaz de `search()`).
- UI de gestión de ofertas con diseño elaborado: basta con páginas simples (crear/listar/editar/eliminar) coherentes con el resto del PoC.

## Decisiones registradas

- JobPosting tiene gestión completa (crear/listar/detalle/editar/eliminar), no solo datos semilla.
- Eliminar una oferta con candidaturas asociadas está bloqueado (excepción de dominio), para no dejar `jobId` huérfanos.

## Preguntas abiertas para el usuario

1. ¿Algún límite de tiempo/rendimiento a validar en tests de integración, o basta con corrección funcional?
