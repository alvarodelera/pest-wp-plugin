# Roadmap de Desarrollo: Pest Plugin for WordPress

Este documento detalla paso a paso la ejecución del proyecto. Cada tarea incluye criterios de aceptación para asegurar la calidad y funcionalidad antes de avanzar.

## 🟢 Fase 1: Infraestructura, Compatibilidad y Meta-Testing
**Objetivo:** Crear un repositorio robusto donde un test básico de WordPress corra sobre PHPUnit 12/Pest v4 usando SQLite.

### 1.1 Scaffolding & Meta-Testing (Calidad desde el inicio)
Configurar el entorno de desarrollo del propio paquete para asegurar estándares altos (level: max).

- [x] **Inicializar Repositorio:** 
    - Estructura estándar de paquete (`src/`, `tests/`, `composer.json`).
    - Inicialización repositorio GIT con .gitignore y .gitattributes 
- [x] **Definir Dependencias Core:**
    - `require`: `pestphp/pest:^4.0` (sin `yoast/phpunit-polyfills` - no necesarios para estrategia forward-only PHPUnit 12+).
    - `require-dev`: `phpstan/phpstan`, `laravel/pint`, `rector/rector`, `pestphp/pest-plugin-type-coverage`, `php-stubs/wordpress-stubs`.
- [x] **Configurar QA Tools:**
    - Crear `phpstan.neon` con nivel 9 (max).
    - Configurar `pint.json` (estándar PSR-12).
    - Configurar GitHub Actions para correr estos checks en cada PR.
    - Configurar el IDE VSCode con un archivo con la settings necesarias para sincronizarlo con estas necesidades cuando se guarden archivos.

**✅ Criterio de Éxito:**
- ✅ Ejecutar `composer lint` no arroja errores.
- ✅ Ejecutar `composer analyse` (PHPStan) pasa en limpio.
- ✅ El repositorio tiene CI funcionando en GitHub.

### 1.2 Bootstrapper & SQLite Automator
La lógica para instalar WordPress y configurar la base de datos sin intervención del usuario.

- [x] **Downloader Script:** Crear clase `WordPressInstaller`.
    - Debe descargar la última versión de WP a `.pest/wordpress/`.
    - Debe ser idempotente (no descargar si ya existe y es la versión correcta).
- [x] **SQLite Integration:**
    - Descargar el plugin `sqlite-database-integration` (o drop-in equivalente).
    - Copiar `db.php` a `.pest/wordpress/wp-content/`.
- [x] **Config Generator:**
    - Generar dinámicamente `wp-tests-config.php` apuntando a la DB SQLite.
    - Definir constantes críticas (`WP_TESTS_DIR`, `WP_DEBUG`).

**✅ Criterio de Éxito:**
- ✅ Al correr el instalador, aparece la carpeta `.pest/wordpress`.
- ✅ Existe un archivo `.pest/wordpress/wp-content/db.php`.
- ✅ Se puede instanciar WordPress manualmente con un script PHP simple (`require 'wp-load.php'`) sin errores de conexión a DB.

### 1.3 The Compatibility Layer (El puente PHPUnit 12 <-> WP)
Hacer que la suite legacy de WP funcione en el entorno moderno.

- [x] **Clase Base `PestWP\TestCase`:**
    - Implementación propia sin depender de WP_UnitTestCase ni Yoast polyfills.
    - Preparada para extender con rollback de transacciones en Fase 2.
- [x] **Bootstrap Loader:**
    - Crear `src/bootstrap.php`.
    - Debe cargar `vendor/autoload.php` y luego WordPress con SQLite.
    - **NOTA:** No usamos polyfills de Yoast porque no los necesitamos (forward-only strategy: PHP 8.3+, PHPUnit 12+).

**✅ Criterio de Éxito:**
- ✅ Tests en `tests/Integration/` pueden ejecutarse con `./vendor/bin/pest`.
- ✅ El output de Pest es verde y muestra los tiempos de ejecución.

### 1.4 Proof of Concept (PoC) de Integración
Verificar que la base de datos realmente funciona y se limpia.

- [x] **Test de Persistencia:** Crear un test que use `wp_insert_post()`.
- [x] **Test de Aislamiento:**
    - Test A: Crea un post con título "Unico".
    - Test B: Busca un post con título "Unico" y aserta que NO existe.
    - ✅ Implementado con enfoque de snapshots (Fase 2.0 completada).

**✅ Criterio de Éxito:**
- ✅ Test de persistencia pasa (wp_insert_post funciona correctamente).
- ✅ Tests de aislamiento pasan (implementado con snapshots de BD).

