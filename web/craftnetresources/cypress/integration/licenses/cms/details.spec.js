describe('CMS Licenses Details', () => {
    beforeEach(function() {
        cy.login()
    })

    it("should show the CMS license details", function () {
        cy.visit('/licenses/cms')

        // Click the first license
        cy.get('table.VueTables__table tbody tr:first-child td:first-child a').click();

        // Check that the different panes are present
        cy.get('h2').contains("License Details")
        cy.get('h2').contains("Updates")
        cy.get('h2').contains("Plugin Licenses")
        cy.get('h2').contains("Danger Zone")
    })
})
