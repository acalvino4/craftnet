const {test, expect} = require('@playwright/test');
/*const {waitForPluginStore} = require('./.playwright/utils.js');

const waitForDiscoverPage = async ({page}) => {
    await Promise.all([
        // Wait for features sections to be loaded
        page.waitForResponse(response => response.url().includes('//api.craftcms.com/v1/plugin-store/featured-sections')),

        // Wait for active trials to be loaded
        page.waitForResponse(response => response.url().includes('//api.craftcms.com/v1/cms-editions')),
        page.waitForResponse(response => response.url().includes('//api.craftcms.com/v1/plugin-store/plugins-by-handles')),
    ]);
}*/

test('Shoud show the Craft CMS licenses page', async ({page, baseURL}) => {
  await page.goto(baseURL + '/licenses/cms');
  const title = page.locator('h1');
  await expect(title).toHaveText('Craft CMS');
});
