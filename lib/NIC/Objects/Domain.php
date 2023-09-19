<?php

namespace Module\Registrar\Nic\Nic\Object;

class Domain
{
    private $dom;

    private const NS__INF_DOMAIN              = "urn:ietf:params:xml:ns:domain-1.0";
    private const NS__LV_DOMAIN__INF_DOMAIN   = "http://www.nic.lv/epp/schema/lvdomain-ext-1.0";
    private const SCHEMA_LOCATION__INF_DOMAIN = "urn:ietf:params:xml:ns:domain-1.0 domain-1.0.xsd";

    public function __construct(object $dom)
    {
        $this->dom = $dom;
    }

    public static function getMainNS(): string
    {
        return self::NS__INF_DOMAIN;
    }

    public static function getExtensionNS(): string
    {
        return self::NS__LV_DOMAIN__INF_DOMAIN;
    }

    public static function getSchemaLocation(): string
    {
        return self::SCHEMA_LOCATION__INF_DOMAIN;
    }

    public static function getSchemaDetails(): string
    {
        $mainNs         = self::getMainNS();
        $schemaLocation = self::getSchemaLocation();

        return "xmlns:domain='{$mainNs}' xsi:schemaLocation='{$schemaLocation}'";
    }

    public static function getSchemaExtensionDetails(): string
    {
        $extensionNs = self::getExtensionNS();
        
        return "xmlns:lvdomain='{$extensionNs}'";
    }

    /**
     * @return object
     */
    public function getDomain(): object
    {
        $domain = new \stdClass();

        $domain->name        = $this->getResultName();
        $domain->roid        = $this->getResultRoid();
        $domain->statuses    = $this->getResultStatuses();
        $domain->registrant  = $this->getResultRegistrant();
        $domain->contacts    = $this->getResultContacts();
        $domain->nameservers = $this->getResultNameservers();
        $domain->clID        = $this->getResultClId();
        $domain->crID        = $this->getResultCrId();
        $domain->crDate      = $this->getResultCrDate();
        $domain->exDate      = $this->getResultExDate();
        $domain->upID        = $this->getResultUpId();
        $domain->upDate      = $this->getResultUpDate();
        $domain->lvStatus    = $this->getResultStatuses(true);
        $domain->eppCode     = $this->getResultEppCode();

        return $domain;
    }

    /**
     * @return string
     */
    public function getResultName(): string
    {
        $result = $this->dom->getElementsByTagNameNS(self::NS__INF_DOMAIN, 'name')->item(0)->nodeValue;

        return !empty($result) ? $result : '';
    }

    /**
     * @return string
     */
    public function getResultRoid(): string
    {
        $result = $this->dom->getElementsByTagNameNS(self::NS__INF_DOMAIN, 'roid')->item(0)->nodeValue;

        return !empty($result) ? $result : '';
    }

    /**
     * @param bool|null $extension
     * @return array
     */
    public function getResultStatuses($extension = false): array
    {
        $statuses = [];

        if ($extension) {
            $statusNodes = $this->dom->getElementsByTagNameNS(self::NS__LV_DOMAIN__INF_DOMAIN, 'status');
        } else {
            $statusNodes = $this->dom->getElementsByTagNameNS(self::NS__INF_DOMAIN, 'status');
        }

        if (!empty($statusNodes)) {
            foreach ($statusNodes as $statusNode) {
                if (!empty($statusNode)) {
                    $statusValue = $statusNode->getAttribute('s');
                    $reasonValue = $statusNode->nodeValue;

                    if (!(empty($reasonValue))) {
                        $statuses[$statusValue] = $reasonValue;
                    } else {
                        $statuses[] = $statusValue;
                    }
                }
            }
        }

        return $statuses;
    }

    /**
     * @return string
     */
    public function getResultRegistrant(): string
    {
        $result = $this->dom->getElementsByTagNameNS(self::NS__INF_DOMAIN, 'registrant')->item(0)->nodeValue;

        return !empty($result) ? $result : '';
    }

    /**
     * @return array
     */
    public function getResultContacts(): array
    {
        $contacts = [];

        $contactNodes = $this->dom->getElementsByTagNameNS(self::NS__INF_DOMAIN, 'contact');

        if (!empty($contactNodes)) {
            foreach ($contactNodes as $contactNode) {
                $contacts[$contactNode->getAttribute('type')] = $contactNode->nodeValue;
            }
        }

        return $contacts;
    }

    /**
     * @return string
     */
    public function getResultNameservers(): array
    {
        $nameservers = [];

        $nameserverNode = $this->dom->getElementsByTagNameNS(self::NS__INF_DOMAIN, 'ns')->item(0);

        if (!empty($nameserverNode)) {
            $hostAttrNodes  = $nameserverNode->getElementsByTagName('hostAttr');

            foreach ($hostAttrNodes as $hostAttrNode) {
                $nsHostname = $hostAttrNode->getElementsByTagName('hostName')->item(0)->nodeValue ?? null;
                $nsIp       = $hostAttrNode->getElementsByTagName('hostAddr')->item(0)->nodeValue ?? null;

                $nameservers[$nsHostname] = $nsIp;
            }
        }

        return $nameservers;
    }

    /**
     * @return string
     */
    public function getResultClId(): string
    {
        $result = $this->dom->getElementsByTagNameNS(self::NS__INF_DOMAIN, 'clID')->item(0)->nodeValue;

        return !empty($result) ? $result : '';
    }

    /**
     * @return string
     */
    public function getResultCrId(): string
    {
        $result = $this->dom->getElementsByTagNameNS(self::NS__INF_DOMAIN, 'crID')->item(0)->nodeValue;

        return !empty($result) ? $result : '';
    }

    /**
     * @return string
     */
    public function getResultCrDate(): string
    {
        $result = $this->dom->getElementsByTagNameNS(self::NS__INF_DOMAIN, 'crDate')->item(0)->nodeValue;

        return !empty($result) ? $result : '';
    }

    /**
     * @return string
     */
    public function getResultExDate(): string
    {
        $result = $this->dom->getElementsByTagNameNS(self::NS__INF_DOMAIN, 'exDate')->item(0)->nodeValue;

        return !empty($result) ? $result : '';
    }

    /**
     * @return string
     */
    public function getResultUpId(): string
    {
        $result = $this->dom->getElementsByTagNameNS(self::NS__INF_DOMAIN, 'upID')->item(0)->nodeValue;

        return !empty($result) ? $result : '';
    }

    /**
     * @return string
     */
    public function getResultUpDate(): string
    {
        $result = $this->dom->getElementsByTagNameNS(self::NS__INF_DOMAIN, 'upDate')->item(0)->nodeValue;

        return !empty($result) ? $result : '';
    }

    /**
     * @return string
     */
    public function getResultEppCode(): string
    {
        $authInfoNode = $this->dom->getElementsByTagNameNS(self::NS__INF_DOMAIN, 'authInfo')->item(0);

        if (!empty($authInfoNode)) {
            $result = $authInfoNode->getElementsByTagName('pw')->item(0)->nodeValue;
        }

        return !empty($result) ? $result : '';
    }
}