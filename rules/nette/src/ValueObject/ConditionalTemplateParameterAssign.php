<?php

declare(strict_types=1);

namespace Rector\Nette\ValueObject;

use PhpParser\Node\Expr\Assign;

final class ConditionalTemplateParameterAssign
{
    /**
     * @var Assign
     */
    private $assign;

    /**
     * @var string
     */
    private $parameterName;

    public function __construct(Assign $assign, string $parameterName)
    {
        $this->assign = $assign;
        $this->parameterName = $parameterName;
    }

    public function getAssign(): Assign
    {
        return $this->assign;
    }

    public function getParameterName(): string
    {
        return $this->parameterName;
    }
}
