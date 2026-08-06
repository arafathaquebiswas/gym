# POWERSURGE GYM & NUTRITION — Technical SEO Audit

**Site:** https://powersurgebd.com/
**Audited:** 6 August 2026
**Method:** live crawl of 27 public URLs, raw HTTP header inspection, Chrome DevTools Protocol lab run (mobile: 390×844, 4× CPU throttle, ~1.6 Mbps), source review of the PHP MVC codebase, competitor SERP check.

**Scores are based on measured evidence.** Where a number could not be verified with the tools available (search volumes, backlink counts, field Core Web Vitals), it is labelled as such rather than invented — see §8 and §9.

---

## 0. Executive summary

| Area | Grade | One-line verdict |
|---|---|---|
| Indexability | **F** | No `robots.txt`, no `sitemap.xml`, no canonicals. Google has almost nothing to work with. |
| On-page | **F** | 27 pages share **one** meta description. Titles carry no keywords and no location. |
| Local SEO | **F** | The gym's address appears **nowhere** on the website. Facebook and YouTube links are missing. |
| Structured data | **F** | Zero JSON-LD on the entire site. |
| Social SEO | **F** | Zero Open Graph, zero Twitter Card tags. Links shared to Facebook render bare. |
| Analytics | **F** | No GA4, no GTM, no tracking of any kind. |
| Performance | **C** | Good TTFB (254 ms) and CLS (0), but FCP 4.2 s on mobile and a 767 KB logo. |
| Security/HTTPS | **A−** | HTTPS enforced, HTTP→HTTPS 301, good security headers, HTTP/2 + Brotli. |
| Content depth | **D** | Most pages are 20–300 words. Five QA test products are live and indexable. |

**The five things that matter most, in order:**

1. **Publish `robots.txt` + `sitemap.xml`** — both currently return 404. (§1.1, §1.2)
2. **Put the address on the site** and add `LocalBusiness`/`Gym` JSON-LD — you cannot rank for "gym in Mirpur" while your address is invisible to crawlers. (§3, §4)
3. **Unique title + meta description per page** — one shared description across 27 pages is a self-inflicted duplicate-content problem. (§2.1)
4. **Delete the 5 live QA test products** — `qa-test-shaker-*`, `qa-test-whey-protein` are indexable right now. (§6.1)
5. **Add canonical tags** — `www` and non-`www` both return 200 with no canonical; every filter/pagination URL is a duplicate. (§1.3)

---

## 0.1 Data verification (requested first step)

You asked me to verify your NAP and social links against the live site before recommending anything. Results:

| Item | You provided | On the live site | Verdict |
|---|---|---|---|
| Phone | 01904-485009 | `01904-485009` + `tel:01904485009` on home, contact, footer | ✅ Correct and consistent |
| Address | HOUSE-377/3, Barek Mollah Mor, Mirpur 60 Feet Road, Kamal Sarani, Dhaka-1216 | **Absent from every page** | ❌ **Missing site-wide** |
| Facebook | facebook.com/profile.php?id=100063639069967 | **Not linked anywhere** | ❌ **Missing** (URL itself returns 200 ✓) |
| YouTube | youtube.com/channel/UCvwWX5IxgevEY4QL0pr2oPg | **Not linked anywhere** | ❌ **Missing** (URL returns 200 ✓) |
| Instagram | (not provided) | `instagram.com/powersurge_gym_01` in footer | ✅ Present, returns 200 |
| Email | (not provided) | Absent | ⚠️ `gym_email` setting is empty |
| Opening hours | Sat–Thu 7AM–11PM, Fri 5PM–10PM | In footer as plain text only | ⚠️ Not machine-readable (no schema) |
| Map | — | No embed, `google_map_embed` empty | ❌ Missing |

**Root cause:** the settings rows exist but are empty in the production database:

```
gym_address       = (EMPTY)
gym_email         = (EMPTY)
facebook_url      = (EMPTY)
youtube_url       = (EMPTY)
google_map_embed  = (EMPTY)
```

