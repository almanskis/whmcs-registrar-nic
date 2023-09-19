<?php

namespace Module\Registrar\Nic\Nic\Command;

use Module\Registrar\Nic\Nic\{Exception AS NicException, Response};

require_once(__DIR__ . '/../Exception.php');

abstract class Command
{
    protected $xml = null;
    protected $svTRID;

    private const GoodResultCodes = ['1000', '1001', '1500'];
    // const BadResultCodes  = [
    //     'CommandError'           => ['2000', '2001', '2003', '2004', '2005'],
    //     'ServerError'            => ['2100', '2101', '2102', '2103', '2104', '2105'],
    //     'AuthorisationError'     => ['2200', '2201'],
    //     'FunctionError'          => ['2302', '2303', '2304', '2306', '2307', '2308'],
    //     'CommandFailed'          => ['2400'],
    //     'ErrorClosingConnection' => ['2500', '2501']
    // ];

    public function getXML(): string
    {
        return $this->xml;
    }

    /*
    * generate a client transaction ID
    * @return string
    */
    public function clTRID(): string
    {
        $clTRID = base_convert(
            hash('sha256', uniqid()),
            16,
            36
        );

        return $clTRID;
    }

    /**
     * @param object $dom
     * @return object
     * @throws NicException
     */
    public function getCheckedResult(object $dom): object
    {
        $response = new Response($dom);

        if (!in_array($response->getResultCode(), self::GoodResultCodes)) {
            throw new NicException(
                $response->getResultMessage(),
                $response->getResultCode(),
                $response->getResultReason()
            );
        }

        return $response;
    }

    /**
     * @param string $ip
     * @return string
     */
    public function detectIpVersion(string $ip): string
    {
        $ipVersion = '';

        if (strpos($ip, '.') > 0) {
            $ipVersion = 'v4';
        } elseif (strpos($ip, ':') > 0) {
            $ipVersion = 'v6';
        }

        return $ipVersion;
    }
}
