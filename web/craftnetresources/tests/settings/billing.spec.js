const {test, expect} = require('@playwright/test');

test('Shoud show the billing page', async ({page, baseURL}) => {
  await page.goto(baseURL + '/settings/billing');
  const title = page.locator('h1');
  await expect(title).toHaveText('Billing');
});
