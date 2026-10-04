"""Convert a live (rendered) Elementor page into Bricks elements.

Used for the pages moved from Elementor to Bricks on 2026-10-04/05 (the
recipes, /partner-worden/ and the landing pages). It reads the page as the
live site renders it, not the Elementor data on staging: staging has no
XStore, and Elementor leaves XStore's widgets (product grids, headlines,
testimonials) out of a document it cannot render.

Input: live2/<slug>.html (public HTML of productsforhome.nl, the slug with
'/' as '_') and live2css/post-<id>.css (that page's Elementor CSS, for
alignment, columns, backgrounds and spacer heights). Output: out/<id>.json,
a flat Bricks array plus a report of what was mapped or left out.

    curl -s https://productsforhome.nl/samos/ -o live2/samos.html
    curl -s https://productsforhome.nl/wp-content/uploads/elementor/css/post-2191.css -o live2css/post-2191.css
    python3 live2bricks.py samos

The sections it writes carry .pfh-legacy (assets/css/pfh-legacy.css). Saving
goes through the Bricks builder's own save call from a logged-in browser on
staging, after checking each image and product ID exists there; a page that
already has a Bricks layout is left alone.
"""
import json, os, random, re, sys
from bs4 import BeautifulSoup, NavigableString, Tag

LIVE = re.compile(r'^https?://(www\.)?productsforhome\.nl')
rng = random.Random()
MOVED = {'/biologische-olijfolie/': '/olijfolie/biologische-olijfolie/'}


def rid():
    return ''.join(rng.choice('abcdefghijklmnopqrstuvwxyz') for _ in range(6))


def rel(u):
    if not u:
        return u
    u = u.strip()
    m = LIVE.match(u)
    if m:
        u = u[m.end():] or '/'
    # WordPress would answer /x with a redirect to /x/; link to /x/ straight away.
    if u.startswith('/') and not u.startswith('/wp-content/') and not re.search(r'[?#]|\.[a-z0-9]{2,4}$', u) and not u.endswith('/'):
        u += '/'
    # Links that live itself redirects: point at where they end up.
    return MOVED.get(u, u)


# ---------------------------------------------------------------- css

def lum(col):
    col = col.strip().lower()
    m = re.match(r'#([0-9a-f]{3,8})$', col)
    if m:
        h = m.group(1)
        if len(h) in (3, 4):
            h = ''.join(c * 2 for c in h[:3])
        r, g, b = (int(h[i:i + 2], 16) for i in (0, 2, 4))
    else:
        m = re.match(r'rgba?\((\d+),\s*(\d+),\s*(\d+)', col)
        if not m:
            return 1.0
        r, g, b = (int(x) for x in m.groups())
    return (0.299 * r + 0.587 * g + 0.114 * b) / 255


def css_map(css, page_id):
    """data-id -> {prop: value} from desktop rules (no @media)."""
    css = re.sub(r'/\*.*?\*/', '', css, flags=re.S)
    out, i, n = {}, 0, len(css)
    flat = []
    while i < n:
        if css.startswith('@media', i) or css.startswith('@supports', i):
            j = css.index('{', i)
            depth, k = 1, j + 1
            while depth and k < n:
                depth += {'{': 1, '}': -1}.get(css[k], 0)
                k += 1
            i = k
            continue
        j = css.find('{', i)
        if j < 0:
            break
        k = css.find('}', j)
        flat.append((css[i:j].strip(), css[j + 1:k]))
        i = k + 1
    for sel, decl in flat:
        for one in sel.split(','):
            ids = re.findall(r'\.elementor-element-([0-9a-f]{7,8})', one)
            if not ids:
                continue
            target = ids[-1]
            tail = one.split('elementor-element-' + target, 1)[1].strip()
            # The section's own background hides behind motion-effect selectors.
            if tail.startswith(':not(.elementor-motion-effects') or tail.startswith('> .elementor-motion-effects-container'):
                tail = ''
            props = out.setdefault(target, {})
            for d in decl.split(';'):
                if ':' not in d:
                    continue
                p, v = d.split(':', 1)
                p, v = p.strip(), v.strip()
                key = p if not tail else tail + ' ' + p
                props[key] = v
                if p == 'text-align' and 'align' not in props:
                    props['align'] = v
    return out


# ---------------------------------------------------------------- html

