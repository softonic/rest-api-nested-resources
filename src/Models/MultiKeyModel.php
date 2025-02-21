<?php

namespace Softonic\RestApiNestedResources\Models;

use Illuminate\Database\Eloquent\Model;
use Override;
use Ramsey\Uuid\Uuid;

abstract class MultiKeyModel extends Model
{
    /**
     * Identifiers to be hashed and used in the real primary and foreign keys.
     */
    protected static array $generatedIds = [];

    /**
     * Identifiers to be hashed and used in the variable relations.
     */
    protected static array $variableGeneratedIds = [];

    public static function getGeneratedIdsConfig(): array
    {
        return array_merge(static::$generatedIds, static::$variableGeneratedIds);
    }

    public static function getGeneratedIds(array $values, ?array $generatedIdsConfig): array
    {
        $generatedIds = [];
        foreach (array_keys($generatedIdsConfig) as $field) {
            $generatedIds[$field] = self::generateIdForField($field, $values, $generatedIdsConfig);
        }

        return $generatedIds;
    }

    public static function generateIdForField(string $field, array $values, array $generatedIds = null): string
    {
        $generatedIds ??= static::$generatedIds;

        // In order to calculate the uuids, the parameters need to be always in the same order.
        ksort($values);

        return self::generateUuidFromValues(array_intersect_key($values, array_flip($generatedIds[$field])));
    }

    #[Override]
    protected static function boot()
    {
        parent::boot();

        self::creating(self::generateIds(array_merge(static::$generatedIds, static::$variableGeneratedIds)));
        self::updating(self::generateIds(static::$variableGeneratedIds));
    }

    protected static function generateIds(array $generatedIdsConfig): callable
    {
        return function (Model $model) use ($generatedIdsConfig): void {
            $generatedIds = self::getGeneratedIds($model->toArray(), $generatedIdsConfig);
            foreach ($generatedIds as $field => $value) {
                $model->setAttribute($field, $value);
            }
        };
    }

    protected static function generateUuidFromValues(array $values): string
    {
        return Uuid::uuid5(Uuid::NAMESPACE_DNS, implode('', $values))->toString();
    }
}
