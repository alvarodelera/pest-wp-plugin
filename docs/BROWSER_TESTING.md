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

use function Pest\Laravel\browse;

it('can access WordPress dashboard', function () {
    browse(function ($browser) {
        $config = browser();
        
        $browser->visit($config['base_url'] . '/wp-login.php')
            ->type('user_login', $config['admin_user'])
            ->type('user_pass', $config['admin_password'])
            ->press('Log In')
            ->waitForLocation('/wp-admin/')
            ->assertSee('Dashboard');
    });
});

it('can create a new post', function () {
    browse(function ($browser) {
        $config = browser();
        
        $browser->visit($config['base_url'] . '/wp-admin/post-new.php')
            ->type('[aria-label="Add title"]', 'My Test Post')
            ->press('Publish')
            ->press('Publish') // Confirm
            ->waitForText('Post published');
    });
});
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
    browse(function ($browser) {
        $config = browser();
        
        $browser->visit($config['base_url'] . '/wp-admin/')
            ->assertSee('Dashboard');
    });
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
browse(function ($browser) {
    $browser
        ->visit('/url')              // Navegar a URL
        ->type('selector', 'text')   // Escribir en input
        ->press('Button Text')       // Click en botón
        ->click('selector')          // Click en selector
        ->assertSee('text')          // Verificar texto visible
        ->assertDontSee('text')      // Verificar texto no visible
        ->waitForText('text')        // Esperar texto
        ->waitForLocation('/url')    // Esperar navegación
        ->screenshot('nombre');      // Tomar screenshot
});
```

Para más métodos, consulta la [documentación oficial de Pest Browser Testing](https://pestphp.com/docs/browser-testing).

## 🎨 Configuración Avanzada

### Configurar Navegadores

Por defecto, Pest usa Chromium. Puedes cambiar esto en `tests/Pest.php`:

```php
// En tests/Pest.php
uses()->beforeEach(function () {
    // Configuración adicional del browser
})->in('Browser');
```

## 📊 Reports y Debugging

### Ver Screenshots

Los screenshots se guardan automáticamente en failures:

```bash
# Ejecutar tests
./vendor/bin/pest --browser

# Screenshots se guardan en:
# tests/.pest/screenshots/
```

### Debugging

```bash
# Modo headed (navegador visible)
./vendor/bin/pest --browser --headed

# Con pausa para inspección
browse(function ($browser) {
    $browser->visit('/wp-admin/')
        ->pause(); // Pausar ejecución para inspeccionar
});
```

### Verbose Output

```bash
./vendor/bin/pest --browser -v
```

## 🔍 Selectores y Esperas

### Mejores Prácticas para Selectores

```php
browse(function ($browser) {
    // ✅ Bueno: Usar texto visible
    $browser->press('Publish');
    
    // ✅ Bueno: Usar atributos ARIA
    $browser->type('[aria-label="Add title"]', 'My Post');
    
    // ⚠️ Evitar: Selectores frágiles
    $browser->click('.wp-block-post-title');
});
```

### Esperas Explícitas

```php
browse(function ($browser) {
    $browser->visit('/wp-admin/post-new.php')
        ->waitForText('Add title')     // Esperar texto
        ->waitFor('.editor-post-title') // Esperar selector
        ->waitForLocation('/wp-admin/'); // Esperar URL
});
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
