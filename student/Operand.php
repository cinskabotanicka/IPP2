<?php
/**
 * File for the Operand class.
 * @author xhroma15
 * 
 */

namespace IPP\Student;

use Exception;

// Třída pro operand
class Operand {    
    protected $type;
    protected $value;

    public function __construct($argNode) {
        // uloží hodnoty operandu
        try {
            $this->type = $argNode->getAttribute("type");
            // uloží hodnotu operandu
            $this->value = $argNode->nodeValue;
        } catch (Exception $e) {
            exit("Error creating operand: " . $e->getMessage());
        }
    }

    public function getValue() {
        return $this->value;
    }
}