def clean(html, inline=False):
    s = BeautifulSoup(html or '', 'html.parser')
    for c in s.find_all(['script', 'style', 'noscript']):
        c.decompose()
    for n in s.find_all(style=True):
        ta = re.search(r'text-align:\s*(center|right)', n['style'])
        del n['style']
        if ta and not inline:
            n['style'] = 'text-align: ' + ta.group(1) + ';'
    for f in s.find_all('font'):
        f.unwrap()
    for im in s.find_all('img'):
        im['src'] = real_src(im)
        im['class'] = [c for c in im.get('class', []) if not c.startswith('lazyload') and not c.startswith('et-lazyload')]
    for n in s.find_all(True):
        for a in list(n.attrs):
            if a.startswith('data-') or a in ('id', 'dir', 'role', 'aria-hidden', 'tabindex', 'loading', 'decoding', 'srcset', 'sizes', 'fetchpriority'):
                del n[a]
    for n in s.find_all(class_=True):
        del n['class']
    for sp in s.find_all('span'):
        if not sp.attrs:
            sp.unwrap()
    for a in s.find_all('a', href=True):
        a['href'] = rel(a['href'])
    for im in s.find_all('img', src=True):
        im['src'] = rel(im['src'])
    for p in s.find_all(['p', 'h2', 'h3', 'h4', 'h5', 'h6', 'li']):
        if not p.get_text().replace('\xa0', ' ').strip() and not p.find(['img', 'iframe', 'br']):
            p.decompose()
    out = str(s).replace('\xa0', ' ')
    out = re.sub(r'\s+', ' ', out)
    out = re.sub(r'\s*(</?(p|ul|ol|li|h[1-6]|div)[^>]*>)\s*', r'\1', out)
    out = re.sub(r'(<br\s*/?>\s*)+(</p>)', r'\2', out)
    return out.strip()


def real_src(im):
    real = im.get('data-src') or im.get('data-lazy-src') or im.get('data-o-src')
    if real:
        return real
    if im.get('data-srcset'):
        options = []
        for part in im['data-srcset'].split(','):
            bits = part.strip().split()
            if len(bits) == 2 and bits[1].endswith('w'):
                options.append((int(bits[1][:-1]), bits[0]))
        if options:
            want = int(im.get('width') or 0)
            exact = [u for w, u in options if w == want]
            return exact[0] if exact else max(options)[1]
    return im.get('src') or ''


def text_of(el):
    return re.sub(r'\s+', ' ', el.get_text(' ', strip=True)).replace('\xa0', ' ').strip() if el else ''


def inner(el):
    return ''.join(str(c) for c in el.contents) if el else ''


def body(w):
    return w.find(class_='elementor-widget-container', recursive=False) or w


def node(name, settings=None, children=None):
    return {'id': rid(), 'name': name, 'settings': settings or {}, 'children': children or []}


def with_center(base, align):
    return base + (' pfh-legacy__center' if align == 'center' else '')


