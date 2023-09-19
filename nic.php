<?php
/**
 * WHMCS NIC registrar module for .LV domains registration
 */

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

require_once(__DIR__ . '/lib/Helper.php');
require_once(__DIR__ . '/lib/controllers/Nic.php');

use WHMCS\Carbon;
use WHMCS\Domain\Registrar\Domain;
use Module\Registrar\Nic\Helper;
use Module\Registrar\Nic\Controllers\NIC AS NicController;
use Module\Registrar\Nic\Nic\Exception AS NicException;

/**
 * Define module related metadata
 *
 * @return array
 */
function nic_MetaData()
{
    return [
        'DisplayName' => 'NIC (LV domains)',
        'APIVersion' => '1.0',
    ];
}

/**
 * Define registrar configuration options.
 *
 * @return array
 */
function nic_getConfigArray()
{
    return [
        'contactsPreffix' => [
            'FriendlyName' => 'Contacts Preffix',
            'Type'         => 'text',
            'Size'         => '5',
            'Default'      => '',
            'Description'  => 'Unique contacts preffix to use for client contacts creation. Up to 5 characters',
        ],
        'productionUrl' => [
            'FriendlyName' => 'Production URL',
            'Type'         => 'text',
            'Size'         => '25',
            'Default'      => 'epp.nic.lv',
            'Description'  => 'Production URL. Default is epp.nic.lv',
        ],
        'productionUid' => [
            'FriendlyName' => 'Production EPP username',
            'Type'         => 'text',
            'Size'         => '25',
            'Default'      => '',
            'Description'  => 'Production EPP username',
        ],
        'productionPassword' => [
            'FriendlyName' => 'Production EPP Password',
            'Type'         => 'password',
            'Size'         => '25',
            'Default'      => '',
            'Description'  => 'Production EPP password',
        ],
        'productionPort' => [
            'FriendlyName' => 'Production Port',
            'Type'         => 'text',
            'Default'      => '700',
            'Description'  => 'Production connection port. Default is 700',
        ],
        'productionPrimaryPerson' => [
            'FriendlyName' => 'Production Primary Person',
            'Type'         => 'text',
            'Default'      => '',
            'Description'  => 'Production Primary Person contact',
        ],
        'productionHostmaster' => [
            'FriendlyName' => 'Production Hostmaster',
            'Type'         => 'text',
            'Default'      => '',
            'Description'  => 'Production Hostmaster contact',
        ],
        'sandboxUrl' => [
            'FriendlyName' => 'Sandbox URL',
            'Type'         => 'text',
            'Size'         => '25',
            'Default'      => 'epp-sandbox.nic.lv',
            'Description'  => 'Sandbox URL. Default is epp-sandbox.nic.lv',
        ],
        'sandboxUid' => [
            'FriendlyName' => 'Sandbox EPP username',
            'Type'         => 'text',
            'Size'         => '25',
            'Default'      => '',
            'Description'  => 'Sandbox EPP username',
        ],
        'sandboxPassword' => [
            'FriendlyName' => 'Sandbox EPP Password',
            'Type'         => 'password',
            'Size'         => '25',
            'Default'      => '',
            'Description'  => 'Sandbox EPP password',
        ],
        'sandboxPort' => [
            'FriendlyName' => 'Sandbox Port',
            'Type'         => 'text',
            'Default'      => '700',
            'Description'  => 'Sandbox connection port. Default is 700',
        ],
        'sandboxPrimaryPerson' => [
            'FriendlyName' => 'Sandbox Primary Person',
            'Type'         => 'text',
            'Default'      => '',
            'Description'  => 'Sandbox Primary Person contact',
        ],
        'sandboxHostmaster' => [
            'FriendlyName' => 'Sandbox Hostmaster',
            'Type'         => 'text',
            'Default'      => '',
            'Description'  => 'Sandbox Hostmaster contact',
        ],
        'debug' => [
            'FriendlyName' => 'Debug Mode',
            'Type'         => 'yesno',
            'Description'  => 'Tick to enable debug',
        ],
        'ssl' => [
            'FriendlyName' => 'SSL',
            'Type'         => 'yesno',
            'Description'  => 'Tick to use SSL',
        ],
        'accountMode' => [
            'FriendlyName' => 'Account Mode',
            'Type'         => 'dropdown',
            'Options'      => [
                'production' => 'Production',
                'sandbox'    => 'Sandbox',
            ],
            'Description' => 'Account mode to work with',
        ],
    ];
}

