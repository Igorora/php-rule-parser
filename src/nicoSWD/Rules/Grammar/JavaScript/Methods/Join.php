<?php

declare(strict_types=1);

/**
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        https://github.com/nicoSWD
 * @author      Nicolas Oelgart <nico@oelgart.com>
 */
namespace nicoSWD\Rules\Grammar\JavaScript\Methods;

use nicoSWD\Rules\TokenStream\Token\BaseToken;
use nicoSWD\Rules\TokenStream\Token\TokenArray;
use nicoSWD\Rules\TokenStream\Token\TokenString;
use nicoSWD\Rules\TokenStream\TokenCollection;
use nicoSWD\Rules\Parser\Exception\ParserException;
use nicoSWD\Rules\Grammar\CallableFunction;

final class Join extends CallableFunction
{
    /**
     * @param BaseToken $glue
     * @return BaseToken
     * @throws ParserException
     */
    public function call($glue = null): BaseToken
    {
        if (!$this->token instanceof TokenArray) {
            throw new ParserException(sprintf(
                '%s.join is not a function at position %d on line %d',
                $this->token->getValue(),
                $this->token->getPosition(),
                $this->token->getLine()
            ));
        }

        if ($glue) {
            $glue = $glue->getValue();
        } else {
            $glue = ',';
        }

        $array = $this->token->getValue();

        if ($array instanceof TokenCollection) {
            $array = $array->toArray();
        }

        return new TokenString(
            implode($glue, $array),
            $this->token->getOffset(),
            $this->token->getStack()
        );
    }
}
