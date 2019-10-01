<?php
/*
 * A PHP Library to simplify the OAuth2 process with youthweb.net.
 * Copyright (C) 2016-2019  Youthweb e.V. <info@youthweb.net>

 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace Youthweb\OAuth2\Client\Provider;

use League\OAuth2\Client\Provider\ResourceOwnerInterface;

class YouthwebResourceOwner implements ResourceOwnerInterface
{
    /**
     * Domain
     *
     * @var string
     */
    protected $domain;

    /**
     * Raw response
     *
     * @var array
     */
    protected $response;

    /**
     * Creates new resource owner.
     *
     * @param array $response
     */
    public function __construct(array $response = [])
    {
        $this->response = $response;
    }

    /**
     * Get resource owner id
     *
     * @return string|null
     */
    public function getId()
    {
        return $this->response['data']['id'] ?: null;
    }

    /**
     * Get resource owner email
     *
     * @return string|null
     */
    public function getEmail()
    {
        return $this->response['data']['attributes']['email'] ?: null;
    }

    /**
     * Get resource owner name
     *
     * @return string|null
     */
    public function getName()
    {
        $name = null;

        if (isset($this->response['data']['attributes']['first_name'])) {
            $name = $this->response['data']['attributes']['first_name'] . ' ' . $this->response['data']['attributes']['last_name'];
        }

        return $name;
    }

    /**
     * Get resource owner nickname
     *
     * @return string|null
     */
    public function getNickname()
    {
        return $this->response['data']['attributes']['username'] ?: null;
    }

    /**
     * Get resource owner url
     *
     * @return string|null
     */
    public function getUrl()
    {
        return trim($this->domain . '/users/' . $this->getId()) ?: null;
    }

    /**
     * Set resource owner domain
     *
     * @param string $domain
     *
     * @return ResourceOwner
     */
    public function setDomain($domain)
    {
        $this->domain = $domain;

        return $this;
    }

    /**
     * Return all of the owner details available as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return $this->response;
    }
}
