# Clinic Theme Migration Runbook

This runbook is the execution order for adapting the next mirrored HTML folder into the clinic theme with minimum regressions.

## 1) Choose Source and Destination

1. Pick canonical source page in destination theme:
   - `themes/clinic_theme/index.php`
2. Pick the incoming folder to adapt:
   - Example: `themes/clinic_theme/new-folder/`

## 2) Convert HTML to PHP

Run from project root:

```bash
find themes/clinic_theme/new-folder -type f -name '*.html' -print0 | \
  xargs -0 -I{} bash -lc 'mv "$1" "${1%.html}.php"' _ {}
```

## 3) Rewrite Internal Links

Convert local `.html` links to `.php` inside converted files:

```bash
rg -l 'href="[^"]+\.html|src="[^"]+\.html|action="[^"]+\.html' themes/clinic_theme/new-folder/**/*.php
```

Then update only local paths (avoid absolute external URLs).

## 4) Inject / Sync Wrapper

Use the canonical wrapper from `themes/hospital_theme/index.php` pattern and adapt theme-specific values where needed.

Propagate wrapper safely:

```bash
php scripts/pih_propagate_wrapper.php \
  --source=themes/clinic_theme/index.php \
  --target-dir=themes/clinic_theme
```

Dry run mode:

```bash
php scripts/pih_propagate_wrapper.php \
  --source=themes/clinic_theme/index.php \
  --target-dir=themes/clinic_theme \
  --dry-run
```

## 5) Validate Syntax and Diagnostics

```bash
find themes/clinic_theme -type f -name '*.php' ! -path '*/wp-content/*' ! -path '*/wp-includes/*' ! -path '*/wp-json/*' -print0 | \
  xargs -0 -I{} php -l "{}" >/dev/null && echo "clinic php lint ok"
```

## 6) Runtime Validation (Home + Deep Route)

```bash
curl -sS "http://localhost/privateimaginghealthcare/index.php?theme_preview=clinic_theme" > "$TMPDIR/clinic_home.html"
curl -sS "http://localhost/privateimaginghealthcare/services/index.php" > "$TMPDIR/clinic_services.html"
```

Check expected markers:

```bash
rg -n "logo|favicon|wp-content/themes|portal/index.php|footer" "$TMPDIR/clinic_home.html" "$TMPDIR/clinic_services.html"
```

## 7) Footer / Header Structural Tweaks

If you remove a footer column, rebalance widths immediately and re-validate on deep routes.

If you move CTA elements (Patient Portal), ensure no duplicate insertion remains in menu/header.

## 8) Portal Isolation Rule

Portal pages should be standalone when public theme styling bleeds into workflow pages.

Current standalone portal entry:
- `portal/index.php`

## 9) Final Verification Checklist

1. Non-home pages load CSS and logo correctly.
2. No malformed absolute URLs.
3. Header/footer layout matches desired structure.
4. Forms still submit to original handlers.
5. `php -l` and diagnostics are clean.

## 10) Recommended Fast Commands

Wrapper propagation:

```bash
php scripts/pih_propagate_wrapper.php --source=themes/clinic_theme/index.php --target-dir=themes/clinic_theme
```

Lint all clinic files:

```bash
find themes/clinic_theme -type f -name '*.php' ! -path '*/wp-content/*' ! -path '*/wp-includes/*' ! -path '*/wp-json/*' -print0 | xargs -0 -I{} php -l "{}"
```
