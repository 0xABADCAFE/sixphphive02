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

use ABadCafe\SixPHPhive02\I8BitProcessor;
use LogicException;

/**
 * BaseMOS6502Processor
 *
 * Common implementation
 */
abstract class BaseMOS6502Processor implements
    I8BitProcessor,
    MOS6502\IConstants,
    MOS6502\IOpcodeEnum,
    MOS6502\IInstructionSize,
    MOS6502\IInstructionCycles
{
    // Registers
    protected int
        $iAccumulator,      // 8-bit
        $iXIndex,           // 8-bit
        $iYIndex,           // 8-bit
        $iStackPointer,     // 8-bit
        $iProgramCounter,   // 16-bit
        $iStatus            // 8-bit,
    ;

    /**
     * @inheritDoc
     * @see I8BitProcessor
     */
    public function start(): self {
        $this->run();
        return $this;
    }

    /**
     * @inheritDoc
     * @see I8BitProcessor
     */
    public function setInitialPC(int $iAddress): self {
        assert($iAddress >= 0 && $iAddress < self::MEM_SIZE, new LogicException());
        $this->iProgramCounter = $iAddress & self::MEM_MASK;
        return $this;
    }

    public function setInitialSR(int $iFlags): self {
        $this->iStatus = $iFlags & 0xFF;
        return $this;
    }

    public function setInitialSP(int $iPos): self {
        $this->iStackPointer = $iPos & 0xFF;
        return $this;
    }

    /**
     * Reset the processor to a known state.
     */
    protected function reset(): void {
        $this->iAccumulator    = 0;
        $this->iXIndex         = 0;
        $this->iYIndex         = 0;
        $this->iStackPointer   = self::STACK_TOP - self::STACK_BASE; // offset in the page at STACK_BASE
        $this->iProgramCounter = $this->readWord(self::VEC_RES);     // load from reset vector
        $this->iStatus         = self::F_ZERO;
    }

    protected static function signByte(int $iValue): int {
        $iValue &= 0xFF;
        return ($iValue & self::F_NEGATIVE) ? $iValue - 256 : $iValue;
    }

    /**
     * Add the value and carry flag to the accumulator, updating accordingly.
     */
    protected function addByteWithCarry(int $iValue): void {

        $iSum = ($this->iStatus & self::F_DECIMAL) ?
            $this->addBCDWithCarry($iValue) :
            ($this->iStatus & self::F_CARRY) + $iValue + $this->iAccumulator;

        $iRes = $iSum & 0xFF;

        // Deal with the result
        $this->iStatus &= self::F_CLR_NZCV;
        $this->iStatus |= ($iRes ? ($iRes & self::F_NEGATIVE) : self::F_ZERO);
        $this->iStatus |= ($iSum & 0x100) ? self::F_CARRY : 0;
        $this->iStatus |= (
            ($iValue & self::F_NEGATIVE) == ($this->iAccumulator & self::F_NEGATIVE) &&
            ($iValue & self::F_NEGATIVE) != ($iRes & self::F_NEGATIVE)
        ) ? self::F_OVERFLOW : 0;

        $this->iAccumulator = $iRes;
    }

    /**
     * Add the value and carry flag to the accumulator using BCD semantics.
     *
     * TODO this is a bit of a mess
     */
    protected function addBCDWithCarry(int $iValue): int {
        // Nybbles
        $iSumL = ($this->iAccumulator & 0x0F) + ($iValue & 0x0F) + ($this->iStatus & self::F_CARRY);
        $iSumH = ($this->iAccumulator & 0xF0) + ($iValue & 0xF0);

        if ($iSumL > 0x09) {
            $iSumH += 0x10; // Carry
            $iSumL += 0x06; // Wrap
        }
        if ($iSumH > 0x90) {
            $iSumH += 0x60; // Wrap
        }
        return $iSumH & 0xFFF0 | $iSumL & 0x0F;
    }

    /**
     * Subtract the value and carry flag from the accumulator, updating accordingly.
     *
     * TODO this is a bit of a mess
     */
    protected function subByteWithCarry(int $iValue): void {
        $iDiff = ($this->iStatus & self::F_DECIMAL) ?
            $this->subBCDWithCarry($iValue) :
            $this->iAccumulator - $iValue - (~$this->iStatus & self::F_CARRY);

        $iRes = $iDiff & 0xFF;

        // Deal with the result
        $this->iStatus &= self::F_CLR_NZCV;
        $this->iStatus |= ($iRes ? ($iRes & self::F_NEGATIVE) : self::F_ZERO);
        $this->iStatus |= ($iDiff & 0x100) ? 0 : self::F_CARRY;
        $this->iStatus |= (
            ($iValue & self::F_NEGATIVE) != ($this->iAccumulator & self::F_NEGATIVE) &&
            ($this->iAccumulator & self::F_NEGATIVE) != ($iRes & self::F_NEGATIVE)
        ) ? self::F_OVERFLOW : 0;

        $this->iAccumulator = $iRes;
    }

    /**
     * Subtract the value and carry flag from the accumulator, using BCD semantics;
     */
    protected function subBCDWithCarry(int $iValue): int {
        // Nybbles
        $iDiffL = ($this->iAccumulator & 0x0F) - ($iValue & 0x0F) - (~$this->iStatus & self::F_CARRY);
        $iDiffH = ($this->iAccumulator & 0xF0) - ($iValue & 0xF0);

        if ($iDiffL & 0x10) {
            $iDiffL -= 0x06;
            --$iDiffH;
        }
        if ($iDiffH & 0x0100) {
            $iDiffH -= 0x60;
        }
        return $iDiffH & 0xFFF0 | $iDiffL & 0x0F;
    }

    protected function cmpByte(int $iTo, int $iValue): void {
        $iDiff = $iTo - $iValue ;//- (~$this->iStatus & self::F_CARRY);
        $iRes = $iDiff & 0xFF;

        // Deal with the result
        $this->iStatus &= self::F_CLR_NZC;
        $this->iStatus |= ($iRes ? ($iRes & self::F_NEGATIVE) : self::F_ZERO);
        $this->iStatus |= ($iDiff & 0x100) ? 0 : self::F_CARRY;
    }


    /**
     * Set the N and Z flags based on the operand
     */
    protected function updateNZ(int $iValue): void {
        $this->iStatus &= self::F_CLR_NZ;
        $this->iStatus |= (($iValue & 0xFF) ? ($iValue & self::F_NEGATIVE) : self::F_ZERO);
    }

    protected function shiftRightWithCarry(int $iValue): int {
        $this->iStatus &= ~self::F_CARRY;
        $this->iStatus |= ($iValue & self::F_CARRY);
        $this->updateNZ( $iValue >>= 1 );
        return $iValue;
    }

    protected function shiftLeftWithCarry(int $iValue): int {
        $this->iStatus &= ~self::F_CARRY;
        $this->iStatus |= ($iValue & self::F_NEGATIVE) >> 7; // sign -> carry
        $this->updateNZ( $iValue = (($iValue << 1) & 0xFF));
        return $iValue;
    }

    protected function rotateRightWithCarry(int $iValue): int {
        $iCarry = ($this->iStatus & self::F_CARRY) << 7; // carry -> sign
        $this->iStatus &= ~self::F_CARRY;
        $this->iStatus |= ($iValue & self::F_CARRY); // carry -> carry
        $this->updateNZ( $iValue = ( ($iValue >> 1) | $iCarry) );
        return $iValue;
    }

    protected function rotateLeftWithCarry(int $iValue): int {
        $iCarry = ($this->iStatus & self::F_CARRY);
        $this->iStatus &= ~self::F_CARRY;
        $this->iStatus |= ($iValue & self::F_NEGATIVE) >> 7; // sign -> carry
        $this->updateNZ( $iValue = ((($iValue << 1) | $iCarry) & 0xFF) );
        return $iValue;
    }
}
