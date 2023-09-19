<?php

namespace Module\Registrar\Nic\Nic;

require_once(__DIR__ . '/Commands/autoload.php');
require_once(__DIR__ . '/Objects/autoload.php');
require_once(__DIR__ . '/../EPP/Client.php');
require_once(__DIR__ . '/../Helper.php');
require_once(__DIR__ . '/Frame.php');
require_once(__DIR__ . '/Response.php');

use Module\Registrar\Nic\Helper;
use Module\Registrar\Nic\Epp\{EppClient, EppException};
use Module\Registrar\Nic\Nic\Frame;
use Module\Registrar\Nic\Nic\Command\{Greeting, Login, Logout};
use Module\Registrar\Nic\Nic\Command\{DomainCheck, DomainCreate, DomainInfo, DomainUpdate, DomainTransfer, DomainDelete};
use Module\Registrar\Nic\Nic\Command\{ContactCheck, ContactCreate, ContactInfo, ContactUpdate, ContactDelete};

class Client extends EppClient
{
    private $connected;
    private $logged_in;
    private $user;
    private $pass;
    private $result;
    private $newPw;

    private $debug;

    /**
     * @param string $host
     * @param string $user
     * @param string $pass
     * @param boolean $debug
     * @param integer $port
     * @param integer timeout
     * @param boolean $ssl
     * @param resource $context
     * @param string $newPw
     * @throws Net_EppException
     */
    public function __construct(
        $host    = null,
        $user    = null,
        $pass    = null,
        $debug   = false,
        $port    = 700,
        $timeout = 1,
        $ssl     = true,
        $context = null,
        $newPw   = null
    ) {
        $this->connected = false;
        $this->logged_in = false;
        $this->debug     = $debug;
        $this->user      = $user;
        $this->pass      = $pass;
        $this->newPw     = $newPw;

        if ($host) {
            try {
                $this->connect($host, $port, $timeout, $ssl, $context);
            } catch (EppException $e) {
                throw new \Exception($e->getMessage());
            }
        }
    }

    public function getResult(): object
    {
        return $this->result;
    }

