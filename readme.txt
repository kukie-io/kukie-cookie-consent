=== Kukie - Cookie Banner and Consent Management (GDPR, CCPA, DSVGO, CNIL, PIPEDA) ===
Contributors: kukieio, filesubmit
Tags: cookie consent, gdpr, ccpa, wpml, polylang
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.8.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Free cookie consent plugin for WordPress. GDPR, CCPA & ePrivacy compliance with Google Consent Mode v2, cookie scanning and 70+ languages.

== Description ==

Kukie.io is a cookie consent management platform that helps websites comply with GDPR (DSGVO), CCPA/CPRA, ePrivacy, UK GDPR, LGPD (Brazil), PIPEDA (Canada), POPIA (South Africa), CNIL (France), TDDDG (Germany, formerly the TTDSG) and other global privacy regulations.

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
* Auto-Categorisation: a continuously updated database of thousands of known cookies across 13 pre-configured services.
* Full Detection: Detects cookies, localStorage, and sessionStorage.
* Scheduled Scans: Weekly or monthly automated scans (Pro plan and above).
* New Cookie Alerts: Get notified when new cookies are detected on your site.
* Scan History: Track changes between scans.

**Banner Customisation**

* Layout Options: 4 layouts - popup, bottom bar, top bar, and floating.
* Full Colour Theming: Background, text, and button colours to match your brand.
* Custom CSS: Advanced design customisation with CSS injection (Pro plan and above).
* Custom Banner Logo: Add your brand logo to the consent banner (Pro plan and above).
* Remove Branding: Remove "Powered by Kukie" for a white-label experience (Agency plan and above).

**Multilingual and Accessibility**

* Auto-Translation: Banner translates to 70+ languages based on visitor browser settings.
* RTL Support: Full right-to-left language support (Arabic, Hebrew, etc.).
* Accessibility: Banner UI follows WCAG 2.1 AA guidelines.
* Accessibility Widget: an optional floating button that opens a panel of reading, contrast and navigation aids for your visitors - bigger text, text spacing, dyslexia-friendly font, high contrast, hide images, pause animations, read aloud, one-tap profiles and more. Delivered inside the same banner script, so there is nothing extra to install; the panel is available in 70+ languages and is configured from the plugin's Accessibility widget page or the Kukie.io dashboard. Available on selected plans. It helps visitors, but does not by itself make a website compliant with any accessibility law.

**Geo-Detection and Region Rules**

* IP-Based Detection: Automatic visitor region detection via Cloudflare and MaxMind GeoLite2.
* Per-Region Consent Models: Configure opt-in, opt-out, notice-only, or hidden mode per country.
* Sub-Region Rules: Granular rules for TDDDG (Germany), CNIL (France), per-state CCPA, Quebec Law 25.
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

The free plan includes: cookie consent banner, 4 layouts, Google Consent Mode v2, GTM, Microsoft UET, 70+ languages, cookie scanner (100 pages), consent logging, geo-detection, analytics dashboard, legal document generators, iFrame blocking, Script Centre, and 12 months consent retention.

Paid plans add:

* **Pro** (from 9 EUR/mo): Scheduled scans, custom CSS, custom banner logo, 20 sites, 500 pages per scan, 3 team members, 24 months consent retention.
* **Agency** (from 19 EUR/mo): Everything in Pro plus consent reports, remove branding, 100 sites, 3,000 pages per scan, 10 team members.
* **Unlimited** (from 89 EUR/mo): Everything in Agency plus unlimited sites, pages, and team members, 36 months consent retention.

