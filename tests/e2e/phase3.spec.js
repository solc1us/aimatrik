import { test, expect } from '@playwright/test';
import { prepare, fixture, login, notice, choose, openUi, mutate, assessmentPath } from './e2e-helpers.js';

let f;
test.beforeEach(async ({ page, baseURL }, info) => {
    f = await prepare(page, baseURL, info.title.match(/BB-\d+/)[0]);
});

// These forms differ from the Phase 2 saveModal contract (role/password/status).
async function submit(page, form, destination = new URL(page.url()).pathname) {
    const action = new URL(await form.getAttribute('action'), page.url()).href;
    const [response] = await Promise.all([
        page.waitForResponse(response => response.request().isNavigationRequest()
            && response.request().method() === 'POST' && response.url() === action),
        page.waitForEvent('domcontentloaded'),
        form.locator('button[type="submit"]').click(),
    ]);
    expect(response.status()).toBe(302);
    expect(new URL(response.headers().location, action).pathname).toBe(destination);
    expect((await response.request().redirectedTo().response()).status()).toBe(200);
    await expect(page).toHaveURL(url => url.pathname === destination);
}

async function confirm(page, button, title = 'Berhasil!', error) {
    await button.click();
    await expect(page.locator('.swal2-title')).toHaveText('Apakah Yakin?');
    await Promise.all([page.waitForEvent('domcontentloaded'), page.locator('.swal2-confirm').click()]);
    if (error) await expect(page.locator('.gagal')).toHaveAttribute('data-gagal', error);
    await notice(page, title);
}

test('BB-15 - Rekapan, detail dan print mengikuti status/owner dan mempertahankan target nol', async ({ page }) => {
    await login(page, 'a');
    const id = f.a.assessment.id;
    const recap = `/admin/home/rekapan/${id}`;
    const paths = [recap, `${recap}/detail`, `${recap}/print`];
    expect(f.a.fields).toHaveLength(1);
    expect(Boolean(f.a.assessment.is_active)).toBe(true);
    expect(Boolean(f.a.assessment.is_publish)).toBe(false);
    expect(f.a.fields[0].level_target).toBe(0);
    expect(f.a.answers.map(item => item.answer)).toEqual(['F', 'F']);
    for (const path of paths) expect((await page.request.get(path, { maxRedirects: 0 })).status()).toBe(404);

    // Reach the inactive+published state through Laravel, using the same assessment.
    expect((await mutate(page, `/admin/daftar-penilaian/set-status/${id}`, { _method: 'PATCH' })).status()).toBe(302);
    expect((await mutate(page, `${assessmentPath(id)}/set-status-penilaian`, {})).status()).toBe(302);
    let current = fixture('inspect', 'BB-15').a;
    expect(Boolean(current.assessment.is_active)).toBe(false);
    expect(Boolean(current.assessment.is_publish)).toBe(true);
    for (const path of paths) expect((await page.request.get(path, { maxRedirects: 0 })).status()).toBe(404);
    // Published metadata is protected: unpublish, activate, then publish again.
    expect((await mutate(page, `${assessmentPath(id)}/set-status-penilaian`, {})).status()).toBe(302);
    expect((await mutate(page, `/admin/daftar-penilaian/set-status/${id}`, { _method: 'PATCH' })).status()).toBe(302);
    expect((await mutate(page, `${assessmentPath(id)}/set-status-penilaian`, {})).status()).toBe(302);
    current = fixture('inspect', 'BB-15').a;
    expect(Boolean(current.assessment.is_active)).toBe(true);
    expect(Boolean(current.assessment.is_publish)).toBe(true);
    expect(current.fields).toEqual(f.a.fields);
    expect(current.answers).toEqual(f.a.answers);

    expect((await page.goto(recap, { waitUntil: 'domcontentloaded' })).status()).toBe(200);
    await expect(page.getByText(f.a.assessment.name, { exact: true })).toBeVisible();
    for (const label of ['Total Domain', 'Total Domain Terjawab']) {
        await expect(page.getByText(label, { exact: true }).locator('..').locator('h4')).toHaveText('1');
    }
    const row = page.locator('#dataTable tbody tr').filter({ hasText: `${f.prefix} Domain` });
    await expect(row).toHaveCount(1);
    await expect(row.locator('td').nth(2)).toHaveText('0.0');
    // Soft assertions collect both recap/print defects while still failing the test.
    await expect.soft(row.locator('td').nth(3), 'Level Target 0 must survive recap aggregation').toHaveText('0.0');
    await expect.soft(page.locator('#dataTable tfoot td').last()).toHaveText('0.0');
    const detail = page.locator(`a[href$="/rekapan/${id}/detail"]`);
    await expect(detail).toBeVisible();
    const [detailResponse] = await Promise.all([
        page.waitForResponse(response => response.request().isNavigationRequest()
            && new URL(response.url()).pathname === `${recap}/detail`), detail.click(),
    ]);
    expect(detailResponse.status()).toBe(200);
    await page.waitForLoadState('domcontentloaded');
    const field = page.locator('#dataTable tbody tr').filter({ hasText: `${f.prefix} Bidang Praktik` });
    await expect(field).toHaveCount(1);
    await expect(field).toContainText(`${f.prefix} Kapabilitas`);
    await expect(field.locator('td').nth(3)).toHaveText('0.0');
    await expect(field.locator('td').nth(4)).toHaveText('0.0');
    await expect(field).toContainText('Baik'); // Both Level 0 answers are Fully Achieved.

    await page.goto(recap, { waitUntil: 'domcontentloaded' });
    const [printResponse] = await Promise.all([
        page.waitForResponse(response => response.request().isNavigationRequest()
            && new URL(response.url()).pathname === `${recap}/print`),
        page.locator(`a[href$="/rekapan/${id}/print"]`).click(),
    ]);
    expect(printResponse.status()).toBe(200);
    expect(printResponse.headers()['content-type']).toContain('text/html');
    const print = page.locator('#print-area');
    await expect(print).toBeVisible();
    await expect(print.getByRole('heading', { name: f.a.assessment.name, exact: true })).toBeVisible();
    await expect(print.getByRole('heading', { name: 'Ringkasan Umum', exact: true })).toBeVisible();
    await expect(page.getByRole('button', { name: /Cetak Halaman/ })).toBeVisible();
    for (const number of [1, 2]) {
        const practice = print.locator('tbody tr').filter({ hasText: `${f.prefix} Praktik ${number}` });
        await expect(practice).toHaveCount(1);
        await expect(practice).toContainText('Fully Achieved');
    }
    const summary = print.locator('table').filter({ has: page.getByRole('columnheader', { name: 'Domain', exact: true }) });
    const summaryRow = summary.locator('tbody tr').filter({ hasText: `${f.prefix} Domain` });
    await expect(summaryRow.locator('td').nth(2)).toHaveText('0.0');
    await expect.soft(summaryRow.locator('td').nth(3), 'Print must retain target 0').toHaveText('0.0');
    await expect.soft(summary.locator('tfoot td').last()).toHaveText('0.0');
    await login(page, 'b');
    for (const path of paths) expect((await page.request.get(path, { maxRedirects: 0 })).status()).toBe(404);
    expect(fixture('inspect', 'BB-15').a).toEqual(current);
});

