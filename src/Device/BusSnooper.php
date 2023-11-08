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

namespace ABadCafe\SixPHPhive02\Device;

/**
 * BusSnooper
 */
class BusSnooper implements IByteAccessible {

    private IByteAccessible $oTarget;

    private array $aReadMonitors = [];
    private array $aWriteMonitors = [];

    private string $sAccessed = '';

    private bool $bColour;
    /**
     * Wrap an IByteAccessible instance and collect access info
     */
    public function __construct(IByteAccessible $oTarget, bool $bColour) {
        $this->oTarget = $oTarget;
        $this->bColour = $bColour;
    }


    public function setReadAccessMonitor(int $iAddress, callable $cFunction): self {
        $this->aReadMonitors[$iAddress] = $cFunction;
    }

    public function setWriteAccessMonitor(int $iAddress, callable $cFunction): self {
        $this->aWriteMonitors[$iAddress] = $cFunction;
    }

    public function softReset(): self {
        $this->oTarget->softReset();
        return $this;
    }

    public function hardReset(): self {
        $this->oTarget->hardReset();
        return $this;
    }

    public function readByte(int $iAddress): int {
        $iRead = $this->oTarget->readByte($iAddress);
        $this->sAccessed .= sprintf(" [R: \$%04X => $%02X]", $iAddress, $iRead);
        if (isset($this->aReadMonitors[$iAddress])) {
            $cCallable = $this->aReadMonitors[$iAddress];
            $cCallable($iAddress, $iRead);
        }
        return $iRead;
    }

    public function writeByte(int $iAddress, int $iValue): void {
        if ($this->bColour) {
            $this->sAccessed .= sprintf(" \x1b[1m\x1b[48:5:%dm[W: \$%02X => $%04X]\x1b[m", 1, $iValue, $iAddress);
        } else {
            $this->sAccessed .= sprintf(" [W: \$%02X => $%04X]", $iValue, $iAddress);
        }
        if (isset($this->aWriteMonitors[$iAddress])) {
            $cCallable = $this->aWriteMonitors[$iAddress];
            $cCallable($iAddress, $iValue);
        }
        $this->oTarget->writeByte($iAddress, $iValue);
    }

    public function getName(): string {
        return 'Bus Snooper for: ' . $this->oTarget->getName();
    }

    public function resetSnoops(): void {
        $this->sAccessed = '';
    }

    public function getSnoops(): string {
        return $this->sAccessed;
    }

    public function bypass(): IByteAccessible {
        return $this->oTarget;
    }
}
