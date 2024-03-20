<?php

namespace App;

use App\Contracts\EntityManagerServiceInterface;
use App\Services\EntityManagerService;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Slim\Interfaces\InvocationStrategyInterface;

class RouteEntityBindingStrategy implements InvocationStrategyInterface
{
    // basically here we choose how to invoke our route methods
    // $callable is what we get from the route binding [class, $method]
    // with reflection we get the needed arguments and return the callback

    public function __construct(
        private readonly EntityManagerServiceInterface $entityManagerService,
        private readonly ResponseFactoryInterface      $responseFactory
    ) {
    }

    public function __invoke(
        callable               $callable,
        ServerRequestInterface $request,
        ResponseInterface      $response,
        array                  $routeArguments
    ): ResponseInterface {
        // get the reflection from the callable
        $routeMethodReflection = $this->createReflectionFromCallable($callable);
        $resolvedArguments     = [];

        foreach ($routeMethodReflection->getParameters() as $parameter) {
            $type          = $parameter->getType();
            $typeName      = $type->getName();
            $parameterName = $parameter->getName();

            // check if the type is builtin ( for the array strategy )

            if ($type->isBuiltin()) {
                if ($typeName === 'array' && $parameterName === 'args') {
                    $resolvedArguments[] = $routeArguments;
                }
            } // set request and response and entity binding
            else {
                if ($typeName === ServerRequestInterface::class) {
                    $resolvedArguments[] = $request;
                    continue;
                }
                if ($typeName === ResponseInterface::class) {
                    $resolvedArguments[] = $response;
                    continue;
                }

                // we need to set ids in route bindings to actually match route method param names
                $entityId = $routeArguments[$parameterName] ?? null;

                if (! $entityId || $parameter->allowsNull()) {
                    throw new \InvalidArgumentException(
                        'Unable to resolve argument "' . $parameterName . '" in the callable'
                    );
                } else {
                    $entity = $this->entityManagerService->getRepository($typeName)->find($entityId);

                    if (! $entity) {
                       return $this->responseFactory->createResponse(404, 'Resource not found');
                    }

                    $resolvedArguments[] = $entity;
                }
            }
        }


        return $callable(...$resolvedArguments);
    }

    public function createReflectionFromCallable($callable): \ReflectionFunctionAbstract
    {
        return is_array($callable)
            ? new \ReflectionMethod($callable[0], $callable[1])
            : new \ReflectionFunction($callable);
    }
}