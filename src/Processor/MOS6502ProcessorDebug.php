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

    protected IByteAccessible $oOutsideDirect;

    public function __construct(IByteAccessible $oOutside) {
        parent::__construct(new BusSnooper($oOutside));
        $this->oOutsideDirect = $oOutside;
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
        $sFlags .= $iFlags & self::F_ZERO      ? 'Z' : '-';
        $sFlags .= $iFlags & self::F_CARRY     ? 'C' : '-';
        $sFlags .= $iFlags & self::F_INTERRUPT ? 'I' : '-';
        $sFlags .= $iFlags & self::F_DECIMAL   ? 'D' : '-';
        $sFlags .= $iFlags & self::F_OVERFLOW  ? 'V' : '-';
        $sFlags .= $iFlags & self::F_BREAK     ? 'B' : '-';
        return $sFlags;
    }

    public function dump() {
        printf(
            "Registers:\n" .
            "\t A: \$%02X : %d\n" .
            "\t X: \$%02X : %d\n" .
            "\t Y: \$%02X : %d\n" .
            "\t S: %s%s%s%s%s%s%s\n" .
            "\tSP: \$%02X (%d) \$%04X\n" .
            "\tPC: \$%04X (%d)\n",
            $this->iAccumulator, self::signByte($this->iAccumulator),
            $this->iXIndex, $this->iXIndex,
            $this->iYIndex, $this->iYIndex,
            $this->iStatus & self::F_NEGATIVE  ? 'N' : '-',
            $this->iStatus & self::F_ZERO      ? 'Z' : '-',
            $this->iStatus & self::F_CARRY     ? 'C' : '-',
            $this->iStatus & self::F_INTERRUPT ? 'I' : '-',
            $this->iStatus & self::F_DECIMAL   ? 'D' : '-',
            $this->iStatus & self::F_OVERFLOW  ? 'V' : '-',
            $this->iStatus & self::F_BREAK     ? 'B' : '-',
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
            $iOpcode = $this->oOutside->readByte($this->iProgramCounter);
            printf(
                "\t%04X: %-12s : ",
                $this->iProgramCounter,
                $this->decodeInstruction($this->iProgramCounter)
            );

            usleep(25000);

            $bRunning = $this->executeOpcode($iOpcode);
            $iCycles += self::OP_CYCLES[$iOpcode];
            ++$iOps;

            printf(
                " A:%02X X:%02X Y:%02X S:%02X SR:%s\n",
                $this->iAccumulator,
                $this->iXIndex,
                $this->iYIndex,
                $this->iStackPointer,
                $this->translateFlags($this->iStatus)
            );

        }
        //$fTime = microtime(true) - $fMark;

        //printf("Completed %d ops in %.6f seconds, %.2f op/s\n", $iOps, $fTime, $iOps/$fTime);
    }

}
