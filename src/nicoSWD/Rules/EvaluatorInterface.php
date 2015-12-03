<?php

/**
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        https://github.com/nicoSWD
 * @since       0.3
 * @author      Nicolas Oelgart <nico@oelgart.com>
 */
namespace nicoSWD\Rules;

/**
 * Interface EvaluatorInterface
 * @package nicoSWD\Rules
 */
interface EvaluatorInterface
{
    public function evaluate(string $group) : bool;
}
