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
use IPP\Student\Exceptions\ValueException;

class Interpreter extends AbstractInterpreter
{

    public $labelMap = []; // Mapování názvů labelů na pořadí instrukcí
    public $callStack = []; // Zásobník volání

    public function execute(): int
    {
        // Získání instrukcí
        $instructions = $this->getInstructions();
        // Seřazení instrukcí podle pořadí
        $sortedInstructions = $this->sortInstructions($instructions);
        // Provedení instrukcí
        $this->executeInstructions($sortedInstructions);
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
        foreach ($instructions as $index => $instruction) {
            // Získání opcode a názvu třídy instrukce
            $opcode = $instructions[$index]->getOpcode();
            $className = $instructions[$index]->getClassName($opcode);
            // Zavolání metody pro provedení instrukce
            $specificMethod = $instructions[$index]->getSpecificMethod($className);
            $specificMethod();

            // Zpracování instrukce LABEL
            if ($instruction->getOpcode() == 'LABEL') {
                $labelName = $instruction->getArgs()[0]->getValue();
                $this->labelMap[$labelName] = $index;
            }

            // Zpracování instrukce JUMP
            if ($instruction->getOpcode() == 'JUMP') {
                $labelName = $instruction->getArgs()[0]->getValue();
                if (!isset($this->labelMap[$labelName])) {
                    throw new InvalidSourceStructureException("Label not found");
                }
                // Nastavení indexu instrukce, na kterou se má skočit
                $instructions[$index]->setJumpIndex($this->labelMap[$labelName]);
                // Změna indexu na index instrukce, na kterou se má skočit
                $index = $this->labelMap[$labelName];
            }

            // Zpracování instrukce JUMPIFEQ
            if ($instruction->getOpcode() == 'JUMPIFEQ') {
                $labelName = $instruction->getArgs()[0]->getValue();
                if (!isset($this->labelMap[$labelName])) {
                    throw new InvalidSourceStructureException("Label not found");
                }
                $instructions[$index]->setJumpIndex($this->labelMap[$labelName]);
                // Pokud je podmínka splněna, změní index na index instrukce, na kterou se má skočit
                if ($specificMethod()) {
                    $index = $this->labelMap[$labelName];
                }
            }

            // Zpracování instrukce JUMPIFNEQ
            if ($instruction->getOpcode() == 'JUMPIFNEQ') {
                $labelName = $instruction->getArgs()[0]->getValue();
                if (!isset($this->labelMap[$labelName])) {
                    throw new InvalidSourceStructureException("Label not found");
                }
                $instructions[$index]->setJumpIndex($this->labelMap[$labelName]);
                // Pokud je podmínka splněna, změní index na index instrukce, na kterou se má skočit
                if ($specificMethod()) {
                    $index = $this->labelMap[$labelName];
                }
            }

            // Zpracování instrukce CALL
            if ($instruction->getOpcode() == 'CALL') {
                $labelName = $instruction->getArgs()[0]->getValue();
                if (!isset($this->labelMap[$labelName])) {
                    throw new InvalidSourceStructureException("Label not found");
                }
                // Uložení aktuální pozice do zásobníku volání
                array_push($this->callStack, $index + 1);
                // Nastavení indexu na index instrukce, na kterou se má skočit
                $index = $this->labelMap[$labelName];
            }

            // Zpracování instrukce RETURN
            if ($instruction->getOpcode() == 'RETURN') {
                // Pokud je zásobník volání prázdný, vyvolá chybu
                if (empty($this->callStack)) {
                    throw new ValueException("Return from empty call stack");
                }
                // Vyjmutí pozice ze zásobníku volání
                $returnIndex = array_pop($this->callStack);
                // Nastavení indexu na pozici uloženou v zásobníku volání
                $index = $returnIndex;
            }
        }
    }

}
