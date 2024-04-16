<?php
/**
 * Soubor pro třídu FrameManager, Frame a jejich potomky.
 * @author xhroma15
 * 
 */

namespace IPP\Student;

use IPP\Student\Exceptions\InvalidSourceStructureException;

// Abstraktní třída pro rámce
abstract class Frame {
    protected $frameVars = [];
    protected $frameName;

    public function getFrameName()
    {
        return $this->frameName;
    }

    // Metoda pro přidání proměnné do rámce
    public function addVariable($var)
    {
        $this->frameVars[] = $var;
    }

    // Metoda pro získání proměnných v rámci
    public function getVariables()
    {
        return $this->frameVars;
    }

    public function getVariable($varName)
    {
        foreach ($this->frameVars as $var) {
            if ($var->getName() == $varName->getName()) {
                return $var;
            }
        }

        return null;
    }

    public function isVariableInFrame($varCheck)
    {
        foreach ($this->frameVars as $var) {
            if ($var->getName() == $varCheck->getName()) {
                return true;
            }
        }

        return false;
    }

    public function addValueToVariable($var, $value)
    {
        foreach ($this->frameVars as $frameVar) {
            if ($frameVar->getName() == $var->getName()) {
                $frameVar->setValue($value);
            }
        }
    }
}

// Třída reprezentující globální rámec (GF)
class GlobalFrame extends Frame {
    public function __construct() 
    {
        $this->frameVars = [];
        $this->frameName = "GF";
    }
}

// Třída reprezentující lokální rámec (LF)
class LocalFrame extends Frame {
    public function __construct() 
    {
        $this->frameVars = [];
        $this->frameName = "LF";
    }
}

// Třída reprezentující dočasný rámec (TF)
class TemporaryFrame extends Frame {
    public function __construct() 
    {
        $this->frameVars = [];
        $this->frameName = "TF";
    }

    // Metoda pro přesun proměnných z dočasného rámce do lokálního rámce
    public function moveVariablesToFrame($frame)
    {
        foreach ($this->frameVars as $var) {
            $frame->addVariable($var);
        }
    }
}

class DataStack {
    public $stack;

    public function __construct() {
        $this->stack = [];
    }

    public function push($item) {
        array_push($this->stack, $item);
    }

    public function pop() {
        return array_pop($this->stack);
    }

    // Metoda vracející bool hodnotu, zda je zásobník prázdný
    public function isEmpty(){
        return empty($this->stack);
    }
}

// Třída pro správu rámců
class FrameManager {
    public $frameStack = [];
    public $numberOfInstruction;
    public $dataStack;

    public function __construct() 
    {
        $frameStack[0]= $this->createFrame('GF');
        $this->numberOfInstruction = 0;
        $this->dataStack = new DataStack();
    }

    public function getDataStack()
    {
        return $this->dataStack;
    }

    // Metoda pro vytvoření nového rámce a umístění na zásobník
    public function createFrame($type)
    {
        switch ($type) {
            case 'GF':
                $frame = new GlobalFrame();
                break;
            case 'LF':
                $frame = new LocalFrame();
                break;
            case 'TF':
                $frame = new TemporaryFrame();
                break;
            default:
                throw new InvalidSourceStructureException("Invalid frame type");
        }
        
        $this->frameStack[] = $frame;
    }

    // Metoda pro odstranění rámce ze zásobníku
    public function popFrame()
    {
        array_pop($this->frameStack);
    }

    // Metoda pro získání aktuálního rámce
    public function getCurrentFrame()
    {
        return end($this->frameStack);
    }

    public function isFrameInStack($frameName)
    {
        foreach ($this->frameStack as $frame) {
            if ($frame->getFrameName() == $frameName) {
                return true;
            }
        }

        return false;
    }

    // Metoda pro získání globálního rámce
    public function getGlobalFrame()
    {
        foreach ($this->frameStack as $frame) {
            if ($frame->getFrameName() == 'GF') {
                return $frame;
            }
        }
    }

    public function getNumberOfFrames()
    {
        return count($this->frameStack);
    }

    public function executedInstruction()
    {
        $this->numberOfInstruction++;
    }

    public function numberOfExecutedInstructions()
    {
        $numberOfInstructions = $this->numberOfInstruction;

        return $numberOfInstructions;
    }

}