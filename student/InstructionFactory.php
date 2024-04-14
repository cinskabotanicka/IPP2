<?php

/**
 * Soubor pro třídu InstructionFactory, Instruction a její potomky.
 * @author xhroma15
 * 
 */

namespace IPP\Student;

use IPP\Student\SemanticException;
use IPP\Student\Variable;
use IPP\Student\FrameManager;
use IPP\Student\Frame;
use IPP\Student\Exceptions\OperandTypeException;
use IPP\Student\Exceptions\VariableAccessException;
use IPP\Student\Interpreter;

class InstructionFactory
{
    protected $frameManager;

    public function __construct()
    {
        $this->frameManager = new FrameManager();
    }   
    
    public static $classMap = [
        'move' => VariableOperation::class, // MOVE ⟨var⟩ ⟨symb⟩
        'createframe' => FrameOperation::class, // CREATEFRAME
        'pushframe' => FrameOperation::class, // PUSHFRAME
        'popframe' => FrameOperation::class, // POPFRAME
        'defvar' => VariableOperation::class, // DEFVAR ⟨var⟩
        'call' => Label::class, // CALL ⟨label⟩
        'return' => Operation::class, // RETURN
        'pushs' => VariableOperation::class, // PUSHS ⟨symb⟩
        'pops' => VariableOperation::class, // POPS ⟨var⟩
        'add' => Arithmetic::class, // ADD ⟨var⟩ ⟨symb1⟩ ⟨symb2⟩
        'sub' => Arithmetic::class, // SUB ⟨var⟩ ⟨symb1⟩ ⟨symb2⟩
        'mul' => Arithmetic::class, // MUL ⟨var⟩ ⟨symb1⟩ ⟨symb2⟩
        'idiv' => Arithmetic::class, // IDIV ⟨var⟩ ⟨symb1⟩ ⟨symb2⟩
        'lt' => Comparison::class, // LT ⟨var⟩ ⟨symb1⟩ ⟨symb2⟩
        'gt' => Comparison::class, // GT ⟨var⟩ ⟨symb1⟩ ⟨symb2⟩
        'eq' => Comparison::class, // EQ ⟨var⟩ ⟨symb1⟩ ⟨symb2⟩
        'and' => Logical::class, // AND ⟨var⟩ ⟨symb1⟩ ⟨symb2⟩
        'or' => Logical::class, // OR ⟨var⟩ ⟨symb1⟩ ⟨symb2⟩
        'not' => Logical::class, // NOT ⟨var⟩ ⟨symb1⟩
        'int2char' => TypeConversion::class, // INT2CHAR ⟨var⟩ ⟨symb⟩
        'stri2int' => TypeConversion::class, // STRI2INT ⟨var⟩ ⟨symb1⟩ ⟨symb2⟩
        'read' => Input::class, // READ ⟨var⟩ ⟨type⟩
        'write' => Output::class, // WRITE ⟨symb⟩
        'concat' => Operation::class, // CONCAT ⟨var⟩ ⟨symb1⟩ ⟨symb2⟩
        'strlen' => Operation::class, // STRLEN ⟨var⟩ ⟨symb⟩
        'getchar' => Operation::class, // GETCHAR ⟨var⟩ ⟨symb1⟩ ⟨symb2⟩
        'setchar' => Operation::class, // SETCHAR ⟨var⟩ ⟨symb1⟩ ⟨symb2⟩
        'type' => Operation::class, // TYPE ⟨var⟩ ⟨symb⟩  
        'label' => Label::class, // LABEL ⟨label⟩
        'jump' => Label::class, // JUMP ⟨label⟩
        'jumpifeq' => ConditionalJump::class, // JUMPIFEQ ⟨label⟩ ⟨symb1⟩ ⟨symb2⟩
        'jumpifneq' => ConditionalJump::class, // JUMPIFNEQ ⟨label⟩ ⟨symb1⟩ ⟨symb2⟩
        'exit' => Label::class, // EXIT ⟨symb⟩
        'dprint' => Debug::class, // DPRINT ⟨symb⟩
        'break' => Debug::class // BREAK
    ];

    public function createInstruction(string $opcode, array $args, int $order)
    {
        $opcode = strtolower($opcode);
        if (array_key_exists($opcode, self::$classMap)) {
            $className = self::$classMap[$opcode];
            $instruction = new $className($opcode, $args, $order, $this->frameManager);
            return $instruction;
        } else {
            throw new SemanticException('Unknown opcode');
        }
    }

}

