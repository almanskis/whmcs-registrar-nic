<?php

namespace Module\Registrar\Nic;

require_once(__DIR__ . '/NIC/Client.php');

use DateTime;
use Illuminate\Database\Capsule\Manager as Capsule;
use Module\Registrar\Nic\Nic\Exception AS NicException;

class Helper
{
    private const MODULE_NAME = 'NIC';

    private static $NIC_TABLE_CONTACTS = 'mod_nic_contacts';

    private string $contactPreffix = '';

    /**
     * @param boolean $sandbox
     */
    function __construct($sandbox = false)
    {
        if ($sandbox) {
            self::$NIC_TABLE_CONTACTS .= '_sandbox';
        }
    }

    public function setContactPreffix(string $value): void
    {
        $this->contactPreffix = $value;
    }

    public function getContactPreffix(): string
    {
        return $this->contactPreffix;
    }

    public function getRegistrarDetails(array $params): array
    {
        $details = [];

        $details['preffix'] = $params['contactsPreffix'];

        switch ($params['accountMode']) {
            case 'production':
                $details['host']              = $params['productionUrl'];
                $details['user']              = $params['productionUid'];
                $details['pass']              = $params['productionPassword'];
                $details['port']              = $params['productionPort'];
                $details['contactPrimary']    = $params['productionPrimaryPerson'];
                $details['contactHostmaster'] = $params['productionHostmaster'];
                break;
            
            case 'sandbox':
                $details['host']              = $params['sandboxUrl'];
                $details['user']              = $params['sandboxUid'];
                $details['pass']              = $params['sandboxPassword'];
                $details['port']              = $params['sandboxPort'];
                $details['contactPrimary']    = $params['sandboxPrimaryPerson'];
                $details['contactHostmaster'] = $params['sandboxHostmaster'];
                break;

            default:
                return null;
                break;
        }

        $details['debug']   = $params['debug'] === 'on' ? true : false;
        $details['ssl']     = $params['ssl']   === 'on' ? true : false;
        $details['timeout'] = 15;
        $details['context'] = '';

        return $details;
    }

    public function formatClientDetails(array $params): array
    {
        $companyName    = '';
        $vatNumber      = '';
        $companyId      = '';
        $registrantType = 'priv';

        if (
            $params['fullname'] !== $params['companyname'] 
            && empty($params['companyname']) === false
        ) {
            $companyName    = $params['companyname'];
            $vatNumber      = $params['tax_id'];
            $registrantType = 'org';

            // NIC request response - Company registration number can be set only for residents of Latvia
            if ($params['countrycode'] == 'LV') {
                $companyId   = $params['customfields1']; // This one should be adjusted by you whmcs configuration or commented out entirely
            }
        }

        return [
            'id'           => "{$this->contactPreffix}-{$params['userid']}-{$registrantType}",
            'name'         => $params['fullname'],
            'street'       => $params['address1'],
            'city'         => $params['city'],
            'postCode'     => $params['postcode'],
            'countryCode'  => $params['countrycode'],
            'phone'        => $params['phonenumberformatted'],
            'email'        => $params['email'],
            'organization' => $companyName,
            'vatNumber'    => $vatNumber,
            'companyId'    => $companyId
        ];
    }

    public function saveContactRegistrant(int $clientId, string $contactId): void
    {
        self::saveClientType($clientId, $contactId, 'registrant');
    }

    public function getNameServersFromDomainInfo(array $nameservers, object $domainInfo, string $putNsTo = 'key'): array
    {
        $domainNameservers = $domainInfo->nameservers;

        if ($putNsTo == 'value') {
            $index = 1;

            foreach ($domainNameservers as $hostname => $ip) {
                $nameservers["ns{$index}"] = $hostname;

                $index++;
            }

            return $nameservers;
        }

        return $domainNameservers;
    }

    public function findAddedNameservers(array $current_nameservers, array $updated_nameservers): array
    {
        return array_diff_key($updated_nameservers, $current_nameservers);
    }

    public function findDeletedNameservers(array $current_nameservers,array $updated_nameservers): array
    {
        return array_diff_key($current_nameservers, $updated_nameservers);
    }

    public function formatContactDetails(object $details): array
    {
        return [
            'Name'          => $details->postInfo->name,
            'Company Name'  => $details->org,
            'Email Address' => $details->email,
            'Phone Number'  => $details->phoneNumber,
            'Address'       => $details->postInfo->street,
            'City'          => $details->postInfo->city,
            'Post Code'     => $details->postInfo->postCode,
            'Country'       => $details->postInfo->countryCode,
            'Reg Number'    => $details->regNumber,
            'Vat Number'    => $details->vatNumber,
        ];
    }

    public function formatContactDetailsforUpdate(string $id, array $details): array
    {
        // Nic request response - Company registration number can be set only for residents of Latvia
        $regNumber = '';
        if ($details['Country'] === 'LV') {
            $regNumber = $details['Reg Number'];
        }

        return [
            'id'           => $id,
            'name'         => $details['Name'],
            'email'        => $details['Email Address'],
            'phone'        => $details['Phone Number'],
            'street'       => $details['Address'],
            'city'         => $details['City'],
            'postalCode'   => $details['Post Code'],
            'countryCode'  => $details['Country'],
            'organization' => $details['Company Name'],
            'regNumber'    => $regNumber,
            'vatNumber'    => $details['Vat Number']
        ];
    }

