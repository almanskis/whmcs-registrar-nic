<?php

namespace Module\Registrar\Nic\Nic;

use Module\Registrar\Nic\Nic\Object\Contact;
use Module\Registrar\Nic\Nic\Object\Domain;

class Frame
{
    private $xml;
    private $frame;

    private const XML_PREFIX = '<?xml version="1.0" encoding="UTF-8" standalone="no"?>';
    private const TEMPLATE   = 
        '<epp xmlns="urn:ietf:params:xml:ns:epp-1.0"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xmlns:domain="%s" 
            xmlns:contact="%s" 
            xmlns:lvdomain="%s"
            xmlns:lvcontact="%s"
            xmlns:secDNS="urn:ietf:params:xml:ns:secDNS-1.1">
            %s
        </epp>';

    public function __construct(object $frame)
    {
        $this->frame = $frame;

        $xml = self::XML_PREFIX.PHP_EOL.self::TEMPLATE.PHP_EOL;
        $xml = sprintf(
            $xml,
            Domain::getMainNS(),
            Contact::getMainNS(),
            Domain::getExtensionNS(),
            Contact::getExtensionNS(),
            $frame->getXML()
        );

        $this->xml = $xml;
    }

    public function getXML(): string
    {
        return $this->xml;
    }

    public function getResult(object $dom)
    {
        return $this->frame->getResult($dom);
    }
}