trait ArgumentCountChecker
{
    /**
     * Zkontroluje, zda má pole argumentů správný počet prvků.
     *
     * @param array $args Pole argumentů.
     * @param int $expectedCount Očekávaný počet prvků.
     */
    protected function checkArgumentCount(array $args, int $expectedCount)
    {
        if (count($args) !== $expectedCount) {
            throw new SemanticException('Invalid number of arguments');
        }
    }
}

trait ArgumentTypeChecker
{
    /**
     * Zkontroluje, zda je daný argument typu Operand.
     *
     * @param mixed $arg Argument k ověření.
     * @throws \Exception Pokud argument není typu Operand.
     */
    protected function checkIsOperand($arg)
    {
        if (!$arg instanceof Operand) {
            return;
        }
    }

    /**
     * Zkontroluje, zda je daný argument typu Variable.
     *
     * @param mixed $arg Argument k ověření.
     * @throws \Exception Pokud argument není typu Variable.
     */
    protected function checkIsVariable($arg)
    {
        if (!$arg instanceof Variable) {
            return;
        }
    }
}

abstract class Instruction
{
    protected $opcode;
    protected $order;
    protected $args = [];
    protected FrameManager $frameManager;

    public function __construct(string $opcode, array $args, int $order, FrameManager $frameManager)
    {
        $this->opcode = $opcode;
        $this->args = $args;
        $this->order = $order;
        $this->frameManager = $frameManager;
    }

    public function getOpcode()
    {
        return $this->opcode;
    }

    public static function getClassName($opcode)
    {
        if (isset(InstructionFactory::$classMap[$opcode])) {
            return InstructionFactory::$classMap[$opcode];
        } else {
            throw new SemanticException('Unknown opcode');
        }
    }

    public function getArgs()
    {
        return $this->args;
    }

    public function getOrder()
    {
        return $this->order;
    }

}

/**
 * Třída pro operace s rámci.
 */
class FrameOperation extends Instruction
{
    use ArgumentCountChecker, ArgumentTypeChecker;
    
    public function __construct($opcode, $args, $order)
    {
        $this->checkArgumentCount($this->args, 0);
        parent::__construct($opcode, $args, $order); 
    }

    private function createFrame()
    {

    }

    private function pushFrame()
    {

    }

    private function popFrame()
    {

    }

    public function getSpecificMethod() 
    {
        switch ($this->opcode) {
            case 'createframe':
                return [$this, 'createFrame'];
            case 'pushframe':
                return [$this, 'pushFrame'];
            case 'popframe':
                return [$this, 'popFrame'];
            default:
                throw new SemanticException('Unknown opcode');
        }
    }
}

/**
 * Třída pro operace s návěštími.
 */
class Label extends Instruction
{
    public function __construct($opcode, $args, $order, $frameManager)
    {
        parent::__construct($opcode, $args, $order, $frameManager);
    }

    public function label()
    {
        // Doplň implementační logiku pro LABEL
    }

    public function call()
    {
        // Doplň implementační logiku pro CALL
    }

    public function jump()
    {
        // Doplň implementační logiku pro JUMP
    }

    public function exit()
    {
        // Doplň implementační logiku pro EXIT
    }

    public function getSpecificMethod()
    {
        switch ($this->opcode) {
            case 'label':
                return [$this, 'label'];
            case 'call':
                return [$this, 'call'];
            case 'jump':
                return [$this, 'jump'];
            case 'exit':
                return [$this, 'exit'];
            default:
                throw new SemanticException('Unknown opcode');
        }
    }
}

/**
 * Třída pro operace s řetězci.
 */
class Operation extends Instruction
{
    public function __construct($opcode, $args, $order, $frameManager)
    {
        parent::__construct($opcode, $args, $order, $frameManager);
    }

    public function return()
    {
        // Doplň implementační logiku pro RETURN
    }

    public function concat()
    {
        // Doplň implementační logiku pro CONCAT
    }

    public function strlen()
    {
        // Doplň implementační logiku pro STRLEN
    }   

    public function getchar()
    {
        // Doplň implementační logiku pro GETCHAR
    }

    public function setchar()
    {
        // Doplň implementační logiku pro SETCHAR
    }

    public function type()
    {
        // Doplň implementační logiku pro TYPE
    }

    public function getSpecificMethod()
    {
        switch ($this->opcode) {
            case 'return':
                return [$this, 'return'];
            case 'concat':
                return [$this, 'concat'];
            case 'strlen':
                return [$this, 'strlen'];
            case 'getchar':
                return [$this, 'getchar'];
            case 'setchar':
                return [$this, 'setchar'];
            case 'type':
                return [$this, 'type'];
            default:
                throw new SemanticException('Unknown opcode');
        }
    }
}

