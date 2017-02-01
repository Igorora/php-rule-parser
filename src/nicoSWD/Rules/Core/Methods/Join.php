<?php

/**
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        https://github.com/nicoSWD
 * @author      Nicolas Oelgart <nico@oelgart.com>
 */
declare(strict_types=1);

namespace nicoSWD\Rules\Core\Methods;

use nicoSWD\Rules\AST\TokenCollection;
use nicoSWD\Rules\Core\CallableFunction;
use nicoSWD\Rules\Exceptions\ParserException;
use nicoSWD\Rules\Tokens\{TokenArray, TokenString};
use nicoSWD\Rules\Tokens;
use nicoSWD\Rules\Tokens\BaseToken;

final class Join extends CallableFunction
{
    /**
     * @param BaseToken $glue
     * @return TokenString
     * @throws ParserException
     */
    public function call($glue = null) : TokenString
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

    public function getName() : string
    {
        return 'join';
    }
}
