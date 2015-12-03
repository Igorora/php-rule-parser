<?php

declare(strict_types=1);

/**
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        https://github.com/nicoSWD
 * @author      Nicolas Oelgart <nico@oelgart.com>
 */
namespace nicoSWD\Rules\Tokens;

use nicoSWD\Rules\Constants;

final class TokenInteger extends BaseToken
{
    public function getGroup() : int
    {
        return Constants::GROUP_VALUE;
    }

    /**
     * @return int
     */
    public function getValue()
    {
        return (int) $this->value;
    }
}
