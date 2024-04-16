<?php
/**
 * Soubor pro třídu Variable.
 * @author xhroma15
 * 
 */

namespace IPP\Student;

use Exception;

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
    protected function frame($name) {
        preg_match('/.*(?=@)/', $name, $matches);
        return $matches[0];
    }

    // Získá název
    protected function name($name) {
        preg_match('/(?<=@).*/', $name, $matches);
        return $matches[0];
    }

    // Zkontroluje název
    protected function checkName($name) {
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

    public function getFrame() {
        return $this->frame;
    }

    public function getType() {
        return $this->type;
    }

    public function getName() {
        return $this->name;
    }

    public function getValue() {
        return $this->value;
    }

    public function setValue($value) {
        $this->value = $value;
    }
}