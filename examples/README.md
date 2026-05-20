# Examples

Runnable scripts. Output is written to `examples/out/` (git-ignored).

```bash
composer install            # from the package root, first
php examples/01_save_png.php       # pie -> PNG          (needs ext-gd)
php examples/02_email_inline.php   # HTML email w/ inline PNG charts (needs ext-gd)
php examples/03_svg_string.php     # donut -> SVG        (no extensions needed)
```

If your PHP CLI lacks `ext-gd`, run the GD examples inside a container/image that has it.
