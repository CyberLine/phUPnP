<?php

namespace CyberLine\phUPnP;

/**
 * Class Scanner
 *
 * @package CyberLine\UPnP
 * @author Alexander Over <cyberline@php.net>
 */
class Scanner implements \JsonSerializable
{
    /** @var string */
    static protected $host = '239.255.255.250';

    /** @var int */
    static protected $port = 1900;

    /**
     * Maximum wait time in seconds. Should be between 1 and 120 inclusive.
     *
     * @var int
     */
    protected $delayResponse = 1;

    /** @var int */
    protected $timeout = 5;

    /** @var string */
    protected $userAgent = 'iOS/5.0 UDAP/2.0 iPhone/4';

    /** @var array */
    protected $searchTypes = [
        'ssdp:all',
        'upnp:rootdevice',
    ];

    /** @var string */
    protected $searchType = 'ssdp:all';

    /** @var array */
    private $devices = [];

    /**
     * @param integer $delayResponse
     * @return $this
     */
    public function setDelayResponse($delayResponse)
    {
        if ((int)$delayResponse >= 1 && (int)$delayResponse <= 120) {
            $this->delayResponse = (int)$delayResponse;

            return $this;
        }

        throw new \OutOfRangeException(
            sprintf(
                '%d is not a valid delay. Valid delay is between 1 and 120 (seconds)',
                $delayResponse
            )
        );
    }

    /**
     * @param int $timeout
     * @return Scanner
     */
    public function setTimeout($timeout)
    {
        if ((int)$timeout <= (int)$this->delayResponse) {
            $this->timeout = (int)$timeout;

            return $this;
        }

        throw new \OutOfBoundsException(
            sprintf(
                'Timeout of %d is smaller then delay of %d',
                $timeout,
                $this->delayResponse
            )
        );
    }

    /**
     * @param string $userAgent
     * @return Scanner
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;

        return $this;
    }

    /**
     * @param string $searchType
     * @return $this
     */
    public function setSearchType($searchType)
    {
        if (in_array($searchType, $this->searchTypes)) {
            $this->searchType = $searchType;

            return $this;
        }

        throw new \InvalidArgumentException(
            sprintf(
                '%s is not a valid searchtype. Valid searchtypes are: %s',
                $searchType,
                implode(', ', $this->searchTypes)
            )
        );
    }

    /**
     * Main scan function
     *
     * @return array
     */
    public function discover()
    {
        $devices = $this->doMSearchRequest();

        if (empty($devices)) {
            return [];
        }

        $targets = [];
        foreach ($devices as $key => $device) {
            $devices[$key] = $this->parseMSearchResponse($device);
            array_push($targets, $devices[$key]['location']);
        }

        foreach ($this->fetchUpnpXmlDeviceInfo($targets) as $location => $xml) {
            if (!empty($xml)) {
                try {
                    $simpleXML = new \SimpleXMLElement($xml);
                    if (!property_exists($simpleXML, 'URLBase')) {
                        $location = parse_url($location);
                        $simpleXML->URLBase = sprintf('%s://%s:%d/', $location['scheme'], $location['host'], $location['port']);
                    }
                    array_push($this->devices, $simpleXML);
                } catch (\Exception $e) { /* SimpleXML parsing failed */ }
            }
        }

        return $this->devices;
    }

    /**
     * Fetch all available UPnP devices via unicast
     *
     * @return array
     */
    protected function doMSearchRequest()
    {
        $request = $this->getMSearchRequest();

        $socket = socket_create(AF_INET, SOCK_DGRAM, 0);
        @socket_set_option($socket, 1, 6, true);
        socket_sendto($socket, $request, strlen($request), 0, static::$host, static::$port);
        socket_set_option($socket, SOL_SOCKET, SO_RCVTIMEO, [
            'sec' => $this->timeout,
            'usec' => '0'
        ]);

        $response = [];
        $from = null;
        $port = null;
        do {
            $buffer = null;
            @socket_recvfrom($socket, $buffer, 1024, MSG_WAITALL, $from, $port);
            if (!is_null($buffer)) {
                array_push($response, $buffer);
            }
        } while (!is_null($buffer));

        socket_close($socket);

        return $response;
    }

    /**
     * Prepare Msearch request string
     *
     * @return string
     */
    protected function getMSearchRequest()
    {
        $ssdpMessage = [
            'M-SEARCH * HTTP/1.1',
            sprintf('HOST: %s:%d', static::$host, static::$port),
            'MAN: "ssdp:discover"',
            sprintf('MX: %d', $this->delayResponse),
            sprintf('ST: %s', $this->searchType),
            sprintf('USER-AGENT: %s', $this->userAgent),
        ];

        return implode("\r\n", $ssdpMessage) . "\r\n";
    }

    /**
     * Parse response from device to a more readable format
     *
     * @param $response
     * @return array
     */
    protected function parseMSearchResponse($response)
    {
        $mapping = [
            'http' => 'http',
            'cach' => 'cache-control',
            'date' => 'date',
            'ext' => 'ext',
            'loca' => 'location',
            'serv' => 'server',
            'st:' => 'st',
            'usn:' => 'usn',
            'cont' => 'content-length',
        ];

        $parsedResponse = [];
        foreach (explode("\r\n", $response) as $resultLine) {
            foreach ($mapping as $key => $replace) {
                if (stripos($resultLine, $key) === 0) {
                    $parsedResponse[$replace] = str_ireplace($replace . ': ', '', $resultLine);
                }
            }
        }

        return $parsedResponse;
    }

    /**
     * Fetch XML's from all devices async
     *
     * @param array $targets
     * @return array
     */
    protected function fetchUpnpXmlDeviceInfo(array $targets)
    {
        $targets = array_values(array_unique($targets));
        $multi = curl_multi_init();

        $curl = $xmls = [];
        foreach ($targets as $key => $target) {
            $curl[$key] = curl_init();
            curl_setopt($curl[$key], CURLOPT_URL, $target);
            curl_setopt($curl[$key], CURLOPT_TIMEOUT, $this->timeout);
            curl_setopt($curl[$key], CURLOPT_RETURNTRANSFER, true);
            curl_multi_add_handle($multi, $curl[$key]);
        }

        $active = null;
        do {
            $mrc = curl_multi_exec($multi, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($multi) != -1) {
                do {
                    $mrc = curl_multi_exec($multi, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }

        foreach ($curl as $key => $handle) {
            $xmls[$targets[$key]] = curl_multi_getcontent($handle);
            curl_multi_remove_handle($multi, $handle);
        }

        curl_multi_close($multi);

        return $xmls;
    }

    /**
     * @return array
     */
    public function jsonSerialize()
    {
        if (empty($this->devices)) {
            $this->discover();
        }

        return [
            'total' => count($this->devices),
            'devices' => $this->devices,
        ];
    }
}
