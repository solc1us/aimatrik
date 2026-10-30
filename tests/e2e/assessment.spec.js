import { test, expect } from '@playwright/test';
import { prepare, fixture, login, assessmentPath, saveModal, notice, defineTarget, openField,
    answer, saveAnswers, pdf, mutate, answerPayload } from './e2e-helpers.js';

let f;
test.beforeEach(async ({ page, baseURL }, info) => {
    f = await prepare(page, baseURL, info.title.match(/BB-\d+/)[0]);
    await login(page, 'a');
});

test('BB-10 - Membuat assessment mengkloning hierarki aktif dan bertahan setelah refresh', async ({ page }) => {
    await page.goto('/admin/daftar-penilaian', { waitUntil: 'domcontentloaded' });
    await page.locator('[data-bs-target="#modalCreateDaftarPenilaian"]').click();
    await saveModal(page, '#modalCreateDaftarPenilaian', { name: `${f.name} A`, description: f.name });
    await expect(page.getByText(`${f.name} A`, { exact: true })).toBeVisible();
    await page.reload({ waitUntil: 'domcontentloaded' });
    await expect(page.getByText(`${f.name} A`, { exact: true })).toBeVisible();
    const current = fixture('inspect', 'BB-10');
    expect(current.a.assessment).not.toBeNull();
    expect(current.a.fields).toHaveLength(1);
    expect(current.a.answers).toHaveLength(2);
    await page.locator(`a[href$="/penilaian/${current.a.assessment.id}"]`).click();
    await expect(page.getByText(`${f.prefix} Domain`, { exact: false }).first()).toBeVisible();
    await expect(page.getByText(`${f.prefix} Kapabilitas`, { exact: false }).first()).toBeVisible();
    await defineTarget(page, current.a.fields[0].id);
    await openField(page, current.a.fields[0].id);
    for (const item of current.a.answers) await expect(page.locator(`#praktik-${item.id}`)).toBeVisible();
    await page.reload({ waitUntil: 'domcontentloaded' });
    await openField(page, current.a.fields[0].id);
    await expect(page.locator('select[name^="pelaksanaan["]')).toHaveCount(2);
});

test('BB-11 - Target dan jawaban tersimpan melalui UI', async ({ page }) => {
    await page.goto(assessmentPath(f.a.assessment.id), { waitUntil: 'domcontentloaded' });
    await defineTarget(page, f.a.fields[0].id);
    await answer(page, f.a);
    await saveAnswers(page, f.a);
    await page.reload({ waitUntil: 'domcontentloaded' });
    await openField(page, f.a.fields[0].id);
    await expect(page.locator(`input[name="level_target[${f.a.fields[0].id}]"]`)).toHaveValue('0');
    for (const item of f.a.answers) await expect(page.locator(`#praktik-${item.id} select`)).toHaveValue('F');
    const current = fixture('inspect', 'BB-11');
    expect(current.a.answers.map(item => item.answer)).toEqual(['F', 'F']);
});

test('BB-12 - PDF dummy dapat diunggah/dilihat/dihapus dan ekstensi invalid tidak disimpan', async ({ page }) => {
    const id = f.a.answers[0].id;
    const field = f.a.fields[0].id;
    const detail = page.locator(`#collapseDetail${id}`);
    await page.goto(assessmentPath(f.a.assessment.id), { waitUntil: 'domcontentloaded' });
    await openField(page, field);
    await page.locator(`[data-bs-target="#collapseDetail${id}"]`).click();
    await expect(detail).toHaveClass(/\bshow\b/);
    await detail.locator('input[type="file"]').setInputFiles(pdf());
    await saveAnswers(page, f.a);
    await openField(page, field);
    await page.locator(`[data-bs-target="#collapseDetail${id}"]`).click();
    await expect(detail).toHaveClass(/\bshow\b/);
    await detail.locator(`[data-id_target="${id}"]`).click();
    const preview = page.locator('.modal#modalPreviewFilePendukung');
    await expect(preview).toBeVisible();
    const link = preview.getByRole('link', { name: 'Lihat PDF', exact: true });
    await expect(link).toBeVisible();
    const url = await link.getAttribute('href');
    const file = await page.request.get(url);
    expect(file.status()).toBe(200);
    expect(file.headers()['content-type']).toContain('application/pdf');
    expect(fixture('inspect', 'BB-12').a.assets).toHaveLength(1);
    await preview.locator('#btnKosongkanFilePendukung').click();
    await notice(page);
    expect(fixture('inspect', 'BB-12').a.assets).toEqual([]);
    expect((await page.request.get(url)).status()).toBe(404);

    await page.reload({ waitUntil: 'domcontentloaded' });
    await openField(page, field);
    await page.locator(`[data-bs-target="#collapseDetail${id}"]`).click();
    await expect(detail).toHaveClass(/\bshow\b/);
    await detail.locator('input[type="file"]').setInputFiles({ name: 'e2e-invalid.exe', mimeType: 'application/octet-stream', buffer: Buffer.from('E2E dummy text; not an executable') });
    await saveAnswers(page, f.a);
    expect(fixture('inspect', 'BB-12').a.assets).toEqual([]);
    expect(fixture('inspect', 'BB-12').a.files).toEqual([]);
});