The templates already render all four correctly when populated — [views/partials/footer.php:45-48](../views/partials/footer.php#L45-L48) wraps each in `!empty()`. **This is a data fix, not a code fix:** Admin → Settings, or one SQL update (§3.1).

### ⚠️ NAP inconsistency found off-site

Your business is listed on moumachi.com.bd as **"POWERSURGE GYM & NUTRITION, 377/3 Barek Mollah Mor, Mirpur-2"** — that says **Mirpur-2**, while you gave me **Mirpur 60 Feet Road, Kamal Sarani, Dhaka-1216**. Google treats conflicting addresses as a trust signal problem for local ranking. Decide on **one** canonical format, use it byte-identical everywhere (site, Google Business Profile, Facebook, every directory), and correct the listings that disagree. Recommended canonical string:

```
House 377/3, Barek Mollah Mor, Mirpur 60 Feet Road, Kamal Sarani, Dhaka 1216, Bangladesh
```

---

## 1. Technical SEO

### 1.1 robots.txt — 🔴 CRITICAL — MISSING

```
GET https://powersurgebd.com/robots.txt  →  404 (serves the HTML 404 page)
```

**Why it matters:** without it you cannot declare your sitemap, and you cannot stop crawlers wasting budget on `/cart`, `/checkout`, `/login`, `/admin`, and filtered store URLs.

**Create `/robots.txt`** in the project root (it is a real file, so the `.htaccess` front-controller rule leaves it alone):

```
User-agent: *
Allow: /

# Transactional and private areas — no ranking value, burns crawl budget
Disallow: /cart
Disallow: /checkout
Disallow: /track-order
Disallow: /login
Disallow: /logout
Disallow: /register
Disallow: /admin
Disallow: /member
Disallow: /delivery
Disallow: /pos
Disallow: /api

# Filter/sort permutations — canonical points to the clean URL
Disallow: /*?*sort=
Disallow: /*?*min_price=
Disallow: /*?*max_price=
Disallow: /*?*availability=
Disallow: /*?*on_sale=
Disallow: /*?*best_seller=
Disallow: /*?*min_rating=
Disallow: /*?*q=

# Let bots fetch CSS/JS so mobile rendering is judged correctly
Allow: /assets/

Sitemap: https://powersurgebd.com/sitemap.xml
```

**Estimated time:** 5 minutes. **Impact:** high — prerequisite for everything else.

### 1.2 sitemap.xml — 🔴 CRITICAL — MISSING

```
GET https://powersurgebd.com/sitemap.xml  →  404
```

Do **not** hand-write it — your products, blog posts and trainers change. Generate it from the database. Add to [index.php](../index.php) with the other public routes:

```php
$router->get('/sitemap.xml', [SitemapController::class, 'index']);
```

New file `controllers/SitemapController.php`:

```php
<?php

final class SitemapController extends Controller
{
    /** Static pages with their relative importance; dynamic URLs are appended from the DB. */
    private const STATIC_PAGES = [
        '/' => ['1.0', 'weekly'],
        '/about' => ['0.8', 'monthly'],
        '/membership' => ['0.9', 'weekly'],
        '/personal-training' => ['0.9', 'monthly'],
        '/store' => ['0.9', 'daily'],
        '/bundles' => ['0.7', 'weekly'],
        '/gallery' => ['0.6', 'monthly'],
        '/blog' => ['0.7', 'weekly'],
        '/faq' => ['0.6', 'monthly'],
        '/contact' => ['0.8', 'monthly'],
    ];

    public function index(): void
    {
        $urls = [];
        foreach (self::STATIC_PAGES as $path => [$priority, $freq]) {
            $urls[] = ['loc' => url($path), 'priority' => $priority, 'changefreq' => $freq];
        }

        $db = Database::connection();

        foreach ($db->query("SELECT slug, updated_at FROM products WHERE status = 'published'") as $row) {
            $urls[] = ['loc' => url('/store/' . $row['slug']), 'priority' => '0.7', 'changefreq' => 'weekly', 'lastmod' => $row['updated_at']];
        }
        foreach ($db->query("SELECT slug, updated_at FROM blog_posts WHERE status = 'published'") as $row) {
            $urls[] = ['loc' => url('/blog/' . $row['slug']), 'priority' => '0.6', 'changefreq' => 'monthly', 'lastmod' => $row['updated_at']];
        }
        foreach ($db->query('SELECT slug FROM trainers WHERE is_active = 1') as $row) {
            $urls[] = ['loc' => url('/trainers/' . $row['slug']), 'priority' => '0.6', 'changefreq' => 'monthly'];
        }
        // Category listings are legitimate landing pages — see §6.3
        foreach ($db->query('SELECT slug FROM product_categories WHERE is_active = 1') as $row) {
            $urls[] = ['loc' => url('/store?category=' . $row['slug']), 'priority' => '0.6', 'changefreq' => 'weekly'];
        }

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            echo "  <url>\n    <loc>" . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";
            if (!empty($u['lastmod'])) {
                echo '    <lastmod>' . date('Y-m-d', strtotime($u['lastmod'])) . "</lastmod>\n";
            }
            echo "    <changefreq>{$u['changefreq']}</changefreq>\n    <priority>{$u['priority']}</priority>\n  </url>\n";
        }
        echo '</urlset>';
    }
}
```

⚠️ Verify the column names against your schema before deploying (`blog_posts.status`, `trainers.is_active`, `product_categories.is_active`) — adjust if they differ.

**Time:** 30 minutes. **Impact:** high.

### 1.3 Canonical URLs — 🔴 CRITICAL — 0 of 27 pages have one

Measured: `canonical present: 0 / 27`.

Three duplicate-content sources are live right now:

| Duplicate | Evidence |
|---|---|
| `www` vs non-`www` | Both `https://powersurgebd.com/` and `https://www.powersurgebd.com/` return **200** with identical HTML |
| Store filters | `/store?category=accessories` returns the same title/H1 as `/store` |
| Pagination | `/store?page=2&...` is fully indexable with no `rel` hints |

**Fix — [views/layouts/main.php](../views/layouts/main.php), inside `<head>`:**

```php
<link rel="canonical" href="<?= e($canonical ?? url(strtok($_SERVER['REQUEST_URI'], '?'))) ?>">
```

That defaults every page to its own path with the query string stripped — which is exactly right for the store filters — while letting a controller override it by passing `'canonical' => url('/store?category=protein')` for pages that *should* be indexed with a parameter.

**Also pick one hostname.** Add to the top of [.htaccess](../.htaccess), right after `RewriteEngine On`:

```apache
# Canonical host — www duplicates every URL otherwise
RewriteCond %{HTTP_HOST} ^www\.powersurgebd\.com$ [NC]
RewriteRule ^(.*)$ https://powersurgebd.com/$1 [R=301,L]
```

**Time:** 20 minutes. **Impact:** high.

### 1.4 Status codes, redirects, broken links — ✅ MOSTLY CLEAN

| Check | Result |
|---|---|
| `http://` → `https://` | **301** ✓ correct |
| Unknown URL (`/this-page-does-not-exist-123`) | **404** ✓ real status, not a soft 404 |
| Redirect chains | None found — `/packages` and `/pricing` → `/membership` are single-hop |
| Broken internal links | **0** across 27 crawled pages |
| 5xx errors | **0** |
| HTTP version | HTTP/2, with `alt-svc: h3` (HTTP/3 available) ✓ |

The only redirect issue is the missing `www` → non-`www` rule in §1.3.

### 1.5 Caching — 🟠 HIGH — static assets are served uncacheable

```
GET /assets/css/style.css
  cache-control: private, max-age=0, no-store, no-cache, must-revalidate
  expires: Thu, 01 Jan 1970 00:00:01 GMT
```

Your CSS and JS are re-downloaded on **every single page view**. (Images are fine: `cache-control: public, max-age=604800`.) Since assets are now fingerprinted with `?v=<filemtime>` ([core/helpers.php:16](../core/helpers.php#L16)), they can safely be cached for a year.

**Add to [.htaccess](../.htaccess):**

```apache
# Static assets are fingerprinted with ?v=<filemtime>, so they can be cached hard.
<IfModule mod_headers.c>
    <FilesMatch "\.(css|js|jpg|jpeg|png|gif|webp|avif|svg|woff2?|ttf|ico)$">
        Header set Cache-Control "public, max-age=31536000, immutable"
        Header unset Expires
        Header unset Pragma
    </FilesMatch>
</IfModule>

<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 year"
    ExpiresByType application/javascript "access plus 1 year"
    ExpiresByType image/png "access plus 1 year"
    ExpiresByType image/jpeg "access plus 1 year"
    ExpiresByType image/webp "access plus 1 year"
    ExpiresByType font/woff2 "access plus 1 year"
</IfModule>
```

**Time:** 10 minutes. **Impact:** medium-high (repeat-visit speed).

### 1.6 Compression — ✅ PASS

`content-encoding: br` (Brotli) on HTML **and** CSS. Nothing to do.

### 1.7 Core Web Vitals (lab) — 🟠 HIGH

Measured over CDP, mobile emulation, 4× CPU throttle, cache disabled:

| Metric | Measured | Target | Status |
|---|---|---|---|
| TTFB | **254 ms** | < 800 ms | ✅ good |
| FCP | **4,240 ms** | < 1,800 ms | 🔴 poor |
| DOMContentLoaded | 4,287 ms | — | 🔴 |
| Load | 5,389 ms | — | 🔴 |
| CLS | **0** | < 0.1 | ✅ excellent |
| Total transfer | 391 KB | < 500 KB | 🟡 acceptable |

LCP did not emit an entry in the headless run; given FCP of 4.2 s the real LCP is almost certainly worse. **Field data (INP included) can only come from CrUX** — run PageSpeed Insights on `https://powersurgebd.com/` once GSC is connected (§10); the keyless API quota was exhausted during this audit.

The server is fast (254 ms). Everything after that is front-end weight. Causes, in order of damage:

**a) 🔴 767 KB logo, displayed at 47×44 px**
`assets/images/logo/logo.png` is **992×930 px, 767 KB** and appears in the navbar of every page at 47×44 px. It is also the largest single asset on the site.

```bash
# Resize to 2× the largest display size and convert to WebP
sips -Z 400 assets/images/logo/logo.png --out assets/images/logo/logo@2x.png
cwebp -q 82 assets/images/logo/logo@2x.png -o assets/images/logo/logo.webp
```
Expected: 767 KB → ~20 KB (**-97%**).

**b) 🟠 jsPDF (115 KB) loads on the homepage for a button most visitors never click**
[views/home.php:449](../views/home.php#L449) loads `jspdf.umd.min.js` eagerly — it is only used by the BMI calculator's PDF download. Remove it from `$extraScripts` and load it on demand:

```js
// assets/js/bmi-calculator.js — load the 115 KB PDF library only when a PDF is actually requested
async function ensureJsPdf() {
  if (window.jspdf) return;
  await new Promise((resolve, reject) => {
    const s = document.createElement('script');
    s.src = 'https://cdn.jsdelivr.net/npm/jspdf@2.5.2/dist/jspdf.umd.min.js';
    s.onload = resolve;
    s.onerror = reject;
    document.head.appendChild(s);
  });
}
```
Expected: −115 KB and one fewer third-party connection on every homepage load.

**c) 🟠 8.5 MB of images in `assets/images/`, none in WebP**
Seven trainer photos are 285–581 KB PNGs. PNG is the wrong format for photographs.

```bash
for f in assets/images/trainer/*.png assets/images/gyminterior/*.jpg assets/images/pic/*.jpg; do
  cwebp -q 82 "$f" -o "${f%.*}.webp"
done
```
Expected: 8.5 MB → ~1.5 MB.

**d) 🟡 Three render-blocking stylesheets, two of them third-party**
`bootstrap.min.css` (33 KB) and `bootstrap-icons.min.css` (13 KB) come from jsDelivr — an extra DNS + TLS handshake before your page can paint. At minimum add resource hints to the top of `<head>` in [views/layouts/main.php](../views/layouts/main.php):