test('BB-17 - Super Admin mengelola satu user dummy dan Admin biasa ditolak', async ({ page }) => {
    const path = '/admin/master-data/user';
    await login(page, 'super');
    expect((await page.goto(path, { waitUntil: 'domcontentloaded' })).status()).toBe(200);
    await openUi(page, '#modalCreateUser');
    const create = page.locator('.modal#modalCreateUser form');
    await create.locator('[name="email"]').fill(f.testEmail);
    await create.locator('[name="name"]').fill(`${f.name} User`);
    for (const name of ['password', 'password_confirmation']) await create.locator(`[name="${name}"]`).fill(process.env.PW_ADMIN_A_PASSWORD);
    await choose(page, create.locator('[name="role_access"]'), 'Admin');
    await create.locator('[name="is_active"]').check();
    await submit(page, create);
    await notice(page);
    const created = fixture('inspect', 'BB-17').testUser;
    expect(created).not.toBeNull();
    expect(Object.values(f.users)).not.toContain(created.id);
    expect(created.email).toBe(f.testEmail);
    expect(created.name).toBe(`${f.name} User`);
    expect(Boolean(created.is_super_admin)).toBe(false);
    expect(Boolean(created.is_active)).toBe(true);
    expect(created.passwordMatchesInitial).toBe(true);
    const row = page.locator('#dataTable tbody tr').filter({ hasText: f.testEmail });
    await expect(row).toHaveCount(1);
    await expect(row.getByText('Admin', { exact: true })).toBeVisible();
    await openUi(page, `#modalEditUser-${created.id}`);
    const edit = page.locator(`.modal#modalEditUser-${created.id} form`);
    await edit.locator('[name="name"]').fill(`${f.name} Updated`);
    await edit.locator('[name="password"]').fill('');
    await edit.locator('[name="password_confirmation"]').fill('');
    await submit(page, edit);
    await notice(page);
    const updated = fixture('inspect', 'BB-17').testUser;
    expect(updated.id).toBe(created.id);
    expect(updated.name).toBe(`${f.name} Updated`);
    expect(updated.passwordMatchesInitial).toBe(true);
    await page.reload({ waitUntil: 'domcontentloaded' });
    await expect(row).toContainText(updated.name);

    await openUi(page, '#modalCreateUser');
    await create.locator('[name="email"]').fill(f.testEmail);
    await create.locator('[name="name"]').fill(`${f.name} User`);
    for (const name of ['password', 'password_confirmation']) await create.locator(`[name="${name}"]`).fill(process.env.PW_ADMIN_A_PASSWORD);
    await choose(page, create.locator('[name="role_access"]'), 'Admin');
    await submit(page, create);
    await expect(page.locator('.alert-danger')).toContainText(/email.*(?:sudah|telah|digunakan|already|taken)/i);
    expect(fixture('inspect', 'BB-17').testUser).toEqual(updated);
    await expect(row).toHaveCount(1);
    await confirm(page, row.locator('[title="Hapus Data"]'), 'Gagal!', /user masih aktif/i);
    expect(fixture('inspect', 'BB-17').testUser).toEqual(updated);
    await expect(row).toHaveCount(1);
    await confirm(page, row.locator('.changeStatus'));
    expect(Boolean(fixture('inspect', 'BB-17').testUser.is_active)).toBe(false);
    await expect(row.locator('.changeStatus')).not.toHaveClass(/\bon\b/);
    await confirm(page, row.locator('[title="Hapus Data"]'));
    await expect(row).toHaveCount(0);
    expect(fixture('inspect', 'BB-17').testUser).toBeNull();
    await login(page, 'a');
    expect((await page.goto(path, { waitUntil: 'domcontentloaded' })).status()).toBe(403);
    await expect(page.getByRole('heading', { name: '403 - Forbidden Access', exact: true })).toBeVisible();
});

