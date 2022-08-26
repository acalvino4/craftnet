export const checkRoute = ({currentOrganization, $router, $route, orgRouteName, userRouteName}) => {
  if (currentOrganization && $route.params.orgSlug !== currentOrganization.slug) {
    // Redirect to the right org profile if the org slug is different than the current org slug.
    $router.push({
      name: orgRouteName,
      params: {
        orgSlug: currentOrganization.slug,
      },
    })
  } else if (!currentOrganization && $route.params.orgSlug) {
    // Redirect to the user profile if the org slug is provided but there is no current org.
    $router.push({
      name: userRouteName,
    })
  }
}

export default {
  checkRoute
}