/**
 * Admin Area Custom Button Array.
 *
 * @return array
 */
function nic_AdminCustomButtonArray() {
    return [
        'Cancel Renewal'   => 'RenewDomainCancel',
        'Transfer Cancel'  => 'TransferDomainCancel',
        'Transfer Approve' => 'TransferDomainApprove',
        'Transfer Reject'  => 'TransferDomainReject',
        'Sync'             => 'Sync'
    ];
}

/**
 * Client Area Custom Button Array.
 *
 * @return array
 */
function nic_ClientAreaCustomButtonArray()
{
    return [];
}

/**
 * Client Area Allowed Functions.
 *
 * @return array
 */
function nic_ClientAreaAllowedFunctions()
{
    return [];
}

/**
 * Register a domain.
 *
 * A minimum length of a domain name is 2 characters.
 * 
 * 2023-07-20
 * Only available period is 1 year
 * 
 * @return array
 */
function nic_RegisterDomain($params)
{
    $helper = new Helper($params['accountMode'] === 'sandbox');

    $function = 'domain_register';

    $action      = $function;
    $requestData = $params;
    
    $registrarDetails = $helper->getRegistrarDetails($params);

    if (empty($registrarDetails)) {
        $message = 'Failed to get registrar details. Check if module has all values set.';

        $helper->logActivity($message);

        return ['error' => $message];
    }

    try {
        $action      = "{$function}__login";
        $requestData = $registrarDetails;
        $NicClient  = NicController::login($requestData);

        $registrantId = $helper->getClientTypeId($params['userid'], 'registrant');

        if (empty($registrantId)) {
            $helper->setContactPreffix($registrarDetails['preffix']);

            $action      = "{$function}__contact_create";
            $requestData = $helper->formatClientDetails($params);

            $response = NicController::contactCreate($NicClient, $requestData);
            $helper->logModuleActions($action, $requestData, $response);

            $registrantId = $response->id;

            $helper->saveContactRegistrant($params['userid'], $registrantId);
        }

        $action         = "{$function}__registrant_get_details";
        $requestData    = ['id' => $registrantId];
        $contactDetails = NicController::contactGetInfo($NicClient, $registrantId);

        $contactAdmin = $registrarDetails['contactHostmaster'];

        if (empty($contactDetails->org)) {
            $contactAdmin = $registrantId;
        }
        
        $action      = "{$function}__domain_create";
        $nameservers = [$params['ns1'] => '', $params['ns2'] => '', $params['ns3'] => '', $params['ns4'] => '', $params['ns5'] => ''];

        $requestData = [
            'domain'       => $params['domain'],
            'registrant'   => $registrantId,
            'contactAdmin' => $contactAdmin,
            'contactTech'  => $registrarDetails['contactHostmaster'],
            'nameservers'  => $nameservers,
            'period'       => $params['regperiod'],
        ];

        $response = NicController::domainCreate($NicClient, $requestData);
        $helper->logModuleActions($action, $requestData, $response);

        // Domain is not registered immediately, so domain info requests are needed to know when domain is registered.
        // Domain registrations on nic.lv are processed every 1 minute
        $action      = "{$function}__domain_info";
        $requestData = ['domain' => $params['domain']];

        $domainStatus  = 'pendingCreate';
        $requestsCount = 0;

        while ($domainStatus == 'pendingCreate') {
            $response = NicController::domainGetInfo($NicClient, $requestData);
            
            $domainStatus = key($response->statuses);

            $requestsCount++;
            if ($requestsCount > 15) {
                break;
            }

            sleep(5);
        }

        $helper->logModuleActions($action, $requestData, $response);

        $NicClient->logout();

        if ($domainStatus == 'pendingCreate') {
            return ['error' => 'Domain registration timeout - Unable to register the domain'];
        } else {
            $helper->updateDomainDueDate($params['domainid'], $response->exDate);
            $helper->updateDomainNextInvoiceDate($params['domainid'], $response->exDate);
        }
    } catch (NicException $e) {
        $helper->handleNicException($e, $action);

        return ['error' => $e->getMessage()];
    } catch (Exception $e) {
        $helper->logModuleActions($action, $requestData, $e->getMessage());

        return ['error' => $e->getMessage()];
    }

    return ['success' => true];
}

