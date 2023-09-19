<?php

namespace Module\Registrar\Nic\Nic\Command;

use Module\Registrar\Nic\Nic\Command\Command;

require_once(__DIR__ . '/Command.php');

class Logout extends Command
{
    private const TEMPLATE = <<<XML
    <command>
        <logout/>
        <clTRID>%s</clTRID>
    </command>
XML;

    public function __construct()
    {
        $this->xml = sprintf(
            self::TEMPLATE,
            $this->clTRID()
        );
    }

    public function getResult(object $dom): bool
    {
        return true;
    }
}
