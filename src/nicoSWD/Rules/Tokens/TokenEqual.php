<?php

/**
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        https://github.com/nicoSWD
 * @since       0.3
 * @author      Nicolas Oelgart <nico@oelgart.com>
 */
namespace nicoSWD\Rules\Tokens;

use nicoSWD\Rules\Constants;

/**
 * Class TokenEqual
 * @package nicoSWD\Rules\Tokens
 */
final class TokenEqual extends BaseToken
{
    /**
     * @return int
     */
    public function getGroup()
    {
        return Constants::GROUP_OPERATOR;
    }
}
