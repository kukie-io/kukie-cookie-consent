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

	// %s and numbered %1$s / %2$s substitution (the same placeholders the
	// PHP strings use, so translators can reorder them).
	function kukieSprintf(template, ...values) {
		let next = 0;
		return String(template)
			.replace(/%(\d+)\$s/g, (_, n) => String(values[Number(n) - 1] ?? ''))
			.replace(/%s/g, () => String(values[next++] ?? ''));
	}

	// The admin user's WordPress language as a BCP 47 tag (en-GB, bg-BG),
	// so dates, numbers and relative times follow WordPress, not the
	// browser. Falls back to the browser default when the tag is unusable.
	const KUKIE_LOCALE = (() => {
		const tag = window.kukieAdmin && typeof kukieAdmin.locale === 'string' ? kukieAdmin.locale : '';
		try {
			if (tag) {
				new Intl.DateTimeFormat(tag);
				return tag;
			}
		} catch (e) {
			// fall through
		}
		return undefined;
	})();

	function formatNumber(n) {
		try {
			return new Intl.NumberFormat(KUKIE_LOCALE).format(Number(n) || 0);
		} catch (e) {
			return String(n);
		}
	}

	// Relative time ("2 minutes ago", "in 3 days"); Intl handles the plural
	// forms of every language, so no translated string has to.
	function relativeTime(iso) {
		const t = Date.parse(iso);
		if (Number.isNaN(t)) return '';
		const diff = (t - Date.now()) / 1000;
		const abs = Math.abs(diff);
		try {
			const rtf = new Intl.RelativeTimeFormat(KUKIE_LOCALE, { numeric: 'auto' });
			if (abs < 60) return rtf.format(Math.round(diff), 'second');
			if (abs < 3600) return rtf.format(Math.round(diff / 60), 'minute');
			if (abs < 86400) return rtf.format(Math.round(diff / 3600), 'hour');
			return rtf.format(Math.round(diff / 86400), 'day');
		} catch (e) {
			return formatDate(iso);
		}
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
			return { success: false, data: { message: kukieI18n('networkError', "Couldn't reach the server. Check your internet connection and try again.") } };
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
				data: { message: kukieI18n('saveDisabled', "Couldn't load the settings, so saving is turned off. Reload the page to try again.") },
			};
		}

		data.config_version = currentConfigVersion;

		let result = await kukieAjax(action, data);

		if (!result.success && result.data?.code === 'version_conflict') {
			// 0 is a legitimate version - never a truthiness check here.
			if (typeof result.data.current_version === 'number') {
				currentConfigVersion = result.data.current_version;
			}

			const choice = await kukieDialog({
				title: kukieI18n('conflictTitle', 'Replace changes made elsewhere?'),
				body: kukieI18n('conflictBody', 'These settings were changed in the Kukie.io dashboard or another window after you opened this page. Saving replaces those changes with yours. To see their changes instead, reload the page.'),
				cancelLabel: kukieI18n('cancel', 'Cancel'),
				secondaryLabel: kukieI18n('conflictReload', 'Reload page'),
				confirmLabel: kukieI18n('conflictOverwrite', 'Save my changes'),
			});

			if (choice === 'secondary') {
				window.location.reload();
				return { success: false, cancelled: true };
			}
			if (choice !== 'confirm') {
				return { success: false, cancelled: true };
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

	// Success and information only: an error never lives in a message that
	// disappears (it goes to the page's persistent notice, see showError).
	// One polite live region, created once, so screen readers announce every
	// toast; the toast stays while hovered or focused.
	function showToast(message) {
		let region = document.getElementById('kukie-toast-region');
		if (!region) {
			region = document.createElement('div');
			region.id = 'kukie-toast-region';
			region.className = 'kukie-toast-region';
			region.setAttribute('role', 'status');
			region.setAttribute('aria-live', 'polite');
			document.body.appendChild(region);
		}
		region.replaceChildren();

		const toast = document.createElement('div');
		toast.className = 'kukie-toast kukie-toast--success';
		toast.textContent = message;
		region.appendChild(toast);

		let remaining = 6000;
		let started = Date.now();
		let timer = setTimeout(dismiss, remaining);
		function dismiss() {
			toast.classList.add('kukie-toast--removing');
			setTimeout(() => toast.remove(), 300);
		}
		toast.addEventListener('mouseenter', () => { clearTimeout(timer); remaining -= Date.now() - started; });
		toast.addEventListener('mouseleave', () => { started = Date.now(); timer = setTimeout(dismiss, Math.max(remaining, 1500)); });
	}

	// Accessible confirmation dialog (native <dialog>: focus is trapped, Esc
	// cancels, focus returns to the opener). Resolves to 'confirm',
	// 'secondary' or 'cancel'. Initial focus is on Cancel, so Enter never
	// confirms a destructive action by accident.
	function kukieDialog({ title, body, confirmLabel, cancelLabel, secondaryLabel = '', destructive = false }) {
		if (typeof HTMLDialogElement !== 'function') {
			return Promise.resolve(window.confirm(`${title}\n\n${body}`) ? 'confirm' : 'cancel');
		}

		return new Promise((resolve) => {
			const opener = document.activeElement;
			const dlg = document.createElement('dialog');
			dlg.className = 'kukie-dialog';
			dlg.setAttribute('aria-labelledby', 'kukie-dialog-title');
			dlg.setAttribute('aria-describedby', 'kukie-dialog-body');

			const heading = document.createElement('h2');
			heading.id = 'kukie-dialog-title';
			heading.className = 'kukie-dialog-title';
			heading.textContent = title;

			const text = document.createElement('p');
			text.id = 'kukie-dialog-body';
			text.className = 'kukie-dialog-body';
			text.textContent = body;

			const actions = document.createElement('div');
			actions.className = 'kukie-dialog-actions';

			let settled = false;
			const close = (value) => {
				if (settled) return;
				settled = true;
				dlg.close();
				dlg.remove();
				if (opener && typeof opener.focus === 'function') opener.focus();
				resolve(value);
			};
			const button = (label, cls, value) => {
				const b = document.createElement('button');
				b.type = 'button';
				b.className = cls;
				b.textContent = label;
				b.addEventListener('click', () => close(value));
				return b;
			};

			const cancel = button(cancelLabel, 'kukie-btn-secondary', 'cancel');
			actions.appendChild(cancel);
			if (secondaryLabel) actions.appendChild(button(secondaryLabel, 'kukie-btn-secondary', 'secondary'));
			actions.appendChild(button(confirmLabel, destructive ? 'kukie-btn-danger kukie-btn-danger--solid' : 'kukie-btn-primary', 'confirm'));

			dlg.append(heading, text, actions);
			dlg.addEventListener('cancel', (e) => { e.preventDefault(); close('cancel'); });
			(document.querySelector('.kukie-wrap') || document.body).appendChild(dlg);
			dlg.showModal();
			cancel.focus();
		});
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

	// An error stays on the page until the next attempt: the notice has
	// role="alert", and it is scrolled into view when it sits off screen.
	function showError(id, message) {
		showNotice(id, message, 'error');
		const el = document.getElementById(id);
		if (el && typeof el.scrollIntoView === 'function') {
			const r = el.getBoundingClientRect();
			if (r.top < 0 || r.bottom > window.innerHeight) el.scrollIntoView({ block: 'center' });
		}
	}

	// The shared ending of every settings save: a cancelled conflict dialog
	// says nothing, success is a toast, failure a persistent notice.
	function handleSaveResult(result, noticeId) {
		if (result.cancelled) return false;
		if (result.success) {
			hideNotice(noticeId);
			showToast(result.data?.message || '');
			return true;
		}
		showError(noticeId, result.data?.message || kukieI18n('failedToSave', "Couldn't save the settings. Try again."));
		return false;
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
				toggleBtn.setAttribute('aria-label', isPassword ? kukieI18n('hideKey', 'Hide API key') : kukieI18n('showKey', 'Show API key'));
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
				showError('kukie-connect-error', kukieI18n('invalidKey', 'Paste the 64-character API key from your site on Kukie.io.'));
				input.setAttribute('aria-invalid', 'true');
				input.focus();
				return;
			}

			input.removeAttribute('aria-invalid');
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
				showError('kukie-connect-error', result.data?.message || kukieI18n('connectFailed', "Couldn't connect to Kukie.io. Check the API key and try again."));
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
	let scanPollTimer = null;
	let scanPollInFlight = false;
	let scanPollStarted = 0;

	const SCAN_POLL_MS = 5000;
	const SCAN_POLL_MAX_MS = 30 * 60 * 1000;

	function initDashboardPage() {
		const cards = document.getElementById('kukie-overview-cards');
		if (!cards) return;

		loadDashboardData();
		startDashboardRefresh();

		// A hidden tab polls nothing: the 60-second refresh and the scan
		// progress poll both stop, and resume (with an immediate refresh)
		// when the tab is visible again.
		document.addEventListener('visibilitychange', () => {
			if (document.hidden) {
				clearInterval(dashboardRefreshTimer);
				dashboardRefreshTimer = null;
				clearTimeout(scanPollTimer);
				scanPollTimer = null;
			} else {
				loadDashboardData();
				startDashboardRefresh();
			}
		});

		const scanBtn = document.getElementById('kukie-trigger-scan');
		if (scanBtn) {
			scanBtn.addEventListener('click', triggerScan);
		}
	}

	function startDashboardRefresh() {
		if (dashboardRefreshTimer) return;
		dashboardRefreshTimer = setInterval(loadDashboardData, 60000);
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

	const SCAN_STATUS = {
		pending: ['scanPending', 'Waiting to start', 'running'],
		running: ['scanRunning', 'Scanning', 'running'],
		completed: ['scanCompleted', 'Completed', 'active'],
		failed: ['scanFailed', 'Failed', 'failed'],
		cancelled: ['scanCancelled', 'Cancelled', 'inactive'],
	};

	function setScanStatus(status) {
		const known = SCAN_STATUS[status];
		if (known) {
			setBadge('kukie-scan-status', kukieI18n(known[0], known[1]), known[2]);
		} else {
			setBadge('kukie-scan-status', capitalize(status), 'inactive');
		}
	}

	async function loadDashboardData() {
		const result = await kukieAjax('kukie_get_status');

		if (!result.success) {
			// showNotice, not showError: this also runs on the 60 s background
			// refresh, which must never scroll the page under the reader.
			showNotice('kukie-dashboard-error', result.data?.message || kukieI18n('dashboardError', "Couldn't load the dashboard. Try again in a minute."));
			return;
		}

		hideNotice('kukie-dashboard-error');
		const d = result.data;

		// Banner status
		setBadge(
			'kukie-stat-banner',
			d.banner_enabled ? kukieI18n('on', 'On') : kukieI18n('off', 'Off'),
			d.banner_enabled ? 'active' : 'inactive'
		);

		// Consents today
		const today = d.consent_stats?.today;
		setText('kukie-stat-today', formatNumber(today
			? (today.accept_all || 0) + (today.reject_all || 0) + (today.custom_consent || 0)
			: 0
		));

		// Plan, with the trial's end date underneath while one runs.
		const planEl = document.getElementById('kukie-stat-plan');
		if (planEl) {
			const name = document.createElement('span');
			name.textContent = d.plan?.name || 'Free';
			const nodes = [name];
			if (d.plan?.trial) {
				const hint = document.createElement('span');
				hint.className = 'kukie-stat-hint';
				hint.textContent = d.plan.trial_ends_at
					? kukieSprintf(kukieI18n('trialEnds', 'Trial ends %s'), formatDay(d.plan.trial_ends_at))
					: kukieI18n('trial', 'Trial');
				nodes.push(hint);
			}
			planEl.replaceChildren(...nodes);
		}

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
				setBadge('kukie-stat-a11y', kukieI18n('on', 'On'), 'active');
			} else {
				setBadge('kukie-stat-a11y', kukieI18n('off', 'Off'), 'inactive');
			}
		} else {
			setText('kukie-stat-a11y', '-');
		}

		// Uptime monitoring (block on Kukie.io since 1 October 2026; an older
		// service answer leaves the card at "-").
		const up = d.uptime_monitoring;
		if (up && typeof up === 'object') {
			if (!up.available) {
				setBadge('kukie-stat-uptime', kukieI18n('notInPlan', 'Not in plan'), 'inactive');
			} else if (!up.enabled) {
				setBadge('kukie-stat-uptime', kukieI18n('off', 'Off'), 'inactive');
			} else {
				const [label, cls] = uptimeChip(up.status);
				setBadge('kukie-stat-uptime', label, cls);
			}
		} else {
			setText('kukie-stat-uptime', '-');
		}

		// Consent overview
		const todayEl = document.getElementById('kukie-consent-today');
		if (todayEl) {
			if (today) {
				const chip = (cls, label, n) => {
					const s = document.createElement('span');
					s.className = `kukie-consent-chip kukie-consent-chip--${cls}`;
					s.textContent = `${label}: ${formatNumber(n || 0)}`;
					return s;
				};
				todayEl.replaceChildren(
					chip('accept', kukieI18n('accepted', 'Accepted'), today.accept_all),
					chip('reject', kukieI18n('rejected', 'Rejected'), today.reject_all),
					chip('partial', kukieI18n('custom', 'Custom'), today.custom_consent)
				);
			} else {
				todayEl.textContent = kukieI18n('noDataYet', 'No consents yet');
			}
		}

		setText('kukie-consent-week', formatNumber(d.consent_stats?.this_week?.total ?? 0));
		setText('kukie-consent-month', formatNumber(d.consent_stats?.this_month?.total ?? 0));

		// Scan info
		const scan = d.last_scan;
		if (scan) {
			setScanStatus(scan.status);
			setText('kukie-scan-date', scan.date ? formatDate(scan.date) : '-');
			setText('kukie-scan-cookies', formatNumber(scan.cookies_found ?? 0));
			setText('kukie-scan-pages', formatNumber(scan.pages_scanned ?? 0));
			if (scan.status === 'pending' || scan.status === 'running') {
				startScanPolling();
			}
		} else {
			setText('kukie-scan-status', kukieI18n('noScansYet', 'No scans yet'));
			setText('kukie-scan-date', '-');
			setText('kukie-scan-cookies', '-');
			setText('kukie-scan-pages', '-');
		}
	}

	// Live scan progress: /status is cached for five minutes on the server
	// side of the plugin, so a running scan polls the uncached
	// kukie_scan_status every few seconds until it finishes, then reloads
	// the dashboard once (KUK-QA-2026-458).
	function startScanPolling() {
		// One chain only: a 60 s refresh landing while a poll request is in
		// flight (scanPollTimer is null then) must not start a second one.
		if (scanPollTimer || scanPollInFlight || document.hidden) return;
		if (!scanPollStarted) scanPollStarted = Date.now();
		scanPollTimer = setTimeout(pollScanStatus, SCAN_POLL_MS);
	}

	async function pollScanStatus() {
		scanPollTimer = null;
		if (Date.now() - scanPollStarted > SCAN_POLL_MAX_MS) {
			scanPollStarted = 0;
			return;
		}

		scanPollInFlight = true;
		const result = await kukieAjax('kukie_scan_status');
		scanPollInFlight = false;
		if (!result.success || !result.data) {
			startScanPolling();
			return;
		}

		const scan = result.data;
		const progress = document.getElementById('kukie-scan-progress');
		setScanStatus(scan.status);
		setText('kukie-scan-cookies', formatNumber(scan.cookies_found ?? 0));
		setText('kukie-scan-pages', formatNumber(scan.pages_scanned ?? 0));

		if (scan.status === 'pending' || scan.status === 'running') {
			if (progress) {
				progress.hidden = !(scan.total_pages > 0);
				progress.textContent = scan.total_pages > 0
					? kukieSprintf(kukieI18n('scanProgress', 'Scanning page %1$s of %2$s'), formatNumber(scan.pages_scanned), formatNumber(scan.total_pages))
					: '';
			}
			startScanPolling();
			return;
		}

		if (progress) progress.hidden = true;
		scanPollStarted = 0;
		loadDashboardData();
	}

	async function triggerScan() {
		const btn = document.getElementById('kukie-trigger-scan');
		if (!btn) return;

		setButtonLoading(btn, true);

		const result = await kukieAjax('kukie_trigger_scan');

		setButtonLoading(btn, false);

		if (result.success) {
			hideNotice('kukie-dashboard-error');
			showToast(result.data.message);
			setScanStatus('pending');
			scanPollStarted = 0;
			startScanPolling();
		} else {
			showError('kukie-dashboard-error', result.data?.message || kukieI18n('scanStartFailed', "Couldn't start the scan. Try again."));
		}
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

			handleSaveResult(result, 'kukie-gcm-error');
		});
	}

	async function loadGcmSettings(form, loading) {
		const result = await kukieAjax('kukie_get_settings');

		if (loading) loading.hidden = true;

		// Reveal the form only after a successful load: a revealed but
		// unpopulated form would post template defaults on Save.
		if (!result.success) {
			showError('kukie-gcm-error', result.data?.message || kukieI18n('couldNotLoad', "Couldn't load the settings. Reload the page to try again."));
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

			handleSaveResult(result, 'kukie-uet-error');
		});
	}

	async function loadUetSettings(form, loading) {
		const result = await kukieAjax('kukie_get_settings');

		if (loading) loading.hidden = true;

		// Reveal the form only after a successful load (see loadGcmSettings).
		if (!result.success) {
			showError('kukie-uet-error', result.data?.message || kukieI18n('couldNotLoad', "Couldn't load the settings. Reload the page to try again."));
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
				disable_for_admins: form.querySelector('#kukie-disable-for-admins')?.checked ? '1' : '0',
			};

			const result = await kukieSaveSettings('kukie_save_settings', data);

			setButtonLoading(saveBtn, false);

			handleSaveResult(result, 'kukie-settings-error');
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
					hideNotice('kukie-settings-error');
					if (statusEl) statusEl.textContent = kukieI18n('verifiedFound', 'Banner script found on your site.');
				} else {
					// Not found is guidance to act on (a firewall, a cache),
					// so it stays on the page as a warning.
					const message = result.data?.message || kukieI18n('notFound', "Couldn't find the banner script on your site.");
					showNotice('kukie-settings-error', message, 'warning');
					if (statusEl) statusEl.textContent = kukieI18n('notVerified', 'Not verified');
				}
			});
		}

		// Disconnect button
		const disconnectBtn = document.getElementById('kukie-disconnect-btn');
		if (disconnectBtn) {
			disconnectBtn.addEventListener('click', async () => {
				const choice = await kukieDialog({
					title: kukieI18n('disconnectTitle', 'Disconnect from Kukie.io?'),
					body: kukieI18n('disconnectBody', 'The consent banner disappears from this site straight away, and nothing is blocked until you connect again. Your settings, scans and consent records stay in your Kukie.io account.'),
					cancelLabel: kukieI18n('cancel', 'Cancel'),
					confirmLabel: kukieI18n('disconnectConfirm', 'Disconnect'),
					destructive: true,
				});
				if (choice !== 'confirm') {
					return;
				}

				disconnectBtn.disabled = true;
				disconnectBtn.textContent = kukieI18n('disconnecting', 'Disconnecting…');

				const result = await kukieAjax('kukie_disconnect');

				if (result.success) {
					showToast(result.data.message);
					if (result.data.redirect) {
						window.location.href = result.data.redirect;
					}
				} else {
					showError('kukie-settings-error', result.data?.message || kukieI18n('failedDisconnect', "Couldn't disconnect. Try again."));
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
			showError('kukie-settings-error', result.data?.message || kukieI18n('couldNotLoad', "Couldn't load the settings. Reload the page to try again."));
			form.style.display = 'none';
			if (content) content.hidden = false;
			return;
		}

		if (content) content.hidden = false;

		const d = result.data;
		rememberConfigVersion(d);

		// Banner enabled
		setChecked('kukie-banner-enabled', d.banner_enabled);

		// Hide for administrators (local-only, never comes from the API)
		setChecked('kukie-disable-for-admins', d.disable_for_admins);

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

			handleSaveResult(result, 'kukie-language-error');
		});
	}

	async function loadLanguageSettings(form, loading) {
		const result = await kukieAjax('kukie_get_settings');

		if (loading) loading.hidden = true;

		// Reveal the form only after a successful load: an unpopulated grid
		// posts an empty language list on Save.
		if (!result.success) {
			showError('kukie-language-error', result.data?.message || kukieI18n('couldNotLoad', "Couldn't load the settings. Reload the page to try again."));
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

			handleSaveResult(result, 'kukie-behaviour-error');
		});
	}

	async function loadBehaviourSettings(form, loading) {
		const result = await kukieAjax('kukie_get_settings');

		if (loading) loading.hidden = true;

		if (!result.success) {
			showError('kukie-behaviour-error', result.data?.message || kukieI18n('couldNotLoad', "Couldn't load the settings. Reload the page to try again."));
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

			handleSaveResult(result, 'kukie-iframes-error');
		});
	}

	async function loadIframeSettings(form, loading) {
		const result = await kukieAjax('kukie_get_settings');

		if (loading) loading.hidden = true;

		if (!result.success) {
			showError('kukie-iframes-error', result.data?.message || kukieI18n('couldNotLoad', "Couldn't load the settings. Reload the page to try again."));
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
		// The Save button sits INSIDE the hidden content, so it cannot be
		// clicked while the form is withheld.
		if (!result.success) {
			if (loading) loading.hidden = true;
			showError('kukie-design-error', result.data?.message || kukieI18n('couldNotLoad', "Couldn't load the settings. Reload the page to try again."));
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
		setValue('kukie-revisit-text', rb.text || 'Cookie settings');
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

		hideNotice('kukie-design-error');
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
			'revisit_button[text]': document.getElementById('kukie-revisit-text')?.value || 'Cookie settings',
			'revisit_button[color]': document.getElementById('kukie-revisit-color')?.value || '',
			'revisit_button[icon_color]': document.getElementById('kukie-revisit-icon-color')?.value || '',
			'revisit_button[offset_x]': document.getElementById('kukie-revisit-offset-x')?.value || '20',
			'revisit_button[offset_y]': document.getElementById('kukie-revisit-offset-y')?.value || '20',
		};

		const result = await kukieSaveSettings('kukie_save_banner_design', data);

		setButtonLoading(saveBtn, false);

		handleSaveResult(result, 'kukie-design-error');
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

			if (result.cancelled) {
				return;
			} else if (result.success) {
				hideNotice('kukie-a11y-error');
				showToast(result.data.message);
			} else if (result.data?.code === 'plan_upgrade_required') {
				// The plan changed under us (or the cached state was stale):
				// show the locked state with the server's own message.
				renderA11yLocked(true, {
					required_plan: result.data.required_plan || '',
					upgrade_url: result.data.upgrade_url || '',
					enabled: document.getElementById('kukie-a11y-enabled')?.checked,
				}, result.data.message);
				showError('kukie-a11y-error', result.data.message);
			} else {
				showError('kukie-a11y-error', result.data?.message || kukieI18n('failedToSave', "Couldn't save the settings. Try again."));
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
			showError('kukie-a11y-error', result.data?.message || kukieI18n('couldNotLoad', "Couldn't load the settings. Reload the page to try again."));
			return;
		}

		const d = result.data;
		const a = d.accessibility_widget;

		// Service answered without the block (pre-1.8.0 API) - there is
		// nothing safe to render, and nothing safe to save.
		if (!a || typeof a !== 'object') {
			showError('kukie-a11y-error', kukieI18n('a11yNoBlock', "Couldn't load the accessibility widget settings from Kukie.io. Try again in a few minutes."));
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
			? kukieSprintf(kukieI18n('a11yRequiredPlan', 'The accessibility widget is available on the %s plan and above. Upgrade to turn it on.'), plan)
			: kukieI18n('a11yNotIncluded', 'The accessibility widget is not included in your plan.'));
		setText('kukie-a11y-locked-text', text);

		const upgrade = document.getElementById('kukie-a11y-upgrade');
		if (upgrade) {
			const url = (a && a.upgrade_url) || kukieAdmin.billingUrl || 'https://app.kukie.io/billing';
			if (/^https:\/\//.test(url)) upgrade.href = url;
		}
		setText('kukie-a11y-upgrade-label', plan
			? kukieSprintf(kukieI18n('upgradeTo', 'Upgrade to %s'), plan)
			: kukieI18n('seePlans', 'See plans on Kukie.io'));

		const stillOn = document.getElementById('kukie-a11y-still-on');
		if (stillOn) {
			stillOn.hidden = !(a && a.enabled);
			stillOn.textContent = kukieI18n('a11yStillOn', 'This site still has the widget turned on from an earlier plan. Visitors do not see it until your plan includes it again. The setting is kept, so there is nothing to set up again after you upgrade.');
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
	// UPTIME MONITORING PAGE (1.9.0)
	// ─────────────────────────────────────────

	// The last payload from Kukie.io. Null until a load succeeds: the save
	// is refused before that, exactly like kukieSaveSettings() refuses a
	// blind save of the banner settings.
	let uptimeState = null;
	// The interval the form showed when it rendered: the save sends the
	// interval only when the user picked a different one, so a faster choice
	// stored before a downgrade (shown clamped to the plan) is not rewritten
	// by a save of some other field.
	let uptimeIntervalShown = null;

	const UPTIME_CAUSES = {
		timeout: ['causeTimeout', 'The site did not respond in time.'],
		connect: ['causeConnect', 'The connection could not be made (refused or dropped).'],
		dns: ['causeDns', 'The domain name could not be found (DNS).'],
		tls: ['causeTls', 'The TLS (SSL) handshake failed. The certificate may be expired or invalid.'],
		redirect: ['causeRedirect', 'The site redirected too many times.'],
		oversized: ['causeOversized', 'The page was larger than the 5 MB limit.'],
		blocked: ['causeBlocked', 'The address points to a private network and cannot be checked from outside.'],
		challenge: ['causeChallenge', 'The site answered with a bot-protection page instead of its content.'],
		transport: ['causeTransport', 'A network error interrupted the check.'],
	};

	function uptimeCause(cause, statusCode) {
		if (cause === 'http') {
			return statusCode
				? kukieSprintf(kukieI18n('causeHttp', 'The site answered with HTTP %s.'), statusCode)
				: kukieI18n('causeHttpGeneric', 'The site answered with an error status.');
		}
		const known = UPTIME_CAUSES[cause];
		return known ? kukieI18n(known[0], known[1]) : kukieI18n('causeUnknown', 'The site could not be reached.');
	}

	// [label, badge class] for a monitor status; shared with the dashboard card.
	function uptimeChip(status) {
		switch (status) {
			case 'up': return [kukieI18n('uptimeUp', 'Up'), 'active'];
			case 'down': return [kukieI18n('uptimeDown', 'Down'), 'failed'];
			case 'blocked': return [kukieI18n('uptimeBlocked', 'Blocked by bot protection'), 'warning'];
			default: return [kukieI18n('uptimePending', 'Waiting for the first check'), 'running'];
		}
	}

	// Whole phrases per value (no number glued to a plural noun); the English
	// fallbacks cover a stale cached script.
	const INTERVAL_FALLBACK = { 60: 'Every minute', 180: 'Every 3 minutes', 300: 'Every 5 minutes', 900: 'Every 15 minutes', 1800: 'Every 30 minutes', 3600: 'Every hour' };
	const HISTORY_FALLBACK = { 30: '30 days', 90: '90 days', 365: '1 year' };

	function intervalLabel(seconds) {
		return kukieI18n('interval' + seconds, INTERVAL_FALLBACK[seconds] || '')
			|| kukieSprintf(kukieI18n('intervalMinutes', 'Every %s minutes'), formatNumber(Math.round(seconds / 60)));
	}

	function historyLabel(days) {
		return kukieI18n('history' + days, HISTORY_FALLBACK[days] || '')
			|| kukieSprintf(kukieI18n('historyDays', '%s days'), formatNumber(days));
	}

	function formatDuration(seconds) {
		const sec = Math.max(0, Math.round(Number(seconds) || 0));
		if (sec < 60) return kukieSprintf(kukieI18n('durSeconds', '%s s'), sec);
		if (sec < 3600) return kukieSprintf(kukieI18n('durMinutes', '%s min'), Math.round(sec / 60));
		if (sec < 86400) return kukieSprintf(kukieI18n('durHours', '%1$s h %2$s min'), Math.floor(sec / 3600), Math.floor((sec % 3600) / 60));
		return kukieSprintf(kukieI18n('durDays', '%1$s d %2$s h'), Math.floor(sec / 86400), Math.floor((sec % 86400) / 3600));
	}

	function formatPercent(value) {
		if (value === null || value === undefined) return kukieI18n('noDataShort', 'No data yet');
		// Truncate, never round up: 99.996% must not read as 100% next to an
		// incident count.
		const truncated = Math.floor(Number(value) * 100) / 100;
		try {
			return new Intl.NumberFormat(KUKIE_LOCALE, { style: 'percent', maximumFractionDigits: 2 }).format(truncated / 100);
		} catch (e) {
			return `${value}%`;
		}
	}

	function initUptimePage() {
		const content = document.getElementById('kukie-uptime-content');
		if (!content) return;

		loadUptime();

		const recheck = document.getElementById('kukie-uptime-recheck');
		if (recheck) {
			recheck.addEventListener('click', () => {
				setButtonLoading(recheck, true);
				loadUptime().finally(() => setButtonLoading(recheck, false));
			});
		}

		const form = document.getElementById('kukie-uptime-form');
		if (form) {
			form.addEventListener('submit', (e) => {
				e.preventDefault();
				saveUptime();
			});
		}
	}

	async function loadUptime() {
		const loading = document.getElementById('kukie-uptime-loading');
		const content = document.getElementById('kukie-uptime-content');

		const result = await kukieAjax('kukie_get_uptime');

		if (loading) loading.hidden = true;

		if (!result.success || !result.data || typeof result.data !== 'object' || typeof result.data.available !== 'boolean') {
			showError('kukie-uptime-error', result.data?.message || kukieI18n('uptimeNoService', "Couldn't load uptime monitoring from Kukie.io. Try again in a few minutes."));
			return false;
		}

		hideNotice('kukie-uptime-error');
		renderUptime(result.data);
		if (content) content.hidden = false;
		return true;
	}

	function renderUptime(u) {
		uptimeState = u;
		const locked = !u.available || !u.settings;

		const lockedEl = document.getElementById('kukie-uptime-locked');
		const unlockedEl = document.getElementById('kukie-uptime-unlocked');
		if (lockedEl) lockedEl.hidden = !locked;
		if (unlockedEl) unlockedEl.hidden = locked;

		if (locked) {
			renderUptimeLocked(u);
			return;
		}

		renderUptimeStatus(u);
		renderUptimeSettings(u);
		renderUptimeIncidents(u);
		renderUptimeChecker(u);
	}

	function renderUptimeLocked(u, messageOverride) {
		const plan = u.required_plan ? String(u.required_plan) : '';
		setText('kukie-uptime-locked-text', messageOverride || (plan
			? kukieSprintf(kukieI18n('uptimeRequiredPlan', 'Uptime monitoring is available on the %s plan and above. Upgrade to turn it on.'), plan)
			: kukieI18n('uptimeNotIncluded', 'Uptime monitoring is not included in your plan.')));

		const upgrade = document.getElementById('kukie-uptime-upgrade');
		if (upgrade && typeof u.upgrade_url === 'string' && /^https:\/\//.test(u.upgrade_url)) {
			upgrade.href = u.upgrade_url;
		}
		setText('kukie-uptime-upgrade-label', plan
			? kukieSprintf(kukieI18n('upgradeTo', 'Upgrade to %s'), plan)
			: kukieI18n('seePlans', 'See plans on Kukie.io'));

		const rows = Array.isArray(u.plan_rows) ? u.plan_rows : [];
		const card = document.getElementById('kukie-uptime-plans-card');
		const body = document.getElementById('kukie-uptime-plans');
		if (card) card.hidden = rows.length === 0;
		if (body) {
			body.replaceChildren(...rows.map(row => {
				const tr = document.createElement('tr');
				[row.name, intervalLabel(row.interval_seconds), historyLabel(row.retention_days)].forEach((value, i) => {
					const cell = document.createElement(i === 0 ? 'th' : 'td');
					if (i === 0) cell.scope = 'row';
					cell.textContent = value;
					tr.appendChild(cell);
				});
				return tr;
			}));
		}
	}

	function renderUptimeStatus(u) {
		const settings = u.settings || {};
		const m = u.monitor;
		let state;
		let title;
		const body = [];

		if (!settings.enabled) {
			state = 'off';
			title = kukieI18n('uptimeOffTitle', 'Uptime monitoring is off');
			body.push(kukieI18n('uptimeOffBody', 'Turn on Monitor this site below to start checking it.'));
		} else if (m && m.paused_reason) {
			state = 'paused';
			title = kukieI18n('uptimePausedTitle', 'Uptime monitoring is paused');
			body.push(m.paused_reason === 'frozen'
				? kukieI18n('uptimeFrozenBody', 'Checks are paused because this site is frozen on Kukie.io.')
				: kukieI18n('uptimeBillingBody', 'Checks are paused until the subscription is active again.'));
		} else if (!m || m.status === 'pending' || !m.last_checked_at) {
			state = 'pending';
			title = kukieI18n('uptimePendingTitle', 'Waiting for the first check');
			body.push(kukieI18n('uptimePendingBody', 'The first check runs within a minute. Reload this page to see the result.'));
		} else if (m.status === 'down') {
			state = 'down';
			title = kukieI18n('uptimeDownTitle', 'Your site is down');
			if (m.status_changed_at) body.push(kukieSprintf(kukieI18n('uptimeDownSince', 'Went down %s'), relativeTime(m.status_changed_at)));
			body.push(uptimeCause(m.last_error, m.last_status_code));
		} else if (m.status === 'blocked') {
			state = 'blocked';
			title = kukieI18n('uptimeBlockedTitle', "Your site's bot protection is blocking the check");
			body.push(kukieI18n('uptimeBlockedBody', 'Visitors may be fine, but Kukie.io cannot see the page. Add the checker below to the allow list of your firewall or security plugin.'));
		} else {
			state = 'up';
			title = kukieI18n('uptimeUpTitle', 'Your site is up');
		}

		if (m && m.last_checked_at && (state === 'up' || state === 'down' || state === 'blocked')) {
			let line = kukieSprintf(kukieI18n('uptimeLastCheck', 'Last checked %s'), relativeTime(m.last_checked_at));
			if (state === 'up' && typeof m.last_response_ms === 'number') {
				line += ', ' + kukieSprintf(kukieI18n('ms', '%s ms'), formatNumber(m.last_response_ms));
			}
			body.push(line);
		}

		const statusCard = document.getElementById('kukie-uptime-status');
		if (statusCard) statusCard.dataset.state = state;
		setText('kukie-uptime-status-title', title);
		setText('kukie-uptime-status-body', body.join(' '));

		const stats = u.stats;
		const statsEl = document.getElementById('kukie-uptime-stats');
		if (statsEl) statsEl.hidden = !stats;
		if (stats) {
			setText('kukie-uptime-24h', formatPercent(stats.uptime_24h));
			setText('kukie-uptime-7d', formatPercent(stats.uptime_7d));
			setText('kukie-uptime-30d', formatPercent(stats.uptime_30d));
			setText('kukie-uptime-avg', typeof stats.avg_response_24h_ms === 'number'
				? kukieSprintf(kukieI18n('ms', '%s ms'), formatNumber(stats.avg_response_24h_ms))
				: kukieI18n('noDataShort', 'No data yet'));
			setText('kukie-uptime-incidents-30d', formatNumber(stats.incidents_30d ?? 0));

			const ssl = m && m.ssl ? m.ssl : {};
			const sslEl = document.getElementById('kukie-uptime-ssl');
			if (sslEl) {
				sslEl.textContent = ssl.expires_at
					? kukieSprintf(kukieI18n('sslExpires', 'Expires %s'), formatDay(ssl.expires_at))
					: kukieI18n('notAvailable', 'Not available');
				sslEl.classList.toggle('kukie-text-warning', typeof ssl.days_left === 'number' && ssl.days_left <= 14);
			}
		}
	}

	function renderUptimeSettings(u) {
		const settings = u.settings || {};
		setChecked('kukie-uptime-enabled', settings.enabled);
		setValue('kukie-uptime-url', settings.url || '');
		setChecked('kukie-uptime-notify', settings.notify_email !== false);
		setChecked('kukie-uptime-report', settings.report_enabled);

		const hosts = Array.isArray(settings.allowed_hosts) ? settings.allowed_hosts : [];
		setText('kukie-uptime-url-hint', hosts.length === 2
			? kukieSprintf(kukieI18n('uptimeUrlHint', 'Must be on %1$s or %2$s. Usually your homepage.'), hosts[0], hosts[1])
			: '');

		const count = Number(settings.alert_emails_count) || 0;
		setText('kukie-uptime-recipients', count > 0 ? formatNumber(count) : kukieI18n('none', 'None'));
		setText('kukie-uptime-webhook', settings.webhook_enabled ? kukieI18n('on', 'On') : kukieI18n('off', 'Off'));

		// One radio card per cadence. Slower than the plan's fastest is
		// selectable; a faster cadence is shown locked with the plan that
		// unlocks it (the server rejects it anyway).
		uptimeIntervalShown = null;
		const grid = document.getElementById('kukie-uptime-intervals');
		const interval = u.interval || {};
		const options = Array.isArray(interval.options) ? interval.options : [];
		if (grid) {
			grid.replaceChildren(...options.map(opt => {
				const label = document.createElement('label');
				label.className = 'kukie-interval-option' + (opt.available ? '' : ' kukie-interval-option--locked');

				const input = document.createElement('input');
				input.type = 'radio';
				input.name = 'kukie_uptime_interval';
				input.value = String(opt.seconds);
				input.checked = opt.seconds === interval.effective;
				if (input.checked) uptimeIntervalShown = String(opt.seconds);
				input.disabled = !opt.available;

				const title = document.createElement('span');
				title.className = 'kukie-interval-title';
				title.textContent = intervalLabel(opt.seconds);

				label.append(input, title);
				if (!opt.available && opt.plan_name) {
					const lock = document.createElement('span');
					lock.className = 'kukie-interval-lock';
					const icon = document.createElement('span');
					icon.className = 'dashicons dashicons-lock';
					icon.setAttribute('aria-hidden', 'true');
					lock.append(icon, document.createTextNode(kukieSprintf(kukieI18n('planAndAbove', '%s plan and above'), opt.plan_name)));
					label.appendChild(lock);
				}
				return label;
			}));
		}
	}

	function renderUptimeIncidents(u) {
		const incidents = Array.isArray(u.incidents) ? u.incidents : [];
		const empty = document.getElementById('kukie-uptime-incidents-empty');
		const table = document.getElementById('kukie-uptime-incidents-table');
		const body = document.getElementById('kukie-uptime-incidents');
		if (empty) empty.hidden = incidents.length > 0;
		if (table) table.hidden = incidents.length === 0;
		if (!body) return;

		body.replaceChildren(...incidents.map(incident => {
			const tr = document.createElement('tr');

			const started = document.createElement('td');
			started.textContent = incident.started_at ? formatDate(incident.started_at) : '-';

			const duration = document.createElement('td');
			if (incident.ongoing) {
				duration.appendChild(badge(kukieI18n('ongoing', 'Ongoing'), 'failed'));
			} else {
				duration.textContent = formatDuration(incident.duration_seconds);
			}

			const cause = document.createElement('td');
			cause.textContent = uptimeCause(incident.cause, incident.status_code);

			tr.append(started, duration, cause);
			return tr;
		}));
	}

	function renderUptimeChecker(u) {
		const checker = u.checker || {};
		setText('kukie-uptime-ipv4', checker.ipv4 || '-');
		setText('kukie-uptime-ipv6', checker.ipv6 || '-');
		setText('kukie-uptime-ua', checker.user_agent || '-');
	}

	async function saveUptime() {
		hideNotice('kukie-uptime-error');

		if (uptimeState === null) {
			showError('kukie-uptime-error', kukieI18n('saveDisabled', "Couldn't load the settings, so saving is turned off. Reload the page to try again."));
			return;
		}

		const urlField = document.getElementById('kukie-uptime-url');
		const url = urlField ? urlField.value.trim() : '';
		if (urlField && !/^https?:\/\/\S+$/i.test(url)) {
			urlField.setAttribute('aria-invalid', 'true');
			showError('kukie-uptime-error', kukieI18n('uptimeUrlInvalid', 'Enter a full web address that starts with https:// or http://.'));
			urlField.focus();
			return;
		}
		if (urlField) urlField.removeAttribute('aria-invalid');

		const data = {
			enabled: document.getElementById('kukie-uptime-enabled')?.checked ? '1' : '0',
			url,
			notify_email: document.getElementById('kukie-uptime-notify')?.checked ? '1' : '0',
			report_enabled: document.getElementById('kukie-uptime-report')?.checked ? '1' : '0',
		};
		const interval = document.querySelector('input[name="kukie_uptime_interval"]:checked');
		if (interval && interval.value !== uptimeIntervalShown) data.check_interval_seconds = interval.value;

		const saveBtn = document.getElementById('kukie-uptime-save');
		setButtonLoading(saveBtn, true);
		const result = await kukieAjax('kukie_save_uptime', data);
		setButtonLoading(saveBtn, false);

		if (result.success) {
			showToast(result.data?.message || kukieI18n('uptimeSaved', 'Uptime monitoring settings saved'));
			if (result.data?.uptime && typeof result.data.uptime.available === 'boolean') {
				renderUptime(result.data.uptime);
			}
			return;
		}

		if (result.data?.code === 'plan_upgrade_required') {
			// The plan changed on Kukie.io while this page was open.
			renderUptime({
				...uptimeState,
				available: false,
				required_plan: result.data.required_plan || '',
				upgrade_url: result.data.upgrade_url || '',
			});
		}
		showError('kukie-uptime-error', result.data?.message || kukieI18n('failedToSave', "Couldn't save the settings. Try again."));
	}

	// ─────────────────────────────────────────
	// COPY BUTTONS ([data-kukie-copy] = id of the element to copy)
	// ─────────────────────────────────────────

	function initCopyButtons() {
		document.querySelectorAll('[data-kukie-copy]').forEach(btn => {
			const restingLabel = btn.getAttribute('aria-label') || '';
			btn.addEventListener('click', async () => {
				const source = document.getElementById(btn.dataset.kukieCopy);
				const text = source ? source.textContent.trim() : '';
				if (!text || text === '-') return;
				try {
					await navigator.clipboard.writeText(text);
					btn.textContent = kukieI18n('copied', 'Copied');
					btn.setAttribute('aria-label', kukieI18n('copied', 'Copied'));
					setTimeout(() => {
						btn.textContent = kukieI18n('copy', 'Copy');
						if (restingLabel) btn.setAttribute('aria-label', restingLabel);
					}, 2000);
				} catch (e) {
					// No clipboard access (an http admin, a denied permission):
					// select the text so it can be copied by hand.
					const range = document.createRange();
					range.selectNodeContents(source);
					const selection = window.getSelection();
					selection.removeAllRanges();
					selection.addRange(range);
					showToast(kukieI18n('copyFailed', "Couldn't copy. Select the text and copy it yourself."));
				}
			});
		});
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
		out.textContent = all.length
			? kukieSprintf(kukieI18n('gridCount', '%1$s of %2$s selected'), formatNumber(on.length), formatNumber(all.length))
			: '';
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
		const d = new Date(dateStr);
		if (Number.isNaN(d.getTime())) return String(dateStr || '');
		try {
			return d.toLocaleString(KUKIE_LOCALE, {
				year: 'numeric',
				month: 'short',
				day: 'numeric',
				hour: '2-digit',
				minute: '2-digit',
			});
		} catch (e) {
			return d.toLocaleString();
		}
	}

	function formatDay(dateStr) {
		const d = new Date(dateStr);
		if (Number.isNaN(d.getTime())) return String(dateStr || '');
		try {
			return d.toLocaleDateString(KUKIE_LOCALE, { year: 'numeric', month: 'short', day: 'numeric' });
		} catch (e) {
			return d.toLocaleDateString();
		}
	}

	// ─────────────────────────────────────────
	// INIT
	// ─────────────────────────────────────────

	document.addEventListener('DOMContentLoaded', () => {
		initRocketNotice();
		initGridTools();
		initCopyButtons();

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

		if (document.getElementById('kukie-uptime-content')) {
			initUptimePage();
		}
	});
})();
