<?php

namespace Civix\CoreBundle\Doctrine\Query\Functions;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\AST\PathExpression;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

class GroupConcat extends FunctionNode
{
    /**
     * @var bool
     */
    public $isDistinct = false;
    /**
     * @var null|PathExpression[]
     */
    public $pathExp = null;
    /**
     * @var mixed
     */
    public $separator = null;
    /**
     * @var mixed
     */
    public $orderBy = null;

    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $lexer = $parser->getLexer();
        if ($lexer->isNextToken(Lexer::T_DISTINCT)) {
            $parser->match(Lexer::T_DISTINCT);
            $this->isDistinct = true;
        }
        // first Path Expression is mandatory
        $this->pathExp = [];
        if ($lexer->isNextToken(Lexer::T_IDENTIFIER)) {
            $this->pathExp[] = $parser->StringExpression();
        } else {
            $this->pathExp[] = $parser->SingleValuedPathExpression();
        }
        while ($lexer->isNextToken(Lexer::T_COMMA)) {
            $parser->match(Lexer::T_COMMA);
            $this->pathExp[] = $parser->StringPrimary();
        }
        if ($lexer->isNextToken(Lexer::T_ORDER)) {
            $this->orderBy = $parser->OrderByClause();
        }
        if ($lexer->isNextToken(Lexer::T_IDENTIFIER)) {
            if (strtolower($lexer->lookahead['value']) !== 'separator') {
                $parser->syntaxError('separator');
            }
            $parser->match(Lexer::T_IDENTIFIER);
            $this->separator = $parser->StringPrimary();
        }
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    public function getSql(SqlWalker $sqlWalker)
    {
        $result = 'GROUP_CONCAT('.($this->isDistinct ? 'DISTINCT ' : '');
        $fields = [];
        foreach ($this->pathExp as $pathExp) {
            $fields[] = $pathExp->dispatch($sqlWalker);
        }
        $result .= sprintf('%s', implode(', ', $fields));
        if ($this->orderBy) {
            $result .= ' '.$sqlWalker->walkOrderByClause($this->orderBy);
        }
        if ($this->separator) {
            $result .= ' SEPARATOR '.$sqlWalker->walkStringPrimary($this->separator);
        }
        $result .= ')';

        return $result;
    }
}