test('BB-13 - Publish menolak mutasi UI/request langsung dan unpublish mengizinkan edit', async ({ page }) => {
    const path = assessmentPath(f.a.assessment.id);
    await page.goto(path, { waitUntil: 'domcontentloaded' });
    expect(Boolean(f.a.assessment.is_publish)).toBe(false);
    const [publish] = await Promise.all([
        page.waitForResponse(response => response.request().method() === 'POST'
            && new URL(response.url()).pathname === `${path}/set-status-penilaian`),
        page.getByRole('button', { name: /Publish Penilaian/ }).click(),
    ]);
    expect(publish.status()).toBe(302);
    await page.waitForURL(url => url.pathname === path, { waitUntil: 'domcontentloaded' });
    const before = fixture('inspect', 'BB-13').a;
    expect(Boolean(before.assessment.is_publish)).toBe(true);
    await expect(page.locator('.berhasil')).toHaveAttribute('data-berhasil', /berhasil diubah statusnya/i);
    await notice(page);
    await openField(page, f.a.fields[0].id);
    for (const item of f.a.answers) await expect(page.locator(`#praktik-${item.id} select`)).toBeDisabled();
    await expect(page.locator(`#simpan-bidang-praktik-${f.a.fields[0].id}`)).toHaveCount(0);

    for (const [suffix, body] of [
        ['store-level-target', { level: f.a.fields[0].id, level_target: '4' }],
        ['image-penilaian/delete', { idTarget: f.a.answers[0].id }],
    ]) {
        const response = await mutate(page, `${path}/${suffix}`, body);
        expect(response.status()).toBe(500); // Existing Json::error convention, not a generic 500 acceptance.
        expect((await response.json()).meta.error).toMatch(/sudah dipublish/i);
    }
    const result = await mutate(page, `${path}/store-penilaian`, answerPayload(f.a),
        `file_pendukung[${f.a.fields[0].id}][0][${f.a.answers[0].id}][]`);
    expect(result.status()).toBe(302);
    await page.goto(path, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('.gagal')).toHaveAttribute('data-gagal', /sudah dipublish/i);
    await notice(page, 'Gagal!');
    for (const [action, body] of [
        ['update', { _method: 'PATCH', name: `${f.name} Changed`, description: f.name }],
        ['set-status', { _method: 'PATCH' }],
        ['delete', { _method: 'DELETE' }],
    ]) {
        const response = await mutate(page, `/admin/daftar-penilaian/${action}/${f.a.assessment.id}`, body);
        expect(response.status()).toBe(302);
        await page.goto(path, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('.gagal')).toHaveAttribute('data-gagal', /sudah dipublish/i);
        await notice(page, 'Gagal!');
    }
    expect(fixture('inspect', 'BB-13').a).toEqual(before);
    const [unpublish] = await Promise.all([
        page.waitForResponse(response => response.request().method() === 'POST'
            && new URL(response.url()).pathname === `${path}/set-status-penilaian`),
        page.getByRole('button', { name: /Unpublish Penilaian/ }).click(),
    ]);
    expect(unpublish.status()).toBe(302);
    await page.waitForURL(url => url.pathname === path, { waitUntil: 'domcontentloaded' });
    expect(Boolean(fixture('inspect', 'BB-13').a.assessment.is_publish)).toBe(false);
    await notice(page);
    await answer(page, f.a, 'Not Achieved');
    await saveAnswers(page, f.a);
    await page.reload({ waitUntil: 'domcontentloaded' });
    await openField(page, f.a.fields[0].id);
    for (const item of f.a.answers) await expect(page.locator(`#praktik-${item.id} select`)).toHaveValue('N');
});

