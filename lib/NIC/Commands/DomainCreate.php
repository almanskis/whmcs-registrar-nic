<?php

namespace Module\Registrar\Nic\Nic\Command;

use Module\Registrar\Nic\Nic\Command\Command;
use Module\Registrar\Nic\Nic\Object\Domain;

class DomainCreate extends Command
{
    private const TEMPLATE = <<<XML
    <command>
        <create>
            <domain:create %s>
                <domain:name>%s</domain:name>
                <domain:period unit='y'>%s</domain:period>
                %s
                <domain:registrant>%s</domain:registrant>
                <domain:contact type='admin'>%s</domain:contact>
                <domain:contact type='billing'>%s</domain:contact>
                <domain:contact type='tech'>%s</domain:contact>
                <domain:authInfo>
                    <domain:pw></domain:pw>
                </domain:authInfo>
            </domain:create>
        </create>
        <clTRID>%s</clTRID>
    </command>
XML;

    /**
     * @param string $domain
     * @param int|null $period - default is 1, NIC maximum and only allowed registration period is 1 year.
     * @param string $registrantId
     * @param string|null $contactAdminId
     * @param string|null $contactTechId
     * @param array|null $nameservers
     */
    public function __construct(
        string $domain,
        string $registrantId,
        string $contactAdminId = null,
        string $contactTechId  = '__DEFAULT__',
        array  $nameservers    = [],
        string $period = '1'
    ) {
        $contactAdminId   = $contactAdminId ?? $registrantId;
        $contactBillingId = '__DEFAULT__';

        $nameserversTemplate = '';

        if (sizeof($nameservers) > 0) {
            $nameserversTemplate = "<domain:ns>".PHP_EOL;

            foreach ($nameservers as $nameserver => $ip) {
                if (!empty($nameserver)) {
                    $nameserversTemplate .= "<domain:hostAttr>".PHP_EOL;
                    $nameserversTemplate .= "<domain:hostName>{$nameserver}</domain:hostName>".PHP_EOL;

                    if (!empty($ip)) {
                        $ipVersion = parent::detectIpVersion($ip);
                        if (!empty($ipVersion)) {
                            $nameserversTemplate .= "<domain:hostAddr ip=\"{$ipVersion}\">{$ip}</domain:hostAddr>".PHP_EOL;
                        }
                    }

                    $nameserversTemplate .= "</domain:hostAttr>".PHP_EOL;
                }
            }

            $nameserversTemplate .= "</domain:ns>".PHP_EOL;
        }

        $this->xml = sprintf(
            self::TEMPLATE,
            Domain::getSchemaDetails(),
            $domain,
            $period,
            $nameserversTemplate,
            $registrantId,
            $contactAdminId,
            $contactBillingId,
            $contactTechId,
            $this->clTRID()
        );
    }

    public function getResult(object $dom): object
    {
        parent::getCheckedResult($dom);

        $Domain = new Domain($dom);

        $result = new \stdClass();
        $result->creData = $Domain->getResultCrDate();

        return $result;
    }
}
