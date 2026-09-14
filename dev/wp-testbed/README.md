# Real-WordPress testbed

The archive filter engine cannot be tested against stubs — faceted counts,
`include_children`, WooCommerce's on-sale lookup and the reserved-query-var
behaviour only show up on a real install. This stands one up.

    bash dev/wp-testbed/00-bootstrap.sh /tmp/pfh-wp
    cd /tmp/pfh-wp/wp && php test-engine.php
    cd /tmp/pfh-wp/wp && php -S 127.0.0.1:8913
    open http://127.0.0.1:8913/shop-test/

## What it is

* WordPress on **SQLite**, via the official `sqlite-database-integration`
  drop-in — the dev machine has no MySQL.
* WooCommerce from the plugin directory.
* 32 demo products across 7 categories (one hierarchy, `gia-giamas` with four
  children), three global attributes with terms, varied prices, eight on sale,
  two out of stock.
* `mu-bricks-stub.php` stands in for `\Bricks\Element`. Bricks is paid and
  cannot be downloaded, but the elements only need the base class to exist —
  everything under it is plain WordPress and WooCommerce. It also registers a
  `[pfh_archive_test]` shortcode so the front end renders the archive without
  a Bricks template.

## What it found

Things that no amount of stubbed testing surfaced:

* `wp_localize_script()` silently does nothing for an unregistered handle, and
  a block theme renders shortcodes before `wp_enqueue_scripts` — so the
  archive's JS config never reached the page.
* The AJAX handler expected the filter state as a JSON blob while the script
  sent form fields. Filtering fell back to a full page load, every time.
* `page`, `cat`, `order` and `orderby` are reserved WordPress query vars.
  `?page=3` on a page means "third page of the post content" and emptied the
  archive. All wire names are now namespaced `pfh_*`.
* `.pfh-prod__item` carries slider-only flex sizing, which collapsed the
  archive's grid columns into each other.

## Round 3 — the product block and the AJAX filter

Bugs the real install caught that stubbed rendering could not:

1. **The card variables existed in three copies.** `products`, `product-grid`
   and `archive` each wrote their own `--pfh-cart-*` / `--pfh-star-*` /
   `--pfh-t-*` list, and the archive's copy was missing entirely — so its
   cards fell back to the stylesheet and the TOEVOEGEN button rendered
   olive instead of the teal its own control default asked for. The two
   surviving copies had also drifted (`cartRadius` 999 vs 5). Now
   `card_vars()` in the trait owns the whole set and the elements merge it.
2. **The archive's card controls styled nothing.** `cardRadius`,
   `cardRatio` and `cardImageBg` were written under their own names while
   the shared card CSS reads `--pfh-media-*`, so the slider's roomy
   defaults — a 26px pad in a 310/358 box — showed through on the grid.
3. **The archive stylesheet outranked its own controls.** `.pfh-arch
   .pfh-prod__name` beats `.pfh-prod__name`, so hard-coded type in
   `pfh-archive.css` meant the panel's title, price and review settings
   changed nothing on an archive.
4. **`.pfh-arch .pfh-prod` matches nothing.** Both classes sit on the same
   element, so the descendant selector for the block-button spacing never
   applied.
5. **The mobile card rules ignored element intent.** The slider's flat
   `--pfh-t-size: 17px` at 767px grew the archive's 14px title back up.
   Both small-screen values are tokens now.
6. **AJAX degraded to a full page reload, silently.** Two causes, and the
   page still worked either way, which is why it went unnoticed:
   `admin_url()` gave an absolute URL, so any host that is not the stored
   site address made the POST cross-origin; and `history.replaceState()`
   threw on a cross-origin URL *inside* the `.then()`, landing in the
   request-failure `catch` that navigates. Endpoint and payload URL are
   both relative now, and painting happens after the catch.
7. **"All top level categories" was never implemented.** It fell through to
   the children branch, so on a category with no children the whole pill
   row disappeared and the visitor could not leave that category.

