<?php
/**
 * Soubor pro třídu Operand.
 * @author xhroma15
 * 
 */
namespace IPP\Student;

use Exception;
use IPP\Student\Exceptions\OperandValueException;
use IPP\Student\Exceptions\OperandTypeException;

// Třída pro operand
class Operand {    
    protected $type;
    protected $value;

    public function __construct($argNode) {
        // uloží hodnoty operandu
        try {
            $this->type = $argNode->getAttribute("type");
            $nodeValue = $argNode->nodeValue;

            switch ($this->type) {
                case "int":
                    // Pokud je typ int, ověří, zda je hodnota celé číslo
                    if (!ctype_digit($nodeValue)) {
                        throw new OperandValueException("Operand value does not match the type");
                    }
                    break;
                case "bool":
                    // Pokud je typ bool, ověří, zda je hodnota true nebo false
                    if ($nodeValue !== "true" && $nodeValue !== "false") {
                        throw new OperandValueException("Operand value does not match the type");
                    }
                    break;
                case "string":
                    // Pokud je typ string, nemusí se dělat žádná další kontrola
                    break;
                case "nil":
                    // Pokud je typ nil, ověří, zda je hodnota "nil"
                    if ($nodeValue !== "nil") {
                        throw new OperandValueException("Operand value does not match the type");
                    }
                    break;
                default:
                    // Pokud je typ neznámý, vyvolá chybu
                    throw new OperandValueException("Unknown operand type");
            }

            // Uloží hodnotu operandu
            $this->value = $nodeValue;
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
