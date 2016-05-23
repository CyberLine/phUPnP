<?php

namespace CyberLine\phUPnP
{
    require 'src/Scanner.php';

    try {
        $scanner = new Scanner;
        $scanner
            ->setTimeout(1);

        print json_encode($scanner);
    } catch (\Exception $e) {
        print 'Exception: ' . $e->getMessage() . PHP_EOL;
    }
}
