import { spawnSync } from 'node:child_process';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { test, expect } from '@playwright/test';

const root = fileURLToPath(new URL('../../', import.meta.url));
const runtime = new URL('../../node_modules/.cache/aimatrik-e2e/', import.meta.url);
export const master = '/admin/master-data/domain';
export const assessmentPath = id => `/admin/daftar-penilaian/penilaian/${id}`;
const uiDiagnostics = new WeakMap();

export function fixture(action, id) {
    const started = Date.now();
    const result = spawnSync(process.env.PW_PHP || 'php', ['tests/e2e/fixtures.php', action, ...(id ? [id] : [])], {
        cwd: root, encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'],
    });
    console.info(`[E2E timing] fixture ${action}${id ? ` ${id}` : ''}: ${Date.now() - started} ms`);
    if (result.error || result.status !== 0) {
        if (result.stderr?.startsWith('E2E fixture isolation refused:')) throw new Error(result.stderr.trim());
        throw new Error('E2E fixture guard/setup failed; verify the dedicated testing environment. Diagnostics suppressed to protect credentials.');
    }
    try { return JSON.parse(result.stdout); } catch { throw new Error('E2E fixture returned invalid metadata.'); }
}

export async function prepare(page, baseURL, id) {
    const required = ['PW_SUPER_EMAIL', 'PW_SUPER_PASSWORD', 'PW_ADMIN_A_EMAIL', 'PW_ADMIN_A_PASSWORD', 'PW_ADMIN_B_EMAIL', 'PW_ADMIN_B_PASSWORD'];
    const missing = required.filter(key => !process.env[key]);
    const reason = process.env.PW_E2E !== '1' || process.env.APP_ENV !== 'testing' || process.env.DB_CONNECTION !== 'pgsql'
        || process.env.DB_DATABASE !== 'aimatrik_e2e' || missing.length
        ? `Phase 2 requires PW_E2E=1, APP_ENV=testing, DB_CONNECTION=pgsql, DB_DATABASE=aimatrik_e2e and fixture credentials; missing keys: ${missing.join(', ') || 'none'}` : '';
    if (reason) { console.info(`SKIP ${id}: ${reason}`); test.skip(true, reason); }
    const origin = new URL(baseURL);
    expect(origin.protocol).toBe('http:');
    expect(origin.hostname).toBe('127.0.0.1');
    const events = [];
    uiDiagnostics.set(page, events);
    const clean = value => {
        let text = String(value).replace(/https?:\/\/[^\s"'<>]+/g, url => {
            try { return new URL(url).origin + new URL(url).pathname; } catch { return '[URL]'; }
        });
        for (const [key, secret] of Object.entries(process.env)) {
            if (/PASSWORD|TOKEN|SECRET|APP_KEY|PW_.*EMAIL/i.test(key) && secret?.length >= 4) text = text.replaceAll(secret, '[redacted]');
        }
        return text.slice(0, 600);
    };
    const record = event => events.push({ time: Date.now(), ...event });
    page.on('pageerror', error => record({ pageerror: clean(error.message) }));
    page.on('console', message => { if (message.type() === 'error') record({ consoleError: clean(message.text()) }); });
    page.on('request', request => {
        if (['script', 'stylesheet'].includes(request.resourceType())) record({ requested: clean(request.url()) });
    });
    page.on('response', response => {
        if (['script', 'stylesheet'].includes(response.request().resourceType())) {
            record({ asset: clean(response.url()), status: response.status(), contentType: response.headers()['content-type'] });
        }
    });
    page.on('requestfailed', request => record({ failed: clean(request.url()), reason: clean(request.failure()?.errorText) }));
    const manifest = fixture('check');
    // Attest the HTTP server too: checking only the CLI database is insufficient.
    const response = await page.request.get('/login', { maxRedirects: 0 });
    const attested = response.headers()['x-aimatrik-e2e'] === manifest.run;
    const hostRejected = response.status() === 403 && origin.port !== '8000';
    if (!attested || hostRejected) {
        const message = 'E2E server attestation failed: check the guarded router, matching APP_URL, fixture run and host allowlist (8001 is not currently allowed). No mutation performed.';
        console.info(`SKIP ${id}: ${message}`);
        test.skip(true, message);
    }
    // An attested server with a broken login page must FAIL, not be hidden as SKIP.
    expect(response.status(), 'Guarded E2E login page must be accessible').toBe(200);
    return fixture('prepare', id);
}

