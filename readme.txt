=== Kukie - Cookie Banner and Consent Management (GDPR, CCPA, DSGVO, CNIL, PIPEDA) ===
Contributors: kukieio, filesubmit
Tags: cookie consent, gdpr, ccpa, wpml, polylang
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.9.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Cookie consent banner for WordPress: hold back trackers until visitors choose, record every choice, Google Consent Mode v2 and 70+ languages.

== Description ==

Kukie.io is a cookie consent platform. This plugin connects your WordPress site to your Kukie.io account and adds a consent banner that holds back non-essential scripts until a visitor chooses, and keeps a record of every choice.

Region rules let you match the banner to the rules that apply to each visitor: opt-in for visitors covered by the GDPR (DSGVO), the UK GDPR and the ePrivacy Directive, opt-out with a "Do Not Sell or Share My Personal Information" link for California (CCPA/CPRA), and settings for LGPD (Brazil), PIPEDA (Canada), POPIA (South Africa), the French CNIL guidance and the German TDDDG.

= Features =

Everything below is on the **free plan** unless it names a plan.

**Consent management**

* Consent banner: Accept all, Reject all and Manage preferences. In opt-in regions, Accept all and Reject all have the same fill, size and weight unless you set your own button colours.
* Script blocking: known trackers such as Google Analytics and the Meta Pixel wait until the visitor agrees. Blocking a script that other code has already inserted is best effort, so tag the scripts you must be certain about with data-cc-category.
* Preferences by category (Essential, Functional, Analytics, Marketing) and, if you want, by service.
* Revisit button so visitors can change their choice at any time: 4 positions, 3 styles, 6 icons.
* California opt-out: the "Do Not Sell or Share My Personal Information" link.
* Consent records: every choice is recorded with a receipt you can verify, and you can export the records as CSV.
* Google Consent Mode v2: once you switch it on, the banner passes each choice to Google Analytics, Google Ads and Google Tag Manager.
* Google Tag Manager and Microsoft UET Consent Mode.
* Global Privacy Control and Do Not Track: optionally treat these browser signals as a rejection.
* Consent records are stored on servers in the EU.

**Cookie scanner**

* A real browser visits your pages and lists every cookie, plus localStorage and sessionStorage.
* Cookies are sorted into categories from a database of thousands of known cookies.
* Scheduled scans (Pro plan and above), alerts for new cookies, and scan history.

**Banner design**

* 4 layouts: pop-up, bottom bar, top bar and floating.
* Your own background, text and button colours.
* Custom CSS and a banner logo (Pro plan and above).
* Remove the "Powered by Kukie.io" line (Agency plan and above).
* Preferences modal: its width, button layout and order, background overlay, policy links and title icon, set from the plugin's Consent banner page.

**Languages and accessibility**

* The banner appears in the visitor's language, with 70+ languages and right-to-left support for Arabic, Hebrew and others.
* The banner works with a keyboard and screen readers, and its buttons and switches are sized for touch.
* Accessibility widget: an optional floating button that opens a panel of reading, contrast and navigation aids for visitors, such as bigger text, text spacing, a dyslexia-friendly font, high contrast, read aloud and one-tap profiles. It runs inside the same banner script, so there is nothing extra to install, and you set it up on the plugin's Accessibility widget page or on Kukie.io. A plan feature: the page shows which plan includes it. The widget helps visitors, but it does not on its own make a website meet any accessibility law.

**Uptime monitoring**

* Kukie.io checks your homepage around the clock and emails you when it stops responding, and again when it is back, with the cause in plain words.
* Two failed checks in a row are needed before an alert, and a failed check is always repeated one minute later.
* Warnings before your SSL certificate expires, and an optional monthly uptime report.
* Turn it on, choose the page and the check interval on the plugin's Uptime monitoring page. A plan feature: the page shows which plan includes it.
* Checks run no JavaScript, so they never count as visits in Google Analytics or similar tools.

**Region rules**

* Visitor region from Cloudflare and MaxMind GeoLite2.
* Opt-in, opt-out, notice-only or no banner per country or region.
* Sub-region rules, for example for Germany (TDDDG), France (CNIL), individual US states and Quebec (Law 25).
* An optional cookie wall for chosen regions.

**Legal documents**

* Cookie policy, privacy policy and terms of service generators that start from your scan results.
* Publish them as a public page, embed them in an iFrame, or copy the HTML.
* The generated text is a starting point for your own policies, not legal advice.

**Analytics**

* Consent rates by day, week and month, by category and by country.
* CSV export of consent records and analytics.
* Consent reports (Agency plan and above).

