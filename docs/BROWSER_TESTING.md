# Browser Testing con Pest

Esta guía explica cómo configurar y ejecutar tests de navegador end-to-end (E2E) en WordPress usando **Pest Browser Testing** (basado en Playwright).

> **Nota**: Este plugin integra el [Pest Browser Testing oficial](https://pestphp.com/docs/browser-testing) con WordPress, proporcionando helpers específicos para testing de WP.

## 🚀 Inicio Rápido

### 1. Instalar Dependencias

El plugin ya incluye `pestphp/pest-plugin-browser`, solo necesitas instalar los navegadores:

```bash
composer install
./vendor/bin/pest --browser-install
```

### 2. Configurar Credenciales de WordPress

Ejecuta el wizard de configuración para establecer las credenciales de tu instalación:

```bash
vendor/bin/pest-setup-browser --url http://localhost:8080 --user admin --pass password
```

Este comando creará/actualizará la función `browser()` en `tests/Pest.php` con tu configuración.

### 3. Ejecutar Tests de Navegador

```bash
./vendor/bin/pest --browser          # Ejecutar tests browser
./vendor/bin/pest --browser --headed # Ejecutar con navegador visible
```

## 📋 Configuración

### Configuración Manual

Si prefieres configurar manualmente, añade la función `browser()` en `tests/Pest.php`:

```php
function browser(): array
{
    return [
        'base_url' => 'http://localhost:8080',
        'admin_user' => 'admin',
        'admin_password' => 'password',
    ];
}
```

### Variables de Entorno

También puedes usar variables de entorno (se usan como fallback):

```bash
export WP_BASE_URL=http://localhost:8080
export WP_ADMIN_USER=admin
export WP_ADMIN_PASSWORD=password
```

## 🎯 Estrategia Zero-Login

Los tests de navegador usan la estrategia "zero-login" para optimizar la velocidad:

1. **Global Setup**: El script `playwright/global-setup.ts` se ejecuta UNA VEZ antes de todos los tests
2. **Autenticación**: Se autentica en WordPress y guarda el estado en `.pest/state/admin.json`
3. **Reutilización**: Todos los tests reutilizan este estado, evitando login repetidos

### Ventajas

- ⚡ **Velocidad**: Tests cargan directamente en el dashboard (< 3s vs ~10s con login)
- 🔒 **Seguridad**: Credenciales solo se usan en global-setup
- 📦 **Aislamiento**: Cada test mantiene su propio contexto pero comparte la autenticación

## ✍️ Escribir Tests con Pest Browser Testing

### Ejemplo Básico

Crea un archivo en `tests/Browser/`:

```php
<?php

declare(strict_types=1);

it('can access WordPress dashboard', function () {
    $config = browser();
    
    visit($config['base_url'] . '/wp-login.php')
        ->type('user_login', $config['admin_user'])
        ->type('user_pass', $config['admin_password'])
        ->press('Log In')
        ->assertPathBeginsWith('/wp-admin')
        ->assertSee('Dashboard');
});

it('can create a new post', function () {
    $config = browser();
    
    visit($config['base_url'] . '/wp-admin/post-new.php')
        ->type('[aria-label="Add title"]', 'My Test Post')
        ->press('Publish')
        ->wait(1)
        ->press('Publish') // Confirm
        ->assertSee('Post published');
});
```

### Sintaxis de Pest Browser

Pest Browser usa `visit()` que retorna un objeto `$page` con métodos encadenables:

```php
// Visita simple
$page = visit('/');
$page->assertSee('Welcome');

// Encadenado
visit('/wp-admin/')
    ->click('Posts')
    ->assertSee('All Posts');

// Con configuración
visit('/')
    ->on()->mobile()     // Viewport móvil
    ->inDarkMode();      // Modo oscuro
```

### Autenticación Persistente

Para evitar login en cada test, usa `loginAs()` antes de los tests:

```php
use function PestWP\loginAs;
use function PestWP\createUser;

beforeEach(function () {
    $admin = createUser('administrator');
    loginAs($admin);
});

it('can access admin area when logged in', function () {
    $config = browser();
    
    visit($config['base_url'] . '/wp-admin/')
        ->assertSee('Dashboard');
});
```

## 🛠️ Helpers de PHP

El plugin proporciona helpers para trabajar con la configuración de browser:

```php
use function PestWP\Functions\getBrowserConfig;

// Obtener configuración
$config = getBrowserConfig();
echo $config['base_url'];      // http://localhost:8080
echo $config['admin_user'];    // admin
echo $config['admin_password']; // password
```

### Métodos Disponibles de Pest Browser

Pest Browser Testing proporciona una API fluida para interactuar con el navegador:

```php
$page = visit('/');

// Navegación
$page->navigate('/other-page');

// Interacción con formularios
$page->type('selector', 'text')      // Escribir en input
    ->press('Button Text')            // Click en botón
    ->click('selector')               // Click en selector
    ->check('checkbox')               // Marcar checkbox
    ->select('dropdown', 'value');    // Seleccionar opción

// Assertions
$page->assertSee('text')              // Verificar texto visible
    ->assertDontSee('text')           // Verificar texto no visible
    ->assertPresent('selector')       // Verificar elemento existe
    ->assertValue('input', 'value')   // Verificar valor de input
    ->assertPathIs('/expected');      // Verificar URL

// Utilidades
$page->wait(2)                        // Esperar 2 segundos
    ->screenshot('nombre');           // Tomar screenshot
```

Para más métodos, consulta la [documentación oficial de Pest Browser Testing](https://pestphp.com/docs/browser-testing).

## 🎨 Configuración Avanzada

### Configurar Navegadores

Por defecto, Pest usa Chrome. Puedes cambiar esto en `tests/Pest.php`:

```php
// En tests/Pest.php
pest()->browser()
    ->inFirefox();  // Usar Firefox en lugar de Chrome

// O Safari
pest()->browser()
    ->inSafari();
```

## 📊 Reports y Debugging

### Ver Screenshots

Los screenshots se guardan automáticamente en failures:

```bash
# Ejecutar tests
./vendor/bin/pest --browser

# Screenshots se guardan en:
# tests/Browser/Screenshots/
```

### Debugging

```bash
# Modo headed (navegador visible)
./vendor/bin/pest --browser --headed

# Modo debug (pausa en errores, abre navegador)
./vendor/bin/pest --debug
```

Para pausar durante un test:

```php
it('debugs a page', function () {
    $config = browser();
    
    $page = visit($config['base_url'] . '/wp-admin/')
        ->debug(); // Pausa ejecución para inspeccionar
});
```

### Verbose Output

```bash
./vendor/bin/pest --browser -v
```

## 🔍 Selectores y Esperas

### Mejores Prácticas para Selectores

```php
$page = visit('/wp-admin/');

// ✅ Bueno: Usar texto visible
$page->press('Publish');

// ✅ Bueno: Usar atributos ARIA
$page->type('[aria-label="Add title"]', 'My Post');

// ✅ Bueno: Usar data-testid (con atajo @)
$page->click('@save-button'); // Equivale a [data-testid="save-button"]

// ⚠️ Evitar: Selectores frágiles
$page->click('.wp-block-post-title');
```

### Esperas

```php
$page = visit('/wp-admin/post-new.php')
    ->wait(2)                              // Esperar 2 segundos
    ->assertPresent('.editor-post-title')  // Verificar que existe
    ->assertSee('Add title');              // Verificar texto visible
```

## 🐛 Troubleshooting

### Error: "Browser plugin not found"

Asegúrate de haber instalado las dependencias:

```bash
composer install
./vendor/bin/pest --browser-install
```

### Tests fallan con "Cannot connect to browser"

Verifica que los navegadores estén instalados:

```bash
./vendor/bin/pest --browser-install
```

### WordPress no responde

Verifica que:
- WordPress está corriendo en la URL configurada
- La función `browser()` tiene la URL correcta
- No hay firewalls bloqueando el acceso

### Debugging de Configuración

```php
// En tu test
it('shows browser config', function () {
    $config = browser();
    dump($config); // Ver configuración actual
});
```

## 📚 Recursos

- [Pest Browser Testing Documentation](https://pestphp.com/docs/browser-testing)
- [WordPress Testing Handbook](https://make.wordpress.org/core/handbook/testing/)
- [Pest Plugin Documentation](../README.md)

## 🎯 Diferencias con Playwright Puro

Este plugin usa **Pest Browser Testing** que:

✅ **Ventajas**:
- Sintaxis PHP nativa (no necesitas TypeScript)
- Integración directa con Pest
- Misma API que Laravel Dusk (familiaridad)
- Screenshots automáticos en failures
- Configuración simplificada

⚠️ **Consideraciones**:
- Basado en Playwright por debajo
- Menos opciones avanzadas que Playwright puro
- Documentación en desarrollo (Pest Browser es nuevo)

## 🎯 Próximos Pasos

1. **Fase 3.3**: WP Admin Locators - Helpers específicos de WordPress
2. **Fase 4**: Tooling & Release - Architecture presets y CI/CD templates
