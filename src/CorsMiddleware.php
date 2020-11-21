<?php declare(strict_types=1);

namespace Qlimix\HttpMiddleware;

use Neomerx\Cors\Contracts\AnalysisResultInterface;
use Neomerx\Cors\Contracts\AnalyzerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class CorsMiddleware implements MiddlewareInterface
{
    private AnalyzerInterface $analyzer;
    private ResponseFactoryInterface $response;

    public function __construct(AnalyzerInterface $analyzer, ResponseFactoryInterface $response)
    {
        $this->analyzer = $analyzer;
        $this->response = $response;
    }

    /**
     * @inheritdoc
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $result = $this->analyzer->analyze($request);

        switch ($result->getRequestType()) {
            case AnalysisResultInterface::ERR_NO_HOST_HEADER:
            case AnalysisResultInterface::ERR_ORIGIN_NOT_ALLOWED:
            case AnalysisResultInterface::ERR_METHOD_NOT_SUPPORTED:
            case AnalysisResultInterface::ERR_HEADERS_NOT_SUPPORTED:
                return $this->response->createResponse(403);
            case AnalysisResultInterface::TYPE_PRE_FLIGHT_REQUEST:
                return $this->addCorsHeaders($this->response->createResponse(204), $result);
            case AnalysisResultInterface::TYPE_REQUEST_OUT_OF_CORS_SCOPE:
                return $handler->handle($request);
            default:
                return $this->addCorsHeaders($handler->handle($request), $result);
        }
    }

    private function addCorsHeaders(ResponseInterface $response, AnalysisResultInterface $result): ResponseInterface
    {
        $corsHeaders = $result->getResponseHeaders();
        foreach ($corsHeaders as $name => $value) {
            $response = $response->withHeader($name, $value);
        }

        return $response;
    }
}