**Script Centre**

* Manage third-party scripts per service, so each loads only with consent.
* iFrame blocking: YouTube, Google Maps, social embeds and other services wait for consent behind a styled placeholder.
* Built-in detectors for Google Analytics, the Meta Pixel, Hotjar and more.
* Place scripts at the start or end of the head or the body.

**Security**

* Two-factor authentication for your Kukie.io account.
* Rate limits on the API.
* Visitor IP addresses and user agents are hashed in consent records.
* Team roles: Owner, Admin and Editor.

= What's included on each plan =

The free plan includes the consent banner, 4 layouts, Google Consent Mode v2, Google Tag Manager, Microsoft UET Consent Mode, 70+ languages, the cookie scanner (100 pages per scan), consent records kept for 12 months, region rules, analytics, the legal document generators, iFrame blocking and the Script Centre, for up to 5 sites.

Paid plans add:

* **Pro** (from €9 per month): scheduled scans, custom CSS, a banner logo, 20 sites, 500 pages per scan, 3 team members and consent records kept for 24 months.
* **Agency** (from €19 per month): everything in Pro, plus consent reports, removing the Kukie.io branding, 100 sites, 3,000 pages per scan and 10 team members.
* **Unlimited** (from €89 per month): everything in Agency, plus unlimited sites, pages and team members, and consent records kept for 36 months.

