<?php

/**
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        https://github.com/nicoSWD
 * @since       0.3.4
 * @author      Nicolas Oelgart <nico@oelgart.com>
 */
namespace nicoSWD\Rules\tests\methods;

/**
 * Class SyntaxErrorTest
 */
class SyntaxErrorTest extends \AbstractTestBase
{
    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Unexpected value at position 15 on line 1
     */
    public function testMissingCommaInArgumentsThrowsException()
    {
        $this->evaluate('"foo".charAt(1 2 ) === "b"');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Unexpected token "," at position 17 on line 1
     */
    public function testMissingValueInArgumentsThrowsException()
    {
        $this->evaluate('"foo".charAt(1 , ) === "b"');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Unexpected token "," at position 17 on line 1
     */
    public function testMissingValueBetweenCommasInArgumentsThrowsException()
    {
        $this->evaluate('"foo".charAt(1 , , ) === "b"');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Unexpected token "<" at position 17 on line 1
     */
    public function testUnexpectedTokenInArgumentsThrowsException()
    {
        $this->evaluate('"foo".charAt(1 , < , ) === "b"');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Unexpected end of string. Expected ")"
     */
    public function testUnexpectedEndOfStringThrowsException()
    {
        $this->evaluate('"foo".charAt(1 , ');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage undefined is not a function at position 7 on line 1
     */
    public function testUndefinedMethodThrowsException()
    {
        $this->evaluate('/^foo$/.teddst("foo") === true');
    }
}
