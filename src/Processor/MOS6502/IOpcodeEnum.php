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

/**
 * Enumerates the MOS6502 opcodes.
 */
interface IOpcodeEnum {
    const
        // ADC - ADd with Carry - Affects N V Z C
        ADC_IM  = 0x69, // Immediate
        ADC_ZP  = 0x65, // Zero Page
        ADC_ZPX = 0x75, // Zero Page,X
        ADC_AB  = 0x6D, // Absolute
        ADC_ABX = 0x7D, // Absolute,X
        ADC_ABY = 0x79, // Absolute,Y
        ADC_IX  = 0x61, // Indirect,X
        ADC_IY  = 0x71, // Indirect,Y

        // AND - Bitwise AND with accumylator - Affects N Z
        AND_IM  = 0x29, // Immediate
        AND_ZP  = 0x25, // Zero Page
        AND_ZPX = 0x35, // Zero Page,X
        AND_AB  = 0x2D, // Absolute
        AND_ABX = 0x3D, // Absolute,X
        AND_ABY = 0x39, // Absolute,Y
        AND_IX  = 0x21, // Indirect,X
        AND_IY  = 0x31, // Indirect,Y

        // ASL - Arithmetic Shift Left - Affects N Z C
        ASL_A   = 0x0A, // Accumulator
        ASL_ZP  = 0x06, // Zero Page
        ASL_ZPX = 0x16, // Zero Page,X
        ASL_AB  = 0x0E, // Absolute
        ASL_ABX = 0x1E, // Absolute,X

        // BCC - Branch on Carry Clear
        BCC     = 0x90,

        // BCS - Branch on Carry Set
        BCS     = 0xB0,

        // BEQ - Branch on Result Zero
        BEQ     = 0xF0,

        // BIT - Test bit with accumulator
        BIT_ZP  = 0x24, // Zero Page
        BIT_AB  = 0x2C, // Absolute

        // BMI - Branch on result minus
        BMI     = 0x30,

        // BNE - Branch on result not zero
        BNE     = 0xD0,

        // BPL - Branch on result plus
        BPL     = 0x10,

        // BRK - Force break
        BRK     = 0x00,

        // BVC - Branch on overflow clear
        BVC     = 0x50,

        // BVS - Branch on overflow set
        BVS     = 0x70,

        // CLC - Clear Carry Flag
        CLC     = 0x18,

        // CLD - Clear Decimal Mode
        CLD     = 0xD8,

        // CLI - Clear Interrupt Disable Bit
        CLI     = 0x58,

        // CLV - Clear Overflow Flag
        CLV     = 0xB8,

        // CMP - Compare Memory with Accumulator
        CMP_IM  = 0xC9, // Immediate
        CMP_ZP  = 0xC5, // Zero Page
        CMP_ZPX = 0xD5, // Zero Page,X
        CMP_AB  = 0xCD, // Absolute
        CMP_ABX = 0xDD, // Absolute,X
        CMP_ABY = 0xD9, // Absolute,Y
        CMP_IX  = 0xC1, // Indirect,X
        CMP_IY  = 0xD1, // Indirect,Y

        // CPX - Compare Memory and Index X
        CPX_IM  = 0xE0, // Immediate
        CPX_ZP  = 0xE4, // Zero Page
        CPX_AB  = 0xEC, // Absolute

        // CPY - Compare Memory and Index Y
        CPY_IM  = 0xC0, // Immediate
        CPY_ZP  = 0xC4, // Zero Page
        CPY_AB  = 0xCC, // Absolute

        // DEC - Decrement Memory by One
        DEC_ZP  = 0xC6, // Zero Page
        DEC_ZPX = 0xD6, // Zero Page,
        DEC_AB  = 0xCE, // Absolute
        DEC_ABX = 0xDE, // Absolute,

        // DEX - Decrement Index X by One
        DEX     = 0xCA,

        // DEY - Decrement Index Y by One
        DEY     = 0x88,

        // EOR - Exclusive-OR Memory with Accumulator
        EOR_IM  = 0x49, // Immediate
        EOR_ZP  = 0x45, // Zero Page
        EOR_ZPX = 0x55, // Zero Page,X
        EOR_AB  = 0x4D, // Absolute
        EOR_ABX = 0x5D, // Absolute,X
        EOR_ABY = 0x59, // Absolute,Y
        EOR_IX  = 0x41, // Indirect,X
        EOR_IY  = 0x51, // Indirect,Y

        // INC - Increment Memory by One
        INC_ZP  = 0xE6, // Zero Page
        INC_ZPX = 0xF6, // Zero Page,
        INC_AB  = 0xEE, // Absolute
        INC_ABX = 0xFE, // Absolute,

        // INX - Increment Index X by One
        INX     = 0xE8,

        // INY - Increment Index Y by One
        INY     = 0xC8,

        // JMP - Jump to New Location
        JMP_AB  = 0x4C,
        JMP_IN  = 0x6C,

        // JSR - Jump to New Location Saving Return Address
        JSR_AB  = 0x20, // Absolute