class Page:
    def __init__(self, slug, html, css):
        self.slug = slug
        self.soup = BeautifulSoup(html, 'html.parser')
        self.root = self.soup.find(attrs={'data-elementor-type': 'wp-page'})
        self.id = int(self.root['data-elementor-id'])
        self.css = css_map(css, self.id)
        self.report = []
        self.reviews = False
        self.out = []
        self.current = None

    def align(self, eid, default=''):
        p = self.css.get(eid, {})
        for k in ('align', '.elementor-heading-title text-align', '.elementor-widget-container text-align', 'text-align'):
            if k in p:
                v = p[k]
                return 'center' if v == 'center' else ('right' if v in ('right', 'end') else 'left')
        return default

    # ------------------------------------------------------------ widgets
    def widget(self, w):
        t = w.get('data-widget_type', '').replace('.default', '')
        eid = w.get('data-id')
        b = body(w)
        if t == 'heading':
            h = b.find(class_='elementor-heading-title')
            if not h or not text_of(h):
                return None
            tag = h.name if re.match(r'h[1-6]$', h.name) else 'h3'
            return node('heading', {'text': clean(inner(h), True), 'tag': tag, '_cssClasses': with_center('pfh-legacy__h', self.align(eid))})
        if t in ('etheme_animated_headline', 'etheme_advanced_headline'):
            h = b.find(re.compile(r'^h[1-6]$'))
            items = []
            if h and text_of(h):
                al = 'center' if b.find(class_='elementor-align-center') or self.align(eid) == 'center' or t == 'etheme_animated_headline' else self.align(eid)
                items.append(node('heading', {'text': text_of(h), 'tag': h.name, '_cssClasses': with_center('pfh-legacy__h', al)}))
                after = b.find(class_='etheme-a-h-text-after')
                if after and text_of(after):
                    items.append(node('text', {'text': '<p>' + clean(inner(after), True) + '</p>', '_cssClasses': with_center('pfh-legacy__text', al)}))
            return {'many': items}
        if t in ('text-editor', 'woocommerce-archive-etheme_description_second'):
            if 'Placeholder for ajax description' in text_of(b):
                return None
            html = clean(inner(b))
            if not BeautifulSoup(html, 'html.parser').get_text().strip() and '<img' not in html and '<iframe' not in html:
                return None
            return node('text', {'text': html, '_cssClasses': with_center('pfh-legacy__text', self.align(eid))})
        if t == 'image':
            im = b.find('img')
            if not im:
                return None
            m = re.search(r'wp-image-(\d+)', ' '.join(im.get('class', [])))
            url = rel(real_src(im))
            st = {'image': {'id': int(m.group(1)) if m else '', 'filename': url.split('/')[-1], 'size': 'large', 'full': url, 'url': url}, '_cssClasses': with_center('pfh-legacy__img', self.align(eid, 'center'))}
            if im.get('alt'):
                st['altText'] = im['alt']
            a = im.find_parent('a')
            if a and a.get('href'):
                st['link'] = {'type': 'external', 'url': rel(a['href'])}
            return node('image', st)
        if t == 'spacer':
            v = self.css.get(eid, {}).get('--spacer-size', '50px')
            px = int(float(re.sub(r'[^0-9.]', '', v) or 50))
            return node('div', {'_height': '%dpx' % round(px * 0.6), '_cssClasses': 'pfh-legacy__space'})
        if t == 'divider':
            return node('div', {'_cssClasses': 'pfh-legacy__rule'})
        if t in ('button', 'text_button'):
            a = b.find('a')
            label = text_of(b.find(class_='elementor-button-text') or a)
            if not label:
                return None
            href = rel(a.get('href')) if a and a.get('href') else ''
            if not href:
                href = '/contact/' if 'contact' in label.lower() else ''
                self.report.append('button "%s" has no link on live; now %s' % (label, href or '#'))
            col = w.find_parent(attrs={'data-element_type': 'column'})
            centred_col = bool(col) and any(k.endswith('justify-content') and v == 'center' for k, v in self.css.get(col.get('data-id'), {}).items())
            al = 'center' if (centred_col or w.find_parent(class_='elementor-align-center') or 'elementor-align-center' in w.get('class', []) or self.align(eid) == 'center' or self.css.get(eid, {}).get('.elementor-button-wrapper text-align') == 'center') else ''
            return node('button', {'text': label, 'link': {'type': 'external', 'url': href or '#'}, '_cssClasses': with_center('pfh-legacy__btn', al)})
        if t in ('icon-list', 'etheme_icon_list'):
            lis = []
            for li in b.find_all('li'):
                label = text_of(li.find(class_=re.compile('icon-list-text|etheme-icon-list-item-text')) or li)
                if not label:
                    continue
                a = li.find('a', href=True)
                lis.append('<li>' + ('<a href="%s">%s</a>' % (rel(a['href']), label) if a else label) + '</li>')
            if not lis:
                return None
            return node('text', {'text': '<ul>' + ''.join(lis) + '</ul>', '_cssClasses': with_center('pfh-legacy__checks', self.align(eid))})
        if t in ('toggle', 'accordion', 'nested-accordion'):
            qa = []
            for item in b.select('.elementor-toggle-item, .elementor-accordion-item, details.e-n-accordion-item'):
                q = item.select_one('.elementor-toggle-title, .elementor-accordion-title, .e-n-accordion-item-title-text')
                a = item.select_one('.elementor-tab-content, .e-con')
                if q and text_of(q):
                    qa.append({'q': text_of(q), 'a': clean(inner(a)) if a else ''})
            return {'breakout': node('pfh-faq', {'items': qa, 'fromCategory': False})}
        if t == 'testimonial-carousel':
            seen, cards = set(), []
            for sl in b.select('.swiper-slide'):
                txt = text_of(sl.select_one('.elementor-testimonial__text'))
                if not txt or txt in seen:
                    continue
                seen.add(txt)
                kids = [node('text-basic', {'text': txt, 'tag': 'p', '_cssClasses': 'pfh-legacy__quote-text'})]
                cite = sl.select_one('.elementor-testimonial__cite')
                lines = [text_of(x) for x in (cite.find_all('span', recursive=False) if cite else []) if text_of(x)]
                for k, line in enumerate(lines[:2]):
                    kids.append(node('text-basic', {'text': line, 'tag': 'p', '_cssClasses': 'pfh-legacy__quote-head' if k == 0 else 'pfh-legacy__quote-name'}))
                cards.append(node('block', {'_cssClasses': 'pfh-legacy__quote'}, kids))
            if not cards:
                return None
            return node('block', {'_cssClasses': 'pfh-legacy__quotes'}, cards)
        if t == 'etheme_product_grid':
            ids = []
            for p in b.select('.product'):
                m = re.search(r'post-(\d+)', ' '.join(p.get('class', [])))
                if m and m.group(1) not in ids:
                    ids.append(m.group(1))
            if not ids:
                return None
            st = json.loads(w.get('data-settings') or '{}')
            cols = int(st.get('cols') or 4)
            return {'breakout': node('pfh-product-grid', {
                'heading': '', 'intro': '', 'source': 'ids', 'productIds': ', '.join(ids), 'limit': min(24, len(ids)),
                'columns': cols, 'columnsTablet': int(st.get('cols_tablet') or 2), 'columnsMobile': int(st.get('cols_mobile') or 2),
                'paddingTop': 16, 'paddingBottom': 56, 'bgColor': {'hex': '#ffffff'}, 'headGap': 0,
            })}
        if t == 'etheme_post_navigation':
            links = []
            prev = b.select_one('.etheme-post-navigation__prev a')
            nxt = b.select_one('.etheme-post-navigation__next a')
            here = '/' + self.slug.replace('_', '/') + '/'
            base = here.rstrip('/').rsplit('/', 1)[0] + '/'
            for side in ('prev', 'next'):
                a = prev if side == 'prev' else nxt
                if a and (not rel(a['href']).startswith(base) or rel(a['href']) == base):
                    self.report.append('%s link to %s left out: not a recipe' % (side, rel(a['href'])))
                    if side == 'prev':
                        prev = None
                    else:
                        nxt = None
            if prev:
                links.append(node('text-basic', {'text': text_of(prev.select_one('.post-navigation__prev--title')) or 'Vorige', 'tag': 'a', 'link': {'type': 'external', 'url': rel(prev['href'])}, '_cssClasses': 'pfh-legacy__nav-prev'}))
            else:
                links.append(node('div', {'_cssClasses': 'pfh-legacy__nav-gap'}))
            parent = base
            links.append(node('text-basic', {'text': 'Alle recepten', 'tag': 'a', 'link': {'type': 'external', 'url': parent}, '_cssClasses': 'pfh-legacy__nav-all'}))
            if nxt:
                links.append(node('text-basic', {'text': text_of(nxt.select_one('.post-navigation__next--title')) or 'Volgende', 'tag': 'a', 'link': {'type': 'external', 'url': rel(nxt['href'])}, '_cssClasses': 'pfh-legacy__nav-next'}))
            else:
                links.append(node('div', {'_cssClasses': 'pfh-legacy__nav-gap'}))
            return node('block', {'_cssClasses': 'pfh-legacy__postnav'}, links)
        if t == 'image-carousel':
            imgs = []
            for im in b.select('img.swiper-slide-image'):
                url = rel(real_src(im))
                if url and url not in [x['url'] for x in imgs]:
                    imgs.append({'id': '', 'filename': url.split('/')[-1], 'size': 'medium', 'full': url, 'url': url, 'alt': im.get('alt', '')})
            if not imgs:
                return None
            st = json.loads(w.get('data-settings') or '{}')
            return node('image-gallery', {'items': {'images': imgs, 'size': 'medium'}, 'columns': min(6, int(st.get('slides_to_show') or 4)), 'imageRatio': 'ratio-square', '_cssClasses': 'pfh-legacy__gallery'})
        if t == 'html':
            raw = inner(b)
            if re.search(r'<valued-widget[^>]*layout="rating"', raw):
                return node('pfh-rating', {'align': 'center'})
            if re.search(r'<valued-widget[^>]*layout="reviews"', raw):
                if self.reviews:
                    return None
                self.reviews = True
                return {'breakout': node('pfh-reviews', {})}
            self.report.append('html widget kept as code: ' + re.sub(r'\s+', ' ', raw)[:80])
            return node('code', {'code': raw.strip(), 'executeCode': True, 'noRoot': True})
        if t == 'shortcode':
            return node('shortcode', {'shortcode': text_of(b)})
        self.report.append('left out: ' + t)
        return None

    # ------------------------------------------------------------ layout
    def bg(self, eid):
        """'' for none, 'band' for a light tint, 'dark' for a dark one."""
        p = self.css.get(eid, {})
        over = p.get('> .elementor-background-overlay background-color', '')
        op = float(p.get('> .elementor-background-overlay opacity', '1') or 1)
        if over and lum(over) < 0.35 and op >= 0.4:
            return 'dark'
        col = p.get('background-color') or p.get('--background-color') or ''
        if not col or col.lower() in ('#fff', '#ffffff', '#ffffff00', 'transparent', 'rgba(0,0,0,0)', '#fff0'):
            return ''
        return 'dark' if lum(col) < 0.35 else 'band'

    def section(self, band):
        inner_ = node('container', {'_cssClasses': 'pfh-legacy__inner'})
        extra = {'band': ' pfh-legacy--band', 'dark': ' pfh-legacy--band pfh-legacy--dark'}.get(band, '')
        sec = node('section', {'_cssClasses': 'pfh-legacy' + extra}, [inner_])
        self.out.append(sec)
        self.current = {'sec': sec, 'inner': inner_, 'band': band}
        return inner_

    def target(self, band):
        if not self.current or self.current['band'] != band:
            return self.section(band)
        return self.current['inner']

    def put(self, res, parent, band):
        """Place a widget result; parent None means the running section."""
        if res is None:
            return parent
        if isinstance(res, dict) and 'many' in res:
            for r in res['many']:
                parent = self.put(r, parent, band)
            return parent
        if isinstance(res, dict) and 'breakout' in res:
            inner_ = (self.current or {}).get('inner')
            # A heading right above an FAQ becomes the FAQ's own title.
            if res['breakout']['name'] == 'pfh-faq' and inner_ and inner_['children'] and inner_['children'][-1]['name'] == 'heading':
                head = inner_['children'].pop()
                res['breakout']['settings']['title'] = re.sub(r'<[^>]+>', '', head['settings']['text'])
                res['breakout']['settings']['titleTag'] = head['settings'].get('tag', 'h2')
                if not inner_['children']:
                    self.out.remove(self.current['sec'])
            if parent is None or parent is (self.current or {}).get('inner'):
                self.out.append(res['breakout'])
                self.current = None
                return None
            parent['children'].append(res['breakout'])
            return parent
        if parent is None:
            parent = self.target(band)
        parent['children'].append(res)
        return parent

    def kids(self, el):
        """Direct Elementor children (containers, columns, widgets) of el."""
        found = []
        for c in el.find_all(True, recursive=False):
            if c.get('data-element_type') and 'elementor-hidden-desktop' in c.get('class', []):
                # A phone-only copy of something the page already shows.
                self.report.append('phone-only %s left out' % (c.get('data-widget_type') or c.get('data-element_type')).replace('.default', ''))
                continue
            if c.get('data-element_type'):
                found.append(c)
            else:
                found.extend(self.kids(c))
        return found

    def is_row(self, el):
        eid = el.get('data-id')
        p = self.css.get(eid, {})
        if el.get('data-element_type') == 'section':
            return len([c for c in self.kids(el) if c.get('data-element_type') == 'column']) > 1
        if p.get('--display') == 'grid' or 'e-grid' in el.get('class', []):
            cols = p.get('--e-con-grid-template-columns', '')
            m = re.search(r'repeat\((\d+)', cols)
            return bool(m and int(m.group(1)) > 1)
        return p.get('--flex-direction') == 'row'

    def width(self, el):
        eid = el.get('data-id')
        p = self.css.get(eid, {})
        v = p.get('--width') or p.get('width') or ''
        m = re.match(r'([\d.]+)%', v)
        if m:
            return float(m.group(1))
        m = re.search(r'elementor-col-(\d+)', ' '.join(el.get('class', [])))
        if m:
            return float(m.group(1))
        return None

    def columns(self, el, band):
        row = node('block', {'_cssClasses': 'pfh-legacy__cols'})
        cols = self.kids(el)
        for c in cols:
            col = node('block', {'_cssClasses': 'pfh-legacy__col'})
            w = self.width(c)
            if w and len(cols) > 1 and abs(w - 100.0 / len(cols)) > 2:
                col['settings']['_attributes'] = [{'id': rid(), 'name': 'style', 'value': '--pfh-col:%d' % round(w)}]
            if c.get('data-element_type') == 'widget':
                self.put(self.widget(c), col, band)
            else:
                self.fill(c, col, band)
            row['children'].append(col)
        return row

    def fill(self, el, parent, band):
        for c in self.kids(el):
            et = c.get('data-element_type')
            if et == 'widget':
                parent = self.put(self.widget(c), parent, band)
            elif self.is_row(c):
                row = self.columns(c, band)
                parent = self.put(row, parent, band)
            else:
                parent = self.fill(c, parent, band) if parent is not None else self.fill_flat(c, band)
        return parent

    def fill_flat(self, el, band):
        self.fill(el, None, band)
        return None

    def convert(self):
        for top in self.kids(self.root):
            eid = top.get('data-id')
            band = self.bg(eid)
            if self.current and self.current['band'] != band:
                self.current = None
            if top.get('data-element_type') == 'widget':
                self.put(self.widget(top), None, band)
            elif self.is_row(top):
                self.put(self.columns(top, band), None, band)
            else:
                self.fill(top, None, band)
        for n in self.out:
            if n['name'] != 'section':
                continue
            kids = n['children'][0]['children']
            while kids and kids[0]['name'] == 'div' and 'pfh-legacy__space' in kids[0]['settings'].get('_cssClasses', ''):
                kids.pop(0)
            while kids and kids[-1]['name'] == 'div' and 'pfh-legacy__space' in kids[-1]['settings'].get('_cssClasses', ''):
                kids.pop()
        self.out = [n for n in self.out if n['name'] != 'section' or n['children'][0]['children']]
        return self.out


