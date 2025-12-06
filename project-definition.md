# Project Blueprint: Pest Plugin for WordPress

**Estado:** Planificación (Actualizado para Pest v4 / Dic 2025)  
**Objetivo:** Modernizar la Developer Experience (DX) del testing en WordPress.

## 1. Resumen Ejecutivo

El objetivo es crear un paquete de Composer (`pest-plugin-wordpress`) que actúe como el puente definitivo entre **PEST v4** y el ecosistema de WordPress.

Actualmente, configurar tests en WordPress es complejo debido a la incompatibilidad entre las versiones modernas de PHPUnit (requeridas por Pest) y la suite de pruebas legacy de WordPress. Este paquete busca ofrecer una experiencia "Zero-Config" que solucione internamente estos conflictos de dependencias, utilizando **SQLite** por defecto para una ejecución ultrarrápida sin configuración de servidores.

## 2. Objetivos Principales

- **Simplicidad Radical (SQLite First):** Instalación y ejecución inmediata sin necesidad de Docker o MySQL local para tests de integración.
- **Sintaxis Expresiva:** Llevar la API de expectativas (`expect()`) de Pest al mundo de WP.
- **Bridging de Versiones (Critical):** Hacer funcionar `WP_UnitTestCase` (diseñado para PHPUnit 9/10) sobre el motor de PHPUnit 12 que usa Pest v4.
- **Browser Testing Agnóstico:** Soportar Playwright conectándose a cualquier entorno local (wp-env, LocalWP, DDEV) sin forzar una herramienta específica.

## 3. Arquitectura y Dependencias

El paquete funcionará como una capa intermedia que abstrae la complejidad de la Test Suite nativa de WordPress y sus polyfills.

### Diagrama de Dependencias

```mermaid
graph TD
    UserProject[Usuario: Plugin/Theme] -->|require --dev| PestWP[Pest Plugin WordPress]
    
    subgraph "Tu Paquete (Pest WP)"
        Bootstrapper[Bootstrapper & SQLite Handler]
        Expectations[Custom Expectations API]
        TestCase[WP Compatibility Layer]
        BrowserBridge[Browser Discovery Wizard]
    end

    PestWP -->|Extends| PestCore[Pest v4 Framework]
    PestWP -->|Wraps| WPTests[WordPress Core Test Suite]
    PestWP -->|Integrates| Playwright[Pest Browser Plugin - Playwright]
    
    PestCore -->|Runs on| PHPUnit[PHPUnit 11 / 12]
    TestCase -->|Uses| YoastPolyfills[Yoast PHPUnit Polyfills]
    
    subgraph "Database Strategy"
        SQLite[SQLite Drop-in-Default]
        MySQL[MySQL - Optional/CI]
    end
    
    WPTests --> SQLite
    WPTests -.-> MySQL
```

### Matriz de Compatibilidad Objetivo

| Componente | Versión Soportada | Notas |
| :--- | :--- | :--- |
| **PHP** | 8.3+ | Requisito estricto de Pest v4 y PHPUnit 12. |
| **PEST** | v4.x | Uso nativo de Plugins, Arch y Browser (Playwright). |
| **Database** | SQLite / MySQL | SQLite manejado internamente por el paquete. |
| **WordPress** | 6.5+ | Enfocado en versiones con soporte de PHP 8.x. |

## 4. Funcionalidades Detalladas

### A. Core & Setup (SQLite First Strategy)

- **Instalador Inteligente (`pest:install-wp`):**
    1. Descarga el Core de WP a `.pest/wordpress`.
    2. Instala el drop-in de SQLite (`db.php`) automáticamente.
    3. Configura `wp-config.php` para usar SQLite en memoria o archivo temporal.
    - **Resultado:** El usuario corre `pest` y funciona sin instalar nada más.
- **Unified Test Case:** Una clase `PestWP\TestCase` que extiende `WP_UnitTestCase` y gestiona los polyfills de PHPUnit 12.

