<?php

namespace Softonic\RestApiNestedResources\Http\Middleware;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Contracts\Routing\UrlRoutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use Softonic\RestApiNestedResources\Models\MultiKeyModel;

class SubstituteBindings
{
    /**
     * The router instance.
     */
    protected Registrar $router;

    /**
     * The IoC container instance.
     */
    protected Container $container;

    /**
     * Create a new bindings substitutor.
     */
    public function __construct(Registrar $router, Container $container = null)
    {
        $this->router    = $router;
        $this->container = $container ?: new Container();
    }

    /**
     * Handle an incoming request.
     *
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $this->router->substituteBindings($route = $request->route());

        $this->substituteImplicitBindings($route);

        return $next($request);
    }

    /**
     * Substitute the implicit Eloquent model bindings for the route.
     */
    protected function substituteImplicitBindings(Route $route): void
    {
        $this->resolveForRoute($route);
    }

    /**
     * Resolve the implicit route bindings for the given route.
     */
    protected function resolveForRoute(Route $route): void
    {
        $parameters = $route->parameters();

        foreach ($route->signatureParameters(UrlRoutable::class) as $parameter) {
            if (!$pathParameters = static::getPathParameters($parameter->name, $parameters)) {
                continue;
            }

            if ($pathParameters instanceof UrlRoutable) {
                continue;
            }

            $instance = $this->container->make($parameter->getType()->getName());

            try {
                $id    = ($instance instanceof MultiKeyModel)
                    ? $instance::generateIdForField($instance->getKeyName(), $pathParameters)
                    : $pathParameters[$instance->getKeyName()];
                $model = $instance::findOrFail($id);
            } catch (ModelNotFoundException $e) {
                throw new ModelNotFoundException(
                    "{$e->getModel()} resource not found for " . json_encode($pathParameters, JSON_THROW_ON_ERROR)
                );
            }

            foreach (array_keys($parameters) as $parameterName) {
                $route->forgetParameter($parameterName);
            }
            $route->setParameter($parameter->name, $model);
        }
    }

    /**
     * Return the path parameters prepending the "id_" string to them.
     */
    protected static function getPathParameters(string $name, array $parameters): array
    {
        $pathParameters = [];
        $snakeName      = Str::snake($name);

        foreach ($parameters as $parameter => $value) {
            $pathParameters['id_' . $parameter] = $value;

            $snakeName = Str::after($snakeName, $parameter);

            $snakeName = Str::after($snakeName, '_');
        }

        return $pathParameters;
    }
}
