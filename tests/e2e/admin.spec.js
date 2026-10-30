import { test, expect } from '@playwright/test';
import { prepare, fixture, login, master, saveModal, notice, removeRow, choose } from './e2e-helpers.js';

let f;
test.beforeEach(async ({ page, baseURL }, info) => {
    f = await prepare(page, baseURL, info.title.match(/BB-\d+/)[0]);
});

test('BB-07 - Master data mengikuti role pada akses URL langsung', async ({ page }) => {
    const paths = [master, `${master}/bidang-kapabilitas/${f.domain}`,
        `${master}/bidang-kapabilitas/${f.domain}/bidang-praktik/${f.capability}`,
        `${master}/bidang-kapabilitas/${f.domain}/bidang-praktik/${f.capability}/praktik/${f.field}`];
    await page.goto(master, { waitUntil: 'domcontentloaded' });
    await expect(page).toHaveURL(/\/login\/?$/);
    await login(page, 'super');
    for (const path of paths) {
        expect((await page.goto(path, { waitUntil: 'domcontentloaded' })).status()).toBe(200);
        await expect(page.locator('#dataTable')).toBeVisible();
    }
    await login(page, 'a');
    for (const path of paths) {
        expect((await page.goto(path, { waitUntil: 'domcontentloaded' })).status()).toBe(403);
        await expect(page.getByRole('heading', { name: '403 - Forbidden Access', exact: true })).toBeVisible();
    }
});

test('BB-08 - Domain dummy dapat dibuat, diedit, ditoggle dan dihapus; duplikat ditolak', async ({ page }) => {
    await login(page, 'super');
    await page.goto(master, { waitUntil: 'domcontentloaded' });
    const code = `${f.prefix}-BB-08`;
    await page.locator('[data-bs-target="#modalCreateDomain"]').click();
    await saveModal(page, '#modalCreateDomain', { code, name: `${f.name} Domain` });
    const row = page.locator('#dataTable tbody tr').filter({ hasText: code });
    await expect(row).toHaveCount(1);
    const edit = await row.locator('[title="Ubah Data"]').getAttribute('data-bs-target');
    await row.locator('[title="Ubah Data"]').click();
    await saveModal(page, edit, { code, name: `${f.name} Updated` });
    await expect(row).toContainText(`${f.name} Updated`);
    const beforeDuplicate = fixture('inspect', 'BB-08').dummyDomains;
    expect(beforeDuplicate).toHaveLength(1);

    await page.locator('[data-bs-target="#modalCreateDomain"]').click();
    const modal = page.locator('.modal#modalCreateDomain');
    await modal.locator('[name="code"]').fill(code);
    await modal.locator('[name="name"]').fill(`${f.name} Duplicate`);
    const [duplicate] = await Promise.all([
        page.waitForResponse(response => response.request().method() === 'POST'
            && new URL(response.url()).pathname === `${master}/store`),
        page.waitForEvent('domcontentloaded'),
        modal.locator('button[type="submit"]').click(),
    ]);
    expect(duplicate.status()).toBe(302);
    expect(new URL(duplicate.headers().location, page.url()).pathname).toBe(master);
    const validation = page.locator('.alert-danger');
    await expect(validation).toBeVisible();
    await expect(validation).toContainText(/(?:code|kode).*(?:sudah|telah|digunakan|already|taken)/i);
    expect(fixture('inspect', 'BB-08').dummyDomains).toEqual(beforeDuplicate);
    await expect(row).toHaveCount(1);
    await expect(page.locator('#dataTable')).not.toContainText(`${f.name} Duplicate`);

    for (const active of [true, false]) {
        await row.locator('button.changeStatus').click();
        await expect(page.locator('.swal2-title')).toHaveText('Apakah Yakin?');
        await page.locator('.swal2-confirm').click();
        await notice(page);
        if (active) await expect(row.locator('button.changeStatus')).toHaveClass(/\bon\b/);
        else await expect(row.locator('button.changeStatus')).not.toHaveClass(/\bon\b/);
    }
    await removeRow(page, row);
    await expect(row).toHaveCount(0);
});

