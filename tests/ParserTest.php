<?php

/**
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        https://github.com/nicoSWD
 * @since       0.3
 * @author      Nicolas Oelgart <nico@oelgart.com>
 */
use nicoSWD\Rules\Evaluator;
use nicoSWD\Rules\Parser;
use nicoSWD\Rules\Tokenizer;

/**
 * Class RuleEvaluatesTrueTest
 */
class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var Evaluator
     */
    private $evaluator;

    /**
     *
     */
    public function setup()
    {
        $this->parser = new Parser(new Tokenizer());
        $this->evaluator = new Evaluator();
    }

    public function testMultipleAnds()
    {
        $rule = 'COUNTRY=="MA" and CURRENCY=="EGP" && TOTALAMOUNT>50000';

        $this->assertTrue($this->ruleEvaluatesTrue($rule, [
            'COUNTRY'     => 'MA',
            'CURRENCY'    => 'EGP',
            'TOTALAMOUNT' => '50001'
        ]));

        $rule = 'COUNTRY = "EG" and CURRENCY=="EGP" && TOTALAMOUNT>50000';

        $this->assertFalse($this->ruleEvaluatesTrue($rule, [
            'COUNTRY'     => 'MA',
            'CURRENCY'    => 'EGP',
            'TOTALAMOUNT' => '50001'
        ]));

        $rule = '((COUNTRY=="EG") and (CURRENCY=="EGP") && (TOTALAMOUNT>50000))';

        $this->assertFalse($this->ruleEvaluatesTrue($rule, [
            'COUNTRY'     => 'MA',
            'CURRENCY'    => 'EGP',
            'TOTALAMOUNT' => '50001'
        ]));
    }

    private function ruleEvaluatesTrue($rule, array $variables = [])
    {
        $this->parser->assignVariables($variables);
        $result = $this->parser->parse($rule);

        return $this->evaluator->evaluate($result);
    }

    public function testMixedOrsAndAnds()
    {
        $rule = '
            COUNTRY=="MA" and
            CURRENCY=="EGP" && (
            TOTALAMOUNT>50000 ||
            TOTALAMOUNT == 0)';

        $this->assertTrue($this->ruleEvaluatesTrue($rule, [
            'COUNTRY'     => 'MA',
            'CURRENCY'    => 'EGP',
            'TOTALAMOUNT' => '50001'
        ]));
    }

    /**
     *
     */
    public function testEmptyOrIncompleteRuleReturnsFalse()
    {
        $rule = '';

        $this->assertFalse($this->ruleEvaluatesTrue($rule, [
            'COUNTRY' => 'MA'
        ]));
    }

    /**
     *
     */
    public function testNullAsVariableDoesNotFail()
    {
        $rule = 'COUNTRY == "EMD" && (PAYMENTCONDITION == "L000" || PAYMENTCONDITION=="L002"
            || PAYMENTCONDITION=="LM18" || PAYMENTCONDITION=="LM19" || PAYMENTCONDITION=="LM20")
            && (OFFERTYPE=="ZNOR" || OFFERTYPE=="ZNOD" || OFFERTYPE=="ZNOP")';

        $this->assertFalse($this->ruleEvaluatesTrue($rule, [
            'PAYMENTCONDITION' => 'LM18',
            'COUNTRY'          => 'EMD',
            'OFFERTYPE'        => null
        ]));
    }

    public function testFreakingLongRule()
    {
        $rule = '
            COUNTRY=="SA" && (CUSTOMERCODE=="0002950182" ||
            CUSTOMERCODE=="100130" || CUSTOMERCODE=="100143" ||
            CUSTOMERCODE=="100149" || CUSTOMERCODE=="0002951129" ||
            CUSTOMERCODE=="0002950746" || CUSTOMERCODE=="0002950747" ||
            CUSTOMERCODE=="0002950748" || CUSTOMERCODE=="0002950749" ||
            CUSTOMERCODE=="100392" || CUSTOMERCODE=="0002950751" ||
            CUSTOMERCODE=="0002950897" || CUSTOMERCODE=="100208" ||
            CUSTOMERCODE=="0002951140" || CUSTOMERCODE=="100209") &&
            ISDISCOUNT==1';

        $this->assertTrue($this->ruleEvaluatesTrue($rule, [
            'COUNTRY'      => 'SA',
            'CUSTOMERCODE' => '0002950751',
            'ISDISCOUNT'   => '1'
        ]));

        $this->assertFalse($this->ruleEvaluatesTrue($rule, [
            'COUNTRY'      => 'SA',
            'CUSTOMERCODE' => '0002950751',
            'ISDISCOUNT'   => '0'
        ]));
    }

    public function testNegativeComparison()
    {
        $rule = '
            COUNTRY !== "EG" &&
            CUSTOMERCODE!="55350000" &&
            CUSTOMERCODE!="55358500" &&
            CUSTOMERCODE!="55303100" &&
            CURRENCY=="MAD" &&
            TOTALAMOUNT>500000 &&
            TOTALAMOUNT<=1000000';

        $this->assertTrue($this->ruleEvaluatesTrue($rule, [
            'COUNTRY'      => 'MA',
            'CURRENCY'     => 'MAD',
            'CUSTOMERCODE' => '0002950751',
            'TOTALAMOUNT'  => '999999'
        ]));
    }

    public function testAllAvailableOperators()
    {
        $this->assertTrue($this->ruleEvaluatesTrue('1 = 1'));
        $this->assertTrue($this->ruleEvaluatesTrue('1 is 1'));
        $this->assertTrue($this->ruleEvaluatesTrue('3 == 3'));
        $this->assertTrue($this->ruleEvaluatesTrue('4 == 4'));
        $this->assertTrue($this->ruleEvaluatesTrue('"4" == 4'));
        $this->assertTrue($this->ruleEvaluatesTrue('2 > 1'));
        $this->assertTrue($this->ruleEvaluatesTrue('1 < 2'));
        $this->assertTrue($this->ruleEvaluatesTrue('1 <> 2'));
        $this->assertTrue($this->ruleEvaluatesTrue('1 != 2'));
        $this->assertTrue($this->ruleEvaluatesTrue('1 is not 2'));
        $this->assertTrue($this->ruleEvaluatesTrue('1 <= 2'));
        $this->assertTrue($this->ruleEvaluatesTrue('2 <= 2'));
        $this->assertTrue($this->ruleEvaluatesTrue('3 >= 2'));
        $this->assertTrue($this->ruleEvaluatesTrue('2 >= 2'));

        $this->assertFalse($this->ruleEvaluatesTrue('2 !== 2'));
        $this->assertFalse($this->ruleEvaluatesTrue('2 is not 2'));
    }

    public function testCommentsAreIgnoredCorrectly()
    {
        $this->assertFalse($this->ruleEvaluatesTrue('1 = 2 // or 1 = 1'));
        $this->assertTrue($this->ruleEvaluatesTrue('1 = 1 # and 2 = 1'));
        $this->assertFalse($this->ruleEvaluatesTrue('1 = 1 /* or 2 = 1 */ and 2 != 2'));
        $this->assertTrue($this->ruleEvaluatesTrue('1 = 3 /* or 2 = 1 */ or 2 = 2'));
        $this->assertTrue($this->ruleEvaluatesTrue(
            '1 /* test */ = 1 /* test */ and /* test */ 2 /* test */ = /* test */ 2'
        ));
    }

    public function testNegativeNumbers()
    {
        $rule = 'TOTALAMOUNT > -1 && TOTALAMOUNT < 1';

        $this->assertTrue($this->ruleEvaluatesTrue($rule, [
            'TOTALAMOUNT' => '0'
        ]));

        $rule = 'TOTALAMOUNT = -1';

        $this->assertTrue($this->ruleEvaluatesTrue($rule, [
            'TOTALAMOUNT' => -1
        ]));
    }

    public function testSpacesInValues()
    {
        $rule = 'GREETING is "whaddup yall"';

        $this->assertTrue($this->ruleEvaluatesTrue($rule, [
            'GREETING' => 'whaddup yall'
        ]));
    }

    public function testIsOperator()
    {
        $rule = 'totalamount is -1';

        $this->assertTrue($this->ruleEvaluatesTrue($rule, [
            'TOTALAMOUNT' => '-1'
        ]));

        $rule = 'totalamount is 3';

        $this->assertFalse($this->ruleEvaluatesTrue($rule, [
            'TOTALAMOUNT' => '-1'
        ]));

        $rule = 'totalamount is not 3 and 3 is not totalamount';

        $this->assertTrue($this->ruleEvaluatesTrue($rule, [
            'TOTALAMOUNT' => '-1'
        ]));

        $this->assertFalse($this->ruleEvaluatesTrue($rule, [
            'TOTALAMOUNT' => '3'
        ]));

        $rule = 'totalamount is not 3 and 3 is not totalamount';

        $this->assertTrue($this->ruleEvaluatesTrue($rule, [
            'TOTALAMOUNT' => '-3'
        ]));
    }

    public function testSpacesBetweenStuff()
    {
        $rule = 'totalamount   is     not   3
                and    3        is    not   totalamount
                    and ( (  totalamount   is   totalamount   )
                        and   -2   <
                totalamount
            )';

        $this->assertTrue($this->ruleEvaluatesTrue($rule, [
            'TOTALAMOUNT' => '-1'
        ]));
    }

    public function testSingleLineCommentDoesNotKillTheRest()
    {
        $rule = ' 2 > 3

                // and    3        is    not   totalamount

                or totalamount is -1
            ';

        $this->assertTrue($this->ruleEvaluatesTrue($rule, [
            'totalamount' => '-1'
        ]));
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Unexpected token "(" at position 23 on line 1
     */
    public function testEmptyParenthesisThrowException()
    {
        $rule = '(totalamount is not 3) ()';

        $this->ruleEvaluatesTrue($rule, [
            'TOTALAMOUNT' => '-1'
        ]);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Missing operator at position 39 on line 1
     */
    public function testMisplacedNotThrowsException()
    {
        $rule = 'country is "EMD" and currency is "EUR" not';

        $this->ruleEvaluatesTrue($rule, [
            'COUNTRY'  => 'GLF',
            'CURRENCY' => 'USD'
        ]);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Unexpected "is" at position 11 on line 1
     */
    public function testDoubleIsOperatorThrowsException()
    {
        $rule = 'country is is "EMD"';

        $this->ruleEvaluatesTrue($rule, [
            'COUNTRY' => 'GLF',
        ]);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Unexpected "=" at position 11 on line 1
     */
    public function testDoubleOperatorThrowsException()
    {
        $rule = 'country is = "EMD"';

        $this->ruleEvaluatesTrue($rule, [
            'COUNTRY' => 'GLF',
        ]);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Incomplete expression for token "is" at position 0 on line 1
     */
    public function testMissingLeftValueThrowsException()
    {
        $rule = 'is "EMD"';

        $this->ruleEvaluatesTrue($rule, [
            'COUNTRY' => 'GLF',
        ]);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Missing operator at position 17 on line 1
     */
    public function testMissingOperatorThrowsException()
    {
        $rule = 'TOTALAMOUNT = -1 TOTALAMOUNT > 10';

        $this->ruleEvaluatesTrue($rule, [
            'TOTALAMOUNT' => '-1'
        ]);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Missing operator at position 23 on line 1
     */
    public function testMissingOperatorThrowsException2()
    {
        $rule = 'customercode = 2951356 CUSTOMERCODE=="2951356"';

        $this->ruleEvaluatesTrue($rule, [
            'CUSTOMERCODE' => '12347'
        ]);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Missing opening parenthesis at position 5
     */
    public function testMissingOpeningParenthesisThrowsException()
    {
        $this->ruleEvaluatesTrue('1 = 1)', []);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Missing closing parenthesis
     */
    public function testMissingClosingParenthesisThrowsException()
    {
        $this->ruleEvaluatesTrue('(1 = 1', []);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Unknown token "-" at position 9
     */
    public function testMisplacedMinusThrowsException()
    {
        $this->ruleEvaluatesTrue('1 = 1 && -foo = 1', ['FOO' => 1]);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Undefined variable "FOO" at position 12 on line 2
     */
    public function testUndefinedVariableThrowsException()
    {
        $rule = ' // new line on purpose
            foo = "MA"';

        $this->ruleEvaluatesTrue($rule, []);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Incomplete expression
     */
    public function testIncompleteExpressionExceptionIsThrownCorrectly()
    {
        $rule = '1 is 1 and COUNTRY';

        $this->ruleEvaluatesTrue($rule, [
            'COUNTRY' => 'MA'
        ]);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Undefined variable "COUNTRY" at position 0
     */
    public function testRulesEvaluatesTrueThrowsExceptionsForUndefinedVars()
    {
        $rule = 'COUNTRY=="MA"';

        $this->ruleEvaluatesTrue($rule, []);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Incomplete and/or condition
     */
    public function testRulesEvaluatesTrueThrowsExceptionsOnSyntaxErrors()
    {
        $rule = 'COUNTRY == "MA" &&';

        $this->ruleEvaluatesTrue($rule, [
            'COUNTRY' => 'EG'
        ]);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Unexpected "and" at position 19 on line 1
     */
    public function testMultipleLogicalTokensThrowException()
    {
        $rule = 'COUNTRY == "MA" && and';

        $this->ruleEvaluatesTrue($rule, ['COUNTRY' => 'EG']);
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage Unknown token "^" at position 16
     */
    public function testUnknownTokenExceptionIsThrown()
    {
        $rule = 'COUNTRY == "MA" ^';

        $this->ruleEvaluatesTrue($rule, [
            'COUNTRY' => 'MA'
        ]);
    }
}
