<?php

namespace Softonic\RestApiNestedResources\Http\Middleware;

use Closure;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Mockery;
use Mockery\MockInterface;
use Override;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class EnsureModelDoesNotExistTest extends TestCase
{
    private Builder $builder;

    private $model;

    private EnsureModelDoesNotExist $middleware;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = Mockery::mock(Builder::class);
        $this->model   = Mockery::mock('alias:PlatformVersion');

        $this->middleware = new EnsureModelDoesNotExist();
    }

    #[Override]
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    #[Test]
    public function whenModelDoesNotExistItShouldPassToTheNextMiddleware(): void
    {
        $pathParameters    = [
            'platform' => 'windows',
        ];
        $requestParameters = [
            'id_platform' => 'mac',
            'id_version'  => 'test',
            'name'        => 'windows test',
        ];

        $request = $this->getRequest($pathParameters, $requestParameters);

        $this->builder->shouldReceive('count')
            ->once()
            ->andReturn(0);

        $this->model->shouldReceive('where')
            ->once()
            ->with([
                'id_platform' => 'windows',
                'id_version'  => 'test',
            ])
            ->andReturn($this->builder);

        [$controllerResponse, $next] = $this->whenNextMiddlewareIsExecuted();

        $response = $this->middleware->handle(
            $request,
            $next,
            'PlatformVersion',
            'id_platform',
            'id_version'
        );

        self::assertSame($controllerResponse, $response, 'The response must not be modified');
    }

    #[Test]
    public function whenModelExistsItShouldAbortTheExecution(): void
    {
        $pathParameters    = [
            'platform' => 'windows',
        ];
        $requestParameters = [
            'id_platform' => 'mac',
            'id_version'  => 'test',
            'name'        => 'windows test',
        ];

        $request = $this->getRequest($pathParameters, $requestParameters);

        $this->builder->shouldReceive('count')
            ->once()
            ->andReturn(1);

        $this->model->shouldReceive('where')
            ->once()
            ->with([
                'id_platform' => 'windows',
                'id_version'  => 'test',
            ])
            ->andReturn($this->builder);

        $next = $this->whenNextMiddlewareIsNotExecuted();

        $parametersToCheck = [
            'id_platform' => 'windows',
            'id_version'  => 'test',
        ];
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage(
            'PlatformVersion resource already exists for ' . json_encode($parametersToCheck)
        );

        $this->middleware->handle(
            $request,
            $next,
            'PlatformVersion',
            'id_platform',
            'id_version'
        );
    }

    private function getRequest(array $pathParameters, array $requestParameters): MockInterface
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

    protected function whenNextMiddlewareIsExecuted(): array
    {
        $controllerResponse = Response::getFacadeRoot();
        $next               = fn (Request $request) => $controllerResponse;

        return [$controllerResponse, $next];
    }

    protected function whenNextMiddlewareIsNotExecuted(): Closure
    {
        return function (Request $request): void {
            self::assertTrue(false, 'The next handler must never be executed in this case.');
        };
    }
}
