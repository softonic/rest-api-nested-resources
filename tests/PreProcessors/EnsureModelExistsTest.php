<?php

namespace Softonic\RestApiNestedResources\PreProcessors;

use Illuminate\Database\Query\Builder;
use Mockery;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpKernel\Exception\ConflictHttpException;

class EnsureModelExistsTest extends TestCase
{
    private Builder $builder;

    private $model;

    private EnsureModelExists $ensureModelExists;

    #[Override]
    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = Mockery::mock(Builder::class);
        $this->model = Mockery::mock('alias:PlatformVersion');

        $this->ensureModelExists = new EnsureModelExists();
    }

    #[Override]
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    #[Test]
    public function whenModelExistsItShouldDoNothing(): void
    {
        $this->builder->shouldReceive('count')
            ->once()
            ->andReturn(1);

        $this->model->shouldReceive('where')
            ->once()
            ->with([
                'id_platform' => 'windows',
                'id_version' => 'test',
            ])
            ->andReturn($this->builder);

        $parameters = [
            'id_program' => 'f0f1ae26-e44a-460a-9f59-f53c83ec4372',
            'id_platform' => 'windows',
            'id_version' => '1.0',
            'id_platformversion' => 'test',
        ];

        $result = $this->ensureModelExists->process(
            'PlatformVersion',
            ['id_platform', 'id_version=id_platformversion'],
            $parameters
        );

        self::assertNull($result);
    }

    #[Test]
    public function whenModelDoesNotExistItShouldThrowAnException(): void
    {
        $this->builder->shouldReceive('count')
            ->once()
            ->andReturn(0);

        $this->model->shouldReceive('where')
            ->once()
            ->with([
                'id_platform' => 'windows',
                'id_version' => 'test',
            ])
            ->andReturn($this->builder);

        $parameters = [
            'id_program' => 'f0f1ae26-e44a-460a-9f59-f53c83ec4372',
            'id_platform' => 'windows',
            'id_version' => '1.0',
            'id_platformversion' => 'test',
        ];

        $parametersToCheck = [
            'id_platform' => 'windows',
            'id_version' => 'test',
        ];
        $this->expectException(ConflictHttpException::class);
        $this->expectExceptionMessage(
            'PlatformVersion resource does not exist for ' . json_encode($parametersToCheck)
        );

        $this->ensureModelExists->process(
            'PlatformVersion',
            ['id_platform', 'id_version=id_platformversion'],
            $parameters
        );
    }

    public static function fieldsDoesNotExistProvider(): array
    {
        return [
            'without any parameter' => [
                [
                    'id_program' => 'f0f1ae26-e44a-460a-9f59-f53c83ec4372',
                    'id_platform' => 'windows',
                ],
            ],
            'without some parameter#2' => [
                [
                    'id_program' => 'f0f1ae26-e44a-460a-9f59-f53c83ec4372',
                    'id_platformversion' => 'test',
                ],
            ],
        ];
    }

    #[DataProvider('fieldsDoesNotExistProvider')]
    #[Test]
    public function whenFieldsDoesNotExistItShouldDoNothing(array $parameters): void
    {
        $this->model->shouldNotReceive('where');

        $result = $this->ensureModelExists->process(
            'PlatformVersion',
            ['id_platform', 'id_version=id_platformversion'],
            $parameters
        );

        self::assertNull($result);
    }

    #[Test]
    public function whenNullParameterIsReceivedItShouldDoNothing(): void
    {
        $model = Mockery::mock('alias:Developer');
        $model->shouldNotReceive('where');

        $result = $this->ensureModelExists->process(
            'Developer',
            ['id_developer'],
            ['id_developer' => null]
        );

        self::assertNull($result);
    }
}