Run order: `00-bootstrap.sh`, `02-woocommerce.php`, `03-demo-products.php`,
`04-test-page.php`, `05-ratings.php`, then `test-engine.php`,
`test-shophead.php`, `test-cards.php`, `test-catnav.php`.

## Round 4 — styles and fonts across all 19 widgets

Checked by rendering every element on one page (`06-all-widgets.php` plus
`06-all-widgets-page.php`) and reading **computed** styles, not markup.
`style-scan.js` is that scan, kept so it can be re-run.

1. **Three widgets rendered in the theme's font.** `.pfh-arch`, `.pfh-bdg`
   and `.pfh-ck` each set `font-family: inherit`. Those stylesheets load
   after `pfh-base` and match at the same one-class specificity, so
   `inherit` won and pulled the theme's body font — the whole shop archive
   came out in Manrope. All three take `var(--pfh-font)` now.
2. **The badge and cookie banner had no tokens at all.** Both were enqueued
   with `[]` as their dependency list while their markup is wrapped in
   `.pfh-scope`, whose tokens and resets live in `pfh-base`. They render on
   every page, including pages with no PFH element, where nothing else would
   have enqueued it — so every `--pfh-*` was undefined and `box-sizing` was
   the browser default. Both now depend on `pfh-base` and call
   `PFH_Widgets_Assets::base()`.
3. **The webfont was call-order dependent.** `pfh-base` did not declare
   `pfh-font-outfit`; the font only arrived because every helper happened to
   call `base()` first. It is a declared dependency now.
4. **`pfh-info` leaked onto the featured sections.** Its row modifiers are
   not namespaced — `.pfh-media-left` just means "image on the left", and
   `featured`/`featured-olive` use the same names on their own roots. At
   tablet width a bare `.pfh-media-left` rule forced `display: flex` and
   `flex-direction: column` onto a featured section. All of that stylesheet's
   modifier rules are scoped under `.pfh-info` now — and note the
   combinator: `pfh-morder-*` and `pfh-hov-*` sit on the root so they
   compound (`.pfh-info.pfh-hov-zoom`), while `pfh-media-left` sits on a row
   inside and takes a space. Writing a descendant where the classes share an
   element matches nothing, silently.
5. **Three info layout declarations were dead.** `--pfh-i-media-w`,
   `--pfh-i-stack-gap` and `--pfh-i-stack-max` resolved empty on the
   featured roots the leak reached, dropping `grid-template-columns`,
   `row-gap` and `max-width` entirely. Fixed by 4.
6. **The CTA card overflowed any narrow container.** `aspect-ratio: 1110/419`
   with `min-height: 419px` and no width cap resolves a 1110px width of its
   own, so in a Bricks container narrower than that it grew straight out and
   gave the page a horizontal scrollbar. Capped at `width/max-width: 100%`.

`audit.php` grew checks for (1), (6) and (2) — verified by reintroducing
each bug and watching it fail. A fourth check, "does this selector start on
a class the stylesheet owns", was written and dropped: it flagged 269
legitimate root modifiers, because ownership is a property of which element
emits the class, not of the selector. That question is answered in the
browser instead, by `style-scan.js`.

Clean at the end: 641 fallback-less token references resolved across 2,980
elements, 1,077 rules over 17 stylesheets reaching no foreign widget, no
foreign font in any of the 19, and no horizontal overflow at any of 36
widths (every breakpoint plus a pixel either side).

## Round 5 — description, notice, counter, FAQ

Built against the Figma screenshots and checked with computed styles.

**Collection description** gained a heading and a fallback pair. The fallback
is templated per page (`%category%`, `%shop%`) rather than one block of copy
repeated everywhere: identical text on every category archive is duplicate
content, so a single generic paragraph would cost more in search than showing
nothing at all. `test-fallback.php` checks that every category's heading and
body differ from one another, that no `%token%` survives, that the shop page
gets its own variant, and that a real term description or typed text always
wins over the fallback.

