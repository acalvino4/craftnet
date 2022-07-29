{% macro showLicense(license, user) -%}
    {{ license.getEdition().getDescription() }}
    {%- if user %} [**`{{ license.getShortKey() }}`**]({{ license.getEditUrl() }})
    {%- else %} **`{{ license.getShortKey() }}`**
    {%- endif %}
    {%- set domain = license.getDomain() %}
    {%- if domain %} ({{ domain }}){% endif %}
{%- endmacro %}

{% from _self import showLicense %}
{% set user = user ?? null %}

Hey {{ user.friendlyName ?? 'there' }},

{% if renewLicenses|length %}
{% set pl = renewLicenses|length != 1 %}

{% if autoRenewFailed %}
The following {{ pl ? 'licenses were' : 'license was' }} due to be auto-renewed for another year of updates:
{% elseif redirect %}
The following {{ pl ? 'licenses are' : 'license is' }} due to be renewed for another year of updates:
{% else %}
The following {{ pl ? 'licenses have' : 'license has' }} been auto-renewed for another year of updates:
{% endif %}

{% for license in renewLicenses %}
- {{ showLicense(license, user) }}

{% endfor %}

{% if autoRenewFailed %}
However, there was an issue with your billing info which prevented the payment from going through. Please go to the following URL to fix your billing info for future payments:<br>
<https://id.craftcms.com/account/billing>
{% elseif redirect %}
However, your bank requires further action. Please go to the following URL to complete the renewal:<br>
<{{ redirect }}>
{% endif %}
{% endif %}

{% if expireLicenses|length %}
{% set pl = expireLicenses|length != 1 %}
The following {{ pl ? 'licenses have' : 'license has' }} expired:

{% for license in expireLicenses %}
- {{ showLicense(license, user) }}

{% endfor %}

{% if user %}
To ensure you don’t miss any updates, click on the license key {{ pl ? 'links' : 'link' }} above, and click the “Renew your license” button in the “Updates” section.
{% else %}
To ensure you don’t miss any updates, follow these steps:

1. Create a [Craft ID](https://id.craftcms.com) account (if you don’t already have one).
2. Go to the [Claim License](https://id.craftcms.com/licenses/claim) page to add {{ pl ? 'these licenses' : 'this license' }} to your account, using the “Claim licenses by your email address” feature.
3. From the license {{ pl ? 'screens' : 'screen' }}, click  and click the “Renew your license” button in the “Updates” section.
{% endif %}
{% endif %}

{{ note ?? '' }}

Have a great day!