## 🟡 Fase 2: Developer Experience (La API del Usuario)
**Objetivo:** Que el desarrollador sienta que está usando una herramienta moderna, no un wrapper viejo de WP.

### 2.0 Database Isolation (Pre-requisito) ✅ COMPLETADA
Implementar aislamiento de base de datos entre tests.

- [x] **Snapshots SQLite:** Implementar sistema de snapshots que copia el estado limpio de la BD antes de cada test.
    - **Nota:** Se descartó el enfoque de transacciones porque `WP_SQLite_Translator` envuelve cada query en `begin_transaction()`/`commit()` automáticamente, lo que impide el rollback manual.
    - **Benchmark:** File copy (~1.76ms) es ~14x más rápido que rollback (~24.5ms) por test.
- [x] **DatabaseManager:** Nueva clase `src/Database/DatabaseManager.php` que gestiona snapshots.
    - `initialize()` - Detecta la ruta de la BD y crea snapshot inicial.
    - `createSnapshot()` - Copia la BD a archivo temporal.
    - `restoreSnapshot()` - Restaura la BD antes de cada test.
    - `cleanup()` - Limpia el snapshot al final del suite.
- [x] **TestCase con Hooks:** Hooks `beforeEach`/`afterEach` en `tests/Pest.php` manejan la restauración automáticamente.
- [x] **Validación:** Los tests de `DatabaseIsolationTest.php` pasan correctamente.

**✅ Criterio de Éxito:**
- ✅ Test A crea un post, Test B verifica que el post NO existe.
- ✅ Cada test comienza con un estado limpio de la base de datos.
- ✅ 42 tests pasan, PHPStan nivel 9 sin errores.

### 2.1 Pest Plugin & Autoloading ✅ COMPLETADA
Integración nativa con el ecosistema Pest.

- [x] **Plugin Class:** Implementar la interfaz `Pest\Plugin`.
    - Implementado `PestWP\Plugin` con interfaz `Bootable`.
    - El plugin se registra en `composer.json` bajo `extra.pest.plugins`.
- [x] **Autoload Hooks:** Configurar `composer.json` (`extra.pest.plugins`) para que Pest cargue tu bootstrap automáticamente.
    - El autoloader carga `src/bootstrap.php` con la función `bootstrap()`.
    - El plugin llama a `bootstrap()` si WordPress no está cargado.
- [x] **Global Uses:** El TestCase con hooks está configurado en `tests/Pest.php`.
    - Se usa `uses(PestWP\TestCase::class)->in('Integration')` para tests de integración.
    - El TransactionManager maneja el aislamiento via SAVEPOINT/ROLLBACK.

**✅ Criterio de Éxito:**
- ✅ El usuario instala el paquete y el Plugin de Pest se registra automáticamente.
- ✅ Los tests de integración corren sin configuración manual extra.
- ✅ 42 tests pasan, PHPStan nivel 9 sin errores, Pint sin issues.

### 2.2 Factories Wrapper (Tipado Fuerte) ✅ COMPLETADA
Mejorar las factorías de WP para que sean amigables con el IDE.

- [x] **`createPost()`:** Wrapper de WordPress post creation.
    - Tiene PHPDoc `@return \WP_Post`.
    - Acepta argumentos personalizados o usa valores por defecto sensibles.
    - Lanza excepciones descriptivas en caso de error.
- [x] **`createUser()`:** Wrapper de WordPress user creation.
    - Permite pasar roles como string simple: `createUser('editor')`.
    - También acepta array completo de argumentos.
    - Retorna `\WP_User` con tipado fuerte.
- [x] **`createTerm()`:** Wrapper de WordPress term creation.
    - Retorna el term ID como int.
    - Acepta nombre, taxonomía y argumentos adicionales.
- [x] **`createAttachment()`:** Wrapper de WordPress attachment creation.
    - Crea imagen dummy automáticamente si no se proporciona archivo.
    - Retorna attachment ID como int.
    - Genera metadata de imagen automáticamente.

**✅ Criterio de Éxito:**
- ✅ En el IDE (VS Code / PhpStorm), al escribir `createPost()->`, el autocompletado sugiere propiedades como `post_title` o `ID`.
- ✅ PHPStan nivel 9 no se queja de tipos desconocidos al usar estos helpers.
- ✅ 61 tests pasan (129 assertions), incluyendo 19 tests específicos para factory helpers.

### 2.3 Auth Helpers ✅ COMPLETADA
Simplificar la autenticación en tests.