**Notice** now measures 1240 wide with a 101px band, Outfit SemiBold 20/100%
title, Light 12/100% text and a 183x38 #7CAEB2 button at radius 5 — all
verified from `getComputedStyle`, not from the markup. The accent bar is a
sibling of the copy inside one `__lead` wrapper: as a direct child of the band
it wrapped independently on a phone and a 2px bar landed on its own line above
the title.

**Counter** is 36/16/12 in green-900 over #C6C6C6, all at a 100% line height.
Its single-column breakpoint moved from 420 to 360: at 375 — the commonest
phone — two up costs 270px of height against 459px stacked with nothing
clipped, and keeps the figures reading as one grid.

**FAQ** follows the sheet exactly: 32/150% heading, an 857 list at gap 12,
rows at radius 12 with #A5C7CA closed and #3C868C open, questions at
SemiBold 16/130% and 88%, answers at 14/100%/0.1px and 70%, 14px between a
question and its answer and 16px to the bottom edge. The supplied backdrop is
painted on a `::before` layer so the Section background colour control still
works underneath it.

Two bugs caught while building it, both the same shape as earlier rounds:

1. **Both chevrons drew at once on every shut row.** `.pfh-faq__chev svg`
   scored 0,1,1 with its `display: block`, against 0,1,0 for the plain-class
   hiding rules — so the down and up arrows stacked and read as an hourglass.
   The hiding rules carry the element too now.
2. **The supplied chevrons filled solid black.** Their files carry
   `fill="none"` on the `<svg>`, and the icon registry emits only the paths,
   so each one drew as a filled triangle rather than a 1.5px stroke.

Also fixed across all five shop widgets: none of them had a side gutter, so at
a viewport the same width as the container they sat flush against the screen
edge. They now pad by `--pfh-gutter` and widen their cap to match, which is
the pattern `pfh-cta.css` already used.

`07-four-preview.php` renders the four on a bare page with the plugin's own
CSS and JS inlined, at the designed width, for checking against Figma.

## Round 6 — the two cards are not one card

The shop grid and the home slider share `trait-pfh-product-card.php`, and the
archive's values had been set **in the shared controls**: `cartPosition` =
`block`, `cartBg` = `#7caeb2`, `cartRadius` = `5`. Every product slider on the
site therefore grew the archive's full-width TOEVOEGEN bar. Reported by the
client, not by any test — which is why `test-cards-differ.php` now exists.

The trait holds the slider's values again (`br`, `#51604f`, `999`) and the
archive sets its own in `card_defaults()`, alongside `cartReveal => none` so
its bar is always visible. The rule to keep: **a default in the trait is the
slider's default.** Anything the archive wants differently belongs in
`card_defaults()`.

Two smaller differences the designs call for, now controls rather than
assumptions:

- `priceSuffixOld` — the slider repeats "incl. VAT" on the struck-through
  price, the shop grid puts the suffix on the current price only (and reads
  "incl. BTW").
- `reviewShowStars` — the star row is a choice. It stays on everywhere; the
  shop sheet appears to show the count alone, so this is the one switch.

`test-cards-differ.php` asserts each card's own settings **and** that
`cartPosition`, `cartRadius`, `cartReveal` and `cartBg` differ between the two,
so a future edit that collapses them fails rather than ships.

`pfh-cards-html.php` renders both cards on one page for a side-by-side look.

Also this round: the notice's accent bar is off by default. The vertical marks
beside the title in the Figma frame are its own layout guides, not a rule in
the design — the control stays for anyone who wants one.

The FAQ chevrons were verified byte-for-byte against the supplied files: both
`d` attributes are identical to `Vector-4.svg` and `Vector-3.svg`, the
`0 0 14 8` viewBox is registered for each, and the state mapping is Vector-4
(down) on a shut row, Vector-3 (up) on the open one.

