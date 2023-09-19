<?php

namespace Module\Registrar\Nic\Nic\Command;

use Module\Registrar\Nic\Nic\Command\Command;
use Module\Registrar\Nic\Nic\Object\Domain;

class DomainInfo extends Command
{
    private const TEMPLATE = <<<XML
    <command>
        <info>
            <domain:info %s>
                <domain:name hosts='all'>%s</domain:name>
            </domain:info>
        </info>
        <clTRID>%s</clTRID>
    </command>
XML;

    /**
     * @param string $domain
     */
    public function __construct(string $domain)
    {
        $this->xml = sprintf(
            self::TEMPLATE,
            Domain::getSchemaDetails(),
            $domain,
            $this->clTRID()
        );
    }

    public function getResult(object $dom): object
    {
        parent::getCheckedResult($dom);

        $Domain = new Domain($dom);

        return $Domain->getDomain();
    }
}
