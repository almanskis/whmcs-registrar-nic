<?php

namespace Module\Registrar\Nic\Nic\Command;

use Module\Registrar\Nic\Nic\Command\Command;
use Module\Registrar\Nic\Nic\Object\Domain;

class DomainCheck extends Command
{
    private const TEMPLATE = <<<XML
    <command>
        <check >
            <domain:check %s>
                %s
            </domain:check>
        </check>
        <clTRID>%s</clTRID>
    </command>
XML;

    /**
     * @param string|array $domains
     */
    public function __construct($domains)
    {
        $domain_names_template = '';

        if (is_string($domains)) {
            $domain_names_template = "<domain:name>{$domains}</domain:name>".PHP_EOL;
        } else {
            if (is_array($domains)) {
                foreach ($domains as $domain) {
                    $domain_names_template .= "<domain:name>{$domain}</domain:name>".PHP_EOL;
                }
            }
        }

        $this->xml = sprintf(
            self::TEMPLATE,
            Domain::getSchemaDetails(),
            $domain_names_template,
            $this->clTRID()
        );
    }

    public function getResult(object $dom): array
    {
        parent::getCheckedResult($dom);
        
        $result = [];

        $chkDataNode = $dom->getElementsByTagName('chkData');
        if (!is_null($chkDataNode)) {
            $chkDataNode = $chkDataNode->item(0);
            $cdNodes     = $chkDataNode->getElementsByTagName('cd');

            foreach ($cdNodes as $node) {
                $nameNode   = $node->getElementsByTagName('name')->item(0);
                $reasonNode = $node->getElementsByTagName('reason')->item(0);

                $result[$nameNode->nodeValue] = [
                    'available' => $nameNode->getAttribute('avail') === '1',
                    'reason'    => $reasonNode->textContent
                ];
            }
        }

        return $result;
    }
}