/**
 * Initiate domain transfer.
 *
 * @return array
 */
function nic_TransferDomain($params)
{
    $helper = new Helper($params['accountMode'] === 'sandbox');

    $function = 'domain_transfer';

    $action      = $function;
    $requestData = $params;
    
    $registrarDetails = $helper->getRegistrarDetails($params);

    if (empty($registrarDetails)) {
        $message = 'Failed to get registrar details. Check if module has all values set.';

        $helper->logActivity($message);

        return ['error' => $message];
    }

    try {
        $requestData = $registrarDetails;
        $action      = "{$function}__login";
        $NicClient  = NicController::login($requestData);

        $nameservers = [$params['ns1'], $params['ns2'], $params['ns3'], $params['ns4'], $params['ns5']];

        $action      = "{$function}__transfer_request";
        $requestData = [
            'domain'      => $params['domain'],
            'eppCode'     => $params['eppcode'],
            'nameservers' => $nameservers,
        ];

        $response = NicController::domainTransferRequest($NicClient, $requestData);
        $helper->logModuleActions($action, $requestData, $response);

        $NicClient->logout();

    } catch (NicException $e) {
        $helper->handleNicException($e, $action);

        return ['error' => $e->getMessage()];
        
    } catch (Exception $e) {
        $helper->logModuleActions($action, $requestData, $e->getMessage());

        return ['error' => $e->getMessage()];
    }

    return ['success' => true];
}

/**
 * Cancel domain transfer.
 *
 * @return array
 */
function nic_TransferDomainCancel($params)
{
    $helper = new Helper($params['accountMode'] === 'sandbox');

    $function = 'domain_transfer_cancel';

    $action      = $function;
    $requestData = $params;
    
    $registrarDetails = $helper->getRegistrarDetails($params);

    if (empty($registrarDetails)) {
        $message = 'Failed to get registrar details. Check if module has all values set.';

        $helper->logActivity($message);

        return ['error' => $message];
    }

    try {
        $requestData = $registrarDetails;
        $action      = "{$function}__login";
        $NicClient  = NicController::login($requestData);

        $action      = "{$function}__transfer_cancel";
        $requestData = ['domain' => $params['domain']];

        $response = NicController::domainTransferCancel($NicClient, $requestData);
        $helper->logModuleActions($action, $requestData, $response);

        $NicClient->logout();

        $helper->updateDomainStatus($params['domainid'], 'Cancelled');

    } catch (NicException $e) {
        $helper->handleNicException($e, $action);

        return ['error' => $e->getMessage()];

    } catch (Exception $e) {
        $helper->logModuleActions($action, $requestData, $e->getMessage());

        return ['error' => $e->getMessage()];
    }

    return ['success' => true];
}

/**
 * Approve domain transfer.
 *
 * @return array
 */