    public function greeting(): bool
    {
        $this->debug("Greeting the server");

        $command = new Greeting();
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    public function login(): bool
    {
        $this->debug("Attempting to login");
        
        $command = new Login($this->user, $this->pass, $this->newPw);
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    /**
     * @param string|array $domain(s)
     */
    public function domainCheck($domain): array
    {
        $this->debug("Checking if domain(s) is(are) available");

        $command = new DomainCheck($domain);
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    /**
     * @param string $domain
     * @param int $period
     * @param string $registrantId
     * @param string $contactAdminId
     * @param string $contactTechId
     * @param array $nameservers
     */
    public function domainCreate(
        string $domain,
        string $registrantId,
        string $contactAdminId,
        string $contactTechId,
        $nameservers = [],
        $period = '1'
    ): object {
        $this->debug("Creating domain");

        $command = new DomainCreate(
            $domain,
            $registrantId,
            $contactAdminId,
            $contactTechId,
            $nameservers,
            $period
        );

        $frame = new Frame($command);

        return $this->request($frame);
    }

    /**
     * @param string $domain
     */
    public function domainGetInfo(string $domain): object
    {
        $this->debug("Getting domain info");

        $command = new DomainInfo($domain);
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    /**
     * @param string $domain
     * @param array $addNs
     * @param array $removeNs
     * @param array $addContacts
     * @param array $removeContacts
     * @param string $newRegistrant
     * @param string $renewalStatus
     * @param string $renewalReason
     */
    public function domainUpdate(
        string $domain,
        array  $addNs          = [],
        array  $removeNs       = [],
        array  $addContacts    = [],
        array  $removeContacts = [],
        string $newRegistrant  = '',
        string $renewalStatus  = '',
        string $renewalReason  = ''
    ): object {
        $this->debug("Updating domain details");

        $command = new DomainUpdate(
            $domain,
            $addNs,
            $removeNs,
            $addContacts, 
            $removeContacts,
            $newRegistrant,
            $renewalStatus,
            $renewalReason
        );

        $frame = new Frame($command);

        return $this->request($frame);
    }

    /**
     * @param string $domain
     */
    public function domainTransferQuery(string $domain): object
    {
        $this->debug("Querying domain transfer");

        $command = new DomainTransfer('query', $domain);
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    /**
     * @param string $domain
     */
    public function domainTransferCancel(string $domain): object
    {
        $this->debug("Cancelling domain transfer");

        $command = new DomainTransfer('cancel', $domain);
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    /**
     * @param string $domain
     * @param string $eppCode
     * @param array $nameservers
     */
    public function domainTransferRequest(string $domain, string $eppCode, array $nameservers = []): object
    {
        $this->debug("Requesting domain transfer");

        $command = new DomainTransfer('request', $domain, $eppCode, $nameservers);
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    /**
     * @param string $domain
     */
    public function domainTransferApprove(string $domain): object
    {
        $this->debug("Approving domain transfer");

        $command = new DomainTransfer('approve', $domain);
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    /**
     * @param string $domain
     */
    public function domainTransferReject(string $domain): object
    {
        $this->debug("Rejecting domain transfer");

        $command = new DomainTransfer('reject', $domain);
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    /**
     * @param string $domain
     */
    public function domainDelete(string $domain): object
    {
        $this->debug("Requesting to delete the domain");

        $command = new DomainDelete($domain);
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    /**
     * @param string $contactId
     */
    public function contactCheck(string $contactId): object
    {
        $this->debug("Checking if contact exists");

        $command = new ContactCheck($contactId);
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    /**
     * @param string $contactId
     * @param string $name
     * @param string $organization
     * @param string $street
     * @param string $city
     * @param string $postalCode
     * @param string $countryCode
     * @param string $phone
     * @param string $email
     * @param string $vatNumber
     * @param string $companyId
     */
    public function contactCreate(
        $contactId,
        $name,
        $street,
        $city,
        $postalCode,
        $countryCode,
        $phone,
        $email,
        $organization,
        $vatNumber,
        $companyId
    ): object {
        $this->debug("Creating contact");

        $command = new ContactCreate(
            $contactId,
            $name,
            $street,
            $city,
            $postalCode,
            $countryCode,
            $phone,
            $email,
            $organization,
            $vatNumber,
            $companyId
        );

        $frame = new Frame($command);

        return $this->request($frame);
    }

    /**
     * @param string $contactId
     */
    public function contactGetInfo(string $contactId): object
    {
        $this->debug("Getting contact info");

        $command = new ContactInfo($contactId);
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    /**
     * @param string $contactId
     * @param string $name
     * @param string $organization
     * @param string $email
     * @param string $phone
     * @param string $street
     * @param string $city
     * @param string $postalCode
     * @param string $countryCode
     * @param string $vatNumber
     * @param string $companyId
     */
    public function contactUpdate(
        $contactId,
        $name,
        $email,
        $phone,
        $street,
        $city,
        $postalCode,
        $countryCode,
        $organization = '',
        $regNumber    = '',
        $vatNumber    = ''
    ): object {
        $this->debug("Updating contact details");

        $command = new ContactUpdate(
            $contactId,
            $name,
            $street,
            $city,
            $postalCode,
            $countryCode,
            $phone,
            $email,
            $organization,
            $regNumber,
            $vatNumber
        );

        $frame = new Frame($command);

        return $this->request($frame);
    }

    /**
     * @param string $contactId
     */
    public function contactDelete(string $contactId): object
    {
        $this->debug("Deleting contact");

        $command = new ContactDelete($contactId);
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    /**
     * @return object|array|string|bool
     */
    public function request($frame)
    {
        $this->sendFrame($frame->getXML());
        $dom          = $this->getFrame();
        $this->result = new Response($dom);
        $response     = $frame->getResult($dom);

        return $response;
    }

    public function logout(): bool
    {
        $this->debug("Logging out");
        $command = new Logout($this->user, $this->pass);
        $frame   = new Frame($command);

        return $this->request($frame);
    }

    public function connect($host, $port = 700, $timeout = 1, $ssl = true, $context = NULL): void
    {
        $this->debug("Attempting to connect to {$host}:{$port}");
        parent::connect($host, $port, $timeout, $ssl, $context);
        $this->debug("Connected OK");
        $this->connected = true;
    }

    public function getFrame(): \DOMDocument
    {
        $xml = parent::getFrame();
        $this->xml = $xml;

        foreach (explode("\n", str_replace('><', ">" . PHP_EOL . "<", trim($xml))) as $line) {
            $this->debug("S: %s", $line);
        }

        $dom = new \DOMDocument;
        $dom->loadXML($this->xml);

        return $dom;
    }

    public function sendFrame(string $xml): bool
    {
        foreach (explode("\n", str_replace('><', ">" . PHP_EOL . "<", trim($xml))) as $line) {
            $this->debug("C: {$line}");
        }

        return parent::sendFrame($xml);
    }

    protected function debug(): void
    {
        if (!$this->debug) return;

        $args = func_get_args();

        if (function_exists('logActivity')) {
            Helper::logActivity(vsprintf(array_shift($args), $args));
        } else {
            fwrite(STDERR, vsprintf(array_shift($args), $args) . PHP_EOL);
        }
    }

    public function __destruct()
    {
        if ($this->logged_in) $this->logout();

        $this->debug("Disconnecting from the server");
        $this->disconnect();
    }
}