test('BB-18 - Profil dan credential akun disposable benar-benar tersimpan', async ({ page }) => {
    const initialPassword = process.env.PW_ADMIN_A_PASSWORD;
    const replacement = `${initialPassword}-BB-18`;
    expect(f.testUser).not.toBeNull();
    expect(Object.values(f.users)).not.toContain(f.testUser.id);
    expect(f.testUser.passwordMatchesInitial).toBe(true);
    expect(f.testUser.passwordMatchesReplacement).toBe(false);
    await login(page, 'a', { email: f.testEmail, password: initialPassword });
    const profilePath = '/admin/setting/profile';
    expect((await page.goto(profilePath, { waitUntil: 'domcontentloaded' })).status()).toBe(200);
    const profile = page.locator(`form[action$="${profilePath}"]`);
    await profile.locator('[name="name"]').fill(`${f.name} Updated`);
    await submit(page, profile);
    await notice(page);
    await page.reload({ waitUntil: 'domcontentloaded' });
    await expect(profile.locator('[name="name"]')).toHaveValue(`${f.name} Updated`);
    const renamed = fixture('inspect', 'BB-18').testUser;
    expect(renamed.id).toBe(f.testUser.id);
    expect(renamed.name).toBe(`${f.name} Updated`);
    const invalidName = await mutate(page, profilePath, { _method: 'PATCH', name: '' });
    expect(invalidName.status()).toBe(422);
    expect(Object.keys((await invalidName.json()).errors)).toContain('name');
    expect(fixture('inspect', 'BB-18').testUser).toEqual(renamed);

    const securityPath = '/admin/setting/security';
    expect((await page.goto(securityPath, { waitUntil: 'domcontentloaded' })).status()).toBe(200);
    const mismatch = await mutate(page, securityPath, { _method: 'PATCH', email: f.testEmail,
        password: replacement, password_confirmation: initialPassword });
    expect(mismatch.status()).toBe(422);
    expect(Object.keys((await mismatch.json()).errors)).toContain('password');
    const duplicate = await mutate(page, securityPath, { _method: 'PATCH', email: process.env.PW_ADMIN_A_EMAIL });
    expect(duplicate.status()).toBe(422);
    expect(Object.keys((await duplicate.json()).errors)).toContain('email');
    expect(fixture('inspect', 'BB-18').testUser).toEqual(renamed);
    const security = page.locator(`form[action$="${securityPath}"]`);
    await security.locator('[name="email"]').fill(f.updatedEmail);
    for (const name of ['password', 'password_confirmation']) await security.locator(`[name="${name}"]`).fill(replacement);
    await submit(page, security, '/login');
    await notice(page);
    const protectedResponse = await page.request.get('/admin/home', { maxRedirects: 0 });
    expect(protectedResponse.status()).toBe(302);
    expect(new URL(protectedResponse.headers().location, page.url()).pathname).toBe('/login');
    const secured = fixture('inspect', 'BB-18').testUser;
    // Known source defect: SecurityController commits without calling updateUserSecurity.
    // Boolean checks expose the defect without returning any password/hash from PHP.
    expect.soft(secured.email).toBe(f.updatedEmail);
    expect.soft(secured.passwordMatchesInitial).toBe(false);
    expect.soft(secured.passwordMatchesReplacement).toBe(true);
    await login(page, 'a', { email: f.updatedEmail, password: replacement });
    await page.goto(securityPath, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('input[name="email"]')).toHaveValue(f.updatedEmail);
    const dropdown = page.locator('.navbar-top .dropdown');
    await dropdown.locator('[data-bs-toggle="dropdown"]').click();
    await expect(dropdown.locator('.dropdown-menu')).toBeVisible();
    await dropdown.getByRole('link', { name: /\bLogout$/ }).click();
    await expect(page).toHaveURL(url => ['/', '/login'].includes(url.pathname));
    await page.goto('/login', { waitUntil: 'domcontentloaded' });
    await page.getByLabel('Alamat email', { exact: true }).fill(f.updatedEmail);
    await page.getByLabel('Kata sandi', { exact: true }).fill(initialPassword);
    await page.getByRole('button', { name: 'Login', exact: true }).click();
    await expect(page).toHaveURL(/\/login\/?$/);
    await expect(page.locator('.aim-field-error').first()).toContainText(/Identitas tersebut tidak cocok|These credentials do not match/);
});

