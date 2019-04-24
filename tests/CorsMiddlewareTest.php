<?php declare(strict_types=1);

namespace Qlimix\Tests\HttpMiddleware;

use Neomerx\Cors\Contracts\AnalysisResultInterface;
use Neomerx\Cors\Contracts\AnalyzerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Qlimix\HttpMiddleware\CorsMiddleware;

final class CorsMiddlewareTest extends TestCase
{
    /** @var MockObject */
    private $analyzer;

    /** @var MockObject */
    private $responseFactory;

    /** @var MockObject */
    private $request;

    /** @var MockObject */
    private $handler;

    /** @var CorsMiddleware */
    private $corsMiddleware;

    public function setUp(): void
    {
        $this->analyzer = $this->createMock(AnalyzerInterface::class);
        $this->responseFactory = $this->createMock(ResponseFactoryInterface::class);

        $this->request = $this->createMock(ServerRequestInterface::class);
        $this->handler = $this->createMock(RequestHandlerInterface::class);

        $this->corsMiddleware = new CorsMiddleware($this->analyzer, $this->responseFactory);
    }

    /**
     * @test
     */
    public function shouldHandleRequestWithCors(): void
    {
        $result = $this->createMock(AnalysisResultInterface::class);

        $result->expects($this->once())
            ->method('getRequestType')
            ->willReturn(AnalysisResultInterface::TYPE_ACTUAL_REQUEST);

        $resultHeaders = ['foo' => 'bar'];

        $result->expects($this->once())
            ->method('getResponseHeaders')
            ->willReturn($resultHeaders);

        $this->analyzer->expects($this->once())
            ->method('analyze')
            ->willReturn($result);

        $response = $this->createMock(ResponseInterface::class);

        $headers = [];

        $response->expects($this->once())
            ->method('withHeader')
            ->willReturnCallback(static function (string $key, string $value) use (&$headers, $response) {
                $headers[$key] = $value;
                return $response;
            });

        $this->handler->expects($this->once())
            ->method('handle')
            ->willReturn($response);

        $this->corsMiddleware->process($this->request, $this->handler);

        $this->assertSame($headers, $resultHeaders);
    }

    /**
     * @test
     */
    public function shouldHandleRequestWithoutCors(): void
    {
        $result = $this->createMock(AnalysisResultInterface::class);

        $result->expects($this->once())
            ->method('getRequestType')
            ->willReturn(AnalysisResultInterface::TYPE_REQUEST_OUT_OF_CORS_SCOPE);

        $this->analyzer->expects($this->once())
            ->method('analyze')
            ->willReturn($result);

        $this->handler->expects($this->once())
            ->method('handle')
            ->willReturn($this->createMock(ResponseInterface::class));

        $this->corsMiddleware->process($this->request, $this->handler);
    }

    /**
     * @test
     */
    public function shouldHandlePreFlightRequest(): void
    {
        $result = $this->createMock(AnalysisResultInterface::class);

        $result->expects($this->once())
            ->method('getRequestType')
            ->willReturn(AnalysisResultInterface::TYPE_PRE_FLIGHT_REQUEST);

        $resultHeaders = ['foo' => 'bar'];

        $result->expects($this->once())
            ->method('getResponseHeaders')
            ->willReturn($resultHeaders);

        $this->analyzer->expects($this->once())
            ->method('analyze')
            ->willReturn($result);

        $response = $this->createMock(ResponseInterface::class);

        $this->responseFactory->expects($this->once())
            ->method('createResponse')
            ->willReturnCallback(static function (int $statusCode, string $reasonPhrase = '') use ($response) {
                $response->method('getStatusCode')
                    ->willReturn($statusCode);

                return $response;
            });

        $headers = [];

        $response->expects($this->once())
            ->method('withHeader')
            ->willReturnCallback(static function (string $key, string $value) use (&$headers, $response) {
                $headers[$key] = $value;
                return $response;
            });

        $this->corsMiddleware->process($this->request, $this->handler);

        $this->assertSame($headers, $resultHeaders);
    }

    public function analyzeResultsProvider(): array
    {
        return [
            [AnalysisResultInterface::ERR_ORIGIN_NOT_ALLOWED],
            [AnalysisResultInterface::ERR_METHOD_NOT_SUPPORTED],
            [AnalysisResultInterface::ERR_HEADERS_NOT_SUPPORTED],
            [AnalysisResultInterface::ERR_NO_HOST_HEADER],
        ];
    }

    /**
     * @dataProvider analyzeResultsProvider
     *
     * @test
     */
    public function shouldCreateForbiddenResponse(int $requestType): void
    {
        $result = $this->createMock(AnalysisResultInterface::class);

        $result->expects($this->once())
            ->method('getRequestType')
            ->willReturn($requestType);

        $this->analyzer->expects($this->once())
            ->method('analyze')
            ->willReturn($result);

        $this->responseFactory->expects($this->once())
            ->method('createResponse')
            ->willReturnCallback(function (int $statusCode, string $reasonPhrase = '') {
                $response = $this->createMock(ResponseInterface::class);
                $response->method('getStatusCode')
                    ->willReturn($statusCode);

                return $response;
            });

        $response = $this->corsMiddleware->process($this->request, $this->handler);

        $this->assertSame($response->getStatusCode(), 403);
    }
}
