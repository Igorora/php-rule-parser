<?php

/**
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        https://github.com/nicoSWD
 * @since       0.3
 * @author      Nicolas Oelgart <nico@oelgart.com>
 */
namespace nicoSWD\Rules;

/**
 * Class Parser
 * @package nicoSWD\Rules
 */
class Parser
{
    /**
     * @var array
     */
    protected $variables = [];

    /**
     * @var null|mixed[]
     */
    protected $values = \null;

    /**
     * @var null|string
     */
    protected $operator =  \null;

    /**
     * @var string
     */
    protected $output = '';

    /**
     * @var bool
     */
    protected $operatorRequired = \false;

    /**
     * @var bool
     */
    protected $incompleteCondition = \false;

    /**
     * @var int
     */
    protected $openParenthesis = 0;

    /**
     * @var int
     */
    protected $closedParenthesis = 0;

    /**
     * @var TokenizerInterface
     */
    protected $tokenizer;

    /**
     * @var Expressions\Factory
     */
    protected $expressionFactory;

    /**
     * @param TokenizerInterface  $tokenizer
     * @param Expressions\Factory $expressionFactory
     */
    public function __construct(TokenizerInterface $tokenizer, Expressions\Factory $expressionFactory)
    {
        $this->tokenizer = $tokenizer;
        $this->expressionFactory = $expressionFactory;
    }

    /**
     * @param string $rule
     * @return string
     * @throws Exceptions\ParserException
     */
    public function parse($rule)
    {
        $this->output = '';
        $this->operator = \null;
        $this->values = \null;
        $this->operatorRequired = \false;

        foreach (new AST($this->tokenizer->tokenize($rule), $this->variables) as $token) {
            switch ($token->getGroup()) {
                case Constants::GROUP_VALUE:
                    $this->assignVariableValueFromToken($token);
                    break;
                case Constants::GROUP_LOGICAL:
                    $this->assignLogicalToken($token);
                    continue 2;
                case Constants::GROUP_PARENTHESES:
                    $this->assignParentheses($token);
                    continue 2;
                case Constants::GROUP_OPERATOR:
                    $this->assignOperator($token);
                    continue 2;
                case Constants::GROUP_COMMENT:
                case Constants::GROUP_SPACE:
                    continue 2;
                default:
                    throw new Exceptions\ParserException(sprintf(
                        'Unknown token "%s" at position %d on line %d',
                        $token->getValue(),
                        $token->getPosition(),
                        $token->getLine()
                    ));
            }

            $this->parseExpression();
        }

        $this->assertSyntaxSeemsOkay();
        return $this->output;
    }

    /**
     * @param array $variables
     */
    public function assignVariables(array $variables)
    {
        $this->variables = $variables;
    }

    /**
     * @param Tokens\BaseToken $token
     * @throws Exceptions\ParserException
     */
    protected function assignVariableValueFromToken(Tokens\BaseToken $token)
    {
        if ($this->operatorRequired) {
            throw new Exceptions\ParserException(sprintf(
                'Missing operator at position %d on line %d',
                $token->getPosition(),
                $token->getLine()
            ));
        }

        $this->operatorRequired = !$this->operatorRequired;
        $this->incompleteCondition = \false;

        if (!isset($this->values)) {
            $this->values = [$token->getValue()];
        } else {
            $this->values[] = $token->getValue();
        }
    }

    /**
     * @param Tokens\BaseToken $token
     * @throws Exceptions\ParserException
     */
    protected function assignParentheses(Tokens\BaseToken $token)
    {
        $tokenValue = $token->getValue();

        if ($tokenValue === '(') {
            if ($this->operatorRequired) {
                throw new Exceptions\ParserException(sprintf(
                    'Unexpected token "(" at position %d on line %d',
                    $token->getPosition(),
                    $token->getLine()
                ));
            }

            $this->openParenthesis++;
        } else {
            if ($this->openParenthesis < 1) {
                throw new Exceptions\ParserException(sprintf(
                    'Missing opening parenthesis at position %d on line %d',
                    $token->getPosition(),
                    $token->getLine()
                ));
            }

            $this->closedParenthesis++;
        }

        $this->output .= $tokenValue;
    }

    /**
     * @param Tokens\BaseToken $token
     * @throws Exceptions\ParserException
     */
    protected function assignLogicalToken(Tokens\BaseToken $token)
    {
        if (!$this->operatorRequired) {
            throw new Exceptions\ParserException(sprintf(
                'Unexpected "%s" at position %d on line %d',
                $token->getOriginalValue(),
                $token->getPosition(),
                $token->getLine()
            ));
        }

        $this->output .= $token->getValue();
        $this->incompleteCondition = \true;
        $this->operatorRequired = \false;
    }

    /**
     * @param Tokens\BaseToken $token
     * @throws Exceptions\ParserException
     */
    protected function assignOperator(Tokens\BaseToken $token)
    {
        if (isset($this->operator)) {
            throw new Exceptions\ParserException(sprintf(
                'Unexpected "%s" at position %d on line %d',
                $token->getOriginalValue(),
                $token->getPosition(),
                $token->getLine()
            ));
        } elseif (!isset($this->values)) {
            throw new Exceptions\ParserException(sprintf(
                'Incomplete expression for token "%s" at position %d on line %d',
                $token->getOriginalValue(),
                $token->getPosition(),
                $token->getLine()
            ));
        }

        $this->operator = $token->getValue();
        $this->operatorRequired = \false;
    }

    /**
     * @throws Exceptions\ExpressionFactoryException
     */
    protected function parseExpression()
    {
        if (!isset($this->operator) || count($this->values) <> 2) {
            return;
        }

        $this->operatorRequired = \true;
        $expression = $this->expressionFactory->createFromOperator($this->operator);
        $this->output .= (int) $expression->evaluate($this->values[0], $this->values[1]);

        unset($this->operator, $this->values);
    }

    /**
     * @throws Exceptions\ParserException
     */
    protected function assertSyntaxSeemsOkay()
    {
        if ($this->incompleteCondition) {
            throw new Exceptions\ParserException(
                'Incomplete and/or condition'
            );
        } elseif ($this->openParenthesis > $this->closedParenthesis) {
            throw new Exceptions\ParserException(
                'Missing closing parenthesis'
            );
        } elseif (isset($this->operator) || (isset($this->values) && count($this->values) > 0)) {
            throw new Exceptions\ParserException(
                'Incomplete expression'
            );
        }
    }
}