## Round 7 — the plugin must not touch routing on activation

Reported by the client: **activating the plugin turned the shop page into the
home page.** Not reproducible in this testbed, but the mechanism was found and
it is serious.

`PFH_Widgets_Permalinks` shipped **switched on**: `product_mode` defaulted to
`'slug'` and `category_mode` to `'full_path'`, neither of which is `'default'`,
so `active()` returned true and `parse_request` + `template_redirect` were
hooked the moment the plugin was activated — before anyone had opened its
settings screen. A trading shop had its URL handling taken over by an install.
Both now default to "Use WooCommerce settings": **activating the plugin changes
no routing whatsoever**, and the manager is opt-in.

Behind that, two real defects in the resolver itself:

1. **A post type archive was not treated as resolved.** `/shop/` arrives as
   nothing but `['post_type' => 'product']`, which fell through every check,
   so a product or a category sharing that slug could take the shop page over.
2. **Reserved paths were compared against `$_SERVER['REQUEST_URI']`.** On an
   install in a subdirectory that carries the subdirectory in front
   (`/store/shop/`) while `get_page_uri()` returns `shop`, so the shop page
   lost its protection exactly where the paths differ. The guard takes
   `$wp->request` now — the path WordPress already parsed. `test-services.php`
   caught this one: ten resolver assertions went red the moment the guard went
   in, because with no REQUEST_URI every path read as reserved.

The shop, cart, checkout, my-account, terms, front and posts pages are now off
limits to the resolver, filterable via `pfh_widgets_reserved_paths`.
`test-routing.php` covers a fresh activation, each reserved page and its
paginated form, a subdirectory install, and a path that merely starts with the
same letters.

### The shop card is pinned, not defaulted

The client's shop page kept rendering the slider's round hover button long
after the default had moved. Bricks stores an element's settings when the page
is saved, so a card added under an older build carries that build's value for
ever and **no change to a PHP default will ever reach it**.

`cartPosition` and `cartReveal` are therefore forced in the archive's
`render()` and removed from its panel: the shop card's button is the
full-width TOEVOEGEN bar, always visible, because that is what the card is.
`test-cards-differ.php` renders an element carrying the old stored values and
asserts the old layout cannot come back.

Also this round: the notice's accent bar is gone entirely — markup, CSS and
control — so no stored setting can revive it; the shop card shows the review
count without stars, per the sheet; and the FAQ list is 1040 wide.

## Round 8 — moving a default is not enough

The client reported the routing problem again after round 7: "no changes at
all". Round 7 moved `product_mode` and `category_mode` to `'default'`, which
only helps an install that has **never saved** the permalink settings. Theirs
had: the option existed with `slug` / `full_path` in it, so `active()` stayed
true and the manager kept running exactly as before. A default is only a
default.

So there is now a master switch, `enabled`, off unless it was deliberately
turned on, checked before anything else:

```php
if ( ! self::get( 'enabled', false ) ) {
    return false;   // active()
}
```

An install with the old modes stored reads them back fine and still does
nothing with them. `test-routing.php` covers exactly that case.

**Legacy redirects are 302 now, not 301.** A browser caches a 301 and keeps
following it long after the plugin that sent it was fixed or removed, so a
wrong one during setup cannot be taken back without the visitor clearing their
own cache — which is indistinguishable from "your fix did nothing". `301` is
opt-in via `permanent_redirect`, for once the URL shape is final.

**`class-pfh-diagnostics.php`** was added for the same reason: a problem that
will not reproduce on a clean install has to be read on the install that has
it. `/wp-admin/admin.php?page=pfh-widgets&pfh_diagnostics=1` prints, read-only,
whether the manager is on, what is stored in the option, whether
`parse_request` and `template_redirect` are hooked, the shop page id and
permalink, the front-page settings, and how many `post_type=product` rewrite
rules WordPress is actually holding — the last of which is what a missing
`/shop/` usually comes down to.

