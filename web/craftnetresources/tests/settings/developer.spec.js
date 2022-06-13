const {test, expect} = require('@playwright/test');

test('Shoud show the developer settings page', async ({page, baseURL}) => {
  await page.goto(baseURL + '/settings/developer');
  const title = page.locator('h1');
  await expect(title).toHaveText('Developer Settings');
});
