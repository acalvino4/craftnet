const {test, expect} = require('@playwright/test');

test('Shoud show the developer support page', async ({page, baseURL}) => {
  await page.goto(baseURL + '/settings/developer-support');
  const title = page.locator('h1');
  await expect(title).toHaveText('Developer Support');
});