- [x] **`loginAs(int|WP_User $user)`:**
    - Implementado con soporte para WP_User o user ID.
    - Maneja `wp_set_current_user` y `wp_set_auth_cookie`.
    - Funciona correctamente con sistema de permisos de WordPress.
- [x] **`logout()`:**
    - Implementado para limpiar el usuario actual.
    - Usa `wp_set_current_user(0)` y `wp_clear_auth_cookie()`.
- [x] **`currentUser()`:**
    - Wrapper conveniente de `wp_get_current_user()`.
    - Retorna objeto `\WP_User` con tipado fuerte.
- [x] **`isUserLoggedIn()`:**
    - Wrapper de `is_user_logged_in()` de WordPress.
    - Verifica el estado de autenticación actual.
- [x] **Database Isolation Fix:**
    - Modificado `tests/Pest.php` para ejecutar `logout()` antes y después de cada test.
    - Esto previene que el estado de auth de un test afecte a otros.
    - Los auth tests NO usan transaction rollback para preservar usuarios en DB.

**✅ Criterio de Éxito:**
- ✅ `loginAs($admin); expect(current_user_can('manage_options'))->toBeTrue();`
- ✅ `logout(); expect(is_user_logged_in())->toBeFalse();`
- ✅ 20 tests de auth helpers pasan (60 assertions).
- ✅ 81 tests totales pasan (189 assertions).
- ✅ PHPStan nivel 9 sin errores.
- ✅ Pint sin issues de estilo.

### 2.4 Custom Expectations (DSL) ✅ COMPLETADA
El "lenguaje" del plugin.

- [x] **Expectations Organizadas por Categoría:**
    - **Posts** (`posts.php`): `toBePublished()`, `toBeDraft()`, `toBePending()`, `toBePrivate()`, `toBeInTrash()`.
    - **Errors** (`errors.php`): `toBeWPError()`, `toHaveErrorCode()`.
    - **Metadata** (`metadata.php`): `toHaveMeta()`, `toHaveMetaKey()`, `toHaveUserMeta()`.
    - **Hooks** (`hooks.php`): `toHaveAction()`, `toHaveFilter()`.
    - **Terms** (`terms.php`): `toHaveTerm()`, `toBeRegisteredTaxonomy()`.
    - **Users** (`users.php`): `toHaveCapability()`, `toHaveRole()`, `can()`.
    - **Shortcodes** (`shortcodes.php`): `toBeRegisteredShortcode()`.
    - **Options** (`options.php`): `toHaveOption()`, `toHaveTransient()`.
    - **Post Types** (`post-types.php`): `toBeRegisteredPostType()`, `toSupportFeature()`.
- [x] **Helpers Adicionales:**
    - Options: `setOption()`, `deleteOption()`.
    - Transients: `setTransient()`, `deleteTransient()`.
    - Shortcodes: `registerTestShortcode()`, `unregisterShortcode()`.
- [x] **Tests Comprehensivos:**
    - 39 tests para expectations originales (72 assertions).
    - 43 tests para expectations adicionales (92 assertions).
    - Total: 163 tests pasando (364 assertions).
- [x] **Estructura Modular:**
    - Archivos separados en `src/Expectations/` por categoría.
    - README.md en carpeta Expectations.
    - Carga automática desde `src/Expectations.php`.

**✅ Criterio de Éxito:**
- ✅ Todos los expectations tienen tests cubriendo casos positivos y negativos.
- ✅ 163 tests pasan (364 assertions).
- ✅ PHPStan nivel 9 sin errores.
- ✅ Pint PSR-12 compliant.

## 🔵 Fase 3: Browser Testing (E2E con Playwright)
**Objetivo:** Tests de navegador estables y agnósticos al entorno.

### 3.1 Wizard de Configuración ✅ COMPLETADA
Guiar al usuario para conectar su entorno.

- [x] **Comando `pest-setup-browser`:**
    - Input: URL Base (--url).
    - Input: Credenciales Admin (--user, --pass).
    - Action: Crear/Actualizar `tests/Pest.php` sección `browser()`.
    - Implementación: `src/Commands/SetupBrowserCommand.php`.
    - CLI Executable: `bin/pest-setup-browser`.
    - Validación completa de inputs (URL format, empty values).
    - Preserva contenido existente del archivo Pest.php.
    - Maneja múltiples formatos de URL (HTTP/HTTPS, con puerto).
    - Soporta caracteres especiales en contraseñas.
