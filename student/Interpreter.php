<?php
/**
 * Soubor pro třídu Interpreter.
 * @author xhroma15
 * 
 */

namespace IPP\Student;

use IPP\Core\AbstractInterpreter;
use IPP\Student\Variable;
use IPP\Student\Operand;
use IPP\Student\InstructionFactory;
use IPP\Student\Instruction;  
use IPP\Core\ReturnCode;
use IPP\Student\Exceptions\InvalidSourceStructureException;

class Interpreter extends AbstractInterpreter
{
    public function execute(): int
    {
        // Získání instrukcí
        $instructions = $this->getInstructions();
        // Provedení instrukcí
        $this->executeInstructions($instructions);
        // vrátí kód návratu OK
        return ReturnCode::OK;
    }

    // Metoda pro získání instrukcí
    private function getInstructions(): array
    {
        // Získání DOM dokumentu z vstupního XML souboru
        $dom = $this->source->getDOMDocument();
        $instructions = [];
        $instructionFactory = new InstructionFactory();

        // Projde všechny prvky <instruction> v DOM dokumentu
        foreach ($dom->getElementsByTagName('instruction') as $instructionNode) {
            // Získání opcode z atributu 'opcode'
            $opcode = $instructionNode->getAttribute('opcode');
            $order = $instructionNode->getAttribute('order');

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
            $instructions[] = $instructionFactory->createInstruction($opcode, $args, $order);
        }
        
        return $instructions;

    }

    // Metoda pro provedení instrukcí
    private function executeInstructions(array $instructions): void
    {
        // Získání správného pořadí instrukcí na základě atributu 'order'
        $instructionOrder = [];

        foreach ($instructions as $index => $instruction) {
            $order = $instruction->getOrder(); 
            if (isset($instructionOrder[$order - 1])) {
                throw new InvalidSourceStructureException("Multiple instruction order number");
            }
            $instructionOrder[$order - 1] = $index;
        }

        // Seřazení instrukcí podle pořadí
        ksort($instructionOrder);

        // Provedení instrukcí v pořadí získaném z atributu 'order'
        foreach ($instructionOrder as $index) {

            $opcode = $instructions[$index]->getOpcode();
            $className = $instructions[$index]->getClassName($opcode);
            // Zavolání metody pro provedení instrukce
            $specificMethod = $instructions[$index]->getSpecificMethod($className);
            $specificMethod();
        }
    }

}