/**
 * Třída pro aritmetické operace.
 */
class Arithmetic extends Instruction
{
    public function __construct($opcode, $args, $order, $frameManager)
    {
        parent::__construct($opcode, $args, $order, $frameManager);
    }

    public function add()
    {
        // Ověření, zda je první argument proměnná
        if (!($this->args[0] instanceof Variable)) {
            throw new OperandTypeException('First argument must be variable');
        }
        // Ověření, zda je druhý argument integer
        if (!($this->args[1] instanceof Operand) || $this->args[1]->getType() !== 'int') {
            throw new OperandTypeException('Second argument must be integer');
        }
        // Ověření, zda je třetí argument integer
        if (!($this->args[2] instanceof Operand) || $this->args[2]->getType() !== 'int') {
            throw new OperandTypeException('Third argument must be integer');
        }
        // Ověření, zda je proměnná z prvního argumentu inicializovaná v aktuálním rámci
        $currentFrame = $this->frameManager->getCurrentFrame();
        if (!$currentFrame->isVariableInFrame($this->args[0])) {
            throw new VariableAccessException('Variable is not initialized');
        }
        // Přičtení hodnot do proměnné v rámci
        $result = ($this->args[1]->getValue() + $this->args[2]->getValue());
        $currentFrame->addValueToVariable($this->args[0], $result);
    }

    public function sub()
    {
        // Doplň implementační logiku pro SUB
    }

    public function mul()
    {
        // Doplň implementační logiku pro MUL
    }

    public function idiv()
    {
        // Doplň implementační logiku pro IDIV
    }

    public function getSpecificMethod()
    {
        switch ($this->opcode) {
            case 'add':
                return [$this, 'add'];
            case 'sub':
                return [$this, 'sub'];
            case 'mul':
                return [$this, 'mul'];
            case 'idiv':
                return [$this, 'idiv'];
            default:
                throw new SemanticException('Unknown opcode');
        }
    }
}

/**
 * Třída pro porovnávací operace.
 */
class Comparison extends Instruction
{
    public function __construct($opcode, $args, $order, $frameManager)
    {
        parent::__construct($opcode, $args, $order, $frameManager);
    }

    public function lt()
    {
        // Doplň implementační logiku pro LT
    }

    public function gt()
    {
        // Doplň implementační logiku pro GT
    }

    public function eq()
    {
        // Doplň implementační logiku pro EQ
    }

    public function getSpecificMethod()
    {
        switch ($this->opcode) {
            case 'lt':
                return [$this, 'lt'];
            case 'gt':
                return [$this, 'gt'];
            case 'eq':
                return [$this, 'eq'];
            default:
                throw new SemanticException('Unknown opcode');
        }
    }
}

/**
 * Třída pro logické operace.
 */
class Logical extends Instruction
{
    public function __construct($opcode, $args, $order, $frameManager)
    {
        parent::__construct($opcode, $args, $order, $frameManager);
    }

    public function and()
    {
        // Doplň implementační logiku pro AND
    }

    public function or()
    {
        // Doplň implementační logiku pro OR
    }

    public function not()
    {
        // Doplň implementační logiku pro NOT
    }

    public function getSpecificMethod()
    {
        switch ($this->opcode) {
            case 'and':
                return [$this, 'and'];
            case 'or':
                return [$this, 'or'];
            case 'not':
                return [$this, 'not'];
            default:
                throw new SemanticException('Unknown opcode');
        }
    }
}

/**
 * Třída pro operace s typy.
 */
class TypeConversion extends Instruction
{
    public function __construct($opcode, $args, $order, $frameManager)
    {
        parent::__construct($opcode, $args, $order, $frameManager);
    }

    public function int2char()
    {
        // Doplň implementační logiku pro INT2CHAR
    }

    public function stri2int()
    {
        // Doplň implementační logiku pro STRI2INT
    }

    public function getSpecificMethod()
    {
        switch ($this->opcode) {
            case 'int2char':
                return [$this, 'int2char'];
            case 'stri2int':
                return [$this, 'stri2int'];
            default:
                throw new SemanticException('Unknown opcode');
        }
    }
}

/**
 * Třída pro vstupní instrukce.
 */
