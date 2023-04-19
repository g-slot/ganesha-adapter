<?php

namespace Gilmon\Ganesha\HttpClient;

use Psr\Http\Message\ResponseInterface;

interface FailureDetectorInterface
{
    public function isFailureResponse(ResponseInterface $response, array $requestOptions): bool;
}
