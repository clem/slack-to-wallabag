<?php

namespace App\Services\Doctrine;

use Doctrine\ORM\Query\AST\Functions\FunctionNode;
use Doctrine\ORM\Query\Lexer;
use Doctrine\ORM\Query\Parser;
use Doctrine\ORM\Query\SqlWalker;

/**
 * DateFunction ::= "Date" "(" ArithmeticPrimary ")"
 */
class DqlDate extends FunctionNode
{
    /**
     * @var FunctionNode
     */
    public $dateExpression;

    /**
     * @inheritdoc
     */
    public function parse(Parser $parser)
    {
        $parser->match(Lexer::T_IDENTIFIER);
        $parser->match(Lexer::T_OPEN_PARENTHESIS);
        $this->dateExpression = $parser->ArithmeticPrimary();
        $parser->match(Lexer::T_CLOSE_PARENTHESIS);
    }

    /**
     * @inheritdoc
     */
    public function getSql(SqlWalker $sqlWalker)
    {
        return 'DATE('.$this->dateExpression->dispatch($sqlWalker).')';
    }
}
