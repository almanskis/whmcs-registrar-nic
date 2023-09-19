<?php

namespace Module\Registrar\Nic\Nic\Command;

use Module\Registrar\Nic\Nic\Command\Command;

require_once(__DIR__ . '/Command.php');

class Greeting extends Command
{
    private const TEMPLATE = '<hello/>';

    public function __construct()
    {
        $this->xml = self::TEMPLATE;
    }

    public function getResult(object $dom): bool
    {
        return true;
    }
}
