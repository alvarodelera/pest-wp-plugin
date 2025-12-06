# Roadmap de Desarrollo: Pest Plugin for WordPress

Este documento detalla paso a paso la ejecución del proyecto. Cada tarea incluye criterios de aceptación para asegurar la calidad y funcionalidad antes de avanzar.

## 🟢 Fase 1: Infraestructura, Compatibilidad y Meta-Testing
**Objetivo:** Crear un repositorio robusto donde un test básico de WordPress corra sobre PHPUnit 12/Pest v4 usando SQLite.

### 1.1 Scaffolding & Meta-Testing (Calidad desde el inicio)
Configurar el entorno de desarrollo del propio paquete para asegurar estándares altos (level: max).

- [ ] **Inicializar Repositorio:** 
    - Estructura estándar de paquete (`src/`, `tests/`, `composer.json`).
    - Inicialización repositorio GIT con .gitignore y .gitattributes 
- [ ] **Definir Dependencias Core:**
    - `require`: `pestphp/pest:^4.0`, `yoast/phpunit-polyfills:^3.0`.
    - `require-dev`: `phpstan/phpstan`, `laravel/pint`, `rector/rector`, `pestphp/pest-plugin-type-coverage`.
- [ ] **Configurar QA Tools:**
    - Crear `phpstan.neon` con nivel 9 (max).
    - Configurar `pint.json` (estándar PSR-12).
    - Configurar GitHub Actions para correr estos checks en cada PR.
    - Configurar el IDE VSCode con un archivo con la settings necesarias para sincronizarlo con estas necesidades cuando se guarden archivos.

**✅ Criterio de Éxito:**
- Ejecutar `composer lint` no arroja errores.
- Ejecutar `composer analyse` (PHPStan) pasa en limpio sobre un archivo "Hola Mundo".
- El repositorio tiene CI funcionando en GitHub.

### 1.2 Bootstrapper & SQLite Automator
La lógica para instalar WordPress y configurar la base de datos sin intervención del usuario.

- [ ] **Downloader Script:** Crear clase `WordPressInstaller`.
    - Debe descargar la última versión de WP a `.pest/wordpress/`.
    - Debe ser idempotente (no descargar si ya existe y es la versión correcta).
- [ ] **SQLite Integration:**
    - Descargar el plugin `sqlite-database-integration` (o drop-in equivalente).
    - Copiar `db.php` a `.pest/wordpress/wp-content/`.
- [ ] **Config Generator:**
    - Generar dinámicamente `wp-tests-config.php` apuntando a la DB SQLite.
    - Definir constantes críticas (`WP_TESTS_DIR`, `WP_DEBUG`).

**✅ Criterio de Éxito:**
- Al correr el instalador, aparece la carpeta `.pest/wordpress`.
- Existe un archivo `.pest/wordpress/wp-content/db.php`.
- Se puede instanciar WordPress manualmente con un script PHP simple (`require 'wp-load.php'`) sin errores de conexión a DB.

### 1.3 The Compatibility Layer (El puente PHPUnit 12 <-> WP)
Hacer que la suite legacy de WP funcione en el entorno moderno.

- [ ] **Clase Base `PestWP\TestCase`:**
    - Extender `WP_UnitTestCase`.
    - Usar el trait `Yoast\PHPUnitPolyfills\TestCases\TestCaseTrait`.
- [ ] **Bootstrap Loader:**
    - Crear `src/bootstrap.php`.
    - Debe cargar `vendor/autoload.php` y luego la suite de pruebas de WP (`includes/functions.php`).
    - **CRÍTICO:** Implementar parches en memoria si WP Core usa sintaxis deprecada de PHP 8.3+.

**✅ Criterio de Éxito:**
- Un archivo de test `tests/Unit/ExampleTest.php` que extiende tu nueva clase base puede ejecutarse con `./vendor/bin/pest`.
- El output de Pest es verde y muestra los tiempos de ejecución.

### 1.4 Proof of Concept (PoC) de Integración
Verificar que la base de datos realmente funciona y se limpia.

- [ ] **Test de Persistencia:** Crear un test que use `wp_insert_post()`.
- [ ] **Test de Aislamiento:**
    - Test A: Crea un post con título "Unico".
    - Test B: Busca un post con título "Unico" y aserta que NO existe.

**✅ Criterio de Éxito:**
- Ambos tests pasan. Esto confirma que la transacción de base de datos (rollback) de `WP_UnitTestCase` está funcionando correctamente sobre SQLite.

## 🟡 Fase 2: Developer Experience (La API del Usuario)
**Objetivo:** Que el desarrollador sienta que está usando una herramienta moderna, no un wrapper viejo de WP.

### 2.1 Pest Plugin & Autoloading
Integración nativa con el ecosistema Pest.

- [ ] **Plugin Class:** Implementar la interfaz `Pest\Plugin`.
- [ ] **Autoload Hooks:** Configurar `composer.json` (`extra.pest.plugins`) para que Pest cargue tu bootstrap automáticamente.
- [ ] **Global Uses:** Inyectar `uses(PestWP\TestCase::class)->in('tests/WP')` para que el usuario no tenga que escribirlo.

