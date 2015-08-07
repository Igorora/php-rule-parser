<?php

/**
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        https://github.com/nicoSWD
 * @since       0.3.4
 * @author      Nicolas Oelgart <nico@oelgart.com>
 */
namespace nicoSWD\Rules\tests\methods;

/**
 * Class ReplaceTest
 */
class ReplaceTest extends \AbstractTestBase
{
    public function testValidNeedleReturnsCorrectPosition()
    {
        $this->assertTrue($this->evaluate('foo.replace("a", "A") === "bAr"', ['foo' => 'bar']));
        $this->assertTrue($this->evaluate('"bar".replace("r", "R") === "baR"'));
    }

    public function testOmittedParametersDoNotReplaceAnything()
    {
        $this->assertTrue($this->evaluate('"bar".replace() === "bar"'));
    }

    public function testOmittedSecondParameterReplacesWithUndefined()
    {
        $this->assertTrue($this->evaluate('"bar".replace("r") === "baundefined"'));
    }
}
