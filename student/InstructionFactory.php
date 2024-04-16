<?php

/**
 * Soubor pro třídu InstructionFactory, Instruction a její potomky.
 * @author xhroma15
 * 
 */

namespace IPP\Student;

use IPP\Student\Exceptions\SemanticException;
use IPP\Student\Variable;
use IPP\Student\FrameManager;
use IPP\Student\Exceptions\OperandTypeException;
use IPP\Student\Exceptions\VariableAccessException;
use IPP\Student\Exceptions\OperandValueException;
use IPP\Student\Exceptions\FrameAccessException;
use IPP\Core\Settings;
use IPP\Student\Exceptions\StringOperationException;
use IPP\Student\Exceptions\ValueException;

/**
 * Třída InstructionFactory.
 * Třída pro vytváření instrukcí.
 */
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
        $this->frameManager->executedInstruction();      
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
    use ArgumentCountChecker;
    
    public function __construct($opcode, $args, $order, $frameManager)
    {
        $this->checkArgumentCount($this->args, 0);
        parent::__construct($opcode, $args, $order, $frameManager); 
    }

    private function createFrame()
    {
        // Zkontroluje, zda je počet argumentů správný
        if (count($this->args) !== 0) {
            throw new SemanticException('Invalid number of arguments');
        }

        // Zahodí případný původní dočasný rámec
        if ($this->frameManager->getCurrentFrame()->getFrameName() === 'TF'){
            $this->frameManager->popFrame();
        }

        // Vytvoří nový dočasný paměťový rámec
        $this->frameManager->createFrame('TF');
    }

    private function pushFrame()
    {
        // Zkontroluje počet argumentů
        if (count($this->args) !== 0) {
            throw new SemanticException('Invalid number of arguments');
        }

        // Zkontroluje, zda je aktuální rámec TF
        $currentFrame = $this->frameManager->getCurrentFrame();
        if ($currentFrame->getFrameName() !== 'TF') {
            throw new FrameAccessException("No TemporaryFrame (TF) available to push onto the stack.");
        }

        // Přesune TF na zásobník rámců, ale s novým názvem LF
        $this->frameManager->popFrame();
        $this->frameManager->createFrame('LF');
        $currentFrame->moveVariablesToFrame($this->frameManager->getCurrentFrame());
    }

    private function popFrame()
    {
        // Zkontroluje, zda je aktuální rámec LF
        $currentFrame = $this->frameManager->getCurrentFrame();
        if ($currentFrame->getFrameName() !== 'LF') {
            throw new FrameAccessException("No LocalFrame (LF) available to pop from the stack.");
        }
        // Přesune LF na zásobník rámců, ale s novým názvem TF
        $this->frameManager->popFrame();
        $this->frameManager->createFrame('TF');
        $currentFrame->moveVariablesToFrame($this->frameManager->getCurrentFrame());
        
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
        // Zkontroluje počet argumentů
        if (count($this->args) !== 1) {
            throw new SemanticException('Invalid number of arguments');
        }
    }

    public function call()
    {
        // Zkontroluje počet argumentů
        if (count($this->args) !== 1) {
            throw new SemanticException('Invalid number of arguments');
        }

        // Zkontroluje, zda je argument typu label
        if (!($this->args[0]->getType() === 'label')) {
            throw new OperandTypeException('Operand must be of type label');
        }
    }

    public function jump()
    {
        // Zkontroluje počet argumentů
        if (count($this->args) !== 1) {
            throw new SemanticException('Invalid number of arguments');
        }
        // Zkontroluje, zda je argument typu label
        if (!($this->args[0]->getType() === 'label')) {
            throw new OperandTypeException('Operand must be of type label');
        }
    }

    public function exit()
    {
        $symb = $this->args[0];

        // Kontrola počtu argumentů
        if (count($this->args) !== 1) {
            throw new SemanticException('Invalid number of arguments');
        }

        // Kontrola typu argumentu
        if (!($symb instanceof Operand)) {
            throw new OperandTypeException('Operand must be of type');
        }
        // Zkontroluje, zda je v operandu celočíselná hodnota
        if ($symb->getType() !== 'int') {
            throw new OperandValueException('Operand must be integer');
        }

        // Získá hodnotu operandu
        $value = $symb->getValue();

        // Zkontroluje, zda je hodnota v intervalu 0 až 9
        if ($value < 0 || $value > 9) {
            throw new OperandValueException('Invalid exit code');
        }

        // Ukončí interpretaci s daným návratovým kódem
        exit($value);
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
        // Zkontroluje, zda je počet argumentů správný
        if (count($this->args) !== 0) {
            throw new SemanticException('Invalid number of arguments');
        }

    }

    public function concat()
    {
        $var = $this->args[0];
        $symb1 = $this->args[1];
        $symb2 = $this->args[2];

        // Zkontroluje, zda je proměnná z prvního argumentu inicializovaná v aktuálním rámci nebo v globálním rámci
        $currentFrame = $this->frameManager->getCurrentFrame();
        if (!$currentFrame->isVariableInFrame($var) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($var)) {
            throw new VariableAccessException('Variable is not initialized');
        }
        // Zjistí, zda je proměnná v aktuálním rámci nebo v globálním rámci a uloží do proměnné $frame
        $frame = $currentFrame->isVariableInFrame($var) ? $currentFrame : $this->frameManager->getGlobalFrame();

        // Zkontroluje, zda je první argument proměnná
        if (!($var instanceof Variable)) {
            throw new OperandTypeException('First argument must be variable');
        }

        // Zkontroluje, zda jsou druhý a třetí argument typu string, nebo jsou to proměnné typu string
        if ($symb1->getType() !== 'string' && $symb1->getType() !== 'var') {
            throw new OperandTypeException('Second argument must be string or variable');
        }
        if ($symb2->getType() !== 'string' && $symb2->getType() !== 'var') {
            throw new OperandTypeException('Third argument must be string or variable');
        }
        // Zkontroluje, zda je proměnná z druhého argumentu inicializovaná v aktuálním rámci nebo v globálním rámci 
        if ($symb1->getType() === 'var' && !$currentFrame->isVariableInFrame($symb1) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($symb1)) {
            throw new VariableAccessException('Variable is not initialized');
        }
        // Uloží do proměnné $frame
        if ($symb1->getType() === 'var') {
            $frame1 = $currentFrame->isVariableInFrame($symb1) ? $currentFrame : $this->frameManager->getGlobalFrame();
        }
        // Zkontroluje, zda je proměnná z třetího argumentu inicializovaná v aktuálním rámci nebo v globálním rámci
        if ($symb2->getType() === 'var' && !$currentFrame->isVariableInFrame($symb2) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($symb2)) {
            throw new VariableAccessException('Variable is not initialized');
        }
        // Uloží do proměnné $frame
        if ($symb2->getType() === 'var') {
            $frame2 = $currentFrame->isVariableInFrame($symb2) ? $currentFrame : $this->frameManager->getGlobalFrame();
        }

        // Získání hodnot operandů
        $value1 = $symb1->getType() === 'var' ? $frame1->getVariable($symb1)->getValue() : $symb1->getValue();
        $value2 = $symb2->getType() === 'var' ? $frame2->getVariable($symb2)->getValue() : $symb2->getValue();
        // Provede konkatenaci dvou řetězců a uloží do proměnné
        $concatenatedString = $value1 . $value2;
        $frame->addValueToVariable($var, $concatenatedString);
    }

    public function strlen()
    {
        $var = $this->args[0];
        $symb = $this->args[1];

        // Zkontroluje, zda je proměnná z prvního argumentu inicializovaná v aktuálním rámci
        $currentFrame = $this->frameManager->getCurrentFrame();
        if (!$currentFrame->isVariableInFrame($var) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($var)) {
            throw new VariableAccessException('Variable is not initialized');
        }
        // Zjistí, zda je proměnná v aktuálním rámci nebo v globálním rámci a uloží do proměnné $frame
        $frame = $currentFrame->isVariableInFrame($var) ? $currentFrame : $this->frameManager->getGlobalFrame();

        // Zkontroluje, zda je první argument proměnná
        if (!($var instanceof Variable)) {
            throw new OperandTypeException('First argument must be variable');
        }

        // Zkontroluje, zda je druhý argument typu string
        if ($symb->getType() !== 'string') {
            throw new OperandTypeException('Second argument must be string');
        }

        // Získání délky řetězce a uložení do proměnné
        $stringLength = mb_strlen($symb->getValue(), 'UTF-8');
        $frame->addValueToVariable($var, $stringLength);
    }   

    public function getchar()
    {
        // Zkontroluje, zda je počet argumentů správný
        if (count($this->args) !== 3) {
            throw new SemanticException('Invalid number of arguments');
        }
        $var = $this->args[0];
        $symb1 = $this->args[1];
        $symb2 = $this->args[2];

        // Zkontroluje, zda je proměnná z prvního argumentu inicializovaná v aktuálním rámci
        $currentFrame = $this->frameManager->getCurrentFrame();
        if (!$currentFrame->isVariableInFrame($var) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($var)) {
            throw new VariableAccessException('Variable is not initialized');
        }
        // Zjistí, zda je proměnná v aktuálním rámci nebo v globálním rámci a uloží do proměnné $frame
        $frame = $currentFrame->isVariableInFrame($var) ? $currentFrame : $this->frameManager->getGlobalFrame();

        // Zkontroluje, zda je první argument proměnná
        if (!($var instanceof Variable)) {
            throw new OperandTypeException('First argument must be variable');
        }

        // Zkontroluje, zda jsou druhý a třetí argument typu string a integer
        if ($symb1->getType() !== 'string' || $symb2->getType() !== 'int') {
            throw new OperandTypeException('Second argument must be string, third argument must be integer');
        }

        // Nastavení proměnných pro získání znaku
        $string = $symb1->getValue();
        $index = $symb2->getValue();

        // Zkontroluje, zda je index v rozsahu řetězce
        if ($index < 0 || $index >= mb_strlen($string, 'UTF-8')) {
            throw new StringOperationException('Index out of bounds');
        }

        // Získání znaku na daném indexu a uložení do proměnné
        $character = mb_substr($string, $index, 1, 'UTF-8');
        $frame->addValueToVariable($var, $character);
    }

    public function setchar()
    {
        // Zkontroluje, zda je počet argumentů správný
        if (count($this->args) !== 3) {
            throw new SemanticException('Invalid number of arguments');
        }

        $var = $this->args[0];
        $symb1 = $this->args[1];
        $symb2 = $this->args[2];
    
        // Zkontroluje, zda je proměnná z prvního argumentu inicializovaná v aktuálním rámci
        $currentFrame = $this->frameManager->getCurrentFrame();
        if (!$currentFrame->isVariableInFrame($var) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($var)) {
            throw new VariableAccessException('Variable is not initialized');
        }
        // Zjistí, zda je proměnná v aktuálním rámci nebo v globálním rámci a uloží do proměnné $frame
        $frame = $currentFrame->isVariableInFrame($var) ? $currentFrame : $this->frameManager->getGlobalFrame();
    
        // Zkontroluje, zda je první argument proměnná
        if (!($var instanceof Variable)) {
            throw new OperandTypeException('First argument must be variable');
        }
    
        // Zkontroluje, zda jsou druhý a třetí argument typu integer a string
        if ($symb1->getType() !== 'int' || $symb2->getType() !== 'string') {
            throw new OperandTypeException('Second argument must be integer, third argument must be string');
        }
    
        // Získání indexu a řetězce pro nahrazení
        $index = $symb1->getValue();
        $string = $symb2->getValue();
    
        // Zkontroluje, zda je hodnota proměnné nastavena a je typu string
        if ($var->getValue() === null || !is_string($var->getValue())) {
            throw new OperandTypeException('Variable must be initialized with a string value');
        }

        // Zkontroluje, zda je index v rozsahu řetězce
        $varString = $var->getValue();
        if ($varString === null || $index < 0 || $index >= mb_strlen($varString, 'UTF-8')) {
            throw new StringOperationException('Index out of bounds');
        }
    
        // Získání prvního znaku z řetězce
        $replacementChar = mb_substr($string, 0, 1, 'UTF-8');
    
        // Upravení řetězce a uložení do proměnné v rámci
        $newString = mb_substr($varString, 0, $index, 'UTF-8') . $replacementChar . mb_substr($varString, $index + 1, null, 'UTF-8');
        $frame->addValueToVariable($var, $newString);
    }

    public function type()
    {
        $var = $this->args[0];
        $symb = $this->args[1];

        //Zkontroluje, zda je počet argumentů správný
        if (count($this->args) !== 2) {
            throw new SemanticException('Invalid number of arguments');
        }

        // Zkontroluje, zda je první argument proměnná
        if (!($var instanceof Variable)) {
            throw new OperandTypeException('First argument must be variable');
        }

        // Zkontroluje, zda je druhý argument typu operand
        if (!($symb instanceof Operand)) {
            throw new OperandTypeException('Second argument must be operand');
        }

        // Zkontroluje, zda je proměnná z prvního argumentu inicializovaná v aktuálním rámci nebo v globálním rámci
        $currentFrame = $this->frameManager->getCurrentFrame();
        if (!$currentFrame->isVariableInFrame($var) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($var)) {
            throw new VariableAccessException('Variable is not initialized');
        }
        // Zjistí, zda je proměnná v aktuálním rámci nebo v globálním rámci a uloží do proměnné $frame
        $frame = $currentFrame->isVariableInFrame($var) ? $currentFrame : $this->frameManager->getGlobalFrame();

        // Získá typ symbolu a uloží do proměnné
        $type = $symb->getType();
        
        // Pokud je symbol neinicializovaná proměnná, uloží prázdný řetězec
        if ($type === null) {
            $typeString = '';
        } else {
            // Jinak získá řetězec označující typ symbolu
            switch ($type) {
                case 'int':
                    $typeString = 'int';
                    break;
                case 'bool':
                    $typeString = 'bool';
                    break;
                case 'string':
                    $typeString = 'string';
                    break;
                case 'nil':
                    $typeString = 'nil';
                    break;
                default:
                    throw new OperandTypeException('Invalid type');
            }
        }

        // Uloží typ symbolu do proměnné
        $frame->addValueToVariable($var, $typeString);
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
        // Zkontroluje, zda je počet argumentů správný
        if (count($args) !== 3) {
            throw new SemanticException('Invalid number of arguments');
        }

        // Zkontroluje, zda je první argument proměnná
        if (!($args[0] instanceof Variable)) {
            throw new OperandTypeException('First argument must be variable');
        }

        // Zkontroluje, zda je druhý a třetí argument integer
        if (!($args[1] instanceof Operand) || $args[1]->getType() !== 'int') {
            throw new OperandTypeException('Second argument must be integer');
        }
        if (!($args[2] instanceof Operand) || $args[2]->getType() !== 'int') {
            throw new OperandTypeException('Third argument must be integer');
        }
    }

    public function add()
    {
        // Zkontroluje, zda je proměnná z prvního argumentu inicializovaná v aktuálním rámci
        $currentFrame = $this->frameManager->getCurrentFrame();
        if (!$currentFrame->isVariableInFrame($this->args[0]) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($this->args[0])) {
            throw new VariableAccessException('Variable is not initialized');
        }
        // Zjistí, zda je proměnná v aktuálním rámci nebo v globálním rámci a uloží do proměnné $frame
        $frame = $currentFrame->isVariableInFrame($this->args[0]) ? $currentFrame : $this->frameManager->getGlobalFrame();

        // Přičtení hodnot do proměnné v rámci
        $result = ($this->args[1]->getValue() + $this->args[2]->getValue());
        $frame->addValueToVariable($this->args[0], $result);
    }

    public function sub()
    {
        // Ověření, zda je proměnná z prvního argumentu inicializovaná v aktuálním rámci
        $currentFrame = $this->frameManager->getCurrentFrame();
        if (!$currentFrame->isVariableInFrame($this->args[0]) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($this->args[0])) {
            throw new VariableAccessException('Variable is not initialized');
        }
        // Zjistí, zda je proměnná v aktuálním rámci nebo v globálním rámci a uloží do proměnné $frame
        $frame = $currentFrame->isVariableInFrame($this->args[1]) ? $currentFrame : $this->frameManager->getGlobalFrame();

        // Odečtení hodnot do proměnné v rámci
        $result = ($this->args[1]->getValue() - $this->args[2]->getValue());    
        $frame->addValueToVariable($this->args[0], $result);
    }

    public function mul()
    {
        // Ověření, zda je proměnná z prvního argumentu inicializovaná v aktuálním rámci
        $currentFrame = $this->frameManager->getCurrentFrame();
        if (!$currentFrame->isVariableInFrame($this->args[0]) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($this->args[0])) {
            throw new VariableAccessException('Variable is not initialized');
        }
        // Zjistí, zda je proměnná v aktuálním rámci nebo v globálním rámci a uloží do proměnné $frame
        $frame = $currentFrame->isVariableInFrame($this->args[0]) ? $currentFrame : $this->frameManager->getGlobalFrame();

        // Vynásobení hodnot do proměnné v rámci
        $result = ($this->args[1]->getValue() * $this->args[2]->getValue());
        $frame->addValueToVariable($this->args[0], $result);
    }

    public function idiv()
    {
        // Ověření, zda je proměnná z prvního argumentu inicializovaná v aktuálním rámci
        $currentFrame = $this->frameManager->getCurrentFrame();
        if (!$currentFrame->isVariableInFrame($this->args[0]) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($this->args[0])) {
            throw new VariableAccessException('Variable is not initialized');
        }
        // Zjistí, zda je proměnná v aktuálním rámci nebo v globálním rámci a uloží do proměnné $frame
        $frame = $currentFrame->isVariableInFrame($this->args[0]) ? $currentFrame : $this->frameManager->getGlobalFrame();

        // Ověření, zda je třetí argument různý od nuly
        if ($this->args[2]->getValue() === 0) {
            throw new OperandValueException('Division by zero');
        }
        // Celočíselně podělí hodnoty do proměnné v rámci
        $result = intval($this->args[1]->getValue() / $this->args[2]->getValue());
        $frame->addValueToVariable($this->args[0], $result);
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

        // Zkontroluje, zda je počet argumentů správný
        if (count($args) !== 3) {
            throw new SemanticException('Invalid number of arguments');
        }

        // Zkontroluje, zda je první argument proměnná
        if (!($args[0] instanceof Variable)) {
            throw new OperandTypeException('First argument must be variable');
        }

        // Zkontroluje, zda je druhý a třetí argument , bool nebo string

        if (!($args[1] instanceof Operand) || (($args[1]->getType() === 'int') || ($args[1]->getType() === 'bool') || ($args[1]->getType() === 'string'))) {
            throw new OperandTypeException('Second argument must be integer');
        }
        if (!($args[2] instanceof Operand) || $args[2]->getType() !== 'int') {
            throw new OperandTypeException('Third argument must be integer');
        }
    }

    public function lt()
    {
        $var = $this->args[0]; 
        $symb1 = $this->args[1];
        $symb2 = $this->args[2];

        // Ověření, zda je proměnná z prvního argumentu inicializovaná v aktuálním rámci
        $currentFrame = $this->frameManager->getCurrentFrame();
        if (!$currentFrame->isVariableInFrame($this->args[0]) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($this->args[0])) {
            throw new VariableAccessException('Variable is not initialized');
        }
        // Zjištění, zda je proměnná v aktuálním rámci nebo v globálním rámci a uložení do proměnné $frame
        $frame = $currentFrame->isVariableInFrame($this->args[0]) ? $currentFrame : $this->frameManager->getGlobalFrame();

        // Porovnání hodnot a uložení výsledku do proměnné
        if ($symb1->getType() === 'int' && $symb2->getType() === 'int') {
            $result = intval($symb1->getValue()) < intval($symb2->getValue());
        } elseif ($symb1->getType() === 'string' && $symb2->getType() === 'string') {
            $result = strcasecmp($symb1->getValue(), $symb2->getValue()) < 0;
        } else {
            throw new OperandTypeException('LT operation supports only int, bool or string operands.');
        }

        $frame->addValueToVariable($var, $result);
    }

    public function gt()
    {
        $var = $this->args[0]; 
        $symb1 = $this->args[1];
        $symb2 = $this->args[2];

        // Ověření, zda je proměnná z prvního argumentu inicializovaná v aktuálním rámci
        $currentFrame = $this->frameManager->getCurrentFrame();
        if (!$currentFrame->isVariableInFrame($this->args[0]) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($this->args[0])) {
            throw new VariableAccessException('Variable is not initialized');
        }
        // Zjištění, zda je proměnná v aktuálním rámci nebo v globálním rámci a uložení do proměnné $frame
        $frame = $currentFrame->isVariableInFrame($this->args[0]) ? $currentFrame : $this->frameManager->getGlobalFrame();

        // Porovnání hodnot a uložení výsledku do proměnné
        if ($symb1->getType() === 'int' && $symb2->getType() === 'int') {
            $result = intval($symb1->getValue()) < intval($symb2->getValue());
        } elseif ($symb1->getType() === 'string' && $symb2->getType() === 'string') {
            $result = strcasecmp($symb1->getValue(), $symb2->getValue()) < 0;
        } else {
            throw new OperandTypeException('QT operation supports only int, bool or string operands.');
        }

        $frame->addValueToVariable($var, $result);
    }

    public function eq()
    {
        $var = $this->args[0]; 
        $symb1 = $this->args[1];
        $symb2 = $this->args[2];

        // Ověření, zda je proměnná z prvního argumentu inicializovaná v aktuálním rámci
        $currentFrame = $this->frameManager->getCurrentFrame();
        if (!$currentFrame->isVariableInFrame($this->args[0]) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($this->args[0])) {
            throw new VariableAccessException('Variable is not initialized');
        }
        // Zjištění, zda je proměnná v aktuálním rámci nebo v globálním rámci a uložení do proměnné $frame
        $frame = $currentFrame->isVariableInFrame($this->args[0]) ? $currentFrame : $this->frameManager->getGlobalFrame();

        // Porovnání hodnot a uložení výsledku do proměnné
        if ($symb1->getType() === 'nil') {
            $result = $symb2->getType() === 'nil';
        } elseif ($symb1->getType() === $symb2->getType()) {
            if ($symb1->getType() === 'int') {
                $result = intval($symb1->getValue()) === intval($symb2->getValue());
            } elseif ($symb1->getType() === 'string') {
                $result = strcmp($symb1->getValue(), $symb2->getValue()) === 0;
            } elseif ($symb1->getType() === 'bool') {
                $result = $symb1->getValue() === $symb2->getValue();
            } else {
                throw new OperandTypeException('EQ operation supports only int, string, bool, and nil operands.');
            }
        } else {
            $result = false;
        }

        $frame->addValueToVariable($var, $result);
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
        
        // Zkontroluje, zda je počet argumentů správný
        if (count($args) !== 3 && $opcode !== 'not') {
            throw new SemanticException('Invalid number of arguments');
        }

        // Kontrola pro NOT
        if ($opcode === 'not') {
            if (count($args) !== 2) {
                throw new SemanticException('Invalid number of arguments');
            }
        }

        // Zkontroluje, zda je první argument proměnná
        if (!($args[0] instanceof Variable)) {
            throw new OperandTypeException('First argument must be a variable');
        }

        // Zkontroluje, zda je druhý argument typu bool
        if (!($args[1] instanceof Operand) || $args[1]->getType() !== 'bool') {
            throw new OperandTypeException('Second argument must be bool');
        }

        // Zkontroluje, zda jsou druhý a třetí argument typu bool
        if ($opcode !== 'not') {
            if (!($args[2] instanceof Operand) || $args[2]->getType() !== 'bool') {
                throw new OperandTypeException('Third argument must be bool');
            }
        }
    }

    public function and()
    {
        $var = $this->args[0];
        $symb1 = $this->args[1]->getValue();
        $symb2 = $this->args[2]->getValue();

        $result = $symb1 && $symb2;
        
        // Ověření, zda je proměnná z prvního argumentu inicializovaná v aktuálním rámci
        $currentFrame = $this->frameManager->getCurrentFrame();
        if (!$currentFrame->isVariableInFrame($this->args[0]) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($this->args[0])) {
            throw new VariableAccessException('Variable is not initialized');
        }
        // Zjistí, zda je proměnná v aktuálním rámci nebo v globálním rámci a uloží do proměnné $frame
        $frame = $currentFrame->isVariableInFrame($this->args[0]) ? $currentFrame : $this->frameManager->getGlobalFrame();

        $frame->addValueToVariable($var, $result);
    }

    public function or()
    {
        $var = $this->args[0];
        $symb1 = $this->args[1]->getValue();
        $symb2 = $this->args[2]->getValue();

        $result = $symb1 || $symb2;
        
        // Ověření, zda je proměnná z prvního argumentu inicializovaná v aktuálním rámci
        $currentFrame = $this->frameManager->getCurrentFrame();
        if (!$currentFrame->isVariableInFrame($this->args[0]) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($this->args[0])) {
            throw new VariableAccessException('Variable is not initialized');
        }
        // Zjistí, zda je proměnná v aktuálním rámci nebo v globálním rámci a uloží do proměnné $frame
        $frame = $currentFrame->isVariableInFrame($this->args[0]) ? $currentFrame : $this->frameManager->getGlobalFrame();

        $frame->addValueToVariable($var, $result);
    }

    public function not()
    {
        $var = $this->args[0];
        $symb = $this->args[1]->getValue();

        $result = !$symb;
        
        // Ověření, zda je proměnná z prvního argumentu inicializovaná v aktuálním rámci
        $currentFrame = $this->frameManager->getCurrentFrame();
        if (!$currentFrame->isVariableInFrame($this->args[0]) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($this->args[0])) {
            throw new VariableAccessException('Variable is not initialized');
        }
        // Zjistí, zda je proměnná v aktuálním rámci nebo v globálním rámci a uloží do proměnné $frame
        $frame = $currentFrame->isVariableInFrame($this->args[0]) ? $currentFrame : $this->frameManager->getGlobalFrame();

        $frame->addValueToVariable($var, $result);
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
        $var = $this->args[0];
        $symb = $this->args[1];

        // Zkontroluje, zda je proměnná z prvního argumentu inicializovaná v aktuálním rámci
        $currentFrame = $this->frameManager->getCurrentFrame();
        if (!$currentFrame->isVariableInFrame($var) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($var)) {
            throw new VariableAccessException('Variable is not initialized');
        }
        // Zjistí, zda je proměnná v aktuálním rámci nebo v globálním rámci a uloží do proměnné $frame
        $frame = $currentFrame->isVariableInFrame($this->args[0]) ? $currentFrame : $this->frameManager->getGlobalFrame();

        // Zkontroluje, zda je první argument proměnná
        if (!($var instanceof Variable)) {
            throw new OperandTypeException('First argument must be variable');
        }

        // Zkontroluje, zda je druhý argument integer nebo proměnná
        // Získá hodnotu ze zdroje (operandu nebo proměnné)
        if ($symb instanceof Operand) {
            $value = $symb->getValue();
        } elseif ($symb instanceof Variable) {
            // Zkontroluje, zda je proměnná inicializovaná
            if (!$frame->isVariableInFrame($symb) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($symb)) {
                throw new VariableAccessException('Variable is not initialized');
            }
            // zjisit, zda je proměnná v aktuálním rámci nebo v globálním rámci a uloží do proměnné $frame2
            $frame = $frame->isVariableInFrame($symb) ? $frame : $this->frameManager->getGlobalFrame();
            $value = $frame->getVariable($symb)->getValue();
        } else {
            throw new OperandTypeException('Second argument must be operand or variable');
        }

        // Převede hodnotu integer na string
        $char = mb_chr($value, 'UTF-8');

        // Zkontroluje, zda je hodnota platná
        if ($char === false) {
            throw new OperandValueException('Invalid Unicode ordinal value');
        }

        $frame->addValueToVariable($var, $char);
    }

    public function stri2int()
    {
        $var = $this->args[0];
        $symb1 = $this->args[1];
        $symb2 = $this->args[2];

        // Zkontroluje, zda je proměnná z prvního argumentu inicializovaná v aktuálním rámci
        $currentFrame = $this->frameManager->getCurrentFrame();
        if (!$currentFrame->isVariableInFrame($var) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($var)) {
            throw new VariableAccessException('Variable is not initialized');
        }
        // Zjistí, zda je proměnná v aktuálním rámci nebo v globálním rámci a uloží do proměnné $frame
        $frame = $currentFrame->isVariableInFrame($this->args[0]) ? $currentFrame : $this->frameManager->getGlobalFrame();

        // Zkontroluje, zda jsou argumenty string a integer
        if ($symb1->getType() !== 'string' || $symb2->getType() !== 'int') {
            throw new OperandTypeException('Second argument must be string, third argument must be integer');
        }

        // Získá hodnoty operandů
        $string = $symb1->getValue();
        $index = $symb2->getValue();

        // Zkontroluje, zda je index nezáporný a menší než délka řetězce
        if ($index < 0 || $index >= mb_strlen($string, 'UTF-8')) {
            throw new StringOperationException('Index out of bounds');
        }

        // Získá ordinální hodnotu znaku na daném indexu
        $ordinalValue = mb_ord(mb_substr($string, $index, 1, 'UTF-8'), 'UTF-8');

        $frame->addValueToVariable($var, $ordinalValue);
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
    }

    public function read()
    {

        $var = $this->args[0];
        
        // Zkontroluje, zda je proměnná z prvního argumentu inicializovaná v aktuálním rámci
        $currentFrame = $this->frameManager->getCurrentFrame();
        if (!$currentFrame->isVariableInFrame($var) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($var)) {
            throw new VariableAccessException('Variable is not initialized');
        }
        // Zjistí, zda je proměnná v aktuálním rámci nebo v globálním rámci a uloží do proměnné $frame
        $frame = $currentFrame->isVariableInFrame($this->args[0]) ? $currentFrame : $this->frameManager->getGlobalFrame();

        // načte hodnotu ze vstupu pomocí třídy reader ze settings
        $reader = new Settings();
        $read = $reader->getInputReader();
        if ($this->args[1]->getValue() === 'int') {
            $value = $read->readInt();
        } elseif ($this->args[1]->getValue() === 'string') {
            $value = $read->readString();
        } elseif ($this->args[1]->getValue() === 'bool') {
            $value = $read->readBool();
        } else {
            throw new OperandValueException('Invalid type of input');
        }
        // uloží hodnotu do proměnné v rámci, pokud byla zadána prázdná nebo chybná hodnota, uloží nil
        if ($value === null) {
            $frame->addValueToVariable($var, 'nil');
        } else {
        $frame->addValueToVariable($var, $value);
        }
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
        $writer = new Settings();
        $write = $writer->getStdOutWriter();

        $symb = $this->args[0];
        // Pokud je argument proměnná, vypíše její hodnotu
        if ($symb instanceof Variable) {
            $var = $symb;
        
            // Zkontroluje, zda je proměnná z prvního argumentu inicializovaná v aktuálním rámci
            $currentFrame = $this->frameManager->getCurrentFrame();
            if (!$currentFrame->isVariableInFrame($var) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($var)) {
                throw new VariableAccessException('Variable is not initialized');
            }
            // Zjistí, zda je proměnná v aktuálním rámci, pokud ne tak jestli neni v globálním rámci a uloží do proměnné $frame
            $frame = $currentFrame->isVariableInFrame($var) ? $currentFrame : $this->frameManager->getGlobalFrame();
            $value = $frame->getVariable($var)->getValue();
        } elseif ($symb instanceof Operand) {
            // Pokud je argument operand, vypíše jeho hodnotu
            $value = $symb->getValue();
        } else {
            // Pokud je argument něco jiného, vyhodí výjimku
            throw new OperandValueException('Invalid type of output');
        }
        
        switch ($symb->getType()) {
            case 'var':
                // Přečte hodnotu proměnné a vypíše ji i s novým řádkem 
                $write->writeString($value);
                break;
            case 'int':
                $write->writeInt($value);
                break;
            case 'string':
                $write->writeString($value);
                break;
            case 'bool':
                // Převede hodnotu bool na string a vypíše
                $write->writeString($value ? 'true' : 'false');
                break;
            case 'nil':
                // Pro hodnotu nil vypíše prázdný řetězec
                $write->writeString('');
                break;
            default:
                throw new OperandValueException('Invalid type of output');
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
        // Získání symbolu ze vstupních argumentů
        $symbol = $this->args[0];

        // Získání hodnoty ze symbolu
        $value = $this->getValueFromSymbol($symbol);

        // Výpis hodnoty na standardní chybový výstup (stderr)
        fwrite(STDERR, $value . PHP_EOL);
    }

    public function break()
    {
        // Výpis stavu interpretu na standardní chybový výstup (stderr)
        $interpreterState = $this->getInterpreterState();
        fwrite(STDERR, $interpreterState . PHP_EOL);
    }

    private function getValueFromSymbol($symbol)
    {
        switch ($symbol->getType()) {
            case 'var':
                // Zkontroluje, zda je proměnná z prvního argumentu inicializovaná v aktuálním rámci
                $currentFrame = $this->frameManager->getCurrentFrame();
                if (!$currentFrame->isVariableInFrame($symbol) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($symbol)) {
                    throw new VariableAccessException('Variable is not initialized');
                }
                // Zjistí, zda je proměnná v aktuálním rámci nebo v globálním rámci a uloží do proměnné $frame
                $frame = $currentFrame->isVariableInFrame($symbol) ? $currentFrame : $this->frameManager->getGlobalFrame();
                $value = $frame->getVariableValue($symbol);
            case 'int':
                $value = $symbol->getValue();
            case 'bool':
                $value = $symbol->getValue() ? 'true' : 'false';
            case 'string':
                $value = $symbol->getValue();
            case 'nil':
                $value = '';

            return $value;

            default:
                throw new OperandTypeException('Invalid operand type');
        }
    }

    private function getInterpreterState()
    {
        $string = PHP_EOL . 'Interpreter state: ';
        $string .= 'Order: ' . $this->order . ', ';
        $string .= 'Opcode: ' . $this->opcode . ', ';
        $string .= 'Arguments: ' . implode(', ', $this->args) . ', ';
        $string .= 'Number of executed instructions: ' . $this->frameManager->numberOfExecutedInstructions() . ', ';
        $string .= 'Number of frames: ' . $this->frameManager->getNumberOfFrames() . '.';
        // Vrátí řetězec s informacemi o stavu interpretu
        return $string;
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
    use ArgumentCountChecker;

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
        $var = $this->args[0];
        $source = $this->args[1];

        // Zkontroluje, zda je počet argumentů správný
        if (count($this->args) !== 2) {
            throw new SemanticException('Invalid number of arguments');
        }

        // Zkontroluje, zda je první argument proměnná
        if (!($var instanceof Variable)) {
            throw new OperandTypeException('First argument must be variable');
        }

        // Zkontroluje, zda je proměnná z prvního argumentu inicializovaná v aktuálním rámci nebo v globálním rámci
        $currentFrame = $this->frameManager->getCurrentFrame();
        if (!$currentFrame->isVariableInFrame($var) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($var)) {
            throw new VariableAccessException('Variable is not initialized');
        }
        // Zjistí, zda je proměnná v aktuálním rámci nebo v globálním rámci a uloží do proměnné $frame
        $frame = $currentFrame->isVariableInFrame($var) ? $currentFrame : $this->frameManager->getGlobalFrame();

        // Získá hodnotu ze zdroje (operandu nebo proměnné)
        if ($source instanceof Operand) {
            $value = $source->getValue();
        } elseif ($source instanceof Variable) {
            // Zkontroluje, zda je proměnná inicializovaná
            if (!$frame->isVariableInFrame($source) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($source)) {
                throw new VariableAccessException('Variable is not initialized');
            }
            // zjisit, zda je proměnná v aktuálním rámci nebo v globálním rámci a uloží do proměnné $frame2
            $frame2 = $frame->isVariableInFrame($source) ? $frame : $this->frameManager->getGlobalFrame();
            $value = $frame2->getVariable($source)->getValue();
        } else {
            throw new OperandTypeException('Second argument must be operand or variable');
        }

        // Uloží hodnotu do proměnné
        $frame->addValueToVariable($var, $value);
    }

    public function pushs()
    {
        // Kontrola počtu argumentů
        if (count($this->args) !== 1) {
            throw new SemanticException('Invalid number of arguments');
        }

        $symbol = $this->args[0];

        // Uložení hodnoty na datový zásobník
        $dataS = $this->frameManager->getDataStack();
        $dataS->push($symbol);
    }

    public function pops()
    {
        // Kontrola počtu argumentů
        if (count($this->args) !== 1) {
            throw new SemanticException('Invalid number of arguments');
        }

        // Kontrola, zda je argument proměnná
        if (!($this->args[0] instanceof Variable)) {
            throw new OperandTypeException('First argument must be variable');
        }

        $currentFrame = $this->frameManager->getCurrentFrame();
        // Ověření, zda je proměnná z prvního argumentu inicializovaná
        if (!$currentFrame->isVariableInFrame($this->args[0]) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($this->args[0])) {
            throw new VariableAccessException('Variable is not initialized');
        }
        // Zjistí, zda je proměnná v aktuálním rámci nebo v globálním rámci a uloží do proměnné $frame
        $frame = $currentFrame->isVariableInFrame($this->args[0]) ? $currentFrame : $this->frameManager->getGlobalFrame();

        $var = $this->args[0];

        // Vyjmutí hodnoty z vrcholu datového zásobníku, pokud je prázdný, vyhodí výjimku
        if ($this->frameManager->getDataStack()->isEmpty()) {
            throw new ValueException('Data stack is empty');
        }

        // Vyjmutí hodnoty ze zásobníku a uložení do proměnné
        $symbol = $this->frameManager->getDataStack()->pop();

        // Kontrola, zda byla hodnota vyjmuta úspěšně
        if ($symbol === null) {
            throw new SemanticException('Data stack is empty');
        }

        // Uložení hodnoty do proměnné
        $frame->addValueToVariable($var, $symbol->getValue());
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
    use ArgumentCountChecker;

    public function __construct($opcode, $args, $order, $frameManager)
    {
        parent::__construct($opcode, $args, $order, $frameManager);
        $this->checkArgumentCount($args, 3);
    }

    public function jumpIfEq()
    {    
        // Zkontroluje počet argumentů
        if (count($this->args) !== 3) {
            throw new SemanticException('Invalid number of arguments');
        }
        // Zkontroluje, zda je první argument label
        if (!($this->args[0]->getType() === 'label')) {
            throw new OperandTypeException('First argument must be label');
        }
    
        // Druhý a třetí argument musí být proměnné nebo konstanty
        // Pokud jde o proměnnou, musí být inicializovaná
        $frame1 = $frame2 = null; // Inicializace proměnných pro rámce
        if ($this->args[1]->getType() === 'var') {
            $currentFrame = $this->frameManager->getCurrentFrame();
            if (!$currentFrame->isVariableInFrame($this->args[1]) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($this->args[1])) {
                throw new VariableAccessException('Variable is not initialized');
            }
            // Zjistí, zda je proměnná v aktuálním rámci nebo v globálním rámci a uloží do proměnné $frame1
            $frame1 = $currentFrame->isVariableInFrame($this->args[1]) ? $currentFrame : $this->frameManager->getGlobalFrame();
        }
        if ($this->args[2]->getType() === 'var') {
            $currentFrame = $this->frameManager->getCurrentFrame();
            if (!$currentFrame->isVariableInFrame($this->args[2]) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($this->args[2])) {
                throw new VariableAccessException('Variable is not initialized');
            }
            // Zjistí, zda je proměnná v aktuálním rámci nebo v globálním rámci a uloží do proměnné $frame2
            $frame2 = $currentFrame->isVariableInFrame($this->args[2]) ? $currentFrame : $this->frameManager->getGlobalFrame();
        }
    
        // Porovnání hodnot symbolů
        // Pokud je hodnota proměnné, získá hodnotu proměnné, jinak získá hodnotu operandu
        if ($this->args[1]->getType() === 'var') {
            $value1 = $frame1->getVariable($this->args[1])->getValue();
        } else {
            $value1 = $this->args[1]->getValue();
        }
        if ($this->args[2]->getType() === 'var') {
            $value2 = $frame2->getVariable($this->args[2])->getValue();
        } else {
            $value2 = $this->args[2]->getValue();
        }
    
        // Podmíněný skok na základě rovnosti hodnot
        if ($value1 === $value2) {
            // Vrátí true, pokud dojde k podmíněnému skoku
            return true;
        } else {
            // Vrátí false, pokud podmínka neplatí
            return false;
        }
    }

    public function jumpIfNeq()
    {
        // Zkontroluje počet argumentů
        if (count($this->args) !== 3) {
            throw new SemanticException('Invalid number of arguments');
        }
        // Zkontroluje, zda je první argument label
        if (!($this->args[0]->getType() === 'label')) {
            throw new OperandTypeException('First argument must be label');
        }
    
        // Druhý a třetí argument musí být proměnné nebo konstanty
        // Pokud jde o proměnnou, musí být inicializovaná
        $frame1 = $frame2 = null; // Inicializace proměnných pro rámce
        if ($this->args[1]->getType() === 'var') {
            $currentFrame = $this->frameManager->getCurrentFrame();
            if (!$currentFrame->isVariableInFrame($this->args[1]) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($this->args[1])) {
                throw new VariableAccessException('Variable is not initialized');
            }
            // Zjistí, zda je proměnná v aktuálním rámci nebo v globálním rámci a uloží do proměnné $frame1
            $frame1 = $currentFrame->isVariableInFrame($this->args[1]) ? $currentFrame : $this->frameManager->getGlobalFrame();
        }
        if ($this->args[2]->getType() === 'var') {
            $currentFrame = $this->frameManager->getCurrentFrame();
            if (!$currentFrame->isVariableInFrame($this->args[2]) && !$this->frameManager->getGlobalFrame()->isVariableInFrame($this->args[2])) {
                throw new VariableAccessException('Variable is not initialized');
            }
            // Zjistí, zda je proměnná v aktuálním rámci nebo v globálním rámci a uloží do proměnné $frame2
            $frame2 = $currentFrame->isVariableInFrame($this->args[2]) ? $currentFrame : $this->frameManager->getGlobalFrame();
        }
    
        // Porovnání hodnot symbolů
        // Pokud je hodnota proměnné, získá hodnotu proměnné, jinak získá hodnotu operandu
        if ($this->args[1]->getType() === 'var') {
            $value1 = $frame1->getVariable($this->args[1])->getValue();
        } else {
            $value1 = $this->args[1]->getValue();
        }
        if ($this->args[2]->getType() === 'var') {
            $value2 = $frame2->getVariable($this->args[2])->getValue();
        } else {
            $value2 = $this->args[2]->getValue();
        }
    
        // Podmíněný skok na základě rovnosti hodnot
        if ($value1 !== $value2) {
            // Vrátí true, pokud dojde k podmíněnému skoku
            return true;
        } else {
            // Vrátí false, pokud podmínka neplatí
            return false;
        }
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