function nic_TransferDomainApprove($params)
{
    $helper = new Helper($params['accountMode'] === 'sandbox');

    $function = 'domain_transfer_approve';

    $action      = $function;
    $requestData = $params;
    
    $registrarDetails = $helper->getRegistrarDetails($params);

    if (empty($registrarDetails)) {
        $message = 'Failed to get registrar details. Check if module has all values set.';

        $helper->logActivity($message);

        return ['error' => $message];
    }

    try {
        $requestData = $registrarDetails;
        $action      = "{$function}__login";
        $NicClient  = NicController::login($requestData);

        $action      = "{$function}__transfer_approve";
        $requestData = ['domain' => $params['domain']];

        $response = NicController::domainTransferApprove($NicClient, $requestData);
        $helper->logModuleActions($action, $requestData, $response);

        $response = NicController::domainGetInfo($NicClient, $requestData);

        $NicClient->logout();

        if (empty($response->registrant)) {
            $domainStatus = 'Transferred Away';
            $helper->updateDomainStatus($params['domainid'], $domainStatus);
        }

    } catch (NicException $e) {
        $helper->handleNicException($e, $action);

        return ['error' => $e->getMessage()];

    } catch (Exception $e) {
        $helper->logModuleActions($action, $requestData, $e->getMessage());

        return ['error' => $e->getMessage()];
    }

    return ['success' => true];
}

/**
 * Reject domain transfer.
 *
 * @return array
 */
function nic_TransferDomainReject($params)
{
    $helper = new Helper($params['accountMode'] === 'sandbox');

    $function = 'domain_transfer_reject';

    $action      = $function;
    $requestData = $params;
    
    $registrarDetails = $helper->getRegistrarDetails($params);

    if (empty($registrarDetails)) {
        $message = 'Failed to get registrar details. Check if module has all values set.';

        $helper->logActivity($message);

        return ['error' => $message];
    }

    try {
        $requestData = $registrarDetails;
        $action      = "{$function}__login";
        $NicClient  = NicController::login($requestData);

        $action      = "{$function}__transfer_reject";
        $requestData = ['domain' => $params['domain']];

        $response = NicController::domainTransferReject($NicClient, $requestData);
        $helper->logModuleActions($action, $requestData, $response);

        $NicClient->logout();

    } catch (NicException $e) {
        $helper->handleNicException($e, $action);

        return ['error' => $e->getMessage()];


    } catch (Exception $e) {
        $helper->logModuleActions($action, $requestData, $e->getMessage());

        return ['error' => $e->getMessage()];
    }

    return ['success' => true];
}

function nic_RenewDomainCancel($params)
{
    $helper = new Helper($params['accountMode'] === 'sandbox');

    $function = 'domain_renew_cancel';

    $action      = $function;
    $requestData = $params;
    
    $registrarDetails = $helper->getRegistrarDetails($params);

    if (empty($registrarDetails)) {
        $message = 'Failed to get registrar details. Check if module has all values set.';

        $helper->logActivity($message);

        return ['error' => $message];
    }

    try {
        $requestData = $registrarDetails;
        $action      = "{$function}__login";
        $NicClient  = NicController::login($requestData);

        $action      = "{$function}__renew_cancel";
        $requestData = [
            'domain'        => $params['domain'],
            'renewalStatus' => 'false'
        ];

        $response = NicController::domainUpdate($NicClient, $requestData);
        $helper->logModuleActions($action, $requestData, $response);

        $NicClient->logout();

    } catch (NicException $e) {
        $helper->handleNicException($e, $action);

        return ['error' => $e->getMessage()];

    } catch (Exception $e) {
        $helper->logModuleActions($action, $requestData, $e->getMessage());

        return ['error' => $e->getMessage()];
    }

    return ['success' => true];
}

/**
 * Fetch Domain Information
 * 
 * This function replaces GetNameservers and GetRegistrarLock
 *
 * @return array
 */
