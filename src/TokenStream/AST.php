<?php declare(strict_types=1);

/**
 * @license     http://opensource.org/licenses/mit-license.php MIT
 * @link        https://github.com/nicoSWD
 * @author      Nicolas Oelgart <nico@oelgart.com>
 */
namespace nicoSWD\Rule\TokenStream;

use Closure;
use InvalidArgumentException;
use nicoSWD\Rule\Grammar\CallableUserFunctionInterface;
use nicoSWD\Rule\TokenStream\Exception\UndefinedVariableException;
use nicoSWD\Rule\TokenStream\Token\BaseToken;
use nicoSWD\Rule\TokenStream\Token\TokenFactory;
use nicoSWD\Rule\Tokenizer\TokenizerInterface;
use nicoSWD\Rule\TokenStream\Token\TokenObject;

class AST
{
    /** @var TokenizerInterface */
    private $tokenizer;
    /** @var TokenFactory */
    private $tokenFactory;
    /** @var TokenStreamFactory */
    private $tokenStreamFactory;
    /** @var Closure[] */
    private $functions = [];
    /** @var string[] */
    private $methods = [];
    /** @var mixed[] */
    private $variables = [];

    public function __construct(
        TokenizerInterface $tokenizer,
        TokenFactory $tokenFactory,
        TokenStreamFactory $tokenStreamFactory
    ) {
        $this->tokenizer = $tokenizer;
        $this->tokenFactory = $tokenFactory;
        $this->tokenStreamFactory = $tokenStreamFactory;
    }

    public function getStream(string $rule): TokenStream
    {
        return $this->tokenStreamFactory->create($this->tokenizer->tokenize($rule), $this);
    }

    public function getMethod(string $methodName, BaseToken $token): CallableUserFunctionInterface
    {
        if ($token instanceof TokenObject) {
            return $this->getUserObjectCallable($token, $methodName);
        }

        if (empty($this->methods)) {
            $this->registerMethods();
        }

        if (!isset($this->methods[$methodName])) {
            throw new Exception\UndefinedMethodException($methodName);
        }

        return new $this->methods[$methodName]($token);
    }

    private function registerMethods()
    {
        $this->methods = $this->tokenizer->getGrammar()->getInternalMethods();
    }

    public function setVariables(array $variables)
    {
        $this->variables = $variables;
    }

    public function getVariable(string $variableName): BaseToken
    {
        if (!$this->variableExists($variableName)) {
            throw new UndefinedVariableException($variableName);
        }

        return $this->tokenFactory->createFromPHPType($this->variables[$variableName]);
    }

    public function variableExists(string $variableName): bool
    {
        return array_key_exists($variableName, $this->variables);
    }

    public function getFunction(string $functionName): Closure
    {
        if (empty($this->functions)) {
            $this->registerFunctions();
        }

        if (!isset($this->functions[$functionName])) {
            throw new Exception\UndefinedFunctionException($functionName);
        }

        return $this->functions[$functionName];
    }

    private function registerFunctionClass(string $functionName, string $className)
    {
        $this->functions[$functionName] = function (BaseToken ...$args) use ($className): BaseToken {
            $function = new $className();

            if (!$function instanceof CallableUserFunctionInterface) {
                throw new InvalidArgumentException(
                    sprintf(
                        "%s must be an instance of %s",
                        $className,
                        CallableUserFunctionInterface::class
                    )
                );
            }

            return $function->call(...$args);
        };
    }

    private function registerFunctions()
    {
        foreach ($this->tokenizer->getGrammar()->getInternalFunctions() as $functionName => $className) {
            $this->registerFunctionClass($functionName, $className);
        }
    }

    private function getUserObjectCallable(BaseToken $token, string $methodName): CallableUserFunctionInterface
    {
        return new class ($token, $this->tokenFactory, $methodName) implements CallableUserFunctionInterface
        {
            /** @var BaseToken */
            private $token;
            /** @var TokenFactory */
            private $tokenFactory;
            /** @var string */
            private $methodName;

            public function __construct(BaseToken $token, TokenFactory $tokenFactory, string $methodName)
            {
                $this->token = $token;
                $this->tokenFactory = $tokenFactory;
                $this->methodName = $methodName;
            }

            public function call(BaseToken $param = null): BaseToken
            {
                $object = [$this->token->getValue(), $this->methodName];

                if (!is_callable($object)) {
                    throw new \Exception();
                }

                return $this->tokenFactory->createFromPHPType($object());
            }
        };
    }
}
