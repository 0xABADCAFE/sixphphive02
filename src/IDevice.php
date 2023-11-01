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
 * Root level device interface. Not much to see here.
 */
interface IDevice {

    /**
     * Returns a descriptive name of the device for debugging etc.
     */
    public function getName(): string;

    /**
     * Trigger a soft reset of the device. Depending on what the device is, this may leave
     * some state intact.
     */
    public function softReset(): self;

    /**
     * Trigger a hard reset of the device.
     */
    public function hardReset(): self;
}
