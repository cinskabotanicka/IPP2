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
use IPP\Student\Exceptions\StringOperationException;

// Třída pro operand
class Operand {    
    protected $type;
    protected $value;

    public function __construct($argNode) {
        // uloží hodnoty operandu
        try {
            $this->type = $argNode->getAttribute("type");
            $nodeValue = trim($argNode->nodeValue);

            switch ($this->type) {
                case "type":
                    // Pokud je typ type, ověří, zda je hodnota typ (int, bool, string, nil, label, type, var )
                    if ($nodeValue !== "int" && $nodeValue !== "bool" && $nodeValue !== "string" && $nodeValue !== "nil" && $nodeValue !== "label" && $nodeValue !== "type" && $nodeValue !== "var") {
                        throw new OperandTypeException("Operand type is not valid");
                    }
                    break;
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
                case "string":                    // Zpracování řetězců s escape sekvencemi
                    $processedValue = '';
                    $length = strlen($nodeValue);
                    $i = 0;
                    while ($i < $length) {
                        $char = $nodeValue[$i];
                        if ($char === '\\') {
                            // Ošetření escape sekvence
                            if ($i + 4 <= $length && $nodeValue[$i + 1] === '0' && $nodeValue[$i + 2] === '3' && $nodeValue[$i + 3] === '2') {
                                // Dekóduje escape sekvenci pro mezeru
                                $processedValue .= ' ';
                                $i += 4;
                            } elseif ($i + 1 < $length && is_numeric($nodeValue[$i + 1]) && is_numeric($nodeValue[$i + 2]) && is_numeric($nodeValue[$i + 3])) {
                                // Převede escape sekvenci na ASCII hodnotu
                                $ascii = (int) substr($nodeValue, $i + 1, 3);
                                $processedValue .= chr($ascii);
                                $i += 4; // Přeskočíme escape sekvenci
                            } else {
                                // Neplatná escape sekvence
                                throw new StringOperationException("Invalid escape sequence at position $i");
                            }
                        } else {
                            // Regulerní písmeno
                            $processedValue .= $char;
                            $i++;
                        }
                    }
            
                    $nodeValue = $processedValue;
                    break;
                case "nil":
                    // Pokud je typ nil, ověří, zda je hodnota "nil"
                    if ($nodeValue !== "nil") {
                        throw new OperandValueException("Operand value does not match the type");
                    }
                    break;
                case "label":
                    // Pokud je typ label, nemusí se dělat žádná další kontrola
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
