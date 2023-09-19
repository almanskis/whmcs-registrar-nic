<?php

namespace Module\Registrar\Nic\Nic\Command;

use Module\Registrar\Nic\Nic\Command\Command;
use Module\Registrar\Nic\Nic\Object\Contact;

class ContactUpdate extends Command
{
    private const TEMPLATE = <<<XML
    <command>
        <update %s>
            <contact:update>
                <contact:id>%s</contact:id>
                    <contact:chg>
                        <contact:postalInfo type='loc'>
                            %s
                            %s
                            %s
                        </contact:postalInfo>
                        %s
                        %s
                    </contact:chg>
                </contact:update>
            </update>
            %s
        <clTRID>%s</clTRID>
    </command>
XML;

    /**
     * @param string $contactID
     * @param string|null $name
     * @param string|null $organization
     * @param string|null $street
     * @param string|null $city
     * @param string|null $postalCode
     * @param string|null $countryCode
     * @param string|null $phone
     * @param string|null $email
     * @param string|null $regNumber
     * @param string|null $vatNumber
     */
    public function __construct(
        string $contactId,
        $name         = '',
        $street       = '',
        $city         = '',
        $postalCode   = '',
        $countryCode  = '',
        $phone        = '',
        $email        = '',
        $organization = '',
        $regNumber    = '',
        $vatNumber    = ''
    ) {
        $xmlSchemeName        = self::formatXmlSchemeName($name);
        $xmlSchemeOrg         = self::formatXmlSchemeOrg($organization);
        $xmlSchemeStreet      = self::formatXmlSchemeStreet($street);
        $xmlSchemeCity        = self::formatXmlSchemeCity($city);
        $xmlSchemePostalCode  = self::formatXmlSchemePostalCode($postalCode);
        $xmlSchemeCountryCode = self::formatXmlSchemeCountryCode($countryCode);
        $xmlSchemePhone       = self::formatXmlSchemePhone($phone);
        $xmlSchemeEmail       = self::formatXmlSchemeEmail($email);
        $xmlSchemeExtension   = self::formatXmlSchemeExtension($regNumber, $vatNumber);

        $xmlSchemeContactAddr = '';
        if (!empty($xmlSchemeStreet) || !empty($xmlSchemeCity) || !empty($xmlSchemePostalCode) || !empty($xmlSchemeCountryCode)) {
            $xmlSchemeContactAddr = '<contact:addr>'.PHP_EOL;
            $xmlSchemeContactAddr .= $xmlSchemeStreet;
            $xmlSchemeContactAddr .= $xmlSchemeCity;
            $xmlSchemeContactAddr .= $xmlSchemePostalCode;
            $xmlSchemeContactAddr .= $xmlSchemeCountryCode;
            $xmlSchemeContactAddr .= '</contact:addr>'.PHP_EOL;
        }

        $this->xml = sprintf(
            self::TEMPLATE,
            Contact::getSchemaDetails(),
            $contactId,
            $xmlSchemeName,
            $xmlSchemeOrg,
            $xmlSchemeContactAddr,
            $xmlSchemePhone,
            $xmlSchemeEmail,
            $xmlSchemeExtension,
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

    /**
     * @param string|null $name
     */
    private static function formatXmlSchemeName($name): string
    {
        if (!empty($name)) {
            $name = htmlentities($name);
            return "<contact:name>{$name}</contact:name>".PHP_EOL;
        }

        return '';
    }

    /**
     * @param string|null $organization
     */
    private static function formatXmlSchemeOrg($organization): string
    {
        if (!empty($organization)) {
            $organization = htmlentities($organization);
            return "<contact:org>{$organization}</contact:org>".PHP_EOL;
        }

        return '';
    }

    /**
     * @param string|null $street
     */
    private static function formatXmlSchemeStreet($street): string
    {
        if (!empty($street)) {
            return "<contact:street>{$street}</contact:street>".PHP_EOL;
        }

        return '';
    }

    /**
     * @param string|null $city
     */
    private static function formatXmlSchemeCity($city): string
    {
        if (!empty($city)) {
            return "<contact:city>{$city}</contact:city>".PHP_EOL;
        }

        return '';
    }

    /**
     * @param string|null $postalCode
     */
    private static function formatXmlSchemePostalCode($postalCode): string
    {
        if (!empty($postalCode)) {
            return "<contact:pc>{$postalCode}</contact:pc>".PHP_EOL;
        }

        return '';
    }

    /**
     * @param string|null $countryCode
     */
    private static function formatXmlSchemeCountryCode($countryCode): string
    {
        if (!empty($countryCode)) {
            return "<contact:cc>{$countryCode}</contact:cc>".PHP_EOL;
        }

        return '';
    }

    /**
     * @param string|null $phoneNumber
     */
    private static function formatXmlSchemePhone($phoneNumber): string
    {
        if (!empty($phoneNumber)) {
            return "<contact:voice>{$phoneNumber}</contact:voice>".PHP_EOL;
        }

        return '';
    }

    /**
     * @param string|null $email
     */
    private static function formatXmlSchemeEmail($email): string
    {
        if (!empty($email)) {
            return "<contact:email>{$email}</contact:email>".PHP_EOL;
        }

        return '';
    }

    /**
     * @param string|null $regNumber
     * @param string|null $vatNumber
     */
    private static function formatXmlSchemeExtension($regNumber, $vatNumber): string
    {
        $xmlSchemeExtension = '';

        $xmlSchemeRegNr = '';
        $xmlSchemeVatNr = '';

        if (!empty($regNumber)) {
            $xmlSchemeRegNr =  "<lvcontact:regNr>{$regNumber}</lvcontact:regNr>".PHP_EOL;
        }

        if (!empty($vatNumber)) {
            $xmlSchemeVatNr =  "<lvcontact:vatNr>{$vatNumber}</lvcontact:vatNr>".PHP_EOL;
        }

        if (!empty($regNumber) || !empty($vatNumber)) {
            $schemeExtensionDetails = Contact::getExtensionDetails();
            $extension = "<extension>".PHP_EOL;
            $extension .= "    <lvcontact:update xmlns:lvcontact='{$schemeExtensionDetails}'>".PHP_EOL;
            $extension .= $xmlSchemeRegNr;
            $extension .= $xmlSchemeVatNr;
            $extension .= "    </lvcontact:update>".PHP_EOL;
            $extension .= "</extension>".PHP_EOL;
        }

        return $xmlSchemeExtension;
    }
}
