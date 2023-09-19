<?php

namespace Module\Registrar\Nic\Nic\Command;

use Module\Registrar\Nic\Nic\Command\Command;
use Module\Registrar\Nic\Nic\Object\{Contact, Domain};

require_once(__DIR__ . '/Command.php');

class Login extends Command
{
    private const TEMPLATE = <<<XML
    <command>
        <login>
            <clID>%s</clID>
            <pw>%s</pw>
            %s
            <options>
                <version>1.0</version>
                <lang>en</lang>
            </options>
            <svcs>
                <objURI>%s</objURI>
                <objURI>%s</objURI>
                <svcExtension>
                    <extURI>%s</extURI>
                    <extURI>%s</extURI>
                    <extURI>urn:ietf:params:xml:ns:secDNS-1.1</extURI>
                </svcExtension>
            </svcs>
        </login>
        <clTRID>%s</clTRID>
    </command>
XML;

    /**
     * @param string $user - nic given epp service login
     * @param string $pass - nic given epp service password
     * @param string|null $newPw - used only for sandbox acceptance test
     */
    public function __construct(string $user, string $pass, $newPw = null)
    {
        $newPwTemplate = '';
        if (!empty($newPw)) {
            $newPwTemplate = "<newPW>{$newPw}</newPW>".PHP_EOL;
        }

        $this->xml = sprintf(
            self::TEMPLATE,
            $user,
            $pass,
            $newPwTemplate,
            Domain::getMainNS(),
            Contact::getMainNS(),
            Domain::getExtensionNS(),
            Contact::getExtensionNS(),
            $this->clTRID()
        );
    }

    public function getResult(object $dom): bool
    {
        return true;
    }
}
