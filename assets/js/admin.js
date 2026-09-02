/**
 * Kukie.io WordPress Plugin - Admin JS
 * Pure vanilla JS, no jQuery dependency.
 */

(function () {
	'use strict';

	// ─────────────────────────────────────────
	// i18n Helper
	// ─────────────────────────────────────────

	// Translated strings come in via wp_localize_script (kukieAdmin.i18n).
	// The inline English fallback keeps a stale cached script from ever
	// rendering "undefined".
	function kukieI18n(key, fallback) {
		return (window.kukieAdmin && kukieAdmin.i18n && kukieAdmin.i18n[key]) || fallback;
	}

	// Single %s substitution for the few templated strings.
	function kukieSprintf(template, value) {
		return String(template).replace('%s', value);
	}

	// ─────────────────────────────────────────
	// AJAX Helper
	// ─────────────────────────────────────────

	async function kukieAjax(action, data = {}) {
		const formData = new FormData();
		formData.append('action', action);
		formData.append('nonce', kukieAdmin.nonce);

		Object.entries(data).forEach(([key, value]) => {
			if (Array.isArray(value)) {
				value.forEach(v => formData.append(key + '[]', v));
			} else {
				formData.append(key, value);
			}
		});

		try {
			const response = await fetch(kukieAdmin.ajaxUrl, {
				method: 'POST',
				body: formData,
				credentials: 'same-origin',
			});
			return await response.json();
		} catch (err) {
			return { success: false, data: { message: kukieI18n('networkError', 'Network error. Please try again.') } };
		}
	}

	// ─────────────────────────────────────────
	// Settings Save (optimistic locking)
	// ─────────────────────────────────────────

	// Server-side config version from the last kukie_get_settings load.
	// Sent back with every save so the server can reject the write with a
	// 409 when the settings changed elsewhere (dashboard edit, another
	// plugin instance) instead of silently overwriting them.
	let currentConfigVersion = null;

	function rememberConfigVersion(d) {
		if (d && typeof d.config_version === 'number') {
			currentConfigVersion = d.config_version;
		}
	}

	// Save with conflict handling: on a 409 the user is told their copy was
	// superseded and asked whether to overwrite. Retries at most once per
	// confirmation, so a conflict can never spin.
	async function kukieSaveSettings(action, data) {
		// No successful settings load has completed on this page, so the form
		// may hold unpopulated template defaults and there is no version for
		// the optimistic lock - a save here would be a blind last-write-wins
		// overwrite (it could disable the banner and wipe enabled languages).
		// Refuse instead.
		if (currentConfigVersion === null) {
			return {
				success: false,
				data: { message: kukieI18n('saveDisabled', 'Settings could not be loaded, so saving is disabled. Please reload the page.') },
			};
		}

		data.config_version = currentConfigVersion;

		let result = await kukieAjax(action, data);

		if (!result.success && result.data?.code === 'version_conflict') {
			// 0 is a legitimate version - never a truthiness check here.
			if (typeof result.data.current_version === 'number') {
				currentConfigVersion = result.data.current_version;
			}

			const overwrite = window.confirm(kukieI18n(
				'conflictPrompt',
				'These settings were changed elsewhere (for example in the Kukie.io dashboard) after this page was loaded.\n\n' +
				'OK: save anyway and overwrite the other changes.\n' +
				'Cancel: keep the other changes (reload this page to see them).'
			));

			if (!overwrite) {
				return result;
			}

			// currentConfigVersion is non-null here: the guard at the top of
			// this function established it, and the 409 branch only ever
			// replaces it with the server's numeric current_version.
			data.config_version = currentConfigVersion;
			result = await kukieAjax(action, data);
		}

		if (result.success) {
			rememberConfigVersion(result.data);
		}

		return result;
	}

	// ─────────────────────────────────────────
	// Toast Notifications
	// ─────────────────────────────────────────

	function showToast(message, type = 'success') {
		const existing = document.querySelector('.kukie-toast');
		if (existing) existing.remove();

		const toast = document.createElement('div');
		toast.className = `kukie-toast kukie-toast--${type}`;
		toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
		toast.textContent = message;
		document.body.appendChild(toast);

		setTimeout(() => {
			toast.classList.add('kukie-toast--removing');
			setTimeout(() => toast.remove(), 300);
		}, 4000);
	}

	// ─────────────────────────────────────────
	// Button Loading State
	// ─────────────────────────────────────────

	function setButtonLoading(btn, loading) {
		const text = btn.querySelector('.kukie-btn-text');
		const loader = btn.querySelector('.kukie-btn-loading');
		if (text) text.hidden = loading;
		if (loader) loader.hidden = !loading;
		btn.disabled = loading;
		btn.setAttribute('aria-busy', loading ? 'true' : 'false');
	}

	// ─────────────────────────────────────────
	// Show/Hide Notice (WordPress-native .notice markup)
	// ─────────────────────────────────────────

	function showNotice(id, message, type = 'error') {
		const el = document.getElementById(id);
		if (!el) return;
		let p = el.querySelector('p');
		if (!p) {
			p = document.createElement('p');
			el.appendChild(p);
		}
		p.textContent = message;
		el.className = `notice notice-${type} inline kukie-notice`;
		el.hidden = false;
	}

	function hideNotice(id) {
		const el = document.getElementById(id);
		if (el) el.hidden = true;
	}

	// ─────────────────────────────────────────
	// CONNECT PAGE
	// ─────────────────────────────────────────

	function initConnectPage() {
		const form = document.getElementById('kukie-connect-form');
		const btn = document.getElementById('kukie-connect-btn');
		const input = document.getElementById('kukie-api-key');
		const toggleBtn = document.getElementById('kukie-toggle-key');

		if (!form || !btn) return;

		// Show/hide API key
		if (toggleBtn && input) {
			toggleBtn.addEventListener('click', () => {
				const isPassword = input.type === 'password';
				input.type = isPassword ? 'text' : 'password';
				toggleBtn.setAttribute('aria-pressed', isPassword ? 'true' : 'false');
				const icon = toggleBtn.querySelector('.dashicons');
				if (icon) {
					icon.classList.toggle('dashicons-visibility', !isPassword);
					icon.classList.toggle('dashicons-hidden', isPassword);
				}
			});
		}

		btn.addEventListener('click', async () => {
			hideNotice('kukie-connect-error');
			const apiKey = input.value.trim().replace(/[^a-zA-Z0-9]/g, '');

			if (apiKey.length !== 64) {
				showNotice('kukie-connect-error', 'Invalid API key format. The key should be 64 characters.');
				return;
			}

			setButtonLoading(btn, true);

			const result = await kukieAjax('kukie_connect', { api_key: apiKey });

			setButtonLoading(btn, false);

			if (result.success) {
				form.hidden = true;
				const success = document.getElementById('kukie-connect-success');
				if (success) {
					success.hidden = false;
					setText('kukie-success-org', result.data.organisation);
					setText('kukie-success-plan', result.data.plan);
					setText('kukie-success-domain', result.data.domain);
					const go = document.getElementById('kukie-go-dashboard');
					if (go) go.focus();
				}
			} else {
				showNotice('kukie-connect-error', result.data?.message || 'Connection failed.');
			}
		});

		// Enter key submits
		if (input) {
			input.addEventListener('keydown', (e) => {
				if (e.key === 'Enter') {
					e.preventDefault();
					btn.click();
				}
			});
		}
	}

	// ─────────────────────────────────────────
	// DASHBOARD PAGE
	// ─────────────────────────────────────────

	let dashboardRefreshTimer = null;

	function initDashboardPage() {
		const cards = document.getElementById('kukie-overview-cards');
		if (!cards) return;

		loadDashboardData();

		// Auto-refresh every 60 seconds
		dashboardRefreshTimer = setInterval(loadDashboardData, 60000);

		// Scan trigger
		const scanBtn = document.getElementById('kukie-trigger-scan');
		if (scanBtn) {
			scanBtn.addEventListener('click', triggerScan);
		}
	}

	function badge(label, cls) {
		const span = document.createElement('span');
		span.className = `kukie-badge kukie-badge--${cls}`;
		span.textContent = label;
		return span;
	}

	function setBadge(id, label, cls) {
		const el = document.getElementById(id);
		if (!el) return;
		el.replaceChildren(badge(label, cls));
	}

	async function loadDashboardData() {
		const result = await kukieAjax('kukie_get_status');

		if (!result.success) {
			showNotice('kukie-dashboard-error', result.data?.message || 'Could not load dashboard data.');
			return;
		}

		hideNotice('kukie-dashboard-error');
		const d = result.data;

		// Banner status
		setBadge(
			'kukie-stat-banner',
			d.banner_enabled ? kukieI18n('active', 'Active') : kukieI18n('inactive', 'Inactive'),
			d.banner_enabled ? 'active' : 'inactive'
		);

		// Consents today
		const today = d.consent_stats?.today;
		setText('kukie-stat-today', today
			? String((today.accept_all || 0) + (today.reject_all || 0) + (today.custom_consent || 0))
			: '0'
		);

		// Plan
		const planText = d.plan?.name || 'Free';
		const trialText = d.plan?.trial
			? ' ' + kukieSprintf(kukieI18n('trialDays', '(Trial: %sd)'), d.plan.trial_days_remaining)
			: '';
		setText('kukie-stat-plan', planText + trialText);

		// Verification
		setBadge(
			'kukie-stat-verified',
			d.script_verified ? kukieI18n('verified', 'Verified') : kukieI18n('notVerified', 'Not verified'),
			d.script_verified ? 'active' : 'inactive'
		);

		// Accessibility widget (block present on Kukie.io since plugin 1.8.0;
		// an older service answer simply leaves the card at "-").
		const a11y = d.accessibility_widget;
		if (a11y && typeof a11y === 'object') {
			if (!a11y.available) {
				setBadge('kukie-stat-a11y', kukieI18n('notInPlan', 'Not in plan'), 'inactive');
			} else if (a11y.enabled) {
				setBadge('kukie-stat-a11y', kukieI18n('active', 'Active'), 'active');
			} else {
				setBadge('kukie-stat-a11y', kukieI18n('off', 'Off'), 'inactive');
			}
		} else {
			setText('kukie-stat-a11y', '-');
		}

		// Consent overview
		const todayEl = document.getElementById('kukie-consent-today');
		if (todayEl) {
			if (today) {
				const chip = (cls, label, n) => {
					const s = document.createElement('span');
					s.className = `kukie-consent-chip kukie-consent-chip--${cls}`;
					s.textContent = `${label}: ${n || 0}`;
					return s;
				};
				todayEl.replaceChildren(
					chip('accept', kukieI18n('accepted', 'Accepted'), today.accept_all),
					chip('reject', kukieI18n('rejected', 'Rejected'), today.reject_all),
					chip('partial', kukieI18n('custom', 'Custom'), today.custom_consent)
				);
			} else {
				todayEl.textContent = kukieI18n('noDataYet', 'No data yet');
			}
		}

		setText('kukie-consent-week', d.consent_stats?.this_week?.total ?? '0');
		setText('kukie-consent-month', d.consent_stats?.this_month?.total ?? '0');

		// Scan info
		const scan = d.last_scan;
		if (scan) {
			const cls = scan.status === 'completed' ? 'active'
				: scan.status === 'running' ? 'running'
				: scan.status === 'failed' ? 'failed'
				: 'inactive';
			setBadge('kukie-scan-status', capitalize(scan.status), cls);
			setText('kukie-scan-date', scan.date ? formatDate(scan.date) : 'N/A');
			setText('kukie-scan-cookies', String(scan.cookies_found ?? 0));
			setText('kukie-scan-pages', String(scan.pages_scanned ?? 0));
		} else {
			setText('kukie-scan-status', kukieI18n('noScansYet', 'No scans yet'));
			setText('kukie-scan-date', '-');
			setText('kukie-scan-cookies', '-');
			setText('kukie-scan-pages', '-');
		}
	}

	async function triggerScan() {
		const btn = document.getElementById('kukie-trigger-scan');
		if (!btn) return;

		setButtonLoading(btn, true);

		const result = await kukieAjax('kukie_trigger_scan');

		if (result.success) {
			showToast(result.data.message);
			// Reload dashboard data after a short delay
			setTimeout(loadDashboardData, 2000);
		} else {
			showToast(result.data?.message || 'Could not start scan.', 'error');
		}

		setButtonLoading(btn, false);
	}

	// ─────────────────────────────────────────
	// WP ROCKET NOTICE (dismiss)
	// ─────────────────────────────────────────

	function initRocketNotice() {
		const btn = document.querySelector('#kukie-wp-rocket-notice .kukie-dismiss-btn');
		if (!btn) return;

		btn.addEventListener('click', () => {
			const notice = btn.closest('.notice');
			if (notice) notice.hidden = true;

			const body = new FormData();
			body.append('action', 'kukie_dismiss_wp_rocket_notice');
			body.append('nonce', kukieAdmin.rocketDismissNonce || '');
			fetch(kukieAdmin.ajaxUrl, { method: 'POST', body, credentials: 'same-origin' }).catch(() => {});
		});
	}

	// ─────────────────────────────────────────
	// GCM TAB (Consent banner page)
	// ─────────────────────────────────────────

	function initGcmPage() {
		const form = document.getElementById('kukie-gcm-form');
		const loading = document.getElementById('kukie-gcm-loading');
		if (!form) return;

		loadGcmSettings(form, loading);

		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			hideNotice('kukie-gcm-error');

			const saveBtn = document.getElementById('kukie-gcm-save');
			setButtonLoading(saveBtn, true);

			const data = {
				gcm_v2_enabled: form.querySelector('#kukie-gcm-enabled')?.checked ? '1' : '0',
			};

			const result = await kukieSaveSettings('kukie_save_gcm', data);

			setButtonLoading(saveBtn, false);

			if (result.success) {
				showToast(result.data.message);
			} else {
				showToast(result.data?.message || kukieI18n('failedToSave', 'Failed to save.'), 'error');
			}
		});
	}

	async function loadGcmSettings(form, loading) {
		const result = await kukieAjax('kukie_get_settings');

		if (loading) loading.hidden = true;

		// Reveal the form only after a successful load: a revealed but
		// unpopulated form would post template defaults on Save.
		if (!result.success) {
			showNotice('kukie-gcm-error', result.data?.message || kukieI18n('couldNotLoad', 'Could not load settings.'));
			return;
		}

		form.hidden = false;

		const d = result.data;
		rememberConfigVersion(d);
		setChecked('kukie-gcm-enabled', d.gcm_v2_enabled);
	}

	// ─────────────────────────────────────────
	// UET TAB (Consent banner page)
	// ─────────────────────────────────────────

	function initUetPage() {
		const form = document.getElementById('kukie-uet-form');
		const loading = document.getElementById('kukie-uet-loading');
		if (!form) return;

		loadUetSettings(form, loading);

		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			hideNotice('kukie-uet-error');

			const saveBtn = document.getElementById('kukie-uet-save');
			setButtonLoading(saveBtn, true);

			const data = {
				ms_uet_enabled: form.querySelector('#kukie-uet-enabled')?.checked ? '1' : '0',
			};

			const result = await kukieSaveSettings('kukie_save_uet', data);

			setButtonLoading(saveBtn, false);

			if (result.success) {
				showToast(result.data.message);
			} else {
				showToast(result.data?.message || kukieI18n('failedToSave', 'Failed to save.'), 'error');
			}
		});
	}

	async function loadUetSettings(form, loading) {
		const result = await kukieAjax('kukie_get_settings');

		if (loading) loading.hidden = true;

		// Reveal the form only after a successful load (see loadGcmSettings).
		if (!result.success) {
			showNotice('kukie-uet-error', result.data?.message || kukieI18n('couldNotLoad', 'Could not load settings.'));
			return;
		}

		form.hidden = false;

		const d = result.data;
		rememberConfigVersion(d);
		setChecked('kukie-uet-enabled', d.ms_uet_enabled);
	}

	// ─────────────────────────────────────────
	// SETTINGS PAGE (connection, banner on/off, script position)
	// ─────────────────────────────────────────

	function initSettingsPage() {
		const form = document.getElementById('kukie-settings-form');
		const loading = document.getElementById('kukie-settings-loading');
		const content = document.getElementById('kukie-settings-content');
		if (!form) return;

		loadSettingsData(form, loading, content);
		refreshConnectionCard();

		// Script position radio -> show/hide manual embed
		form.querySelectorAll('input[name="script_position"]').forEach(radio => {
			radio.addEventListener('change', () => {
				const manualEmbed = document.getElementById('kukie-manual-embed');
				if (manualEmbed) {
					manualEmbed.hidden = !(radio.value === 'manual' && radio.checked);
				}
			});
		});

		// Save - only the fields this page owns (the handler is presence-based,
		// so the Language tab's fields are never touched from here).
		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			hideNotice('kukie-settings-error');

			const saveBtn = document.getElementById('kukie-settings-save');
			setButtonLoading(saveBtn, true);

			const data = {
				banner_enabled: form.querySelector('#kukie-banner-enabled')?.checked ? '1' : '0',
				script_position: form.querySelector('input[name="script_position"]:checked')?.value || 'head',
			};

			const result = await kukieSaveSettings('kukie_save_settings', data);

			setButtonLoading(saveBtn, false);

			if (result.success) {
				showToast(result.data.message);
			} else {
				showToast(result.data?.message || kukieI18n('failedToSave', 'Failed to save.'), 'error');
			}
		});

		// Verify button
		const verifyBtn = document.getElementById('kukie-verify-btn');
		if (verifyBtn) {
			verifyBtn.addEventListener('click', async () => {
				setButtonLoading(verifyBtn, true);

				const result = await kukieAjax('kukie_verify');

				setButtonLoading(verifyBtn, false);

				const statusEl = document.getElementById('kukie-verified-status');
				if (result.success && result.data?.verified) {
					showToast(kukieI18n('verifiedToast', 'Banner script verified on your site!'));
					if (statusEl) statusEl.textContent = kukieI18n('verifiedDetected', 'Verified! Banner script detected.');
				} else {
					showToast(result.data?.message || kukieI18n('notFound', 'Banner script not found.'), 'error');
					if (statusEl) statusEl.textContent = result.data?.message || kukieI18n('notVerified', 'Not verified');
				}
			});
		}

		// Disconnect button
		const disconnectBtn = document.getElementById('kukie-disconnect-btn');
		if (disconnectBtn) {
			disconnectBtn.addEventListener('click', async () => {
				if (!confirm(kukieI18n('disconnectConfirm', 'Are you sure you want to disconnect from Kukie.io? The cookie consent banner will be removed from your site.'))) {
					return;
				}

				disconnectBtn.disabled = true;
				disconnectBtn.textContent = kukieI18n('disconnecting', 'Disconnecting...');

				const result = await kukieAjax('kukie_disconnect');

				if (result.success) {
					showToast(result.data.message);
					if (result.data.redirect) {
						window.location.href = result.data.redirect;
					}
				} else {
					showToast(result.data?.message || kukieI18n('failedDisconnect', 'Failed to disconnect.'), 'error');
					disconnectBtn.disabled = false;
					disconnectBtn.textContent = kukieI18n('disconnectLabel', 'Disconnect from Kukie.io');
				}
			});
		}
	}

	// The Connection card is server-rendered from values stored at connect
	// time; /status carries the live plan name, organisation and domain, and
	// the PHP side mirrors them into the option on every fetch. Refresh the
	// visible cells too so the page never shows a stale plan.
	async function refreshConnectionCard() {
		const result = await kukieAjax('kukie_get_status');
		if (!result.success || !result.data) return;
		const d = result.data;
		if (d.plan?.name) setText('kukie-conn-plan', d.plan.name);
		if (d.organisation) setText('kukie-conn-org', d.organisation);
		if (d.domain) setText('kukie-conn-domain', d.domain);
	}

	async function loadSettingsData(form, loading, content) {
		const result = await kukieAjax('kukie_get_settings');

		if (loading) loading.hidden = true;

		// Reveal the form only after a successful load: unpopulated template
		// defaults here mean banner_enabled unchecked, so a blind Save would
		// disable the banner. The server-rendered connection card (with the
		// Disconnect button) stays reachable - only the settings form itself
		// is withheld.
		if (!result.success) {
			showNotice('kukie-settings-error', result.data?.message || kukieI18n('couldNotLoad', 'Could not load settings.'));
			form.style.display = 'none';
			if (content) content.hidden = false;
			return;
		}

		if (content) content.hidden = false;

		const d = result.data;
		rememberConfigVersion(d);

		// Banner enabled
		setChecked('kukie-banner-enabled', d.banner_enabled);

		// Script position
		const posRadio = form.querySelector(`input[name="script_position"][value="${d.script_position || 'head'}"]`);
		if (posRadio) posRadio.checked = true;

		// Show manual embed if position is manual
		const manualEmbed = document.getElementById('kukie-manual-embed');
		if (manualEmbed) manualEmbed.hidden = d.script_position !== 'manual';

		// Build embed code display.
		// Use the stored CDN bundle URL (embed_url) verbatim -- the same value the
		// automatic <head>/<body> injection enqueues. Never rebuild it from
		// dashboard_url, which is a per-site /sites/{id} dashboard link and would
		// produce an invalid script URL.
		const embedCodeEl = document.getElementById('kukie-embed-code-display');
		if (embedCodeEl) {
			const siteKey = document.querySelector('.kukie-info-value code')?.textContent || '';
			// Fall back to the CDN URL built from the UUID site key (with correct
			// /s/ and /c.js separators) only when embed_url is missing on a legacy
			// connection. Never fall back to dashboard_url string surgery.
			// CDN bundles carry their config in the URL path, so the snippet is
			// just the script src -- no data-site-key. This mirrors what the
			// automatic <head>/<body> injection emits.
			const embedUrl = kukieAdmin.embedUrl || (siteKey ? `https://cdn.kukie.io/s/${siteKey}/c.js` : '');
			if (embedUrl) {
				embedCodeEl.textContent = `<script src="${embedUrl}" async></script>`;
			}
		}

		// Verification status
		const verifiedStatusEl = document.getElementById('kukie-verified-status');
		if (verifiedStatusEl && d.verified_at) {
			verifiedStatusEl.textContent = kukieSprintf(kukieI18n('verifiedOn', 'Verified on %s'), formatDate(d.verified_at));
		}
	}

	// ─────────────────────────────────────────
	// LANGUAGE TAB (Consent banner page)
	// ─────────────────────────────────────────

	function initLanguagePage() {
		const form = document.getElementById('kukie-language-form');
		const loading = document.getElementById('kukie-language-loading');
		if (!form) return;

		loadLanguageSettings(form, loading);

		// Auto-translate toggle -> show/hide language options
		const autoTranslateToggle = document.getElementById('kukie-auto-translate');
		const langOptions = document.getElementById('kukie-language-options');
		if (autoTranslateToggle && langOptions) {
			autoTranslateToggle.addEventListener('change', () => {
				langOptions.hidden = !autoTranslateToggle.checked;
			});
		}

		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			hideNotice('kukie-language-error');

			const saveBtn = document.getElementById('kukie-language-save');
			setButtonLoading(saveBtn, true);

			const enabledLangs = [];
			form.querySelectorAll('input[name="enabled_languages[]"]:checked').forEach(cb => {
				enabledLangs.push(cb.value);
			});

			// has_enabled_languages: an empty checkbox list posts no array at
			// all; the marker tells the handler to send [] rather than leave
			// the server value untouched.
			const data = {
				force_language: form.querySelector('#kukie-force-language')?.value || 'auto',
				auto_translate: form.querySelector('#kukie-auto-translate')?.checked ? '1' : '0',
				default_language: form.querySelector('#kukie-default-language')?.value || 'en',
				enabled_languages: enabledLangs,
				has_enabled_languages: '1',
			};

			const result = await kukieSaveSettings('kukie_save_settings', data);

			setButtonLoading(saveBtn, false);

			if (result.success) {
				showToast(result.data.message);
			} else {
				showToast(result.data?.message || kukieI18n('failedToSave', 'Failed to save.'), 'error');
			}
		});
	}

	async function loadLanguageSettings(form, loading) {
		const result = await kukieAjax('kukie_get_settings');

		if (loading) loading.hidden = true;

		// Reveal the form only after a successful load: an unpopulated grid
		// posts an empty language list on Save.
		if (!result.success) {
			showNotice('kukie-language-error', result.data?.message || kukieI18n('couldNotLoad', 'Could not load settings.'));
			return;
		}

		form.hidden = false;

		const d = result.data;
		rememberConfigVersion(d);

		// Banner language override (WPML/Polylang dropdown)
		const forceLangSelect = document.getElementById('kukie-force-language');
		if (forceLangSelect) {
			forceLangSelect.value = d.force_language || 'auto';
		}

		setChecked('kukie-auto-translate', d.auto_translate);

		const langOptions = document.getElementById('kukie-language-options');
		if (langOptions) {
			langOptions.hidden = !d.auto_translate;
		}

		const langs = d.available_languages || [];
		const enabledLangs = d.enabled_languages || [];

		const langSelect = document.getElementById('kukie-default-language');
		if (langSelect && langs.length) {
			langSelect.replaceChildren();
			langs.forEach(lang => {
				const opt = document.createElement('option');
				opt.value = lang.locale;
				opt.textContent = lang.name;
				if (lang.locale === d.default_language) opt.selected = true;
				langSelect.appendChild(opt);
			});
		}

		const grid = document.getElementById('kukie-languages-grid');
		if (grid && langs.length) {
			grid.replaceChildren();
			langs.forEach(lang => {
				grid.appendChild(checkboxItem('enabled_languages[]', lang.locale, lang.name, enabledLangs.includes(lang.locale), lang.is_rtl));
			});
		}
	}

	// ─────────────────────────────────────────
	// BEHAVIOUR TAB (Consent banner page)
	// ─────────────────────────────────────────

	function initBehaviourPage() {
		const form = document.getElementById('kukie-behaviour-form');
		const loading = document.getElementById('kukie-behaviour-loading');
		if (!form) return;

		loadBehaviourSettings(form, loading);

		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			hideNotice('kukie-behaviour-error');

			const saveBtn = document.getElementById('kukie-behaviour-save');
			setButtonLoading(saveBtn, true);

			const flag = (id) => (document.getElementById(id)?.checked ? '1' : '0');
			const data = {
				show_branding: flag('kukie-show-branding'),
				auto_block_scripts: flag('kukie-auto-block'),
				respect_dnt: flag('kukie-respect-dnt'),
				respect_gpc: flag('kukie-respect-gpc'),
				reload_on_consent: flag('kukie-reload-on-consent'),
				show_overlay: flag('kukie-show-overlay'),
				disabled_pages: document.getElementById('kukie-disabled-pages')?.value || '',
			};

			const result = await kukieSaveSettings('kukie_save_behaviour', data);

			setButtonLoading(saveBtn, false);

			if (result.success) {
				showToast(result.data.message);
			} else {
				showToast(result.data?.message || kukieI18n('failedToSave', 'Failed to save.'), 'error');
			}
		});
	}

	async function loadBehaviourSettings(form, loading) {
		const result = await kukieAjax('kukie_get_settings');

		if (loading) loading.hidden = true;

		if (!result.success) {
			showNotice('kukie-behaviour-error', result.data?.message || kukieI18n('couldNotLoad', 'Could not load settings.'));
			return;
		}

		form.hidden = false;

		const d = result.data;
		rememberConfigVersion(d);

		setChecked('kukie-show-branding', d.show_branding !== false);
		setChecked('kukie-auto-block', d.auto_block_scripts);
		setChecked('kukie-respect-dnt', d.respect_dnt);
		setChecked('kukie-respect-gpc', d.respect_gpc);
		setChecked('kukie-reload-on-consent', d.reload_on_consent);
		setChecked('kukie-show-overlay', d.show_overlay !== false);
		setValue('kukie-disabled-pages', Array.isArray(d.disabled_pages) ? d.disabled_pages.join('\n') : '');

		// Branding removal is a plan feature: a plan that must keep branding
		// gets the toggle locked ON (the server forces it back anyway).
		const canRemove = d.can_remove_branding === true;
		const branding = document.getElementById('kukie-show-branding');
		const badge = document.getElementById('kukie-branding-locked');
		const row = document.getElementById('kukie-branding-row');
		if (branding) {
			branding.disabled = !canRemove;
			if (!canRemove) branding.checked = true;
		}
		if (badge) badge.hidden = canRemove;
		if (row) row.classList.toggle('kukie-form-row--locked', !canRemove);
	}

	// ─────────────────────────────────────────
	// IFRAME BLOCKING TAB (Consent banner page)
	// ─────────────────────────────────────────

	function initIframesPage() {
		const form = document.getElementById('kukie-iframes-form');
		const loading = document.getElementById('kukie-iframes-loading');
		if (!form) return;

		loadIframeSettings(form, loading);

		const toggle = document.getElementById('kukie-iframe-enabled');
		const wrap = document.getElementById('kukie-iframe-services-wrap');
		if (toggle && wrap) {
			toggle.addEventListener('change', () => { wrap.hidden = !toggle.checked; });
		}

		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			hideNotice('kukie-iframes-error');

			const saveBtn = document.getElementById('kukie-iframes-save');
			setButtonLoading(saveBtn, true);

			const data = {
				iframe_blocking_enabled: document.getElementById('kukie-iframe-enabled')?.checked ? '1' : '0',
				blocked_iframe_services: Array.from(document.querySelectorAll('input[name="blocked_iframe_services[]"]:checked')).map(cb => cb.value),
			};

			const result = await kukieSaveSettings('kukie_save_iframes', data);

			setButtonLoading(saveBtn, false);

			if (result.success) {
				showToast(result.data.message);
			} else {
				showToast(result.data?.message || kukieI18n('failedToSave', 'Failed to save.'), 'error');
			}
		});
	}

	async function loadIframeSettings(form, loading) {
		const result = await kukieAjax('kukie_get_settings');

		if (loading) loading.hidden = true;

		if (!result.success) {
			showNotice('kukie-iframes-error', result.data?.message || kukieI18n('couldNotLoad', 'Could not load settings.'));
			return;
		}

		form.hidden = false;

		const d = result.data;
		rememberConfigVersion(d);

		const enabled = d.iframe_blocking_enabled !== false;
		setChecked('kukie-iframe-enabled', enabled);
		const wrap = document.getElementById('kukie-iframe-services-wrap');
		if (wrap) wrap.hidden = !enabled;

		const grid = document.getElementById('kukie-iframe-services');
		const services = Array.isArray(d.available_iframe_services) ? d.available_iframe_services : [];
		const blocked = Array.isArray(d.blocked_iframe_services) ? d.blocked_iframe_services : [];
		if (grid) {
			grid.replaceChildren();
			services.forEach(svc => {
				grid.appendChild(checkboxItem('blocked_iframe_services[]', svc.id, svc.name, blocked.includes(svc.id), false));
			});
		}
	}

	// ─────────────────────────────────────────
	// DESIGN TAB (Consent banner page)
	// ─────────────────────────────────────────

	function initBannerDesignPage() {
		const loading = document.getElementById('kukie-design-loading');
		const content = document.getElementById('kukie-design-content');
		if (!content) return;

		loadBannerDesignData(loading, content);

		// Layout radio change -> update preview
		document.querySelectorAll('input[name="banner_layout"]').forEach(radio => {
			radio.addEventListener('change', updateBannerPreview);
		});

		// Position radio change -> update preview
		document.querySelectorAll('input[name="banner_position"]').forEach(radio => {
			radio.addEventListener('change', updateBannerPreview);
		});

		// Revisit button toggle -> show/hide fields
		const revisitToggle = document.getElementById('kukie-revisit-enabled');
		const revisitFields = document.getElementById('kukie-revisit-fields');
		if (revisitToggle && revisitFields) {
			revisitToggle.addEventListener('change', () => {
				revisitFields.hidden = !revisitToggle.checked;
			});
		}

		// Revisit color picker <-> text sync
		const colorPicker = document.getElementById('kukie-revisit-color-picker');
		const colorText = document.getElementById('kukie-revisit-color');
		if (colorPicker && colorText) {
			colorPicker.addEventListener('input', () => { colorText.value = colorPicker.value; });
			colorText.addEventListener('input', () => {
				if (/^#[0-9a-f]{6}$/i.test(colorText.value)) colorPicker.value = colorText.value;
			});
		}

		// Icon colour auto toggle
		const iconAutoCheckbox = document.getElementById('kukie-revisit-icon-auto');
		const iconColorGroup = document.getElementById('kukie-icon-color-group');
		const iconColorInput = document.getElementById('kukie-revisit-icon-color');
		const iconColorPicker = document.getElementById('kukie-revisit-icon-color-picker');

		if (iconAutoCheckbox) {
			iconAutoCheckbox.addEventListener('change', function () {
				iconColorGroup.hidden = this.checked;
				if (this.checked) {
					iconColorInput.value = '';
				} else if (!iconColorInput.value) {
					iconColorInput.value = '#ffffff';
					iconColorPicker.value = '#ffffff';
				}
				updateBannerPreview();
			});
		}

		// Icon colour picker <-> text sync
		if (iconColorPicker) {
			iconColorPicker.addEventListener('input', function () {
				iconColorInput.value = this.value;
				updateBannerPreview();
			});
		}
		if (iconColorInput) {
			iconColorInput.addEventListener('input', function () {
				if (/^#[0-9a-f]{6}$/i.test(this.value)) {
					iconColorPicker.value = this.value;
				}
				updateBannerPreview();
			});
		}

		// Save button
		const saveBtn = document.getElementById('kukie-design-save');
		if (saveBtn) {
			saveBtn.addEventListener('click', saveBannerDesign);
		}
	}

	async function loadBannerDesignData(loading, content) {
		const result = await kukieAjax('kukie_get_settings');

		// Reveal the form only after a successful load (see loadGcmSettings).
		// This tab has no persistent notice element, so on failure repurpose
		// the loading area to keep the message visible after the toast fades.
		// The Save button sits INSIDE the hidden content, so it cannot be
		// clicked while the form is withheld.
		if (!result.success) {
			const msg = result.data?.message || 'Could not load design settings.';
			showToast(msg, 'error');
			if (loading) {
				loading.textContent = msg;
				loading.setAttribute('role', 'alert');
			}
			const saveBtn = document.getElementById('kukie-design-save');
			if (saveBtn) saveBtn.disabled = true;
			return;
		}

		if (loading) loading.hidden = true;
		if (content) content.hidden = false;

		const d = result.data;
		rememberConfigVersion(d);

		// Set layout
		const layoutRadio = document.querySelector(`input[name="banner_layout"][value="${d.layout || 'popup'}"]`);
		if (layoutRadio) layoutRadio.checked = true;

		// Set position
		const posRadio = document.querySelector(`input[name="banner_position"][value="${d.position || 'bottom-left'}"]`);
		if (posRadio) posRadio.checked = true;

		// Set revisit button
		const rb = d.revisit_button || {};
		const revisitEnabled = rb.enabled !== false;
		setChecked('kukie-revisit-enabled', revisitEnabled);
		const revisitFields = document.getElementById('kukie-revisit-fields');
		if (revisitFields) revisitFields.hidden = !revisitEnabled;

		setValue('kukie-revisit-position', rb.position || 'bottom_left');
		setValue('kukie-revisit-style', rb.style || 'icon');
		setValue('kukie-revisit-icon', rb.icon || 'cookie');
		setValue('kukie-revisit-text', rb.text || 'Cookie Settings');
		setValue('kukie-revisit-color', rb.color || '');
		const colorPicker = document.getElementById('kukie-revisit-color-picker');
		if (colorPicker) colorPicker.value = rb.color || '#2563eb';

		// Icon colour
		const iconGroup = document.getElementById('kukie-icon-color-group');
		if (rb.icon_color) {
			setValue('kukie-revisit-icon-color', rb.icon_color);
			const iconPicker = document.getElementById('kukie-revisit-icon-color-picker');
			if (iconPicker) iconPicker.value = rb.icon_color;
			setChecked('kukie-revisit-icon-auto', false);
			if (iconGroup) iconGroup.hidden = false;
		} else {
			setValue('kukie-revisit-icon-color', '');
			setChecked('kukie-revisit-icon-auto', true);
			if (iconGroup) iconGroup.hidden = true;
		}

		setValue('kukie-revisit-offset-x', rb.offset_x ?? 20);
		setValue('kukie-revisit-offset-y', rb.offset_y ?? 20);

		updateBannerPreview();
	}

	async function saveBannerDesign() {
		const saveBtn = document.getElementById('kukie-design-save');
		if (!saveBtn) return;

		setButtonLoading(saveBtn, true);

		const layout = document.querySelector('input[name="banner_layout"]:checked')?.value || 'popup';
		const position = document.querySelector('input[name="banner_position"]:checked')?.value || 'bottom-left';

		const data = {
			layout,
			position,
			'revisit_button[enabled]': document.getElementById('kukie-revisit-enabled')?.checked ? '1' : '0',
			'revisit_button[position]': document.getElementById('kukie-revisit-position')?.value || 'bottom_left',
			'revisit_button[style]': document.getElementById('kukie-revisit-style')?.value || 'icon',
			'revisit_button[icon]': document.getElementById('kukie-revisit-icon')?.value || 'cookie',
			'revisit_button[text]': document.getElementById('kukie-revisit-text')?.value || 'Cookie Settings',
			'revisit_button[color]': document.getElementById('kukie-revisit-color')?.value || '',
			'revisit_button[icon_color]': document.getElementById('kukie-revisit-icon-color')?.value || '',
			'revisit_button[offset_x]': document.getElementById('kukie-revisit-offset-x')?.value || '20',
			'revisit_button[offset_y]': document.getElementById('kukie-revisit-offset-y')?.value || '20',
		};

		const result = await kukieSaveSettings('kukie_save_banner_design', data);

		setButtonLoading(saveBtn, false);

		if (result.success) {
			showToast(result.data.message);
		} else {
			showToast(result.data?.message || 'Failed to save design settings.', 'error');
		}
	}

	function updateBannerPreview() {
		const layout = document.querySelector('input[name="banner_layout"]:checked')?.value || 'popup';
		const position = document.querySelector('input[name="banner_position"]:checked')?.value || 'bottom-left';

		const page = document.getElementById('kukie-preview-page');
		if (page) {
			page.setAttribute('data-layout', layout);
			page.setAttribute('data-position', position);
		}

		// Show/hide position card - only relevant for floating layout
		const posCard = document.getElementById('kukie-position-card');
		if (posCard) {
			posCard.hidden = layout !== 'floating';
		}
	}

	// ─────────────────────────────────────────
	// ACCESSIBILITY WIDGET PAGE
	// ─────────────────────────────────────────

	// Reference data from the last successful load (module list, locale
	// list, the banner's enabled languages) - needed to rebuild the default
	// language options as the selection changes.
	let a11yRef = { modules: [], locales: [], bannerLanguages: [] };

	function initA11yPage() {
		const form = document.getElementById('kukie-a11y-form');
		if (!form) return;

		loadA11ySettings();

		// Colour: inherit checkbox <-> picker group
		const inherit = document.getElementById('kukie-a11y-color-inherit');
		const colorGroup = document.getElementById('kukie-a11y-color-group');
		const picker = document.getElementById('kukie-a11y-color-picker');
		const colorText = document.getElementById('kukie-a11y-color');
		if (inherit && colorGroup) {
			inherit.addEventListener('change', () => {
				colorGroup.hidden = inherit.checked;
				if (!inherit.checked && colorText && !colorText.value) {
					colorText.value = picker ? picker.value : '#2563eb';
				}
			});
		}
		if (picker && colorText) {
			picker.addEventListener('input', () => { colorText.value = picker.value; });
			colorText.addEventListener('input', () => {
				if (/^#[0-9a-f]{6}$/i.test(colorText.value)) picker.value = colorText.value;
			});
		}

		// Languages: custom selection toggle
		const custom = document.getElementById('kukie-a11y-langs-custom');
		const langsWrap = document.getElementById('kukie-a11y-langs-wrap');
		if (custom && langsWrap) {
			custom.addEventListener('change', () => {
				langsWrap.hidden = !custom.checked;
				renderA11yDefaultOptions();
			});
		}
		const langsGrid = document.getElementById('kukie-a11y-languages');
		if (langsGrid) {
			langsGrid.addEventListener('change', renderA11yDefaultOptions);
		}

		// Statement toggle -> URL field
		const stmt = document.getElementById('kukie-a11y-stmt-enabled');
		const stmtGroup = document.getElementById('kukie-a11y-stmt-url-group');
		if (stmt && stmtGroup) {
			stmt.addEventListener('change', () => { stmtGroup.hidden = !stmt.checked; });
		}

		// Locked state: re-check the plan after an upgrade
		const recheck = document.getElementById('kukie-a11y-recheck');
		if (recheck) {
			recheck.addEventListener('click', () => {
				setButtonLoading(recheck, true);
				loadA11ySettings().finally(() => setButtonLoading(recheck, false));
			});
		}

		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			hideNotice('kukie-a11y-error');

			const saveBtn = document.getElementById('kukie-a11y-save');
			setButtonLoading(saveBtn, true);

			const result = await kukieSaveSettings('kukie_save_a11y', collectA11yForm());

			setButtonLoading(saveBtn, false);

			if (result.success) {
				showToast(result.data.message);
			} else if (result.data?.code === 'plan_upgrade_required') {
				// The plan changed under us (or the cached state was stale):
				// show the locked state with the server's own message.
				renderA11yLocked(true, {
					required_plan: result.data.required_plan || '',
					upgrade_url: result.data.upgrade_url || '',
					enabled: document.getElementById('kukie-a11y-enabled')?.checked,
				}, result.data.message);
				showToast(result.data.message, 'error');
			} else {
				showToast(result.data?.message || kukieI18n('failedToSave', 'Failed to save.'), 'error');
			}
		});
	}

	async function loadA11ySettings() {
		const loading = document.getElementById('kukie-a11y-loading');
		const content = document.getElementById('kukie-a11y-content');

		// fresh=1: never serve the locked/unlocked verdict from the 10-minute
		// settings cache - the plan flag is the one input that changes on the
		// Kukie.io side while this page is open.
		const result = await kukieAjax('kukie_get_settings', { fresh: '1' });

		if (loading) loading.hidden = true;

		if (!result.success) {
			showNotice('kukie-a11y-error', result.data?.message || kukieI18n('couldNotLoad', 'Could not load settings.'));
			return;
		}

		const d = result.data;
		const a = d.accessibility_widget;

		// Service answered without the block (pre-1.8.0 API) - there is
		// nothing safe to render, and nothing safe to save.
		if (!a || typeof a !== 'object') {
			showNotice('kukie-a11y-error', kukieI18n('a11yNoBlock', 'The Kukie.io service did not return accessibility widget settings. Please try again in a few minutes.'));
			return;
		}

		rememberConfigVersion(d);

		a11yRef = {
			modules: Array.isArray(a.modules) ? a.modules : [],
			locales: Array.isArray(a.available_locales) ? a.available_locales : [],
			bannerLanguages: Array.isArray(d.enabled_languages) ? d.enabled_languages : [],
		};

		populateA11yForm(a);
		if (content) content.hidden = false;

		renderA11yLocked(!a.available, a, null);
	}

	function populateA11yForm(a) {
		// Effective state: a plan that lost the feature reads OFF (the stored
		// value is kept server-side for a re-upgrade; nothing is delivered).
		setChecked('kukie-a11y-enabled', a.available !== false && a.enabled);

		const posRadio = document.querySelector(`input[name="kukie_a11y_position"][value="${a.position || 'bottom-right'}"]`);
		if (posRadio) posRadio.checked = true;

		setValue('kukie-a11y-size', String(a.size || 44));
		setChecked('kukie-a11y-hide-mobile', a.hide_mobile);

		// Colour
		const themePrimary = a.banner_theme_primary || '#2563eb';
		const swatch = document.getElementById('kukie-a11y-theme-swatch');
		if (swatch) swatch.style.background = themePrimary;
		setChecked('kukie-a11y-color-inherit', !a.color);
		const colorGroup = document.getElementById('kukie-a11y-color-group');
		if (colorGroup) colorGroup.hidden = !a.color;
		setValue('kukie-a11y-color', a.color || '');
		const picker = document.getElementById('kukie-a11y-color-picker');
		if (picker) picker.value = a.color || themePrimary;

		// Modules (checked = visible; the server stores the HIDDEN list)
		const hidden = Array.isArray(a.hidden_modules) ? a.hidden_modules : [];
		const modulesGrid = document.getElementById('kukie-a11y-modules');
		const sectionsGrid = document.getElementById('kukie-a11y-sections');
		if (modulesGrid) modulesGrid.replaceChildren();
		if (sectionsGrid) sectionsGrid.replaceChildren();
		a11yRef.modules.forEach(m => {
			const target = m.group ? sectionsGrid : modulesGrid;
			if (!target) return;
			target.appendChild(checkboxItem('kukie_a11y_modules[]', m.key, m.label, !hidden.includes(m.key), false));
		});

		// Languages
		const selection = Array.isArray(a.languages) ? a.languages : [];
		setChecked('kukie-a11y-langs-custom', selection.length > 0);
		const langsWrap = document.getElementById('kukie-a11y-langs-wrap');
		if (langsWrap) langsWrap.hidden = selection.length === 0;
		const langsGrid = document.getElementById('kukie-a11y-languages');
		if (langsGrid) {
			langsGrid.replaceChildren();
			a11yRef.locales.forEach(l => {
				langsGrid.appendChild(checkboxItem('kukie_a11y_languages[]', l.code, l.name, selection.includes(l.code), false));
			});
		}
		renderA11yDefaultOptions(a.default_language || '');
		updateGridCount('kukie-a11y-modules');
		updateGridCount('kukie-a11y-languages');

		// Statement link
		setChecked('kukie-a11y-stmt-enabled', a.statement_enabled !== false);
		const stmtGroup = document.getElementById('kukie-a11y-stmt-url-group');
		if (stmtGroup) stmtGroup.hidden = a.statement_enabled === false;
		const stmtUrl = document.getElementById('kukie-a11y-stmt-url');
		if (stmtUrl) {
			stmtUrl.value = a.statement_url || '';
			stmtUrl.placeholder = a.statement_published_url || 'https://';
		}
	}

	// Effective language set = the custom selection (+ en, always embedded as
	// the fallback) or, without one, the banner's languages + en. Keeps the
	// current choice when it is still valid, otherwise falls back to auto.
	function renderA11yDefaultOptions(preferred) {
		const select = document.getElementById('kukie-a11y-default-language');
		if (!select) return;

		const keep = typeof preferred === 'string' ? preferred : select.value;
		const custom = document.getElementById('kukie-a11y-langs-custom')?.checked;
		let codes;
		if (custom) {
			codes = Array.from(document.querySelectorAll('input[name="kukie_a11y_languages[]"]:checked')).map(cb => cb.value);
		} else {
			codes = a11yRef.bannerLanguages.slice();
		}
		if (!codes.includes('en')) codes.push('en');

		const names = {};
		a11yRef.locales.forEach(l => { names[l.code] = l.name; });

		select.replaceChildren();
		const auto = document.createElement('option');
		auto.value = '';
		auto.textContent = kukieI18n('autoDetect', 'Auto-detect (recommended)');
		select.appendChild(auto);
		codes.forEach(code => {
			const opt = document.createElement('option');
			opt.value = code;
			opt.textContent = names[code] || code.toUpperCase();
			select.appendChild(opt);
		});
		select.value = codes.includes(keep) ? keep : '';
	}

	function renderA11yLocked(locked, a, messageOverride) {
		const lockedCard = document.getElementById('kukie-a11y-locked');
		const intro = document.getElementById('kukie-a11y-intro');
		const fields = document.getElementById('kukie-a11y-fields');
		const form = document.getElementById('kukie-a11y-form');
		const saveBtn = document.getElementById('kukie-a11y-save');

		if (lockedCard) lockedCard.hidden = !locked;
		if (intro) intro.hidden = locked;
		if (fields) fields.disabled = locked;
		if (form) form.classList.toggle('kukie-locked', locked);
		if (saveBtn) saveBtn.disabled = locked;

		if (!locked) return;

		const plan = a && a.required_plan ? String(a.required_plan) : '';
		const text = messageOverride || (plan
			? kukieSprintf(kukieI18n('a11yRequiredPlan', 'The accessibility widget is available on the %s plan and above.'), plan)
			: kukieI18n('a11yNotIncluded', 'The accessibility widget is not included in your plan.'));
		setText('kukie-a11y-locked-text', text);

		const upgrade = document.getElementById('kukie-a11y-upgrade');
		if (upgrade) {
			const url = (a && a.upgrade_url) || kukieAdmin.billingUrl || 'https://app.kukie.io/billing';
			if (/^https:\/\//.test(url)) upgrade.href = url;
		}

		const stillOn = document.getElementById('kukie-a11y-still-on');
		if (stillOn) {
			stillOn.hidden = !(a && a.enabled);
			stillOn.textContent = kukieI18n('a11yStillOn', 'This site still has the widget switched on from an earlier plan. Visitors do not see it until the plan includes it again; the setting is kept so nothing needs re-doing after an upgrade.');
		}
	}

	function collectA11yForm() {
		const inherit = document.getElementById('kukie-a11y-color-inherit')?.checked;
		const custom = document.getElementById('kukie-a11y-langs-custom')?.checked;

		// Unticked module = hidden.
		const hiddenModules = Array.from(document.querySelectorAll('input[name="kukie_a11y_modules[]"]'))
			.filter(cb => !cb.checked)
			.map(cb => cb.value);

		const languages = custom
			? Array.from(document.querySelectorAll('input[name="kukie_a11y_languages[]"]:checked')).map(cb => cb.value)
			: [];

		return {
			enabled: document.getElementById('kukie-a11y-enabled')?.checked ? '1' : '0',
			position: document.querySelector('input[name="kukie_a11y_position"]:checked')?.value || 'bottom-right',
			color: inherit ? '' : (document.getElementById('kukie-a11y-color')?.value || ''),
			size: document.getElementById('kukie-a11y-size')?.value || '44',
			hide_mobile: document.getElementById('kukie-a11y-hide-mobile')?.checked ? '1' : '0',
			hidden_modules: hiddenModules,
			statement_enabled: document.getElementById('kukie-a11y-stmt-enabled')?.checked ? '1' : '0',
			statement_url: document.getElementById('kukie-a11y-stmt-url')?.value.trim() || '',
			languages,
			default_language: document.getElementById('kukie-a11y-default-language')?.value || '',
		};
	}

	// ─────────────────────────────────────────
	// CHECKBOX GRID TOOLS (select all / clear / "n of m" count)
	// ─────────────────────────────────────────

	function updateGridCount(gridId) {
		const grid = document.getElementById(gridId);
		const out = document.querySelector(`[data-kukie-count="${gridId}"]`);
		if (!grid || !out) return;
		const all = grid.querySelectorAll('input[type="checkbox"]');
		const on = grid.querySelectorAll('input[type="checkbox"]:checked');
		out.textContent = all.length ? `${on.length} / ${all.length}` : '';
	}

	function initGridTools() {
		document.querySelectorAll('[data-kukie-check]').forEach(btn => {
			btn.addEventListener('click', () => {
				const grid = document.getElementById(btn.dataset.kukieGrid);
				if (!grid) return;
				const checked = btn.dataset.kukieCheck === 'all';
				grid.querySelectorAll('input[type="checkbox"]').forEach(cb => { cb.checked = checked; });
				grid.dispatchEvent(new Event('change', { bubbles: true }));
				updateGridCount(btn.dataset.kukieGrid);
			});
		});
		document.querySelectorAll('.kukie-checkbox-grid').forEach(grid => {
			grid.addEventListener('change', () => updateGridCount(grid.id));
		});
	}

	// ─────────────────────────────────────────
	// HELPERS
	// ─────────────────────────────────────────

	function checkboxItem(name, value, labelText, checked, rtl) {
		const label = document.createElement('label');
		label.className = 'kukie-checkbox-item';

		const checkbox = document.createElement('input');
		checkbox.type = 'checkbox';
		checkbox.name = name;
		checkbox.value = value;
		checkbox.checked = Boolean(checked);

		const span = document.createElement('span');
		span.textContent = labelText;
		if (rtl) span.setAttribute('dir', 'ltr');

		label.appendChild(checkbox);
		label.appendChild(span);
		return label;
	}

	function setText(id, text) {
		const el = document.getElementById(id);
		if (el) el.textContent = text;
	}

	function setValue(id, value) {
		const el = document.getElementById(id);
		if (el) el.value = value;
	}

	function setChecked(id, checked) {
		const el = document.getElementById(id);
		if (el) el.checked = Boolean(checked);
	}

	function capitalize(str) {
		if (!str) return '';
		return str.charAt(0).toUpperCase() + str.slice(1).replace(/_/g, ' ');
	}

	function formatDate(dateStr) {
		try {
			const d = new Date(dateStr);
			return d.toLocaleDateString(undefined, {
				year: 'numeric',
				month: 'short',
				day: 'numeric',
				hour: '2-digit',
				minute: '2-digit',
			});
		} catch {
			return dateStr;
		}
	}

	// ─────────────────────────────────────────
	// INIT
	// ─────────────────────────────────────────

	document.addEventListener('DOMContentLoaded', () => {
		initRocketNotice();
		initGridTools();

		// Detect which page we're on by looking for page-specific elements
		if (document.getElementById('kukie-connect-form')) {
			initConnectPage();
		}

		if (document.getElementById('kukie-overview-cards')) {
			initDashboardPage();
		}

		if (document.getElementById('kukie-design-content')) {
			initBannerDesignPage();
		}

		if (document.getElementById('kukie-gcm-form')) {
			initGcmPage();
		}

		if (document.getElementById('kukie-uet-form')) {
			initUetPage();
		}

		if (document.getElementById('kukie-settings-form')) {
			initSettingsPage();
		}

		if (document.getElementById('kukie-language-form')) {
			initLanguagePage();
		}

		if (document.getElementById('kukie-behaviour-form')) {
			initBehaviourPage();
		}

		if (document.getElementById('kukie-iframes-form')) {
			initIframesPage();
		}

		if (document.getElementById('kukie-a11y-form')) {
			initA11yPage();
		}
	});
})();