function nic_GetDomainInformation($params)
{
    $helper = new Helper($params['accountMode'] === 'sandbox');

    $function = 'domain_get_information';

    $action      = $function;
    $requestData = $params;

    $registrarDetails = $helper->getRegistrarDetails($params);

    if (empty($registrarDetails)) {
        $message = 'Failed to get registrar details. Check if module has all values set.';

        $helper->logActivity($message);

        return ['error' => $message];
    }

    $nameservers = [
        'ns1' => '',
        'ns2' => '',
        'ns3' => '',
        'ns4' => '',
        'ns5' => ''
    ];

    $Domain = new Domain();

    try {
        $action      = "{$function}__login";
        $requestData = $registrarDetails;
        $NicClient  = NicController::login($requestData);

        $action      = "{$function}__domain_info";
        $requestData = ['domain' => $params['domain']];

        $domainInfo  = NicController::domainGetInfo($NicClient, $requestData);
        $nameservers = $helper->getNameServersFromDomainInfo($nameservers, $domainInfo, 'value');
        $helper->logModuleActions($action, $requestData, $domainInfo);

        $NicClient->logout();

        $registrantId = $helper->getClientTypeId($params['userid'], 'registrant');
        if (empty($registrantId)) {
            $helper->saveContactRegistrant($params['userid'], $domainInfo->registrant);
        }

        $exDateYmd = (new DateTime($domainInfo->exDate))->format('Y-m-d');

        $Domain->setDomain($domainInfo->name)
            ->setNameservers($nameservers)
            ->setRegistrationStatus('active')
            ->setTransferLock($domainInfo->statuses['serverTransferProhibited'] ? true : false)
            ->setExpiryDate(Carbon::createFromFormat('Y-m-d', $exDateYmd));

    } catch (NicException $e) {
        $helper->handleNicException($e, $action);

        return ['error' => $e->getMessage()];

    } catch (Exception $e) {
        $helper->logModuleActions($action, $requestData, $e->getMessage());

        return ['error' => $e->getMessage()];
    }

    return $Domain;
}

/**
 * Save nameserver changes.
 *
 * @return array
 */
function nic_SaveNameservers($params)
{
    $helper = new Helper($params['accountMode'] === 'sandbox');

    $function = 'nameservers_save';

    $action      = $function;
    $requestData = $params;

    $registrarDetails = $helper->getRegistrarDetails($params);

    if (empty($registrarDetails)) {
        $message = 'Failed to get registrar details. Check if module has all values set.';

        $helper->logActivity($message);

        return ['error' => $message];
    }

    $current_nameservers = [
        '' => [],
        '' => [],
        '' => [],
        '' => [],
        '' => []
    ];

    $updated_nameservers = [
        $params['ns1'] => '',
        $params['ns2'] => '',
        $params['ns3'] => '',
        $params['ns4'] => '',
        $params['ns5'] => ''
    ];

    try {
        $action      = "{$function}__login";
        $requestData = $registrarDetails;
        $NicClient  = NicController::login($requestData);

        $action      = "{$function}__domain_info";
        $requestData = ['domain' => $params['domain']];

        $domainInfo  = NicController::domainGetInfo($NicClient, $requestData);
        $nameservers = $helper->getNameServersFromDomainInfo($current_nameservers, $domainInfo);

        $current_nameservers = $helper->removeEmptyKeyValues($nameservers);
        $updated_nameservers = $helper->removeEmptyKeyValues($updated_nameservers);

        $action      = "{$function}__nameservers_update";
        $requestData = [
            'domain'   => $params['domain'],
            'addNs'    => $helper->findAddedNameservers($current_nameservers, $updated_nameservers),
            'removeNs' => $helper->findDeletedNameservers($current_nameservers, $updated_nameservers)
        ];

        $response = NicController::domainUpdate($NicClient, $requestData);
        $helper->logModuleActions($action, $requestData, $response);

        $NicClient->logout();

    } catch (NicException $e) {
        $helper->handleNicException($e, $action);

        return ['error' => $e->getMessage()];

    } catch (Exception $e) {
        $helper->logModuleActions($action, $requestData, $e->getMessage());

        return ['error' => $e->getMessage()];
    }

    return $nameservers;
}

