const {test, expect} = require('@playwright/test');

test('Shoud show the profile page', async ({ page, baseURL }) => {
    await page.goto(baseURL + '/settings/profile');
    const title = page.locator('h1');
    await expect(title).toHaveText('Profile');
});
