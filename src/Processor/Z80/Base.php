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

namespace ABadCafe\SixPHPhive02\Processor\Z80;

use ABadCafe\SixPHPhive02\I8BitProcessor;
use LogicException;

/**
 * UserRegs
 *
 * Models the user register set of the Z80. The CPU maintains 2 of these, which are
 * swappable by the EXX instruction.
 */

final class RegPair {

    public int $iFull = 0;

    public function getLo(): int {
        return $this->iFull & 0xFF;
    }

    public function getHi(): int {
        return ($this->iFull >> 8) & 0xFF;
    }

    public function setLo(int $iLo): void {
        $this->iFull &= 0xFF00;
        $this->iFull |= ($iLo & 0xFF);
    }

    public function setHi(int $iHi): void {
        $this->iFull &= 0xFF;
        $this->iFull |= (($iHi & 0xFF) << 8);
    }
}

/**
 *
 */
final class AFPair {
    public int $iAccumulator = 0, $iFlags = 0;
}

final class UserRegs {
    public RegPair $oBC, $oDE, $oHL;
    public function __construct() {
        $this->oBC = new RegPair();
        $this->oDE = new RegPair();
        $this->oHL = new RegPair();
    }
}

/**
 * Base
 *
 * Common implementation
 */
abstract class Base implements
    I8BitProcessor,
    IConstants
{
    protected AFPair
        $oAF,
        $oAltAF
    ;

    protected UserRegs
        $oRegs,
        $oAltRegs
    ;

    protected int
        $iStackPointer,   // 16-bit
        $iProgramCounter, // 16-bit
        $iXIndex,         // 16-bit
        $iYIndex,         // 16-bit
        $iInterruptVector // 8-bit
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

    public function setInitialSP(int $iPos): self {
        $this->iStackPointer = $iPos & self::MEM_MASK;
        return $this;
    }

    public function getPC(): int {
        return $this->iProgramCounter;
    }

    /**
     * Reset the processor to a known state.
     */
    protected function reset(): void {
        $oAF          = $this->oAF;
        $this->oAF    = $this->oAltAF;
        $this->oAltAF = $oAF;
    }

    protected function exchangeAF(): void {
        $oRegs          = $this->oRegs;
        $this->oRegs    = $this->oAltRegs;
        $this->oAltRegs = $oRegs;
    }

    protected function exchangeRegs(): void {
        // switcheroo
        $oRegs          = $this->oRegs;
        $this->oRegs    = $this->oAltRegs;
        $this->oAltRegs = $oRegs;
    }

}
