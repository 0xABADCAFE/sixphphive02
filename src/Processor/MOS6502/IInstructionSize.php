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
 * IInstructionSize
 *
 * Instruction length, in bytes, per opcode
 */
interface IInstructionSize extends IOpcodeEnum {
    const OP_SIZE = [
        self::ADC_IM  => 2, // Immediate
        self::ADC_ZP  => 2, // Zero Page
        self::ADC_ZPX => 2, // Zero Page,X
        self::ADC_AB  => 3, // Absolute
        self::ADC_ABX => 3, // Absolute,X
        self::ADC_ABY => 3, // Absolute,Y
        self::ADC_IX  => 2, // Indirect,X
        self::ADC_IY  => 2, // Indirect,Y

        self::AND_IM  => 2, // Immediate
        self::AND_ZP  => 2, // Zero Page
        self::AND_ZPX => 2, // Zero Page,X
        self::AND_AB  => 3, // Absolute
        self::AND_ABX => 3, // Absolute,X
        self::AND_ABY => 3, // Absolute,Y
        self::AND_IX  => 2, // Indirect,X
        self::AND_IY  => 2, // Indirect,Y

        self::ASL_A   => 1, // Accumulator
        self::ASL_ZP  => 2, // Zero Page
        self::ASL_ZPX => 2, // Zero Page,X
        self::ASL_AB  => 3, // Absolute
        self::ASL_ABX => 3, // Absolute,X

        self::BCC     => 2,
        self::BCS     => 2,
        self::BEQ     => 2,

        self::BIT_ZP  => 2, // Zero Page
        self::BIT_AB  => 3, // Absolute

        self::BMI     => 2,
        self::BNE     => 2,
        self::BPL     => 2,
        self::BRK     => 1,
        self::BVC     => 2,
        self::BVS     => 2,

        self::CLC     => 1,
        self::CLD     => 1,
        self::CLI     => 1,
        self::CLV     => 1,

        self::CMP_IM  => 2, // Immediate
        self::CMP_ZP  => 2, // Zero Page
        self::CMP_ZPX => 2, // Zero Page,X
        self::CMP_AB  => 3, // Absolute
        self::CMP_ABX => 3, // Absolute,X
        self::CMP_ABY => 3, // Absolute,Y
        self::CMP_IX  => 2, // Indirect,X
        self::CMP_IY  => 2, // Indirect,Y

        self::CPX_IM  => 2, // Immediate
        self::CPX_ZP  => 2, // Zero Page
        self::CPX_AB  => 3, // Absolute

        self::CPY_IM  => 2, // Immediate
        self::CPY_ZP  => 2, // Zero Page
        self::CPY_AB  => 3, // Absolute

        self::DEC_ZP  => 2, // Zero Page
        self::DEC_ZPX => 2, // Zero Page,X
        self::DEC_AB  => 3, // Absolute
        self::DEC_ABX => 3, // Absolute,X

        self::DEX     => 1,
        self::DEY     => 1,

        self::EOR_IM  => 2, // Immediate
        self::EOR_ZP  => 2, // Zero Page
        self::EOR_ZPX => 2, // Zero Page,X
        self::EOR_AB  => 3, // Absolute
        self::EOR_ABX => 3, // Absolute,X
        self::EOR_ABY => 3, // Absolute,Y
        self::EOR_IX  => 2, // Indirect,X
        self::EOR_IY  => 2, // Indirect,Y

        self::INC_ZP  => 2, // Zero Page
        self::INC_ZPX => 2, // Zero Page,X
        self::INC_AB  => 3, // Absolute
        self::INC_ABX => 3, // Absolute,X

        self::INX     => 1,
        self::INY     => 1,

        self::JMP_AB  => 3,
        self::JMP_IN  => 3,

        self::JSR_AB  => 3,

        self::LDA_IM  => 2, // Immediate
        self::LDA_ZP  => 2, // Zero Page
        self::LDA_ZPX => 2, // Zero Page,X
        self::LDA_AB  => 3, // Absolute
        self::LDA_ABX => 3, // Absolute,X
        self::LDA_ABY => 3, // Absolute,Y
        self::LDA_IX  => 2, // Indirect,X
        self::LDA_IY  => 2, // Indirect,Y

        self::LDX_IM  => 2, // Immediate
        self::LDX_ZP  => 2, // Zero Page
        self::LDX_ZPY => 2, // Zero Page,Y
        self::LDX_AB  => 3, // Absolute
        self::LDX_ABY => 3, // Absolute,Y

        self::LDY_IM  => 2, // Immediate
        self::LDY_ZP  => 2, // Zero Page
        self::LDY_ZPX => 2, // Zero Page,X
        self::LDY_AB  => 3, // Absolute
        self::LDY_ABX => 3, // Absolute,X

        self::LSR_A   => 1, // Accumulator
        self::LSR_ZP  => 2, // Zero Page
        self::LSR_ZPX => 2, // Zero Page,X
        self::LSR_AB  => 3, // Absolute
        self::LSR_ABX => 3, // Absolute,X

        self::NOP     => 1,

        self::ORA_IM  => 2, // Immediate
        self::ORA_ZP  => 2, // Zero Page
        self::ORA_ZPX => 2, // Zero Page,X
        self::ORA_AB  => 3, // Absolute
        self::ORA_ABX => 3, // Absolute,X
        self::ORA_ABY => 3, // Absolute,Y
        self::ORA_IX  => 2, // Indirect,X
        self::ORA_IY  => 2, // Indirect,Y

        self::PHA     => 1,
        self::PHP     => 1,

        self::PLA     => 1,
        self::PLP     => 1,

        self::ROL_A   => 1, // Accumulator
        self::ROL_ZP  => 2, // Zero Page
        self::ROL_ZPX => 2, // Zero Page,X
        self::ROL_AB  => 3, // Absolute
        self::ROL_ABX => 3, // Absolute,X

        self::ROR_A   => 1, // Accumulator
        self::ROR_ZP  => 2, // Zero Page
        self::ROR_ZPX => 2, // Zero Page,X
        self::ROR_AB  => 3, // Absolute
        self::ROR_ABX => 3, // Absolute,X

        self::RTI     => 1,
        self::RTS     => 1,

        self::SBC_IM  => 2, // Immediate
        self::SBC_ZP  => 2, // Zero Page
        self::SBC_ZPX => 2, // Zero Page,X
        self::SBC_AB  => 3, // Absolute
        self::SBC_ABX => 3, // Absolute,X
        self::SBC_ABY => 3, // Absolute,Y
        self::SBC_IX  => 2, // Indirect,X
        self::SBC_IY  => 2, // Indirect,Y

        self::SEC     => 1,
        self::SED     => 1,
        self::SEI     => 1,

        self::STA_ZP  => 2, // Zero Page
        self::STA_ZPX => 2, // Zero Page,X
        self::STA_AB  => 3, // Absolute
        self::STA_ABX => 3, // Absolute,X
        self::STA_ABY => 3, // Absolute,Y
        self::STA_IX  => 2, // Indirect,X
        self::STA_IY  => 2, // Indirect,Y

        self::STX_ZP  => 2, // Zero Page
        self::STX_ZPY => 2, // Zero Page,Y
        self::STX_AB  => 3, // Absolute

        self::STY_ZP  => 2, // Zero Page
        self::STY_ZPX => 2, // Zero Page,Y
        self::STY_AB  => 3, // Absolute

        self::TAX     => 1,
        self::TAY     => 1,
        self::TSX     => 1,
        self::TXA     => 1,
        self::TXS     => 1,
        self::TYA     => 1,
    ];

}
