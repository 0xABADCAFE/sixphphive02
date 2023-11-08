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

namespace ABadCafe\SixPHPhive02\Processor\MOS6502;

use ABadCafe\SixPHPhive02\Device\IByteAccessible;
use ABadCafe\SixPHPhive02\I8BitProcessor;

/**
 * Miscellaneous constants specific to the M6502
 */
interface IConstants {

    // Status flags
    public const
        F_CARRY     = 1,
        F_ZERO      = 2,
        F_INTERRUPT = 4,
        F_DECIMAL   = 8,
        F_BREAK     = 16,
        F_UNUSED    = 32,
        F_OVERFLOW  = 64,
        F_NEGATIVE  = 128,

        // Masks
        F_CLR_NZCV = ~(self::F_NEGATIVE | self::F_ZERO | self::F_CARRY | self::F_OVERFLOW),
        F_CLR_NZC  = ~(self::F_NEGATIVE | self::F_ZERO | self::F_CARRY),
        F_CLR_NZV  = ~(self::F_NEGATIVE | self::F_ZERO | self::F_OVERFLOW),
        F_CLR_NZ   = ~(self::F_NEGATIVE | self::F_ZERO)
    ;




    // Vectors
    public const
        // Vector addresses
        VEC_NMI     = 0xFFFA,
        VEC_RES     = 0xFFFC,
        VEC_IRQ     = 0xFFFE
    ;

    // Memory
    public const
        // Other fixed things
        PAGE_SIZE   = 0x100,
        STACK_BASE  = 0x100,
        STACK_TOP   = 0x1FF,
        MEM_SIZE    = 65536,
        MEM_MASK    = 0xFFFF
    ;
}