    public function formatDomainStatus(string $registrar_status = ''): string
    {
        switch ($registrar_status) {
            case 'ok':
                # Everythings good
                $status = 'Active';
                break;

            case 'inactive':
                # Domain has no nameservers set and therefore is not reachable
                $status = 'Pending';
                break;

            case 'serverHold':
                # Domain is inactive and has to be paid for to become active. (Late payment)
                $status = 'Redemption Period (Expired)';
                break;

            case 'pendingCreate':
                # Domain Registration request is sent but not yet processed
                $status = 'Pending Registration';
                break;

            case 'pendingRenew':
                # Domain is Active, but has to be renewed before it expires.
                $status = 'Active';
                break;

            case 'pendingTransfer':
                # Initiated transfer but not yet reviewed by the other registrar.
                $status = 'Pending Transfer';
                break;

            case 'clientAutoRenewProhibited':
                # Domain Active, but requested not to be renewed.
                $status = 'Active';
                break;

            case 'serverTransferProhibited':
                # Domain Active, but unavailable for transfer for the first and last months of the domain registration.
                $status = 'Active';
                break;

            case 'serverUpdateProhibited':
                # Domain is Active but locked for editing (because of payment or legal issues).
                $status = 'Active';
                break;

            case 'clientUpdateProhibited':
                # Domain is Active and locked for changes except for this status change.
                $status = 'Active';
                break;
            
            default:
                $status = 'Active';
                break;
        }

        return $status;
    }

    public function updateDomainStatus(int $domainId, string $status): void
    {
        self::updateDomainData($domainId, ['status' => $status]);
    }

    public function updateDomainDueDate(int $domainId, string $date): void
    {
        $date = self::formatDate($date, "Y-m-d");

        self::updateDomainData($domainId, ['nextduedate' => $date]);
    }

    public function updateDomainNextInvoiceDate(int $domainId, string $date): void
    {
        $date = self::formatDate($date, "Y-m-d");

        self::updateDomainData($domainId, ['nextinvoicedate' => $date]);
    }

    public function updateDomainExpiryDate(int $domainId, string $date): void
    {
        $date = self::formatDate($date, "Y-m-d");

        self::updateDomainData($domainId, ['expirydate' => $date]);
    }

    public function removeEmptyKeyValues(array $array): array
    {
        return array_filter($array, function($key) {return !empty($key);}, ARRAY_FILTER_USE_KEY);
    }

    public static function handleNicException(NicException $exception, string $action): void
    {
        $exceptionAction  = $exception->getAction() ? "{$action}__" . $exception->getAction() : $action;
        $exceptionReason  = $exception->getReason() ? "\nReason: " . $exception->getReason() : '';
        $exceptionMessage = $exception->getMessage() . "\nEpp Error Code: " . $exception->getCode() . $exceptionReason;

        self::logModuleActions($exceptionAction, $exception->getData(), $exceptionMessage);
    }

    public static function logActivity(string $msg): void
    {
        logActivity(self::MODULE_NAME . " - {$msg}");
    }

    public static function logModuleActions(string $action, array $requestData, $response = null, $processedData = null): void
    {
        logModuleCall(self::MODULE_NAME, $action, $requestData, $response, $processedData);
    }

    /**
     * --------------------
     * 
     * Private mehods
     * 
     * --------------------
     */

    private function saveClientType(int $clientId, string $contactId, string $contactType): void
    {
        Capsule::table(self::$NIC_TABLE_CONTACTS)
            ->insert([
                'client_id'    => $clientId,
                'contact_id'   => $contactId,
                'contact_type' => $contactType
            ]);
    }

    public function getClientTypeId(int $clientId, string $type): string
    {
        self::checkIfContactsTableExists();

        $contactId = Capsule::table(self::$NIC_TABLE_CONTACTS)
                        ->where('client_id', $clientId)
                        ->where('contact_type', $type)
                        ->value('contact_id');

        return $contactId ?? '';
    }

    private function formatDate(string $date, string $format): string
    {
        $date = new DateTime($date);

        return $date->format($format);
    }

    private function updateDomainData(int $id, array $data): void
    {
        Capsule::table('tbldomains')
            ->where('id', $id)
            ->update($data);
    }

    // Check if Contacts table exists and create if needed
    private function checkIfContactsTableExists(): void
    {
        if (!Capsule::schema()->hasTable(self::$NIC_TABLE_CONTACTS)) {
            Capsule::schema()->create(
                self::$NIC_TABLE_CONTACTS,
                function ($table) {
                    $table->engine = 'MyISAM';
                    $table->increments('id');
                    $table->integer('client_id')->comment('ID from tblclients');
                    $table->string('contact_id', 32)->comment('ID of NIC contact');
                    $table->enum('contact_type', ['registrant'])->comment('Type of NIC contact');
                    $table->timestamp('created_at')->nullable()->useCurrent();
                }
            );
        }
    }
}
