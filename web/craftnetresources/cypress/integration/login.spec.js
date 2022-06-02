describe('Login', () => {
    it("should show the login form", function () {
        cy.visit('/')
        cy.get('h1').contains("Sign into Craft Console")
    })
})
