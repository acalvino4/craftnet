const {test, expect} = require('@playwright/test');

test('Shoud show the organizations page', async ({ page, baseURL }) => {
    await page.goto(baseURL + '/settings/organizations');
    const title = page.locator('h1');
    await expect(title).toHaveText('Organizations');
});
