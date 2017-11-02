<?php declare(strict_types=1);

namespace Rector\BetterReflection\Reflector;

use phpDocumentor\Reflection\Types\Object_;
use phpDocumentor\Reflection\Types\Static_;
use Rector\BetterReflection\Reflection\ReflectionMethod;
use Rector\BetterReflection\Reflector\Exception\IdentifierNotFound;

final class MethodReflector
{
    /**
     * @var SmartClassReflector
     */
    private $smartClassReflector;

    public function __construct(SmartClassReflector $smartClassReflector)
    {
        $this->smartClassReflector = $smartClassReflector;
    }

    public function reflectClassMethod(string $class, string $method): ?ReflectionMethod
    {
        try {
            $classReflection = $this->smartClassReflector->reflect($class);
        } catch (IdentifierNotFound $identifierNotFoundException) {
            return null;
        }

        if ($classReflection === null) {
            return null;
        }

        return $classReflection->getImmediateMethods()[$method] ?? null;
    }

    public function getMethodReturnType(string $class, string $methodCallName): ?string
    {
        $methodReflection = $this->reflectClassMethod($class, $methodCallName);

        if (! $methodReflection) {
            return null;
        }

        $returnType = $methodReflection->getReturnType();
        if ($returnType) {
            return (string) $returnType;
        }

        $returnTypes = $methodReflection->getDocBlockReturnTypes();
        if (! isset($returnTypes[0])) {
            return null;
        }

        if ($returnTypes[0] instanceof Object_) {
            return ltrim((string) $returnTypes[0]->getFqsen(), '\\');
        }

        if ($returnTypes[0] instanceof Static_ || $returnTypes[0] instanceof Self_) {
            return $class;
        }

        return null;
    }

    /**
     * @param string[] $types
     * @return string[]
     */
    public function resolveReturnTypesForTypesAndMethod(array $types, string $method): array
    {
        if (! count($types)) {
            return [];
        }

        $type = $types[0];

        $returnType = $this->getMethodReturnType($type, $method);

        if ($returnType === 'self') {
            return $types;
        }

        return [$returnType];
    }
}
