<?php

namespace Module\Registrar\Nic\Nic\Command;

use Module\Registrar\Nic\Nic\Command\Command;
use Module\Registrar\Nic\Nic\Object\Domain;

class DomainDelete extends Command
{
    private const TEMPLATE = <<<XML
    <command>
        <delete>
            <domain:delete %s>
                <domain:name>%s</domain:name>
            </domain:delete>
        </delete>
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
        return (object)[];
    }
}
