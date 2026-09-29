# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Principios de desarrollo

- Seguir SOLID, PSR (PSR-4 autoload, PSR-12 estilo de código) y KISS. Preferir patrones de diseño simples y justificados; evitar sobre-ingeniería (no añadir abstracciones, capas o flexibilidad que el requisito actual no pida).
- Ante ambigüedad en los requisitos o decisiones de diseño no obvias (nombrado de bounded contexts, forma de un agregado, invariantes de un Value Object, elección CQRS vs. servicio simple, etc.), preguntar al usuario en vez de asumir o inventar.
- Maximizar la cobertura de tests (unitarios de dominio/aplicación + integración de infraestructura/UI). Usar linters, análisis estático y code-review de forma continua durante el desarrollo, no solo al final de cada bloque.

## Arquitectura

Proyecto Symfony 7.4 estructurado en Hexagonal + DDD + CQRS por bounded context bajo `src/<BoundedContext>/{Domain,Application,Infrastructure,UI}`, más `src/Shared/` para código transversal (bus, Clock, eventos base). Ver las reglas de dependencia entre capas y las decisiones de arquitectura tomadas en el `README.md`.
