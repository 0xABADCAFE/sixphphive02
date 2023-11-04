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

namespace ABadCafe\SixPHPhive02\Processor;

use ABadCafe\SixPHPhive02\Device\IByteAccessible;
use ABadCafe\SixPHPhive02\Device\BusSnooper;
use LogicException;

/**
 * MOS6502Processor
 *
 * Basic implementation.
 */
class MOS6502ProcessorDebug extends MOS6502Processor implements MOS6502\IInsructionDisassembly {

    protected const REG_CHANGED_TPL = "\x1b[1m\x1b[48:5:%dm%02X\x1b[m";

    protected IByteAccessible $oOutsideDirect;

    protected int $iDelay;

    protected bool $bColour;

    // Shadow regs
    protected int
        $iSAccumulator,      // 8-bit
        $iSXIndex,           // 8-bit
        $iSYIndex,           // 8-bit
        $iSStackPointer,     // 8-bit
        $iSProgramCounter,   // 16-bit
        $iSStatus            // 8-bit,
    ;

    public function __construct(IByteAccessible $oOutside, int $iDelay = 25000) {
        $this->bColour = stream_isatty(STDOUT);
        if ($oOutside instanceof BusSnooper) {
            parent::__construct($oOutside);
            $this->oOutsideDirect = $oOutside->bypass();
        } else {
            parent::__construct(new BusSnooper($oOutside, $this->bColour));
            $this->oOutsideDirect = $oOutside;
        }
        $this->iDelay = $iDelay;


    }


    /**
     * @inheritDoc
     * @see IDevice
     */
    public function getName(): string {
        return 'MOS 6502 (debug)';
    }

    public function translateFlags(int $iFlags): string {
        $sFlags = '';
        $sFlags .= $iFlags & self::F_NEGATIVE  ? 'N' : '-';
        $sFlags .= $iFlags & self::F_OVERFLOW  ? 'V' : '-';
        $sFlags .= $iFlags & self::F_UNUSED    ? 'X' : '-';
        $sFlags .= $iFlags & self::F_BREAK     ? 'B' : '-';
        $sFlags .= $iFlags & self::F_DECIMAL   ? 'D' : '-';
        $sFlags .= $iFlags & self::F_INTERRUPT ? 'I' : '-';
        $sFlags .= $iFlags & self::F_ZERO      ? 'Z' : '-';
        $sFlags .= $iFlags & self::F_CARRY     ? 'C' : '-';

        return $sFlags;
    }

    public function dump() {
        printf(
            "Registers:\n" .
            "\t A: \$%02X : %d\n" .
            "\t X: \$%02X : %d\n" .
            "\t Y: \$%02X : %d\n" .
            "\t S: %s\n" .
            "\tSP: \$%02X (%d) \$%04X\n" .
            "\tPC: \$%04X (%d)\n",
            $this->iAccumulator, self::signByte($this->iAccumulator),
            $this->iXIndex, $this->iXIndex,
            $this->iYIndex, $this->iYIndex,
            $this->translateFlags($this->iStatus),
            $this->iStackPointer, $this->iStackPointer, $this->iStackPointer + self::STACK_BASE,
            $this->iProgramCounter, $this->iProgramCounter
        );
        //print("Zero Page:\n");
        //$this->dumpPage(0);
        //print("Stack:\n");
        //$this->dumpPage(self::STACK_BASE);
    }

    public function disassemble(int $iFrom, int $iBytes, bool $bAddress=true): void {
        $iFrom &= 0xFFFF;
        while ($iFrom < 0xFFFF && $iBytes > 0) {
            $iOpcode = $this->oOutsideDirect->readByte($iFrom);

            $iSize = self::OP_SIZE[$iOpcode] ?? 1;

            $str_operation = $this->decodeInstruction($iFrom);

            if ($bAddress) {
                printf(
                    "\t$%04X:\t%s\n",
                    $iFrom,
                    $str_operation
                );
            } else {
                echo "\t", $str_operation, "\n";
            }

            $iFrom += $iSize;
            $iBytes -= $iSize;
        }
    }

    protected function decodeInstruction(int $iFrom): string {
        $iFrom &= self::MEM_MASK;
        $iOpcode = $this->oOutsideDirect->readByte($iFrom);
        if (isset(self::OP_DISASM[$iOpcode])) {
            return sprintf(
                self::OP_DISASM[$iOpcode],
                $this->oOutsideDirect->readByte(($iFrom + 1) & self::MEM_MASK),
                $this->oOutsideDirect->readByte(($iFrom + 2) & self::MEM_MASK)
            );
        }
        return sprintf("\$%02X", $iOpcode);
    }

    protected function run() {
        $bRunning = true;
        $iCycles  = 0;
        $iOps     = 0;
        //$fMark    = microtime(true);
        while ($bRunning) {
            $this->oOutside->resetSnoops();
            $iOpcode    = $this->oOutside->readByte($this->iProgramCounter);
            $iExpectNext = $this->iProgramCounter + self::OP_SIZE[$iOpcode];
            printf(
                "\t%04X: %-12s | ",
                $this->iProgramCounter,
                $this->decodeInstruction($this->iProgramCounter)
            );

            //usleep($this->iDelay);

            $bRunning = $this->executeOpcode($iOpcode);
            $iCycles += self::OP_CYCLES[$iOpcode];
            ++$iOps;

            printf(
                "A:%s X:%s Y:%s S:%s SR:%s | %s\n",
                $this->renderA(),
                $this->renderX(),
                $this->renderY(),
                $this->renderSP(),
                $this->translateFlags($this->iStatus),
                $this->oOutside->getSnoops()
            );
            if ($this->iProgramCounter != $iExpectNext) {
                printf(
                    "\nJUMP TAKEN [PC reloaded \$%04X, following instruction was \$%04X]\n",
                    $this->iProgramCounter,
                    $iExpectNext
                );
            }
        }

        echo $this->oOutsideDirect->getPageDump(0x4000), "\n";

        //$fTime = microtime(true) - $fMark;

        //printf("Completed %d ops in %.6f seconds, %.2f op/s\n", $iOps, $fTime, $iOps/$fTime);
    }

    protected function renderA(): string {
        return $this->renderRegChanged($this->iSAccumulator, $this->iAccumulator);
    }

    protected function renderX(): string {
        return $this->renderRegChanged($this->iSXIndex, $this->iXIndex);
    }

    protected function renderY(): string {
        return $this->renderRegChanged($this->iSYIndex, $this->iYIndex);
    }

    protected function renderSP(): string {
        return $this->renderRegChanged($this->iSStackPointer, $this->iStackPointer);
    }

    protected function renderRegChanged(int &$iFrom, int &$iTo): string {
        if ($this->bColour) {
            if ($iFrom !== $iTo) {
                $iFrom = $iTo;
                return sprintf(self::REG_CHANGED_TPL, 1, $iTo);
            }
        }
        return sprintf("%02X", $iTo);
    }

    protected function reset(): void {
        parent::reset();
        $this->iSAccumulator    = $this->iAccumulator;
        $this->iSXIndex         = $this->iXIndex;
        $this->iSYIndex         = $this->iYIndex;
        $this->iSStackPointer   = $this->iStackPointer;
        $this->iSProgramCounter = $this->iProgramCounter;
        $this->iSStatus         = $this->iStatus;
    }
}