## Round 9 — Bricks saves every control, so defaults never arrive

A screenshot of the client's live page settled what three rounds of guessing
could not. Their shop card rendered with a **full-width button** — the thing
pinned in `render()` in round 7 — but in the **old olive colour, with no
label, stars still on and "incl. VAT"**. Every pinned value arrived; every
value that was merely a *default* did not.

That is the whole of "no changes at all". Bricks writes a value for every
control into the page when it is saved, and hands the element those settings
at render. A default is only consulted for a key that is **missing**, so an
element built under an older release keeps that release's card for ever and no
amount of changing defaults can reach it.

Two fixes, both needed:

1. **`CARD_VERSION` / `migrate_card()`.** The card's settings carry a stamp.
   An element saved before the current revision has the design-owned keys
   dropped once, so the current defaults apply; the stamp is then current and
   the editor's own choices are kept from then on. Layout choices — columns,
   per page, container width — are never in that list and never touched.
2. **`get()` consults the control's own default.** Dropping a stored value
   only helps if something supplies the right one. `card_defaults()` retunes
   the shared controls for this element (14px title where the slider wants 20,
   teal button where the slider wants olive) and those live on the control,
   not at the call site — so a dropped setting fell through to the trait's
   fallback, which is the *slider's* value, and the archive quietly rendered
   the slider's card again. `get()` now reads settings, then this element's
   control default, then the caller's.

`test-migrate.php` renders an element carrying exactly the client's stored
values and asserts the corrected card comes back, that a deliberately chosen
colour, label and title size are still kept, and that layout settings survive
untouched.

**The lesson for this codebase:** in Bricks, a default reaches an element
exactly once — the moment it is first added to a page. Anything the design
owns rather than the editor must be pinned in `render()` or migrated. Treating
it as a default means shipping a fix that cannot arrive.

## Round 10 — the revision pattern generalised, and four design fixes

The client confirmed the routing problem was a Bricks template condition, not
the plugin. What remained was design, and most of it was the same root cause
as round 9: a corrected default cannot reach an element Bricks has saved.

`trait-pfh-design-revision.php` now carries that pattern for any element:
`apply_design_revision( $revision, $keys )` at the top of `render()`, a
`designRevision` control to hold the stamp, and `setting()` — which reads the
stored value, then **this element's own control default**, then the caller's
fallback. That middle step is essential: without it a dropped setting falls
through to whatever the call site passes, which for shared controls is the
slider's value.

Applied to the notice, the counter and the FAQ, which is why the notice radius
(14 → 5) and the FAQ width finally arrive. `test-revision.php` asserts each
corrected value lands, that a choice made *after* the stamp is kept, and that
typed copy is never touched.

Four fixes:

1. **Every stroked icon was filling solid black.** SVG's default fill is
   black, and the registry emits paths without a root `fill`. The FAQ
   chevrons were patched individually in round 5; the basket and the filter
   icon were still blobs. `fill="none"` now goes on the `<svg>` root — every
   shape declares its own fill or stroke, so nothing else changes.
2. **The add-to-cart AJAX was working all along.** The item was added, the
   cart count moved, fragments refreshed. What failed was the *confirmation*:
   the added state hides the label and shows a 13px tick, which is legible on
   the slider's 46px round button and invisible on a 250px bar. The shop
   card's button now says TOEGEVOEGD.
3. **The struck-through price lost its size.** The Figma draws it at 10px
   against a 12px current price; that lived in the hard-coded archive CSS
   removed in round 4, so it inherited 12. It is a control now
   (`oldPriceSize`), empty meaning "match the current price".
4. **The FAQ had no animation and jumped.** `<details>` snaps, and one-open
   -at-a-time slammed the previous row shut under the cursor. The summary's
   toggle is taken over and the row's height animated between states, with
   in-flight animations cancelled so a fast second click cannot fight the
   first. `prefers-reduced-motion` and a no-JavaScript page both fall back to
   plain open/close.

