<?php

declare(strict_types=1);

/**
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        https://github.com/nicoSWD
 * @author      Nicolas Oelgart <nico@oelgart.com>
 */
namespace nicoSWD\Rule\TokenStream\Node;

use nicoSWD\Rule\TokenStream\Token\BaseToken;

final class NodeString extends BaseNode
{
    public function getNode(): BaseToken
    {
        $current = $this->getCurrentNode();

        while ($this->hasMethodCall()) {
            $current = $this->getMethod($current)->call(...$this->getArguments());
        }

        return $current;
    }
}
