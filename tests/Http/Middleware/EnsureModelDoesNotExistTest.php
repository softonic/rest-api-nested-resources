<?php

namespace Softonic\RestApiNestedResources\Http\Middleware;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Mockery;
use Mockery\MockInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class EnsureModelDoesNotExistTest extends TestCase
{
    private Builder $builder;

    private $model;

    private EnsureModelDoesNotExist $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = Mockery::mock(Builder::class);
        $this->model   = Mockery::mock('alias:PlatformVersion');

        $this->middleware = new EnsureModelDoesNotExist();
    }

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    /**
     * @test
     */
    public function whenModelDoesNotExistItShouldPassToTheNextMiddleware()
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

    /**
     * @test
     */
    public function whenModelExistsItShouldAbortTheExecution()
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
        $request = \Mockery::mock(Request::class);
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
        $next               = function (\Illuminate\Http\Request $request) use ($controllerResponse) {
            return $controllerResponse;
        };

        return [$controllerResponse, $next];
    }

    protected function whenNextMiddlewareIsNotExecuted(): \Closure
    {
        return function (Request $request) {
            self::assertTrue(false, 'The next handler must never be executed in this case.');
        };
    }
}
