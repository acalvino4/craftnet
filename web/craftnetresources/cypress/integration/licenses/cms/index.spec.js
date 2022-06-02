describe('CMS Licenses Index', () => {
    beforeEach(function() {
        cy.login()
    })

    it("should show the CMS licenses page", function () {
        cy.visit('/licenses/cms')
        cy.get('h1').contains("Craft CMS")
    })
})
