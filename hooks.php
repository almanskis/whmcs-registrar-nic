<?php
/**
 * WHMCS NIC Registrar Module Hooks File
 */

require __DIR__ . '/lib/HooksHelper.php';

use Module\Registrar\Nic\HooksHelper AS Helper;

// Invoice created. Stop domain from being renew before the invoice is paid.
add_hook('InvoiceCreated', 1, function($vars) {
    $domains       = Helper::getInvoiceDomains($vars['invoiceid']);
    $renewalStatus = 'false';

    if (!empty($domains)) {
        Helper::updateDomainsRenewalStatus($domains, $renewalStatus);
    }
});

// Restore renew status
add_hook('InvoicePaid', 1, function($vars) {
    $domains       = Helper::getInvoiceDomains($vars['invoiceid']);
    $renewalStatus = 'true';

    if (!empty($domains)) {
        Helper::updateDomainsRenewalStatus($domains, $renewalStatus);
    }
});

// Stop domain from being renew
add_hook('InvoiceRefunded', 1, function($vars) {
    $domains       = Helper::getInvoiceDomains($vars['invoiceid']);
    $renewalStatus = 'false';

    if (!empty($domains)) {
        Helper::updateDomainsRenewalStatus($domains, $renewalStatus);
    }
});

// Stop domain from being renew
add_hook('InvoiceUnpaid', 1, function($vars) {
    $domains       = Helper::getInvoiceDomains($vars['invoiceid']);
    $renewalStatus = 'false';

    if (!empty($domains)) {
        Helper::updateDomainsRenewalStatus($domains, $renewalStatus);
    }
});

// Stop domain from being renew
add_hook('InvoiceCancelled', 1, function($vars) {
    $domains       = Helper::getInvoiceDomains($vars['invoiceid']);
    $renewalStatus = 'false';

    if (!empty($domains)) {
        Helper::updateDomainsRenewalStatus($domains, $renewalStatus);
    }
});