class Input extends Instruction
{
    public function __construct($opcode, $args, $order, $frameManager)
    {
        parent::__construct($opcode, $args, $order, $frameManager);
        //reads input from stdin and stores it

    }

    public function read()
    {
        // Doplň implementační logiku pro READ
    }

    public function getSpecificMethod()
    {
        switch ($this->opcode) {
            case 'read':
                return [$this, 'read'];
            default:
                throw new SemanticException('Unknown opcode');
        }
    }
}

/**
 * Třída pro výstupní instrukce.
 */
class Output extends Instruction
{
    public function __construct($opcode, $args, $order, $frameManager)
    {
        parent::__construct($opcode, $args, $order, $frameManager);
    }

    public function write()
    {
        // Pokud je argument proměnná, vypíše její hodnotu
        if ($this->args[0] instanceof Variable) {
            $currentFrame = $this->frameManager->getCurrentFrame();
            $var = $currentFrame->getVariable($this->args[0]);
            // Vypíše hodnotu proměnné a přidá konce řádku
            echo $var->getValue() . PHP_EOL;
        } else {
            // Pokud je argument operand, vypíše jeho hodnotu
            echo $this->args[0]->getValue();
        }
    }

    public function getSpecificMethod()
    {
        switch ($this->opcode) {
            case 'write':
                return [$this, 'write'];
            default:
                throw new SemanticException('Unknown opcode');
        }
    }
}


/**
 * Třída pro debugovací instrukce.
 */
class Debug extends Instruction
{
    public function __construct($opcode, $args, $order, $frameManager)
    {
        parent::__construct($opcode, $args, $order, $frameManager);
    }

    public function dprint()
    {
        // Doplň implementační logiku pro DPRINT
    }

    public function break()
    {
        // Doplň implementační logiku pro BREAK
    }

    public function getSpecificMethod()
    {
        switch ($this->opcode) {
            case 'dprint':
                return [$this, 'dprint'];
            case 'break':
                return [$this, 'break'];
            default:
                throw new SemanticException('Unknown opcode');
        }
    }
}


/**
 * Třída pro operace s proměnnými.
 */
class VariableOperation extends Instruction
{
    use ArgumentCountChecker, ArgumentTypeChecker;

    public function __construct($opcode, $args, $order, $frameManager)
    {
        parent::__construct($opcode, $args, $order, $frameManager);
    }

    public function defvar()
    {
        $this->checkArgumentCount($this->args, 1);
        // přidá proměnnou do aktuálního rámce
        $currentFrame = $this->frameManager->getCurrentFrame();
        $currentFrame->addVariable($this->args[0]);
    }

    public function move()
    {
        $this->checkArgumentCount($this->args, 2);
        $this->checkIsVariable($this->args[0]);
        $this->checkIsOperand($this->args[1]);
        // přesune hodnotu z operandu do proměnné
    }

    public function pushs()
    {
        $this->checkArgumentCount($this->args, 1);
        $this->checkIsOperand($this->args[0]);
        // přidá hodnotu operandu na zásobník
    }

    public function pops()
    {
        $this->checkArgumentCount($this->args, 1);
        $this->checkIsVariable($this->args[0]);
        // odebere hodnotu ze zásobníku a uloží do proměnné
    }

    public function getSpecificMethod()
    {
        switch ($this->opcode) {
            case 'defvar':
                return [$this, 'defvar'];
            case 'move':
                return [$this, 'move'];
            case 'pushs':
                return [$this, 'pushs'];
            case 'pops':
                return [$this, 'pops'];
            default:
                throw new SemanticException('Unknown opcode');
        }
    }
}

/**
 * Třída pro skokové instrukce.
 */
class ConditionalJump extends Instruction
{
    use ArgumentCountChecker, ArgumentTypeChecker;

    public function __construct($opcode, $args, $order, $frameManager)
    {
        parent::__construct($opcode, $args, $order, $frameManager);
        $this->checkArgumentCount($args, 3);
    }

    public function jumpIfEq()
    {
        $this->checkIsVariable($this->args[0]); 
        $this->checkIsOperand($this->args[1]); 
        $this->checkIsOperand($this->args[2]);
    }

    public function jumpIfNeq()
    {
        // Doplň implementační logiku pro JUMPIFNEQ
    }

    public function getSpecificMethod()
    {
        switch ($this->opcode) {
            case 'jumpifeq':
                return [$this, 'jumpIfEq'];
            case 'jumpifneq':
                return [$this, 'jumpIfNeq']; 
            default:
                throw new SemanticException('Unknown opcode');
        }
    }
}