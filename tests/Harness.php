<?php

/**
 *   ___ _     ___ _  _ ___ _    _          __ ___
 *  / __(_)_ _| _ \ || | _ \ |_ (_)_ _____ /  \_  )
 *  \__ \ \ \ /  _/ __ |  _/ ' \| \ V / -_) () / /
 *  |___/_/_\_\_| |_||_|_| |_||_|_|\_/\___|\__/___|
 *
 *   - The world\'s least sensible 6502 emulator -
 */

declare(strict_types=1);

namespace ABadCafe\SixPHPhive02\Test;
use ABadCafe\SixPHPhive02\Device\IByteAccessible;

use \Throwable;
use \LogicException;

interface ITest {

    public function assert(bool $bCondition, string $sAssertion): void;
}

trait TTest {
    public function assert(bool $bCondition, string $sAssertion): void {
        Harness::assert($bCondition, $sAssertion);
    }

    protected function createMockMemory(array $aData): IByteAccessible {
        return new class($aData) implements IByteAccessible {
            public array $aData;
            public function __construct(array $aData) {
                $this->aData = $aData;
            }

            public function getName(): string {
                return 'Mock';
            }

            public function softReset(): self {
                return $this;
            }

            public function hardReset(): self {
                return $this;
            }

            public function readByte(int $iAddress): int {
                if ($iAddress < 0 || $iAddress > 0xFFFF) {
                    throw new \OutOfBoundsException('Address ' . $iAddress .' out of bounds');
                }
                if (!isset($this->aData[$iAddress])) {
                    throw new \OutOfBoundsException('Read from uninitialised address ' . $iAddress);
                }
                return $this->aData[$iAddress];
            }

            public function writeByte(int $iAddress, int $iValue): void {
                if ($iAddress < 0 || $iAddress > 0xFFFF) {
                    throw new \OutOfBoundsException('Address ' . $iAddress . ' out of bounds');
                }
                $this->aData[$iAddress] = $iValue;
            }
        };
    }
}

class Harness {

    private static int $iTestsPassed      = 0;
    private static int $iTestsFailed      = 0;
    private static int $iAssertionsPassed = 0;
    private static int $iAssertionsFailed = 0;
    private static int $iTestsErrored     = 0;

    public static function assert(bool $bCondition, string $sAssertion) {
        if (false === $bCondition) {
            ++self::$iAssertionsFailed;
            throw new LogicException($sAssertion);
        }
        ++self::$iAssertionsPassed;
    }

    public static function runNamedTests(ITest $oTest, array $aTests) {
        self::$iTestsPassed      = 0;
        self::$iTestsFailed      = 0;
        self::$iAssertionsPassed = 0;
        self::$iAssertionsFailed = 0;
        self::$iTestsErrored     = 0;

        foreach($aTests as $sTestName) {
            try {
                echo "Running ", get_class($oTest), '::', $sTestName, "()...";
                $cTest = [$oTest, $sTestName];
                $cTest();
                ++self::$iTestsPassed;
                echo "Passed\n";
            } catch (LogicException $oError) {
                printf("Failed: %s\n", $oError->getMessage());
                ++self::$iTestsFailed;
            } catch (Throwable $oError) {
                printf("Error: %s\n", $oError->getMessage());
                ++self::$iTestsErrored;
            }
        }
        printf(
            "\tPassed %d tests with %d assertions\n",
            self::$iTestsPassed,
            self::$iAssertionsPassed
        );
        if (self::$iTestsFailed) {
            printf(
                "\tFailed %d tests\n",
                self::$iTestsFailed
            );
        }
        if (self::$iTestsErrored) {
            printf(
                "\tErrored %d tests\n",
                self::$iTestsErrored
            );
        }
    }
}
