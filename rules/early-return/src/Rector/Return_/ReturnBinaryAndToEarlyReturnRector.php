<?php

declare(strict_types=1);

namespace Rector\EarlyReturn\Rector\Return_;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\BinaryOp\BooleanAnd;
use PhpParser\Node\Stmt\If_;
use PhpParser\Node\Stmt\Return_;
use Rector\Core\NodeManipulator\IfManipulator;
use Rector\Core\PhpParser\Node\AssignAndBinaryMap;
use Rector\Core\Rector\AbstractRector;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\CodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;

/**
 * @see \Rector\EarlyReturn\Tests\Rector\Return_\ReturnBinaryAndToEarlyReturnRector\ReturnBinaryAndToEarlyReturnRectorTest
 */
final class ReturnBinaryAndToEarlyReturnRector extends AbstractRector
{
    /**
     * @var IfManipulator
     */
    private $ifManipulator;

    /**
     * @var AssignAndBinaryMap
     */
    private $assignAndBinaryMap;

    public function __construct(IfManipulator $ifManipulator, AssignAndBinaryMap $assignAndBinaryMap)
    {
        $this->ifManipulator = $ifManipulator;
        $this->assignAndBinaryMap = $assignAndBinaryMap;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Changes Single return of && to early returns', [
            new CodeSample(
                <<<'CODE_SAMPLE'
class SomeClass
{
    public function accept($something, $somethingelse)
    {
        return $something && $somethingelse;
    }
}
CODE_SAMPLE

                ,
                <<<'CODE_SAMPLE'
class SomeClass
{
    public function accept($something, $somethingelse)
    {
        if (!$something) {
            return false;
        }
        return (bool) $somethingelse;
    }
}
CODE_SAMPLE
            ),
        ]);
    }

    /**
     * @return string[]
     */
    public function getNodeTypes(): array
    {
        return [Return_::class];
    }

    /**
     * @param Return_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (! $node->expr instanceof BooleanAnd) {
            return null;
        }

        $left = $node->expr->left;
        $ifNegations = $this->createMultipleIfsNegation($left, $node, []);

        foreach ($ifNegations as $key => $ifNegation) {
            if ($key === 0) {
                $this->mirrorComments($ifNegation, $node);
            }

            $this->addNodeBeforeNode($ifNegation, $node);
        }

        $lastReturnExpr = $this->assignAndBinaryMap->getTruthyExpr($node->expr->right);
        $this->addNodeBeforeNode(new Return_($lastReturnExpr), $node);
        $this->removeNode($node);

        return $node;
    }

    /**
     * @param If_[] $ifNegations
     * @return If_[]
     */
    private function createMultipleIfsNegation(Expr $expr, Return_ $return, array $ifNegations): array
    {
        while ($expr instanceof BooleanAnd) {
            $ifNegations = array_merge($ifNegations, $this->collectLeftBooleanAndToIfs($expr, $return, $ifNegations));
            $ifNegations[] = $this->ifManipulator->createIfNegation(
                $expr->right,
                new Return_($this->nodeFactory->createFalse())
            );

            $expr = $expr->right;
        }
        return $ifNegations + [
            $this->ifManipulator->createIfNegation($expr, new Return_($this->nodeFactory->createFalse())),
        ];
    }

    /**
     * @param If_[] $ifNegations
     * @return If_[]
     */
    private function collectLeftBooleanAndToIfs(BooleanAnd $booleanAnd, Return_ $return, array $ifNegations): array
    {
        $left = $booleanAnd->left;
        if (! $left instanceof BooleanAnd) {
            return [$this->ifManipulator->createIfNegation($left, new Return_($this->nodeFactory->createFalse()))];
        }

        return $this->createMultipleIfsNegation($left, $return, $ifNegations);
    }
}
