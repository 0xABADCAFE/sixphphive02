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

    /**
     * Wrap an IByteAccessible instance and collect access info
     */
    public function __construct(IByteAccessible $oTarget) {
        $this->oTarget = $oTarget;
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
        printf(" [R: \$%04X => $%02X]", $iAddress, $iRead);
        return $iRead;
    }

    public function writeByte(int $iAddress, int $iValue): void {
        printf(" [W: \$%02X => $%04X]", $iValue, $iAddress);
        $this->oTarget->writeByte($iAddress, $iValue);
    }

    public function getName(): string {
        return 'Bus Snooper for: ' . $this->oTarget->getName();
    }

    public function bypass(): IByteAccessible {
        return $this->oTarget;
    }
}
