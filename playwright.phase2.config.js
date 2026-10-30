import base from './playwright.config.js';
import { defineConfig } from '@playwright/test';

export default defineConfig({
    ...base,
    testMatch: ['**/admin.spec.js', '**/assessment.spec.js'],
    timeout: 120_000,
    outputDir: './node_modules/.cache/playwright-phase2-results',
    use: {
        ...base.use,
        baseURL: process.env.PW_BASE_URL || 'http://127.0.0.1:8001',
    },
});
