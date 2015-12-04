<?php

declare(strict_types=1);

/**
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        https://github.com/nicoSWD
 * @author      Nicolas Oelgart <nico@oelgart.com>
 */
namespace nicoSWD\Rules\AST\Nodes;

use nicoSWD\Rules\AST;
use nicoSWD\Rules\AST\TokenCollection;
use nicoSWD\Rules\Core\CallableFunction;
use nicoSWD\Rules\Tokens;
use nicoSWD\Rules\Constants;
use nicoSWD\Rules\Exceptions\ParserException;
use nicoSWD\Rules\Tokens\{
    BaseToken,
    TokenComment,
    TokenMethod,
    TokenNewline,
    TokenSpace
};

abstract class BaseNode
{
    /**
     * @var AST
     */
    protected $ast;

    /**
     * @var string
     */
    protected $methodName = '';

    /**
     * @var int
     */
    protected $methodOffset = 0;

    /**
     * @param AST $ast
     */
    public function __construct(AST $ast)
    {
        $this->ast = $ast;
    }

    abstract public function getNode() : BaseToken;

    /**
     * Looks ahead, but does not move the pointer.
     */
    protected function hasMethodCall() : bool
    {
        $stackClone = $this->ast->getStack()->getClone();

        while ($stackClone->valid()) {
            $stackClone->next();

            if (!$token = $stackClone->current()) {
                break;
            } elseif ($this->isIgnoredToken($token)) {
                continue;
            } elseif ($token instanceof TokenMethod) {
                $this->methodName = $token->getValue();
                $this->methodOffset = $token->getOffset();

                return true;
            } else {
                break;
            }
        }

        return false;
    }

    /**
     * @throws ParserException
     */
    public function getMethod(BaseToken $token) : CallableFunction
    {
        $methodName = $this->getMethodName();
        $methodClass = '\nicoSWD\Rules\Core\Methods\\' . ucfirst($methodName);

        if (!class_exists($methodClass)) {
            $current = $this->ast->getStack()->current();

            throw new ParserException(sprintf(
                'undefined is not a function at position %d on line %d',
                $current->getPosition(),
                $current->getLine()
            ));
        }

        /** @var CallableFunction $instance */
        $instance = new $methodClass($token);

        if ($instance->getName() !== $methodName) {
            throw new ParserException(
                'undefined is not a function'
            );
        }

        return $instance;
    }

    protected function getMethodName() : string
    {
        do {
            $this->ast->next();
        } while ($this->ast->getStack()->current()->getOffset() < $this->methodOffset);

        return trim(ltrim(rtrim($this->methodName, "\r\n("), '.'));
    }

    /**
     * @throws ParserException
     */
    protected function getCommaSeparatedValues(string $stopAt = ')') : TokenCollection
    {
        $commaExpected = false;
        $items = new TokenCollection();

        do {
            $this->ast->next();

            if (!$current = $this->ast->current()) {
                throw new ParserException(sprintf(
                    'Unexpected end of string. Expected "%s"',
                    $stopAt
                ));
            }

            if ($current->getGroup() === Constants::GROUP_VALUE) {
                if ($commaExpected) {
                    throw new ParserException(sprintf(
                        'Unexpected value at position %d on line %d',
                        $current->getPosition(),
                        $current->getLine()
                    ));
                }

                $commaExpected = true;
                $items->attach($current);
            } elseif ($current instanceof Tokens\TokenComma) {
                if (!$commaExpected) {
                    throw new ParserException(sprintf(
                        'Unexpected token "," at position %d on line %d',
                        $current->getPosition(),
                        $current->getLine()
                    ));
                }

                $commaExpected = false;
            } elseif ($current->getValue() === $stopAt) {
                break;
            } elseif (!$this->isIgnoredToken($current)) {
                throw new ParserException(sprintf(
                    'Unexpected token "%s" at position %d on line %d',
                    $current->getOriginalValue(),
                    $current->getPosition(),
                    $current->getLine()
                ));
            }
        } while ($this->ast->valid());

        if (!$commaExpected && $items->count() > 0) {
            throw new ParserException(sprintf(
                'Unexpected token "," at position %d on line %d',
                $current->getPosition(),
                $current->getLine()
            ));
        }

        $items->rewind();
        return $items;
    }

    protected function isIgnoredToken(BaseToken $token) : bool
    {
        return (
            $token instanceof TokenSpace ||
            $token instanceof TokenNewline ||
            $token instanceof TokenComment
        );
    }
}
