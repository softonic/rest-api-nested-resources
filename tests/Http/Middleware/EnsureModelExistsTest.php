<?php

namespace Softonic\RestApiNestedResources\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Mockery;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Softonic\RestApiNestedResources\PreProcessors\EnsureModelExists as EnsureModelExistsProcessor;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class EnsureModelExistsTest extends TestCase
{
    private EnsureModelExistsProcessor $ensureModelExistsProcessor;

    private EnsureModelExists $middleware;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->ensureModelExistsProcessor = Mockery::mock(EnsureModelExistsProcessor::class);

        $this->middleware = new EnsureModelExists($this->ensureModelExistsProcessor);
    }

    #[Test]
    public function whenModelExistsItShouldPassToTheNextMiddleware(): void
    {
        $pathParameters    = [
            'program'  => 'f0f1ae26-e44a-460a-9f59-f53c83ec4372',
            'platform' => 'windows',
        ];
        $requestParameters = [
            'id_program'         => 'f0f1ae26-e44a-460a-9f59-f53c83ec4372',
            'id_platform'        => 'mac',
            'id_version'         => '1.0',
            'id_platformversion' => 'test',
        ];
        $request           = $this->getRequest($pathParameters, $requestParameters);

        $this->ensureModelExistsProcessor->shouldReceive('process')
            ->once()
            ->with(
                'PlatformVersion',
                ['id_platform', 'id_version=id_platformversion'],
                [
                    'id_program'         => 'f0f1ae26-e44a-460a-9f59-f53c83ec4372',
                    'id_platform'        => 'windows',
                    'id_version'         => '1.0',
                    'id_platformversion' => 'test',
                ]
            );

        [$controllerResponse, $next] = $this->whenNextMiddlewareIsExecuted();

        $response = $this->middleware->handle(
            $request,
            $next,
            'PlatformVersion',
            'id_platform',
            'id_version=id_platformversion'
        );

        self::assertSame($controllerResponse, $response, 'The response must not be modified');
    }

    #[Test]
    public function whenModelDoesNotExistItShouldAbortTheExecution(): void
    {
        $pathParameters    = [
            'program'  => 'f0f1ae26-e44a-460a-9f59-f53c83ec4372',
            'platform' => 'windows',
        ];
        $requestParameters = [
            'id_program'         => 'f0f1ae26-e44a-460a-9f59-f53c83ec4372',
            'id_platform'        => 'mac',
            'id_version'         => '1.0',
            'id_platformversion' => 'test',
        ];
        $request           = $this->getRequest($pathParameters, $requestParameters);

        $this->ensureModelExistsProcessor->shouldReceive('process')
            ->once()
            ->with(
                'PlatformVersion',
                ['id_platform', 'id_version=id_platformversion'],
                [
                    'id_program'         => 'f0f1ae26-e44a-460a-9f59-f53c83ec4372',
                    'id_platform'        => 'windows',
                    'id_version'         => '1.0',
                    'id_platformversion' => 'test',
                ]
            )
            ->andThrow(new ConflictHttpException('error message'));

        $next = $this->whenNextMiddlewareIsNotExecuted();

        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage('error message');

        $this->middleware->handle(
            $request,
            $next,
            'PlatformVersion',
            'id_platform',
            'id_version=id_platformversion'
        );
    }

    private function getRequest(array $pathParameters, array $requestParameters): Request
    {
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('route->parameters')
            ->once()
            ->andReturn($pathParameters);
        $request->shouldReceive('all')
            ->once()
            ->andReturn($requestParameters);

        return $request;
    }

    private function whenNextMiddlewareIsExecuted(): array
    {
        $controllerResponse = Response::getFacadeRoot();
        $next               = fn (Request $request) => $controllerResponse;

        return [$controllerResponse, $next];
    }

    private function whenNextMiddlewareIsNotExecuted(): Closure
    {
        return function (Request $request): void {
            self::assertTrue(false, 'The next handler must never be executed in this case.');
        };
    }
}