def flatten(nodes):
    flat = []

    def walk(n, parent):
        flat.append({'id': n['id'], 'name': n['name'], 'parent': parent, 'children': [c['id'] for c in n['children']], 'settings': n['settings']})
        for c in n['children']:
            walk(c, n['id'])
    for n in nodes:
        walk(n, 0)
    return flat


def show(n, d=0):
    s = n['settings']
    bits = [n['name']]
    if s.get('_cssClasses'):
        bits.append('.' + s['_cssClasses'].replace(' ', '.'))
    if s.get('tag'):
        bits.append('<%s>' % s['tag'])
    if s.get('text'):
        bits.append('"%s"' % re.sub(r'\s+', ' ', re.sub(r'<[^>]+>', ' ', s['text'])).strip()[:60])
    if s.get('productIds'):
        bits.append('ids:' + s['productIds'])
    if s.get('items') and isinstance(s['items'], list):
        bits.append('items:%d' % len(s['items']))
    if s.get('image'):
        bits.append('img:%s#%s' % (s['image']['filename'], s['image']['id']))
    if s.get('link'):
        bits.append('-> ' + s['link']['url'])
    print('  ' * d + ' '.join(bits))
    for c in n['children']:
        show(c, d + 1)


if __name__ == '__main__':
    os.makedirs('out', exist_ok=True)
    for slug in sys.argv[1:]:
        html = open('live2/%s.html' % slug, encoding='utf-8').read()
        pid = re.search(r'data-elementor-type="wp-page" data-elementor-id="(\d+)"', html).group(1)
        css_path = 'live2css/post-%s.css' % pid
        css = open(css_path, encoding='utf-8').read() if os.path.exists(css_path) else ''
        rng.seed('pfh-' + slug)
        page = Page(slug, html, css)
        tree = page.convert()
        print('=== %s (%d)' % (slug, page.id))
        for n in tree:
            show(n, 1)
        for r in page.report:
            print('  REPORT', r)
        json.dump({'id': page.id, 'slug': slug, 'elements': flatten(tree), 'report': page.report}, open('out/%d.json' % page.id, 'w'), ensure_ascii=False, separators=(',', ':'))
