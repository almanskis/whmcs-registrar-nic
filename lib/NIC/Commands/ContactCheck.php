<?php

namespace Module\Registrar\Nic\Nic\Command;

use Module\Registrar\Nic\Nic\Command\Command;
use Module\Registrar\Nic\Nic\Object\Contact;

class ContactCheck extends Command
{
    private const TEMPLATE = <<<XML
    <command>
        <check>
             <contact:check %s>
                 <contact:id>%s</contact:id>
             </contact:check>
        </check>
        <clTRID>%s</clTRID>
    </command>
XML;

    /**
     * @param string $contactId
     */
    public function __construct(string $contactId)
    {
        $this->xml = sprintf(
            self::TEMPLATE,
            Contact::getSchemaDetails(),
            $contactId,
            $this->clTRID()
        );
    }

    public function getResult(object $dom): object
    {
        parent::getCheckedResult($dom);

        $contact = new Contact($dom);

        $result              = new \stdClass();
        $result->id          = $contact->getResultId();
        $result->isAvailable = $contact->getResultAvailability();

        return $result;
    }
}
