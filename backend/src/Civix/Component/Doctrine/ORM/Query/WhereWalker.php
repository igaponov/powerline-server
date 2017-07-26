<?php

namespace Civix\Component\Doctrine\ORM\Query;

use Doctrine\ORM\Query\TreeWalkerAdapter;
use Doctrine\ORM\Query\AST\Node;
use Doctrine\ORM\Query\AST\SelectStatement;
use Doctrine\ORM\Query\AST\WhereClause;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\AST\LikeExpression;
use Doctrine\ORM\Query\AST\ComparisonExpression;
use Doctrine\ORM\Query\AST\Literal;
use Doctrine\ORM\Query\AST\ConditionalExpression;
use Doctrine\ORM\Query\AST\ConditionalFactor;
use Doctrine\ORM\Query\AST\ConditionalPrimary;
use Doctrine\ORM\Query\AST\ConditionalTerm;

class WhereWalker extends TreeWalkerAdapter
{
    /**
     * Filter key columns hint name
     */
    const HINT_CURSOR_FILTER_COLUMNS = 'knp_paginator.filter.columns';

    /**
     * Filter value hint name
     */
    const HINT_CURSOR_FILTER_VALUE = 'knp_paginator.filter.value';

    /**
     * Walks down a SelectStatement AST node, modifying it to
     * filter the query like requested by url
     *
     * @param  SelectStatement $AST
     * @return void
     */
    public function walkSelectStatement(SelectStatement $AST)
    {
        $query = $this->_getQuery();
        $queriedValue = $query->getHint(self::HINT_CURSOR_FILTER_VALUE);
        $columns = $query->getHint(self::HINT_CURSOR_FILTER_COLUMNS);
        $components = $this->_getQueryComponents();
        $filterExpressions = array();
        $expressions = array();
        foreach ($columns as $column) {
            $alias = false;
            $parts = explode('.', $column);
            $field = end($parts);
            if (2 <= count($parts)) {
                $alias = reset($parts);
                if (!array_key_exists($alias, $components)) {
                    throw new \UnexpectedValueException("There is no component aliased by [{$alias}] in the given Query");
                }
                $meta = $components[$alias];
                if (!$meta['metadata']->hasField($field)) {
                    throw new \UnexpectedValueException("There is no such field [{$field}] in the given Query component, aliased by [$alias]");
                }
                $pathExpression = new PathExpression(PathExpression::TYPE_STATE_FIELD, $alias, $field);
                $pathExpression->type = PathExpression::TYPE_STATE_FIELD;
            } else {
                if (!array_key_exists($field, $components)) {
                    throw new \UnexpectedValueException("There is no component field [{$field}] in the given Query");
                }
                $pathExpression = $components[$field]['resultVariable'];
            }
            $expression = new ConditionalPrimary();
            if (is_numeric($queriedValue)) {
                $expression->simpleConditionalExpression = new ComparisonExpression($pathExpression, '>=', new Literal(Literal::NUMERIC, $queriedValue));
            } else {
                continue;
            }
            $filterExpressions[] = $expression->simpleConditionalExpression;
            $expressions[] = $expression;
        }
        if (count($expressions) > 1) {
            $conditionalPrimary = new ConditionalExpression($expressions);
        } elseif (count($expressions) > 0) {
            $conditionalPrimary = reset($expressions);
        } else {
            return;
        }
        if ($AST->whereClause) {
            if ($AST->whereClause->conditionalExpression instanceof ConditionalTerm) {
                if (!$this->termContainsFilter($AST->whereClause->conditionalExpression, $filterExpressions)) {
                    array_unshift(
                        $AST->whereClause->conditionalExpression->conditionalFactors,
                        $this->createPrimaryFromNode($conditionalPrimary)
                    );
                }
            } elseif ($AST->whereClause->conditionalExpression instanceof ConditionalPrimary) {
                if (!$this->primaryContainsFilter($AST->whereClause->conditionalExpression, $filterExpressions)) {
                    $AST->whereClause->conditionalExpression = new ConditionalTerm(array(
                        $this->createPrimaryFromNode($conditionalPrimary),
                        $AST->whereClause->conditionalExpression,
                    ));
                }
            } elseif ($AST->whereClause->conditionalExpression instanceof ConditionalExpression) {
                if (!$this->expressionContainsFilter($AST->whereClause->conditionalExpression, $filterExpressions)) {
                    $previousPrimary = new ConditionalPrimary();
                    $previousPrimary->conditionalExpression = $AST->whereClause->conditionalExpression;
                    $AST->whereClause->conditionalExpression = new ConditionalTerm(array(
                        $this->createPrimaryFromNode($conditionalPrimary),
                        $previousPrimary,
                    ));
                }
            }
        } else {
            $AST->whereClause = new WhereClause(
                $conditionalPrimary
            );
        }
    }

