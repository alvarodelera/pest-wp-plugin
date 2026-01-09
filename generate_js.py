import json
import os
import glob

# The map from the HTML
pages_map = {
    'home': 'README.md',
    'installation': 'installation.md',
    'getting-started': 'getting-started.md',
    'configuration': 'configuration.md',
    'factories': 'factories.md',
    'expectations': 'expectations.md',
    'authentication': 'authentication.md',
    'database-isolation': 'database-isolation.md',
    'browser-testing': 'browser-testing.md',
    'rest-api-testing': 'rest-api-testing.md',
    'ajax-testing': 'ajax-testing.md',
    'architecture-testing': 'architecture-testing.md',
    'mocking': 'mocking.md',
    'fixtures': 'fixtures.md',
    'snapshots': 'snapshots.md',
    'visual-regression': 'visual-regression.md',
    'accessibility-testing': 'accessibility-testing.md',
    'woocommerce': 'woocommerce.md',
    'gutenberg': 'gutenberg.md',
    'ci-cd': 'ci-cd.md',
    'migration': 'migration.md'
}

base_path = 'docs/guide/'

embedded_pages = {}

for key, filename in pages_map.items():
    filepath = os.path.join(base_path, filename)
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
            embedded_pages[key] = content
    except Exception as e:
        print(f"Error reading {filepath}: {e}")
        embedded_pages[key] = f"# Error\nCould not load {filename}"

# Create the JS string
js_content = "        const embeddedPages = " + json.dumps(embedded_pages, indent=4) + ";\n"

print(js_content)
