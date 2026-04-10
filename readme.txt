=== Kukie - Cookie Banner and Consent Management (GDPR, CCPA, DSVGO, CNIL, PIPEDA) ===
Contributors: kukieio, filesubmit
Tags: cookie consent, gdpr, ccpa, privacy, cookie banner
Requires at least: 6.0
Tested up to: 6.9
Requires PHP: 8.1
Stable tag: 1.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Free cookie consent plugin for WordPress. GDPR, CCPA & ePrivacy compliance with Google Consent Mode v2, cookie scanning and 70+ languages.

== Description ==

Kukie.io is a cookie consent management platform that helps websites comply with GDPR (DSGVO), CCPA/CPRA, ePrivacy, UK GDPR, LGPD (Brazil), PIPEDA (Canada), POPIA (South Africa), CNIL (France), TTDSG (Germany) and other global privacy regulations.

The plugin connects your WordPress site to your Kukie.io account, displaying a fully customisable cookie consent banner that blocks non-essential scripts until consent is given.

= Features =

All features below are included in the **free plan** unless marked otherwise.

**Consent Management**

* Cookie Consent Banner: Show banner with Accept/Reject options for GDPR and CCPA cookie consent.
* Automatic Cookie Blocking: Block non-essential cookies until users give explicit consent.
* Preference Centre: Let users manage cookie preferences by category (necessary, analytics, marketing, functional).
* Per-Service Consent: Toggle consent for individual services (Google Analytics, Meta Pixel, Hotjar, etc.) directly in the banner UI.
* Revisit Consent Button: Floating button for updating consent choices anytime. 4 positions, 3 styles, 4 icons.
* CCPA/CPRA Opt-Out: "Do Not Sell or Share My Personal Information" support for California residents.
* Consent Logging: Record user consent with full audit trail. CSV export for compliance audits.
* Consent Verification API: Verify consent status programmatically for compliance proof.
* CNIL-Compliant Button Styling: Accept and Reject buttons rendered with equal visual prominence in opt-in regions, eliminating dark patterns.
* Google Consent Mode v2: Automatic consent signalling to Google Analytics, Google Ads, and Google Tag Manager. No manual tag configuration needed.
* Google Tag Manager: Container ID support for GTM integration.
* Microsoft UET Consent Mode: Consent signals for Microsoft advertising tags.
* Global Privacy Control (GPC): Automatic detection and respect of browser GPC signals.
* Do Not Track (DNT): Honour DNT browser header settings.
* GDPR-Compliant Data Storage: All data stored in EU-based servers.

**Cookie Scanner**

* Automatic Scanning: Automated browser scanner detects every cookie on your site.
* Auto-Categorisation: 1,500+ known cookies database across 13 pre-configured services.
* Full Detection: Detects cookies, localStorage, and sessionStorage.
* Scheduled Scans: Weekly, bi-weekly, or monthly automated scans (Pro plan and above).
* New Cookie Alerts: Get notified when new cookies are detected on your site.
* Scan History: Track changes between scans.

**Banner Customisation**

* Layout Options: 5 layouts - popup, bottom bar, top bar, floating, and side panel.
* Full Colour Theming: Background, text, and button colours to match your brand.
* Custom CSS: Advanced design customisation with CSS injection (Pro plan and above).
* Custom Banner Logo: Add your brand logo to the consent banner (Pro plan and above).
* Remove Branding: Remove "Powered by Kukie" for a white-label experience (Agency plan and above).

**Multilingual and Accessibility**

* Auto-Translation: Banner translates to 70+ languages based on visitor browser settings.
* RTL Support: Full right-to-left language support (Arabic, Hebrew, etc.).
* Accessibility: Banner UI follows WCAG 2.1 AA guidelines.

**Geo-Detection and Region Rules**

* IP-Based Detection: Automatic visitor region detection via Cloudflare and MaxMind GeoLite2.
* Per-Region Consent Models: Configure opt-in, opt-out, notice-only, or hidden mode per country.
* Sub-Region Rules: Granular rules for TTDSG (Germany), CNIL (France), per-state CCPA, Quebec Law 25.
* Cookie Wall: Optional cookie wall for specific regions.

**Legal Policy Tools**

* Cookie Policy Generator: Step-by-step wizard with smart defaults, auto-filled from scan data.
* Privacy Policy Generator: GDPR, CCPA, and UK GDPR compliant.
* Terms of Service Generator: Complete terms generation.
* Multiple Formats: Publish as public URLs, embed via iFrame, or copy as raw HTML.

