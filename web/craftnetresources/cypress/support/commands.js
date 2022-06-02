// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add('login', (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add('drag', { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add('dismiss', { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite('visit', (originalFn, url, options) => { ... })

Cypress.Commands.add("login", (loginName, password) => {
    if (!loginName) {
        loginName = Cypress.env('CP_LOGIN')
    }

    if (!password) {
        password = Cypress.env('CP_PASSWORD')
    }

    // cy.request('POST', Cypress.env('SITE_URL') + 'index.php?p=actions/users/login', {
    //     loginName,
    //     password
    // })

    cy.request({
        method: 'POST',
        url: Cypress.env('SITE_URL') + 'index.php?p=actions/users/login',
        form: true,
        body: {
            loginName,
            password
        },
        headers: {
            'content-type': 'multipart/form-data',
        },
    })

    // cy.visit('/')
    // cy.get('#loginName').type(loginName)
    // cy.get('#password').type(password)
    // cy.get('#login-form button[type=submit]').click()
    //
    // cy.wait(1000)
})