export async function login(page, role = 'a', credentials) {
    const key = { super: 'SUPER', a: 'ADMIN_A', b: 'ADMIN_B' }[role];
    await page.context().clearCookies();
    await page.goto('/login', { waitUntil: 'domcontentloaded' });
    await page.getByLabel('Alamat email', { exact: true }).fill(credentials?.email ?? process.env[`PW_${key}_EMAIL`]);
    await page.getByLabel('Kata sandi', { exact: true }).fill(credentials?.password ?? process.env[`PW_${key}_PASSWORD`]);
    await page.getByRole('button', { name: 'Login', exact: true }).click();
    await expect(page).toHaveURL(/\/admin\/home\/?$/);
}

export async function notice(page, title = 'Berhasil!') {
    await page.waitForLoadState('domcontentloaded');
    await expect(page.locator('.swal2-title')).toHaveText(title);
    await page.locator('.swal2-confirm').click();
    await expect(page.locator('.swal2-title')).toBeHidden();
}

export async function choose(page, select, label) {
    // Select2 hides its native select. Use the visible widget, without force/JS changes.
    const nativeOption = select.getByText(label, { exact: true });
    await expect(nativeOption).toHaveCount(1);
    const value = await nativeOption.getAttribute('value');
    expect(value).not.toBeNull();
    const widget = select.locator('..').locator('.select2-selection');
    await widget.click();
    await expect(widget).toHaveAttribute('aria-expanded', 'true');
    const dropdown = page.locator('.select2-container--open .select2-dropdown');
    await expect(dropdown).toHaveCount(1);
    await expect(dropdown).toBeVisible();
    // This bundled Select2 uses treeitem roles. Match visible text in its active dropdown.
    const option = dropdown.getByText(label, { exact: true });
    await expect(option).toBeVisible();
    await option.click();
    await expect(select).toHaveValue(value);
    await expect(widget).toHaveAttribute('aria-expanded', 'false');
}

export async function saveModal(page, selector, values) {
    const modal = page.locator(`.modal${selector}`);
    await expect(modal).toHaveCount(1);
    await expect(modal).toBeVisible();
    for (const [name, value] of Object.entries(values)) await modal.locator(`[name="${name}"]`).fill(value);
    await modal.locator('[name="is_active"]').uncheck();
    const action = new URL(await modal.locator('form').getAttribute('action'), page.url()).href;
    const destination = page.url();
    const [response] = await Promise.all([
        page.waitForResponse(response => response.request().isNavigationRequest()
            && response.request().method() === 'POST' && response.url() === action),
        page.waitForEvent('domcontentloaded'),
        modal.locator('button[type="submit"]').click(),
    ]);
    expect(response.status()).toBe(302);
    expect(new URL(response.headers().location, action).href).toBe(destination);
    const redirected = response.request().redirectedTo();
    expect(redirected?.url()).toBe(destination);
    expect((await redirected.response()).status()).toBe(200);
    await expect(page).toHaveURL(destination);
    await notice(page);
}

export async function removeRow(page, row) {
    await row.locator('[title="Hapus Data"]').click();
    await expect(page.locator('.swal2-title')).toHaveText('Apakah Yakin?');
    await page.locator('.swal2-confirm').click();
    await notice(page);
}

