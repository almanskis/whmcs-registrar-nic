<?php

namespace Module\Registrar\Nic\Nic\Command;

use Module\Registrar\Nic\Nic\Command\Command;
use Module\Registrar\Nic\Nic\Object\Contact;

class ContactCreate extends Command
{
    private const TEMPLATE = <<<XML
    <command>
        <create>
            <contact:create %s>
                <contact:id>%s</contact:id>
                <contact:postalInfo type='loc'>
                    <contact:name>%s</contact:name>
                    %s
                    <contact:addr>
                        <contact:street>%s</contact:street>
                        <contact:city>%s</contact:city>
                        <contact:pc>%s</contact:pc>
                        <contact:cc>%s</contact:cc>
                    </contact:addr>
                </contact:postalInfo>
                <contact:voice>%s</contact:voice>
                <contact:fax></contact:fax>
                <contact:email>%s</contact:email>
                <contact:authInfo>
                    <contact:pw></contact:pw>
                </contact:authInfo>
            </contact:create>
        </create>
        <extension>
            <lvcontact:create>
                %s
                %s
            </lvcontact:create>
        </extension>
        <clTRID>%s</clTRID>
    </command>
XML;

    /**
     * @param string $contactId
     * @param string $name
     * @param string $street
     * @param string $city
     * @param string $postalCode
     * @param string $countryCode
     * @param string $phone
     * @param string $email
     * @param string $organization
     * @param string $vatNumber
     * @param string $companyId
     */
    public function __construct(
        string $contactId,
        string $name,
        string $street,
        string $city,
        string $postalCode,
        string $countryCode,
        string $phone,
        string $email,
        string $organization,
        string $vatNumber,
        string $companyId
    ) {
        $organizationTemplate = '';
        $vatTemplate          = '';
        $companyIdTemplate    = '';

        if (!empty($organization)) {
            $organization = htmlentities($organization);
            $organizationTemplate = "<contact:org>{$organization}</contact:org>".PHP_EOL;
        }

        if (!empty($vatNumber)) {
            $vatTemplate = "<lvcontact:vatNr>{$vatNumber}</lvcontact:vatNr>".PHP_EOL;
        }

        if (!empty($companyId)) {
            $companyIdTemplate = "<lvcontact:regNr>{$companyId}</lvcontact:regNr>".PHP_EOL;
        }

        $this->xml = sprintf(
            self::TEMPLATE,
            Contact::getSchemaDetails(),
            $contactId,
            htmlentities($name),
            $organizationTemplate,
            htmlentities($street),
            $city,
            $postalCode,
            $countryCode,
            $phone,
            $email,
            $vatTemplate,
            $companyIdTemplate,
            $this->clTRID()
        );
    }

    public function getResult(object $dom): object
    {
        parent::getCheckedResult($dom);

        $contact = new Contact($dom);

        $result = new \stdClass();
        $result->id = $contact->getResultId();

        return $result;
    }
}
