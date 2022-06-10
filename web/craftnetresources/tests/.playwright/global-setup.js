// global-setup.js
const { chromium, expect } = require('@playwright/test');

module.exports = async config => {
    const { baseURL } = config.projects[0].use;
    const browser = await chromium.launch();
    const page = await browser.newPage({
        ignoreHTTPSErrors: true,
    });

    // https://api.craftcms.next/v1/carts/db5dfc3352c9257a98c8ad2d4da0de1a
    page.waitForResponse(response => response.url().includes(process.env.PLAYWRIGHT_API_URL + '/carts'))

    await page.goto(baseURL + '/login');

    await page.fill('#loginName', process.env.PLAYWRIGHT_USERNAME);
    await page.fill('#password', process.env.PLAYWRIGHT_PASSWORD);
    await page.click('button[type="submit"]');

    const title = page.locator('h1');
    await expect(title).toHaveText('Craft CMS');

    // Save signed-in state
    await page.context().storageState({ path: './tests/.playwright/authentication/admin.json' });
    await browser.close();
};