The accessibility widget and uptime monitoring are plan features; their pages in the plugin show which plan includes them. Every paid plan has a 14-day free trial. [Compare all plans](https://kukie.io/pricing).

= Useful links =

* [Kukie.io website](https://kukie.io)
* [WordPress plugin page](https://kukie.io/wordpress)
* [Features](https://kukie.io/features)
* [Help Centre](https://kukie.io/docs)
* [WordPress plugin documentation](https://kukie.io/docs/wordpress-plugin)
* [Blog](https://kukie.io/blog)
* [Facebook](https://www.facebook.com/Kukie.io)
* [X (Twitter)](https://x.com/kukie_io)
* [LinkedIn](https://www.linkedin.com/company/kukie-io/)

= External service =

This plugin relies on [Kukie.io](https://kukie.io), a third-party cookie consent service, for its core features.

By installing the plugin and connecting it with your API key, you agree to connect to the Kukie.io service.

**What is loaded:**

* A consent banner script from `https://cdn.kukie.io` (over HTTPS).
* The script contains your banner configuration (colours, texts, cookie categories).

**What is sent:**

* Your site key, so the banner loads your configuration.
* From the plugin's admin pages: your API key and the settings you save, to `https://app.kukie.io`.
* The plugin itself collects no personal data about your visitors.

**When:**

* The banner script loads on every public page of your website.
* The plugin's admin pages connect to `https://app.kukie.io` to read and save your settings, statistics and uptime monitoring.

**Service links:**

* [Kukie.io website](https://kukie.io)
* [Terms of Service](https://kukie.io/terms-of-service)
* [Privacy Policy](https://kukie.io/privacy-policy)

NOTE: INSTALLING THIS PLUGIN DOES NOT ON ITS OWN MAKE YOUR SITE MEET THE GDPR, THE CCPA OR ANY OTHER PRIVACY LAW. THAT DEPENDS ON HOW YOU SET IT UP AND MAY NEED FURTHER MEASURES SPECIFIC TO YOUR ORGANISATION.

== Multilingual Support ==

Kukie.io works with WPML and Polylang. When a visitor views a translated page, the consent banner appears in the matching language, with nothing more to set up.

Language sources, in priority order:

1. The "Banner language" setting in the plugin (Consent banner > Language)
2. The WPML language of the page
3. The Polylang language of the page
4. The WordPress site language

Regional languages that Kukie.io ships as their own translation keep their region: Brazilian Portuguese, Mexican and Argentinian Spanish, and Simplified and Traditional Chinese.

Banner texts and their translations (titles, descriptions, buttons, cookie categories) are managed on https://app.kukie.io, where 70+ banner languages are available. The plugin's own admin pages are translated into 11 languages.

== Installation ==

1. In your WordPress dashboard, go to **Plugins > Add New**.
2. Search for **Kukie**.
3. Select **Install Now**, then **Activate**.
4. Go to **Kukie.io** in the admin menu.
5. Paste the API key from your site on [app.kukie.io](https://app.kukie.io).
6. Your consent banner is live.

The plugin adds five admin pages under **Kukie.io**: **Dashboard** (banner status, consent counts, scans), **Consent banner** (Design, Preferences modal, Behaviour, iFrame blocking, Language, Google Consent Mode v2, Microsoft UET and Regions tabs), **Accessibility widget**, **Uptime monitoring** and **Settings** (script position, hide for administrators, connection).

You can also download the plugin from [WordPress.org](https://wordpress.org/plugins/kukie-cookie-consent/) and upload the ZIP file in **Plugins > Add New > Upload Plugin**.

For step-by-step instructions, see the [WordPress plugin documentation](https://kukie.io/docs/wordpress-plugin/install-wordpress-plugin).

== Frequently Asked Questions ==

= What is GDPR cookie consent? =

Under the GDPR and the ePrivacy Directive, a website generally needs a visitor's consent before it sets cookies that are not strictly necessary, such as analytics and advertising cookies. A consent banner asks for that choice and records it.

= What is the CCPA? =

The California Consumer Privacy Act (CCPA), as amended by the CPRA, gives California residents the right to opt out of the sale or sharing of their personal information. Websites covered by it show a "Do Not Sell or Share My Personal Information" link.

= Is the plugin free? =

Yes. The plugin is free. It connects to your Kukie.io account, where you can stay on the free plan (up to 5 sites) or upgrade for scheduled scans, custom CSS, consent reports and more.

= Do I need a Kukie.io account? =

Yes. Your banner configuration, cookie scans and consent records live in your Kukie.io account. [Creating an account](https://app.kukie.io/register) is free and needs no credit card.

= Where do I find my API key? =

Sign in to [app.kukie.io](https://app.kukie.io), open your site, then Site Settings, then API key. Only an owner or admin of the organisation can generate one.

= Does it support Google Consent Mode v2? =

Yes. Switch it on in **Consent banner > Google Consent Mode v2**, and the banner passes each visitor's choice to Google Analytics, Google Ads and Google Tag Manager, with no tag changes needed.

= Does the plugin block cookies before consent? =

With **Auto-block scripts** on (**Consent banner > Behaviour**), the banner holds back known trackers until the visitor agrees, together with any script you tag with type="text/plain" and data-cc-category. iFrame blocking does the same for embedded videos, maps and widgets. A browser cannot cancel a script that other code has already run, so tag the scripts you must be certain about.

= Does it work with caching plugins? =

Yes. The banner script loads from cdn.kukie.io with your configuration built in, so cached pages show the current banner. The plugin also keeps the script out of the minify, combine, defer and delay settings of WP Rocket, Autoptimize, WP Fastest Cache, LiteSpeed Cache, W3 Total Cache and SiteGround Optimizer.

= Which privacy laws does it work with? =

Region rules let you set the consent model per country or region: opt-in for the GDPR (EU and UK) and the ePrivacy Directive, opt-out for the CCPA/CPRA, and rules for the LGPD (Brazil), PIPEDA (Canada), POPIA (South Africa), the CNIL guidance (France), the TDDDG (Germany) and others. The visitor's region decides which rule applies.

= Does it work on multilingual websites? =

Yes. The banner appears in the visitor's language, with 70+ languages and right-to-left support for Arabic, Hebrew and others. With WPML or Polylang, it follows the language of the page.

= Will it slow down my site? =

The banner script is under 35KB gzipped and loads asynchronously from a CDN, so it does not block the page from rendering. The optional accessibility widget adds about 15KB gzipped, and only on sites that switch it on.

= Does the plugin include an accessibility widget? =

Yes, as a plan feature. On the plugin's Accessibility widget page you can switch on a floating button that opens a panel of reading, contrast and navigation aids for visitors (bigger text, a dyslexia-friendly font, high contrast, read aloud, one-tap profiles and more). It runs inside the same banner script, makes no third-party requests, and its panel is available in 70+ languages. On plans without it, the page shows what the widget does and which plan includes it. The widget helps visitors read and navigate, but no widget on its own makes a website meet the European Accessibility Act, WCAG, the ADA or any other accessibility law: that still depends on your content, your theme and your own testing.

= How does uptime monitoring work? =

On plans that include it, the plugin's Uptime monitoring page lets you turn monitoring on, choose the page to check and how often it is checked. Kukie.io then checks the page around the clock, emails the organisation owner when it goes down and again when it is back, warns before the SSL certificate expires, and can send a monthly report. Extra alert recipients and the webhook are managed on Kukie.io. If a firewall or security plugin such as Wordfence blocks the checker, the page lists the addresses and user agent to allow.

= Will uptime checks show up in my analytics? =

No, not in Google Analytics or other tag-based tools: a check is a plain page request that runs no JavaScript. Server-log statistics count it, and can filter it out by its user agent.

= Can I customise the banner design? =

Yes. Choose from 4 layouts and set your colours and texts; on the Pro plan and above you can add a logo and custom CSS. The layout and the revisit button can be set from the plugin, everything else on [Kukie.io](https://app.kukie.io) with a live preview.

= Can I export consent records? =

Yes. Every choice is recorded with a timestamp, the categories chosen and hashed visitor details. Export the records as CSV from Kukie.io, for example for an audit.

= Do I still need a privacy policy? =

Yes. A consent banner is one part of privacy work: you also need a privacy policy and a cookie policy. Kukie.io includes generators for both on every plan, including the free plan.

= Can I hide the banner for administrators? =

Yes. Turn on **Hide for administrators** on the plugin's Settings page. Logged-in administrators then do not get the banner, which keeps front-end page builders such as Bricks or Elementor working after cookies were rejected. Visitors still see the banner. Developers can widen or narrow the rule (for example to editors who use the builder) with the `kukie_hide_banner_for_user` filter.

= Does Kukie.io suit agencies with many sites? =

Yes. You can manage many sites with team roles (Owner, Admin, Editor). The free plan covers up to 5 sites, Pro 20, Agency 100 and Unlimited has no limit.

== Screenshots ==

1. Plugin dashboard: connection status, site key and quick access to Kukie.io settings
2. Banner design settings: layout, position and a preview
3. Google Consent Mode v2 and Google Tag Manager settings
4. Language and auto-translation settings with 70+ languages
5. The consent banner on a WordPress site: pop-up layout with Accept all, Reject all and Manage preferences
6. Cookie scan results: cookies sorted by category (Essential, Functional, Analytics, Marketing)
7. Consent analytics on Kukie.io: acceptance rates, trends and countries
8. Revisit button settings: position, style, icon and colour

== Changelog ==

= 1.9.0 =
* Added: Uptime monitoring page. On plans that include it, turn monitoring on, choose the page to check and the check interval, and switch the owner's alert emails and the monthly report on or off. The page shows whether the site is up, uptime for the last 24 hours, 7 days and 30 days, the average response time, the SSL certificate's expiry date, recent incidents, and the checker's addresses to allow in a firewall or security plugin. On other plans it shows what the feature does and which plan includes it. Needs the Kukie.io service update of 1 October 2026.
* Added: an Uptime monitoring card on the dashboard.
* Added: a Preferences modal tab on the Consent banner page. Set the modal's width, whether its three buttons sit on one row or stacked, their order, the background overlay, the policy links and the icon or logo next to its title, with a preview. The modal's texts and button colours stay on Kukie.io. Needs the Kukie.io service update of 1 October 2026.
* Added: a running cookie scan now shows its progress on the dashboard ("Scanning page 12 of 40") instead of figures up to five minutes old.
* Added: Mexican and Argentinian Spanish sites (WPML, Polylang or the WordPress language) now get the matching banner translation, and both can be chosen as the banner language.
* Changed: every admin page was reworded: sentence-case labels, plainer descriptions, and error messages that say what to do next. Settings pages use "Save changes".
* Changed: errors stay on the page as a notice instead of disappearing after a few seconds. Disconnecting and the "changed elsewhere" warning now open an accessible dialog instead of the browser's confirmation box.
* Changed: dates, numbers and times follow your WordPress language.
* Changed: the dashboard's consent counts are labelled "Last 7 days" and "Last 30 days", which is what they always counted.
* Fixed: keyboard focus on buttons, checkbox chips and layout options showed a blue fill with white text and no outline. Focus is now a clear outline again.
* Fixed: better contrast for status badges, the success message, switches in the off position and input placeholders. The admin bar indicator now says whether the banner is on or off, not only by its colour.
* Improved: saving settings needs one request to Kukie.io instead of two. The dashboard stops refreshing while its browser tab is hidden.
* Changed: corrected this listing: the banner script size (under 35KB gzipped), Google Consent Mode v2 (it signals once switched on), the blocking description, the plan details, the typo in the title, and claims that read as legal promises.

= 1.8.2 =
* Added: "Hide for administrators" on the Settings page. When on, the banner is not loaded for logged-in administrators, so front-end page builders such as Bricks or Elementor keep working after cookies were rejected. Visitors still see the banner. Off by default. Developers can widen or narrow the rule with the kukie_hide_banner_for_user filter.

= 1.8.1 =
* Translations: the admin interface added in 1.8.0 is now fully translated into German, Spanish, French, Italian, Japanese, Dutch, Polish, Brazilian Portuguese, Romanian and Turkish (Bulgarian was already complete).

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