        // LDA - Load Accumulator with Memory
        LDA_IM  = 0xA9, // Immediate
        LDA_ZP  = 0xA5, // Zero Page
        LDA_ZPX = 0xB5, // Zero Page,X
        LDA_AB  = 0xAD, // Absolute
        LDA_ABX = 0xBD, // Absolute,X
        LDA_ABY = 0xB9, // Absolute,Y
        LDA_IX  = 0xA1, // Indirect,X
        LDA_IY  = 0xB1, // Indirect,Y

        // LDX - Load Index X with Memory
        LDX_IM  = 0xA2, // Immediate
        LDX_ZP  = 0xA6, // Zero Page
        LDX_ZPY = 0xB6, // Zero Page,Y
        LDX_AB  = 0xAE, // Absolute
        LDX_ABY = 0xBE, // Absolute,Y

        // LDY - Load Index Y with Memory
        LDY_IM  = 0xA0, // Immediate
        LDY_ZP  = 0xA4, // Zero Page
        LDY_ZPX = 0xB4, // Zero Page,X
        LDY_AB  = 0xAC, // Absolute
        LDY_ABX = 0xBC, // Absolute,X

        // LSR - Shift One Bit Right (Memory or Accumulator)
        LSR_A   = 0x4A, // Accumulator
        LSR_ZP  = 0x46, // Zero Page
        LSR_ZPX = 0x56, // Zero Page,X
        LSR_AB  = 0x4E, // Absolute
        LSR_ABX = 0x5E, // Absolute,X

        // NOP - No Operation
        NOP     = 0xEA,

        // ORA - OR Memory with Accumulator
        ORA_IM  = 0x09, // Immediate
        ORA_ZP  = 0x05, // Zero Page
        ORA_ZPX = 0x15, // Zero Page,X
        ORA_AB  = 0x0D, // Absolute
        ORA_ABX = 0x1D, // Absolute,X
        ORA_ABY = 0x19, // Absolute,Y
        ORA_IX  = 0x01, // Indirect,X
        ORA_IY  = 0x11, // Indirect,Y


        // PHA - Push Accumulator on Stack
        PHA     = 0x48,

        // PHP - Push Processor Status on Stack
        PHP     = 0x08,

        // PLA - Pull Accumulator from Stack
        PLA     = 0x68,

        // PLP - Pull Processor Status from Stack
        PLP     = 0x28,

        // ROL - Rotate One Bit Left (Memory or Accumulator)
        ROL_A   = 0x2A, // Accumulator
        ROL_ZP  = 0x26, // Zero Page
        ROL_ZPX = 0x36, // Zero Page,X
        ROL_AB  = 0x2E, // Absolute
        ROL_ABX = 0x3E, // Absolute,X

        // ROR - Rotate One Bit Right (Memory or Accumulator)
        ROR_A   = 0x6A, // Accumulator
        ROR_ZP  = 0x66, // Zero Page
        ROR_ZPX = 0x76, // Zero Page,X
        ROR_AB  = 0x6E, // Absolute
        ROR_ABX = 0x7E, // Absolute,X

        // RTI - Return from Interrupt
        RTI     = 0x40,

        // RTS - Return from Subroutine
        RTS     = 0x60,

        // SBC - Subtract Memory from Accumulator with Borrow
        SBC_IM  = 0xE9, // Immediate
        SBC_ZP  = 0xE5, // Zero Page
        SBC_ZPX = 0xF5, // Zero Page,X
        SBC_AB  = 0xED, // Absolute
        SBC_ABX = 0xFD, // Absolute,X
        SBC_ABY = 0xF9, // Absolute,Y
        SBC_IX  = 0xE1, // Indirect,X
        SBC_IY  = 0xF1, // Indirect,Y

        // SEC - Set Carry Flag
        SEC     = 0x38,

        // SED - Set Decimal Flag
        SED     = 0xF8,

        // SEI - Set Interrupt Disable Status
        SEI     = 0x78,

        // STA - Store Accumulator in Memory
        STA_ZP  = 0x85, // Zero Page
        STA_ZPX = 0x95, // Zero Page,X
        STA_AB  = 0x8D, // Absolute
        STA_ABX = 0x9D, // Absolute,X
        STA_ABY = 0x99, // Absolute,Y
        STA_IX  = 0x81, // Indirect,X
        STA_IY  = 0x91, // Indirect,Y

        // STX - Store Index X in Memory
        STX_ZP  = 0x86, // Zero Page
        STX_ZPY = 0x96, // Zero Page,Y
        STX_AB  = 0x8E, // Absolute

        // STY - Store Index Y in Memory
        STY_ZP  = 0x84, // Zero Page
        STY_ZPX = 0x94, // Zero Page,Y
        STY_AB  = 0x8C, // Absolute

        // TAX - Transfer Accumulator to Index X
        TAX     = 0xAA,

        // TAY - Transfer Accumulator to Index Y
        TAY     = 0xA8,

        // TSX - Transfer Stack Pointer to Index X
        TSX     = 0xBA,

        // TXA - Transfer Index X to Accumulator
        TXA     = 0x8A,

        // TXS - Transfer Index X to Stack Register
        TXS     = 0x9A,

        // TYA - Transfer Index Y to Accumulator
        TYA     = 0x98,

        END = -1
    ;
}
