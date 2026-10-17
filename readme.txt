=== Kukie - Cookie Consent & Privacy Banner (GDPR, CCPA, DSGVO) ===
Contributors: kukieio, filesubmit
Tags: cookie consent, cookie banner, gdpr, ccpa, consent mode
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.1
Stable tag: 1.11.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Cookie consent banner for GDPR, CCPA and DSGVO. Blocks trackers until visitors choose, records every choice, supports Google Consent Mode v2.

== Description ==

Kukie adds a cookie consent banner to your WordPress site and holds back analytics and advertising scripts until each visitor makes a choice. Every choice is recorded, Google Consent Mode v2 is built in and the banner speaks 70+ languages.

The plugin connects your site to a free [Kukie.io](https://kukie.io) account, where your banner settings, cookie scans and consent records are kept. You manage the banner from WordPress or from Kukie.io.

= Why Kukie =

* **Free plan** for up to 5 sites, with the banner, the cookie scanner and consent records.
* **Light**: one script under 40KB gzipped, loaded asynchronously from a CDN.
* **Works with caching plugins**: WP Rocket, LiteSpeed Cache, Autoptimize, W3 Total Cache, WP Fastest Cache and SiteGround Optimizer leave the banner script alone.
* **Multilingual**: follows WPML and Polylang with nothing to set up.
* **Managed in WordPress**: design, behaviour, Consent Mode and languages are on the plugin's own page.

= Cookie banner and consent management =

* Accept all, Reject all and Manage preferences buttons. In opt-in regions, Accept all and Reject all look the same unless you set your own button colours.
* Preferences by category (Essential, Functional, Analytics, Marketing) or by service.
* Script blocking: known trackers such as Google Analytics and the Meta Pixel wait for consent. Tag a script with `data-cc-category` where you need certainty.
* iFrame blocking: YouTube, Google Maps and social embeds wait behind a placeholder.
* A revisit button, so visitors can change their choice at any time.
* Consent records with a receipt you can verify, stored in the EU and exportable as CSV.
* Global Privacy Control and Do Not Track can count as a rejection.
* 4 layouts (pop-up, bottom bar, top bar, floating) in your own colours.

= Google Consent Mode v2 and Microsoft UET =

Switch on Google Consent Mode v2 and the banner passes each choice to Google Analytics, Google Ads and Google Tag Manager. The plugin shows the banner text Google recommends for Consent Mode and applies it to every language of your banner with one button. Microsoft UET Consent Mode does the same for Microsoft Advertising.

= Cookie scanner =

A real browser visits your pages and lists every cookie and storage item, sorted into categories from a database of thousands of known cookies. Scheduled scans come with Pro and above.

= Region rules for the GDPR, the CCPA and more =

Choose opt-in, opt-out, notice only or no banner for each country or region: opt-in for the GDPR (DSGVO), the UK GDPR and the ePrivacy Directive, opt-out with a "Do Not Sell or Share My Personal Information" link for California (CCPA/CPRA), and rules for the LGPD, PIPEDA, POPIA, the CNIL guidance, the German TDDDG and Quebec's Law 25.

= Also included =

* Cookie policy, privacy policy and terms generators that start from your scan results. The text is a starting point, not legal advice.
* Consent analytics by day, category and country.
* Accessibility widget: bigger text, contrast modes, a dyslexia-friendly font, read aloud and more for your visitors. A plan feature.
* Uptime monitoring with email alerts, SSL expiry warnings and a monthly report. A plan feature.
* Team roles (Owner, Admin, Editor) and two-factor sign-in on Kukie.io.

= Plans =

The free plan covers up to 5 sites with the banner, Google Consent Mode v2, Microsoft UET, 70+ languages, the cookie scanner (100 pages per scan), consent records kept for 12 months, region rules, analytics and the policy generators.

Pro (from €9 per month), Agency (from €19 per month) and Unlimited (from €89 per month) add scheduled scans, custom CSS, more sites, more pages per scan, longer record keeping and, from Agency, branding removal. Every paid plan has a 14-day free trial. [Compare plans](https://kukie.io/pricing).

= External service =

This plugin relies on [Kukie.io](https://kukie.io), a third-party cookie consent service. It connects to the service only after you connect it with your API key.

* **On every public page:** the banner script loads from `https://cdn.kukie.io` with your banner settings built in. The banner sends each visitor's choice to `https://app.kukie.io` for the consent record, with the IP address and user agent hashed, and asks it for the visitor's region when your region rules need it.
* **From the plugin's admin pages:** your API key and the settings you save are sent to `https://app.kukie.io`, which also returns your statistics, scans and uptime data.
* The plugin itself stores no personal data about your visitors.

[Terms of Service](https://kukie.io/terms-of-service) and [Privacy Policy](https://kukie.io/privacy-policy).

**Installing this plugin does not on its own make your site meet the GDPR, the CCPA or any other privacy law. That depends on how you set it up and may need further measures specific to your organisation.**

== Installation ==

1. In WordPress, go to **Plugins > Add New Plugin** and search for **Kukie**.
2. Select **Install Now**, then **Activate**.
3. Open **Kukie.io** in the admin menu and paste the API key from your site on [app.kukie.io](https://app.kukie.io). If you have no account yet, [create one for free](https://app.kukie.io/register).
4. Your banner is live. The side navigation on the Kukie.io page leads to the Dashboard, Consent banner, Accessibility widget, Uptime monitoring and Settings.

Step-by-step instructions: [WordPress plugin documentation](https://kukie.io/docs/wordpress-plugin/install-wordpress-plugin).

== Frequently Asked Questions ==

= What is GDPR cookie consent? =

Under the GDPR and the ePrivacy Directive, a website generally needs a visitor's consent before it sets cookies that are not strictly necessary, such as analytics and advertising cookies. A cookie consent banner asks for that choice and records it.

= Is the plugin free? =

Yes. The plugin and the Kukie.io free plan (up to 5 sites) cost nothing and need no credit card. Paid plans add scheduled scans, custom CSS, more sites and longer record keeping.

= Do I need a Kukie.io account? =

Yes. Your banner settings, cookie scans and consent records are kept in your Kukie.io account. [Create a free account](https://app.kukie.io/register).

= Where do I find my API key? =

On [app.kukie.io](https://app.kukie.io), open your site, then **Site Settings > API key**. Only an owner or admin of the organisation can generate one.

= Does it block cookies before consent? =

With **Auto-block scripts** on (**Consent banner > Behaviour**), known trackers and any script tagged with `type="text/plain"` and `data-cc-category` wait for consent, and iFrame blocking holds back embeds. A browser cannot stop a script that other code has already run, so tag the scripts you must be certain about.

= Does it support Google Consent Mode v2? =

Yes. Switch it on in **Consent banner > Google Consent Mode v2**. Google tags start with consent denied, except in regions where your rules allow cookies until the visitor chooses, and while the banner is on screen no consent update is sent until the visitor makes a choice. **Apply Google's banner text** adds the text Google's EU user consent policy asks for to every language of your banner.

= Which privacy laws does it work with? =

Region rules set the consent model for each country or region: opt-in for the GDPR (EU and UK) and the ePrivacy Directive, opt-out for the CCPA/CPRA, and rules for the LGPD, PIPEDA, POPIA, the CNIL guidance, the TDDDG and others. The visitor's region decides which rule applies.

= Does it work with WPML and Polylang? =

Yes. The banner follows the page language from WPML or Polylang, then the WordPress site language, and you can fix one language under **Consent banner > Language**. Brazilian Portuguese, Mexican and Argentinian Spanish, and Simplified and Traditional Chinese keep their own translation.

= Does it work with caching plugins? =

Yes. The script loads from cdn.kukie.io with your settings built in, so cached pages always show the current banner. The plugin also keeps the script out of the minify, combine, defer and delay settings of WP Rocket, LiteSpeed Cache, Autoptimize, W3 Total Cache, WP Fastest Cache and SiteGround Optimizer.

= Will it slow down my site? =

The banner script is under 40KB gzipped and loads asynchronously from a CDN, so it does not block the page from rendering. The optional accessibility widget adds about 15KB gzipped, only on sites that switch it on.

= Can I hide the banner for administrators? =

Yes. Turn on **Hide for administrators** under **Settings**. This keeps page builders such as Bricks and Elementor working after cookies are rejected, and visitors still see the banner. Developers can change the rule with the `kukie_hide_banner_for_user` filter.

= Can I export consent records? =

Yes. Each choice is stored with a timestamp, the categories chosen and hashed visitor details. Export the records as CSV on Kukie.io.

= Do I still need a privacy policy? =

Yes. A consent banner is one part of privacy work. Kukie.io includes cookie policy and privacy policy generators on every plan, including the free plan.

= Does the accessibility widget make my site meet accessibility laws? =

No widget does that on its own. It helps visitors read and navigate, but meeting WCAG, the European Accessibility Act or the ADA depends on your content, your theme and your own testing.

= Will uptime checks show up in my analytics? =

Not in Google Analytics or other tag-based tools: a check runs no JavaScript. Server-log statistics count it and can filter it out by its user agent.

== Screenshots ==

1. Dashboard: banner status, verification, consent counts and the latest cookie scan.
2. Consent banner design: layout, position and revisit button, with a preview.
3. Google Consent Mode v2, with the banner text Google recommends.
4. Banner behaviour: script blocking, Do Not Track, Global Privacy Control and pages without a banner.
5. Uptime monitoring: status, uptime, response time and SSL certificate expiry.
6. Accessibility widget settings.
7. Settings: connection, script position and installation check.
8. The preferences modal with cookie categories.
9. The cookie consent banner on a website.

== Changelog ==

= 1.11.0 =
* New: plugin name "Kukie - Cookie Consent & Privacy Banner".
* New: a side navigation inside the plugin page for every section and banner tab, with one Kukie.io item in the WordPress menu.
* New: a brand bar with your site's domain and plan.
* Improved: refreshed admin design with softer cards, buttons and tabs.
* Improved: on tablets and phones the sections become a row above the page.
* Improved: faster banner loading, as browsers connect to the Kukie.io CDN earlier.
* Security: the API key is never sent on to a redirected address.
* Fixed: caching-plugin filters no longer fail when another plugin passes an unexpected value.
* Changed: a shorter plugin listing with new screenshots.

= 1.10.0 =
* New: Google's recommended banner text on the Google Consent Mode v2 tab, applied to every language with one button.
* New: a notice when a language or region no longer has all of Google's text.
* Changed: the tab describes the default consent state the banner sends since October 2026.
* Documented: the WP Consent API integration reports opt-in in every region.
* Fixed: the banner script size in this listing (under 40KB gzipped).

= 1.9.0 =
* New: Uptime monitoring page with the page to check, the interval, uptime, response time, SSL expiry and incidents.
* New: an Uptime monitoring card on the dashboard.
* New: Preferences modal tab for the width, button layout and order, overlay, policy links and title icon.
* New: live cookie scan progress on the dashboard.
* New: Mexican and Argentinian Spanish banner translations.
* Changed: every admin page reworded with plainer labels and clearer error messages.
* Changed: errors stay on the page, and confirmations use an accessible dialog.
* Changed: dates, numbers and times follow your WordPress language.
* Fixed: keyboard focus shows a clear outline again.
* Fixed: better contrast for badges, switches and placeholders.
* Improved: saving settings needs one request instead of two.

= 1.8.2 =
* New: Hide for administrators, so page builders such as Bricks and Elementor keep working after cookies are rejected.
* New: the kukie_hide_banner_for_user filter for developers.

= 1.8.1 =
* Improved: the admin pages added in 1.8.0 are translated into all 11 languages.

= 1.8.0 =
* New: Behaviour, iFrame blocking, Language and Regions tabs on the Consent banner page.
* New: Accessibility widget page with position, colour, size, modules and languages.
* Changed: Banner design, Google Consent Mode v2 and Microsoft UET moved into one Consent banner page, and old links redirect.
* Changed: refreshed admin styling with WordPress-native notices and tabs.
* Fixed: today's consent counts include live consents.
* Fixed: the Settings page follows plan and organisation changes made on Kukie.io.
* Fixed: a save on one page can no longer reset a setting from another.
* Fixed: PHP 8.1 deprecation notices on hidden admin pages.

= 1.7.3 =
* New: Lock and Sliders icons for the revisit button.
* Improved: a sharper Shield icon.

= 1.7.2 =
* Fixed: a disconnect could be undone by a save in another tab.
* Fixed: plan prices and the script size in this listing.

= 1.7.1 =
* Fixed: a wrong API key no longer switches the banner off.
* Fixed: settings cannot be saved after a failed load.
* Fixed: reconnecting keeps your script position.
* Fixed: Brazilian Portuguese detected with WPML and Polylang.
* Improved: real error messages from Kukie.io and a cleaner uninstall.

= 1.7.0 =
* Changed: requires WordPress 6.7 or later.
* Fixed: a clear reconnect notice after a security keys change or a database copy.
* Fixed: a warning before overwriting settings changed on Kukie.io.
* Fixed: the scan button shows why a scan could not start.
* Fixed: site verification no longer times out early.

= 1.6.2 =
* Fixed: the manual embed code showed the wrong script URL.

= 1.6.1 =
* Changed: Consent Mode descriptions reworded as a technical signal.

= 1.6.0 =
* New: WPML and Polylang support.
* New: Banner language setting.
* New: the kukie_script_lang filter.

= 1.5.0 =
* New: automatic WP Rocket defer exclusion.
* Improved: the WP Rocket notice checks the settings in effect.

= 1.4.1 =
* Fixed: the banner keeps showing after the API key changes.
* New: a notice when the API key is invalid.

= 1.4.0 =
* New: admin translations in 11 languages.

= 1.3.4 =
* Fixed: WP Consent API script order.

= 1.3.3 =
* Fixed: WP Consent API integration loading.

= 1.3.2 =
* Fixed: WP Consent API script timing.

= 1.3.1 =
* Fixed: wp_has_consent() always returned true.

= 1.3.0 =
* New: WP Consent API integration with category mapping.

= 1.2.2 =
* New: exclusions for six caching plugins.
* Fixed: caching plugins could corrupt the banner script.

= 1.2.1 =
* Fixed: WP Rocket notice link.

= 1.2.0 =
* New: WP Rocket compatibility check.

= 1.1.3 =
* Changed: plugin name.

= 1.1.2 =
* New: revisit button icon colour.

= 1.1.1 =
* Fixed: other plugins' notices inside Kukie pages.

= 1.1.0 =
* Changed: the banner loads through wp_enqueue_script.
* Improved: input sanitisation and output escaping.

= 1.0.9 =
* Fixed: banner loading timing.
* New: cache-busting after settings changes.

= 1.0.8 =
* New: revisit button settings.

= 1.0.7 =
* Fixed: layout settings sync with Kukie.io.

= 1.0.5 =
* New: banner design page with a preview.

= 1.0.1 =
* Changed: branding.
* Fixed: settings page loading.

= 1.0.0 =
* Initial release.
