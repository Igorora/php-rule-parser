<?php

declare(strict_types=1);

/**
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        https://github.com/nicoSWD
 * @author      Nicolas Oelgart <nico@oelgart.com>
 */
namespace nicoSWD\Rules;

use Exception;
use nicoSWD\Rules\Grammar\JavaScript\JavaScript;

class Rule
{
    /** @var string */
    private $rule;

    /** @var Parser */
    private $parser;

    /** @var Evaluator */
    private $evaluator;

    /** @var string */
    private $parsedRule = '';

    /** @var string */
    private $error = '';

    public function __construct(string $rule, array $variables = [])
    {
        $this->rule = $rule;
        $this->parser = new Parser(new Tokenizer(new JavaScript()), new Expressions\Factory(), new RuleGenerator());
        $this->evaluator = new Evaluator();

        $this->parser->assignVariables($variables);
    }

    public function isTrue(): bool
    {
        return $this->evaluator->evaluate(
            $this->parsedRule ?:
            $this->parser->parse($this->rule)
        );
    }

    public function isFalse(): bool
    {
        return !$this->isTrue();
    }

    /**
     * Tells whether a rule is valid (as in "can be parsed without error") or not.
     */
    public function isValid(): bool
    {
        try {
            $this->parsedRule = $this->parser->parse($this->rule);
        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }

        return true;
    }

    public function getError(): string
    {
        return $this->error;
    }

    public function registerToken(string $class, string $regex, int $priority = 10)
    {
        $this->parser->registerToken($class, $regex, $priority);
    }
}
