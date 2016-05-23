# phUPnP

PHP based UPnP device discovery

### Example

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