export async function openUi(page, selector) {
    const targetSelector = selector.startsWith('#modal') ? `.modal${selector}` : selector;
    const button = page.locator(`[data-bs-target="${selector}"]`);
    const target = page.locator(targetSelector);
    const states = [];
    const snapshot = async stage => {
        try {
            states.push({ stage, time: Date.now(), ...await page.evaluate(({ selector, targetSelector }) => {
                const trigger = document.querySelector(`[data-bs-target="${selector}"]`);
                const targets = [...document.querySelectorAll(targetSelector)];
                const describe = element => {
                    if (!element) return null;
                    const rect = element.getBoundingClientRect();
                    const style = getComputedStyle(element);
                    return { tag: element.tagName, id: element.id, classes: element.className,
                        expanded: element.getAttribute('aria-expanded'), hidden: element.getAttribute('aria-hidden'),
                        toggle: element.getAttribute('data-bs-toggle'), target: element.getAttribute('data-bs-target'),
                        parent: element.getAttribute('data-bs-parent'), display: style.display, visibility: style.visibility,
                        pointerEvents: style.pointerEvents, rect: { x: rect.x, y: rect.y, width: rect.width, height: rect.height } };
                };
                const rect = trigger?.getBoundingClientRect();
                const parent = targets[0]?.getAttribute('data-bs-parent');
                return { path: location.pathname, readyState: document.readyState,
                    bootstrap: { modal: typeof window.bootstrap?.Modal, collapse: typeof window.bootstrap?.Collapse,
                        version: window.bootstrap?.Modal?.VERSION,
                        modalInstance: !!window.bootstrap?.Modal?.getInstance(targets[0]),
                        collapseInstance: !!window.bootstrap?.Collapse?.getInstance(targets[0]) },
                    trigger: describe(trigger), targetCount: targets.length, targets: targets.map(describe),
                    parentCount: parent ? document.querySelectorAll(parent).length : null,
                    hit: rect ? describe(document.elementFromPoint(rect.x + rect.width / 2, rect.y + rect.height / 2)) : null,
                    overlays: [...document.querySelectorAll('.modal-backdrop, .swal2-container')].map(describe) };
            }, { selector, targetSelector }) });
        } catch { states.push({ stage, unavailable: true }); }
    };
    try {
        await snapshot('before-ready');
        // Navigation can expose clickable markup before the footer Bootstrap script executes.
        await page.waitForLoadState('domcontentloaded');
        await snapshot('before-click');
        await expect(button).toHaveCount(1);
        await expect(target).toHaveCount(1);
        await button.click();
        await expect(target).toHaveClass(/\bshow\b/);
        await expect(target).toBeVisible();
    } catch (error) {
        await snapshot('after-failure');
        await test.info().attach('bootstrap-ui-diagnostics', {
            body: JSON.stringify({ selector, states, events: uiDiagnostics.get(page) || [] }, null, 2),
            contentType: 'application/json',
        });
        throw error;
    }
}

export async function openField(page, fieldId) {
    const panel = page.locator(`#collapse${fieldId}`);
    const parent = await panel.getAttribute('data-bs-parent');
    expect(parent).toMatch(/^#accordionSection-/);
    await expect(page.locator(parent)).toHaveCount(1);
    await expect(page.locator(parent).locator(`#collapse${fieldId}`)).toHaveCount(1);
    const button = page.locator(`[data-bs-target="#collapse${fieldId}"]`);
    if (await button.getAttribute('aria-expanded') !== 'true') await openUi(page, `#collapse${fieldId}`);
    await expect(panel).toHaveClass(/\bshow\b/);
}

export async function defineTarget(page, fieldId) {
    await openField(page, fieldId);
    await page.locator(`[data-id_level="${fieldId}"]`).click();
    const modal = page.locator('.modal#modalInputLevelTarget');
    await expect(modal).toBeVisible();
    await choose(page, modal.locator('[name="select_level_target"]'), 'Level - 0');
    await modal.locator('button[onclick="validateLevelTargetAndSubmit(this)"]').click();
    await notice(page);
    await expect(page.locator(`input[name="level_target[${fieldId}]"]`)).toHaveValue('0');
}

export async function answer(page, data, value = 'Fully Achieved') {
    const field = data.fields[0].id;
    await openField(page, field);
    for (const item of data.answers) {
        await choose(page, page.locator(`#praktik-${item.id} select`), value);
    }
}

export async function saveAnswers(page, data) {
    await page.locator(`#simpan-bidang-praktik-${data.fields[0].id}`).click();
    await notice(page);
}

export function pdf() {
    return { name: 'e2e-dummy.pdf', mimeType: 'application/pdf', buffer: readFileSync(new URL('dummy.pdf', runtime)) };
}

export async function mutate(page, path, fields, fileField) {
    const token = await page.locator('meta[name="csrf-token"]').getAttribute('content');
    const options = { maxRedirects: 0, headers: { Accept: 'application/json' } };
    const body = { _token: token, ...fields };
    if (fileField) options.multipart = { ...body, [fileField]: pdf() };
    else options.form = body;
    return page.request.post(path, options);
}

export function answerPayload(data, fieldId = data.fields[0].id) {
    return Object.fromEntries([
        [`level_target[${fieldId}]`, '0'],
        ...data.answers.map(item => [`pelaksanaan[${fieldId}][0][${item.id}]`, 'N']),
    ]);
}