**Analytics Dashboard**

* Consent Rate Trends: Daily, weekly, and monthly consent rate tracking.
* Category Breakdown: Per-category acceptance rates (analytics, marketing, functional).
* Geographic Insights: Country-level consent data breakdown.
* CSV Export: Export consent logs and analytics for compliance audits.
* Consent Reports: Detailed compliance reports (Agency plan and above).

**Script Centre**

* Third-Party Script Management: Manage scripts per service with consent-aware loading.
* iFrame Blocking: Block YouTube, Google Maps, and social embeds until consent. Styled placeholders with thumbnails.
* 13 Built-In Detectors: Auto-detect scripts from Google Analytics, Meta Pixel, Hotjar, and more.
* 4 DOM Positions: Inject scripts at head start, head end, body start, or body end.

**Security**

* Two-Factor Authentication (2FA): Secure your Kukie.io account.
* API Rate Limiting: Protection against abuse.
* IP and User-Agent Hashing: GDPR data minimisation for consent logs.
* Team Roles: Owner, admin, and editor roles for team management.

= What's Included Free vs Paid =

The free plan includes: cookie consent banner, 5 layouts, Google Consent Mode v2, GTM, Microsoft UET, 70+ languages, cookie scanner (100 pages), consent logging, geo-detection, analytics dashboard, legal document generators, iFrame blocking, Script Centre, and 12 months consent retention.

Paid plans add:

* **Pro** (from 9 EUR/mo): Scheduled scans, custom CSS, custom banner logo, 20 sites, 500 pages per scan, 3 team members, 24 months consent retention.
* **Agency** (from 19 EUR/mo): Everything in Pro plus consent reports, remove branding, 100 sites, 3,000 pages per scan, 10 team members.
* **Unlimited** (from 59 EUR/mo): Everything in Agency plus unlimited sites, pages, and team members, 36 months consent retention.