test('BB-19 - Metadata, status dan soft-delete assessment milik A', async ({ page }) => {
    await login(page, 'a');
    const path = '/admin/daftar-penilaian';
    const id = f.a.assessment.id;
    expect(Boolean(f.a.assessment.is_active)).toBe(true);
    expect(Boolean(f.a.assessment.is_publish)).toBe(false);
    expect((await page.goto(path, { waitUntil: 'domcontentloaded' })).status()).toBe(200);
    const card = page.locator(`#delete-${id}`).locator('..').locator('..');
    await expect(card.locator(`a[href$="/penilaian/${id}"]`)).toBeVisible();
    const selector = `#modalEditDaftarPenilaian-${id}`;
    await openUi(page, selector);
    const form = page.locator(`.modal${selector} form`);
    const name = `${f.name} Updated`;
    const description = `${f.name} Updated description`;
    await form.locator('[name="name"]').fill(name);
    await form.locator('[name="description"]').fill(description);
    await form.locator('[name="is_active"]').check();
    await submit(page, form);
    await notice(page);
    const updated = fixture('inspect', 'BB-19').a.assessment;
    expect(updated).toMatchObject({ id, name, description, deleted_at: null });
    expect(Boolean(updated.is_active)).toBe(true);
    await page.reload({ waitUntil: 'domcontentloaded' });
    await expect(card.locator(`a[href$="/penilaian/${id}"]`)).toBeVisible();
    await openUi(page, selector);
    await expect(form.locator('[name="name"]')).toHaveValue(name);
    await expect(form.locator('[name="description"]')).toHaveValue(description);
    await form.locator('[data-bs-dismiss="modal"]').click();
    await expect(page.locator(`.modal${selector}`)).toBeHidden();
    await confirm(page, card.locator('[title="Hapus Data"]'), 'Gagal!', /masih aktif/i);
    expect(fixture('inspect', 'BB-19').a.assessment).toEqual(updated);
    await expect(card).toHaveCount(1);
    // The existing card has no status button; change its checkbox in the edit modal.
    await openUi(page, selector);
    await form.locator('[name="is_active"]').uncheck();
    await submit(page, form);
    await notice(page);
    const inactive = fixture('inspect', 'BB-19').a.assessment;
    expect(inactive.id).toBe(id);
    expect(Boolean(inactive.is_active)).toBe(false);
    expect(inactive.deleted_at).toBeNull();
    await expect(card.getByText('Tidak Aktif', { exact: true })).toBeVisible();
    await confirm(page, card.locator('[title="Hapus Data"]'));
    await expect(page.locator(`#delete-${id}`)).toHaveCount(0);
    await page.reload({ waitUntil: 'domcontentloaded' });
    await expect(page.locator(`a[href$="/penilaian/${id}"]`)).toHaveCount(0);
    const deleted = fixture('inspect', 'BB-19').a.assessment;
    expect(deleted).toMatchObject({ id, name, description });
    expect(deleted.deleted_at).toMatch(/^\d{4}-\d{2}-\d{2}T/);
});
