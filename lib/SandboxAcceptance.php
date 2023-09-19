<?php

namespace Module\Registrar\Nic;

require_once(__DIR__ . '/NIC/Client.php');
require_once(__DIR__ . '/controllers/autoload.php');

use Module\Registrar\Nic\Helper AS NicHelper;
use Module\Registrar\Nic\Controllers\NIC AS NicController;
use Module\Registrar\Nic\Nic\Exception AS NicException;

class SandboxAcceptance
{
    private $action           = __CLASS__;
    private $NicHelper        = null;
    private $NicClient        = null;
    private $registrarDetails = [];

    private $shortClientId = '';

    public function __construct($params)
    {
        $this->action = __CLASS__ . __FUNCTION__;

        $this->NicHelper = new NicHelper($params['accountMode'] === 'sandbox');

        $this->registrarDetails = $this->NicHelper->getRegistrarDetails($params);
        
        if (empty($this->registrarDetails)) {
            $message = 'Failed to get registrar details. Check if module has all values set.';
            $code    = '417'; // Http error code for 'Expectation Failed'

            throw new NicException($message, $code, '', $params, $this->action);
        }

        $this->registrarDetails['newPw'] = '_ACCEPT_TEST_';
    }

    public function setShortClientId(string $shortClientId): void
    {
        $this->shortClientId = $shortClientId;
    }

    public function performAcceptenceTest(): void
    {
        self::login();

        // Lavian legal person
        $lvLegalId    = "{$this->shortClientId}-lv-hldr";
        $contactCheck = self::contactCheck($lvLegalId);

        if ($contactCheck->isAvailable == '1') {
            self::contactCreate(
                $lvLegalId,
                'Sandbox LegalLV',
                'Sandbox 1',
                'Sandbox',
                'LV-1001',
                'LV',
                '+371.23456789',
                'lvsandbox@niclegal.lv',
                'Sandbox LV',
                'LV40001234567',
                '40001234567'
            );
        }

        // Latvian private person
        $lvPrivId     = "{$this->shortClientId}-lv-priv";
        $contactCheck = self::contactCheck($lvPrivId);

        if ($contactCheck->isAvailable == '1') {
            self::contactCreate(
                $lvPrivId,
                'Sandbox PrivLV',
                'Sandbox 1',
                'Sandbox',
                'LV-1001',
                'LV',
                '+371.03456789',
                'lvsandbox@nicpriv.lv',
                '',
                '',
                '111111-12345'
            );
        }

        // Non-Latvian legal person
        $nonLvId      = "{$this->shortClientId}-nonlv";
        $contactCheck = self::contactCheck($nonLvId);

        if ($contactCheck->isAvailable == '1') {
            self::contactCreate(
                $nonLvId,
                'Sandbox LegalNonLV',
                'Sandbox 2',
                'Sandboks',
                'LT-12321',
                'LT',
                '+370.61234567',
                'lvsandbox@nicnonlv.lv',
                'LegalNonLv',
                'LT100002345678',
                '987654321'
            );
        }

        // Required domain
        $domainName  = "{$this->shortClientId}-tūdaliņ.lv";
        $domainCheck = self::domainCheck($domainName);

        if ($domainCheck->{$domainName}['available'] === true) {
            $requestData = [
                'domain'      => $domainName,
                'registrant'  => $lvLegalId,
                'admin'       => $lvPrivId,
                'technical'   => '__DEFAULT__',
                'nameservers' => [
                    'ns.sistemos-tūdaliņ.lv' => '1.2.3.4',
                    'ns.somenet.lv'          => ''
                ],
                'period'      => '1'
            ];

            self::domainCreate(
                $requestData['domain'],
                $requestData['registrant'],
                $requestData['admin'],
                $requestData['technical'],
                $requestData['nameservers'],
                $requestData['period']
            );
        }

        // Since domain registrations are processed every minute,
        // and updates for domain are not available until domain is registered,
        // we wait until it is.
        $domainStatus  = 'pendingCreate';
        $requestsCount = 0;
        while ($domainStatus == 'pendingCreate') {
            $response = self::domainGetInfo($domainName);
            
            $domainStatus = key($response->statuses);

            $requestsCount++;
            if ($requestsCount > 14) {
                break;
            }

            sleep(5);
        }

        // Just in case registration fails.
        if ($domainStatus == 'pendingCreate') {
            self::logout();

            $message = 'Sandbox Acceptence test failed';
            $code    = '408'; // HTTP status code for 'Request Timeout'
            $reason  = "Registration of domain {$domainName} failed.";
            throw new NicException($message, $code, $reason, $requestData, $this->action);
        }

        // Update domain with required information
        $addNs         = ['ns2.xn--sistemos-tdali-jpc10d.lv' => '5.6.7.8'];
        $removeNs      = ['ns.xn--sistemos-tdali-jpc10d.lv' => ''];
        $renewalStatus = 'false';
        self::domainUpdate(
            $domainName,
            $addNs,
            $removeNs,
            [],
            [],
            $nonLvId,
            $renewalStatus,
            ''
        );

        // Get domain details after update
        self::domainGetInfo($domainName);

        // Perform simple contact details update
        self::contactUpdate($nonLvId, '', '', '+370.6111111');

        // Try deleting contact.
        // This has to fail but not interupt test progress.
        try {
            self::contactDelete($nonLvId);
        } catch (NicException $e) {
            NicHelper::logActivity("{$this->action} - {$e->getMessage()}");
        }

        // Try deleting contact.
        // This should not fail, but even on success it would interupt test progress.
        try {
            self::contactDelete($lvLegalId);
        } catch (NicException $e) {
            NicHelper::logActivity("{$this->action} - {$e->getMessage()}");
        }

        self::domainDelete($domainName);

        self::logout();
    }
    
