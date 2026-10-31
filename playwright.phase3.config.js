import base from './playwright.phase2.config.js';
import { defineConfig } from '@playwright/test';

export default defineConfig({
    ...base,
    testMatch: ['**/phase3.spec.js'],
    outputDir: './node_modules/.cache/playwright-phase3-results',
});