```html
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>
<link rel="dns-prefetch" href="https://cdn.jsdelivr.net">
```
Better (Phase 3): self-host both and subset Bootstrap Icons to the ~40 glyphs you use.

**e) ✅ Lazy loading already works** — 66 `loading="lazy"` attributes across the crawl. Keep hero/logo images eager.

### 1.8 Mobile usability — ✅ PASS
Viewport meta present on all 27 pages, `lang="en"` set, CLS 0, no horizontal overflow found at 390 px.

### 1.9 JavaScript SEO — ✅ PASS
Content is server-rendered PHP. Titles, H1s and body copy are all present in raw HTML — verified by curl with JS disabled. No hydration risk.

### 1.10 Mixed content & security — ✅ PASS
`content-security-policy: upgrade-insecure-requests`, `X-Frame-Options: SAMEORIGIN`, `X-Content-Type-Options: nosniff`, `Referrer-Policy: strict-origin-when-cross-origin`, secure/HttpOnly/SameSite session cookie. No HTTP subresources found.

**One addition worth making** — HSTS, in [.htaccess](../.htaccess):
```apache
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
```

### 1.11 Orphan pages & crawl budget — 🟠 HIGH

**`/bundles` is nearly orphaned** — it is linked only from the store hero button, from no navigation or footer.