    /**
     * @param  ConditionalExpression $node
     * @param  Node[]                $filterExpressions
     * @return bool
     */
    private function expressionContainsFilter(ConditionalExpression $node, $filterExpressions)
    {
        foreach ($node->conditionalTerms as $conditionalTerm) {
            if ($conditionalTerm instanceof ConditionalTerm && $this->termContainsFilter($conditionalTerm, $filterExpressions)) {
                return true;
            } elseif ($conditionalTerm instanceof ConditionalPrimary && $this->primaryContainsFilter($conditionalTerm, $filterExpressions)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  ConditionalTerm $node
     * @param  Node[]          $filterExpressions
     * @return bool|void
     */
    private function termContainsFilter(ConditionalTerm $node, $filterExpressions)
    {
        foreach ($node->conditionalFactors as $conditionalFactor) {
            if ($conditionalFactor instanceof ConditionalFactor) {
                if ($this->factorContainsFilter($conditionalFactor, $filterExpressions)) {
                    return true;
                }
            } elseif ($conditionalFactor instanceof ConditionalPrimary) {
                if ($this->primaryContainsFilter($conditionalFactor, $filterExpressions)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param  ConditionalFactor $node
     * @param  Node[]            $filterExpressions
     * @return bool
     */
    private function factorContainsFilter(ConditionalFactor $node, $filterExpressions)
    {
        if ($node->conditionalPrimary instanceof ConditionalPrimary && $node->not === false) {
            return $this->primaryContainsFilter($node->conditionalPrimary, $filterExpressions);
        }

        return false;
    }

    /**
     * @param  ConditionalPrimary $node
     * @param  Node[]             $filterExpressions
     * @return bool
     */
    private function primaryContainsFilter(ConditionalPrimary $node, $filterExpressions)
    {
        if ($node->isSimpleConditionalExpression() && ($node->simpleConditionalExpression instanceof LikeExpression || $node->simpleConditionalExpression instanceof ComparisonExpression)) {
            return $this->isExpressionInFilterExpressions($node->simpleConditionalExpression, $filterExpressions);
        }
        if ($node->isConditionalExpression()) {
            return $this->expressionContainsFilter($node->conditionalExpression, $filterExpressions);
        }

        return false;
    }

    /**
     * @param  Node   $node
     * @param  Node[] $filterExpressions
     * @return bool
     */
    private function isExpressionInFilterExpressions(Node $node, $filterExpressions)
    {
        foreach ($filterExpressions as $filterExpression) {
            if ((string) $filterExpression === (string) $node) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  Node               $node
     * @return ConditionalPrimary
     */
    private function createPrimaryFromNode($node)
    {
        if ($node instanceof ConditionalPrimary) {
            $conditionalPrimary = $node;
        } else {
            $conditionalPrimary = new ConditionalPrimary();
            $conditionalPrimary->conditionalExpression = $node;
        }

        return $conditionalPrimary;
    }
}