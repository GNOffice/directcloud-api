<?php

namespace GNOffice\DirectCloud\Exceptions;

use Exception;
use Psr\Http\Message\ResponseInterface;

class BadRequest extends Exception
{
    public function __construct(public ResponseInterface $response)
    {
        $body = json_decode($response->getBody(), true);

        if ($body !== null) {
            if (isset($body['all'])) {
                parent::__construct($body['all']);
            } elseif (isset($body['message'])) {
                parent::__construct($body['message']);
            }
        }
    }
}
