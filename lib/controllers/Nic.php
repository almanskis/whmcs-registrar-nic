<?php

namespace Module\Registrar\Nic\Controllers;

require_once(__DIR__ . '/../NIC/Commands/autoload.php');
require_once(__DIR__ . '/../NIC/Client.php');

use Module\Registrar\Nic\Nic\Client AS NicClient;
use Module\Registrar\Nic\Nic\Exception AS NicException;

class NIC
{
    public static function login(array $requestData): NicClient
    {
        try {
            $client = new NicClient(
                $requestData['host'],
                $requestData['user'],
                $requestData['pass'],
                $requestData['debug'],
                $requestData['port'],
                $requestData['timeout'],
                $requestData['ssl'],
                $requestData['context'],
                $requestData['newPw'],
            );
            
            $client->login();

            return $client;
        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, 'login');
        }
    }

    public static function domainCheck(NicClient $client, string $domain): object
    {
        try {
            return (object)$client->domainCheck($domain);
        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), ['domain' => $domain], 'domain_check');
        }
    }

    public static function domainCreate(NicClient $client, array $requestData): object
    {
        try {
            return (object)$client->domainCreate(
                $requestData['domain'],
                $requestData['registrant'],
                $requestData['contactAdmin'],
                $requestData['contactTech'],
                $requestData['nameservers'],
                $requestData['period']
            );
        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, 'domain_create');
        }
    }

    public static function domainGetInfo(NicClient $client, array $requestData): object
    {
        try {
            return (object)$client->domainGetInfo($requestData['domain']);
        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, 'domain_info');
        }
    }

    public static function domainUpdate(NicClient $client, array $requestData): object
    {
        try {
            return (object)$client->domainUpdate(
                $requestData['domain'] ?? '',
                $requestData['addNs'] ?? [],
                $requestData['removeNs'] ?? [],
                $requestData['addContacts'] ?? [],
                $requestData['removeContacts'] ?? [],
                $requestData['newRegistrant'] ?? '',
                $requestData['renewalStatus'] ?? '',
                $requestData['renewalReason'] ?? ''
            );
        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, 'domain_update');
        }
    }

    public static function domainTransferRequest(NicClient $client, array $requestData): object
    {
        try {
            return (object)$client->domainTransferRequest(
                $requestData['domain'],
                $requestData['eppCode'],
                $requestData['nameservers'],
            );
        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, 'domain_transfer_request');
        }
    }

    public static function domainTransferCancel(NicClient $client, array $requestData): object
    {
        try {
            return (object)$client->domainTransferCancel($requestData['domain']);
        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, 'domain_transfer_cancel');
        }
    }

    public static function domainTransferApprove(NicClient $client, array $requestData): object
    {
        try {
            return (object)$client->domainTransferApprove($requestData['domain']);
        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, 'domain_transfer_approve');
        }
    }

    public static function domainTransferReject(NicClient $client, array $requestData): object
    {
        try {
            return (object)$client->domainTransferReject($requestData['domain']);
        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, 'domain_transfer_reject');
        }
    }

    public static function domainDelete(NicClient $client, string $domain): object
    {
        try {
            return (object)$client->domainDelete($domain);
        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), ['domain' => $domain], 'domain_delete');
        }
    }

    public static function contactCheck(NicClient $client, string $id): object
    {
        try {
            return (object)$client->contactCheck($id);
        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), ['id' => $id], 'contact_check');
        }
    }

    public static function contactCreate(NicClient $client, array $requestData): object
    {
        try {
            return (object)$client->contactCreate(
                $requestData['id'],
                $requestData['name'],
                $requestData['street'],
                $requestData['city'],
                $requestData['postCode'],
                $requestData['countryCode'],
                $requestData['phone'],
                $requestData['email'],
                $requestData['organization'],
                $requestData['vatNumber'],
                $requestData['companyId']
            );            
        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, 'contact_create');
        }
    }

    public static function contactGetInfo(NicClient $client, string $contactId): object
    {
        try {
            return (object)$client->contactGetInfo($contactId);
        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), ['id' => $contactId], 'contact_info');
        }
    }

    public static function contactUpdate(NicClient $client, array $requestData): object
    {
        try {
            return (object)$client->contactUpdate(
                $requestData['id'],
                $requestData['name'],
                $requestData['email'],
                $requestData['phone'],
                $requestData['street'],
                $requestData['city'],
                $requestData['postalCode'],
                $requestData['countryCode'],
                $requestData['organization'],
                $requestData['regNumber'],
                $requestData['vatNumber'],
            );
        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, 'contact_update');
        }
    }

    public static function contactDelete(NicClient $client, string $contactId): object
    {
        try {
            return (object)$client->contactDelete($contactId);
        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), ['id' => $contactId], 'contact_delete');
        }
    }
}