FAQ list width: 1040 → 940, as asked.

## Round 11 — the stamped migration was eating the editor's work

Round 9 and 10 stamped an element's settings with a revision and cleared the
design-owned keys whenever the stamp was behind. That was wrong, and the
client found it: **a builder does not necessarily store a value that equals
its control default.** The stamp therefore never persisted, the reset ran on
every single render, and it deleted whatever had just been set. It reached
them as "uploading the icon doesn't work" and "the title size won't take".

Both the archive's `migrate_card()` and the shared trait now compare instead
of stamping: a setting is dropped **only while it still holds the default it
used to have**. An uploaded icon, a 17px title, a colour someone picked —
none of those equal it, so none are touched. No stamp, nothing to persist,
nothing that can eat anyone's work.

`prepare()` — `ajax_render()` was not `render()`. The filter endpoint builds
the element from the stored settings and calls `render_results()` directly,
so on a filtered or paged request the controls were never registered, the
migration never ran and the card's button was never pinned. The symptom was a
second page whose buttons had lost their TOEVOEGEN label, because that label
is only drawn for the full-width button and the stored settings still said
otherwise. Both entry points share one `prepare()` now.

**Out of stock** is a state of its own: a `<span>`, not a link, with no basket
icon, nothing to click, nothing to tab to, and "Niet beschikbaar" on it. A
link to the product page there only invites a second disappointment.

Also: the cart icon's black background was the round-10 `fill="none"` fix
arriving — the client's screenshots predated it, confirmed by rendering the
icon at 56px on all three button colours. Notice radius back to 14 as asked,
card title 17px, stars back on the shop card.

`test-card-states.php` covers the out-of-stock button, an uploaded icon
surviving, a chosen title size and label surviving, the design still reaching
an untouched card, and page two keeping its label.

One fixture note: `test-engine.php` used to hard-code "30 in stock". It counts
the out-of-stock products itself now, so the stock-state suite can change them
without breaking an unrelated assertion.

## Round 12 — the product highlight banner

A twentieth element, `pfh-highlight`: the wide card that puts one product
forward. Eyebrow, two-line title, pitch, ticked selling points, price.

Built to the brief's split: **every piece of copy is its own control and every
one takes dynamic data**, so an ACF field drops into any of them later, while
the shipped defaults carry the launch copy — which is why it renders as a
finished banner rather than an empty frame. The image is the same, with the
supplied artwork as its fallback. **The prices are the exception and come from
a real WooCommerce product**, with the saving worked out rather than typed, so
the banner cannot drift out of step with the shop. A manual mode covers an
offer that is not one product.

Two things worth keeping:

1. **The seam.** The supplied photograph has its own background baked in and
   it is not quite the card's fill, so the join showed as a line down the
   banner. The image's inner edge fades out over a short distance, which hides
   it without touching the product. A cut-out on transparency needs none of
   that, so it is a setting.
2. **One link, not a link round everything.** The whole card is clickable via
   a stretched pseudo-element on the title's anchor. Wrapping the card in an
   `<a>` would fold every word inside into the link's own name and make the
   banner useless to a screen reader.

`audit.php` earned its keep again: the first draft wrote `--pfh-hl-title`,
`--pfh-hl-px`, `--pfh-hl-py` and `--pfh-hl-eyebrow` inline **and** overrode
them in media queries. An inline style beats a stylesheet, so none of the
responsive sizes would ever have applied. They go through `-set` tokens now,
which is the pattern the rest of the plugin already uses.

### A real flaw the highlight's tests exposed

`setting()` fell back to the control default whenever a value was empty — so
**clearing a field put the default text straight back** and there was no way
to remove an eyebrow or a button. It now distinguishes *absent* (use the
default) from *present but empty* (the editor cleared it on purpose, leave it
cleared). Fixed in the shared trait and in the archive's own copy.