### B. Expectations (Domain Specific Language)

Extender `expect()` para entender objetos de WP:

- **Post & Query:** `expect($post)->toBePublished()`, `expect($query)->toHavePosts()`.
- **User & Auth:** `expect($user)->toBeAdministrator()`, `expect($user)->can('edit_posts')`.
- **Hooks & Errors:** `expect('init')->toHaveAction('my_callback')`, `expect($result)->toBeWPError()`.

### C. Browser Testing (Environment Agnostic)

- **Discovery Wizard:** Al instalar, preguntar al usuario: "¿Cuál es la URL de tu entorno local?" (LocalWP, wp-env, Valet, etc.).
- **Zero-Login Setup:** Utilizar `storageState` de Playwright para loguearse una sola vez en la URL proporcionada.
- **Selectores Resilientes:** Helpers para encontrar elementos de WP independientemente del tema o configuración.

## 5. Roadmap y Lista de Tareas Detallada

### Fase 1: Infraestructura y Compatibilidad (El Núcleo)
**Objetivo:** Lograr que un test vacío corra sin errores usando SQLite.

#### 1.1 Project Scaffolding
- [ ] Inicializar repositorio con estructura de paquete Composer (`src/`, `tests/`, `composer.json`).
- [ ] Definir dependencias require: `pestphp/pest:^4.0`, `yoast/phpunit-polyfills:^3.0`.
- [ ] Configurar CI básico (GitHub Actions) que corra solo en PHP 8.3+.

#### 1.2 Test Bootstrap & SQLite Handler
- [ ] **Bootstrapper:** Crear script que descargue WP Core (`/tmp` o `.pest/wp`) si no existe.
- [ ] **SQLite Drop-in:** Implementar lógica para copiar el drop-in de SQLite (`wp-sqlite-db`) al directorio `wp-content/` de la instalación de prueba.
- [ ] **Config Generator:** Generar un `wp-tests-config.php` dinámico que apunte a la DB SQLite.
- [ ] **Polyfill Layer:** Crear clase `PestWP\TestCase` que extienda `WP_UnitTestCase` y cargue `Yoast\PHPUnitPolyfills`.

#### 1.3 Database Automator
- [ ] Crear comando interno que verifique la integridad de la DB SQLite antes de correr la suite.
- [ ] Soportar fallback a MySQL mediante variables de entorno en `phpunit.xml` (para entornos legacy o CI estricto).

#### 1.4 Integration PoC
- [ ] Escribir el primer test de integración: `it('loads wordpress option', function() { expect(get_option('siteurl'))->not->toBeEmpty(); });`.
- [ ] Verificar que la base de datos se reinicia (rollback) correctamente usando SQLite.

### Fase 2: Developer Experience & Syntax Sugar
**Objetivo:** Que el usuario prefiera usar tu paquete a escribir PHPUnit puro.

#### 2.1 Pest Plugin Hooks
- [ ] Implementar el Plugin de Pest (`Pest\Plugin`) para inyectar configuración global.
- [ ] **Autoloading:** Hacer que `uses(PestWP\TestCase::class)->in('tests/WP')` funcione automáticamente en el `Pest.php` generado.

#### 2.2 Factories Wrapper
- [ ] Crear helper global `create_post(array $args = [])`.
- [ ] Crear helper global `create_user(string $role = 'subscriber')`.
- [ ] **Subtarea:** Asegurar que estos helpers retornen objetos `WP_Post`/`WP_User` en lugar de IDs numéricos.

#### 2.3 Auth Helpers
- [ ] Implementar `loginAs($user_id)` que simule la cookie de auth de WP.
- [ ] Verificar permisos: `expect($user)->can('manage_options')->toBeTrue()`.

#### 2.4 Custom Expectations (The Fun Part)
- [ ] **WP_Error:** `expect($result)->toBeWPError()`.
- [ ] **Posts:** `expect($post)->toBePublished()`, `expect($post)->toBeDraft()`, `expect($post)->toHaveMeta('key', 'val')`.
- [ ] **Actions/Filters:** `expect('init')->toHaveAction('my_function')`.

