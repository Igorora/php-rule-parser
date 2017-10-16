<?php

/**
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        https://github.com/nicoSWD
 * @author      Nicolas Oelgart <nico@oelgart.com>
 */
declare(strict_types=1);

namespace nicoSWD\Rules;

use Closure;
use InvalidArgumentException;
use nicoSWD\Rules\Core\CallableUserFunction;
use nicoSWD\Rules\Tokens\BaseToken;

class Parser
{
    /** @var array */
    public $variables = [];

    /** @var null|mixed[] */
    protected $values = null;

    /** @var null|BaseToken */
    protected $operator =  null;

    /** @var string */
    protected $output = '';

    /** @var bool */
    protected $operatorRequired = false;

    /** @var bool */
    protected $incompleteCondition = false;

    /** @var int */
    protected $openParenthesis = 0;

    /** @var int */
    protected $closedParenthesis = 0;

    /** @var TokenizerInterface */
    protected $tokenizer;

    /** @var Expressions\Factory */
    protected $expressionFactory;

    protected $userDefinedFunctions = [];

    public function __construct(TokenizerInterface $tokenizer, Expressions\Factory $expressionFactory)
    {
        $this->tokenizer = $tokenizer;
        $this->expressionFactory = $expressionFactory;
    }

    /** @throws Exceptions\ParserException */
    public function parse(string $rule): string
    {
        $this->output = '';
        $this->operator = null;
        $this->values = null;
        $this->operatorRequired = false;

        foreach (new AST($this->tokenizer->tokenize($rule), $this) as $token) {
            switch ($token->getType()) {
                case TokenType::VALUE:
                    $this->assignVariableValueFromToken($token);
                    break;
                case TokenType::LOGICAL:
                    $this->assignLogicalToken($token);
                    continue 2;
                case TokenType::PARENTHESES:
                    $this->assignParentheses($token);
                    continue 2;
                case TokenType::OPERATOR:
                    $this->assignOperator($token);
                    continue 2;
                case TokenType::COMMENT:
                case TokenType::SPACE:
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

    public function assignVariables(array $variables)
    {
        $this->variables = $variables;
    }

    /** @throws Exceptions\ParserException */
    protected function assignVariableValueFromToken(BaseToken $token)
    {
        if ($this->operatorRequired) {
            throw new Exceptions\ParserException(sprintf(
                'Missing operator at position %d on line %d',
                $token->getPosition(),
                $token->getLine()
            ));
        }

        $this->operatorRequired = !$this->operatorRequired;
        $this->incompleteCondition = false;

        if (!isset($this->values)) {
            $this->values = [$token->getValue()];
        } else {
            $this->values[] = $token->getValue();
        }
    }

    /** @throws Exceptions\ParserException */
    protected function assignParentheses(BaseToken $token)
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

    /** @throws Exceptions\ParserException */
    protected function assignLogicalToken(BaseToken $token)
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
        $this->incompleteCondition = true;
        $this->operatorRequired = false;
    }

    /** @throws Exceptions\ParserException */
    protected function assignOperator(BaseToken $token)
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

        $this->operator = $token;
        $this->operatorRequired = false;
    }

    /** @throws Exceptions\ExpressionFactoryException */
    protected function parseExpression()
    {
        if (!isset($this->operator) || count($this->values) <> 2) {
            return;
        }

        $this->operatorRequired = true;
        $expression = $this->expressionFactory->createFromOperator($this->operator);
        $this->output .= (int) $expression->evaluate($this->values[0], $this->values[1]);

        unset($this->operator, $this->values);
    }

    /** @throws Exceptions\ParserException */
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

    public function registerFunctionClass(string $className)
    {
        /** @var CallableUserFunction $function */
        $function = new $className();

        if (!$function instanceof CallableUserFunction) {
            throw new InvalidArgumentException(
                sprintf(
                    "%s must be an instance of %s",
                    $className,
                    CallableUserFunction::class
                )
            );
        }

        $this->registerFunction($function->getName(), function () use ($function): BaseToken {
            return $function->call(...func_get_args());
        });
    }

    public function registerFunction(string $name, Closure $callback)
    {
        $this->userDefinedFunctions[$name] = $callback;
    }

    public function registerToken(string $token, string $regex, int $priority = 10)
    {
        $this->tokenizer->registerToken($token, $regex, $priority);
    }

    /**
     * @param string $name
     * @return Closure|null
     */
    public function getFunction(string $name)
    {
        return isset($this->userDefinedFunctions[$name])
            ? $this->userDefinedFunctions[$name]
            : null;
    }
}
