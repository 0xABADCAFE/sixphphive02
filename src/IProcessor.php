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

namespace ABadCafe\SixPHPhive02;

/**
 * Root interface for 8-bit processors.
 */
interface I8BitProcessor extends IDevice {

    /**
     * Begin execution
     */
    public function start(): self;

    /**
     * Set the initial execution point.
     */
    public function setInitialPC(int $iAddress): self;

    /**
     * Attach to the outside world.
     */
    public function setAddressSpace(Device\IByteAccessible $oOutside): self;
}
