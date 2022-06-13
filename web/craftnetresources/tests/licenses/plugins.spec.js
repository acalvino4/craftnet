const {test, expect} = require('@playwright/test');

test('Shoud show the plugin licenses page', async ({page, baseURL}) => {
  await page.goto(baseURL + '/licenses/plugins');
  const title = page.locator('h1');
  await expect(title).toHaveText('Plugins');
});
