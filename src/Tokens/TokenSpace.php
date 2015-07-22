<?php
/**
 * Created by PhpStorm.
 * User: Nico
 * Date: 11/07/15
 * Time: 09:56
 */

namespace nicoSWD\Rules\Tokens;

use nicoSWD\Rules\Constants;

/**
 * Class TokenSpace
 * @package nicoSWD\Rules\Tokens
 */
final class TokenSpace extends BaseToken
{
    /**
     * @return int
     */
    public function getGroup()
    {
        return Constants::GROUP_SPACE;
    }
}
