{% if lineItems|length > 1 %}{% set purchaseString = 'several of your plugins' %}{% else %}{% set purchaseString = 'one of your plugins' %}{% endif %}
Congratulations, {{ developer.title }}! Looks like someone just purchased {{ purchaseString }}:

---

{% for lineItem in lineItems %}

{{ lineItem.getDescription() ~ ' x ' ~ lineItem.qty }} - {{ (lineItem.total)|currency('USD') }}

{% endfor %}

---

You can access this and the rest of your plugin sales from your [Craft ID](https://id.craftcms.com) account.
