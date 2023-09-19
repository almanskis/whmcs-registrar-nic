<?php

namespace Module\Registrar\Nic\Nic\Command;

use Module\Registrar\Nic\Nic\Command\Command;
use Module\Registrar\Nic\Nic\Response;

class DomainTransfer extends Command
{
    private const TEMPLATE = <<<XML
    <command>
        <transfer op=%s>
          <domain:transfer>
            <domain:name>%s</domain:name>
            <domain:authInfo>
              <domain:pw>%s</domain:pw>
            </domain:authInfo>
          </domain:transfer>
        </transfer>
        %s
        <clTRID>%s</clTRID>
    </command>
XML;

    /**
     * 
     * @param string $command possible values: "request", "query", "cancel", "approve", "reject"
     * @param string $domain
     * @param string|null $pw
     * @param array|null $nameservers
     */
    public function __construct(string $command, string $domain, $pw = '', $nameservers = [])
    {
        $extensionTemplate   = '';
        $nameserversTemplate = self::formatNameserverTemplate($nameservers);

        if (!empty($nameserversTemplate)) {
            $extensionTemplate .= "<extension>".PHP_EOL;
            $extensionTemplate .= "<lvdomain:transfer>".PHP_EOL;
            $extensionTemplate .= "<lvdomain:ns>".PHP_EOL;
            $extensionTemplate .= $nameserversTemplate;
            $extensionTemplate .= "</lvdomain:ns>".PHP_EOL;
            $extensionTemplate .= "</lvdomain:transfer>".PHP_EOL;
            $extensionTemplate .= "</extension>".PHP_EOL;
        }

        $this->xml = sprintf(
            self::TEMPLATE,
            "\"{$command}\"",
            $domain,
            $pw,
            $extensionTemplate,
            $this->clTRID()
        );
    }

    public function getResult(object $dom): object
    {
        parent::getCheckedResult($dom);

        $Response = new Response($dom);

        $result          = new \stdClass();
        $result->message = $Response->getResultMessage();

        return $result;
    }

    private function formatNameserverTemplate(array $nameservers): string
    {
        $template = '';

        if (sizeof($nameservers) > 0) {
            foreach ($nameservers as $nameserver) {
                if (!empty($nameserver)) {
                    $template .= "<lvdomain:hostAttr>".PHP_EOL;
                    $template .= "<lvdomain:hostName>{$nameserver}</lvdomain:hostName>".PHP_EOL;
                    $template .= "</lvdomain:hostAttr>".PHP_EOL;
                }
            }
        }

        return $template;
    }
}
