<?php

namespace Module\Registrar\Nic\Nic\Object;

class Contact
{
    private $dom;

    private const NS__INF_CONTACT              = "urn:ietf:params:xml:ns:contact-1.0";
    private const NS__LV_CONTACT__INF_CONTACT  = "http://www.nic.lv/epp/schema/lvcontact-ext-1.0";
    private const SCHEMA_LOCATION__INF_CONTACT = "urn:ietf:params:xml:ns:contact-1.0 domain-1.0.xsd";

    public function __construct(object $dom)
    {
        $this->dom = $dom;
    }

    public static function getMainNS(): string
    {
        return self::NS__INF_CONTACT;
    }

    public static function getExtensionNS(): string
    {
        return self::NS__LV_CONTACT__INF_CONTACT;
    }

    public static function getSchemaLocation(): string
    {
        return self::SCHEMA_LOCATION__INF_CONTACT;
    }

    public static function getSchemaDetails(): string
    {
        $mainNs         = self::getMainNS();
        $schemaLocation = self::getSchemaLocation();

        return "xmlns:contact='{$mainNs}' xsi:schemaLocation='{$schemaLocation}'";
    }

    public static function getExtensionDetails(): string
    {
        $extensionNs = self::getExtensionNS();

        return "xmlns:lvcontact='{$extensionNs}";
    }

    /**
     * @return object
     */
    public function getContact(): object
    {
        $contact = new \stdClass();
        
        $contact->id          = $this->getResultId();
        $contact->org         = $this->getResultOrganization();
        $contact->roid        = $this->getResultRoid();
        $contact->clID        = $this->getResultClId();
        $contact->phoneNumber = $this->getResultPhoneNumber();
        $contact->email       = $this->getResultEmail();
        $contact->postInfo    = $this->getResultPostInfo();
        $contact->crID        = $this->getResultCrId();
        $contact->crDate      = $this->getResultCrDate();
        $contact->upDate      = $this->getResultUpDate();
        $contact->status      = $this->getResultStatus();
        $contact->regNumber   = $this->getResultRegNumber();
        $contact->vatNumber   = $this->getResultVatNumber();

        return $contact;
    }

    /**
     * @return string
     */
    public function getResultAvailability(): string
    {
        $result = $this->dom->getElementsByTagNameNS(self::NS__INF_CONTACT, 'id')->item(0)->getAttribute('avail');

        return !empty($result) ? $result : '';
    }

    /**
     * @return string
     */
    public function getResultId(): string
    {
        $result = $this->dom->getElementsByTagNameNS(self::NS__INF_CONTACT, 'id')->item(0)->nodeValue;

        return !empty($result) ? $result : '';
    }

    /**
     * @return string
     */
    public function getResultOrganization(): string
    {
        $result = $this->dom->getElementsByTagNameNS(self::NS__INF_CONTACT, 'org')->item(0)->nodeValue;

        return !empty($result) ? $result : '';
    }

    /**
     * @return string
     */
    public function getResultRoid(): string
    {
        $result = $this->dom->getElementsByTagNameNS(self::NS__INF_CONTACT, 'roid')->item(0)->nodeValue;

        return !empty($result) ? $result : '';
    }

    /**
     * @return string
     */
    public function getResultClId(): string
    {
        $result = $this->dom->getElementsByTagNameNS(self::NS__INF_CONTACT, 'clID')->item(0)->nodeValue;

        return !empty($result) ? $result : '';
    }

    /**
     * @return string
     */
    public function getResultPhoneNumber(): string
    {
        $result = $this->dom->getElementsByTagNameNS(self::NS__INF_CONTACT, 'voice')->item(0)->nodeValue;

        return !empty($result) ? $result : '';
    }

    /**
     * @return string
     */
    public function getResultEmail(): string
    {
        $result = $this->dom->getElementsByTagNameNS(self::NS__INF_CONTACT, 'email')->item(0)->nodeValue;

        return !empty($result) ? $result : '';
    }

    /**
     * @return object
     */
    public function getResultPostInfo(): object
    {
        $result = new \stdClass();

        $postInfoNode = $this->dom->getElementsByTagNameNS(self::NS__INF_CONTACT, 'postalInfo')->item(0);

        if ($postInfoNode) {
            $result->name = $postInfoNode->getElementsByTagName('name')->item(0)->nodeValue;

            $addressNode = $postInfoNode->getElementsByTagName('addr')->item(0);

            if ($addressNode) {
                $result->street      = $addressNode->getElementsByTagName('street')->item(0)->nodeValue;
                $result->city        = $addressNode->getElementsByTagName('city')->item(0)->nodeValue;
                $result->postCode    = $addressNode->getElementsByTagName('pc')->item(0)->nodeValue;
                $result->countryCode = $addressNode->getElementsByTagName('cc')->item(0)->nodeValue;
            }
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getResultCrId(): string
    {
        $result = $this->dom->getElementsByTagNameNS(self::NS__INF_CONTACT, 'crID')->item(0)->nodeValue;

        return !empty($result) ? $result : '';
    }

    /**
     * @return string
     */
    public function getResultCrDate(): string
    {
        $result = $this->dom->getElementsByTagNameNS(self::NS__INF_CONTACT, 'crDate')->item(0)->nodeValue;

        return !empty($result) ? $result : '';
    }

    /**
     * @return string
     */
    public function getResultUpDate(): string
    {
        $result = $this->dom->getElementsByTagNameNS(self::NS__INF_CONTACT, 'upDate')->item(0)->nodeValue;

        return !empty($result) ? $result : '';
    }

    /**
     * @return string
     */
    public function getResultStatus(): string
    {
        $result = $this->dom->getElementsByTagNameNS(self::NS__INF_CONTACT, 'status')->item(0)->nodeValue;

        return !empty($result) ? $result : '';
    }

    /**
     * @return string
     */
    public function getResultRegNumber(): string
    {
        $result = $this->dom->getElementsByTagNameNS(self::NS__LV_CONTACT__INF_CONTACT, 'regNr')->item(0)->nodeValue;

        return !empty($result) ? $result : '';
    }
    
    /**
     * @return string
     */
    public function getResultVatNumber(): string
    {
        $result = $this->dom->getElementsByTagNameNS(self::NS__LV_CONTACT__INF_CONTACT, 'vatNr')->item(0)->nodeValue;

        return !empty($result) ? $result : '';
    }
}