<?php
/**
 * Soubor pro třídu Variable.
 * @author xhroma15
 * 
 */

namespace IPP\Student;

use Exception;
use IPP\Student\Exceptions\OperandValueException;
use IPP\Student\Exceptions\StringOperationException;

// Podtřída pro proměnnou
class Variable {
    protected $type;
    protected $value;
    protected $frame;
    protected $name;

    public function __construct($argNode) {
        // uloží hodnoty proměnné
        try {
            $this->type = $argNode->getAttribute("type");
            preg_match('/\s*(?P<frame>[^@\s]*)/', $argNode->nodeValue, $matches);
            $this->frame = $matches['frame'];
            preg_match('/(?<=@).*/', $argNode->nodeValue, $match);
            $this->name = $match[0];
            $this->checkName($this->name);
            $this->value = null;
        } catch (Exception $e) {
            exit("Error creating variable: " . $e->getMessage());
        }
    }

    // Získá rámec
    protected function frame($name) : string{
        preg_match('/.*(?=@)/', $name, $matches);
        return $matches[0];
    }

    // Získá název
    protected function name($name) : string{
        preg_match('/(?<=@).*/', $name, $matches);
        return $matches[0];
    }

    // Zkontroluje název
    protected function checkName($name) : void{
        $i = 0;
        $possibleChars = ["_", "-", "$", "&", "%", "*", "!", "?"];
        $nameChars = str_split($name);
        
        foreach ($nameChars as $char) {
            if (ctype_alpha($char)) {
                $i++;
            } elseif (ctype_digit($char)) {
                if ($i == 0) {
                    throw new Exception("Name cannot start with number");
                }
                $i++;
            } elseif (in_array($char, $possibleChars)) {
                $i++;
            } else {
                throw new Exception("Invalid name");
            }
        }
    }

    public function getFrame() : mixed {
        return $this->frame;
    }

    public function getType() : mixed {
        return $this->type;
    }

    public function getName() : mixed {
        return $this->name;
    }

    // Vrátí hondotu, má návrátový typ mixed, protože hodnota může být libovolného typu
    public function getValue() : mixed {
        return $this->value;
    }

    public function setValue($setValue) : void {

        if ($this->type === 'string') {
            // Zkontroluje, zda je hodnota typu string
            if (!is_string($setValue)) {
                throw new OperandValueException("Invalid value type for string variable");
            }
            
            // Zpracuje escape sekvence 
            $processedValue = '';
            $length = strlen($setValue);
            $i = 0;
            while ($i < $length) {
                $char = $setValue[$i];
                if ($char === '\\') {
                    // Ošetření escape sekvence
                    if ($i + 4 <= $length && $setValue[$i + 1] === '0' && $setValue[$i + 2] === '3' && $setValue[$i + 3] === '2') {
                        // Dekóduje escape sekvenci pro mezeru
                        $processedValue .= ' ';
                        $i += 4;
                    } elseif ($i + 1 < $length) {
                        // Převede escape sekvenci na ASCII hodnotu
                        $ascii = (int) substr($setValue, $i + 1, 3);
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
    
            $this->value = $processedValue;
        } else {
            // Pro jinné typy rovnou vytvoří hodnotu
            $this->value = $setValue;
        }
    }
}