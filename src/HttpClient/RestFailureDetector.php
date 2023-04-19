<?php
declare(strict_types=1);


namespace Gilmon\Ganesha\HttpClient;

use Psr\Http\Message\ResponseInterface;

class RestFailureDetector implements FailureDetectorInterface
{

    /**
     * @var int[]
     */
    public const DEFAULT_FAILURE_STATUS_CODES = [
        500, // Internal Server Error
        501, // Not Implemented
        502, // Bad Gateway ou Proxy Error
        503, // Service Unavailable
        504, // Gateway Time-out
        505, // HTTP Version not supported
        506, // Variant Also Negotiates
        507, // Insufficient storage
        508, // Loop detected
        509, // Bandwidth Limit Exceeded
        510, // Not extended
        511, // Network authentication required
        520, // Unknown Error
        521, // Web Server Is Down
        522, // Connection Timed Out
        523, // Origin Is Unreachable
        524, // A Timeout Occurred
        525, // SSL Handshake Failed
        526, // Invalid SSL Certificate
        527, // Railgun Error
    ];

    /**
     * @var int[]
     */
    private array $defaultFailureStatusCodes;

    /**
     * @param int[] $defaultFailureStatusCodes
     */
    public function __construct(?array $defaultFailureStatusCodes = null)
    {
        $this->defaultFailureStatusCodes = $defaultFailureStatusCodes ?? self::DEFAULT_FAILURE_STATUS_CODES;
    }

    public function isFailureResponse(ResponseInterface $response, array $requestOptions): bool
    {
        return $this->isFailureStatusCode($response->getStatusCode(), $requestOptions);
    }

    /**
     * @param array<string, mixed> $requestOptions
     */
    private function isFailureStatusCode(int $responseStatusCode, array $requestOptions): bool
    {
        return \in_array($responseStatusCode, $this->defaultFailureStatusCodes, true);
    }
}