`test-highlight.php` covers the shipped defaults, live prices from a sale
product, a full-price product showing one price, the typed fallbacks, every
field being replaceable, a chosen image winning over the supplied one, the
single-anchor rule, and rendering nothing when it has nothing to say.

## Round 13 — recently viewed

A twenty-first element, `pfh-recent`. It **extends** `PFH_Element_Products`
rather than copying it, so the card, the drag behaviour and every style
control stay identical by construction — the two cannot drift the way a
duplicated element would. The source is pinned and removed from the panel:
"recently viewed" is what this element is, not one option among several.

The history comes from WooCommerce's own `woocommerce_recently_viewed`
cookie, which `wc_track_product_view()` already maintains — newest last,
capped at 15. `PFH_Widgets_Helpers::recently_viewed()` reverses it so the
newest is first. Reading Woo's cookie rather than keeping a second list means
the history is already populated for anyone who has browsed the shop.

**It loads after the page by default, and that is the point.** The history is
per-visitor, so markup rendered into a cached page would show one shopper what
the previous one had been looking at. The element ships an empty hidden
placeholder — no product name, no card markup, nothing of anyone's in the page
source — and fetches that visitor's own slider afterwards. There is a setting
to render inline for a site with no page caching.

With nothing viewed it renders nothing: no heading, no empty rail, no layout
shift. `post__in` gets `[ 0 ]` rather than an empty array, because WP_Query
ignores an empty `post__in` and would quietly turn "products you looked at"
into "every product in the shop".

### A latent bug this exposed

`PFH_Widgets_Helpers::is_builder_context()` ended in `return is_admin()` — and
`is_admin()` is **true on admin-ajax.php**. Every front-end AJAX render
therefore counted as a builder call, and elements put out their editor
placeholders. A visitor with no history was served *"No products matched.
Check the Products group, or switch to manual cards."* A plain AJAX request is
now treated as the front end; the builder's own calls are still caught by
`bricks_is_builder_call()` above it. The archive dodged this only because its
AJAX path calls `render_results()` rather than `render()`.

`test-recent.php` covers an empty cookie, a cookie of separators, an id that
is not a product, the newest-first order, products never viewed being absent,
the source not being offered, and — the one that matters — that the deferred
markup contains no product name and no card.

## Round 14 — "Incompatible Archive."

The release would not install. That message comes out of `unzip_file()` and
says nothing about why, so the first job was to stop guessing: `pfh-test-zip.php`
runs the real archive through **WordPress's own installer path** — `unzip_file()`,
`get_plugin_data()`, and the PclZip fallback — rather than trusting that
`unzip` on a Mac can read it.

It read the archive perfectly. 88 entries, correct single top-level folder, no
`__MACOSX`, no resource forks, and WordPress identified it as
"Products For Home – Bricks Widgets 1.25.0". **The format was never the
problem.** The size was: the release had reached **1,097,005 bytes**, and
hosts commonly cap uploads at 1M. A file that exceeds the limit arrives
truncated or not at all, and what the user sees is "Incompatible Archive."

Three things were wrong with the payload, one of them self-inflicted:

- **`pfh-highlight.jpg` had grown.** Cropping its artefact band in round 12
  re-encoded an already-compressed JPEG at quality 90, taking it from 118K to
  176K — the wrong direction. At 76, progressive, it is 77K with nothing
  visible lost.
- **`pfh-hero-branch.png` was five times oversampled.** 800px wide for a
  decoration rendered at 160. Halved to 400 — still 2.5x for a retina screen
  — it went from 316K to 96K. Scaling the whole canvas keeps the visible
  branch in exactly the same place inside its box, so no layout moved.
- **`pfh-faq-bg.jpg`** likewise, 32K to 19K.

**770K, with 272K of headroom to a 1M limit.**

`audit.php` now estimates the packaged size and fails over a 900K budget,
naming the heaviest files when it does — verified by planting a 400K file and
watching it go red. A plugin that cannot be uploaded is not a plugin, and
that failure had no other way of being caught before it reached the client.

