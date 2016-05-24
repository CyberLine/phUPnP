# phUPnP

PHP based UPnP device discovery

[![Build Status](https://scrutinizer-ci.com/g/CyberLine/phUPnP/badges/build.png?b=master)](https://scrutinizer-ci.com/g/CyberLine/phUPnP/build-status/master) [![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/CyberLine/phUPnP/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/CyberLine/phUPnP/?branch=master) [![Latest Stable Version](https://poser.pugx.org/cyberline/phupnp/v/stable)](https://packagist.org/packages/cyberline/phupnp) [![Total Downloads](https://poser.pugx.org/cyberline/phupnp/downloads)](https://packagist.org/packages/cyberline/phupnp) [![Latest Unstable Version](https://poser.pugx.org/cyberline/phupnp/v/unstable)](https://packagist.org/packages/cyberline/phupnp) [![License](https://poser.pugx.org/cyberline/phupnp/license)](https://packagist.org/packages/cyberline/phupnp)

### Example

#### Install using composer

    composer require cyberline/phupnp

#### Discover all devices with timeout of 1 second

    namespace CyberLine\phUPnP
    {
        require 'src/Scanner.php';
    
        try {
            $scanner = new Scanner;
            $scanner
                ->setTimeout(1);
    
            print_r($scanner->discover());
        } catch (\Exception $e) {
            print 'Exception: ' . $e->getMessage() . PHP_EOL;
        }
    }

#### Discover only root devices

    namespace CyberLine\phUPnP
    {
        require 'src/Scanner.php';
    
        try {
            $scanner = new Scanner;
            $scanner
                ->setTimeout(1)
                ->setSearchType('upnp:rootdevice');
    
            print_r($scanner->discover());
        } catch (\Exception $e) {
            print 'Exception: ' . $e->getMessage() . PHP_EOL;
        }
    }

#### Return json string from scanner

    namespace CyberLine\phUPnP
    {
        require 'src/Scanner.php';
    
        try {
            print json_encode(new Scanner);
        } catch (\Exception $e) {
            print 'Exception: ' . $e->getMessage() . PHP_EOL;
        }
    }