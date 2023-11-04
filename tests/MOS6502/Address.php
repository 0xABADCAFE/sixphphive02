<?php

/**
 *   ___ _     ___ _  _ ___ _    _          __ ___
 *  / __(_)_ _| _ \ || | _ \ |_ (_)_ _____ /  \_  )
 *  \__ \ \ \ /  _/ __ |  _/ ' \| \ V / -_) () / /
 *  |___/_/_\_\_| |_||_|_| |_||_|_|\_/\___|\__/___|
 *
 *   - The world's least sensible 6502 emulator -
 */

declare(strict_types=1);

namespace ABadCafe\SixPHPhive02\Test\MOS6502;

use ABadCafe\SixPHPhive02\Test\ITest;
use ABadCafe\SixPHPhive02\Test\TTest;
use ABadCafe\SixPHPhive02\Processor\MOS6502Processor;


/**
 * Tests the addressing modes for the MOS6502Processor.
 *
 * This is not a true unit test. It extends the processor class, disabling the original constructor so
 * that it can directly manipulate instances of the processor under test conditions. However, it does
 * carry out the same essential task of testing units of code.
 */
class Address extends MOS6502Processor implements ITest {

    use TTest;

    public function __construct() {
        // Disable original constructor
    }

    public function testZeroPage(): void {
        $iInstructionPC = 0x1234;
        $iExpectAddress = 0x23;
        $iExpectValue   = 0x57;

        // Set up the memory
        $oMemory = $this->createMockMemory([
            $iInstructionPC + 1 => $iExpectAddress, // PC+1 is the zeropage address
            $iExpectAddress     => $iExpectValue,   // value at the zero page address
        ]);

        $oProcessor = new MOS6502Processor($oMemory);
        $oProcessor->iProgramCounter = $iInstructionPC;

        // Evaluate the addressing mode
        $iGotAddress = $oProcessor->addrZeroPageByte();

        $this->assert(
            $iGotAddress === $iExpectAddress,
            sprintf(
                'Failed asserting effective address $%04X matches $%04X',
                $iGotAddress,
                $iExpectAddress
            )
        );

        // Compare the value
        $iReadValue = $oMemory->readByte($iGotAddress);
        $this->assert(
            $iReadValue === $iExpectValue,
            sprintf(
                'Failed asserting value at effective address $%04X is $%02X, got $%02X',
                $iGotAddress,
                $iExpectValue,
                $iReadValue
            )
        );
    }

    public function testZeroPageX() {
        $iInstructionPC = 0x1234;
        $iBaseAddress   = 0x23;

        // Iterate over all index values, including for out of range indexes (assume modulo wrap)
        for ($iXIndex = -128; $iXIndex < 384; ++$iXIndex) {

            // Expected address wraps in the zero page
            $iExpectAddress = ($iBaseAddress + $iXIndex) & 0xFF;
            $iExpectValue   = (0x57 ^ $iXIndex) & 0xFF;

            $oMemory = $this->createMockMemory([
                $iInstructionPC + 1 => $iBaseAddress,
                $iExpectAddress     => $iExpectValue,
            ]);

            $oProcessor = new MOS6502Processor($oMemory);
            $oProcessor->iProgramCounter = $iInstructionPC;
            $oProcessor->iXIndex = $iXIndex;

            $iGotAddress = $oProcessor->addrZeroPageXByte();

            $this->assert(
                $iGotAddress === $iExpectAddress,
                sprintf(
                    'Failed asserting effective address $%04X matches $%04X',
                    $iGotAddress,
                    $iExpectAddress
                )
            );

            $iReadValue = $oMemory->readByte($iGotAddress);
            $this->assert(
                $iReadValue === $iExpectValue,
                sprintf(
                    'Failed asserting value at effective address $%04X is $%02X, got $%02X',
                    $iGotAddress,
                    $iExpectValue,
                    $iReadValue
                )
            );
        }
    }

    public function testZeroPageY() {
        $iInstructionPC = 0x1234;
        $iBaseAddress   = 0x23;

        for ($iYIndex = -128; $iYIndex <384; ++$iYIndex) {

            // Expected address wraps in the zero page
            $iExpectAddress = ($iBaseAddress + $iYIndex) & 0xFF;
            $iExpectValue   = (0x57 ^ $iYIndex) & 0xFF;

            $oMemory = $this->createMockMemory([
                $iInstructionPC + 1 => $iBaseAddress,
                $iExpectAddress     => $iExpectValue,
            ]);

            $oProcessor = new MOS6502Processor($oMemory);
            $oProcessor->iProgramCounter = $iInstructionPC;
            $oProcessor->iYIndex = $iYIndex;

            $iGotAddress = $oProcessor->addrZeroPageYByte();

            $this->assert(
                $iGotAddress === $iExpectAddress,
                sprintf(
                    'Failed asserting effective address $%04X matches $%04X',
                    $iGotAddress,
                    $iExpectAddress
                )
            );

            $iReadValue = $oMemory->readByte($iGotAddress);
            $this->assert(
                $iReadValue === $iExpectValue,
                sprintf(
                    'Failed asserting value at effective address $%04X is $%02X, got $%02X',
                    $iGotAddress,
                    $iExpectValue,
                    $iReadValue
                )
            );
        }
    }

