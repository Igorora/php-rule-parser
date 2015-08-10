<?php

/**
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        https://github.com/nicoSWD
 * @since       0.3
 * @author      Nicolas Oelgart <nico@oelgart.com>
 */
namespace nicoSWD\Rules\Tokens;

use nicoSWD\Rules\Stack;

/**
 * Class BaseToken
 * @package nicoSWD\Rules\Tokens
 */
abstract class BaseToken
{
    /**
     * @var string
     */
    protected $value = '';

    /**
     * @var int
     */
    protected $offset = 0;

    /**
     * @var Stack
     */
    protected $stack;

    /**
     * @param string $value
     * @param int    $offset
     * @param Stack  $stack
     */
    public function __construct($value, $offset = 0, Stack $stack = \null)
    {
        $this->value = $value;
        $this->offset = $offset;
        $this->stack = $stack;
    }

    /**
     * @return int
     */
    abstract public function getGroup();

    /**
     * @return mixed
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Some tokens can be represented by different operators,
     * so the original value is used for error reporting,
     * while the other one is used internally.
     *
     * @return string
     */
    final public function getOriginalValue()
    {
        return $this->value;
    }

    /**
     * Returns offset in the whole rule string.
     *
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * @param int $offset
     */
    public function setOffset($offset = 0)
    {
        $this->offset = $offset;
    }

    /**
     * @return Stack
     */
    public function getStack()
    {
        return $this->stack;
    }

    /**
     * @param Stack $stack
     */
    public function setStack(Stack $stack)
    {
        $this->stack = $stack;
    }

    /**
     * @return bool
     */
    public function supportsMethodCalls()
    {
        return \false;
    }

    /**
     * Returns position in the line the token is placed in.
     *
     * @return int
     */
    public function getPosition()
    {
        $offset = 0;

        foreach ($this->stack as $token) {
            if ($token === $this) {
                break;
            } elseif ($token instanceof TokenNewline) {
                $offset = 0;
                continue;
            }

            $offset += strlen($token->getOriginalValue());
        }

        return $offset;
    }

    /**
     * @return int
     */
    public function getLine()
    {
        $line = 1;

        foreach ($this->stack as $token) {
            if ($token === $this) {
                break;
            } elseif ($token instanceof TokenNewline) {
                $line += 1;
            } elseif ($token instanceof TokenComment) {
                $line += substr_count($token->getValue(), "\n");
            }
        }

        return $line;
    }
}