## Round 15 — the artwork moved out of the plugin

Following on from the upload failure: the six fallback images were 426K of a
770K package, for artwork that exists only until the real ACF fields are
connected. They now live in this repository and are served over jsDelivr, and
the plugin carries none of them.

**350K, down from 1,097,005 bytes — 67% smaller, with 674K of headroom.**

`PFH_Widgets_Assets::img()` decides where each one comes from, in order:

1. **A local copy in `assets/img/`**, if one is there. A site can self-host
   any or all of them by dropping the file in, with no setting to find.
2. **The pinned tag over jsDelivr.** A tag, not `main`, so the CDN can cache
   for ever and pushing to the branch can never change what a live site is
   already showing. `IMAGE_REF` is raised when the artwork changes.
3. **Whatever `pfh_widgets_image_base` returns**, for a site that would
   rather serve them from its own media library.

The trade being made: the fallbacks now depend on a public repository staying
public. They are fallbacks, the filter exists, and a local copy always wins —
but it is a real external dependency and worth saying out loud.

`test-images.php` covers all three resolution paths, a base with and without a
trailing slash, that the ref is a tag rather than a branch, and that no
element emits a plugin-local path any more. The six URLs were also fetched
from jsDelivr and checked byte-for-byte against the files.

`audit.php` discounts `assets/img/` from its size estimate, since that is what
the release build excludes — its ~372K estimate against a real 350K zip is
close and deliberately conservative. The path match needed a fix to get there:
the stored paths are relative, so testing for `/assets/img/` never matched and
every image was still being counted.

## Round 16 — a white screen, and the blind spot that let it ship

v1.25.0 took a site down completely. Every request, wp-admin included:

```
Fatal error: Uncaught Error: Class "Bricks\Element" not found
```

**Bricks is a theme.** Themes load at `after_setup_theme`, long after
`plugins_loaded`. Wiring the recently-viewed AJAX handler, I required its
element file at boot — and an element extending `\Bricks\Element` cannot even
be *parsed* before the theme exists. Not a degraded feature: a fatal on every
page, with no way back into the admin to deactivate it.

The archive had done this correctly all along — required inside its callback,
behind `class_exists( '\Bricks\Element' )`. I knew the rule, then broke it two
rounds later. The action is now registered at boot (which costs nothing) and
the class is loaded when the action fires, by which time the theme is up.

### Why no suite caught it

`mu-bricks-stub.php` defines `\Bricks\Element` — and mu-plugins load *before*
plugins. So in this testbed the class always existed by the time the plugin
booted, and the one ordering that matters was never exercised. Every suite
passed on a build that could not load on a real site.

`dev/test-boot-without-bricks.php` closes it. It runs in a **bare PHP process
with no stub at all**, includes every boot file for real, and fails if PHP
cannot get through them. It also checks statically that no element is required
at boot and that every element require sits behind a Bricks guard.

Verified both ways: it exits 1 against the v1.25.1 tree that broke the site,
naming the two unguarded requires, and passes against the fix. Run it before
any release — it is the only check here that sees the real load order.

### Verifying it, rather than asserting it

`test-boot-without-bricks.php` proves the files *parse*. That is not the same
as proving a site comes up, so `wp-testbed/test-no-bricks.php` runs the real
thing: a live WordPress with the stub moved out of `mu-plugins`, checking that
the plugin activates, boots its services, loads **no** element class while
doing so, renders wp-admin and its own settings screen as a real
administrator, shows a notice saying Bricks is needed instead of dying, and
survives deactivation and reactivation with its rewrite rules intact.

```bash
mv wp-content/mu-plugins/bricks-stub.php /tmp/
curl .../pfh-test-no-bricks.php
mv /tmp/bricks-stub.php wp-content/mu-plugins/
```

It skips itself if Bricks is loaded, because then the ordering it guards is
not in play. 17 assertions. Both suites go into the release check.
