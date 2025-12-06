# Custom Expectations

Custom WordPress expectations organized by category.

## Structure

- **posts.php** - Post status expectations (toBePublished, toBeDraft, etc.)
- **errors.php** - WP_Error expectations (toBeWPError, toHaveErrorCode)
- **metadata.php** - Metadata expectations (toHaveMeta, toHaveMetaKey, toHaveUserMeta)
- **hooks.php** - Hook expectations (toHaveAction, toHaveFilter)
- **terms.php** - Term and taxonomy expectations (toHaveTerm, toBeRegisteredTaxonomy)
- **users.php** - User capabilities expectations (toHaveCapability, toHaveRole, can)
- **shortcodes.php** - Shortcode expectations (toBeRegisteredShortcode)
- **options.php** - Options and transients expectations (toHaveOption, toHaveTransient)
- **post-types.php** - Post type expectations (toBeRegisteredPostType, toSupportFeature)

## Usage

All expectations are automatically registered when the plugin loads. Simply use them in your tests:

```php
expect($post)->toBePublished();
expect($user)->toHaveRole('administrator');
expect('my_shortcode')->toBeRegisteredShortcode();
```
