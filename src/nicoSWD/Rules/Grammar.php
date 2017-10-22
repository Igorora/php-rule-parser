<?php

/**
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        https://github.com/nicoSWD
 * @author      Nicolas Oelgart <nico@oelgart.com>
 */
namespace nicoSWD\Rules;

interface Grammar
{
    public function getDefinition(): array;

    public function getInternalFunctions(): array;

    public function getInternalMethods(): array;
}
