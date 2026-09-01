# Test harness

Dev-only. Every file here is `export-ignore`d in `.gitattributes`, so the
wordpress.org package built by `git archive` is byte-identical with or without
it. **Adding or changing a test needs no plugin release.**

## Running

```
composer install
vendor/bin/phpunit
```

No WordPress install, no database, no network. `tests/stubs/wordpress.php`
provides an in-memory option store, a canned-response queue for
`wp_remote_request()`, and a hook registry. That is enough because the
invariants below are pure functions of the `kukie_settings` option and the
HTTP outcome.

## What it covers, and why these paths

Four of the eight round-D findings in this plugin were the same root shape:
**local state diverging from server state across a failed operation.** That is
the shape eyeballing misses and a small state-machine suite catches, so the
harness is scoped to it rather than to line coverage.

| Test | Locks | Finding |
|---|---|---|
| `ApiKeyTrustTest` | only a client built from the STORED key may write `api_key_valid`, and only a 401 may revoke it | KUK-QA-2026-354 |
| `BannerInjectionGateTest` | injection gates on `site_key` alone - never on API-key state | KUK-QA-2026-354 |
| `BlindSaveGuardTest` | a settings page is not saveable after a failed load | KUK-QA-2026-358 |
| `SettingsSaveTest` | local-only fields commit only after the PUT succeeds; server error messages reach the user | KUK-QA-2026-385, 384 |
| `ConnectPlacementTest` | a same-site reconnect preserves a deliberate placement | KUK-QA-2026-386 |
| `ActivationAndMirrorTest` | the admin-bar mirror tracks dashboard-side toggles; the one-time connect redirect is reachable and fires once | KUK-QA-2026-387, 389 |
| `AccessibilityWidgetSaveTest` (1.8.0) | the Accessibility widget save coerces every value to the server whitelists, forwards the block unchanged, mirrors NOTHING locally, and turns the plan-gate 403 into a structured upgrade error | feature lock, no finding |
| `LegacyPageRedirectTest` (1.8.0) | the pre-1.8.0 page slugs redirect to their Cookie banner tab; the tab selector is whitelisted | feature lock, no finding |

`SettingsSaveTest` and `ApiKeyTrustTest` additionally cover the 1.7.2
concurrent-disconnect hardening: a response landing after another request
disconnected must never re-create the deleted option.

## Proving it is not tautological

The suite was written after the fixes shipped, so "it passes" proves nothing on
its own. Point `KUKIE_SRC` at the last release that still had the defects
(1.7.0) and the suite must go red:

```
git archive 5abca18 | tar -x -C /tmp/kukie-1.7.0
KUKIE_SRC=/tmp/kukie-1.7.0 vendor/bin/phpunit    # 22 failures
vendor/bin/phpunit                               # 50 passing
```

Measured 2026-07-31: 22 of 50 fail against 1.7.0, and **all eight findings are
represented** in that failing set. The two 1.8.0 files are FEATURE locks for
surfaces that did not exist before 1.8.0 - they error against 1.7.0 (missing
methods/constants), which is expected and proves nothing either way; the
regression argument above applies to the six finding-lock files. Re-run both halves after changing a test -
a test that cannot fail against 1.7.0 is not locking anything that was ever
broken, and should either be strengthened or dropped.

## Known limits

- **KUK-QA-2026-358's real fix is in `assets/js/admin.js` and runs in a
  browser.** `BlindSaveGuardTest` makes STATIC assertions that the guards exist
  and that all four save paths route through the guarded wrapper. That is
  materially weaker than the behavioural coverage the PHP paths get. A failure
  there is real; a pass is not proof the browser behaviour is correct.
- No coverage of the admin templates, the WP Consent API bridge, the caching
  plugin filters, or `Kukie_Encryption`'s legacy format migration.
- The stubs are not WordPress. They model option/transient semantics closely
  enough for these invariants and no further; anything depending on real
  WordPress behaviour (capabilities, nonces, the alloptions cache) is stubbed
  permissively and is not under test.
