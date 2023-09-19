<?php

namespace Module\Registrar\Nic\Nic;

class Response
{
    private $dom;

    /**
     *
     * @var string $defaultnamespace
     */
    public $defaultnamespace;

    /**
     * @var array of xpath uri
     */
    public $xpathuri;

    public function __construct(object $dom)
    {
        $this->dom = $dom;
    }

    public function getDOM(): object
    {
        return $this->dom;
    }

    public function setDefaultNamespace($namespace)
    {
        $this->defaultnamespace = $namespace;
    }

    public function getResultCode(): string
    {
        return $this->queryPath('/epp:epp/epp:response/epp:result/@code');
    }

    public function getResultMessage(): string
    {
        return $this->queryPath('/epp:epp/epp:response/epp:result/epp:msg');
    }

    public function getResultReason(): string
    {
        return $this->queryPath('/epp:epp/epp:response/epp:result/epp:extValue/epp:reason');
    }

    public function getServerTransactionId(): string
    {
        return $this->queryPath('/epp:epp/epp:response/epp:trID/epp:svTRID');
    }

    /**
     * @return \DOMXpath
     */
    public function xPath(): \DOMXpath
    {
        $xpath = new \DOMXpath($this->dom);
        $this->defaultnamespace = $this->dom->lookupNamespaceUri(NULL);
        $xpath->registerNamespace('epp', $this->defaultnamespace);

        if (is_array($this->xpathuri)) {
            foreach ($this->xpathuri as $uri => $namespace) {
                if ($namespace != 'epp') { // epp was already registered as default namespace, see above
                    $xpath->registerNamespace($namespace, $uri);
                }
            }
        }

        return $xpath;
    }

    /**
     * Make an xpath query and return the results if applicable
     * @param string $path
     * @param null|\DOMElement $object
     * @return null|string
     */
    public function queryPath($path, $object = null): string
    {
        $text = '';

        if ($object) {
            $elements = $object->getElementsByTagName($path);
        } else {
            $xpath    = $this->xPath();
            $elements = $xpath->query($path);
        }

        if (is_object($elements) && ($elements->length > 0)) {
            foreach ($elements as $element) {
                $nodes = $element->childNodes;

                foreach ($nodes as $node) {
                    $text .= "\n {$node->nodeValue}.";
                }
            }
        }

        return $text;
    }
}
