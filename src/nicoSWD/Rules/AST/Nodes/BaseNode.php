<?php

declare(strict_types = 1);

/**
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        https://github.com/nicoSWD
 * @author      Nicolas Oelgart <nico@oelgart.com>
 */
namespace nicoSWD\Rules\AST\Nodes;

use nicoSWD\Rules\AST;
use nicoSWD\Rules\AST\TokenCollection;
use nicoSWD\Rules\Parser;
use nicoSWD\Rules\TokenType;
use nicoSWD\Rules\Core\CallableFunction;
use nicoSWD\Rules\Exceptions\ParserException;
use nicoSWD\Rules\Tokens;
use nicoSWD\Rules\Tokens\BaseToken;
use nicoSWD\Rules\Tokens\TokenComment;
use nicoSWD\Rules\Tokens\TokenMethod;
use nicoSWD\Rules\Tokens\TokenNewline;
use nicoSWD\Rules\Tokens\TokenSpace;

abstract class BaseNode
{
    /** @var AST */
    protected $ast;

    /** @var string */
    protected $methodName = '';

    /** @var int */
    protected $methodOffset = 0;

    public function __construct(AST $ast)
    {
        $this->ast = $ast;
    }

    abstract public function getNode(): BaseToken;

    /**
     * Looks ahead, but does not move the pointer.
     */
    protected function hasMethodCall(): bool
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

    public function getMethod(BaseToken $token): CallableFunction
    {
        $methodName = $this->getMethodName();
        $methodClass = '\nicoSWD\Rules\Core\Methods\\' . ucfirst($methodName);

        if (!class_exists($methodClass)) {
            $current = $this->getCurrentNode();

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

    private function getMethodName(): string
    {
        do {
            $this->ast->next();
        } while ($this->getCurrentNode()->getOffset() < $this->methodOffset);

        return trim(ltrim(rtrim($this->methodName, "\r\n("), '.'));
    }

    public function getArrayItems(): TokenCollection
    {
        return $this->getCommaSeparatedValues(TokenType::SQUARE_BRACKETS);
    }

    public function getArguments(): TokenCollection
    {
        return $this->getCommaSeparatedValues(TokenType::PARENTHESES);
    }

    public function getCurrentNode()
    {
        return $this->ast->getStack()->current();
    }

    public function getParser(): Parser
    {
        return $this->ast->parser;
    }

    private function getCommaSeparatedValues(int $stopAt): TokenCollection
    {
        $commaExpected = false;
        $items = new TokenCollection();

        do {
            $this->ast->next();

            if (!$current = $this->ast->current()) {
                throw new ParserException('Unexpected end of string');
            }

            if ($current->getType() === TokenType::VALUE) {
                if ($commaExpected) {
                    throw ParserException::unexpectedToken($current);
                }

                $commaExpected = true;
                $items->attach($current);
            } elseif ($current instanceof Tokens\TokenComma) {
                if (!$commaExpected) {
                    throw ParserException::unexpectedToken($current);
                }

                $commaExpected = false;
            } elseif ($current->getType() === $stopAt) {
                break;
            } elseif (!$this->isIgnoredToken($current)) {
                throw ParserException::unexpectedToken($current);
            }
        } while ($this->ast->valid());

        if (!$commaExpected && $items->count() > 0) {
            throw new ParserException(sprintf(
                'Unexpected "," at position %d on line %d',
                $current->getPosition(),
                $current->getLine()
            ));
        }

        $items->rewind();

        return $items;
    }

    private function isIgnoredToken(BaseToken $token): bool
    {
        return (
            $token instanceof TokenSpace ||
            $token instanceof TokenNewline ||
            $token instanceof TokenComment
        );
    }
}
