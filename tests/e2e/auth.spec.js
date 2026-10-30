import { randomUUID } from 'node:crypto';
import { test, expect } from '@playwright/test';

test('BB-05 - Halaman login tersedia', async ({ page }) => {
    const response = await page.goto('/login');
    expect(response.status()).toBe(200);
    await expect(page).toHaveTitle(/AIMATRIK/);
    await expect(page.locator('form.aim-login-form')).toBeVisible();
    await expect(page.getByLabel('Alamat email', { exact: true })).toBeVisible();
    await expect(page.getByLabel('Kata sandi', { exact: true })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Login', exact: true })).toBeVisible();
});

test('BB-05 - Credential tidak valid ditolak', async ({ page }) => {
    await page.goto('/login');
    // Generated invalid inputs never target an existing user's account.
    await page.getByLabel('Alamat email', { exact: true }).fill(`pw-invalid-${randomUUID()}@example.invalid`);
    await page.getByLabel('Kata sandi', { exact: true }).fill(randomUUID());
    await page.getByRole('button', { name: 'Login', exact: true }).click();
    await expect(page.locator('.aim-field-error').first()).toBeVisible();
    await expect(page.locator('.aim-field-error').first()).toContainText(
        /Identitas tersebut tidak cocok dengan data kami|These credentials do not match our records/
    );
    await expect(page.locator('#email')).toHaveClass(/\bis-invalid\b/);
    await expect(page).toHaveURL(/\/login\/?$/);
});

test('BB-05 - Login valid membuka dashboard dan logout mengakhiri sesi', async ({ page }) => {
    const email = process.env.PW_ADMIN_EMAIL;
    const password = process.env.PW_ADMIN_PASSWORD;
    if (!email || !password) {
        const reason = 'PW_ADMIN_EMAIL dan PW_ADMIN_PASSWORD belum lengkap; login valid/logout dilewati.';
        console.info(`SKIP BB-05: ${reason}`);
        test.skip(true, reason);
    }

    await page.goto('/login');
    await page.getByLabel('Alamat email', { exact: true }).fill(email);
    await page.getByLabel('Kata sandi', { exact: true }).fill(password);
    await page.getByRole('button', { name: 'Login', exact: true }).click();
    await expect(page).toHaveURL(/\/admin\/home\/?$/);
    await expect(page.getByRole('heading', { name: 'Dashboard - Rekapan Penilaian', exact: true })).toBeVisible();

    const profileDropdown = page.locator('.navbar-top .dropdown');
    const profileToggle = profileDropdown.locator('[data-bs-toggle="dropdown"]');
    await profileToggle.click();
    await expect(profileToggle).toHaveAttribute('aria-expanded', 'true');
    const profileMenu = profileDropdown.locator('.dropdown-menu');
    await expect(profileMenu).toHaveClass(/\bshow\b/);
    await expect(profileMenu).toBeVisible();
    // The icon font contributes a glyph to the link's accessible name.
    const logout = profileMenu.getByRole('link', { name: /\bLogout$/ });
    await expect(logout).toBeVisible();
    await logout.click();
    await expect(page).toHaveURL(url => url.pathname === '/' || url.pathname === '/login');
    await page.goto('/admin/home');
    await expect(page).toHaveURL(/\/login\/?$/);
    await expect(page.locator('form.aim-login-form')).toBeVisible();
});

for (const path of ['/admin/home', '/admin/daftar-penilaian', '/admin/master-data/domain']) {
    test(`BB-06 - Guest tidak dapat mengakses ${path}`, async ({ page }) => {
        // Playwright supplies a fresh, unauthenticated context for each test.
        const response = await page.goto(path);
        if ([401, 403].includes(response.status())) {
            expect(response.ok()).toBe(false);
        } else {
            expect(response.status()).toBe(200);
            await expect(page).toHaveURL(/\/login\/?$/);
            await expect(page.locator('form.aim-login-form')).toBeVisible();
        }
    });
}