    public function testAbsolute() {
        $iInstructionPC = 0x1234;
        $iExpectAddress = 0x2357;
        $iExpectValue   = 0x11;

        $oMemory = $this->createMockMemory([
            $iInstructionPC + 1 => $iExpectAddress & 0xFF, // Lo Byte
            $iInstructionPC + 2 => $iExpectAddress >> 8,   // Hi Byte
            $iExpectAddress     => $iExpectValue,
        ]);

        $oProcessor = new MOS6502Processor($oMemory);
        $oProcessor->iProgramCounter = $iInstructionPC;
        $iGotAddress = $oProcessor->addrAbsoluteByte();

        $this->assert(
            $iGotAddress === $iExpectAddress,
            sprintf(
                'Failed asserting effective address $%04X matches $%04X',
                $iGotAddress,
                $iExpectAddress
            )
        );

        $iReadValue = $oMemory->readByte($iGotAddress);
        $this->assert(
            $iReadValue === $iExpectValue,
            sprintf(
                'Failed asserting value at effective address $%04X is $%02X, got $%02X',
                $iGotAddress,
                $iExpectValue,
                $iReadValue
            )
        );
    }

    public function testAbsoluteX() {
        $iInstructionPC = 0x1234;
        $iBaseAddress   = 0x2357;

        // Iterate over all index values, including for out of range indexes (assume modulo wrap)
        for ($iXIndex = -128; $iXIndex < 384; ++$iXIndex) {

            // Expected address wraps in the zero page
            $iExpectAddress = ($iBaseAddress + ($iXIndex & 0xFF)) & 0xFFFF;
            $iExpectValue   = $iXIndex & 0xFF;

            $oMemory = $this->createMockMemory([
                $iInstructionPC + 1 => $iBaseAddress & 0xFF,
                $iInstructionPC + 2 => $iBaseAddress >> 8,
                $iExpectAddress     => $iExpectValue,
            ]);

            $oProcessor = new MOS6502Processor($oMemory);
            $oProcessor->iProgramCounter = $iInstructionPC;
            $oProcessor->iXIndex = $iXIndex;

            $iGotAddress = $oProcessor->addrAbsoluteXByte();

            $this->assert(
                $iGotAddress === $iExpectAddress,
                sprintf(
                    'Failed asserting effective address $%04X matches $%04X',
                    $iGotAddress,
                    $iExpectAddress
                )
            );

            $iReadValue = $oMemory->readByte($iGotAddress);
            $this->assert(
                $iReadValue === $iExpectValue,
                sprintf(
                    'Failed asserting value at effective address $%04X is $%02X, got $%02X',
                    $iGotAddress,
                    $iExpectValue,
                    $iReadValue
                )
            );
        }
    }

    public function testAbsoluteY() {
        $iInstructionPC = 0x1234;
        $iBaseAddress   = 0x2357;

        // Iterate over all index values, including for out of range indexes (assume modulo wrap)
        for ($iYIndex = -128; $iYIndex < 384; ++$iYIndex) {

            // Expected address wraps in the zero page
            $iExpectAddress = ($iBaseAddress + ($iYIndex & 0xFF)) & 0xFFFF;
            $iExpectValue   = $iYIndex & 0xFF;

            $oMemory = $this->createMockMemory([
                $iInstructionPC + 1 => $iBaseAddress & 0xFF,
                $iInstructionPC + 2 => $iBaseAddress >> 8,
                $iExpectAddress     => $iExpectValue,
            ]);

            $oProcessor = new MOS6502Processor($oMemory);
            $oProcessor->iProgramCounter = $iInstructionPC;
            $oProcessor->iYIndex = $iYIndex;

            $iGotAddress = $oProcessor->addrAbsoluteYByte();
            $this->assert(
                $iGotAddress === $iExpectAddress,
                sprintf(
                    'Failed asserting effective address $%04X matches $%04X',
                    $iGotAddress,
                    $iExpectAddress
                )
            );

            $iReadValue = $oMemory->readByte($iGotAddress);
            $this->assert(
                $iReadValue === $iExpectValue,
                sprintf(
                    'Failed asserting value at effective address $%04X is $%02X, got $%02X',
                    $iGotAddress,
                    $iExpectValue,
                    $iReadValue
                )
            );
        }
    }