- [x] **Tests Comprehensivos:**
    - 14 tests de comando (36 assertions).
    - Coverage: validación, generación de config, actualización, casos edge.
- [x] **Registro en Composer:**
    - Añadido `bin/pest-setup-browser` al `composer.json`.
    - El comando está disponible globalmente tras instalación.

**✅ Criterio de Éxito:**
- ✅ El comando modifica el archivo `tests/Pest.php` correctamente sin romper la sintaxis existente.
- ✅ 177 tests pasan (400 assertions).
- ✅ PHPStan nivel 9 sin errores.
- ✅ Pint PSR-12 compliant.

### 3.2 Pest Browser Plugin Integration ✅ COMPLETADA
Integración con el plugin oficial de Pest para testing E2E.

- [x] **Pest Browser Plugin:**
    - Añadido `pestphp/pest-plugin-browser:^4.0` como dependencia.
    - Configurado `allow-plugins` en `composer.json`.
    - Usa Playwright por debajo, pero con API PHP nativa.
- [x] **Browser Helpers en PHP:**
    - Implementado `src/Functions/browser.php` con helpers esenciales:
    - `getBrowserConfig()` - Lee configuración de `browser()` o env vars.
    - `getStorageStatePath()` - Retorna ruta al archivo de estado de auth.
    - `hasBrowserAuthState()` - Verifica si existe estado guardado.
- [x] **Documentación:**
    - Actualizado `docs/BROWSER_TESTING.md` con guía completa.
    - Ejemplos de uso con API de Pest Browser.
    - Troubleshooting y mejores prácticas.
- [x] **Tests PHP de Helpers:**
    - 3 tests unitarios en `tests/Unit/Functions/BrowserHelpersTest.php`.
    - Coverage de funciones de configuración.
- [x] **Ejemplo de Test Browser:**
    - Creado `tests/Browser/DashboardTest.php` como template.

**✅ Criterio de Éxito:**
- ✅ Plugin Pest Browser instalado y disponible.
- ✅ Helpers de PHP proporcionan configuración al plugin.
- ✅ 180 tests PHP pasan (412 assertions).
- ✅ PHPStan nivel 9 sin errores.
- ✅ Pint PSR-12 compliant.

**Nota:** Se migró de Playwright standalone (TypeScript) a Pest Browser Plugin (PHP nativo) para mejor integración con el ecosistema Pest.

### 3.3 WP Admin Locators ✅ COMPLETADA
Abstracciones para selectores frágiles.

- [x] **URL Helpers:**
    - `adminUrl($page, $params)` - Construye URLs de admin con parámetros opcionales.
    - `loginUrl()` - URL de login de WordPress.
    - `newPostUrl($postType)` - URL para crear nuevo post/page.
    - `editPostUrl($postId)` - URL para editar post específico.
    - `postsListUrl($postType, $status)` - URL de lista de posts.
    - `mediaLibraryUrl()`, `usersListUrl()`, `pluginsUrl()`, etc.
- [x] **Menu Navigation:**
    - `menuSelector($name)` - Selector para items del menú admin.
    - `submenuSelector($parent, $submenu)` - Selector para subitems.
- [x] **Gutenberg Interaction:**
    - `postTitleSelector()` - Selector para campo de título (compatible WP 6.5+).
    - `publishButtonSelector()` - Selector para botón publicar.
    - `blockSelector($blockName)` - Selector para bloques por `data-type`.
    - `blockInserterSelector()` - Selector para añadir bloque.
    - `editorNoticeSelector()` - Selector para notificaciones del editor.
- [x] **Admin UI Selectors:**
    - `noticeSelector($type)` - Selectores para notices (success, error, etc.).
    - `buttonSelector($text, $type)` - Selectores para botones.
    - `tableRowSelector($title)` - Selector para filas en tablas WP.
    - `rowActionSelector($action)` - Selector para acciones de fila.
- [x] **Classic Editor Support:**
    - `classicTitleSelector()`, `classicContentSelector()`, `classicPublishSelector()`.
- [x] **Tests Comprehensivos:**
    - 46 tests unitarios para todos los locators.
    - Coverage de URL helpers, menu selectors, Gutenberg, y data tables.

**✅ Criterio de Éxito:**
- ✅ Helpers de URL generan URLs correctas para todas las páginas admin.
- ✅ Selectores CSS son resilientes y cubren múltiples versiones de WP.
- ✅ 226 tests PHP pasan (489 assertions).
- ✅ PHPStan nivel 9 sin errores.
- ✅ Pint PSR-12 compliant.

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
