<?php

/**
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        https://github.com/nicoSWD
 * @since       0.3.4
 * @author      Nicolas Oelgart <nico@oelgart.com>
 */
use nicoSWD\Rules;

require_once 'AbstractTestBase.php';

/**
 * Class MethodCallsTest
 */
class MethodCallsTest extends AbstractTestBase
{
    public function testFunctions()
    {
        $this->assertTrue($this->evaluate('1 === 2 || ("foo|bar|baz".split("|") === ["foo", "bar", "baz"] && 2 < 3)'));
        $this->assertTrue($this->evaluate('foo === 3 && "bar" in "foo|bar|baz".split("|") && 4 > foo', ['foo' => 3]));
        $this->assertTrue($this->evaluate('"foo".toUpperCase() === "FOO"'));
        $this->assertTrue($this->evaluate('"HeLLo World".charAt(3) === "l".toUpperCase()'));
        $this->assertTrue($this->evaluate('["foo", "bar", "baz"] === "foo|bar|baz".split("|")'));
        $this->assertTrue($this->evaluate('["foo", "bar", "baz"].join("|") === "foo|bar|baz"'));

        $this->assertTrue($this->evaluate(
            '// Something true
            1 === 1 &&
            /* And something true */
            "foo|bar|baz".split("|") === ["foo", "bar", "baz"] && (2 < 3) &&
            // bar is indeed in the array
            "bar" in "foo|bar".split("|") &&
            // More
            [1, 4, /* sneak */ 3].join("") === "143" &&
            // More
            "bar".toUpperCase() === "BAR"
            '
        ));
    }
}
