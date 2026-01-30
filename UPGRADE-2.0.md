# UPGRADE FROM 1.x TO 2.0

This guide helps you upgrade your project from version `1.x` to `2.0` of the **Storyblok Symfony Bundle**.

> ⚠️ Version 2.0 contains **backward-incompatible changes**. Please follow the instructions carefully to prevent runtime issues.

---

## ⚠️ BREAKING CHANGES

### 1. `RendererInterface::render()` Method Signature

The `render()` method in `RendererInterface` now **requires** two arguments:

```diff
- public function render(array|object $values): string;
+ public function render(array|object $values, array $context = []): string;
```

#### 🔧 How to upgrade:

Update all custom implementations of `RendererInterface`:

```php
use Storyblok\Bundle\Block\Renderer\RendererInterface;

final class MyCustomRenderer implements RendererInterface
{
    public function render(array|object $values, array $context = []): string
    {
        // your logic here
    }
}
```

> 📝 This was deprecated in version `1.10.0`. Ensure all calls to `render()` pass both `$values` and a second context argument.

---

### 2. `rich_text` Twig Filter: `array` Argument No Longer Supported

Previously, you could pass an `array` to the `rich_text` Twig filter:

```diff
- {{ block.content|rich_text }}
+ {{ block.content|rich_text }}
```

If `block.content` was an `array`, this **no longer works**.

#### 🔧 How to upgrade:

Ensure that the value passed to `rich_text` is a **Rich Text object**, not an array. Typically, this should be the raw `content` object from Storyblok's JSON (decoded):

```twig
{# ✅ Correct usage #}
{{ storyblok_rich_content|rich_text }}

{# ❌ Old/broken usage in 2.0 #}
{{ storyblok_rich_content|json_decode|rich_text }}
```

> If you were decoding JSON manually, stop doing so — the bundle already provides the decoded content as an object.

---

### 3. `smax_age` with `public: false` Now Throws an Exception

Setting `smax_age` with `public: false` in `storyblok.controller.cache` or `storyblok.cdn.cache` now throws an `InvalidConfigurationException` instead of triggering a deprecation warning.

```yaml
# ❌ This configuration is no longer allowed
storyblok:
    controller:
        cache:
            public: false
            smax_age: 3600
```

#### 🔧 How to upgrade:

The `s-maxage` directive is only applicable to shared caches (CDN/proxy), which require public caching. Either:

1. **Remove `smax_age`** if you want private caching:
   ```yaml
   storyblok:
       controller:
           cache:
               public: false
               max_age: 3600
   ```

2. **Use `public: true`** (or remove it) if you want shared cache caching:
   ```yaml
   storyblok:
       controller:
           cache:
               public: true
               smax_age: 3600
   ```

> 📝 This was deprecated in version `1.16.0`.

---

## 🧪 Run the Symfony Deprecation Detector

Run this command to see what needs to be updated before upgrading:

```bash
bin/console debug:container --deprecations
```

Make sure to address any deprecation notices introduced in `1.10.0` and `1.16.0`.

---

## ✅ Recommended Steps

1. Upgrade to the latest `1.x` version.
2. Fix all deprecation warnings.
3. Update your code to follow the new interface and filter requirements.
4. Require `storyblok/symfony-bundle:^2.0` in your `composer.json`.
5. Clear your Symfony cache:
   ```bash
   bin/console cache:clear
   ```
6. Run your test suite.

---

## 📦 Composer

Update your dependency in `composer.json`:

```bash
composer require storyblok/symfony-bundle:^2.0
```

---

If you run into any issues, feel free to open a GitHub issue or reach out to the maintainers.

Happy coding! 🧑‍💻  
— The Storyblok & SensioLabs Team
