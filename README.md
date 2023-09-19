# whmcs-registrar-nic
WHMCS Registrar module for NIC registrar (.lv domains)

## Information

Writen in PHP v7.4
Tested with WHMCS v8.7.3


## Sandbox acceptance test

Has included sandbox test file in /lib/SandboxAcceptance.php
To test/perform sandbox acceptance test you need to enter your sandbox details and run method of SandboxAcceptance/performAcceptenceTest():

1. Put this at the top of the main module file (nic.php):
```
require_once(__DIR__ . '/lib/SandboxAcceptance.php');
use Module\Registrar\Nic\SandboxAcceptance;
```

2. Create a custom function in module file:
```
    function nic_SandboxAcceptTest($params)
    {
        try {
            $shortClientId = 'Your custom short client id, given to you by the NIC registrar';

            $Sandbox = new SandboxAcceptance($params);
            $Sandbox->setShortClientId($shortClientId);

            $Sandbox->performAcceptenceTest();
        } catch (NicException $e) {
            Helper::handleNicException($e, __FUNCTION__);

            return ['error' => $e->getMessage()];
        } catch (Exception $e) {
            Helper::logModuleActions(__FUNCTION__, $params, $e->getMessage());

            return ['error' => $e->getMessage()];
        }

        return ['success' => true];
    }
```

3. Add said function to the nic_AdminCustomButtonArray():

```
    function nic_AdminCustomButtonArray() {
        return [
            // Your custom functions...
            'Accept-Test' => 'SandboxAcceptTest'
        ];
    }
```

That way it was pretty easy to run test repeatedly in case it fails and I needed to redo it again.


## How to use this repository
--------------------------

1. Check out the latest version from [github](https://github.com/almanskis/whmcs-registrar-nic)
2. Place module catalog in whmcs_root_catalog/registrars.
3. Go to WHMCs Admin area -> System Settings -> Domain Registrar.
4. Active NIC registrar module.
5. Configure module by adding your details. For sandbox testing no need to enter details of the production.
6. Select account mode of the module. It is important to use correct Account mode at the times of testing and production use.
7. Save changes.
8. Now you can try registering some .lv domains.


## Contributions, credits

Some ideas taken from [metaregistrar/php-epp-client](https://github.com/metaregistrar/php-epp-client), mostly based on [agilegeeks/eurid-epp-php](https://github.com/agilegeeks/eurid-epp-php), with some customisations.

Current supporters:
- [bacloud.com](https://www.bacloud.com/en)
