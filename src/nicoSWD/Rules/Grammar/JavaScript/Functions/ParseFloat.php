<?php

declare(strict_types=1);

/**
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        https://github.com/nicoSWD
 * @author      Nicolas Oelgart <nico@oelgart.com>
 */
namespace nicoSWD\Rules\Grammar\JavaScript\Functions;

use nicoSWD\Rules\Grammar\CallableFunction;
use nicoSWD\Rules\Grammar\CallableUserFunction;
use nicoSWD\Rules\TokenStream\Token\BaseToken;
use nicoSWD\Rules\TokenStream\Token\TokenFloat;

final class ParseFloat extends CallableFunction implements CallableUserFunction
{
    /**
     * @param BaseToken $value
     * @param BaseToken $value ...
     * @return BaseToken
     */
    public function call($value = null): BaseToken
    {
        if ($value === null) {
            return new TokenFloat(NAN);
        }

        return new TokenFloat((float) $value->getValue());
    }
}