**🔴 Regression introduced by the recent store-filter rebuild:** the sidebar's category and brand entries are now radio inputs inside a GET form, not links. Measured on the current build:

```
crawlable category/brand links on /store: 0   (previously: one <a href> per category and per brand)
```

Search engines do not submit forms. **Every category and brand landing page is now undiscoverable.** Fix in §6.3 — this one is worth doing before anything else in the store.

---

## 2. On-Page SEO

### 2.1 Meta descriptions — 🔴 CRITICAL — 1 description for 27 pages

Every page on the site serves the same 24-character string:

```html
<meta name="description" content="Train Hard. Surge Ahead.">
```

It comes from [views/layouts/main.php:16](../views/layouts/main.php#L16), which falls back to `$settings['gym_tagline']` because no page ever sets its own.

**Fix — replace line 16 of [views/layouts/main.php](../views/layouts/main.php):**

```php
<meta name="description" content="<?= e($metaDescription ?? $settings['gym_tagline'] ?? 'Modern fitness center in Mirpur, Dhaka') ?>">
```

Then set `$metaDescription` at the top of each view (same place `$pageTitle` is set). Copy written for Bangladeshi search intent, all within the 155-character limit:

| Page | Meta description |
|---|---|
| `/` | `POWERSURGE GYM & NUTRITION — Mirpur's first duplex gym on 60 Feet Road. 6,000 sq ft, fully AC, separate ladies section. Membership from ৳1,000. Call 01904-485009.` |
| `/about` | `Inside POWERSURGE GYM & NUTRITION Mirpur: 6,000 sq ft over two floors, certified trainers, separate cardio and strength zones, and a members-first culture.` |
| `/membership` | `Gym membership in Mirpur from ৳1,000 for 2 months. 4-month and 12-month plans, no joining fee, locker and trainer options. Join POWERSURGE GYM & NUTRITION today.` |
| `/personal-training` | `1-on-1 personal training in Mirpur, Dhaka. Certified coaches for weight loss, muscle gain and strength. Book a session at POWERSURGE GYM & NUTRITION: 01904-485009.` |
| `/store` | `Buy authentic whey protein, creatine, pre-workout and gym gear in Dhaka. Genuine brands, in-stock delivery across Bangladesh from POWERSURGE GYM & NUTRITION.` |
| `/gallery` | `See inside POWERSURGE GYM & NUTRITION Mirpur — equipment, training floors, ladies section and member results. Real photos of Dhaka's first duplex gym.` |
| `/faq` | `Opening hours, membership freezing, student discounts and trainer questions — everything you need to know before joining POWERSURGE GYM & NUTRITION Mirpur.` |
| `/contact` | `Visit POWERSURGE GYM & NUTRITION at House 377/3, Barek Mollah Mor, Mirpur 60 Feet Road, Dhaka 1216. Open Sat–Thu 7AM–11PM. Call 01904-485009.` |
| `/blog` | `Workout guides, nutrition advice and training tips from the coaches at POWERSURGE GYM & NUTRITION Mirpur, written for lifters in Bangladesh.` |

**Time:** 45 minutes. **Impact:** high (CTR + duplicate-content removal).

### 2.2 Title tags — 🟠 HIGH — no keywords, no location

Current pattern is `{Page} | POWERSURGE GYM & NUTRITION`, producing titles like `Home | POWERSURGE GYM & NUTRITION` (21 chars). You are leaving 40+ characters of ranking real estate unused on every page, and "Home" is a wasted word.

Your competitor **getfitgymbd.com** titles their homepage `Get Fit Gym – Best Gym in Mirpur 60 Feet Road` — same road, targeting the exact query.

**Rewrite** (each 50–60 chars, primary keyword first):

| Page | Title |
|---|---|
| `/` | `Best Gym in Mirpur, Dhaka \| POWERSURGE GYM & NUTRITION 60 Feet Road` |
| `/about` | `About POWERSURGE GYM & NUTRITION — Mirpur's First Duplex Fitness Center` |
| `/membership` | `Gym Membership Price in Mirpur from ৳1,000 \| POWERSURGE GYM & NUTRITION` |
| `/personal-training` | `Personal Trainer in Mirpur, Dhaka \| 1-on-1 Coaching` |
| `/store` | `Buy Whey Protein & Supplements Online in Dhaka \| POWERSURGE GYM & NUTRITION` |
| `/gallery` | `POWERSURGE GYM & NUTRITION Photos — Inside Our Mirpur Fitness Center` |
| `/faq` | `Gym Membership FAQ — Hours, Prices & Rules \| POWERSURGE GYM & NUTRITION` |
| `/contact` | `Contact POWERSURGE GYM & NUTRITION Mirpur \| 01904-485009 \| 60 Feet Road` |
| `/blog` | `Fitness & Nutrition Blog for Bangladesh \| POWERSURGE GYM & NUTRITION` |
| `/bundles` | `Supplement Bundle Deals & Combo Offers in Dhaka \| POWERSURGE GYM & NUTRITION` |

Implementation: `$pageTitle` already flows into the layout. For pages that should **not** get the ` | POWERSURGE GYM & NUTRITION` suffix appended twice, change [views/layouts/main.php:15](../views/layouts/main.php#L15) to:

```php
<title><?= e($pageTitle ?? $gymName) ?><?= empty($fullTitle) ? ' | ' . e($gymName) : '' ?></title>
```
…and set `$fullTitle = true;` in views where `$pageTitle` already contains the brand.

### 2.3 Headings — 🟠 MEDIUM

| Issue | Pages | Fix |
|---|---|---|
| **No H1 at all** | `/login`, `/register` | Add `<h1>Create Your PowerSurge Membership</h1>` to `/register`; `/login` is `noindex` anyway (§1.1) |
| **H1 lacks any keyword** | `/` — currently `Train Hard. Surge Ahead.` | `<h1>Best Gym in Mirpur, Dhaka</h1>` with the slogan as the H2 below it |
| **No H2s at all** | `/blog`, `/gallery`, `/store`, `/bundles`, `/cart`, product pages | Every product page should carry `<h2>Description</h2>`, `<h2>Nutrition Facts</h2>`, `<h2>Customer Reviews</h2>` |
| Good hierarchy | `/` (1×H1, 14×H2), `/membership` (1×H1, 9×H2), `/faq` (1×H1, 5×H2) | ✅ no change |

### 2.4 Image ALT tags — ✅ PASS (with a caveat)

**0 images missing `alt`** across 27 pages — genuinely good, and better than most sites this size.

The caveat is *quality*: the alt text is the raw product/trainer name. Make it descriptive **and** local:

| Current | Better |
|---|---|
| `alt="PowerSurge Gym"` (logo) | `alt="PowerSurge Gym Mirpur logo"` |
| `alt="Rakibul Hasan"` | `alt="Rakibul Hasan, certified strength coach at PowerSurge Gym Mirpur"` |
| `alt="Gym T-Shirt"` | `alt="PowerSurge branded cotton gym t-shirt, black"` |
| Gallery photos | `alt="Cardio floor at PowerSurge Gym, Mirpur 60 Feet Road"` |

**Filenames also matter and yours are weak:** `1stpic.jpg`, `middlepic.jpg`, `anotherpic2.jpg`, `8thbd.jpg` carry zero keyword signal. Rename to `powersurge-gym-mirpur-cardio-floor.jpg`, `powersurge-gym-strength-section.jpg`, etc. (Do this when you convert to WebP — one pass, and update the DB `image` columns in the same transaction.)

### 2.5 Thin content — 🟠 HIGH

Word counts from the crawl (body text, nav/footer excluded):

| Page | Words | Verdict |
|---|---|---|
| `/` | 654 | 🟡 acceptable, aim for 900+ |
| `/membership` | 280 | 🔴 thin for a money page |
| `/about` | 256 | 🔴 thin |
| `/personal-training` | 221 | 🔴 thin for a money page |
| `/store` | 297 | 🟡 add category intro copy |
| `/faq` | 129 | 🔴 only 5 questions |
| `/trainers/*` | 93–102 | 🔴 too thin to rank |
| `/blog` | 85 | 🔴 only 3 posts exist |
| product pages | 56–68 | 🔴 essentially empty |
| `/bundles`, `/cart`, `/track-order` | 12–21 | ⚠️ transactional, fine |

Content plan in §7.

### 2.6 Breadcrumbs — 🔴 MISSING everywhere
No breadcrumb markup or UI on any page, including product and blog detail pages where Google displays them in results. Implementation with `BreadcrumbList` JSON-LD in §4.

### 2.7 Internal linking — 🟠 MEDIUM
- Navigation is consistent and crawlable ✓
- **No contextual body links** — the homepage never links to `/personal-training` or `/membership` from within its copy, only from nav/buttons
- **No breadcrumb links** (§2.6)
- **Category/brand links destroyed by the filter rebuild** (§1.11, fix in §6.3)
- Blog posts do not link to related products, and product pages do not link to blog posts

Suggested contextual links to add:

| From | Anchor text | To |
|---|---|---|
| `/` hero copy | "personal training in Mirpur" | `/personal-training` |
| `/` plans section | "see all membership prices" | `/membership` |
| `/about` | "meet our certified trainers" | `/personal-training` |
| `/blog/how-much-protein-do-you-need` | "whey protein we stock" | `/store?category=protein` |
| `/personal-training` | "supplements to support your plan" | `/store` |
| Every product page | "training programs at our Mirpur gym" | `/personal-training` |

---

*Part 1 of 4 — continues with §3 Local SEO, §4 Structured Data (full JSON-LD), §5 Social SEO, §6 E-commerce SEO, §7 Content, §8 Keywords, §9 Competitors, §10 GSC, §11 GA4, §12 AI SEO, §13 Deliverables, §14 Roadmap.*
