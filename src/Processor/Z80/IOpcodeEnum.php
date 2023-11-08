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
 * Z80 Opcodes
 */
interface IOpcodeEnum {

    public const
        NOP         = 0x00,
        LD_BC_NN    = 0x01, // ld bc, nn
        LD_I_BC_A   = 0x02, // ld (bc), a
        INC_BC      = 0x03,
        INC_B       = 0x04,
        DEC_B       = 0x05,
        LD_B_N      = 0x06,
        RLCA        = 0x07,
        EX_AF_AFA   = 0x08,
        ADD_HL_BC   = 0x09
    ;
}
