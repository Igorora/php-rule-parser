<?php

/**
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        https://github.com/nicoSWD
 * @since       0.3.4
 * @author      Nicolas Oelgart <nico@oelgart.com>
 */
namespace nicoSWD\Rules\tests\operators;

/**
 * Class OperatorsTest
 */
class OperatorsTest extends \AbstractTestBase
{
    public function testAllAvailableOperators()
    {
        $this->assertTrue($this->evaluate('3 == 3'), 'Equal operator failed on two integers');
        $this->assertTrue($this->evaluate('4 === 4'));
        $this->assertTrue($this->evaluate('"4" == 4'));
        $this->assertTrue($this->evaluate('2 > 1'));
        $this->assertTrue($this->evaluate('1 < 2'));
        $this->assertTrue($this->evaluate('1 <> 2'));
        $this->assertTrue($this->evaluate('1 != 2'));
        $this->assertTrue($this->evaluate('1 <= 2'));
        $this->assertTrue($this->evaluate('2 <= 2'));
        $this->assertTrue($this->evaluate('3 >= 2'));
        $this->assertTrue($this->evaluate('2 >= 2'));

        $this->assertFalse($this->evaluate('2 !== 2'));
    }

    public function testStrictOperators()
    {
        $this->assertFalse($this->evaluate('"4" === 4'));
        $this->assertTrue($this->evaluate('4 === 4'));

        $this->assertTrue($this->evaluate('4 !== "4"'));
        $this->assertFalse($this->evaluate('4 !== 4'));
    }

    public function testInOperator()
    {
        $this->assertTrue($this->evaluate('123 in foo', ['foo' => [123, 12]]));
        $this->assertFalse($this->evaluate('"123" in foo', ['foo' => [123, 12]]));
        $this->assertFalse($this->evaluate('"123" in [123, 12]'));
        $this->assertTrue($this->evaluate('123 in [123, 12]'));
    }

    public function testInOperatorOnReturnedValueByMethodCall()
    {
        $this->assertTrue($this->evaluate('"123" in "321,123".split(",")'));
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Expected array, got "string"
     */
    public function testInOperatorWithNonArrayRightValueThrowsException()
    {
        $this->assertTrue($this->evaluate('"123" in "foo"'));
    }

    public function testCommentsAreIgnoredCorrectly()
    {
        $this->assertFalse($this->evaluate('1 == 2 // || 1 == 1'));
        $this->assertTrue($this->evaluate('1 == 1 // && 2 == 1'));
        $this->assertFalse($this->evaluate('1 == 1 /* || 2 == 1 */ && 2 != 2'));
        $this->assertTrue($this->evaluate('1 == 3 /* || 2 == 1 */ || 2 == 2'));
        $this->assertTrue($this->evaluate(
            '1 /* test */ == 1 /* test */ && /* test */ 2 /* test */ == /* test */ 2'
        ));
    }

    public function testEqualOperator()
    {
        $this->assertTrue($this->evaluate('foo == -1', ['foo' => -1]));
        $this->assertFalse($this->evaluate('foo == 3', ['foo' => -1]));
        $this->assertTrue($this->evaluate('foo != 3 && 3 != foo', ['foo' => -1]));
        $this->assertFalse($this->evaluate('foo != 3 && 3 != foo', ['foo' => 3]));
        $this->assertTrue($this->evaluate('foo != 3 && 3 != foo', ['foo' => -3]));
    }
}
