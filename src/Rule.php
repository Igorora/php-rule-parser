<?php
/**
 * Created by PhpStorm.
 * User: Nico
 * Date: 17/07/15
 * Time: 20:48
 */

namespace nicoSWD\Rules;

/**
 * Class Rule
 * @package nicoSWD\Rules
 */
class Rule
{
    /**
     * @var string
     */
    private $rule;

    /**
     * @var Parser
     */
    private $parser;

    /**
     * @var Evaluator
     */
    private $evaluator;

    /**
     * @param string $rule
     * @param array  $variables
     */
    public function __construct($rule, array $variables = [])
    {
        $this->rule = (string) $rule;
        $this->parser = new Parser(new Tokenizer());
        $this->evaluator = new Evaluator();

        $this->parser->assignVariables($variables);
    }

    /**
     * @return bool
     */
    public function isTrue()
    {
        return $this->evaluator->evaluate(
            $this->parser->parse($this->rule)
        );
    }

    /**
     * @return bool
     */
    public function isFalse()
    {
        return !$this->isTrue();
    }
}
