<?php
/**
 * MercadoLibre PHP SDK
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE file
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright 2018 Lucas Banegas <lucasconobanegas@gmail.com>
 * @license https://opensource.org/licenses/MIT MIT License
 * @author Lucas Banegas <lucasconobanegas@gmail.com>
 * @link https://github.com/docta/mercadolibre Repository
 * @link https://docta.github.io/mercadolibre Documentation
 */
namespace Docta\MercadoLibre\Exception;

use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

/**
 * Represents an exception for the client.
 */
class ClientException extends IdentityProviderException
{
    /**
     * Returns the short error message
     *
     * @return string|null
     */
    public function getError()
    {
        return is_array($this->response) && array_key_exists('message', $this->response)
            ? $this->response['message'] : null;
    }

    /**
     * Returns causes of error
     *
     * @return array|null
     */
    public function getCause()
    {
        return is_array($this->response) && array_key_exists('cause', $this->response)
            ? $this->response['cause'] : null;
    }
}
