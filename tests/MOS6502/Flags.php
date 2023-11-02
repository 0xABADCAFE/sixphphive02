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
use ABadCafe\SixPHPhive02\Processor\MOS6502ProcessorDebug;

/**
 * Tests the operations affecting status flags for the MOS6502Processor.
 *
 * This is not a true unit test. It extends the processor class, disabling the original constructor so
 * that it can directly manipulate instances of the processor under test conditions. However, it does
 * carry out the same essential task of testing units of code.
 */
class Flags extends MOS6502ProcessorDebug implements ITest {

    use TTest;

    public function __construct() {
        // Disable original constructor
    }

    public function testZeroNegative() {
        $oProcessor = new MOS6502Processor($this->createMockMemory([]));
        $aTestCases = [
              0 => self::F_ZERO,
              1 => 0,
             -1 => self::F_NEGATIVE,
            127 => 0,
            128 => self::F_NEGATIVE,
            255 => self::F_NEGATIVE,
            256 => self::F_ZERO,
            257 => 0
        ];

        foreach ($aTestCases as $iValue => $iExpect) {
            $oProcessor->iStatus = 0;
            $oProcessor->updateNZ($iValue);
            $this->assert(
                $oProcessor->iStatus === $iExpect,
                sprintf(
                    'Failed asserting flags set to %s, got %s',
                    $this->translateFlags($iExpect),
                    $this->translateFlags($oProcessor->iStatus)
                )
            );
        }
    }

    public function testOnlyZeroNegative() {
        $oProcessor = new MOS6502Processor($this->createMockMemory([]));
        $aTestCases = [
              0 => 0xFF & ~self::F_NEGATIVE,
              1 => 0xFF & ~(self::F_ZERO|self::F_NEGATIVE),
             -1 => 0xFF & ~self::F_ZERO,
            127 => 0xFF & ~(self::F_ZERO|self::F_NEGATIVE),
            128 => 0xFF & ~self::F_ZERO,
            255 => 0xFF & ~self::F_ZERO,
            256 => 0xFF & ~self::F_NEGATIVE,
        ];

        foreach ($aTestCases as $iValue => $iExpect) {
            $oProcessor->iStatus = 0xFF;
            $oProcessor->updateNZ($iValue);
            $this->assert(
                $oProcessor->iStatus === $iExpect,
                sprintf(
                    'Failed asserting flags set to %s, got %s',
                    $this->translateFlags($iExpect),
                    $this->translateFlags($oProcessor->iStatus)
                )
            );
        }
    }

    public function testAddByteNoInitialCarry() {
        $oProcessor = new MOS6502Processor($this->createMockMemory([]));

        $aTestCasesPerAccum = [
            0 => [
                  0 => self::F_ZERO,      // 0 + 0
                  1 => 0,                 // 0 + 1
                128 => self::F_NEGATIVE   // 0 + -128 - inputs are opposite sign, no overflow
            ],
            127 => [
                0    => 0,                                  // 127 + 0
                1    => self::F_NEGATIVE|self::F_OVERFLOW,  // 127 + 1 - sign change, overflow
            ],
            255 => [
                0 => self::F_NEGATIVE,                      // 255 + 0
                1 => self::F_ZERO|self::F_CARRY             // 255 + 1 - result carry
            ]
        ];

        foreach ($aTestCasesPerAccum as $iInitialAccumulator => $aTestCases) {
            foreach ($aTestCases as $iValue => $iExpectFlags) {
                $oProcessor->iStatus = 0;
                $oProcessor->iAccumulator = $iInitialAccumulator;
                $oProcessor->addByteWithCarry($iValue);

                $iExpectValue = ($iInitialAccumulator + $iValue) & 0xFF;

                $this->assert(
                    $oProcessor->iAccumulator === $iExpectValue,
                    sprintf(
                        'Failed asserting value is %02X, got %02X',
                        $iExpectValue, $oProcessor->iAccumulator
                    )
                );

                $this->assert(
                    $oProcessor->iStatus === $iExpectFlags,
                    sprintf(
                        'Failed asserting flags set to %s, got %s',
                        $this->translateFlags($iExpectFlags),
                        $this->translateFlags($oProcessor->iStatus)
                    )
                );
            }
        }
    }