test('BB-14 - Admin A tidak dapat membaca/mengubah assessment atau ID anak milik B', async ({ page }) => {
    const before = fixture('inspect', 'BB-14').b;
    await login(page, 'b');
    await page.goto(assessmentPath(f.b.assessment.id), { waitUntil: 'domcontentloaded' });
    await openField(page, f.b.fields[0].id);
    for (const item of f.b.answers) await expect(page.locator(`#praktik-${item.id} select`)).toHaveValue('F');
    await login(page, 'a');
    const ownPath = assessmentPath(f.a.assessment.id);
    await page.goto(ownPath, { waitUntil: 'domcontentloaded' });
    await page.goto(assessmentPath(f.b.assessment.id), { waitUntil: 'domcontentloaded', referer: new URL(ownPath, page.url()).href });
    await expect(page).not.toHaveURL(new RegExp(`/penilaian/${f.b.assessment.id}$`));
    await expect(page.locator('.gagal')).toHaveAttribute('data-gagal', /tidak ditemukan/i);
    await notice(page, 'Gagal!');
    await page.goto(ownPath, { waitUntil: 'domcontentloaded' });

    for (const path of [ownPath, assessmentPath(f.b.assessment.id)]) {
        for (const [suffix, body] of [
            ['store-level-target', { level: f.b.fields[0].id, level_target: '4' }],
            ['image-penilaian/load', { idTarget: f.b.answers[0].id }],
            ['image-penilaian/delete', { idTarget: f.b.answers[0].id }],
        ]) {
            const response = await mutate(page, `${path}/${suffix}`, body);
            expect(response.status()).toBe(500);
            expect((await response.json()).meta.error).toMatch(/tidak ditemukan/i);
        }
        const response = await mutate(page, `${path}/store-penilaian`, answerPayload(f.b),
            `file_pendukung[${f.b.fields[0].id}][0][${f.b.answers[0].id}][]`);
        expect(response.status()).toBe(302);
        await page.goto(ownPath, { waitUntil: 'domcontentloaded' });
        await expect(page.locator('.gagal')).toHaveAttribute('data-gagal', /tidak ditemukan/i);
        await notice(page, 'Gagal!');
    }
    // Also reject a foreign practice nested under an otherwise-owned field ID.
    const mixed = await mutate(page, `${ownPath}/store-penilaian`, answerPayload(f.b, f.a.fields[0].id));
    expect(mixed.status()).toBe(302);
    await page.goto(ownPath, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('.gagal')).toHaveAttribute('data-gagal', /tidak ditemukan/i);
    await notice(page, 'Gagal!');
    expect(fixture('inspect', 'BB-14').b).toEqual(before);
    await login(page, 'b');
    await page.goto(assessmentPath(f.b.assessment.id), { waitUntil: 'domcontentloaded' });
    await openField(page, f.b.fields[0].id);
    for (const item of f.b.answers) await expect(page.locator(`#praktik-${item.id} select`)).toHaveValue('F');
});

test('BB-16 - Assessment tanpa pertanyaan menampilkan empty state dan navigasi normal', async ({ page }) => {
    expect(f.a.fields).toEqual([]);
    expect(f.a.answers).toEqual([]);
    const path = assessmentPath(f.a.assessment.id);
    const response = await page.goto(path, { waitUntil: 'domcontentloaded' });
    expect(response.status()).toBe(200);
    await expect(page).toHaveURL(url => url.pathname === path);
    await expect(page.getByText('Belum ada data yang tersedia', { exact: true })).toBeVisible();
    await expect(page.locator('[id^="form-bidang-praktik-"]')).toHaveCount(0);
    await page.getByRole('link', { name: /Kembali/ }).click();
    await expect(page).toHaveURL(/\/admin\/daftar-penilaian\/?$/);
    const [returned] = await Promise.all([
        page.waitForResponse(response => response.request().isNavigationRequest()
            && new URL(response.url()).pathname === path),
        page.locator(`a[href$="/penilaian/${f.a.assessment.id}"]`).click(),
    ]);
    expect(returned.status()).toBe(200);
    await page.waitForURL(url => url.pathname === path, { waitUntil: 'domcontentloaded' });
    await expect(page.getByText('Belum ada data yang tersedia', { exact: true })).toBeVisible();
});
