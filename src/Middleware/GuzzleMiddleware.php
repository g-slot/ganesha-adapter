<?php
declare(strict_types=1);


namespace Gilmon\Ganesha\Middleware;

use Ackintosh\Ganesha;
use Ackintosh\Ganesha\Exception\RejectedException;
use Ackintosh\Ganesha\GuzzleMiddleware\ServiceNameExtractor;
use Ackintosh\Ganesha\GuzzleMiddleware\ServiceNameExtractorInterface;
use Gilmon\Ganesha\HttpClient\FailureDetectorInterface;
use Gilmon\Ganesha\HttpClient\RestFailureDetector;
use GuzzleHttp\Promise\Create;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class GuzzleMiddleware
{
    private Ganesha $ganesha;
    private ServiceNameExtractorInterface $serviceNameExtractor;
    private $failureDetector;

    public function __construct(
        Ganesha $ganesha,
        ServiceNameExtractorInterface $serviceNameExtractor = null,
        FailureDetectorInterface $failureDetector = null
    ) {
        $this->ganesha = $ganesha;
        $this->serviceNameExtractor = $serviceNameExtractor ?: new ServiceNameExtractor();
        $this->failureDetector = $failureDetector ?? new RestFailureDetector();
    }

    /**
     * @param  callable  $handler
     *
     * @return \Closure
     */
    public function __invoke(callable $handler): \Closure
    {
        return function (RequestInterface $request, array $options) use ($handler) {
            $serviceName = $this->serviceNameExtractor->extract($request, $options);

            if (!$this->ganesha->isAvailable($serviceName)) {
                return Create::rejectionFor(
                    new RejectedException(
                        sprintf('"%s" is not available', $serviceName)
                    )
                );
            }

            $promise = $handler($request, $options);

            return $promise->then(
                function (ResponseInterface $value) use ($serviceName) {
                    if ($this->failureDetector->isFailureResponse($value, [])) {
                        $this->ganesha->failure($serviceName);
                    } else {
                        $this->ganesha->success($serviceName);
                    }

                    return Create::promiseFor($value);
                },
                function ($reason) use ($serviceName) {
                    $this->ganesha->failure($serviceName);
                    return Create::rejectionFor($reason);
                }
            );
        };
    }
}