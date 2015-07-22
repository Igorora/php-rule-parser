<?php

/**
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        https://github.com/nicoSWD
 * @since       0.3
 * @author      Nicolas Oelgart <nico@oelgart.com>
 */
namespace nicoSWD\Rules\Expressions;

/**
 * Class BaseExpression
 * @package nicoSWD\Rules\Expressions
 */
abstract class BaseExpression
{
    /**
     * @param string $leftValue
     * @param string $rightValue
     * @return bool
     */
    abstract public function evaluate($leftValue, $rightValue);
}