/**
 * Get the current WHOIS Contact Information.
 *
 * @return array
 */
function nic_GetContactDetails($params)
{
    $helper = new Helper($params['accountMode'] === 'sandbox');
    
    $function = 'contact_get_details';

    $action      = $function;
    $requestData = $params;

    $registrarDetails = $helper->getRegistrarDetails($params);

    if (empty($registrarDetails)) {
        $message = 'Failed to get registrar details. Check if module has all values set.';

        $helper->logActivity($message);

        return ['error' => $message];
    }

    $contactsDetails = [];

    try {
        $action      = "{$function}__login";
        $requestData = $registrarDetails;
        $NicClient  = NicController::login($requestData);

        $action       = "{$function}__domain_info";
        $requestData  = ['domain' => $params['domain']];
        $domainInfo   = NicController::domainGetInfo($NicClient, $requestData);
        $registrantId = $domainInfo->registrant;

        $action                        = "{$function}__registrant_details";
        $requestData                   = ['id' => $registrantId];
        $registrantDetails             = NicController::contactGetInfo($NicClient, $requestData['id']);
        $contactsDetails['Registrant'] = $helper->formatContactDetails($registrantDetails);
        
        $helper->logModuleActions($action, $requestData, $registrantDetails);

        $NicClient->logout();

    } catch (NicException $e) {
        $helper->handleNicException($e, $action);

        return ['error' => $e->getMessage()];

    } catch (\Exception $e) {
        $helper->logModuleActions($action, $requestData, $e->getMessage());

        return ['error' => $e->getMessage()];
    }

    return $contactsDetails;
}

/**
 * Update the WHOIS Contact Information for a given domain.
 *
 * @return array
 */
function nic_SaveContactDetails($params)
{
    $helper = new Helper($params['accountMode'] === 'sandbox');

    $function = 'contact_save_details';

    $action      = $function;
    $requestData = $params;

    $registrarDetails = $helper->getRegistrarDetails($params);

    if (empty($registrarDetails)) {
        $message = 'Failed to get registrar details. Check if module has all values set.';

        $helper->logActivity($message);

        return ['error' => $message];
    }

    try {
        $action       = "{$function}__login";
        $requestData  = $registrarDetails;
        $NicClient = NicController::login($requestData);

        $action       = "{$function}__domain_info";
        $requestData  = ['domain' => $params['domain']];
        $domainInfo   = NicController::domainGetInfo($NicClient, $requestData);
        $registrantId = $domainInfo->registrant;

        $action      = "{$function}__registrant";
        $requestData = $helper->formatContactDetailsforUpdate($registrantId, $params['contactdetails']['Registrant']);
        $response    = NicController::contactUpdate($NicClient, $requestData);
        $helper->logModuleActions($action, $requestData, $response);

        $NicClient->logout();

    } catch (NicException $e) {
        $helper->handleNicException($e, $action);

        return ['error' => $e->getMessage()];

    } catch (Exception $e) {
        $helper->logModuleActions($action, $requestData, $e->getMessage());

        return ['error' => $e->getMessage()];
    }
}

/**
 * Request EEP Code.
 *
 * @return array
 *
 */