**✅ Criterio de Éxito:**
- El usuario instala el paquete y corre `pest --init`.
- El archivo `Pest.php` generado es limpio.
- Los tests en la carpeta designada corren sin configuración manual extra.

### 2.2 Factories Wrapper (Tipado Fuerte)
Mejorar las factorías de WP para que sean amigables con el IDE.

- [ ] **`create_post()`:** Wrapper de `static::factory()->post->create_and_get()`.
    - Debe tener PHPDoc `@return \WP_Post`.
- [ ] **`create_user()`:** Wrapper de `static::factory()->user->create_and_get()`.
    - Debe permitir pasar roles como string simple: `create_user('editor')`.
- [ ] **`create_term()` / `create_attachment()`:** Implementaciones similares.

**✅ Criterio de Éxito:**
- En el IDE (VS Code / PhpStorm), al escribir `create_post()->`, el autocompletado sugiere propiedades como `post_title` o `ID`.
- PHPStan (Meta-testing) no se queja de tipos desconocidos al usar estos helpers.

### 2.3 Auth Helpers
Simplificar la autenticación en tests.

- [ ] **`loginAs(int|WP_User $user)`:**
    - Debe manejar `wp_set_current_user`.
    - Debe configurar la cookie de auth simulada para que `current_user_can` funcione.

**✅ Criterio de Éxito:**
- Test: `loginAs($admin); expect(current_user_can('manage_options'))->toBeTrue();`
- Test: `logout(); expect(is_user_logged_in())->toBeFalse();`

### 2.4 Custom Expectations (DSL)
El "lenguaje" del plugin.

- [ ] **Objetos WP:**
    - `expect($post)->toBePublished()` (`status === 'publish'`).
    - `expect($post)->toBeDraft()`.
    - `expect($wp_error)->toBeWPError()`.
- [ ] **Base de Datos / Meta:**
    - `expect($post)->toHaveMeta('price', 100)`.
- [ ] **Hooks:**
    - `expect('init')->toHaveAction('mi_funcion')`.

**✅ Criterio de Éxito:**
- Todos los expectations tienen tests unitarios propios dentro del paquete cubriendo casos positivos y negativos (falsos positivos).

## 🔵 Fase 3: Browser Testing (E2E con Playwright)
**Objetivo:** Tests de navegador estables y agnósticos al entorno.

### 3.1 Wizard de Configuración
Guiar al usuario para conectar su entorno.

- [ ] **Comando `pest:setup-browser`:**
    - Input: URL Base.
    - Input: Credenciales Admin (User/Pass).
    - Action: Crear/Actualizar `pest.php` sección `browser()`.

**✅ Criterio de Éxito:**
- El comando modifica el archivo `pest.php` correctamente sin romper la sintaxis existente.

### 3.2 Auth Strategy (Zero-Login)
Optimización de velocidad para tests E2E.

- [ ] **Global Setup Script:** Crear script TS/JS para Playwright.
    - Navegar a `/wp-login.php`.
    - Rellenar form.
    - Guardar estado en `.pest/state/admin.json`.
- [ ] **Helper `loginAsAdmin()` en Pest:**
    - Debe instruir a Playwright para cargar ese JSON antes del test.

**✅ Criterio de Éxito:**
- Un test que visita `/wp-admin/` carga inmediatamente el Dashboard, sin pasar por el formulario de login ni esperar redirecciones.

### 3.3 WP Admin Locators
Abstracciones para selectores frágiles.

- [ ] **Menu Navigation:**
    - `visitAdminPage($slug)` -> Convierte a `admin.php?page=$slug`.
    - `clickMenu($name)` -> Busca por aria-label o texto del menú lateral.
- [ ] **Gutenberg Interaction:**
    - `fillBlock($blockName, $content)` -> Reto complejo. Investigar selectores de atributos `data-type`.

**✅ Criterio de Éxito:**
- Tests corriendo contra WP 6.5 y WP 6.6 (beta) pasan sin cambios en el código del test, demostrando que los locators son resilientes a cambios menores de markup.

## 🟣 Fase 4: Tooling & Release
**Objetivo:** Preparar el paquete para el mundo real.

### 4.1 Architecture Presets
Reglas de calidad específicas para WP.

- [ ] **Preset `wordpress`:**
    - Forbid: `dd`, `dump`, `var_dump`.
    - Forbid: `global $wpdb` (sugerir inyección de dependencias o helpers).
    - Forbid: `mysql_*` functions (obsoletas).

**✅ Criterio de Éxito:**
- Correr `pest --type-coverage` y `pest --lint` sobre un proyecto de prueba con malas prácticas reporta los errores esperados.

### 4.2 Documentación y CI
- [ ] **README.md:** Ejemplos claros de "Integration vs Browser".
- [ ] **GitHub Actions Template:**
    - Crear un workflow reutilizable (`.yml`) que instale Pest, configure SQLite y corra los tests en 30 segundos.

**✅ Criterio de Éxito:**
- Un desarrollador externo (beta tester) puede instalar el paquete y correr su primer test en < 5 minutos siguiendo solo el README.
