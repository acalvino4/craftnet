export default {
  leave() {
    console.log('TODO: Implement leaving an organization.')

    return new Promise((resolve) => {
      resolve()
    })
  },

  save() {
    console.log('TODO: Implement saving a new organization or updating an exising one.')
  },

  convertAccountToOrganization() {
    console.log('TODO: Implement converting an account to an organization. The user can’t convert his account until he leaves all the organizations he’s a member of.')
  },

  saveCurrentOrganizationId(organizationId) {
    return new Promise((resolve) => {
      localStorage.setItem('currentOrganizationId', organizationId)

      resolve({
        organizationId,
      })
    })
  },

  getCurrentOrganizationId() {
    return new Promise((resolve) => {
      const organizationId = parseInt(localStorage.getItem('currentOrganizationId'))
      resolve({
        organizationId,
      })
    })
  }
}
