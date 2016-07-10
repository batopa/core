<?php
/**
 * BEdita, API-first content management framework
 * Copyright 2016 ChannelWeb Srl, Chialab Srl
 *
 * This file is part of BEdita: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * See LICENSE.LGPL or <http://gnu.org/licenses/lgpl-3.0.html> for more details.
 */

namespace BEdita\API\Auth;

use Cake\Auth\BaseAuthenticate;
use Cake\Core\Configure;
use Cake\Network\Exception\UnauthorizedException;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Utility\Security;
use Firebase\JWT\JWT;

/**
 * An authentication adapter for authenticating using JSON Web Tokens.
 *
 * ```
 *  $this->Auth->config('authenticate', [
 *      'BEdita/Auth.Jwt' => [
 *          'parameter' => 'token',
 *          'userModel' => 'Users',
 *          'fields' => [
 *              'username' => 'id',
 *          ],
 *      ],
 *  ]);
 * ```
 *
 * @see http://jwt.io
 * @see http://tools.ietf.org/html/draft-ietf-oauth-json-web-token
 *
 * @since 4.0.0
 */
class JwtAuthenticate extends BaseAuthenticate
{

    /**
     * Default config for this object.
     *
     * - `header` The header where the token is stored. Defaults to `'Authorization'`.
     * - `headerPrefix` The prefix to the token in header. Defaults to `'Bearer'`.
     * - `queryParam` The query parameter where the token is passed as a fallback. Defaults to `'token'`.
     * - `allowedAlgorithms` List of supported verification algorithms. Defaults to `['HS256']`.
     *   See API of JWT::decode() for more info.
     * - `fields` The fields to use to identify a user by.
     * - `userModel` The alias for users table, defaults to Users.
     * - `finder` The finder method to use to fetch user record. Defaults to 'all'.
     *   You can set finder name as string or an array where key is finder name and value
     *   is an array passed to `Table::find()` options.
     *   E.g. ['finderName' => ['some_finder_option' => 'some_value']]
     * - `passwordHasher` Password hasher class. Can be a string specifying class name
     *    or an array containing `className` key, any other keys will be passed as
     *    config to the class. Defaults to 'Default'.
     * - Options `scope` and `contain` have been deprecated since 3.1. Use custom
     *   finder instead to modify the query to fetch user record.
     *
     * @var array
     */
    protected $_defaultConfig = [
        'header' => 'Authorization',
        'headerPrefix' => 'Bearer',
        'queryParam' => 'token',
        'allowedAlgorithms' => [
            'HS256',
            'HS512',
        ],
        'fields' => [
            'username' => 'id',
        ],
        'userModel' => 'Users',
        'scope' => [],
        'finder' => 'all',
        'contain' => null,
        'passwordHasher' => 'Default',
        'queryDatasource' => false,
    ];

    /**
     * Parsed token.
     *
     * @var string|null
     */
    protected $token = null;

    /**
     * Payload data.
     *
     * @var object|null
     */
    protected $payload = null;

    /**
     * Exception.
     *
     * @var \Exception
     */
    protected $error;

    /**
     * Get user record based on info available in JWT.
     *
     * @param \Cake\Network\Request $request The request object.
     * @param \Cake\Network\Response $response Response object.
     * @return array|false User record array or false on failure.
     */
    public function authenticate(Request $request, Response $response)
    {
        return $this->getUser($request);
    }

    /**
     * Get user record based on info available in JWT.
     *
     * @param \Cake\Network\Request $request Request object.
     * @return array|false User record array or false on failure.
     */
    public function getUser(Request $request)
    {
        $payload = $this->getPayload($request);

        if (!$this->_config['queryDatasource'] && !isset($payload->sub)) {
            return (array)$payload;
        }

        if (!isset($payload->sub)) {
            return false;
        }

        $user = $this->_findUser($payload->sub);

        return $user;
    }

    /**
     * Get payload data.
     *
     * @param \Cake\Network\Request|null $request Request instance or null
     * @return object|null Payload object on success, null on failure.
     * @throws \Exception Throws an exception if the token could not be decoded and debug is active.
     */
    public function getPayload(Request $request = null)
    {
        if (!$request) {
            return $this->payload;
        }

        $token = $this->getToken($request);
        if ($token) {
            return $this->payload = $this->decode($token);
        }

        return null;
    }

    /**
     * Get token from header or query string.
     *
     * @param \Cake\Network\Request|null $request Request object.
     * @return string|null Token string if found else null.
     */
    public function getToken(Request $request = null)
    {
        $config = $this->_config;

        if (!$request) {
            return $this->token;
        }

        $header = trim($request->header($config['header']));
        $headerPrefix = strtolower(trim($config['headerPrefix'])) . ' ';
        $headerPrefixLength = strlen($headerPrefix);
        if ($header && strtolower(substr($header, 0, $headerPrefixLength)) == $headerPrefix) {
            return $this->token = substr($header, $headerPrefixLength);
        }

        if (!empty($this->_config['queryParam'])) {
            return $this->token = $request->query($this->_config['queryParam']);
        }

        return null;
    }

    /**
     * Decode JWT token.
     *
     * @param string $token JWT token to decode.
     * @return object|null The token's payload as a PHP object, `null` on failure.
     * @throws \Exception Throws an exception if the token could not be decoded and debug is active.
     */
    protected function decode($token)
    {
        try {
            $payload = JWT::decode($token, Security::salt(), $this->_config['allowedAlgorithms']);

            return $payload;
        } catch (\Exception $e) {
            if (Configure::read('debug')) {
                throw $e;
            }

            $this->error = $e;
        }

        return null;
    }

    /**
     * Handles an unauthenticated access attempt.
     *
     * @param \Cake\Network\Request $request A request object.
     * @param \Cake\Network\Response $response A response object.
     * @return void
     * @throws \Cake\Network\Exception\UnauthorizedException Throws an exception.
     */
    public function unauthenticated(Request $request, Response $response)
    {
        $message = $this->_registry->getController()->Auth->config('authError');
        if ($this->error) {
            $message = $this->error->getMessage();
        }

        throw new UnauthorizedException($message);
    }
}
