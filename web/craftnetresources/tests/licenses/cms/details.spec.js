const {test, expect} = require('@playwright/test');

test('Shoud show the details page for a CMS license', async ({ page, baseURL }) => {
    await page.goto(baseURL + '/licenses/cms');

    await page.click('table tbody tr:first-child td:first-child a');


    const checkPaneTitle = async (containerSelector, title) => {
        const licenseDetailsTitle = page.locator(containerSelector);
        await expect(licenseDetailsTitle).toContainText(title);
    }

    await checkPaneTitle('.license-details h2', 'License Details');
    await checkPaneTitle('.updates h2', 'Updates');
    await checkPaneTitle('.plugin-licenses h2', 'Plugin Licenses');
    await checkPaneTitle('.activity h2', 'Activity');
    await checkPaneTitle('.danger-zone h2', 'Danger Zone');
});
