<?php declare(strict_types = 1);

namespace Nextras\OrmPhpStan\Types;

use Nextras\Orm\Collection\ICollection;
use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\ArrayType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;


class CollectionReturnTypeExtension implements DynamicMethodReturnTypeExtension
{
	public function getClass(): string
	{
		return ICollection::class;
	}


	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		static $methods = [
			'getBy',
			'getById',
			'findBy',
			'orderBy',
			'resetOrderBy',
			'limitBy',
			'applyFunction',
			'fetch',
			'fetchAll',
		];
		return in_array($methodReflection->getName(), $methods, true);
	}


	public function getTypeFromMethodCall(
		MethodReflection $methodReflection,
		MethodCall $methodCall,
		Scope $scope
	): Type
	{
		$varType = $scope->getType($methodCall->var);
		$methodName = $methodReflection->getName();

		if (!$varType instanceof IntersectionType) {
			return ParametersAcceptorSelector::selectSingle($methodReflection->getVariants())->getReturnType();
		}

		static $collectionReturnMethods = [
			'findBy',
			'orderBy',
			'resetOrderBy',
			'limitBy',
			'applyFunction',
		];

		static $entityReturnMethods = [
			'getBy',
			'getById',
			'fetch',
		];

		if (in_array($methodName, $collectionReturnMethods, true)) {
			return $varType;

		} elseif (in_array($methodName, $entityReturnMethods, true)) {
			return TypeCombinator::addNull($varType->getIterableValueType());

		} elseif ($methodName === 'fetchAll') {
			return new ArrayType(new IntegerType(), $varType->getIterableValueType());
		}

		throw new ShouldNotHappenException();
	}
}