The accessibility widget is a plan feature - the plugin page shows which plan includes it. All paid plans include a 14-day free trial. [Compare all plans](https://kukie.io/pricing).

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

By installing and configuring this plugin with your API key, you consent to connecting to the Kukie.io service.

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

== Multilingual Support ==

Kukie works out of the box with WPML and Polylang. When a visitor views a translated page, the cookie consent banner automatically displays in the matching language - no additional configuration needed.

Supported language sources (in priority order):

1. Manual override from plugin settings ("Banner language" dropdown)
2. WPML current language
3. Polylang current language
4. WordPress site locale

Banner translations (titles, descriptions, buttons, cookie categories) are managed in the Kukie dashboard at https://app.kukie.io, where 70+ banner languages are available. The plugin's own admin interface ships with built-in translations for 11 languages.

== Installation ==

1. In your WordPress dashboard, go to **Plugins > Add New**
2. Search for **"Kukie"**
3. Click **Install Now** then **Activate**
4. Go to **Kukie** in the admin sidebar
5. Enter your API key from the [Kukie.io dashboard](https://app.kukie.io)
6. Your cookie consent banner is now active

The plugin adds four admin pages under **Kukie**: **Dashboard** (status, consent counts, scans), **Consent banner** (Design, Google Consent Mode v2 and Microsoft UET tabs), **Accessibility widget** and **Settings** (script position, languages, connection).

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

= Where do I find my API key? =

Log in to [app.kukie.io](https://app.kukie.io), select your site, go to Settings, and generate or copy the API key.

= Does it support Google Consent Mode v2? =

Yes. Google Consent Mode v2 is built in and activates automatically. It signals consent state to Google Analytics, Google Ads, and Google Tag Manager without any manual tag configuration.

= Does the plugin block cookies before consent? =

Yes. The banner script manages cookie blocking automatically. Non-essential scripts and cookies are blocked until the visitor gives explicit consent for each category.

= Is the plugin compatible with caching plugins? =

Yes. The banner script loads from our CDN (cdn.kukie.io) with per-site configuration embedded, so it works with all WordPress caching plugins including WP Super Cache, W3 Total Cache, WP Rocket, and LiteSpeed Cache.

= What privacy regulations does it support? =

GDPR (EU and UK), CCPA/CPRA (California), ePrivacy Directive, LGPD (Brazil), PIPEDA (Canada), POPIA (South Africa), CNIL (France), TDDDG (Germany), and more. Region-specific consent models are applied automatically via geo-detection.

= Does Kukie support multilingual websites? =

Yes. The banner auto-translates to 70+ languages based on the visitor's browser settings. Full RTL (right-to-left) support is included for Arabic, Hebrew, and other RTL languages.

= Will it slow down my site? =

No. The banner script is under 30KB gzipped and loads asynchronously from our global CDN, so it does not block page rendering or hurt your Core Web Vitals scores. The optional accessibility widget adds about 15KB gzipped, and only for sites that switch it on.

= Does the plugin include an accessibility widget? =

Yes, as a plan feature. The Accessibility widget page in the plugin lets you switch on a floating button that opens a panel of reading, contrast and navigation aids for visitors (bigger text, dyslexia-friendly font, high contrast, read aloud, one-tap profiles and more). It ships inside the same banner script - no second embed or plugin - makes no third-party requests, and its panel is available in 70+ languages. On plans without it the page shows what the widget does and which plan includes it. Please note: the widget helps visitors with reading and navigation, but no widget on its own makes a website compliant with the European Accessibility Act, WCAG, the ADA or any other accessibility law - that still depends on your content, your theme and your own testing.

= Can I customise the banner design? =

Yes. Choose from 4 layouts, set your brand colours, customise all text, and add custom CSS. All customisation is done through the [Kukie.io dashboard](https://app.kukie.io) with real-time preview.

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

= 1.8.0 =
* Added: Consent banner page tabs Behaviour (show branding, auto-block scripts, Do Not Track, Global Privacy Control, reload on consent, background overlay, disabled pages), iFrame blocking (toggle plus the blocked services list) and Language (banner language override, auto-translate, default and enabled languages - moved here from Settings), plus a Regions tab pointing to the region rules editor on Kukie.io.
* Fixed: the dashboard's Consents Today and this week/month counts now include today's live consents instead of waiting for the nightly aggregation (requires the Kukie.io service update of 2 September 2026).
* Fixed: the Settings page's Connection card follows plan and organisation changes made on Kukie.io instead of showing the values from the day you connected.
* Fixed: a save from one page can no longer reset a field owned by another (for example the language list from the Settings page).
* Added: Accessibility widget page. Switch on the Kukie accessibility widget for this site and configure its position, colour, button size, mobile visibility, modules, languages and accessibility-statement link from WordPress. Settings are read from and saved to your Kukie.io account, so the plugin and the dashboard never disagree. On plans without the widget the page explains what it does and which plan includes it.
* Changed: the admin menu is now Dashboard, Consent banner, Accessibility widget and Settings. Banner Design, Google Consent Mode v2 and Microsoft UET moved into tabs of the single Consent banner page; old bookmarks to the previous pages redirect to the matching tab.
* Changed: the dashboard shows the accessibility widget state alongside the banner, consent and verification cards.
* Improved: refreshed admin styling - WordPress-native notices and tabs, 40px form controls matching WordPress 7.1, proper labels and descriptions on every field for screen readers, consistent save buttons.
* Improved: the Dashboard's status badges and the Settings page's verification and disconnect messages are now translatable.
* Fixed: hidden admin pages no longer trigger PHP 8.1+ deprecation notices, and the WP Rocket notice no longer prints an inline script.
* Compatibility: tested with WordPress 7.1 (always-iframed post editor, jQuery UI 1.14 - the plugin uses neither).
* Changed: updated the banner script size in this listing (under 30KB gzipped).

= 1.7.3 =
* Added: two new revisit-button icon options, Lock and Sliders, matching the Kukie.io dashboard. Icons chosen there are no longer reset to Cookie when saving the Banner Design page.
* Improved: the Shield revisit-button icon was redrawn as a crisper solid shield with a check mark.
* Changed: updated the banner script size in this listing to around 26KB gzipped.

= 1.7.2 =
* Fixed: disconnecting your site while another tab was still talking to Kukie.io (saving settings, loading a settings page or refreshing the dashboard) no longer silently undoes the disconnect. The site now stays disconnected and the banner stays off.
* Fixed: the safety re-read that runs before those settings writes now genuinely reads the database on standard WordPress installs instead of a stale in-memory copy.
* Fixed: corrected the Unlimited plan price (89 EUR/mo), removed a scheduled-scan cadence (bi-weekly) that does not exist, updated the banner script size to around 25KB gzipped, and clarified that banner translations (70+ languages) come from the Kukie.io service while the plugin's own admin interface ships in 11 languages.
* Changed: updated the German regulation name from TTDSG to TDDDG throughout the listing.
* Improved: removed unused admin styles and cleaned up six incorrect auto-matched translation suggestions from the bundled language files (those strings show in English until proper translations arrive).

= 1.7.1 =
* Fixed: entering an incorrect API key when reconnecting no longer switches off the cookie banner. The banner now always keeps working while only the dashboard connection (stats, scans, settings sync) is affected by API key problems.
* Fixed: settings pages are no longer saveable after a failed load, so a blind save can no longer disable the banner or clear your enabled languages.
* Fixed: reconnecting keeps your Script Position choice, and failed saves no longer change local settings.
* Fixed: WPML/Polylang language detection now reports Brazilian Portuguese (pt_BR) sites to the banner as pt-br instead of collapsing them to generic Portuguese.
* Fixed: real error messages from Kukie.io are shown instead of a generic "API error." message, and a rare stored-key encoding issue is healed automatically.
* Improved: cleaner uninstall (including multisite), translatable 1.7.0 admin strings, and various small admin polish fixes.

= 1.7.0 =
* Changed: Minimum required WordPress version raised from 6.0 to 6.7.
* Fixed: Stored API key encryption no longer uses a delimiter that a random encryption value could collide with; keys are migrated to the new format automatically and existing keys keep working.
* Fixed: After a security-keys (salts) rotation or a database copy from another site, the plugin now detects that the stored API key can no longer be read and shows a clear reconnect notice instead of a broken "connected" dashboard. The cookie banner itself keeps working.
* Fixed: Settings saves now use optimistic locking - if the same settings were changed in the Kukie.io dashboard after the plugin page was loaded, you are warned and can choose whether to overwrite instead of silently losing the other changes.
* Fixed: An invalid revisit-button background colour no longer rejects the whole banner design save; it now falls back to the default like the icon colour does.
* Fixed: The scan button now shows the actual reason a scan could not start (already running, queue full, or rate limit) instead of always reporting "a scan is already running".
* Fixed: The banner enabled/disabled indicator in the admin bar no longer flips when a settings save fails.
* Fixed: Site verification no longer times out on the plugin side while the server is still checking slow sites.
* Fix: existing installs now always load the current CDN banner script; stale stored embed URLs are self-corrected on upgrade.

= 1.6.2 =
* Fix: Manual embed code snippet on the settings page showed an invalid script URL (built from the dashboard URL instead of the CDN bundle URL), which could 404 if pasted. The snippet now uses the correct CDN bundle URL. Sites using the recommended automatic <head>/<body> injection were not affected.

= 1.6.1 =
* Fix: Removed regulatory framing from GCM and UET admin descriptions per Google CMP Partner Program guidance. Consent Mode is now correctly described as a technical mechanism for communicating consent to Google/Microsoft services rather than as a regulatory compliance solution.

= 1.6.0 =
* Added: Full WPML compatibility - banner now follows WPML active language automatically
* Added: Full Polylang compatibility - banner follows Polylang language on pages without WPML
* Added: "Banner language" setting for manual override (Auto-detect by default)
* Added: `kukie_script_lang` filter for programmatic language override
* Added: `wpml-config.xml` for WPML Go Global program compliance
* Changed: Script tag now includes `data-lang` attribute when language detection succeeds
* Tested: WPML 4.6+, Polylang 3.4+

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