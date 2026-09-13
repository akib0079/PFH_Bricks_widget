# PFH Bricks Widgets

Custom Bricks Builder elements for **productsforhome.nl**.

## What is here

| Path | |
|---|---|
| `pfh-bricks-widgets/` | the plugin |
| `dev/` | test harnesses and preview scripts |
| `dev/wp-testbed/` | a real WordPress + WooCommerce test install, and a log of what it has caught |

## Artwork

The fallback images in `pfh-bricks-widgets/assets/img/` are **not shipped in
the plugin zip**. Bundling them put 426K into a package that has to fit
through a host's upload limit, and a release came back as "Incompatible
Archive" because of it.

They are served from this repository over jsDelivr instead, from a pinned
tag so that pushing to `main` can never change what a live site is showing:

```
https://cdn.jsdelivr.net/gh/akib0079/PFH_Bricks_widget@<tag>/pfh-bricks-widgets/assets/img/<file>
```

Two ways to override that:

- **Drop the file into `assets/img/` in the installed plugin.** A local copy
  always wins, so a site can self-host any or all of them with no setting.
- **Filter the base URL**, to point at your own media library or CDN:

  ```php
  add_filter( 'pfh_widgets_image_base', function () {
      return 'https://example.com/wp-content/uploads/pfh-art/';
  } );
  ```

When the artwork changes: commit it, tag the release, and raise
`PFH_Widgets_Assets::IMAGE_REF` to that tag.

## Building a release

```bash
zip -rq9X pfhbrickswidgets.zip pfh-bricks-widgets \
  -x "pfh-bricks-widgets/assets/img/*" -x "*.DS_Store" -x "__MACOSX/*"
```

`dev/audit.php` fails if the packaged size approaches a host's upload limit.

## Tests

```bash
php dev/audit.php              # controls, tokens, style faults, package size
php dev/test-services.php      # permalinks, consent, documents, reviews
php dev/test-shop.php
php dev/test-card-reviews.php
```

The suites needing a real WooCommerce live in `dev/wp-testbed/`; see the
README there for how to stand the install up and what each one guards.
