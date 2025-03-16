# Documentación de Pruebas - Proyecto Panadería

## Introducción

Este documento describe las pruebas implementadas para el proyecto de panadería, incluyendo pruebas unitarias, de integración, de caja blanca y de caja negra.

## Estructura de Pruebas

- `tests/Unit/`: Pruebas unitarias para modelos y controladores
- `tests/Feature/`: Pruebas de integración y de caja negra
- `tests/js/`: Pruebas unitarias para componentes Vue.js
- `cypress/integration/`: Pruebas E2E con Cypress

## Ejecución de Pruebas

### Backend (Laravel)

```bash
# Ejecutar todas las pruebas
php artisan test

# Ejecutar pruebas unitarias
php artisan test --testsuite=Unit

# Ejecutar pruebas de integración
php artisan test --testsuite=Feature

# Ejecutar todas las pruebas Frontend
npm test

# Ejecutar cypress
npx cypress open
