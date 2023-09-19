<?php

namespace Module\Registrar\Nic;

require_once(__DIR__ . '/controllers/autoload.php');
require_once(__DIR__ . '/NIC/Commands/autoload.php');
require_once(__DIR__ . '/NIC/Client.php');

use Illuminate\Database\Capsule\Manager as Capsule;
use Module\Registrar\Nic\Controllers\NIC AS NicController;
use Module\Registrar\Nic\Nic\Exception AS NicException;

/**
 * Helper with various functions used in module hooks
 */
class HooksHelper extends Helper
{
    private const CLASS_NAME = 'HooksHelper__';

    private const TABLE_INVOICE_ITEMS = 'tblinvoiceitems';
    private const TABLE_DOMAINS       = 'tbldomains';
    private const TABLE_REGISTRARS    = 'tblregistrars';

    /**
     * @param boolean $sandbox
     */
    function __construct(bool $sandbox = false)
    {
        parent::__construct($sandbox);
    }

    public static function getInvoiceDomains($invoiceId): array
    {
        return Capsule::table(self::TABLE_INVOICE_ITEMS)
                ->join(self::TABLE_DOMAINS, self::TABLE_DOMAINS . '.id', '=', self::TABLE_INVOICE_ITEMS . '.relid')
                ->where(self::TABLE_INVOICE_ITEMS . '.invoiceid', $invoiceId)
                ->where(self::TABLE_INVOICE_ITEMS . '.type', 'like', '%Domain%')
                ->where(self::TABLE_DOMAINS . '.domain', 'like', '%.lv')
                ->where(self::TABLE_DOMAINS . '.status', 'Active')
                ->pluck(self::TABLE_DOMAINS . '.domain')
                ->toArray();
    }

    /**
     * @param domains - array containing domain names
     * @param renewalStatus - string, either 'false' or 'true'
     */
    public static function updateDomainsRenewalStatus(array $domains, string $renewalStatus): void
    {
        $function = self::CLASS_NAME . __METHOD__;

        $registrarDetails = self::getNicRegistrarDetails();

        if (empty($registrarDetails)) {
            $message = 'Failed to get registrar details. Check if module has all values set.';

            self::logActivity($message);
        }

        $helper = new Parent($registrarDetails['accountMode'] === 'sandbox');

        $action      = $function;
        $requestData = ['domains' => $domains, 'renewalStatus' => $renewalStatus];

        try {
            $action      = "{$function}__login";
            $requestData = $registrarDetails;
            $NicClient  = NicController::login($requestData);

            foreach ($domains as $domain) {
                $action      = "{$function}__update_renew_status";
                $requestData = [
                    'domain'        => $domain,
                    'renewalStatus' => $renewalStatus
                ];
        
                $response = NicController::domainUpdate($NicClient, $requestData);
                $helper->logModuleActions($action, $requestData, $response);
            }

            $NicClient->logout();

        } catch (NicException $e) {
            $helper->handleNicException($e, $action);
        } catch (\Exception $e) {
            $helper->logModuleActions($action, $requestData, $e->getMessage());
        }
    }

    private function getNicRegistrarDetails(): array
    {
        $details = [];

        $params = Capsule::table(self::TABLE_REGISTRARS)
                    ->where('registrar', 'nic')
                    ->pluck('value', 'setting');

        $details['accountMode'] = decrypt($params['accountMode']);

        switch ($details['accountMode']) {
            case 'production':
                $details['host']              = decrypt($params['productionUrl']);
                $details['user']              = decrypt($params['productionUid']);
                $details['pass']              = decrypt($params['productionPassword']);
                $details['port']              = decrypt($params['productionPort']);
                $details['contactPrimary']    = decrypt($params['productionPrimaryPerson']);
                $details['contactHostmaster'] = decrypt($params['productionHostmaster']);
                break;
            
            case 'sandbox':
                $details['host']              = decrypt($params['sandboxUrl']);
                $details['user']              = decrypt($params['sandboxUid']);
                $details['pass']              = decrypt($params['sandboxPassword']);
                $details['port']              = decrypt($params['sandboxPort']);
                $details['contactPrimary']    = decrypt($params['sandboxPrimaryPerson']);
                $details['contactHostmaster'] = decrypt($params['sandboxHostmaster']);
                break;

            default:
                return null;
                break;
        }

        $details['debug']   = decrypt($params['debug']) === 'on' ? true : false;
        $details['ssl']     = decrypt($params['ssl'])   === 'on' ? true : false;
        $details['timeout'] = 15;

        return $details;
    }
}