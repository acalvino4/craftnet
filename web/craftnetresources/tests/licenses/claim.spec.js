const {test, expect} = require('@playwright/test');

test('Shoud show the claim license page', async ({page, baseURL}) => {
  await page.goto(baseURL + '/licenses/claim');
  const title = page.locator('h1');
  await expect(title).toHaveText('Claim license');
});