function nic_GetEPPCode($params)
{
    $helper = new Helper($params['accountMode'] === 'sandbox');

    $function = 'get_epp_code';

    $action      = $function;
    $requestData = $params;
    
    $registrarDetails = $helper->getRegistrarDetails($params);

    if (empty($registrarDetails)) {
        $message = 'Failed to get registrar details. Check if module has all values set.';

        $helper->logActivity($message);

        return ['error' => $message];
    }

    try {
        $requestData = $registrarDetails;
        $action      = "{$function}__login";
        $NicClient  = NicController::login($requestData);

        $action       = "{$function}__domain_info";
        $requestData  = ['domain' => $params['domain']];
        $domainInfo   = NicController::domainGetInfo($NicClient, $requestData);
        $helper->logModuleActions($action, $requestData, $domainInfo);

        $NicClient->logout();

        if (!empty($domainInfo->eppCode)) {
            return ['eppcode' => $domainInfo->eppCode];
        }

    } catch (NicException $e) {
        $helper->handleNicException($e, $action);

        return ['error' => $e->getMessage()];

    } catch (Exception $e) {
        $helper->logModuleActions($action, $requestData, $e->getMessage());

        return ['error' => $e->getMessage()];
    }

    return ['success' => 'success'];
}

/**
 * Delete Domain.
 *
 * @return array
 */
function nic_RequestDelete($params)
{
    $helper = new Helper($params['accountMode'] === 'sandbox');

    $function = "domain_delete";

    $action      = $function;
    $requestData = $params;

    $registrarDetails = $helper->getRegistrarDetails($params);

    if (empty($registrarDetails)) {
        $message = 'Failed to get registrar details. Check if module has all values set.';

        $helper->logActivity($message);

        return ['error' => $message];
    }

    try {
        $requestData = $registrarDetails;
        $action      = "{$function}__login";
        $NicClient  = NicController::login($requestData);

        $action      = "{$function}__delete_request";
        $requestData = ['domain' => $params['domain']];
        $response    = NicController::domainDelete($NicClient, $requestData['domain']);
        $helper->logModuleActions($action, $requestData, $response);

        $NicClient->logout();

    } catch (NicException $e) {
        $helper->handleNicException($e, $action);

        return ['error' => $e->getMessage()];

    } catch (Exception $e) {
        $helper->logModuleActions($action, $requestData, $e->getMessage());

        return ['error' => $e->getMessage()];
    }

    return ['success' => 'success'];
}

/**
 * Sync Domain Status & Expiration Date.
 *
 * @return array
 */
function nic_Sync($params)
{
    $helper = new Helper($params['accountMode'] === 'sandbox');

    $function = 'sync';

    $action      = $function;
    $requestData = $params;

    $registrarDetails = $helper->getRegistrarDetails($params);

    if (empty($registrarDetails)) {
        $message = 'Failed to get registrar details. Check if module has all values set.';

        $helper->logActivity($message);

        return ['error' => $message];
    }

    try {
        $requestData = $registrarDetails;
        $action      = "{$function}__login";
        $NicClient  = NicController::login($requestData);

        $action       = "{$function}__domain_info";
        $requestData  = ['domain' => $params['domain']];
        $domainInfo   = NicController::domainGetInfo($NicClient, $requestData);
        $helper->logModuleActions($action, $requestData, $domainInfo);

        $NicClient->logout();

        $domainStatus = $helper->formatDomainStatus(key($domainInfo->statuses));

        $helper->updateDomainExpiryDate($params['domainid'], $domainInfo->exDate);
        $helper->updateDomainDueDate($params['domainid'], $domainInfo->exDate);
        $helper->updateDomainNextInvoiceDate($params['domainid'], $domainInfo->exDate);
        $helper->updateDomainStatus($params['domainid'], $domainStatus);

        return [
            'success' => true,
            'message' => '(Warning) You must refresh the page to see the changes'
        ];

    } catch (NicException $e) {
        $helper->handleNicException($e, $action);

        return ['error' => $e->getMessage()];

    } catch (Exception $e) {
        $helper->logModuleActions($action, $requestData, $e->getMessage());

        return ['error' => $e->getMessage()];
    }

    return ['success' => 'success'];
}

/**
 * Client Area Output.
 *
 * @return string HTML Output
 */
function nic_ClientArea($params)
{
    return '';
}