    /**
     * Private methods for Sandbox Acceptence Test
     */

     /**
      * @throws NicException
      */
    private function login(): void
    {
        $requestData = null;

        try {
            $this->action = __CLASS__ . __FUNCTION__;

            $requestData = $this->registrarDetails;

            $this->NicClient = NicController::login($requestData);
        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, $this->action);
        } catch (\Exception $e) {
            throw new NicException($e->getMessage(), $e->getCode(), '', $requestData, $this->action);
        }
    }

    /**
      * @throws NicException
      */
    private function logout(): void
    {
        try {
            $this->action = __CLASS__ . __FUNCTION__;
            
            $this->NicClient->logout();

        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), [], $this->action);
        } catch (\Exception $e) {
            throw new NicException($e->getMessage(), $e->getCode(), '', [], $this->action);
        }
    }

    /**
      * @throws NicException
      */
    private function contactCheck(string $id): object
    {
        $this->action = __CLASS__ . __FUNCTION__;

        $requestData = ['id' => $id];

        try {
            return NicController::contactCheck($this->NicClient, $requestData['id']);
        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, $this->action);
        } catch (\Exception $e) {
            throw new NicException($e->getMessage(), $e->getCode(), '', $requestData, $this->action);
        }
    }

    /**
      * @throws NicException
      */
    private function contactCreate(
        string $id,
        string $name,
        string $street,
        string $city,
        string $postalCode,
        string $countryCode,
        string $phoneNumber,
        string $email,
        string $organization = '',
        string $vatNumber = '',
        string $companyId = ''
    ): void {
        $requestData = null;

        try {
            $this->action = __CLASS__ . __FUNCTION__;

            $requestData = [
                'id'           => $id,
                'name'         => $name,
                'street'       => $street,
                'city'         => $city,
                'postCode'     => $postalCode,
                'countryCode'  => $countryCode,
                'phone'        => $phoneNumber,
                'email'        => $email,
                'organization' => $organization,
                'vatNumber'    => $vatNumber,
                'companyId'    => $companyId
            ];

            NicController::contactCreate($this->NicClient, $requestData);
        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, $this->action);
        } catch (\Exception $e) {
            throw new NicException($e->getMessage(), $e->getCode(), '', $requestData, $this->action);
        }
    }

    private function contactGetInfo($id)
    {
        $requestData = null;

        try {   
            $this->action = __CLASS__ . __FUNCTION__;
            
            $requestData = ['id' => $id];
    
            NicController::contactGetInfo($this->NicClient, $requestData['id']);

        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, $this->action);
        } catch (\Exception $e) {
            throw new NicException($e->getMessage(), $e->getCode(), '', $requestData, $this->action);
        }
    }

    /**
      * @throws NicException
      */
    private function contactUpdate(
        string $id,
        $name        = '',
        $email       = '',
        $phone       = '',
        $street      = '',
        $city        = '',
        $postalCode  = '',
        $countryCode = ''
    ): void
    {
        $requestData = null;

        try {
            $this->action = __CLASS__ . __FUNCTION__;

            $requestData = [
                'id'          => $id,
                'name'        => $name,
                'email'       => $email,
                'phone'       => $phone,
                'street'      => $street,
                'city'        => $city,
                'postCode'    => $postalCode,
                'countryCode' => $countryCode
            ];

            NicController::contactUpdate($this->NicClient, $requestData);
        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, $this->action);
        } catch (\Exception $e) {
            throw new NicException($e->getMessage(), $e->getCode(), '', $requestData, $this->action);
        }
    }

    /**
      * @throws NicException
      */
    private function contactDelete(string $id): void
    {
        $requestData = null;

        try {   
            $this->action = __CLASS__ . __FUNCTION__;
            
            $requestData = ['id' => $id];
    
            NicController::contactDelete($this->NicClient, $requestData['id']);

        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, $this->action);
        } catch (\Exception $e) {
            throw new NicException($e->getMessage(), $e->getCode(), '', $requestData, $this->action);
        }
    }

    /**
      * @throws NicException
      */
    private function domainCheck(string $domain): object
    {
        $requestData = null;

        try {   
            $this->action = __CLASS__ . __FUNCTION__;
            
            $requestData = ['domain' => $domain];
    
            return NicController::domainCheck($this->NicClient, $requestData['domain']);

        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, $this->action);
        } catch (\Exception $e) {
            throw new NicException($e->getMessage(), $e->getCode(), '', $requestData, $this->action);
        }
    }

    /**
      * @throws NicException
      */
    private function domainCreate(
        string $domain,
        string $registrant,
        string $contactAdmin,
        string $contactTech,
        array $nameservers,
        string $period = '1'
    ): void {
        $requestData = null;

        try {   
            $this->action = __CLASS__ . __FUNCTION__;
            
            $requestData = [
                'domain'       => $domain,
                'registrant'   => $registrant,
                'contactAdmin' => $contactAdmin,
                'contactTech'  => $contactTech,
                'nameservers'  => $nameservers,
                'period'       => $period
            ];
    
            NicController::domainCreate($this->NicClient, $requestData);

        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, $this->action);
        } catch (\Exception $e) {
            throw new NicException($e->getMessage(), $e->getCode(), '', $requestData, $this->action);
        }
    }

    /**
      * @throws NicException
      */
    private function domainGetInfo(string $domain): object
    {
        $requestData = null;

        try {   
            $this->action = __CLASS__ . __FUNCTION__;
            
            $requestData = ['domain' => $domain];
    
            return NicController::domainGetInfo($this->NicClient, $requestData);

        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, $this->action);
        } catch (\Exception $e) {
            throw new NicException($e->getMessage(), $e->getCode(), '', $requestData, $this->action);
        }
    }

    /**
      * @throws NicException
      */
    private function domainUpdate(
        string $domain,
        array $addNs,
        array $removeNs,
        array $addContacts,
        array $removeContacts,
        string $newRegistrant,
        string $renewalStatus,
        string $renewalReason = ''
    ): void {
        $requestData = null;

        try {   
            $this->action = __CLASS__ . __FUNCTION__;
            
            $requestData = [
                'domain'         => $domain,
                'addNs'          => $addNs,
                'removeNs'       => $removeNs,
                'addContacts'    => $addContacts,
                'removeContacts' => $removeContacts,
                'newRegistrant'  => $newRegistrant,
                'renewalStatus'  => $renewalStatus,
                'renewalReason'  => $renewalReason,
            ];
    
            NicController::domainUpdate($this->NicClient, $requestData);

        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, $this->action);
        } catch (\Exception $e) {
            throw new NicException($e->getMessage(), $e->getCode(), '', $requestData, $this->action);
        }
    }

    /**
      * @throws NicException
      */
    private function domainDelete(string $domain): void
    {
        $requestData = null;

        try {
            $this->action = __CLASS__ . __FUNCTION__;

            $requestData = ['domain' => $domain];

            NicController::domainDelete($this->NicClient, $requestData['domain']);

        } catch (NicException $e) {
            throw new NicException($e->getMessage(), $e->getCode(), $e->getReason(), $requestData, $this->action);
        } catch (\Exception $e) {
            throw new NicException($e->getMessage(), $e->getCode(), '', $requestData, $this->action);
        }
    }
}