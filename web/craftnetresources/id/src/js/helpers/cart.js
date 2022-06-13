export default {

  getCartItemsData(cart) {
    if (!cart) {
      return []
    }

    let lineItems = []

    for (let i = 0; i < cart.lineItems.length; i++) {
      let lineItem = cart.lineItems[i]

      switch (lineItem.purchasable.type) {
        case 'plugin-edition': {
          lineItems.push({
            id: lineItem.id,
            type: lineItem.purchasable.type,
            plugin: lineItem.purchasable.plugin.handle,
            edition: lineItem.purchasable.handle,
            autoRenew: lineItem.options.autoRenew,
            expiryDate: lineItem.options.expiryDate,
          })
          break
        }

        case 'cms-edition': {
          const item = {
            id: lineItem.id,
            type: lineItem.purchasable.type,
            edition: lineItem.purchasable.handle,
            autoRenew: lineItem.options.autoRenew,
            expiryDate: lineItem.options.expiryDate,
          }

          let licenseKey = lineItem.options.licenseKey

          if (licenseKey && licenseKey.substr(0, 3) !== 'new') {
            item.licenseKey = licenseKey
          }

          lineItems.push(item)

          break
        }

        case 'cms-renewal': {
          lineItems.push({
            id: lineItem.id,
            type: lineItem.purchasable.type,
            licenseKey: lineItem.options.licenseKey,
            expiryDate: lineItem.options.expiryDate,
          })
          break
        }

        case 'plugin-renewal': {
          lineItems.push({
            id: lineItem.id,
            type: lineItem.purchasable.type,
            licenseKey: lineItem.options.licenseKey,
            expiryDate: lineItem.options.expiryDate,
          })
          break
        }
      }
    }

    return lineItems
  },

}