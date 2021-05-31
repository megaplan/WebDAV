<?php
/**
 * This file is part of the WebDav package.
 *
 * (c) Geoffroy Letournel <geoffroy.letournel@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Grale\WebDav\Exception;

use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Psr7\Request;

/**
 *
 *
 * @author Geoffroy Letournel <geoffroy.letournel@gmail.com>
 */
abstract class HttpException extends \RuntimeException
{
    /**
     * @var string
     */
    protected $request;

    /**
     * @var string
     */
    protected $response;

    /**
     * @var int
     */
    protected $statusCode;

    /**
     * @var string
     */
    protected $description;

    /**
     * @var string
     */
    protected static $descriptionMapping = array(
        'MOVE' => array(
            403 => 'Source and destination URIs are the same',
            409 => 'One or more parent collections are not found',
            412 => 'The server was unable to maintain the availability of the properties',
            423 => 'The source or the destination resource was locked',
            502 => 'The destination server refuses to accept the resource'
        ),
        'COPY' => array(
            403 => 'Source and destination URIs are the same',
            409 => 'One or more parent collections are not found',
            412 => 'The server was unable to maintain the availability of the properties',
            423 => 'The destination resource was locked',
            502 => 'The destination server refuses to accept the resource',
            507 => 'Insufficient storage'
        ),
        'LOCK' => array(
            412 => 'The lock token could not be enforced',
            423 => 'The resource is already locked'
        ),
        'MKCOL' => array(
            403 => 'Permissions denied',
            405 => 'The resource already exists',
            409 => 'Cannot create a resource if all ancestors do not already exist',
            415 => 'The server does not support the request type of the body',
            507 => 'Insufficient storage'
        ),
        'PROPPATCH' => array(
            403 => 'Properties cannot be set or removed',
            409 => 'Cannot set property to value provided',
            423 => 'The resource is locked',
            507 => 'Insufficient storage'
        )
    );

    /**
     * @param Request $request
     * @param Response $response
     * @return self
     */
    public static function factory(Request $request, Response $response)
    {

        if ($response->getStatusCode() >= 400 && $response->getStatusCode() < 500) {
            $class = __NAMESPACE__ . '\\ClientFailureException';
        } else {
            $class = ServerFailureException::class;
        }

        $exception = new $class($response->getReasonPhrase(), null);

        if ($exception instanceof HttpException) {
            $statusCode = $response->getStatusCode();

            $exception->setStatusCode($statusCode);
            $exception->setResponse($response->getReasonPhrase());
            $exception->setRequest($request->getBody());

            if (isset(self::$descriptionMapping[$request->getMethod()][$statusCode])) {
                $exception->setDescription(self::$descriptionMapping[$request->getMethod()][$statusCode]);
            }
        }

        return $exception;
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @param int $statusCode
     */
    public function setStatusCode($statusCode)
    {
        $this->statusCode = (int)$statusCode;
    }

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $message
     */
    public function setDescription($message)
    {
        $this->description = (string)$message;
    }

    /**
     * @return string
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param string $message
     */
    public function setResponse($message)
    {
        $this->response = (string)$message;
    }

    /**
     * @return string
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param string $message
     */
    public function setRequest($message)
    {
        $this->request = (string)$message;
    }
}