All paid plans include a 14-day free trial. [Compare all plans](https://kukie.io/pricing).

= Useful Links =

* [Kukie.io Website](https://kukie.io)
* [WordPress Plugin Page](https://kukie.io/wordpress)
* [Features Overview](https://kukie.io/features)
* [Help Centre](https://kukie.io/docs)
* [WordPress Plugin Documentation](https://kukie.io/docs/wordpress-plugin)
* [Blog](https://kukie.io/blog)
* [Facebook](https://www.facebook.com/Kukie.io)
* [X (Twitter)](https://x.com/kukie_io)
* [LinkedIn](https://www.linkedin.com/company/kukie-io/)

= External Service =

This plugin relies on [Kukie.io](https://kukie.io), a third-party cookie consent management service (SaaS), to provide its core functionality.

By installing and configuring this plugin with your site key, you consent to connecting to the Kukie.io service.

**What is loaded:**

* A cookie consent banner script from `https://cdn.kukie.io` (served over HTTPS)
* The script contains your banner configuration (colours, text, cookie categories)

**What is transmitted:**

* Your site key (to identify your banner configuration)
* No personal visitor data is collected by the plugin itself

**When:**

* The banner script loads on every public page of your website
* Admin pages connect to `https://app.kukie.io` for banner configuration

**Service links:**

* [Kukie.io Website](https://kukie.io)
* [Terms of Service](https://kukie.io/terms-of-service)
* [Privacy Policy](https://kukie.io/privacy-policy)

NOTE: INSTALLING THIS PLUGIN ALONE DOES NOT MAKE YOUR SITE FULLY COMPLIANT WITH GDPR, CCPA OR OTHER PRIVACY REGULATIONS. COMPLIANCE DEPENDS ON CORRECT CONFIGURATION AND MAY REQUIRE ADDITIONAL LEGAL MEASURES SPECIFIC TO YOUR ORGANISATION.

== Installation ==

1. In your WordPress dashboard, go to **Plugins > Add New**
2. Search for **"Kukie"**
3. Click **Install Now** then **Activate**
4. Go to **Kukie** in the admin sidebar
5. Enter your site key from the [Kukie.io dashboard](https://app.kukie.io)
6. Your cookie consent banner is now active

Alternatively, download the plugin from [WordPress.org](https://wordpress.org/plugins/kukie-cookie-consent/) and upload the ZIP file via **Plugins > Add New > Upload Plugin**.

For detailed setup instructions, visit the [WordPress plugin documentation](https://kukie.io/docs/wordpress-plugin/install-wordpress-plugin).

== Frequently Asked Questions ==

= What is GDPR cookie consent? =

GDPR cookie consent is the legal requirement to obtain consent before setting cookies on a user's browser. The General Data Protection Regulation requires organisations that process data of EU residents to get prior consent before setting any cookies (except strictly necessary cookies).

= What is CCPA compliance? =

The California Consumer Privacy Act (CCPA) and its amendment CPRA give California residents the right to opt out of the sale or sharing of their personal information. Websites must provide a clear "Do Not Sell or Share My Personal Information" option.

= Is the plugin free? =

Yes. The plugin is free and always will be. It connects to your Kukie.io account where you can use the free plan (unlimited pageviews, up to 5 sites) or upgrade for advanced features like scheduled scans, custom CSS, and consent reports.

= Do I need a Kukie.io account? =

Yes. The plugin connects to the Kukie.io platform where your banner configuration, cookie scans, and consent logs are managed. [Sign up](https://app.kukie.io/register) takes 30 seconds - no credit card required.

= Where do I find my site key? =

Log in to [app.kukie.io](https://app.kukie.io), select your site, go to Settings, and copy the site key.

= Does it support Google Consent Mode v2? =

Yes. Google Consent Mode v2 is built in and activates automatically. It signals consent state to Google Analytics, Google Ads, and Google Tag Manager without any manual tag configuration.

= Does the plugin block cookies before consent? =

Yes. The banner script manages cookie blocking automatically. Non-essential scripts and cookies are blocked until the visitor gives explicit consent for each category.

= Is the plugin compatible with caching plugins? =

Yes. The banner script loads from our CDN (cdn.kukie.io) with per-site configuration embedded, so it works with all WordPress caching plugins including WP Super Cache, W3 Total Cache, WP Rocket, and LiteSpeed Cache.

= What privacy regulations does it support? =

GDPR (EU and UK), CCPA/CPRA (California), ePrivacy Directive, LGPD (Brazil), PIPEDA (Canada), POPIA (South Africa), CNIL (France), TTDSG (Germany), and more. Region-specific consent models are applied automatically via geo-detection.

= Does Kukie support multilingual websites? =

Yes. The banner auto-translates to 70+ languages based on the visitor's browser settings. Full RTL (right-to-left) support is included for Arabic, Hebrew, and other RTL languages.

= Will it slow down my site? =

No. The banner script is under 5KB gzipped and loads asynchronously from our global CDN. It has zero impact on your Core Web Vitals scores.

= Can I customise the banner design? =

Yes. Choose from 5 layouts, set your brand colours, customise all text, and add custom CSS. All customisation is done through the [Kukie.io dashboard](https://app.kukie.io) with real-time preview.

= Can I export consent logs for GDPR compliance? =

Yes. All consent events are logged with timestamps, consent choices, and anonymised visitor identifiers. Export to CSV from the Kukie.io dashboard for compliance audits.

= Do I still need a privacy policy if I use Kukie? =

Yes. A cookie consent banner is only one part of privacy compliance. You also need a Privacy Policy and Cookie Policy. Kukie.io includes generators for both on all plans, including the free plan.

= Is Kukie suitable for agencies managing multiple sites? =

Yes. Kukie.io supports multi-site management with team roles (owner, admin, editor). The free plan supports up to 5 sites, and paid plans support up to 100 or unlimited.

== Screenshots ==

1. Plugin dashboard - connection status, site key, and quick access to Kukie.io settings
2. Banner design settings - choose layout, position, and preview on desktop, tablet, and mobile
3. Google Consent Mode v2 and Google Tag Manager integration settings
4. Language and auto-translation configuration with 70+ supported languages
5. Cookie consent banner live on a WordPress site - popup layout with Accept, Reject, and Preferences
6. Cookie scanning results - auto-categorised cookies by type (necessary, analytics, marketing, functional)
7. Consent analytics dashboard - acceptance rates, trends, and geographic breakdown
8. Revisit consent button settings - position, style, icon, and colour customisation

== Changelog ==

= 1.5.0 =
* Added: WP Rocket "Load JavaScript deferred" exclusion is now applied automatically via rocket_exclude_defer_js filter, no manual configuration required
* Improved: WP Rocket compatibility notice now checks runtime exclusion state instead of saved DB option, eliminating false positives when our own filters already handle exclusions
* Result: Default WP Rocket configurations now work with Kukie out of the box across all four optimization paths (Minify, Combine, Defer, Delay) with zero manual setup

= 1.4.1 =
* Fixed: Banner continues to display after API key is regenerated or deleted
* Added: Admin notice when API key is invalid with link to generate a new key
* Added: Banner injection automatically disabled when API key becomes invalid
* Added: Auto-recovery when a new valid API key is entered

= 1.4.0 =
* Added translations for 11 languages: Bulgarian, German, French, Spanish, Italian, Portuguese (Brazil), Dutch, Polish, Romanian, Turkish, Japanese
* Improved internationalization coverage for all plugin strings

= 1.3.4 =
* Fixed WP Consent API bridge: enqueue after WP Consent API script (PHP_INT_MAX - 50 priority)
* Removed redundant window.wp_consent_type inline script (WP Consent API reads consent type via wp_localize_script)

= 1.3.3 =
* Fixed WP Consent API integration not loading because kukie-cookie-consent loads before wp-consent-api alphabetically
* Deferred init to plugins_loaded hook so wp_set_consent() is available

= 1.3.2 =
* Fixed WP Consent API bridge not loading due to script registration timing (priority 10 -> 20)

= 1.3.1 =
* Fixed wp_has_consent() always returning true because window.wp_consent_type was undefined in JavaScript
* Now sets window.wp_consent_type = 'optin' before WP Consent API script loads

= 1.3.0 =
* Added WP Consent API integration - auto-syncs Kukie consent categories to WP Consent API when the plugin is installed
* Registers Kukie as the active consent management plugin
* Category mapping: necessary/functional to functional/preferences, analytics to statistics/statistics-anonymous, marketing to marketing
* No configuration needed - activates automatically when WP Consent API plugin is detected

= 1.2.2 =
* Added caching plugin exclusion filters for Autoptimize, WP Rocket, WP Fastest Cache, LiteSpeed Cache, W3 Total Cache, and SG Optimizer
* Added data-cfasync, data-pagespeed-no-defer, and data-no-optimize attributes to banner script tag
* Added noptimize comment wrapper for Autoptimize compatibility
* Fixes issue where caching plugins could truncate or corrupt the banner script

= 1.2.1 =
* Fixed "Learn more" link in WP Rocket notice pointing to non-existent page

= 1.2.0 =
* Added WP Rocket compatibility detection with admin notice for missing exclusions
* Added data-no-minify, data-no-defer, data-no-delay attributes to banner script tag
* Banner script now automatically skipped by WP Rocket and similar caching plugins

= 1.1.3 =
* Updated name

= 1.1.2 =
* Added icon colour option for revisit button (auto-contrast or custom hex)
* Renamed "Color" to "Background Color" for clarity

= 1.1.1 =
* Fixed admin notices from other plugins rendering inside the Kukie plugin card on all admin pages
* Added standard WordPress admin page markup (div.wrap + h1 + hr.wp-header-end) to all admin templates
* Replaced all en dashes and em dashes with regular hyphens in plugin files

= 1.1.0 =
* Replaced raw script output with wp_enqueue_script() for banner injection
* Updated Tested up to from 6.8 to 6.9
* Added input sanitisation for admin page detection
* Prefixed all template global variables with kukie_
* Added External Service disclosure section for wp.org submission compliance
* Improved output escaping in admin bar status indicator
* Sanitised all POST data with sanitize_text_field() and wp_unslash()
* Added phpcs:ignore for external CDN script version parameter

= 1.0.9 =
* Fixed banner not always loading when script runs before page body is ready
* Added cache-busting to embed script URL so settings changes are reflected immediately

= 1.0.8 =
* Added Revisit Button settings to Banner Design page
* Position, style, icon, text, colour and offset controls

= 1.0.7 =
* Fixed layout/position values to match SaaS app format
* Settings now sync correctly between plugin and Kukie.io dashboard

= 1.0.5 =
* Added Banner Design page with layout and position selection
* Live preview with device tabs (desktop, tablet, mobile)

= 1.0.1 =
* Updated branding colours and logo
* Fixed GCM and Settings page loading issue

= 1.0.0 =
* Initial release