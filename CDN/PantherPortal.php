<?php
/*
 * This file is part of the Sonata project.
 *
 * (c) Thomas Rabaix <thomas.rabaix@sonata-project.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Sonata\MediaBundle\CDN;

/**
 *
 *  From https://pantherportal.cdnetworks.com/wsdl/flush.wsdl
 *
 *  flushRequest:
 *     username, password: credentials of a user that has web service access.
 *     siteId: the numeric id of the site to flush from.
 *     flushtype: the type of flush: "all" or "paths".
 *     paths: a newline-separated list of paths to flush. empty if flushtype is "all".
 *     wildcard: a boolean to activate wildcard mode. may be true only when flushtype is "paths".
 *     use_ims: a boolean to activate fetching with If-Modified-Since. may be true only when flushtype is "all" or wildcard is true.
 *
 *     Please see the documentation available on the Panther Customer Portal
 *     for more detail about the effects of these parameters.
 *
 *    Please note that an error with response "Over the limit of flush requests per hour" means that the flush is rate limited.
 *    Any requests until the rate limit is no longer exceeded will receive this response.
 */
class PantherPortal implements CDNInterface
{
    protected $path;

    protected $username;

    protected $password;

    protected $siteId;

    protected $client;

    protected $wsdl;

    /**
     * @param $path
     * @param $username
     * @param $password
     * @param $siteId
     * @param string $wsdl
     */
    public function __construct($path, $username, $password, $siteId, $wsdl = "https://pantherportal.cdnetworks.com/wsdl/flush.wsdl")
    {
        $this->path     = $path;
        $this->username = $username;
        $this->password = $password;
        $this->siteId   = $siteId;
        $this->wsdl     = $wsdl;
    }

    /**
     * @param $relativePath
     * @param bool $isFlushable
     * @return string
     */
    public function getPath($relativePath, $isFlushable = false)
    {
        return sprintf('%s/%s', $this->path, $relativePath);
    }

    /**
     * Flush a set of ressource matching the provided string
     *
     * @param string $string
     * @return void
     */
    public function flushByString($string)
    {
        $this->flushPaths(array($string));
    }

    /**
     * Flush the ressource
     *
     * @param string $string
     * @return void
     */
    public function flush($string)
    {
        $this->flushPaths(array($string));
    }

    /**
     * Flush different set of ressource matching the provided string array
     *
     * @param array $paths
     * @return void
     */
    public function flushPaths(array $paths)
    {
        $result = $this->getClient()->flush($this->username, $this->password, "paths", $this->siteId, implode("\n",  $paths), true, false);

        if ($result != "Flush successfully submitted.") {
            throw new \RuntimeException('Unable to flush : '. $result);
        }
    }

    /**
     * Return a SoapClient
     *
     * @return \SoapClient
     */
    public function getClient()
    {
        if (!$this->client) {
            $this->client = new \SoapClient($this->wsdl);
        }

        return $this->client;
    }

    /**
     * @param \SoapClient $client
     * @return void
     */
    public function setClient(\SoapClient $client)
    {
        $this->client = $client;
    }
}