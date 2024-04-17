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
class Variable
{
    protected $type;
    protected $value;
    protected $frame;
    protected $name;
    protected $valueType;

    public function __construct($argNode)
    {
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
    protected function frame($name): string
    {
        preg_match('/.*(?=@)/', $name, $matches);
        return $matches[0];
    }

    // Získá název
    protected function name($name): string
    {
        preg_match('/(?<=@).*/', $name, $matches);
        return $matches[0];
    }

    // Zkontroluje název
    protected function checkName($name): void
    {
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

    public function getFrame(): mixed
    {
        return $this->frame;
    }

    public function getType(): mixed
    {
        return $this->type;
    }

    public function getName(): mixed
    {
        return $this->name;
    }

    public function getValueType(): mixed
    {
        return $this->valueType;
    }

    // Vrátí hondotu, má návrátový typ mixed, protože hodnota může být libovolného typu
    public function getValue(): mixed
    {
        return $this->value;
    }

    public function setValue($setValue, $type): void
    {
        if ($type === "int") {
            $setValue = (int) $setValue;
            $this->valueType = $type;
        } elseif ($type === "string" | $type === "label" | $type === "type") {
            $setValue = (string) $setValue;
            $this->valueType = $type;
        } elseif ($type === "bool") {
            $setValue = (bool) $setValue;
            $this->valueType = $type;
        } elseif ($type === "nil") {
            $this->valueType = $type;
            if ($setValue !== "nil") {
                throw new OperandValueException("Operand value does not match the type");
            }
        } else {
            throw new OperandValueException("Unknown operand type");
        }
        $this->value = $setValue;
    }
}
