# Products For Home – Bricks Widgets

Custom [Bricks Builder](https://bricksbuilder.io/) elements for the
[productsforhome.nl](https://productsforhome.nl/) redesign.

Two halves:

* **Twenty-one Bricks elements**, all fully dynamic and editable from the Bricks
  panel — no hard-coded content, no template edits.
* **Four store services** under a **Products For Home** admin menu, replacing
  Complianz, Premmerce Permalink Manager, WebwinkelKeur and PDF Invoices &
  Packing Slips. See [Store services](#store-services).

### Elements


| Element | Panel category | What it does |
| --- | --- | --- |
| **PFH Header** | Products For Home | Announcement bar, sticky header, hover mega menu, search popup, WooCommerce cart drawer, account link, mobile drawer |
| **PFH Hero Slider** | Products For Home | Crossfading hero with staggered reveals, per-slide imagery, floating decorations, a live WebwinkelKeur rating row, vertical dots and a scrolling marquee bar |
| **PFH Category Slider** | Products For Home | Drag-to-scroll card slider with a custom scrollbar and hover effects |
| **PFH Product Slider** | Products For Home | WooCommerce sale slider: sale badge, hover add-to-cart, review row, custom scrollbar |
| **PFH Product Grid** | Products For Home | The same product card in a plain multi-column grid under a centred heading |
| **PFH Featured Section** | Products For Home | Full-bleed promo band: patterned background, masked photo to the screen edge, scrolling notice bar |
| **PFH Featured Section — Olive** | Products For Home | The mirrored twin: image left, text right, green ground, floating branch |
| **PFH Info Section** | Products For Home | Alternating photo/text rows on a gradient ground, sides zig-zag automatically |
| **PFH Highlighted Features** | Products For Home | Bento grid of photo and text tiles with per-tile spans, staggered reveal |
| **PFH Review Slider** | Products For Home | Live WebwinkelKeur reviews in a paged card grid, with a manual fallback |
| **PFH Call To Action** | Products For Home | Closing card that overlaps the footer via a negative margin and its own stacking context |
| **PFH Product** | Products For Home | The whole top of a product page: breadcrumbs, gallery with tag, title, rating, price with the amount saved, variant pills, what is in the box, quantity, add to cart and the promises |
| **PFH Product Tabs** | Products For Home | Omschrijving, Ingredienten, Houdbaarheid and Voedingswaarden, with the steps panel beside them — all of it from fields on the product |
| **PFH Product Highlight** | Products For Home | Static banner for one offer — every word, price and link typed in the panel, overlaps the footer |
| **PFH Recently Viewed** | Products For Home | The product slider, showing only what this visitor has already looked at; renders nothing when that is nothing |
| **PFH Rating Badge** | Products For Home | Inline "Excellent 9,7 | 270 reviews on WebwinkelKeur", live from the API |
| **PFH Shop Header** | Products For Home | Breadcrumbs, collection title, short intro and banner — every field dynamic-data ready for ACF |
| **PFH Shop Archive** | Products For Home | Category pills, product count, filter button, product grid and pagination, all filtering over AJAX with the state in the URL |
| **PFH Collection Description** | Products For Home | Category copy, clamped to a line count with a read-more button that only appears when it overflows |
| **PFH Notice Band** | Products For Home | Announcement strip: heading, line of copy, one action |
| **PFH Counter Row** | Products For Home | Figure strip with dividers; any figure can come from the live WebwinkelKeur feed |
| **PFH FAQ** | Products For Home | `<details>` accordion that works without JavaScript, emitting FAQPage structured data |
| **PFH Footer** | Products For Home | Link columns (WP menus or manual), newsletter block, brand row with social icons, review badge + copyright |

---

## Install

1. Copy the `pfh-bricks-widgets` folder to `wp-content/plugins/`
   (or zip it and upload it via **Plugins → Add New → Upload Plugin**).
2. Activate **Products For Home – Bricks Widgets**.
3. In Bricks, edit your **Header** template → the panel now has a
   **Products For Home** category containing **PFH Header**. Same for the
   **Footer** template and **PFH Footer**.

**Requirements:** WordPress 6.0+, PHP 7.4+, Bricks. WooCommerce is optional —
without it the cart drawer degrades gracefully and the mega menu falls back to
its Custom-list source.

---

## Store services

Five modules that exist to take plugins off the site, not to add features.
Each is a tab under **WP Admin → Products For Home**.

| Tab | Replaces | Off by default? |
| --- | --- | --- |
| **Cookie consent** | Complianz Privacy Suite | No — on, with blocking |
| **Permalinks** | Premmerce Permalink Manager | No — defaults match the live URLs |
| **WebwinkelKeur** | WebwinkelKeur | Badge is off; credentials empty |
| **Instagram** | Smash Balloon / Spotlight | Token empty — the strip falls back to Bricks |
| **Invoices** | PDF Invoices & Packing Slips | No — but nothing is numbered until an invoice is opened |

### Cookie consent

A bottom bar with **no overlay and no scroll lock** — the visitor can keep
shopping while it is up. Corner-card variants are available.

What makes it a consent layer rather than a banner:

* Matching `<script>` and `<iframe>` tags are rewritten server-side to
  `type="text/plain"` before they reach the browser, so Klaviyo, the Meta
  pixel, Triple Whale and PixelYourSite genuinely cannot run before consent.
  The lists are editable per category.
* **Google Consent Mode v2** defaults are published inline at the very top of
  `<head>`, before any Google tag loads. Google's tags are governed by those
  signals rather than by blocking — which is why `googletagmanager` is *not*
  in the blocklist by default. Blocking it outright loses conversion modelling
  in Google Ads; there is a switch for it if legal asks.
* Each choice is recorded (timestamp, categories, policy version, truncated
  IP) in `{prefix}pfh_consent_log`, so consent can be evidenced.

Cache-safe by construction: the markup is identical for every visitor and the
state lives in a cookie read by JavaScript, so LiteSpeed or Kinsta full-page
caching changes nothing.

Category descriptions sit behind a disclosure so the panel stays about three
rows tall. *Category descriptions → show them expanded* opens them all.

Reopen the panel from anywhere with `[pfh_cookie_settings]`, a link to
`#pfh-cookie-settings`, or `window.pfhConsentApi.open()`. Other code can ask
`pfhConsentApi.has('marketing')` or listen for the `pfh:consent` event.

**What this does not replace:** Complianz's generated legal documents. The
cookie and privacy policy pages stay as they are and still need an owner.

### Permalinks

Defaults reproduce the configuration live on productsforhome.nl — **full
category path**, **bare product slug**, **Yoast primary category** — so
switching over changes no URL.

Because the brief's §4 says any URL that resolves today must resolve after
launch, every legacy shape still resolves and then 301s to the canonical one:

| Requested | Result |
| --- | --- |
| `/rauwe-honing/` | 200 — canonical |
| `/product/rauwe-honing/` | 301 → `/rauwe-honing/` |
| `/griekse-producten/honing/rauwe-honing/` | 301 → `/rauwe-honing/` |
| `/griekse-producten/honing/` | 200 — canonical category |
| `/honing/` | 301 → `/griekse-producten/honing/` |
| `/product-category/griekse-producten/honing/` | 301 → canonical |
| `/made/up/path/` | 404, as it should |

Pagination, feeds and embeds survive. Pages and posts keep precedence over
products, always.

The tab shows two live panels: the real URLs a few products and categories
resolve to right now, and a slug-collision scan. The sample line under each
dropdown is only a mock-up — the example panel is what confirms a setting took
effect.

The archive's filter state travels in the URL under namespaced names —
`pfh_cat`, `pfh_page`, `pfh_sort`, `pfh_min`, `pfh_max`, `pfh_sale`,
`pfh_stock`, `pfh_tax_<taxonomy>`. That is not decoration: WordPress reserves
`page`, `cat`, `order` and `orderby` as public query vars, and a bare `?page=3`
on a page means "the third page of the post content", which empties the archive
instead of paging it. PHP publishes the map to the browser so the two halves
cannot drift.

**Before go-live:** run the slug-collision scanner on the tab. With the product
base removed, a product and a page can claim the same URL — the page wins and
the product silently disappears.

Resolution happens in `parse_request` rather than through a catch-all rewrite
rule, because a rule broad enough to match a bare product slug also swallows
every page on the site.

### WebwinkelKeur

Webshop ID and API key move here and become the single source for the review
slider, the inline rating badge and the new sticky badge — one cached call
(12 h, refreshed daily by cron) instead of one per widget. The tab shows a live
connection check.

`PFH_WEBWINKELKEUR_ID` and `PFH_WEBWINKELKEUR_CODE` in `wp-config.php` still
take precedence, which is the preferred way to hold the key on production: a
key in the database ends up in every backup and export.

The sticky badge is an edge tab that expands to a panel with the live score,
star row, review count, editable trust points and an outbound link. Off by
default.

### Live figures, everywhere

`PFH_Widgets_Reviews::figures()` is the single place that turns the cached feed
into a display-ready score and count. The hero rating row, the footer badge,
the inline Rating element and the sticky badge all read it, so one feed cannot
show three different numbers on one page.

The product card's review row has three sources: typed text, **the shop's live
WebwinkelKeur rating** on every card, or **each product's own WooCommerce
rating**. WebwinkelKeur rates the shop rather than individual products, so the
shop rating is the real figure available for every card; per-product needs
WooCommerce reviews on the products themselves. When a product has none, the
row falls back to the shop figure, hides, or shows the typed text — your
choice, because a new product showing nought stars reads worse than no row.

Each surface picks its own scale: the hero shows the score out of 5 (`4.9`),
the footer out of 10 (`9.7`). Both come from the same 9.7 rating.

In the hero's **Rating row** group, put `%score%`, `%count%` or `%total%` in a
slide's rating text and they are replaced with the real figures — `%count%`
honours the "round down to" setting for a `270+` style label, `%total%` is
always exact. **Draw the stars from the score** swaps the star image for a row
that fills to the real rating, rounded to the nearest half star; at 9.7/10 that
is five full stars, so the banner is unchanged until the score actually moves.

Whatever is typed into the element is the fallback: an unreachable feed, or the
Bricks builder, shows exactly that. Editing never waits on a remote call.

**WebwinkelKeur icon** — upload your own from the media library, or point at a
URL. The plugin ships a transparent PNG (`assets/img/pfh-webwinkelkeur.png`)
which is used when both are empty. Prefer a transparent PNG or an SVG: a JPEG
carries its background with it and shows as a solid square against the white
panel. **Tab background** and **Tab score colour** are there so a light icon can
sit on a dark tab or the other way round.

### Invoices

Self-contained PDF writer — no library bundled. Standard Helvetica with real
font metrics, so wrapping is accurate; multi-page with repeating table headers
and page numbers; logo embedded through GD.

Totals are read back from WooCommerce's own `get_order_item_totals()` rather
than recalculated, so the staffel discount, loyalty redemption, shipping and
VAT on the invoice are exactly what the customer saw at checkout.

* Invoice and packing-slip buttons on the order screen, bulk export from the
  orders list, attachment to the configured WooCommerce emails, and a download
  in My Account.
* Numbers are allocated **once** and never change. The counter is incremented
  inside MySQL (`LAST_INSERT_ID(option_value + 1)`), so two orders completing
  in the same second cannot be handed the same number.
* Documents are rendered on request and streamed — nothing is written under
  `wp-content`, so there is no directory of customer invoices at a guessable
  URL. Email attachments go to the system temp directory and are unlinked on
  shutdown.

**Before go-live:** set **Start at** to continue the existing series. Leaving
it at 1 produces duplicate invoice numbers in the books.

Text is encoded to CP1252 (WinAnsi), which covers Dutch and English. Greek or
Cyrillic in a product name would need an embedded TrueType font.

---

## PFH Header

### Structure

```
.pfh-header                  ← sticky wrapper
├── .pfh-topbar              ← announcement bar (rotates if >1 message)
├── .pfh-bar
│   └── .pfh-bar__inner      ← burger · logo · nav · action icons
│       └── .pfh-nav__item
│           └── .pfh-mega    ← full-width panel, absolute to .pfh-bar
└── .pfh-portal              ← moved to <body> on init
    ├── .pfh-scrim           ← dims the page behind an open mega menu
    ├── .pfh-search          ← search popup
    ├── .pfh-drawer--right   ← cart drawer
    └── .pfh-drawer--left    ← mobile menu
```

The overlays are relocated to `<body>` by the script so a transformed ancestor
(a Bricks scroll effect, a theme wrapper) can never clip them. Their styling
travels with them as inline CSS custom properties, so nothing breaks in the move.

### Mega menu

Each row of **Navigation → Menu items** can enable a mega menu and choose where
its cards come from:

- **Product categories** — direct children of a chosen parent, or an explicit
  ordered list of IDs/slugs in *Only these category IDs / slugs*
  (e.g. `honing, olijfolie, 42`). Card image = the category thumbnail.
- **Products** — from one category, ordered by menu order / newest / title /
  best selling / random. Card image = the product image.
- **Custom list** — one card per line: `Title | URL | Image URL`.

Opens on hover (with an anti-flicker delay) or on click. Keyboard accessible:
focus opens the panel, `Escape` closes it, and focus leaving the item closes it.
On touch devices the first tap opens instead of navigating.

### Search popup

WordPress search form scoped to products, everything, or blog posts. Live
results while typing are on by default (debounced, request-cancelling, nonce
guarded); switch them off to get a plain form. Submitting goes to the normal
WordPress search results page, so nothing depends on JavaScript.

### Cart drawer

Slides in from the right, backed by WooCommerce. Quantity steppers and remove
buttons update over AJAX and re-render the drawer server-side, so prices,
coupons and taxes always come from WooCommerce itself. The header badge also
updates through WooCommerce's own `woocommerce_add_to_cart_fragments`, so it
stays correct even when a product is added by another plugin's script.

Set **Cart icon behaviour → Go to cart page** to skip the drawer entirely.

---

## PFH Hero Slider

### Structure

```
.pfh-hero                    ← full-bleed section, background image + overlay
├── .pfh-hero__filters       ← SVG cutout filters (only used by flattened JPGs)
├── .pfh-hero__bg            ← background layer, crossfades on per-slide override
├── .pfh-hero__decor         ← section decorations (lemon, olive branch)
├── .pfh-hero__inner
│   └── .pfh-hero__viewport  ← all slides share one grid cell and crossfade
│       └── .pfh-hero__slide ← text column + media column
├── .pfh-hero__dots          ← anchored to the section edge, not the container
└── .pfh-hero__marquee       ← scrolling bar along the bottom
```

Slides are stacked in a single grid cell rather than translated on a track, so
the section is always as tall as the tallest slide and each slide's parts can
run their own staggered reveal.

### Per-slide content

Everything on a slide is its own repeater field: eyebrow, title, description,
two buttons, rating text, avatars, featured image, two floating images (each
with width, X/Y position and a front/behind toggle), a background override and
a text-colour override.

Titles keep line breaks and accept `<em>…</em>` for the italic accent, matching
the design's *Griekenland*.

Avatars work two ways: a **bulk image** holding the whole cluster, or a list of
image URLs (one per line) rendered as overlapping circles. The list wins when
both are set.

### Motion

Reveal effects: rise + fade (default), mask wipe, slide from left, zoom, blur,
fade, or none — with separate duration, distance and per-element stagger, plus
its own effect for the featured image. Autoplay pauses on hover, on focus and
when the tab is hidden; the active dot doubles as a progress ring.

Everything respects `prefers-reduced-motion`: no autoplay, no transforms, no
bobbing. Arrow keys work when the slider has focus, swipe works on touch, hidden
slides are `aria-hidden` and taken out of the tab order, and slide changes are
announced through a live region.

The reveal never depends on `IntersectionObserver` or `requestAnimationFrame`
alone — a geometry check, a scroll listener and a timeout all race to reveal the
first slide, so the content can never end up stuck invisible in an embedding
context where those callbacks do not fire.

### Figma values

| Figma | Control | Default |
| --- | --- | --- |
| Title — Playfair Display 60 / 115% / `#484C4D` | Typography → Title family / size / line height / colour | `Playfair Display` / `60` / `1.15` / `#484c4d` |
| Description — Outfit **Light** 18 / 140% / `#66758E`, W412 | Typography → Description size / weight / line height / colour / max width | `18` / `300` / `1.4` / `#66758e` / `412` |
| Rating — Playfair Display 14 / 100% / `#000000` | Rating row → font family / size / colour | `Playfair Display` / `14` / `#000000` |
| Stars 91 × 16 | Rating row → Stars width | `91` |
| Shop Now — 143 × 48, r70, `#7CAEB2`, pad 26/14, gap 10 | Buttons → height / padding / radius / background / gap | `48` / `26` / `70` / `#7caeb2` / `10` |
| Learn More — 152 × 48, white, 1px `#5F6D46` | Buttons → Secondary background / text / border | `#ffffff` / `#5f6d46` / `#5f6d46` |
| Marquee bar | Marquee bar → background / height / size | `#7caeb2` / `40` / `16` |

Rendered widths come out at 141 and 153 against the Figma's 143 and 152.

The section decorations (lemon bottom-left, olive branch bottom-right) sit in
**Section decorations**, not on the slides, because they stay put while the
slides change. Each has width, X/Y position, opacity and a hide-on-mobile
toggle. **Media → Horizontal / Vertical offset** nudges the product image if it
should sit further right than the content column allows.

### Flattened images

The `.jpg` copies of the hero art are transparent PNGs that were flattened onto
solid black. The element defaults point at the **`.png` originals** WordPress
kept alongside them, which have real alpha:

```
Group-1-1.png · pngwing.com-32-1.png · pngwing.com-26-1.png · Frame-16.png
```

If you ever have to use a flattened JPG, every image control has a **background**
select — *Cut out a black background* recovers the cutout with an SVG
luminance-to-alpha filter (tune it with **Media → Cutout edge strength**). It
works, but leaves a faint dark fringe on soft edges, so a real PNG is always
better. `Group-1-1.png` is 3.8 MB; compressing it to WebP is worth doing.

---

## PFH Category Slider

### How the slider works

The track is a **native `overflow-x` scroller**. Pointer drag and the custom
scrollbar both write to the same `scrollLeft`, so trackpad gestures, touch
momentum, shift-scroll and keyboard scrolling keep working without any of it
being reimplemented — and the slider still works with JavaScript disabled.

- **Drag** with the mouse (touch keeps native momentum). The click that ends a
  drag is swallowed so a card never opens by accident.
- **Custom scrollbar** under the track, deliberately minimal: a 2px hairline
  with the handle at 30% opacity so it reads as a progress hint rather than a
  control, lifting to 55% and 4px only on hover. It is still draggable, the
  track is clickable to jump, arrow/Home/End work when focused, it carries
  `role="scrollbar"` with a live `aria-valuenow`, and it hides itself when
  everything already fits.
- **Bleed** — cards start at the content column and the next one peeks past the
  right of the screen, as in the design. Turn it off for a boxed track.
- Optional snap, and an optional "vertical wheel scrolls the track" mode that is
  off by default so a normal mouse wheel still scrolls the page.

### Cards

Each card is a repeater row: category label, headline, artwork, link label and
link, plus a fallback background colour, artwork position and a text-colour
override. Headlines take `<strong>…</strong>` for the emphasised part, matching
the design.

**Cards per view** is set separately for desktop / tablet / mobile and accepts
decimals — `3` fills the content column exactly (card width comes out at 361px
at a 1110px container, matching the Figma), `1.15` on mobile lets the next card
peek.

### Hover

Default is **lift + gentle grow**: the card rises 8px and scales to 103%, a soft
shadow fades in, the arrow slides diagonally and the link underline wipes out.

Scaling the *whole card* rather than the artwork inside it matters — the
background is re-laid-out at the new size, so the product keeps its framing and
nothing is cropped. The separate *artwork zoom* option scales inside the card's
clip, which crops by definition; it is there if you want it.

Because `overflow-y` on the track has to be `hidden` (it sits next to
`overflow-x: auto`), a lifted card and its shadow would be clipped. **Hover →
Headroom for the effect** reserves room around the track and takes it straight
back out of the layout with a negative margin, so it adds no whitespace.

### The artwork's baked-in corners

The supplied card JPGs have their rounded corners **baked in against black** —
measured across all five, between 66px and 72px in a 1082px-wide image, so up to
6.65% of the card width (≈24px at the rendered size). The card radius defaults to
**30px**, which clears that, and it *also* scales with the card using container
query units — **Card style → Artwork corner trim**, default `7%` of the card
width — so the corners stay clean at any card size.

The cleanest fix is on the asset side: export the cards as **flat rectangles**
and let CSS round them, then set the trim to `0`. Transparent `.png` versions of
all five cards exist on the server too, but they are 0.7–1.5 MB each (~6 MB for
the set) against ~55 KB for the JPGs, so the JPGs are the default.

---

## PFH Product Slider

Shares the drag-slider engine with the category slider (`pfh-slider.js`), so
drag, the custom scrollbar, keyboard scrolling and touch momentum behave
identically. See that section for how the slider itself works.

### Where the products come from

**Products → Show**: products on sale, featured, a category, best selling,
newest, specific IDs, or **manual cards** — the last one needs no WooCommerce at
all, which keeps the element usable in the builder and on a staging site without
a shop. Order, limit and a hide-out-of-stock toggle are all controls.

Card width comes out at 303px at 3.5 per view in a 1110px container, matching
the design's peeking fourth card.

### The sale badge, selectively

**Sale badge → Show it on** decides which cards get badged:

- *Products that are actually on sale* — WooCommerce decides (default)
- *Only the products I pick* — paste product IDs into the field below it
- *Every card*

Label, colours, size, radius, padding and corner offset are all controls. The
Figma badge is Outfit Medium 12 on `#7d9569`, which is the default.

### Add to cart on hover

The button sits inside the image box and is revealed on hover — fade + rise by
default, with pop and plain-fade alternatives, or always-visible.

It is a real WooCommerce **AJAX add-to-cart** button (`ajax_add_to_cart` with
`data-product_id`), so it fires the `added_to_cart` event that the PFH Header's
cart drawer already listens for — adding from the slider opens and refreshes the
drawer with no extra wiring. Variable and grouped products link to the product
page instead, since they need options chosen.

Two accessibility details: the button is revealed by `:focus-visible` as well as
hover so it is reachable by keyboard, and on small screens it is always visible,
because a touch device has no hover state to reveal it with.

### Reviews

**Reviews → Source** is *Same text on every card* by default: a fixed star count
and a fixed label ("124 reviews"), linking to the WebwinkelKeur page for this
shop, which is the default link. Switch to *Each product's own rating* once
WooCommerce reviews are populated and each card uses its own average and count.

Stars are drawn as two layers with the filled one clipped by a percentage, so
half stars render exactly rather than being rounded.

### Figma values

| Figma | Control | Default |
| --- | --- | --- |
| Badge — Outfit Medium 12 | Sale badge → Font size | `12` |
| Title — Outfit Medium 20, `Olive Green / green-900` | Card typography → Title size / weight / colour | `20` / `500` / `#22301c` |
| Price — Outfit Medium 14, `Olive Green / green-700` | Card typography → Price size / weight / colour | `14` / `500` / `#5f6d46` |
| Image box 310 × 352 | Card → Image box ratio | `310 / 352` |

The two green tokens are read off the exports rather than the palette — if you
have the exact `green-900` and `green-700` hexes, paste them into those two
colour controls.

Titles are clamped to two lines by default (**Card typography → Title lines
before trimming**) so every card in a row keeps the same height without any
measuring.

---

## PFH Product Grid

The **same card** as the product slider — literally: the card markup, the
WooCommerce query, the sale badge, the hover add-to-cart and the review row all
come from `PFH_Product_Card_Trait`, shared by both elements. A change to the
card lands in both at once. Only the layout and heading differ.

The root carries `pfh-prod` as well as `pfh-pgrid`, so it inherits the card
tokens and styles and adds the grid on top.

Columns are set per breakpoint (4 / 2 / 2 by default) with separate column and
row gaps. The heading is centred by default with an optional left alignment,
and takes `<em>…</em>` for the underlined italic accent.

### Figma values

| Figma | Control | Default |
| --- | --- | --- |
| Heading — Playfair Display **SemiBold 28 / 115 %**, block 481 × 64 | Section heading → family / size / weight / line height / max width | `Playfair Display` / `28` / `600` / `1.15` / `481` |
| 4 columns, ~264 px cards | Grid → Columns / Gap | `4` / `18` |

At a 1110 px container this renders the heading block at exactly 481 × 64 and
the cards at 264 px, matching the design.

---

## PFH Featured Section

A full-bleed band: the patterned background covers the section, the text column
sits inside the normal content container, and the product photo runs to the edge
of the screen. The **curved edge is the image's own alpha channel**, so nothing
is clipped in CSS — keep a transparent PNG in the Image control.

The photo is positioned absolutely on desktop (side and width are controls,
57 % from the right by default) and drops below the text as a full-width band on
mobile. It is rendered *after* the content in the DOM: absolute positioning
ignores source order on desktop, and it gives the correct stacking on mobile
without relying on `order`, which does nothing once the section becomes a block.

### Notice bar

A pure-CSS marquee along the bottom — no JavaScript. Items are one per line, the
separator is an image (the bee SVG by default), and **Repeats per pass** controls
how many times the list is duplicated before it loops; raise it if a gap appears
on a very wide screen. Direction, speed, pause-on-hover, colours, height and
spacing are all controls.

**Bar sits over the section** pins it to the bottom edge as in the design, and
reserves the matching space so the photo and content are never covered. Turn it
off to stack the bar underneath instead.

### Figma values

| Figma | Control | Default |
| --- | --- | --- |
| Heading — Playfair Display Medium **italic** 36 / 105 % | Typography → family / size / weight / italic / line height | `Playfair Display` / `36` / `500` / on / `1.05` |
| Body — Outfit 14 / 150 % | Typography → Text size / line height | `14` / `1.5` |
| Button — 166 × 40, radius 70, fill `#FFFFFF` at **80 %**, 1px `#F2E0BF` | Button → height / radius / background / opacity / border | `40` / `70` / `#ffffff` / `80` / `#f2e0bf` |

The button's fill is a pseudo-element rather than opacity on the button itself,
so the pattern shows through the 80 % white while the label stays full strength.

Colours are read off the exports: heading `#5c2e15`, body `#7e4a28`, button text
`#ad6738` (matching the supplied arrow's stroke), bar `#f5dfa8` with `#9b4713`
text (matching the bee). All are controls.

The supplied `Mask-group-1.png` is **2.98 MB** — worth converting to WebP before
launch.

### Stacking on mobile

Below 992px the section stacks. **Image → Mobile order** decides what comes
first — *Image first, then text* by default, matching the design. The section
stays a flex column there rather than switching to a plain block, which is what
makes the order a control at all: `order` does nothing on a block container.

### Floating decoration

**Image → Floating decoration** adds an image on top of the section (the olive
branch in the olive variant) with X/Y position, width, rotation, opacity and a
hide-on-mobile toggle. It sits above the photo and below the text.

---

## PFH Featured Section — Olive

The mirrored twin of the honey band: image on the left, text on the right, green
ground, floating olive branch, leaf separator in the notice bar.

It is a **subclass** of the featured section, not a second copy — same controls,
same CSS, same render, only re-seeded defaults. Anything fixed in the featured
section applies here automatically. Every default is still a control, so it can
be pushed anywhere from the panel.

`#697C66` is the shared accent — it is the fill of the supplied `mdi_leaf.svg`
and the stroke of `arrow-up-right-01-1.svg` — and it is used for the section
ground, the button label and arrow, and the notice-bar text. The bar text is
Outfit SemiBold 20 per Figma.

If you want a third variant, duplicating this file and changing
`olive_defaults()` is about 60 lines; or save a configured instance as a Bricks
global element, which needs no code at all.

---

## PFH Info Section

Two (or any number of) alternating rows over the soft mint gradient: a
square-ish photo on one side, a Playfair heading with an italic underlined
accent, body copy and a pill button on the other.

### Rows

Rows are a **repeater**, so this is not a fixed two-block section — add a third
and it keeps zig-zagging. Each row carries its own image, alt text, image
position, heading, body copy, button label and link.

Sides alternate automatically. *First row image side* sets where the run
starts; any individual row can override it with its own *Image side*, and
leaving that on **Alternate automatically** hands the row back to the pattern.

**Heading** keeps its line breaks, and the italic underlined part is whatever
you wrap in `<em>…</em>` — the same convention as the category slider:

```
The tastiest Greek
<em>delicatessen shop</em>
```

**Text** splits into paragraphs on **blank lines**. Single newlines are ignored
on purpose, so the copy reflows with the column instead of carrying the
author's line breaks down onto a phone. Inline links and `<strong>` are allowed.

A row with no link renders its button as a `<span>` rather than a dead `<a>`.

### Figma values

The 1440 frame pins the whole section, and the background artwork confirms it:
1920 × 1840 exported at 1.333× is **1440 × 1380**, and

```
173 (padding) + 500 + 34 (row gap) + 500 + 173 (padding) = 1380
```

| Thing | Figma | Control |
| --- | --- | --- |
| Content column | 1110 (x 165 → 1275) | Content width |
| Photo | 522 × 500 | Image width / height |
| Gap photo ↔ text | 72 | Gap image ↔ text |
| Text measure | 464 | Text column max width |
| Section padding | 173 | Top / bottom padding |
| Row gap | 34 | Gap between rows |
| Heading | Playfair Display 28 / 115%, block 326 × 64 | Typography |
| Body | Outfit Light 16 / 150% | Typography |
| Button | 166 × 40 | Button |

The column split is authored in px but **emitted as a percentage of the content
width** (522/1110 = 47.027%, 72/1110 = 6.4865%). That is exact at 1110 and stays
in proportion at every width below it, so the photo never fights the text for
room. Its height comes from `aspect-ratio`, not a fixed value, so it tracks.

### The artwork's baked-in corners

Both supplied photos are flattened JPGs whose own rounded corners are baked onto
**black** at roughly a 20px radius. Clipping the media box at the section's 30px
radius cuts those corners away — which is why *Corner radius* must stay at or
above ~20 for this artwork. Drop in transparent PNGs and the radius becomes a
free choice again.

### Responsive

- **≥ 1200** — as designed.
- **992–1199** — same layout, padding eases to 110.
- **768–991** — still side by side, type steps down to the mobile sizes.
- **≤ 767** — stacked. The row becomes a flex column capped at the image width
  and centred, so a tablet gets a 522px photo rather than a 700px-tall one.
- **≤ 575** — tighter padding and button.

*Mobile order* controls the stacked sequence: **Image first** (default), **Text
first**, or **Follow each row's desktop side** — which keeps the zig-zag reading
order, image-first for image-left rows and text-first for image-right rows.

The row stays a flex column when stacked rather than switching to `display:
block`, because `order` does nothing on a block container.

### Hover

Off by default — the design has no hover state. *Hover effect* offers a gentle
zoom or a lift with a shadow; both stay inside the clipped media box, so nothing
crops.

---

## PFH Highlighted Features

A header row (heading left, intro right) over a bento grid of tiles. Every tile
either shows a photo or lays a title and copy over a card background, and
carries its own column and row span.

### Tiles

Tiles are a **repeater** and fill the grid in order, so adding or removing one
reflows the mosaic — nothing is positioned by hand. Each row has artwork, alt
text, artwork position, title, copy, its own title/text/background colours,
a column span, a row span, text position (top / middle / bottom), a measure
override and a link.

A tile with no title and no text renders as a **photo tile** (no padding, and
its alt text goes to screen readers, since the artwork is a background). A tile
with a link becomes a real `<a>`; one without stays a `<div>`.

The design's seven tiles ship as the defaults:

| # | Tile | Span |
| --- | --- | --- |
| 1 | *Pure Goodness* card | 1 × 1 |
| 2 | *Crafted For* card | 1 × 1 |
| 3 | Olive oil in hand | 1 × 2 |
| 4 | Juices against the sky | 1 × 2 |
| 5 | Honey jars | 1 × 1 |
| 6 | *Pure goodness* card | 1 × 1 |
| 7 | Three juices | 1 × 1 |

> **Tile 4 has no artwork yet** — the juices-against-the-sky photo was not in
> the supplied set. It renders as a pale blue placeholder until an image is
> added to that repeater row.

The three text cards use **pre-composed backgrounds**: the honey dipper, the
lemon and the olives are baked into the supplied artwork rather than being
separate layers. Because the card ratio (1.524) is slightly wider than the
artwork's (1.585), `cover` trims about 4% horizontally — so those three default
to `right center`, which takes the trim off the empty left side and leaves the
decoration whole.

### Figma values

| Thing | Figma | Control |
| --- | --- | --- |
| Content column | 1110 | Content width |
| Columns / gap | 3 / 20 → column 356.67 | Grid |
| Row | 234 | Row height |
| Section | 1148 | 140 + 82 + 56 + 742 + 128 |
| Heading | Playfair Display 36 / 115%, block 417 × 82 | Typography |
| Intro | Outfit Light 14 / 140%, 352 wide | Typography |
| Tile title | Playfair Display 24 / 120%, `#49492B` | Typography |
| Tile text | Outfit Light 14 / 140%, `#49492B` | Typography |

`3 × 234 + 2 × 20 = 742`, and `140 + 82 + 56 + 742 + 128 = 1148`.

### Rows scale with the columns

Row height is authored in px but emitted as a **ratio of the column width**
(234 / 356.67 = 0.656) in container query units, so tiles keep their shape when
the column count changes: 3 columns at 1110 gives 356.67 × 234, two columns at
991 gives 458 × 300, one column at 390 gives 350 × 230.

The query container is a **wrapper around** the grid, not the grid itself — an
element that establishes a container cannot query itself, and putting
`container-type` on the grid makes `100cqw` resolve against the viewport
instead, which silently sizes every row wrong.

Columns go 3 → 2 (≤ 991, header stacks above) → 1 (≤ 575). Column spans clamp
per breakpoint so a wide tile can never overflow its row.

### Hover and reveal

**Hover** is deliberately quiet: the artwork breathes to 105% and the tile
lifts 4px with a soft shadow, over 550ms. The artwork is its own absolutely
positioned layer, so it scales without nudging the copy, and the tile clips it
so nothing spills past the radius. Choose lift, zoom, both or none.

**Reveal** fades and rises each tile in turn as the section reaches the
viewport, header first. The hidden state is only ever applied *from script*, so
with JavaScript off — or if anything throws — the section renders plainly
rather than invisibly. IntersectionObserver is the trigger, raced by a geometry
check, a scroll listener and a 1.5s timeout, because an observer never fires in
a few real contexts and an unrevealed section would be a blank hole.

The reveal moves tiles with the independent `translate` property while hover
uses `transform`. They compose instead of competing: the revealed state carries
four classes and would otherwise out-specify the hover rule and pin the lift to
nothing. A per-property delay list staggers only the reveal, so hover stays
instant afterwards. `prefers-reduced-motion` disables both.

---

## PFH Review Slider

The rail (heading, lede, prev/next) beside a scrolling grid of WebwinkelKeur
review cards, two deep and two wide per view, with dots underneath.

### Where the reviews come from

`PFH_Widgets_Reviews::get()` calls the WebwinkelKeur ratings API, normalises the
response and caches it in a transient. Nothing else in the plugin talks to the
network, and a page view never waits twice.

**Credentials**, highest priority first:

1. constants in `wp-config.php` — the recommended setup, because the API code
   never lands in post content:

   ```php
   define( 'PFH_WEBWINKELKEUR_ID', '1222432' );
   define( 'PFH_WEBWINKELKEUR_CODE', 'your-api-code' );
   ```

2. the `pfh_webwinkelkeur_credentials` filter
3. the **Webshop ID** / **API code** fields in the element

The ID is the number at the end of the shop URL — `Products-for-Home_1222432`
→ `1222432`, which is the field's default. The API code comes from the
WebwinkelKeur dashboard under *Settings → API*.

### Behaviour worth knowing

- **Caching.** A good response is cached for the configured window (12h by
  default). A *failed* one is cached for 15 minutes, so a wrong key or an
  outage cannot slow every page view down to the HTTP timeout.
- **Cron.** A daily `pfh_widgets_refresh_reviews` event drops the cache and,
  when the credentials are global (constants or filter), refills it there and
  then — so the refetch happens on a cron request instead of a visitor's.
- **Fallback.** With no credentials, or when the feed is unreachable, the
  manual **Fallback reviews** repeater renders instead. The section is never
  blank, and there is never a broken-looking empty state.
- **The builder always uses the fallback list**, so editing stays fast and
  predictable and Bricks never blocks on a remote call.
- **Ratings.** WebwinkelKeur scores 1–10; the cards show 5 stars. The
  conversion rounds to the nearest half star, so 9/10 renders as 4.5 and
  8.4/10 as 4. Set *API rating scale* to 5 if the endpoint ever changes.
- **Avatars.** WebwinkelKeur returns no photos, so every feed card uses the
  fallback: the built-in neutral avatar, an uploaded placeholder, or the
  reviewer's initials. Fallback reviews can carry a real photo each.
- **Line under the name.** City (what the API actually gives), review date, one
  fixed string, or nothing. City falls back to the fixed string when a reviewer
  left theirs blank.

### Filters

| Filter | Use |
| --- | --- |
| `pfh_webwinkelkeur_credentials` | Supply ID/code from anywhere |
| `pfh_webwinkelkeur_pre_reviews` | Return an array to bypass the API entirely (staging, tests, a different source) |
| `pfh_webwinkelkeur_reviews` | Last look at the normalised list before render |
| `pfh_webwinkelkeur_endpoint` | Point at a proxy or sandbox |
| `pfh_webwinkelkeur_request_args` | `wp_remote_get` arguments |
| `pfh_webwinkelkeur_cache_ttl` | Cache lifetime, with a flag for the failure case |

The response parser accepts a bare list, or one wrapped in `data`, `ratings`,
`reviews` or `items`, and reads each field through a list of aliases
(`comment`/`review`/`text`, `name`/`customer_name`/`author`, `city`/`place`,
and so on). API versions have moved these around before; assuming one shape
would make the section fail silently the day it changes again.

### Figma values

| Thing | Figma | Control |
| --- | --- | --- |
| Content column | 1110 | Content width |
| Rail | 362 | Heading column width |
| Gap rail ↔ cards | 68 | Gap heading ↔ cards |
| Card | 330, 20 gutter | Slider |
| Heading | Playfair Display 42 / 115%, block 362 × 192 | Typography |
| Intro | Outfit Light 14 / 150%, black 62% | Typography |
| Name | Outfit Medium 14 / 100%, `#3D0023` | Card |
| Review | Outfit Light 16 / 150% | Card |
| Stars | 16, 4 apart | Card |
| Arrows | 38, 8 apart | Slider |

`362 + 68 + 330 + 20 + 330 = 1110`. The rail width and the gap are authored in
px and emitted as proportions, so the split is exact at 1110 and stays in
proportion below it.

### Stars

One grey row of glyphs with a clipped coloured copy over it. The clip is
`n × star + floor(n) × gap`, not a percentage of the row, so a half star lands
on the middle of its own glyph rather than somewhere in the gap — and any
fraction the API returns renders correctly.

### Paging

The track is a native `overflow-x` scroller with scroll snapping; arrows, dots
and pointer drag all write to the same `scrollLeft`, so momentum, trackpad and
keyboard scrolling keep working.

Rows and columns are read from the **live grid** rather than from markup, so a
breakpoint that changes either is picked up on resize and the dots recount
themselves. Because `grid-auto-flow: column` fills top-to-bottom then across,
the script also sets `order` on each card so the grid still *reads* left to
right — auto-placement respects order-modified document order, which keeps the
markup in real reading order for search engines and screen readers.

Per view: 2 × 2 down to 992, then 2 × 1, then 1 × 1 below 576. Twenty reviews
one at a time would mean twenty dots, so past **Most dots to show** (8) the
dots become a `3 / 20` counter and the arrows carry navigation.

### Cards stay level

Every card reserves and caps the same number of lines (*Review lines*, 4 by
design), and the badge is pinned to the bottom with `margin-top: auto`. Real
reviews run to wildly different lengths; without both, the grid would look
ragged the moment it left the mock-up.

---

## PFH Call To Action

The closing card: copy on the left, a cut-out photo bleeding off the bottom
right, on a mint gradient — and it deliberately overlaps whatever comes after
it. Place it directly above the footer.

### The overlap

The section carries a **negative bottom margin** and its own **stacking
context**, so the next section slides up underneath the card rather than the
card being drawn on top of a gap:

```css
margin-bottom: calc(-1 * var(--pfh-cta-overlap));   /* 80px by default */
position: relative;
z-index: var(--pfh-cta-z);                          /* 2 by default */
```

Both are controls — *Overlap the next section by* (80, dropping to 60 on
tablet and 48 on mobile, itself a control) and *Stacking order*, which is there
for the case where something inside the footer paints over the card and needs
out-ranking.

Because the pull is a margin on the section rather than a transform on the
card, the footer genuinely moves up: nothing is left floating over empty space,
and the page height stays honest.

### Figma values

| Thing | Figma | Control |
| --- | --- | --- |
| Card | 1110 × 419, radius 20 | Card width / height / radius |
| Side padding | 80 | Card side padding |
| Text column | 503 | Text column width |
| Heading | Playfair Display 34 / 105%, block 503 × 72 | Typography |
| Text | Outfit Light 14 / 150%, block 503 × 63 | Typography |
| Button | 206 × 50, radius 70, padding 20, gap 4 | Button |
| Button fill | p-blue-800 at 80% | Button |
| Photo inset | ~40 from the right, flush to the bottom | Photo |

The supplied background artwork is 1920 × 725 — ratio 2.6483 against the card's
2.6491, which is what confirmed 1110 × 419 in the first place.

Figma puts the copy and the photo in a horizontal auto-layout with **-7**
spacing, i.e. they overlap by seven pixels; the built card reproduces that at
6.66, and since the artwork's left edge is transparent there is nothing visible
to collide.

### The photo needs the PNG, not the JPG

The cut-out was supplied as a `.jpg`, which **cannot carry transparency** — that
export is flattened onto solid black, and dropping it in would put a black box
in the card. The same asset exists as a `.png` beside it with the real alpha
channel (about half the canvas is fully transparent), and that is what the
default points at. If you ever re-export this image, keep it PNG or WebP.

The photo is sized off the **card height** (102% of it) rather than a fixed
width, so it stays in step with the text column as the card narrows, and it is
anchored to the bottom edge — the artwork is already cropped there, which is
what gives the hands their run off the card. The card clips its contents, so a
negative inset runs the photo further off the edge.

Where the image comes from the media library its dimensions are published as
`width`/`height` attributes. The photo's box takes its width from the intrinsic
ratio, which a lazily-loaded image does not have until it arrives — without the
attributes the card would settle a few hundred pixels wide on load.

### Responsive

- **≥ 992** — as designed; the card keeps its ratio through `aspect-ratio`, so
  everything scales together rather than the photo eating the copy.
- **768–991** — same layout, type steps down, overlap 60.
- **≤ 767** — the card stacks: copy on top, photo full-bleed along the bottom
  edge (it is pulled out to the card's edges with negative margins so the crop
  still reads deliberately). Overlap 48. *Show the photo on mobile* turns it off
  entirely, and the card closes its padding when it does.

`min-height` backs up the ratio, and content can still push the card taller —
so a longer heading in another language grows the card instead of spilling.

---

## PFH Product

The top of the product page, in one element: the breadcrumb trail, the gallery,
and the whole buying column beside it. Everything on it comes from the product
being viewed — drop it into a Bricks **single product** template and it fills
itself in.

### What it draws

| Part | Where it comes from |
| --- | --- |
| Breadcrumbs | Home, the category path, then the product. Linked all the way down |
| Gallery | Featured image first, then the gallery images. Arrows and thumbnails appear only when there is more than one |
| Tag | The **Tag** field on the product. Empty means no tag at all |
| Category, title, description | The product's primary category, name and short description |
| Rating | The shop's own WebwinkelKeur score and review count, with a link through — the shop's, not this product's |
| Price | The price, the old price struck through, and **the amount saved** — not a percentage |
| Variants | Pills, one group per attribute, with the choice named beside the label |
| What is in the box | The **Highlights** field on the product. Nothing in it, and the block is left out |
| The line above it | The **Highlight title** field on the product, so it can differ per product. Empty falls back to the element's own |
| Quantity and cart | A stepper writing single figures as 01, and add-to-cart without a reload |
| Promises | Three fixed lines, the same on every product |

Every border on the page reads one token, `--pfh-pdp-line`, which the client
gave as `#EAEAEA`.

### The three fields on the product

WooCommerce has nowhere to put a corner tag, a heading, or a "what is in the
box" list, so the plugin adds all three in their own **Products For Home** tab
in the Product data box, next to the price and stock.

* **Tag** — a short badge for the corner of the gallery. Empty means none.
* **Highlight title** — the line above the box ("Smaak — Mandarijn"). It is
  the client's to write, fixed rather than following the chooser, and
  different for every product.
* **Highlights** — a line and, optionally, a note that sits to its right
  ("1x 1000ml Griekse vruchtensap" · "33 glazen van 467ml"). Add and remove
  rows as needed; different for every product.

Both fields have a *field name* setting on the element, so an ACF field can be
pointed at instead. The reader understands a plain list of strings and the
usual ACF row key names as well as its own shape.

### It opens on something you can buy

A chooser that opens on nothing leaves the add to cart button greyed out until
the shopper works out for themselves which combination exists. So the page
opens on the product's own default when that can be bought, and otherwise on
the first variation that can — in the shop's own order, skipping anything sold
out. The price row, the chooser and the hidden variation field all describe
that same variation, so the form is valid before anyone touches it, with or
without JavaScript.

*Open on an available variant* switches it off, and then only what the shop
declared as the default is chosen. The bottom reminder does the same thing for
the same reason.

### Variants resolve here, not in WooCommerce's script

The pills set a real `<select>` behind each group — that is still the field
that posts, and what a screen reader announces — and the element works out the
variation itself from the data printed with the form. No jQuery, no dependency
on WooCommerce's variation script, and the same code path in the builder as on
the page.

Choosing updates the price, the old price, the saving, the variation id and the
photograph, and greys out any pill that cannot be reached: an out-of-stock
combination is visibly not on offer rather than failing at the cart. Clicking
the chosen pill again clears it. Past 60 variations the data is not printed and
the chooser falls back to posting the form.

The first group's chosen pill is solid `#2B5F63` and every group under it is
the softer `#7CAEB2`, which is the order the design draws them in. Naming
groups in **Groups using the soft selected colour** decides it by name instead
of by position — "smaak" in the design.
Name them the way the panel shows them; the taxonomy name works too.

### Adding to the cart

Goes through the same endpoint the product cards use, so the cart drawer and
the header count update the way they do everywhere else. Turn it off and the
form posts normally. A failure says so and leaves the form alone, so the
ordinary submit is still there.

---

## PFH Product Tabs

The four panels under the product, and the steps panel beside them. Everything
in them comes from the product, so one element covers every product in the
shop.

| Tab | Where it comes from |
| --- | --- |
| **Omschrijving** | The product's own description in WooCommerce. Every bullet in it is drawn with the tick from the design |
| **Ingredienten** | The ingredients field, the allergens box, and the *Zonder* claims as ticked pills |
| **Houdbaarheid** | A heading and a row of cards — icon, heading, text — as many as the product needs |
| **Voedingswaarden** | A heading, a line under it, and a table whose three column headings and every row are the client's |

Each tab has its own switch. A tab whose fields are empty is **not drawn at
all** rather than opening onto a blank panel — except Omschrijving, which
always shows and says *Geen informatie beschikbaar.* when there is nothing
there. With every tab switched off the element renders nothing.

### The fields

All of them are in the **Products For Home** tab in the Product data box, so
whoever adds a product fills them in where they are already working. The
repeating ones — highlights, claims, storage cards, nutrition rows — add and
remove rows, and the storage icons come from the media library with the
supplied icon as the default.

The reader understands other field shapes too: a plain list of strings, or
ACF's own row key names, so an ACF field can be pointed at instead by changing
the field name on the element.

### The steps panel

The copy is the same on every product, so it is set once on the element. Each
product can still switch it off — *Show "In 3 stappen klaar"* in the same tab —
and when it is off the panels widen to fill the space rather than leaving a
gap.

### Tabs, properly

The strip is a real tablist: the buttons carry `role="tab"`, only the open
panel is in the document, and only the open tab is in the tab order, so the
arrow keys, Home and End move between them the way they do anywhere else. The
active underline is one element scaled from the centre, which is what keeps the
row from shifting by a pixel on hover.

The ticks in the description are a CSS mask on `li::before` rather than
rewritten markup. Whatever the client types into WooCommerce comes out with the
right bullet without anyone touching the HTML, and the tick takes its colour
from the element's own token.

Below 768px the strip scrolls sideways instead of wrapping — four wrapped tabs
read as two rows of links — and the chosen tab is scrolled into view. The
nutrition table gets its own scroller so a wide table never pushes the page
sideways.

---

## PFH Related Products

The row under a product. It **extends** the product slider rather than copying
it, so the card, the drag behaviour and every style control are the same ones
by construction — the two cannot drift apart the way a duplicate would. Only
the source is its own, and it is pinned:

1. **What the editor chose** — *Related products* in the Products For Home tab,
   using WooCommerce's own product search.
2. **Otherwise the rest of that product's category**, in the order the shop
   itself puts them in, never including the product being looked at.
3. **Otherwise nothing.** A row filled with whatever the shop happens to sell
   is worse than no row, so a product with neither leaves the section off the
   page entirely.

On a product page it always follows the product being viewed; the ID on the
element is only so there is something to look at while building.

### The band behind it

Unlike the shop's own sliders this row sits on artwork, and ships with the
design's: choose a picture under **Layout & background → Background image**, or
paste a URL under the one below it, and either replaces the default. Clearing
both leaves the section plain. *Background fit* and *Background position* are
there for artwork that is a shape rather than a wash.

Every slider gained the same four controls — the product slider, the recently
viewed row and the grid — but only this one ships a picture, so no existing
page grows a background it was not asked for.

The section paints `background-color` and `background-image` as separate
longhands. The shorthand would reset the picture every time the colour was set,
which is the sort of thing that works until someone changes the colour.

---

## PFH Product USP

A band of pastel cards saying why this product. The eyebrow, the title and
every card — icon, heading, text — are the product's own, so the band reads
differently under every product in the shop, and a product with none is left
off the page rather than shown empty. The element's own cards are the fallback,
for products nobody has filled in yet.

The four pastels — `#F9E9CF`, `#DFE9DC`, `#E6EFF4`, `#FDE1D5` — fall in turn,
so a product that adds a fifth card starts the run again. Any card can name its
own colour instead, and the run carries on around it. Each card's colour is set
inline as `--pfh-usp-card`, which is what lets one element paint a different
band per product without a stylesheet per product.

The hover is a three-pixel lift with a shadow that was not there before, and
the icon tile grows by six percent. On four cards at once anything more reads
as motion for its own sake. Both are dropped under `prefers-reduced-motion`.

Two columns below 992px and one below 560px: two pastel cards side by side at
phone width leaves neither enough room for its own sentence.

---

## PFH FAQ, per product

The same accordion as the shop page, reading the product first. Switch on
*Questions from the product* and each product answers with its own — the
questions live in the Products For Home tab beside everything else — and a
product with none falls back to the questions typed on the element, which is
what the shop page keeps using.

---

## PFH About

An opening, a run of sections that alternate a picture with their text, and a
band of the awards the shop has won. Every part is a field, so the story can be
rewritten without anyone opening a template.

Sections are a repeater: heading, text, picture. One **with** a picture sits
beside it and every other one turns around, so the eye moves down the page
rather than straight down one side of it. One **without** is a column of text
set to a width that can still be read. Stacked on a phone the picture goes
above its text every time — alternating sides means nothing in one column.

The awards band takes its own heading and paragraph and a repeater of badges,
each with a caption and an optional link. With no badges it shows just the
words; with neither it is left off the page. The badges are set to multiply
against the band, so a photograph of a medal on a white square does not sit in
a white box.

---

## PFH Contact

A heading, then a card holding the ways to reach the shop beside a map.

**Every detail is a link, not a line of text.** The phone dials, the email
opens a message, the address opens whatever the visitor uses for maps. Each one
has a label and an optional note under it, and any left empty is left out
entirely — no empty row, no stray icon.

A written-out number still dials. `+31 (0)6 17 39 23 02` becomes
`tel:+31617392302`: the bracketed nought is an instruction rather than a digit,
the one you leave out when you dial the country code, and keeping it gives a
number that reads correctly and rings nowhere.

The KVK and BTW numbers sit quietly under a rule, and go together when both are
empty.

The map follows the address unless given a place of its own. A **picture**
chosen instead replaces it entirely, which is also how to have a contact page
that loads nothing from Google; `pfh_contact_map` is there for a shop that
wants its own embed. With the map off, the details take the full width rather
than leaving a gap where it was.

---

## PFH My Account

One element, two states, no page reloads.

### A visitor

A card in two halves: the shop's pastel on the left with what an account is
actually for, and on the right the sign-in form with *Registreren* behind a
switch beside it. Both forms are **WooCommerce's own** — the field names, the
nonces and the buttons `WC_Form_Handler` looks for — so the shop's login rules,
its rate limiting, its error messages and anything a plugin has hooked onto the
register form all still apply. Nothing about signing in is reimplemented here;
only its shape is.

Registration disappears by itself when WooCommerce has it switched off, and the
password field disappears when the shop generates its own. The register form
asks for a first and last name, which WooCommerce's handler knows nothing
about, so the plugin saves them on `woocommerce_created_customer`.

### A customer

A rail of tabs beside a panel: **Overzicht**, **Bestellingen**, **Adressen**,
**Gegevens**, and a way out. Switching tabs changes nothing but which panel is
visible, and writes `#acc-orders` into the address bar — so a refresh, or the
redirect WooCommerce does after saving an address, comes back where the
customer was. Arrow keys walk the rail the way a tablist should.

The overview counts the orders, how many are moving, and what has been spent,
in the three pastels from the reasons band.

### Orders, and where the parcel is

Each order shows its number, its date, a status pill in the colour that status
deserves, and the total. Opening one draws a line of four steps — **Besteld,
Betaald, Verzonden, Bezorgd** — with everything behind the parcel filled in and
the step it is on ringed. A completed order has no step still in progress; a
cancelled one says so instead of drawing a journey that stopped.

A tracking number is read from WooCommerce Shipment Tracking, from the plain
`_tracking_number` keys a hand-rolled setup uses, or from the
`pfh_account_order_tracking` filter — whichever the shop has.

**The contents are fetched when the order is first opened**, once, and kept
afterwards: a customer with forty orders does not download forty of them to
look at one. The endpoint checks `current_user_can( 'view_order' )` before it
answers, because an order id is a small number in a form field and anyone can
type a different one. The test suite asserts that somebody else's order comes
back refused, with none of it in the reply.

Below 992px the rail becomes a strip that scrolls; below 720px the tracking
line runs down the side instead of across, and the order header wraps to two
rows.

---

## PFH Bottom Add To Cart

The reminder at the foot of the page: the category, the product, what it costs
and what that saves, a dropdown for each choice, and one button that adds it
without a reload. It sits over the footer, so the page ends on the thing the
shopper came for rather than on a gap.

It follows the product being viewed. Off a product page it shows whichever
product is named on the element, so it can close a landing page too.

| Part | Where it comes from |
| --- | --- |
| Category | The product's own category — the deepest one, which is its shelf rather than "Shop" |
| Name and price | The product. A variable product opens on its default variation, so the price shown is one that can actually be bought |
| Saving | The difference between the two, **as an amount** — "Bespaar €5,00", not a percentage |
| Choices | One dropdown per attribute, sharing the button's width |
| Picture | The **Bottom add to cart → Picture** field on the product. Empty uses the product image |

### The overlap

The section carries a negative bottom margin, the way the closing banner does,
so whatever follows rises behind the card. Put it directly above the footer and
set *Pull the next section up* to taste — 90px in the design. Nothing else
needs to know it is there.

### One piece of code, not two

The card prints the same form the single product page does, down to the data
attributes, so `pfh-product.js` drives both: choosing, the price following the
choice, unreachable combinations greying out, the loader, the moment of
"Toegevoegd" and the cart drawer opening are one implementation. There is no
second script to drift from the first, and the test suite asserts both sides of
every hook they share.

What a variable product costs before anyone has chosen is likewise settled in
one place — `trait-pfh-product-price.php` — because the product page and the
reminder showing different opening prices for the same product is the kind of
thing nobody notices until a customer does.

### The dropdowns

Real `<select>` fields rather than the pills upstairs: down here the row has to
stay one line high whatever the product has, and a phone gives a select its own
native picker. Each is labelled for a screen reader, and the chevron is drawn
rather than left to the browser.

Below 768px the card stacks, the dropdowns go full width, and the picture drops
under the button. Below 560px the dropdowns stack and grow to 44px, which is a
thumb rather than a pointer.

---

## PFH Instagram Strip

The heading, the account, two round arrows, and under all of it a row of square
photographs that runs past both edges of the page and never reaches an end.

### Where the pictures come from

1. **The shop's Instagram feed**, when a token is connected under
   **Products For Home → Instagram**. One account for the whole shop, so the
   strip is the same wherever it is dropped.
2. **The pictures set on the element**, whenever there is no token — and
   equally when Instagram is down, the token has lapsed, or the answer comes
   back in a shape nobody expected. The shop does not notice; it just shows the
   other set.
3. **Nothing.** With neither, the section is left off the page rather than
   drawn hollow. In the builder it says so instead, and names where the token
   goes.

A video's poster is used rather than the file, a post with no picture at all is
skipped, and the caption becomes the alt text — trimmed, with its hashtag tail
and any emoji dropped. Emoji are dropped deliberately: the feed is cached in
the options table, and on a shop still running utf8 rather than utf8mb4 a
4-byte character makes that write fail outright, so the cache would never
store and every page view would go back to Instagram.

### The token

It lives in the settings screen, not on the element, and `PFH_INSTAGRAM_TOKEN`
in `wp-config.php` beats it — which is where it belongs on production, since a
token in the database is in every backup and export. A long-lived token lasts
60 days, so a weekly cron refreshes the stored one before it lapses; a token in
`wp-config.php` is left alone, because it is the site owner's to manage and
cannot be written back anyway. The screen says which of the two is in play,
whether Instagram is answering, and when the token runs out.

The feed is fetched once every few hours and cached. A failed call is cached
for fifteen minutes, so a revoked token costs one request a quarter of an hour
rather than one per page view.

### The marquee

The track is moved with a transform rather than `scrollLeft`: a transform is
composited, so the drift stays smooth while the rest of the page is doing its
own work. One set of pictures is printed and the script clones it until the row
is wider than the screen, then wraps at the width of a single set — and because
the wrap falls on the seam where one set ends and the next begins, there is
nothing to see when it happens.

It holds still whenever moving would be rude or pointless: under the cursor,
during a drag, while the tab is hidden, while the section is off screen, and
for anyone who asked for reduced motion. The arrows and dragging keep working
in every one of those cases. An arrow moves it by exactly one picture, eased;
a drag throws it and settles; a drag that ends on a picture does not also open
it.

Speed, direction and whether it pauses under the cursor are all controls. Speed
0 holds it still and leaves the arrows and dragging working.

Nothing is cloned on the server — doubling the markup would double what a
browser downloads for no gain — and the clones that the script makes are marked
as scenery, so a screen reader reads the pictures once and the keyboard walks
them once.

Below 992px the pictures come down to 220px, below 560px to 180px, and below
768px the account and the arrows take their own row under the title rather than
crushing it.

---

## PFH Product Highlight

One offer, given a banner: an italic eyebrow with a gift icon, a two-weight
headline, a short pitch, ticked selling points, the price, an optional button
— beside a photograph that runs to the card's edge. Like the Call To Action it
sits directly above the footer and hangs over it.

### Static, on purpose

Every word, price and link is typed into the panel and printed as typed. There
is no product lookup, no WooCommerce, no dynamic data and no database query —
the text fields do not even offer the dynamic-data picker. A tag like
`{post_title}` typed into a field is printed literally.

It used to read its prices from a product picked in a searchable list. That is
gone: the block now has nothing in it that can fail during a save, or render
differently on the page than in the builder.

| Group | Fields |
| --- | --- |
| Copy | Eyebrow, gift icon on/off, title first and second line, title tag, description |
| Selling points | Rows of text, text colour, tick colour |
| Price | Price, price before the discount (struck through), saving line |
| Image | Photograph (the supplied one until chosen), description, share of the banner, fade, side |
| Button and link | Label, link, new tab, whole banner clickable, corner radius, background, text colour |
| Style | Background, corner radius, image corner radius, padding, type sizes, text colour |
| Layout | Container width, minimum height, space above and below, overlap |

Leaving a field empty hides that part — and only that part. The card and its
photograph always render, so a block that was added is a block that shows.
The defaults live in one `DEFAULTS` constant that the panel and the render both
read, so what the page shows never depends on Bricks having built the controls.

### Why it would not save: the gift emoji

The previous version's eyebrow icon was a text field defaulting to the gift
emoji, U+1F381. That is a 4-byte character, and where a site's `wp_postmeta`
table is **utf8** rather than utf8mb4 — the default before 2015, and still
common on sites moved between hosts — WordPress refuses to write any value
containing one. `wpdb::process_fields()` strips what the column cannot hold,
sees the value changed, and writes nothing.

Bricks keeps a whole page in one post-meta value, so it was not only the banner
that failed: **every later save of any page holding it failed**, while the
builder went on showing the edits. The page stayed on its last save from before
the banner arrived — which is exactly "nothing I add to this template saves",
and exactly an older version of the template on the front end.
`dev/wp-testbed/test-save-guard.php` reproduces it on WordPress core's own save
code.

Two fixes, each enough on its own:

* **The block stores no 4-byte characters.** The gift is a switch, and the
  emoji is printed as the entity `&#x1F381;`, which is never saved.
  `dev/audit.php` fails the build if any shipped file holds one.
* **`PFH_Widgets_Save_Guard`** writes 4-byte characters in Bricks element data
  as HTML entities when — and only when — the postmeta column is utf8, the way
  WordPress already does for post titles and content. An entity renders as the
  same character, so an emoji typed into any Bricks field on such a site no
  longer takes the page down. On utf8mb4 it does nothing.

*Products For Home → Diagnose* reports the column's charset in words, and the
save log records the database's own refusal message when a write is turned
down — a refused write ends HTTP 200, so it is otherwise invisible.

### The overlap

Same idea as the Call To Action, with one correction worth knowing: the pull
has to cover **the section's own bottom padding as well as the overlap**, or
most of the margin is spent closing that gap — 74px of overlap gave 18px of
visible hang before this was right.

```css
margin-bottom: calc((var(--pfh-hl-overlap) + var(--pfh-hl-pb)) * -1);
```

The number in the panel is therefore what the card really hangs over. Below
782px the card is stacked and full height, and the overlap is dropped — there
it only crowds both sections.

### Corners and the button

*Corner radius* rounds the card (20 as drawn, down to 0 for square) and
*Image corner radius* rounds the photograph within it. They are separate
because the card already clips the photo to its own corners: the second is for
rounding the image itself against a card that is square, or squaring the image
inside a rounded card.

The **Button and link** group carries the button's own corner radius,
background and text colour. Radius left empty keeps the 5px it is drawn with; half the
button's height or more gives a pill. The notice band's button has the same
setting — it used to share the band's corner radius, so setting the band to 14
took the button with it and there was no way to separate them. Left empty it
still follows the band, so nothing already built moves.

### Figma values

| Thing | Figma | Control |
| --- | --- | --- |
| Card | 1240 wide, min 468 tall, radius 20 | Max width / Minimum height / Corner radius |
| Inner padding | 52 sides, 48 top and bottom | Inner padding |
| Eyebrow | Playfair Display italic 22 | Eyebrow size |
| Headline | 32 / 110%, light over medium | Title size |
| Body and points | Outfit 14 / 150% | Text size |
| Price | 24, medium | Price size |
| Media | half the card, flush to the bottom edge | Image share of the banner / Image side |

The supplied photograph carries its own background, which is not quite the
card's fill — so *Fade the image into the card* fades the inner edge over a short
distance and hides the seam. A cut-out on transparency needs none of it, so it
is a setting rather than something baked in.

---

## Defaults have to survive the front end

Worth knowing before adding a control with a default, because it silently
deleted whole sections from live pages.

Two Bricks behaviours meet:

* Bricks **builds an element's control list when it needs the panel**. On the
  front end it does not, so `$this->controls` is empty exactly where the
  defaults declared in it are wanted.
* Bricks **does not store a value that still equals its default**. A section
  the editor drops in and leaves alone is saved with little or nothing in its
  settings — the defaults are the whole of its content.

An element that answered "no setting, so nothing" therefore rendered *nothing
at all* on a live page while looking perfectly correct in the builder. That is
how it was reported: "when I add that widget it doesn't show up".

`PFH_Element_Defaults` is the answer. `setting()` reads the stored value, then
the control's own default, then the caller's; `ensure_controls()` builds the
control list on demand, once, guarded against recursion. Every element reads
its settings through it.

One distinction it keeps: a key that is **absent** was never touched and takes
the default, while a key that is **present and empty** was cleared on purpose —
an eyebrow the editor deleted must not come back.

`dev/wp-testbed/test-frontend-defaults.php` holds the line, and it is the test
to keep green: for every element it renders with the defaults stamped in and
again with nothing at all, and the two must match. If they ever diverge, some
call site is falling back to a different value than the panel is advertising —
which is a second way to get the same bug, and how the shop header came to
promise a title it did not render.

---

## PFH Recently Viewed

The product slider, showing only what this visitor has already looked at. It
**extends** the slider rather than copying it, so the card, the drag behaviour
and every style control stay identical by construction — the two cannot drift
apart the way a duplicated element would. Only the source is pinned, and it is
not offered as a choice: it is what the element is.

### It loads after the page, and that matters

The history lives in WooCommerce's own per-visitor cookie. A page cache that
stored this rendered would serve **one shopper's browsing history to the next**,
so by default the element ships an empty hidden placeholder and fetches the
visitor's own slider afterwards. The page itself stays cacheable and each
visitor's history stays their own.

*Load after the page* turns that off, for a site with no page caching at all.
Leave it on.

### Nothing viewed, nothing shown

With no history the request returns nothing and the placeholder simply stays
hidden — no heading, no empty rail, no layout shift. The response is checked
for a real card before it is used, so an editor placeholder or another
plugin's notice cannot end up in front of a shopper who has simply not browsed
yet. A failed request costs nothing and says nothing: this section is a
convenience, not part of the page's job.

WooCommerce remembers the last 15 products a visitor opened, which is the
ceiling on *How many to show*.

---

## AJAX add to cart, everywhere

Nothing on the site reloads to add to the cart, and nothing prints a separate
notice — the cart drawer opening *is* the confirmation.

WooCommerce only ships AJAX add-to-cart for **simple** products, and only on
loops it renders itself. A variable product falls back to a link to its page,
and a theme's own buttons reload. `PFH_Widgets_Quickadd` closes both gaps with
two endpoints:

| Endpoint | Does |
| --- | --- |
| `pfh_add` | Adds a simple product or a resolved variation |
| `pfh_variations` | Describes a variable product so the chooser can be built without loading its page |

`assets/js/pfh-quickadd.js` intercepts, in capture phase:

- our own product cards (`[data-pfh-add]`),
- WooCommerce loop buttons wherever the theme renders them
  (`.add_to_cart_button`, `.product_type_variable`),
- and the single-product `form.cart`.

It loads on **every** front-end page while WooCommerce is active, not only
where a PFH element happens to be — filter `pfh_widgets_quickadd` to switch it
off. Grouped products still go to their page, because they need their own
quantity table.

On success it fires a `pfh:added` DOM event. The header listens for that
directly, so the drawer opens and refreshes **without jQuery or WooCommerce's
loop script** having to be on the page at all.

### The variant chooser

A variable product opens a modal instead of navigating: product image, live
price, one `<select>` per attribute, a quantity stepper.

Choosing narrows to a single variation and the button only enables when one
resolves. Combinations that do not exist say so; ones that are out of stock say
that instead; and an invalid pick puts the product's own price back rather than
leaving the last valid variation's on screen.

The variation is resolved **again on the server** before anything is added, via
`find_matching_product_variation`, so a chooser left open while stock or
attributes change cannot put the wrong thing in the cart.

The card's `href` stays the real WooCommerce destination throughout, so with
JavaScript off the button still works — the script cancels the navigation.

---

## The mobile bar

Below the menu breakpoint the bar stops being a three-column grid and becomes a
flex row: the **logo holds the left edge** (`margin-right: auto`) and everything
else orders itself after it. `display: contents` on the actions wrapper lifts
the individual icons into that row, which is what lets the burger sit *between*
them rather than only before or after the whole group.

**Account is hidden on mobile by default** — it stays in the mobile menu instead
of crowding the bar. *Show the account icon on mobile* brings it back, and it
slots in with the other icons rather than past the burger.

*Mobile icon order* sets the sequence after the logo:

| Option | Result |
| --- | --- |
| Search, cart, menu *(default)* | logo → search → cart → **menu** |
| Search, menu, cart | logo → search → menu → cart |
| Menu, search, cart | logo → menu → search → cart |
| Cart, search, menu | logo → cart → search → **menu** |

Desktop is untouched: still the three-column grid with the nav in the middle
and the burger hidden.

---

## Product card, 1.12.0

Measured against the Figma frame and corrected:

| | Figma | Was | Now |
| --- | --- | --- | --- |
| Media ratio | 310 × 358 | 310 / 352 | **310 / 358** |
| Media radius | 18 | 16 | **18** |
| Media → copy gap | 14 | 18 | **14** |
| Title | Outfit Medium 20 / 100% | 20 / 500 / 1.3 | **20 / 500 / 1** |
| Price line height | 100% | 1.2 | **1** |
| Gap between cards | 20 | 20 | unchanged |
| Media ground | `#F4F4F4` | same | unchanged |

Rendered: card **447.64** against Figma's 447, media ratio exact to the
decimal, radius 18, inner gap 14, gap between cards 20. Card width comes out
311.4 against 310 — 3.5 cards across a 1140 column, which is what puts it
there.

**Every card is the same height whatever the product is called.** The title was
already clamped, which stops a long name pushing a card taller; it now
*reserves* those lines too, so a short name no longer leaves its card shorter
and a shelf of mixed names stops coming out ragged.

The Figma frame is 447 because the mock uses names that fit one line. At the
shipped default of two reserved title lines a card is 467.64; set *Title lines*
to 1 for exactly the mock's height, at the cost of truncating longer names.

---

## Fixes in 1.11.6 — the real cause of the category bug

The category filter worked in the builder and did nothing on the front end.
1.11.5 blamed the control's options list; that was wrong. The actual cause:

```php
private function cards() {
    static $cards = null;          // shared by EVERY instance of the class
    if ( null !== $cards ) {
        return $cards;             // second slider returns the first one's cards
    }
    ...
}
```

A method `static` belongs to the class, not the object. The page carries two
product sliders, so the first one ran its query and filled the static, and the
honey slider returned early with those cards — it never ran a query at all, let
alone a filtered one. No category would ever have worked. The sale badges on
the honey shelf were the tell: they were the other slider's on-sale results.

It looked fine in the builder because Bricks renders each element in its own
isolated request, so there is only ever one instance and the static never
collides.

Nine more of these were hiding in the same shape and are all now per-instance:
`uid()` in six elements — two copies of a widget shared one DOM id, so the
second slider's viewport, scrollbar and `aria-controls` all pointed at the
first — plus `slides()` in the hero and category sliders and `nav_items()` in
the header.

`dev/test-multi-instance.php` renders two sliders in one request and asserts
their cards and ids are independent. It exits non-zero if the bug returns.

The 1.11.5 changes stay: resolving an id, slug or array for the category is
genuine robustness, and a category source that resolves to nothing returning
nothing — rather than the whole catalogue — is what would have made this
visible in the first place.

---

## Fixes in 1.11.5

**The product slider ignored its category on the front end.** The builder
filtered correctly; the live page showed the whole catalogue.

`product_cat_options()` deliberately returned an empty list outside the builder
— there seemed no reason to run `get_terms()` on a page view just to fill a
panel dropdown. But Bricks validates a select control's saved value against
that options map, so with no options the chosen category did not survive to the
front end. `$this->get( 'category' )` came back empty, the `category` branch
added no `tax_query` at all, and the query fell through to every published
product ordered by menu order — which is why the shelf filled with unrelated
items rather than rendering empty. In the builder the options existed, so it
looked right there. The same asymmetry affected the header's two category
controls.

Three changes:

- The list is built on the front end too, behind a 12-hour transient that is
  dropped whenever a product category is created, edited or deleted. The
  builder still reads live terms so a new category appears immediately.
- `PFH_Widgets_Helpers::category_ids()` resolves whatever the panel saved — an
  id, a slug, an array of either, or the object shape a control can store — and
  the query matches child categories too.
- A category source that resolves to nothing now returns nothing instead of the
  whole catalogue. Silently widening to every product is what disguised a lost
  setting as a filtering bug.

---

## Fixes in 1.11.4

**The olive band's branch is pinned to a corner.** It sat at `left: 87%` of a
full-bleed band, so the wider the screen the further right it started — past
1500px it ran off the edge and was clipped. *Pin the decoration to* (top right
by default, with px offsets from each edge) holds it 64px from the right and
44px from the top at any width. Verified fully inside the band at 1440 and
2560.

**The chooser's minus button was pushed against the group's border.** The
steppers are `<button>`s, and the theme's own button rules add padding and
left-align the label. They are flex-centred now with padding refused outright;
the glyph sits dead centre and the two buttons are symmetric.

---

## Fixes in 1.11.3

**The card's add-to-cart icon moved out of its corner.** The loader added
`.pfh-prod__cart { position: relative }`, and because the quick-add stylesheet
loads after the product one at equal specificity it overrode the
`position: absolute` that pins the button to the card corner — so the button
fell into the flow. The rule is gone; the spinner rides the button's existing
flex centring instead, with the icon taken out of flow so the pseudo-element is
the only child left to centre. The button does not move in any state.

**Hero decorations pin to a corner.** *Pin to* (bottom left / bottom right /
top left / top right, or a free percentage position) with a px offset from each
edge — negative runs the artwork off the edge as in the design. The lemon
defaults to bottom-left at -40, the branch to bottom-right at -30, so both hold
the same relationship to their edge at any width instead of sliding inward as a
percentage of the section. Verified identical at 1572px and 2560px.

---

## Fixes in 1.11.2

**The quantity stepper jumped by random amounts.** `renderPicker()` bound the
click and change listeners every time the chooser opened, but `.pfh-qa__body`
is a persistent element — only its markup is replaced — so the listeners piled
up. After six opens one click on "+" ran six handlers and the field went up by
six. They are bound once in `build()` now and read the current data at event
time. Verified stepping by exactly one after six opens.

**Closing the cart drawer jumped the page to the top.** `Panel.close()` returns
focus to whatever opened the drawer; when that is an add-to-cart half way down
the page, the trigger is the header's cart button and focusing it scrolled it
into view. Both the drawer and the chooser now restore focus with
`preventScroll`. Verified: scroll position identical before, during and after.

**The chooser's quantity field showed a doubled border** — the group draws the
box, and a theme's own input border sat inside it. The input refuses border,
background and shadow outright. The submit label is flex-centred so a theme's
`text-align: left` on buttons cannot push it off centre; both controls are the
full width of the panel.

**Marquee typography now matches Figma.** The honey bar is Outfit SemiBold 20 /
100% in `#9B4713` with a 30px bee and a 48px gap; the hero bar is Outfit
Regular 16 / 100%.

**Floating images span the full band again** rather than the content column,
and their width is emitted as a `clamp()` measured against the 1440 design
frame — so a decoration drawn at 160px is 160 at 1440, 213 at 1920 and caps at
280, instead of staying stranded at a fixed size as the monitor grows.
*Scale floating images with the screen* turns it off; *Design frame width*
retargets it.

---

## Fixes in 1.11.1

**The page froze after adding a variant.** `.pfh-qa { display: grid }` is an
author rule, so it beat the browser's own `[hidden] { display: none }`: once
closed, the dialog stayed a full-viewport layer at `z-index: 100000` with an
invisible scrim, and every click on the page landed on it instead. Fixed with
`.pfh-qa[hidden] { display: none }`, plus `pointer-events: none` while it is
not open so the closing fade cannot swallow a click either.

**Add to cart feels immediate.** The drawer now opens the moment the add
succeeds and fills a moment later, instead of waiting for a second round trip.
Card buttons and the dialog's submit spin in place while the request is in
flight, and a card shows a tick as it lands.

**The chooser's button is full width.** Quantity and submit used to share a
row, so a translated label broke onto two lines. They are stacked now, and the
quantity field's focus ring moved to its bordered group.

**Stray focus rings.** Themes style `:focus` with selectors a component class
cannot out-specify, which is where the rings around the search field and the
cart icon came from. There is now a defensive reset inside our own components,
and the search field's ring lives on the pill rather than the input.

**Floating decorations** are positioned against the **content column** by
default rather than the full-bleed section, so a decoration placed at "82%" in
the 1440 design keeps its place on a wide monitor instead of drifting toward
the edge. *Position floating images against* switches it back.

---

## Fixes in 1.11.0

**Focus rings.** The base `:focus-visible` rule carried two classes of
specificity, which out-specified every component that said `outline: none` —
the search field included, hence the dark ring around it. The rule is now
zero-specificity, mouse clicks never leave a ring (`:focus:not(:focus-visible)`),
and the search field has a designed focus state of its own.

**Container width is now 1140** across every element, so section content lines
up with the header logo.

**Full-bleed sliders align to the logo.** The inset used to be a percentage of
the widget's *parent*; inside a page-builder container that silently became the
reference, and the first card stopped lining up. It is now measured against the
viewport, minus the scrollbar width, which the slider script publishes as
`--pfh-sbw`. Verified identical at 1440 and 2560.

**Marquees no longer gap.** The CSS loop translates by -50%, which is seamless
only while one half is wider than the strip — on a wide monitor it was not.
`pfh-marquee.js` clones the group until the track comfortably out-runs the
strip, keeps the count even so the wrap stays invisible, and scales the
duration so the words keep the speed the element asked for.

**Mega menu artwork on large screens.** The art is drawn for a 1920px frame;
`cover` on a wider panel scaled it up and cropped the decoration out. It now
spans the panel up to the artwork's own width and then stops
(`min(100%, 1920px)`), so it stays beside the content.

**The two branch cut-outs** were matted onto white, which fringed against the
hero's warm ground and the olive band's green. `assets/img/` ships both with
the matte recovered and the halo softened. Note that the supplied artwork also
has white baked into *opaque* pixels along the leaves — that part is a cut-out
quality issue in the source and needs re-exporting for a perfect result.

**Featured photos** now ship their full `srcset` with a `sizes` hint, and the
stacked height tracks the viewport rather than being one fixed value for every
phone.

---

## PFH Rating Badge

The inline shop-rating line — **Excellent 9,7 | 270 reviews on
WebwinkelKeur** — as its own element, so it can sit in the header, a hero, a
product page or anywhere else, not just the footer.

Score and count come from `PFH_Widgets_Reviews::summary()`, which reads the
WebwinkelKeur **ratings summary** endpoint. That reports the whole shop; the
ratings *list* is capped by `limit`, so counting it would understate the total.
When the summary is unavailable the method returns nulls rather than a guess
and the element falls back to the values typed in the panel — so the line never
renders blank or wrong.

The builder always shows the typed values, so editing never waits on a remote
call and the panel shows exactly what an unreachable feed would.

The count text is a template: `%s reviews on` is filled with the live number,
localised (`number_format_i18n`), so a Dutch site reads *1.284* and the score
reads *9,6*.

Cached for 12 hours; a failed call is cached for 15 minutes so an outage cannot
slow every page view. Both caches are cleared by the daily
`pfh_widgets_refresh_reviews` cron alongside the review feed.

`pfh_webwinkelkeur_pre_summary` short-circuits the whole thing — return
`[ 'rating' => 9.6, 'count' => 1284 ]` on staging and no request is made.

**The footer badge uses the same figures.** *Use the live WebwinkelKeur rating*
is on by default; its Score and Review count fields are the fallback.

### Credentials

Put them in `wp-config.php`, not in an element:

```php
define( 'PFH_WEBWINKELKEUR_ID', '1222432' );
define( 'PFH_WEBWINKELKEUR_CODE', 'your-api-code' );
```

Constants beat both the filter and the panel fields, and keep the API code out
of post content, out of revisions and out of any exported template.

---

## PFH Footer

Columns are a repeater; each one pulls its links from a **WordPress menu**
(managed under *Appearance → Menus* — the most client-friendly option) or from
a **manual list**, one `Label | URL` per line.

The newsletter block is either the built-in styled form (post to any URL, e.g.
a Mailchimp endpoint) or a **shortcode** — drop in Mailchimp, Fluent Forms,
MailPoet, whatever the client uses; the CSS styles the common field markup to
match.

`{year}` in the copyright is replaced with the current year.

On mobile the link columns collapse into accordions automatically.

---

## Matching the Figma exactly

Everything is driven by CSS custom properties written inline by the element, so
tuning is a panel edit, never a stylesheet edit. The defaults below were taken
off the supplied Figma frames (1440 px wide):

The design frame is **1440 px** wide. Measured off the exports, the content
column inside it is **1110 px** with ~165 px margins, which is the default for
both elements (**Layout → Content width**). Keep the header and footer on the
same number so the header logo lines up with the first footer column.

### Header

| Figma value | Control | Default |
| --- | --- | --- |
| Announcement bar height / 16px Outfit Regular | Announcement bar → Bar height / Font size | `41` / `16` |
| Header height | Layout → Header height | `72` |
| Menu item 15px Outfit Medium, 40px gap | Navigation → Font size / weight / gap | `15` / `500` / `40` |
| Active underline | Navigation → Underline colour / thickness | `#6f8566` / `2` |
| Mega card image box 196 × 220 | Mega menu → Image box ratio | `196 / 220` |
| Mega panel bottom radius | Mega menu → Bottom corner radius | `30` |
| Mega grid inset from the container | Mega menu → Horizontal inset | `22` |

**Navigation → Menu alignment** (default *Centred on the container*) matches the
design. Switch to *Centred in the free space* if the logo ever gets wide enough
to push the menu off-centre.

### Footer

| Figma value | Control | Default |
| --- | --- | --- |
| Background | Layout → Background | `#51604f` |
| Column heading — Outfit **Medium 18** / 105% | Link columns → Heading size / weight / line height | `18` / `500` / `1.05` |
| Column link — Outfit **Regular 14** / 210% | Link columns → Link size / line height | `14` / `2.1` |
| Column gap | Link columns → Gap between columns | `50` |
| Newsletter block 348 × field 42 | Newsletter → Block width / Field height | `348` / `42` |
| Subscribe button | Newsletter → Button background / text | `#a9bfa5` / `#51604f` |
| Footer logo 203 wide | Brand & social → Logo width | `203` |
| Social icons 20 px | Brand & social → Icon size / gap | `20` / `16` |
| WebwinkelKeur badge 126 × 19 | Reviews & copyright → Provider logo width | `126` |

Link spacing comes from the **210 % line height**, not from a gap — that is how
Figma spaces them. *Extra space between links* is an additive control on top and
defaults to `0`.

**Link columns → Column spacing** defaults to *Fixed gap, newsletter right*,
which is the Figma layout: every column sizes to its own content, a fixed 50 px
separates them, and the newsletter is pinned to the right edge. Switch to
*Spread evenly* to distribute the columns across the full row instead. Each
repeater row also takes an optional **Column width** if you need to pin one
exactly (the Figma "Categories" column is 144 px).

### Footer logo and the black background

The supplied `Group-20640.jpg` is white artwork on a **solid black background**,
so dropping it straight onto the green would show a black box. **Brand & social
→ Logo background** defaults to *Drop a black background*, which blends the
black away (`mix-blend-mode: screen`, isolated to the footer so it cannot affect
anything else on the page). Once a transparent PNG or SVG replaces it, switch
that control to *Keep as uploaded*.

The social icons and the review badge are already transparent white SVGs and are
used as-is. If you ever swap in dark icons, turn on **Recolour uploaded icons**
and the **Icon colour** control takes over.

The **Outfit** font is loaded from Google Fonts. If the theme already self-hosts
it, disable the duplicate:

```php
add_filter( 'pfh_widgets_load_google_font', '__return_false' );
```

---

## One page, one column

Every section on the product page — the product, the tabs, the reasons, the
related row, the questions, the Instagram strip, the bottom reminder — and the
footer under them all sit on the same 1140 column with the same 24px gutter, so
the content edge never steps as the page scrolls. The vertical rhythm between
them is 144px on a desktop and 104px on a phone; the product and its tabs sit
closer together at 96px, because they are one block rather than two.

Each of those is still a control. What changed is only what they default to:
the sections had drifted to three different widths (1140, 1188, 1240) and six
different paddings, which is the sort of thing that reads as unfinished without
anyone being able to say why.

## Hooks

| Hook | Type | Purpose |
| --- | --- | --- |
| `pfh_widgets_load_google_font` | filter | Return `false` to stop loading Outfit from Google Fonts |
| `pfh_widgets_search_args` | filter | `($args, $term)` — adjust the live-search `WP_Query` |
| `pfh_webwinkelkeur_credentials` | filter | Supply the WebwinkelKeur ID / API code from anywhere |
| `pfh_webwinkelkeur_pre_reviews` | filter | Return an array to bypass the API entirely |
| `pfh_webwinkelkeur_reviews` | filter | Last look at the normalised reviews before render |
| `pfh_webwinkelkeur_endpoint` | filter | Point the feed at a proxy or sandbox |
| `pfh_webwinkelkeur_request_args` | filter | `wp_remote_get` arguments for the feed |
| `pfh_webwinkelkeur_cache_ttl` | filter | `($ttl, $failed)` — cache lifetime |
| `pfh_widgets_refresh_reviews` | action | Daily cron event; drops and rewarms the review cache |

---

## Development

`../dev/` holds a standalone preview harness that renders both elements with
stubbed WordPress and Bricks classes, so layout work does not need a WordPress
install:

```bash
php dev/render.php > dev/preview/index.html && php -S 127.0.0.1:8912 -t dev/preview
```

`php dev/ajax-test.php` runs the AJAX and helper smoke tests.

---

## Roadmap

Next sections to build out as separate elements in this same plugin: USP bar,
product grid, bundle/combo cards, review carousel, blog grid, and the contact
block.

### A note on tokens

Every element writes its settings as inline CSS custom properties. Any token
that is **also overridden in a media query** must be emitted under a `-set`
name, with the stylesheet resolving `--token: var(--token-set, default)` —
otherwise the inline value wins over the media query and the responsive
override silently does nothing. The audit in `dev/` checks for this.
