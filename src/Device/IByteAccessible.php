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
use ABadCafe\SixPHPhive02\IDevice;

/**
 * Root interface for byte accessible devices accessibe from the 6502 core.
 *
 * All byte accessible devices are required to support 8-bit read and write access
 * to a maximum 16-bit address range, but are not required to actually act upon
 * either. For example, a ROM can simply discard writes and a write only sink
 * can return a fixed value for reads.
 */
interface IByteAccessible extends IDevice {
    const MAX_ADDRESS = 0xFFFF;

    /**
     * Reads a byte from the given address. The exact behaviour depends on the
     * nauture of the device.
     */
    public function readByte(int $iAddress): int;

    /**
     * write a byte to the given address. The exact behaviour depends on the
     * nauture of the device. For example, a read-only memory may simply ignore
     * the operation.
     */
    public function writeByte(int $iAddress, int $iValue): void;
}

