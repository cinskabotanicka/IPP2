<?php
/**
 * Soubor pro třídu Operand.
 * @author xhroma15
 * 
 */

namespace IPP\Student;

use Exception;
use IPP\Student\OperandValueException;

// Třída pro operand
class Operand {    
    protected $type;
    protected $value;

    public function __construct($argNode) {
        // uloží hodnoty operandu
        try {
            $this->type = $argNode->getAttribute("type");
            // uloží hodnotu operandu
            // pokud je to int, zkountoluje, zda je i hodnota int
            if ($this->type == "int") {
                if (!is_numeric($argNode->nodeValue)) {
                    throw new OperandValueException("Operand value does not match the type");
                }
            }
            // pokuď je to bool, zkontroluje, zda je i hodnota bool
            if ($this->type == "bool") {
                if ($argNode->nodeValue != "true" && $argNode->nodeValue != "false") {
                    throw new OperandValueException("Operand value does not match the type");
                }
            }
            // pokud je to string, zkontroluje, zda je i hodnota string
            if ($this->type == "string") {
                if (!is_string($argNode->nodeValue)) {
                    throw new OperandValueException("Operand value does not match the type");
                }
            }
            // pokud je to nil, zkontroluje, zda je i hodnota nil
            if ($this->type == "nil") {
                if ($argNode->nodeValue != "nil") {
                    throw new OperandValueException("Operand value does not match the type");
                }
            }

            $this->value = $argNode->nodeValue;
        }
        catch (Exception $e) {
            throw new OperandValueException("Operand value does not match the type");
        }
    }

    public function getValue() {
        return $this->value;
    }

    public function getType() {
        return $this->type;
    }
}
