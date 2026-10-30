import { test, expect } from '@playwright/test';

let pageErrors;

test.beforeEach(async ({ page }) => {
    pageErrors = [];
    page.on('pageerror', error => pageErrors.push(error.message));
    const response = await page.goto('/');
    expect(response.status()).toBe(200);
});

test.afterEach(() => {
    expect(pageErrors, 'Tidak boleh ada JavaScript page error').toEqual([]);
});

test('BB-01 - Landing page dan gambar framework original', async ({ page }) => {
    await expect(page).toHaveTitle(/AIMATRIK/);
    await expect(page.getByRole('link', { name: 'AIMATRIK Home' })).toBeVisible();
    await expect(page.locator('#aimNavbar')).toBeVisible();
    await expect(page.getByRole('navigation', { name: 'Main navigation', exact: true })).toBeVisible();
    for (const id of ['home', 'visi', 'mengapa', 'struktur', 'level', 'tingkat-praktik', 'pengembang']) {
        await expect(page.locator(`#${id}`)).toBeVisible();
    }
    await expect(page.getByRole('heading', { name: '6 Domain Utama dalam Model AIMATRIK.', exact: true })).toBeVisible();
    const framework = page.locator('#struktur img[src$="/assets/images/logo/asset-model.png"]');
    await framework.scrollIntoViewIfNeeded();
    await expect(framework).toBeVisible();
    await expect(framework).toHaveJSProperty('complete', true);
    expect(await framework.evaluate(image => image.naturalWidth)).toBeGreaterThan(0);
});

test('BB-02 - Enam accordion domain dapat dibuka dan ditutup', async ({ page }) => {
    const accordion = page.locator('#accordionExample');
    await expect(accordion).toHaveCount(1);
    await expect(accordion.locator(':scope > .accordion-item')).toHaveCount(6);
    const buttons = accordion.locator('button[data-bs-toggle="collapse"]');
    await expect(buttons).toHaveCount(6);
    const ids = await accordion.locator('[id]').evaluateAll(elements => elements.map(element => element.id));
    expect(new Set(ids).size, 'ID accordion harus unik').toBe(ids.length);
    const targets = [];

    for (let index = 0; index < 6; index++) {
        const button = buttons.nth(index);
        const target = await button.getAttribute('data-bs-target');
        expect(target).toMatch(/^#[\w-]+$/);
        targets.push(target);
        await expect(button).toHaveAttribute('aria-controls', target.slice(1));
        const panel = page.locator(target);
        await expect(panel).toHaveCount(1);
        await expect(panel).toHaveAttribute('data-bs-parent', '#accordionExample');
        const heading = await panel.getAttribute('aria-labelledby');
        expect(heading).toMatch(/^[\w-]+$/);
        await expect(page.locator(`#${heading}`)).toHaveCount(1);
        await expect(panel).toBeHidden();

        await button.click();
        // Bootstrap ignores toggles during its collapse transition.
        await expect(panel).toHaveClass(/\bshow\b/);
        await expect(panel).toBeVisible();
        await expect(button).toHaveAttribute('aria-expanded', 'true');
        await button.click();
        await expect(panel).toBeHidden();
        await expect(button).toHaveAttribute('aria-expanded', 'false');
    }
    expect(new Set(targets).size, 'Target accordion harus unik').toBe(6);
});

test('BB-03 - Sustainability menampilkan tujuan, kapabilitas dan praktik lengkap', async ({ page }) => {
    await page.getByRole('button', { name: /Domain Keberlanjutan \(Sustainability\).*SA/ }).click();
    const panel = page.locator('#collapseSix');
    await expect(panel).toHaveClass(/\bshow\b/);
    await expect(panel.locator('.accordion-body > p')).toBeVisible();
    await expect(panel.locator('.accordion-body > p')).toHaveText(
        'Menilai sejauh mana organisasi mengintegrasikan prinsip keberlanjutan dalam tata kelola AI, mencakup efisiensi sumber daya, pengelolaan dampak lingkungan, inklusivitas, keberlanjutan operasional, dan manfaat AI bagi pemangku kepentingan.'
    );
    const capabilities = panel.locator('.text-list').filter({
        has: page.getByRole('heading', { name: 'Bidang Kapabilitas', exact: true }),
    }).getByRole('listitem');
    await expect(capabilities).toHaveText([
        'SA-1 — Pengelolaan Sumber Daya dan Dampak Lingkungan AI',
        'SA-2 — Keberlanjutan Sosial dan Manfaat Institusional AI',
    ]);
    const practices = panel.locator('.text-list').filter({
        has: page.getByRole('heading', { name: 'Bidang Praktik', exact: true }),
    }).getByRole('listitem');
    await expect(practices).toHaveText([
        'SA-1.1 — Perencanaan dan Efisiensi Sumber Daya AI',
        'SA-1.2 — Pemantauan Dampak Lingkungan dan Optimalisasi AI',
        'SA-2.1 — Pengembangan AI yang Inklusif dan Berkelanjutan',
        'SA-2.2 — Keberlanjutan Operasional dan Evaluasi Manfaat AI',
    ]);
    for (const item of await panel.getByRole('listitem').all()) {
        await item.scrollIntoViewIfNeeded();
        await expect(item).toBeVisible();
    }
});
