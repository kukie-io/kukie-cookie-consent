/**
 * Kukie.io WordPress Plugin - Admin JS
 * Pure vanilla JS, no jQuery dependency.
 */

(function () {
	'use strict';

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
			return { success: false, data: { message: 'Network error. Please try again.' } };
		}
	}

	// ─────────────────────────────────────────
	// Toast Notifications
	// ─────────────────────────────────────────

	function showToast(message, type = 'success') {
		const existing = document.querySelector('.kukie-toast');
		if (existing) existing.remove();

		const toast = document.createElement('div');
		toast.className = `kukie-toast kukie-toast--${type}`;
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
		if (text) text.style.display = loading ? 'none' : '';
		if (loader) loader.style.display = loading ? 'inline-flex' : 'none';
		btn.disabled = loading;
	}

	// ─────────────────────────────────────────
	// Show/Hide Notice
	// ─────────────────────────────────────────

	function showNotice(id, message, type = 'error') {
		const el = document.getElementById(id);
		if (!el) return;
		el.textContent = message;
		el.className = `kukie-notice kukie-notice-${type}`;
		el.style.display = 'block';
	}

	function hideNotice(id) {
		const el = document.getElementById(id);
		if (el) el.style.display = 'none';
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
				form.style.display = 'none';
				const success = document.getElementById('kukie-connect-success');
				if (success) {
					success.style.display = 'block';
					setText('kukie-success-org', result.data.organisation);
					setText('kukie-success-plan', result.data.plan);
					setText('kukie-success-domain', result.data.domain);
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

	async function loadDashboardData() {
		const result = await kukieAjax('kukie_get_status');

		if (!result.success) {
			showNotice('kukie-dashboard-error', result.data?.message || 'Could not load dashboard data.');
			return;
		}

		hideNotice('kukie-dashboard-error');
		const d = result.data;

		// Banner status
		const bannerEnabled = d.banner_enabled;
		const bannerEl = document.getElementById('kukie-stat-banner');
		if (bannerEl) {
			bannerEl.innerHTML = bannerEnabled
				? '<span class="kukie-badge kukie-badge--active">Active</span>'
				: '<span class="kukie-badge kukie-badge--inactive">Inactive</span>';
		}

		// Consents today
		const today = d.consent_stats?.today;
		setText('kukie-stat-today', today
			? String((today.accept_all || 0) + (today.reject_all || 0) + (today.custom_consent || 0))
			: '0'
		);

		// Plan
		const planText = d.plan?.name || 'Free';
		const trialText = d.plan?.trial ? ` (Trial: ${d.plan.trial_days_remaining}d)` : '';
		setText('kukie-stat-plan', planText + trialText);

		// Verification
		const verifiedEl = document.getElementById('kukie-stat-verified');
		if (verifiedEl) {
			verifiedEl.innerHTML = d.script_verified
				? '<span class="kukie-badge kukie-badge--active">Verified</span>'
				: '<span class="kukie-badge kukie-badge--inactive">Not Verified</span>';
		}

		// Consent overview
		if (today) {
			const todayEl = document.getElementById('kukie-consent-today');
			if (todayEl) {
				todayEl.innerHTML =
					`<span class="kukie-consent-chip kukie-consent-chip--accept">Accepted: ${today.accept_all || 0}</span>` +
					`<span class="kukie-consent-chip kukie-consent-chip--reject">Rejected: ${today.reject_all || 0}</span>` +
					`<span class="kukie-consent-chip kukie-consent-chip--partial">Custom: ${today.custom_consent || 0}</span>`;
			}
		} else {
			setText('kukie-consent-today', 'No data yet');
		}

		setText('kukie-consent-week', d.consent_stats?.this_week?.total ?? '0');
		setText('kukie-consent-month', d.consent_stats?.this_month?.total ?? '0');

		// Scan info
		const scan = d.last_scan;
		if (scan) {
			const statusEl = document.getElementById('kukie-scan-status');
			if (statusEl) {
				const cls = scan.status === 'completed' ? 'active'
					: scan.status === 'running' ? 'running'
					: scan.status === 'failed' ? 'failed'
					: 'inactive';
				statusEl.innerHTML = `<span class="kukie-badge kukie-badge--${cls}">${capitalize(scan.status)}</span>`;
			}
			setText('kukie-scan-date', scan.date ? formatDate(scan.date) : 'N/A');
			setText('kukie-scan-cookies', String(scan.cookies_found ?? 0));
			setText('kukie-scan-pages', String(scan.pages_scanned ?? 0));
		} else {
			setText('kukie-scan-status', 'No scans yet');
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
	// GCM PAGE
	// ─────────────────────────────────────────

	function initGcmPage() {
		const form = document.getElementById('kukie-gcm-form');
		const loading = document.getElementById('kukie-gcm-loading');
		if (!form) return;

		loadGcmSettings(form, loading);

		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			hideNotice('kukie-gcm-error');
			hideNotice('kukie-gcm-success');

			const saveBtn = document.getElementById('kukie-gcm-save');
			setButtonLoading(saveBtn, true);

			const data = {
				gcm_v2_enabled: form.querySelector('#kukie-gcm-enabled')?.checked ? '1' : '0',
				auto_block_scripts: form.querySelector('#kukie-auto-block')?.checked ? '1' : '0',
			};

			const result = await kukieAjax('kukie_save_gcm', data);

			setButtonLoading(saveBtn, false);

			if (result.success) {
				showToast(result.data.message);
			} else {
				showToast(result.data?.message || 'Failed to save.', 'error');
			}
		});
	}

	async function loadGcmSettings(form, loading) {
		const result = await kukieAjax('kukie_get_settings');

		if (loading) loading.style.display = 'none';
		form.style.display = 'block';

		if (!result.success) {
			showNotice('kukie-gcm-error', result.data?.message || 'Could not load settings.');
			return;
		}

		const d = result.data;
		setChecked('kukie-gcm-enabled', d.gcm_v2_enabled);
		setChecked('kukie-auto-block', d.auto_block_scripts);
	}

	// ─────────────────────────────────────────
	// UET PAGE
	// ─────────────────────────────────────────

	function initUetPage() {
		const form = document.getElementById('kukie-uet-form');
		const loading = document.getElementById('kukie-uet-loading');
		if (!form) return;

		loadUetSettings(form, loading);

		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			hideNotice('kukie-uet-error');
			hideNotice('kukie-uet-success');

			const saveBtn = document.getElementById('kukie-uet-save');
			setButtonLoading(saveBtn, true);

			const data = {
				ms_uet_enabled: form.querySelector('#kukie-uet-enabled')?.checked ? '1' : '0',
			};

			const result = await kukieAjax('kukie_save_uet', data);

			setButtonLoading(saveBtn, false);

			if (result.success) {
				showToast(result.data.message);
			} else {
				showToast(result.data?.message || 'Failed to save.', 'error');
			}
		});
	}

	async function loadUetSettings(form, loading) {
		const result = await kukieAjax('kukie_get_settings');

		if (loading) loading.style.display = 'none';
		form.style.display = 'block';

		if (!result.success) {
			showNotice('kukie-uet-error', result.data?.message || 'Could not load settings.');
			return;
		}

		const d = result.data;
		setChecked('kukie-uet-enabled', d.ms_uet_enabled);
	}

	// ─────────────────────────────────────────
	// SETTINGS PAGE
	// ─────────────────────────────────────────

	function initSettingsPage() {
		const form = document.getElementById('kukie-settings-form');
		const loading = document.getElementById('kukie-settings-loading');
		const content = document.getElementById('kukie-settings-content');
		if (!form) return;

		loadSettingsData(form, loading, content);

		// Script position radio → show/hide manual embed
		form.querySelectorAll('input[name="script_position"]').forEach(radio => {
			radio.addEventListener('change', () => {
				const manualEmbed = document.getElementById('kukie-manual-embed');
				if (manualEmbed) {
					manualEmbed.style.display = radio.value === 'manual' && radio.checked ? 'block' : 'none';
				}
			});
		});

		// Auto-translate toggle → show/hide language options
		const autoTranslateToggle = document.getElementById('kukie-auto-translate');
		const langOptions = document.getElementById('kukie-language-options');
		if (autoTranslateToggle && langOptions) {
			autoTranslateToggle.addEventListener('change', () => {
				langOptions.style.display = autoTranslateToggle.checked ? 'block' : 'none';
			});
		}

		// Save
		form.addEventListener('submit', async (e) => {
			e.preventDefault();
			hideNotice('kukie-settings-error');
			hideNotice('kukie-settings-success');

			const saveBtn = document.getElementById('kukie-settings-save');
			setButtonLoading(saveBtn, true);

			const enabledLangs = [];
			form.querySelectorAll('input[name="enabled_languages[]"]:checked').forEach(cb => {
				enabledLangs.push(cb.value);
			});

			const data = {
				banner_enabled: form.querySelector('#kukie-banner-enabled')?.checked ? '1' : '0',
				script_position: form.querySelector('input[name="script_position"]:checked')?.value || 'head',
				force_language: form.querySelector('#kukie-force-language')?.value || 'auto',
				auto_translate: form.querySelector('#kukie-auto-translate')?.checked ? '1' : '0',
				default_language: form.querySelector('#kukie-default-language')?.value || 'en',
				enabled_languages: enabledLangs,
			};

			const result = await kukieAjax('kukie_save_settings', data);

			setButtonLoading(saveBtn, false);

			if (result.success) {
				showToast(result.data.message);
			} else {
				showToast(result.data?.message || 'Failed to save.', 'error');
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
					showToast('Banner script verified on your site!');
					if (statusEl) statusEl.textContent = 'Verified! Banner script detected.';
				} else {
					showToast(result.data?.message || 'Banner script not found.', 'error');
					if (statusEl) statusEl.textContent = result.data?.message || 'Not verified.';
				}
			});
		}

		// Disconnect button
		const disconnectBtn = document.getElementById('kukie-disconnect-btn');
		if (disconnectBtn) {
			disconnectBtn.addEventListener('click', async () => {
				if (!confirm('Are you sure you want to disconnect from Kukie.io? The cookie consent banner will be removed from your site.')) {
					return;
				}

				disconnectBtn.disabled = true;
				disconnectBtn.textContent = 'Disconnecting...';

				const result = await kukieAjax('kukie_disconnect');

				if (result.success) {
					showToast(result.data.message);
					if (result.data.redirect) {
						window.location.href = result.data.redirect;
					}
				} else {
					showToast(result.data?.message || 'Failed to disconnect.', 'error');
					disconnectBtn.disabled = false;
					disconnectBtn.textContent = 'Disconnect from Kukie.io';
				}
			});
		}
	}

	async function loadSettingsData(form, loading, content) {
		const result = await kukieAjax('kukie_get_settings');

		if (loading) loading.style.display = 'none';
		if (content) content.style.display = 'block';

		if (!result.success) {
			showNotice('kukie-settings-error', result.data?.message || 'Could not load settings.');
			return;
		}

		const d = result.data;

		// Banner enabled
		setChecked('kukie-banner-enabled', d.banner_enabled);

		// Script position
		const posRadio = form.querySelector(`input[name="script_position"][value="${d.script_position || 'head'}"]`);
		if (posRadio) posRadio.checked = true;

		// Show manual embed if position is manual
		if (d.script_position === 'manual') {
			const manualEmbed = document.getElementById('kukie-manual-embed');
			if (manualEmbed) manualEmbed.style.display = 'block';
		}

		// Build embed code display
		const embedCodeEl = document.getElementById('kukie-embed-code-display');
		if (embedCodeEl && kukieAdmin.dashboardUrl) {
			// Approximate the embed code - it depends on whether CDN is used
			const siteKey = document.querySelector('.kukie-info-value code')?.textContent || '';
			embedCodeEl.textContent = `<script src="${kukieAdmin.dashboardUrl.replace('sites/', '')}c.js" data-site-key="${siteKey}" async></script>`;
		}

		// Verification status
		const verifiedStatusEl = document.getElementById('kukie-verified-status');
		if (verifiedStatusEl && d.verified_at) {
			verifiedStatusEl.textContent = `Verified on ${formatDate(d.verified_at)}`;
		}

		// Banner language override (WPML/Polylang dropdown)
		const forceLangSelect = document.getElementById('kukie-force-language');
		if (forceLangSelect) {
			forceLangSelect.value = d.force_language || 'auto';
		}

		// Auto-translate
		setChecked('kukie-auto-translate', d.auto_translate);

		// Show/hide language options based on auto-translate state
		const langOptions = document.getElementById('kukie-language-options');
		if (langOptions) {
			langOptions.style.display = d.auto_translate ? 'block' : 'none';
		}

		// Populate language dropdown + checkboxes
		const langs = d.available_languages || [];
		const enabledLangs = d.enabled_languages || [];

		// Default language dropdown
		const langSelect = document.getElementById('kukie-default-language');
		if (langSelect && langs.length) {
			langSelect.innerHTML = '';
			langs.forEach(lang => {
				const opt = document.createElement('option');
				opt.value = lang.locale;
				opt.textContent = lang.name;
				if (lang.locale === d.default_language) opt.selected = true;
				langSelect.appendChild(opt);
			});
		}

		// Language checkboxes grid
		const grid = document.getElementById('kukie-languages-grid');
		if (grid && langs.length) {
			grid.innerHTML = '';
			langs.forEach(lang => {
				const label = document.createElement('label');
				label.className = 'kukie-checkbox-item';

				const checkbox = document.createElement('input');
				checkbox.type = 'checkbox';
				checkbox.name = 'enabled_languages[]';
				checkbox.value = lang.locale;
				checkbox.checked = enabledLangs.includes(lang.locale);

				const span = document.createElement('span');
				span.textContent = lang.name;
				if (lang.is_rtl) span.setAttribute('dir', 'ltr');

				label.appendChild(checkbox);
				label.appendChild(span);
				grid.appendChild(label);
			});
		}
	}

	// ─────────────────────────────────────────
	// BANNER DESIGN PAGE
	// ─────────────────────────────────────────

	function initBannerDesignPage() {
		const loading = document.getElementById('kukie-design-loading');
		const content = document.getElementById('kukie-design-content');
		if (!content) return;

		loadBannerDesignData(loading, content);

		// Layout radio change → update preview
		document.querySelectorAll('input[name="banner_layout"]').forEach(radio => {
			radio.addEventListener('change', updateBannerPreview);
		});

		// Position radio change → update preview
		document.querySelectorAll('input[name="banner_position"]').forEach(radio => {
			radio.addEventListener('change', updateBannerPreview);
		});

		// Revisit button toggle → show/hide fields
		const revisitToggle = document.getElementById('kukie-revisit-enabled');
		const revisitFields = document.getElementById('kukie-revisit-fields');
		if (revisitToggle && revisitFields) {
			revisitToggle.addEventListener('change', () => {
				revisitFields.style.display = revisitToggle.checked ? '' : 'none';
			});
		}

		// Revisit color picker ↔ text sync
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
				iconColorGroup.style.display = this.checked ? 'none' : '';
				if (this.checked) {
					iconColorInput.value = '';
				} else if (!iconColorInput.value) {
					iconColorInput.value = '#ffffff';
					iconColorPicker.value = '#ffffff';
				}
				updateBannerPreview();
			});
		}

		// Icon colour picker ↔ text sync
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

		if (loading) loading.style.display = 'none';
		if (content) content.style.display = 'block';

		if (!result.success) {
			showToast(result.data?.message || 'Could not load design settings.', 'error');
			return;
		}

		const d = result.data;

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
		if (revisitFields) revisitFields.style.display = revisitEnabled ? '' : 'none';

		setValue('kukie-revisit-position', rb.position || 'bottom_left');
		setValue('kukie-revisit-style', rb.style || 'icon');
		setValue('kukie-revisit-icon', rb.icon || 'cookie');
		setValue('kukie-revisit-text', rb.text || 'Cookie Settings');
		setValue('kukie-revisit-color', rb.color || '');
		const colorPicker = document.getElementById('kukie-revisit-color-picker');
		if (colorPicker) colorPicker.value = rb.color || '#2563eb';

		// Icon colour
		if (rb.icon_color) {
			setValue('kukie-revisit-icon-color', rb.icon_color);
			const iconPicker = document.getElementById('kukie-revisit-icon-color-picker');
			if (iconPicker) iconPicker.value = rb.icon_color;
			setChecked('kukie-revisit-icon-auto', false);
			const iconGroup = document.getElementById('kukie-icon-color-group');
			if (iconGroup) iconGroup.style.display = '';
		} else {
			setValue('kukie-revisit-icon-color', '');
			setChecked('kukie-revisit-icon-auto', true);
			const iconGroup = document.getElementById('kukie-icon-color-group');
			if (iconGroup) iconGroup.style.display = 'none';
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

		const result = await kukieAjax('kukie_save_banner_design', data);

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
			posCard.style.display = layout === 'floating' ? '' : 'none';
		}
	}

	// ─────────────────────────────────────────
	// HELPERS
	// ─────────────────────────────────────────

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
	});
})();