### Fase 3: Browser Testing (Pest v4 + Playwright)
**Objetivo:** Tests E2E estables sobre cualquier entorno local.

#### 3.1 Wizard de Configuración
- [ ] Crear comando interactivo `pest:setup-browser`:
    - Preguntar Base URL (ej. `http://my-site.local`).
    - Preguntar Credenciales de Admin (o intentar crearlas si hay acceso a CLI).
    - Generar/Actualizar `pest.php` con `browser()->baseUrl(...)`.

#### 3.2 Authentication Strategy
- [ ] Crear un script `global-setup.ts` para Playwright.
- [ ] Implementar lógica de login única y guardado de `storageState.json`.

#### 3.3 WP Admin Locators & Helpers
- [ ] Crear Trait `InteractsWithWordPress` para el Browser.
- [ ] Implementar `visitAdmin(string $page)`.
- [ ] Implementar `clickAdminMenu(string $menuName)`.

### Fase 4: Tooling, Documentación y Lanzamiento
**Objetivo:** Pulir el producto para consumo público.

#### 4.1 Architecture Presets
- [ ] Crear preset `arch()->preset()->wordpress()`.
- [ ] **Regla:** Prohibir `dd()`, `var_dump()` y `global $wpdb`.

#### 4.2 Documentación
- [ ] Escribir README con ejemplos de configuración para LocalWP, DDEV y wp-env.
- [ ] Documentar la estrategia de SQLite vs MySQL.

#### 4.3 CI/CD Templates
- [ ] Crear archivo `.github/workflows/wp-tests.yml` de ejemplo usando la estrategia SQLite (muy rápido en CI).

## 6. Desarrollo Interno del Paquete (Meta-Testing)

Esta sección define las herramientas y dependencias que usaremos para desarrollar y testear este paquete. El estándar de calidad debe ser máximo (level: max).

### 6.1 composer.json (Snippet de referencia)

```json
"require-dev": {
    "pestphp/pest": "^4.0",
    "pestphp/pest-plugin-type-coverage": "^3.0",
    "phpstan/phpstan": "^2.0",
    "laravel/pint": "^1.18",
    "rector/rector": "^1.2",
    "symfony/var-dumper": "^7.1",
    "mockery/mockery": "^1.6",
    "yoast/phpunit-polyfills": "^3.0"
},
"scripts": {
    "test": "pest",
    "test:coverage": "pest --coverage",
    "test:types": "pest --type-coverage",
    "lint": "pint",
    "lint:fix": "pint --fix",
    "analyse": "phpstan analyse --memory-limit=2G",
    "refactor": "rector"
}
```

### 6.2 Explicación de Herramientas

- **`pestphp/pest:^4.0`**: Usamos Pest para testear nuestro plugin de Pest (Inception!).
- **`phpstan/phpstan`**: Análisis estático estricto. **Objetivo:** Level 9 (Max). Es crucial para asegurar que nuestros helpers y expectations manejan correctamente los tipos difusos de WordPress (`false|WP_Error|int`).
- **`pestphp/pest-plugin-type-coverage`**: Plugin oficial de Pest para verificar que tenemos el 100% de tipos definidos en nuestro código.
- **`laravel/pint`**: Linter de código basado en PHP-CS-Fixer pero con configuración cero. Mantiene el estilo PSR-12 automáticamente.
- **`rector/rector`**: Herramienta de refactorización automática. Nos ayudará a mantener el paquete compatible con futuras versiones de PHP (ej. actualizar automáticamente a nuevas sintaxis).
- **`symfony/var-dumper`**: Imprescindible para debugear durante el desarrollo (`dd()`).
- **`mockery/mockery`**: Aunque Pest tiene mocks, Mockery es necesario a veces para simular comportamientos complejos de clases legacy de WordPress que no son fáciles de instanciar.
