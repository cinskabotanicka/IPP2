<?php

namespace IPP\Student;

use IPP\Core\AbstractInterpreter;
use IPP\Core\Exception\NotImplementedException;
use IPP\Student\Variable;
use IPP\Student\Operand;
use IPP\Student\Instruction;


class Interpreter extends AbstractInterpreter
{
    public function execute(): int
    {
        // Získání instrukcí
        $instructions = $this->getInstructions();

        // Výpis instrukcí s jejich argumenty
        $this->stdout->writeString("Instructions: " . PHP_EOL);
                
        // Procházení instrukcí
        foreach ($instructions as $instruction) {
            $opcode = $instruction->getOpcode();
            $args = $instruction->getArgs();
            
            // Výpis opcode a argumentů
            $this->stdout->writeString("Opcode: " . $opcode . PHP_EOL);// Vytiskne všechny argumenty postupně
            foreach ($args as $arg) {
                // Získání typu a hodnoty argumentu
                $value = $arg->getValue();

                // Vytisknutí typu a hodnoty argumentu
                $this->stdout->writeString("Argument value: " . $value . PHP_EOL);
            }
            
        }

        $val = $this->input->readString();
        $this->stdout->writeString("stdout");
        $this->stderr->writeString("stderr");
        
        throw new NotImplementedException;
    }

    // Metoda pro získání instrukcí
    private function getInstructions(): array
    {
        // Získání DOM dokumentu z vstupního XML souboru
        $dom = $this->source->getDOMDocument();
        $instructions = [];

        // Projde všechny prvky <instruction> v DOM dokumentu
        foreach ($dom->getElementsByTagName('instruction') as $instructionNode) {
            // Získání opcode z atributu 'opcode'
            $opcode = $instructionNode->getAttribute('opcode');

            // Inicializace pole pro argumenty
            $args = [];

            // Projde všechny potomky 'instruction' a získá jejich argumenty
            foreach ($instructionNode->childNodes as $argNode) {
                // Přeskakuje textové uzly
                if ($argNode->nodeType !== XML_ELEMENT_NODE) {
                    continue;
                }
                
                // Získání typu a hodnoty argumentu z atributu 'type' a obsahu uzlu
                $argType = $argNode->getAttribute('type');
                $argValue = $argNode->nodeValue;
                
                // Vytvoření instance Operandu a přidání do pole
                if ($argType == 'var') {
                    $args[] = new Variable($argNode);
                } else {
                    $args[] = new Operand($argNode);
                }
            }
            
            // Vytvoření instance třídy Instruction s opcode a argumenty a přidání do pole
            $instructions[] = new Instruction($opcode, $args);
        }
        
        
        return $instructions;
    }
}
