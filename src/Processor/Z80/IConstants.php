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

use ABadCafe\SixPHPhive02\Device\IByteAccessible;
use ABadCafe\SixPHPhive02\I8BitProcessor;

/**
 * Miscellaneous constants specific to the Z80
 */
interface IConstants {

    // Flag bits
    public const
        F_CARRY      = 1,
        F_ADD_SUB    = 2,
        F_PARITY     = 4,
        F_OVERFLOW   = 4, // same as F_PARITY
        F_UNUSED_0   = 8,
        F_HALF_CARRY = 16,
        F_UNUSED_1   = 32,
        F_ZERO       = 64,
        F_SIGN       = 128

    ;
}