    public function testAddByteWithInitialCarry() {
        $oProcessor = new MOS6502Processor($this->createMockMemory([]));

        $aTestCasesPerAccum = [
            0 => [                                 // A   N            C
                  0 => 0,                          // 0 + 0          + 1 = 1
                  1 => 0,                          // 0 + 1          + 1 = 2
                128 => self::F_NEGATIVE,           // 0 + -128 (128) + 1 = -127 inputs are opposite sign, no overflow
                255 => self::F_ZERO|self::F_CARRY  // 0 + -1 (255)   + 1 = 0 (256)
            ],
            127 => [                                       // A     N            C
                  0 => self::F_NEGATIVE|self::F_OVERFLOW,  // 127 + 0          + 1 = -128
                127 => self::F_NEGATIVE|self::F_OVERFLOW,  // 127 + 127        + 1 = 255 (-128
                128 => self::F_ZERO|self::F_CARRY,         // 127 + -128 (128) + 1 = 0 (256)
                255 => self::F_CARRY                       // 127 + -1 (255)   + 1 = 127 (383)
            ],
            255 => [                                       // A          N          C
                  0 => self::F_ZERO|self::F_CARRY,         // -1 (255) + 0        + 1 = 0 (256)
                255 => self::F_NEGATIVE|self::F_CARRY,     // -1 (255) + -1 (255) + 1 = -1 (511)
            ],
        ];

        foreach ($aTestCasesPerAccum as $iInitialAccumulator => $aTestCases) {
            foreach ($aTestCases as $iValue => $iExpectFlags) {
                $oProcessor->iStatus = self::F_CARRY;
                $oProcessor->iAccumulator = $iInitialAccumulator;
                $oProcessor->addByteWithCarry($iValue);

                $iExpectValue = ($iInitialAccumulator + $iValue + 1) & 0xFF;

                $this->assert(
                    $oProcessor->iAccumulator === $iExpectValue,
                    sprintf(
                        'Failed asserting value is %02X, got %02X',
                        $iExpectValue,
                        $oProcessor->iAccumulator
                    )
                );

                $this->assert(
                    $oProcessor->iStatus === $iExpectFlags,
                    sprintf(
                        'Failed asserting flags set to %s, got %s',
                        $this->translateFlags($iExpectFlags),
                        $this->translateFlags($oProcessor->iStatus)
                    )
                );
            }
        }
    }

    public function testSubByteNoInitialCarry() {
        $oProcessor = new MOS6502Processor($this->createMockMemory([]));

        // Note that the Borrow flag is the INVERSE of the Carry flag

        $aTestCasesPerAccum = [
            0 => [
                    0 => self::F_NEGATIVE, // 0 - 0 - 1  = -1
                  254 => 0,
                  255 => self::F_ZERO      // 0 - -1 - 1 = 0
            ],
            1 => [
                    0 => self::F_ZERO|self::F_CARRY,      // 1 - 0 - 1  = 0
                  255 => 0,
            ],
        ];

        foreach ($aTestCasesPerAccum as $iInitialAccumulator => $aTestCases) {
            foreach ($aTestCases as $iValue => $iExpectFlags) {
                $oProcessor->iStatus = 0;
                $oProcessor->iAccumulator = $iInitialAccumulator;
                $oProcessor->subByteWithCarry($iValue);

                $iExpectValue = ($iInitialAccumulator - $iValue - 1) & 0xFF;

                $this->assert(
                    $oProcessor->iAccumulator === $iExpectValue,
                    sprintf(
                        'Failed asserting value is %02X, got %02X',
                        $iExpectValue,
                        $oProcessor->iAccumulator
                    )
                );

                $this->assert(
                    $oProcessor->iStatus === $iExpectFlags,
                    sprintf(
                        "\nFailed asserting flags set to %s, got %s on $%02X - $%02X - $01 = $%02X",
                        $this->translateFlags($iExpectFlags),
                        $this->translateFlags($oProcessor->iStatus),
                        $iInitialAccumulator,
                        $iValue,
                        $iExpectValue
                    )
                );
            }
        }
    }

    public function testSubByteWithInitialCarry() {
        $oProcessor = new MOS6502Processor($this->createMockMemory([]));

        // Note that the Borrow flag is the INVERSE of the Carry flag

        $aTestCasesPerAccum = [
            0 => [
                    0 => self::F_ZERO|self::F_CARRY, // 0 - 0  = 0
                    1 => self::F_NEGATIVE,           // 0 - 1  = -1
                  255 => 0                           // 0 - -1 = 1
            ],

        ];

        foreach ($aTestCasesPerAccum as $iInitialAccumulator => $aTestCases) {
            foreach ($aTestCases as $iValue => $iExpectFlags) {
                $oProcessor->iStatus = self::F_CARRY;
                $oProcessor->iAccumulator = $iInitialAccumulator;
                $oProcessor->subByteWithCarry($iValue);

                $iExpectValue = ($iInitialAccumulator - $iValue) & 0xFF;

                $this->assert(
                    $oProcessor->iAccumulator === $iExpectValue,
                    sprintf(
                        'Failed asserting value is %02X, got %02X',
                        $iExpectValue,
                        $oProcessor->iAccumulator
                    )
                );

                $this->assert(
                    $oProcessor->iStatus === $iExpectFlags,
                    sprintf(
                        "\nFailed asserting flags set to %s, got %s on $%02X - $%02X - $01 = $%02X",
                        $this->translateFlags($iExpectFlags),
                        $this->translateFlags($oProcessor->iStatus),
                        $iInitialAccumulator,
                        $iValue,
                        $iExpectValue
                    )
                );
            }
        }
    }
}
