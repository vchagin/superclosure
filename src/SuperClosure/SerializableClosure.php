<?php namespace SuperClosure;

use SuperClosure\ClosureParser\ClosureParser;
use SuperClosure\ClosureParser\Ast\AstParser as DefaultClosureParser;

/**
 * This class allows you to do the impossible: serialize closures! With the
 * combined power of lexical parsing, the Reflection API, and the infamous eval
 * function, you can serialize a closure, unserialize it in a different PHP
 * process, and execute it. It's like function teleportation!
 */
class SerializableClosure extends SuperClosure implements \Serializable
{
    /**
     * @var ClosureParser The closure parser that will be used to determine the closure's context
     */
    protected $closureParser;

    /**
     * @var string
     */
    protected $code;

    /**
     * @var array
     */
    protected $variables;

    /**
     * @param \Closure      $closure
     * @param ClosureParser $closureParser
     */
    public function __construct(\Closure $closure, ClosureParser $closureParser = null)
    {
        parent::__construct($closure);

        $this->closureParser = $closureParser ?: new DefaultClosureParser;
    }

    /**
     * @return string
     */
    public function getCode()
    {
        $this->fetchSerializableData();

        return $this->code;
    }

    /**
     * @return array
     */
    public function getVariables()
    {
        $this->fetchSerializableData();

        return $this->variables;
    }

    /**
     * Serialize the code and of context of the closure
     *
     * @return string
     */
    public function serialize()
    {
        try {
            // Prepare the data for serialization
            $this->fetchSerializableData();
        } catch (\Exception $e) {
            // Note: The serialize() method cannot throw exceptions and must return string or null
            return null;
        }

        return \serialize(array($this->code, $this->variables, $this->binding));
    }

    /**
     * Unserializes the closure's data and recreates the closure and tis context. The used variables are extracted into
     * the scope prior to redefining the closure. If the closure's binding was serialized (PHP 5.4+), then the closure
     * will also be rebound to its object and scope. NOTE: There be dragons here! Both `eval()` & `extract()` are used
     * in this method to perform the unserialization.
     *
     * @param string $serialized
     *
     * @throws ClosureUnserializationException
     */
    public function unserialize($serialized)
    {
        // Unserialize the data we need to reconstruct the SuperClosure
        list($this->code, $this->variables, $this->binding) = \unserialize($serialized);

        // Simulate the original context the Closure was created in
        extract($this->variables);

        // Evaluate the code to recreate the Closure
        @eval("\$this->closure = {$this->code};"); // HERE BE DRAGONS!
        if (!$this->closure instanceof \Closure) {
            throw new ClosureUnserializationException(
                'The serialized closure was corrupted and cannot be unserialized.'
            );
        }

        // Rebind the closure to its former $this object and scope (or to null, if there was no binding serialized)
        if (Env::supportsBindings()) {
            $this->bindTo($this->binding);
        }
    }

    /**
     * Uses the closure parser to get information about the closure required for serialization
     */
    protected function fetchSerializableData()
    {
        if (!$this->code) {
            // Use the parser to fetch the closure context
            $parser = $this->closureParser;
            $context = $parser->parse($this);

            // Save the data from the closure context, and wrap any inner closures to be serializable as well
            $this->code = $context->getCode();
            $this->binding = $context->getBinding();
            $this->variables = array_map(function ($variable) use ($parser) {
                if ($variable instanceof \Closure) {
                    return new SerializableClosure($variable, $parser);
                } else {
                    return $variable;
                }
            }, $context->getVariables());
        }
    }
}
