<?php

return [
    'autoSetPaymentSource' => true,

    // Since API loads a customer based on an email, we can't trust they own the customer's addresses, and shouldn't load last used addresses
    'autoSetNewCartAddresses' => true,
];
