describe('Plugin Licenses', () => {
    beforeEach(function() {
        cy.login()
    })

    it("should show the plugin licenses page", function () {
        cy.visit('/licenses/plugins')
        cy.get('h1').contains("Plugins")
    })
})