test('BB-09 - CRUD hierarki kecil mempertahankan parent dan navigasi', async ({ page }) => {
    await login(page, 'super');
    await page.goto(master, { waitUntil: 'domcontentloaded' });
    const domainRow = page.locator('#dataTable tbody tr').filter({ hasText: `${f.prefix}-BB-09` });
    const capabilityPath = `${master}/bidang-kapabilitas/${f.dummyDomain}`;
    await domainRow.locator(`a[href$="/bidang-kapabilitas/${f.dummyDomain}"]`).click();
    await expect(page).toHaveURL(new RegExp(`${capabilityPath}$`));
    await page.locator('[data-bs-target="#modalCreateBidangKapabilitas"]').click();
    await expect(page.locator('#modalCreateBidangKapabilitas [name="domain_id"]')).toHaveValue(f.dummyDomain);
    const capCode = `${f.prefix}-BB-09-C`;
    await saveModal(page, '#modalCreateBidangKapabilitas', { code: capCode, name: `${f.name} Capability` });
    let row = page.locator('#dataTable tbody tr').filter({ hasText: capCode });
    let edit = await row.locator('[title="Ubah Data"]').getAttribute('data-bs-target');
    await row.locator('[title="Ubah Data"]').click();
    await saveModal(page, edit, { name: `${f.name} Capability Updated` });
    await expect(row).toContainText(`${f.name} Capability Updated`);
    const fieldLink = row.locator('a[title="Atur Kapabilitas"]');
    const fieldURL = await fieldLink.getAttribute('href');
    const capabilityId = new URL(fieldURL).pathname.split('/').at(-1);
    await fieldLink.click();
    await expect(page).toHaveURL(fieldURL);
    await page.locator('[data-bs-target="#modalCreateBidangPraktik"]').click();
    await expect(page.locator('#modalCreateBidangPraktik [name="bidang_kapabilitas_id"]')).toHaveValue(capabilityId);
    const fieldCode = `${f.prefix}-BB-09-F`;
    await saveModal(page, '#modalCreateBidangPraktik', { code: fieldCode, name: `${f.name} Field` });
    row = page.locator('#dataTable tbody tr').filter({ hasText: fieldCode });
    edit = await row.locator('[title="Ubah Data"]').getAttribute('data-bs-target');
    await row.locator('[title="Ubah Data"]').click();
    await saveModal(page, edit, { name: `${f.name} Field Updated` });
    await expect(row).toContainText(`${f.name} Field Updated`);
    const practiceLink = row.locator('a[title="Atur List Praktik"]');
    const practiceURL = await practiceLink.getAttribute('href');
    const fieldId = new URL(practiceURL).pathname.split('/').at(-1);
    await practiceLink.click();
    await expect(page).toHaveURL(practiceURL);
    await page.locator('[data-bs-target="#modalCreatePraktik"]').click();
    const practiceModal = page.locator('.modal#modalCreatePraktik');
    await expect(practiceModal.locator('[name="bidang_praktik_id"]')).toHaveValue(fieldId);
    await choose(page, practiceModal.locator('[name="kategori_level"]'), 'Level 0');
    await saveModal(page, '#modalCreatePraktik', { praktik: `${f.name} Practice` });
    row = page.locator('#dataTable tbody tr').filter({ hasText: `${f.name} Practice` });
    edit = await row.locator('[title="Ubah Data"]').getAttribute('data-bs-target');
    await row.locator('[title="Ubah Data"]').click();
    await saveModal(page, edit, { praktik: `${f.name} Practice Updated` });
    row = page.locator('#dataTable tbody tr').filter({ hasText: `${f.name} Practice Updated` });
    await expect(row).toHaveCount(1);
    await removeRow(page, row);
    await expect(row).toHaveCount(0);

    await page.goto(fieldURL, { waitUntil: 'domcontentloaded' });
    row = page.locator('#dataTable tbody tr').filter({ hasText: fieldCode });
    await expect(row).toContainText(`${f.name} Field Updated`);
    await removeRow(page, row);
    await expect(row).toHaveCount(0);
    await page.goto(capabilityPath, { waitUntil: 'domcontentloaded' });
    row = page.locator('#dataTable tbody tr').filter({ hasText: capCode });
    await removeRow(page, row);
    await expect(row).toHaveCount(0);
});