    public function testPreIndexZeroPageXByte() {

        $iInstructionPC = 0x1234;
        $iBaseAddress   = 0x57;
        $iExpectEven    = 0x24;
        $iExpectOdd     = 0x13;

        $aMemory = [
            $iInstructionPC + 1 => $iBaseAddress
        ];

        // Fill the zero page with an obvious pattern:
        //    Even zeropage 16-bit accesses will get the word 0xB0A0
        //    Odd zeropage 16-bit accesses will get the word 0xA0B0
        for ($i = 0; $i < 256; $i += 2) {
            $aMemory[$i]   = 0xA0;
            $aMemory[$i+1] = 0xB0;
        }

        // If the zero page address hits 255, the high byte will be 256, so we need to set that
        $aMemory[256] = 0xA0;

        // Set the referenced values
        $aMemory[0xA0B0] = $iExpectOdd;
        $aMemory[0xB0A0] = $iExpectEven;

        $oMemory = $this->createMockMemory($aMemory);

        $oProcessor = new MOS6502Processor($oMemory);

        $this->assert(
            $oProcessor->readWord(0) == 0xB0A0,
            sprintf("16-bit value at %02X is %04X", 0, 0xB0A0)
        );

        $this->assert(
            $oProcessor->readWord(1) == 0xA0B0,
            sprintf("16-bit value at %02X is %04X", 0, 0xA0B0)
        );

        // Iterate over all index values, including for out of range indexes (assume modulo wrap)
        for ($iXIndex = -128; $iXIndex < 384; ++$iXIndex) {

            $iExpectZeroPageAccess = (($iBaseAddress + $iXIndex) & 0xFF);
            $bIsOddZeroPage        = $iExpectZeroPageAccess & 1;

            // Expected address wraps in the zero page
            $iExpectAddress = $bIsOddZeroPage ? 0xA0B0 : 0xB0A0;
            $iExpectValue   = $bIsOddZeroPage ? $iExpectOdd : $iExpectEven;

            $oProcessor->iProgramCounter = $iInstructionPC;
            $oProcessor->iXIndex = $iXIndex;

            $iGotAddress = $oProcessor->addrPreIndexZeroPageXByte();

            $this->assert(
                $iGotAddress === $iExpectAddress,
                sprintf(
                    'Failed asserting effective address $%04X matches $%04X',
                    $iGotAddress,
                    $iExpectAddress
                )
            );

            $iReadValue = $oMemory->readByte($iGotAddress);
            $this->assert(
                $iReadValue === $iExpectValue,
                sprintf(
                    'Failed asserting value at effective address $%04X is $%02X, got $%02X',
                    $iGotAddress,
                    $iExpectValue,
                    $iReadValue
                )
            );
        }
    }

    public function testPostIndexZeroPageYByte() {
        $iInstructionPC = 0x1234;
        $iBaseAddress = 0x3456;
        $iZeroPageLoc = 0x63;

        $aMemory = [
            $iInstructionPC + 1 => $iZeroPageLoc,
            $iZeroPageLoc       => $iBaseAddress & 0xFF,
            $iZeroPageLoc + 1   => $iBaseAddress >> 8,
        ];

        for ($i = 0; $i < 256; ++$i) {
            $aMemory[$i + $iBaseAddress] = ($i ^ $iZeroPageLoc) & 0xFF;
        }

        $oMemory = $this->createMockMemory($aMemory);
        $oProcessor = new MOS6502Processor($oMemory);

        // Iterate over all index values, including for out of range indexes (assume modulo wrap)
        for ($iYIndex = -128; $iYIndex < 384; ++$iYIndex) {

            $oProcessor->iProgramCounter = $iInstructionPC;
            $oProcessor->iYIndex = $iYIndex;

            $iExpectAddress = $iBaseAddress + ($iYIndex & 0xFF);
            $iExpectValue   = ($iYIndex ^ $iZeroPageLoc) & 0xFF;

            $iGotAddress = $oProcessor->addrPostIndexZeroPageYByte();

            $this->assert(
                $iGotAddress === $iExpectAddress,
                sprintf(
                    'Failed asserting effective address $%04X matches $%04X',
                    $iGotAddress,
                    $iExpectAddress
                )
            );
            $iReadValue = $oMemory->readByte($iGotAddress);
            $this->assert(
                $iReadValue === $iExpectValue,
                sprintf(
                    'Failed asserting value at effective address $%04X is $%02X, got $%02X',
                    $iGotAddress,
                    $iExpectValue,
                    $iReadValue
                )
            );
        }

    }
}
