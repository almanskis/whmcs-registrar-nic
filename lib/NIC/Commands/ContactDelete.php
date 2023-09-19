<?php

namespace Module\Registrar\Nic\Nic\Command;

use Module\Registrar\Nic\Nic\Command\Command;
use Module\Registrar\Nic\Nic\Object\Contact;

class ContactDelete extends Command
{
    private const TEMPLATE = <<<XML
    <command>
        <info>
            <contact:delete %s>
                <contact:id>%s</contact:id>
            </contact:delete>
        </info>
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

        return (object)[];
    }
}
