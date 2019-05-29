<?php namespace SuperClosure\ClosureParser\Ast;

use SuperClosure\ClosureBinding;
use SuperClosure\ClosureParser\ClosureContext;
use PhpParser\Node\Expr\Closure as ClosureAst;

class AstClosureContext extends ClosureContext
{
    /**
     * @var ClosureAst
     */
    protected $ast;

    /**
     * @var ClosureLocation
     */
    protected $location;

    /**
     * @param string          $code
     * @param array           $variables
     * @param ClosureAst      $ast
     * @param ClosureLocation $location
     * @param ClosureBinding  $binding
     */
    public function __construct(
        $code,
        array $variables,
        ClosureAst $ast,
        ClosureLocation $location,
        ClosureBinding $binding = null
    ) {
        parent::__construct($code, $variables, $binding);
        $this->ast = $ast;
        $this->location = $location;
    }

    /**
     * @return ClosureAst
     */
    public function getAst()
    {
        return $this->ast;
    }

    /**
     * @return ClosureLocation
     */
    public function getLocation()
    {
        return $this->location;
    }
}
