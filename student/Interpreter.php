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
use IPP\Core\ReturnCode;
use IPP\Student\Exceptions\InvalidSourceStructureException;
use IPP\Student\Exceptions\ValueException;

/**
 * Třída Interpreter slouží k interpretaci instrukcí z IPPcode24.
 */
class Interpreter extends AbstractInterpreter
{
    /**
     * @var array Mapování názvů labelů na pořadí instrukcí
     */
    public $labelMap = [];

    /**
     * @var array Zásobník volání
     */
    public $callStack = [];

    /**
     * Provede interpretaci instrukcí.
     * @return int Návratový kód interpretace
     */
    public function execute(): int
    {
        // Získání instrukcí
        $instructions = $this->getInstructions();
        // Seřazení instrukcí podle pořadí
        $sortedInstructions = $this->sortInstructions($instructions);
        // Provedení instrukcí
        $this->executeInstructions($sortedInstructions);
        // Vrátí kód návratu OK
        return ReturnCode::OK;
    }


    public function getAttribute($element, $attributeName) {
        if ($element->hasAttribute($attributeName)) {
            return $element->getAttribute($attributeName);
        } else {
            return null; // or handle the case where the attribute is not found
        }
    }

    /**
     * Získá instrukce z vstupního XML dokumentu.
     * @return array Pole objektů Instruction
     * @throws InvalidSourceStructureException Pokud je struktura vstupního XML neplatná
     */
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
                
                // Získání typu argumentu a jeho hodnoty
                
                $argType = $this->getAttribute($argNode, 'type');
                
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

    // Metoda pro seřazení instrukcí podle pořadí
    private function sortInstructions(array $instructions): array
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
        // Vytvoření pole seřazených instrukcí
        $sortedInstructions = [];
        foreach ($instructionOrder as $index) {
            $sortedInstructions[] = $instructions[$index];
        }

        return $sortedInstructions;
    }

    // Metoda pro provedení instrukcí
    private function executeInstructions(array $instructions): void
    {
        $index = 0; // Počáteční index instrukce

        // Načtení všech labelů do mapy
        foreach ($instructions as $instruction) {
            if ($instruction->getOpcode() === 'label') {
                $labelName = $instruction->getArgs()[0]->getValue();
                $this->labelMap[$labelName] = $index;
            }
            $index++;
        }

        $index = 0; // Počáteční index instrukce
    
        while ($index < count($instructions)) {
            // Získání aktuální instrukce
            $instruction = $instructions[$index];
            
            // Získání opcode a názvu třídy instrukce
            $opcode = $instruction->getOpcode();
            $className = $instruction->getClassName($opcode);
            
            // Zavolání metody pro provedení instrukce
            $specificMethod = $instruction->getSpecificMethod($className);
            $specificMethod();
    
            // Zpracování instrukce LABEL
            if ($instruction->getOpcode() === 'label') {
                // Přeskočení instrukce
                $index++;
                continue;
            }
    
            // Zpracování instrukce JUMP
            if ($instruction->getOpcode() === 'jump') {
                $labelName = $instruction->getArgs()[0]->getValue();
                if (!isset($this->labelMap[$labelName])) {
                    throw new InvalidSourceStructureException("Label not found");
                }
                // Nastavení indexu instrukce, na kterou se má skočit
                $index = $this->labelMap[$labelName];
            }
    
            // Zpracování instrukce JUMPIFEQ
            if ($instruction->getOpcode() === 'jumpifeq') {
                $labelName = $instruction->getArgs()[0]->getValue();
                if (!isset($this->labelMap[$labelName])) {
                    throw new InvalidSourceStructureException("Label not found");
                }
                // Pokud je podmínka splněna, změní index na index instrukce, na kterou se má skočit
                if ($specificMethod()) {
                    $index = $this->labelMap[$labelName];
                    continue; // Opakuj smyčku s novým indexem
                }
            }
    
            // Zpracování instrukce JUMPIFNEQ
            if ($instruction->getOpcode() === 'jumpifneq') {
                $labelName = $instruction->getArgs()[0]->getValue();
                if (!isset($this->labelMap[$labelName])) {
                    throw new InvalidSourceStructureException("Label not found");
                }
                // Pokud je podmínka splněna, změní index na index instrukce, na kterou se má skočit
                if ($specificMethod()) {
                    $index = $this->labelMap[$labelName];
                    continue; // Opakuj smyčku s novým indexem
                }
            }
    
            // Zpracování instrukce CALL
            if ($instruction->getOpcode() === 'call') {
                $labelName = $instruction->getArgs()[0]->getValue();
                if (!isset($this->labelMap[$labelName])) {
                    throw new InvalidSourceStructureException("Label not found");
                }
                // Uložení aktuální pozice do zásobníku volání
                array_push($this->callStack, $index + 1);
                // Nastavení indexu na index instrukce, na kterou se má skočit
                $index = $this->labelMap[$labelName];
                continue; // Opakuj smyčku s novým indexem
            }
    
            // Zpracování instrukce RETURN
            if ($instruction->getOpcode() === 'return') {
                // Pokud je zásobník volání prázdný, vyvoláme chybu 56
                if (empty($this->callStack)) {
                    throw new ValueException("Return from empty call stack");
                }
                // Vyjmutí pozice ze zásobníku volání
                $returnIndex = array_pop($this->callStack);
                // Nastavení indexu na pozici uloženou v zásobníku volání
                $index = $returnIndex;
                continue; // Opakuj smyčku s novým indexem
            }
    
            // Zvýšení indexu pro další instrukci
            $index++;
        }
    }

}
