<?php

namespace Module\Registrar\Nic\Nic\Command;

use Module\Registrar\Nic\Nic\Command\Command;
use Module\Registrar\Nic\Nic\Object\Domain;

class DomainUpdate extends Command
{
    private const TEMPLATE = <<<XML
    <command>
        <update>
            <domain:update %s>
                <domain:name>%s</domain:name>
                %s
                %s
                %s
            </domain:update>
        </update>
        %s
        <clTRID>%s</clTRID>
    </command>
XML;

    /**
     * @param string $domain
     * @param array|null $nsToAdd
     * @param array|null $nsToRemove
     * @param array|null $contactsToAdd
     * @param array|null $contactsToRemove
     * @param string|null $newRegistrant
     * @param string|null $renewalStatus
     * @param string|null $renewalReason
     */
    public function __construct(
        string $domain, 
        $nsToAdd          = [],
        $nsToRemove       = [],
        $contactsToAdd    = [],
        $contactsToRemove = [],
        $newRegistrant    = '',
        $renewalStatus    = '',
        $renewalReason    = ''
    ) {
        $addNs            = '';
        $removeNs         = '';
        $addContacts      = '';
        $removeContacts   = '';

        $updateAdd       = '';
        $updateRemove    = '';
        $updateChange    = '';
        $updateExtension = '';

        if (!empty($nsToAdd)) {
            $addNs = self::formatNsScheme($nsToAdd);
        }

        if (!empty($nsToRemove)) {
            $removeNs = self::formatNsScheme($nsToRemove);
        }

        if (!empty($contactsToAdd)) {
            $addContacts = self::formatContactsScheme($contactsToAdd);
        }

        if (!empty($contactsToRemove)) {
            $removeContacts = self::formatContactsScheme($contactsToRemove);
        }

        if (!empty($addNs) || !empty($addContacts)) {
            $updateAdd  = "<domain:add>".PHP_EOL;
            $updateAdd .= $addNs;
            $updateAdd .= $addContacts;
            $updateAdd .= "</domain:add>";
        }

        if (!empty($removeNs) || !empty($removeContacts)) {
            $updateRemove  = "<domain:rem>".PHP_EOL;
            $updateRemove .= $removeNs;
            $updateRemove .= $removeContacts;
            $updateRemove .= "</domain:rem>";
        }

        if (!empty($newRegistrant)) {
            $updateChange = self::formatRegistrantChangeScheme($newRegistrant);
        }

        if (!empty($renewalStatus)) {
            $updateExtension = self::formatExtensionRenewalStatus($renewalStatus, $renewalReason);
        }

        $this->xml = sprintf(
            self::TEMPLATE,
            Domain::getSchemaDetails(),
            $domain,
            $updateAdd,
            $updateRemove,
            $updateChange,
            $updateExtension,
            $this->clTRID()
        );
    }

    public function getResult(object $dom): object
    {
        parent::getCheckedResult($dom);

        return (object)[];
    }

    /**
     * Private methods
     */
    private function formatNsScheme(array $nameservers): string
    {
        $xmlScheme = "<domain:ns>".PHP_EOL;

        foreach ($nameservers as $hostname => $ip) {
            $xmlScheme .= "<domain:hostAttr>".PHP_EOL;
            $xmlScheme .= "<domain:hostName>{$hostname}</domain:hostName>".PHP_EOL;

            if (!empty($ip)) {
                $xmlScheme .= "<domain:hostAddr>{$ip}</domain:hostAddr>".PHP_EOL;
            }

            $xmlScheme .= "</domain:hostAttr>".PHP_EOL;
        }

        $xmlScheme .= "</domain:ns>".PHP_EOL;

        return $xmlScheme;
    }

    private function formatContactsScheme(array $contacts): string
    {
        $xmlScheme = '';

        foreach ($contacts as $type => $contact) {
            $xmlScheme .= "<domain:contact type=\"{$type}\">{$contact}</domain:contact>".PHP_EOL;
        }

        return $xmlScheme;
    }

    private function formatRegistrantChangeScheme(string $registrant): string
    {
        $xmlScheme  = "<domain:chg>".PHP_EOL;
        $xmlScheme .= "<domain:registrant>{$registrant}</domain:registrant>".PHP_EOL;
        $xmlScheme .= "</domain:chg>";

        return $xmlScheme;
    }

    private function formatExtensionRenewalStatus(string $renewalStatus, string $renewalReason = 'Unpaid invoice'): string
    {
        $schemaExtensionDetails = Domain::getSchemaExtensionDetails();

        $xmlScheme = "<extension>".PHP_EOL;
        $xmlScheme .= "<lvdomain:update {$schemaExtensionDetails}>".PHP_EOL;

        if ($renewalStatus === 'true') {
            $xmlScheme .= "<lvdomain:rem>".PHP_EOL;
            $xmlScheme .= "<lvdomain:status s='clientAutoRenewProhibited' lang='en'></lvdomain:status>".PHP_EOL;
            $xmlScheme .= "</lvdomain:rem>".PHP_EOL;
        } else {
            $xmlScheme .= "<lvdomain:add>".PHP_EOL;
            $xmlScheme .= "<lvdomain:status s='clientAutoRenewProhibited' lang='en'>{$renewalReason}</lvdomain:status>".PHP_EOL;
            $xmlScheme .= "</lvdomain:add>".PHP_EOL;
        }

        $xmlScheme .= "</lvdomain:update>".PHP_EOL;
        $xmlScheme .= "</extension>";

        return $xmlScheme;
    }
}
