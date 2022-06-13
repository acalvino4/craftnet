const {test, expect} = require('@playwright/test');

test('Shoud show the account page', async ({page, baseURL}) => {
  await page.goto(baseURL + '/settings/account');
  const title = page.locator('h1');
  await expect(title).toHaveText('Account');
});
