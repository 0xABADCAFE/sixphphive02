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
 * IInstructionCycles
 *
 * Minimum cycle count per opcode
 */
interface IInstructionCycles extends IOpcodeEnum {
    const OP_CYCLES = [
        self::ADC_IM  => 2, // Immediate
        self::ADC_ZP  => 3, // Zero Page
        self::ADC_ZPX => 4, // Zero Page,X
        self::ADC_AB  => 4, // Absolute
        self::ADC_ABX => 4, // Absolute,X
        self::ADC_ABY => 4, // Absolute,Y
        self::ADC_IX  => 6, // Indirect,X
        self::ADC_IY  => 5, // Indirect,Y

        self::AND_IM  => 2, // Immediate
        self::AND_ZP  => 3, // Zero Page
        self::AND_ZPX => 4, // Zero Page,X
        self::AND_AB  => 4, // Absolute
        self::AND_ABX => 4, // Absolute,X
        self::AND_ABY => 4, // Absolute,Y
        self::AND_IX  => 6, // Indirect,X
        self::AND_IY  => 5, // Indirect,Y

        self::ASL_A   => 2, // Accumulator
        self::ASL_ZP  => 5, // Zero Page
        self::ASL_ZPX => 6, // Zero Page,X
        self::ASL_AB  => 6, // Absolute
        self::ASL_ABX => 7, // Absolute,X

        self::BCC     => 2,
        self::BCS     => 2,
        self::BEQ     => 2,

        self::BIT_ZP  => 3, // Zero Page
        self::BIT_AB  => 4, // Absolute

        self::BMI     => 2,
        self::BNE     => 2,
        self::BPL     => 2,
        self::BRK     => 7,
        self::BVC     => 2,
        self::BVS     => 2,

        self::CLC     => 2,
        self::CLD     => 2,
        self::CLI     => 2,
        self::CLV     => 2,

        self::CMP_IM  => 2, // Immediate
        self::CMP_ZP  => 3, // Zero Page
        self::CMP_ZPX => 4, // Zero Page,X
        self::CMP_AB  => 4, // Absolute
        self::CMP_ABX => 4, // Absolute,X
        self::CMP_ABY => 4, // Absolute,Y
        self::CMP_IX  => 6, // Indirect,X
        self::CMP_IY  => 5, // Indirect,Y

        self::CPX_IM  => 2, // Immediate
        self::CPX_ZP  => 3, // Zero Page
        self::CPX_AB  => 4, // Absolute

        self::CPY_IM  => 2, // Immediate
        self::CPY_ZP  => 3, // Zero Page
        self::CPY_AB  => 4, // Absolute

        self::DEC_ZP  => 5, // Zero Page
        self::DEC_ZPX => 6, // Zero Page,
        self::DEC_AB  => 6, // Absolute
        self::DEC_ABX => 7, // Absolute,

        self::DEX     => 2,
        self::DEY     => 2,

        self::EOR_IM  => 2, // Immediate
        self::EOR_ZP  => 3, // Zero Page
        self::EOR_ZPX => 4, // Zero Page,X
        self::EOR_AB  => 4, // Absolute
        self::EOR_ABX => 4, // Absolute,X
        self::EOR_ABY => 4, // Absolute,Y
        self::EOR_IX  => 6, // Indirect,X
        self::EOR_IY  => 5, // Indirect,Y

        self::INC_ZP  => 5, // Zero Page
        self::INC_ZPX => 6, // Zero Page,X
        self::INC_AB  => 6, // Absolute
        self::INC_ABX => 7, // Absolute,X

        self::INX     => 2,
        self::INY     => 2,

        self::JMP_AB  => 6,
        self::JMP_IN  => 5,

        self::JSR_AB  => 6,

        self::LDA_IM  => 2, // Immediate
        self::LDA_ZP  => 3, // Zero Page
        self::LDA_ZPX => 4, // Zero Page,X
        self::LDA_AB  => 4, // Absolute
        self::LDA_ABX => 4, // Absolute,X
        self::LDA_ABY => 4, // Absolute,Y
        self::LDA_IX  => 6, // Indirect,X
        self::LDA_IY  => 5, // Indirect,Y

        self::LDX_IM  => 2, // Immediate
        self::LDX_ZP  => 3, // Zero Page
        self::LDX_ZPY => 4, // Zero Page,Y
        self::LDX_AB  => 4, // Absolute
        self::LDX_ABY => 4, // Absolute,Y

        self::LDY_IM  => 2, // Immediate
        self::LDY_ZP  => 3, // Zero Page
        self::LDY_ZPX => 4, // Zero Page,X
        self::LDY_AB  => 4, // Absolute
        self::LDY_ABX => 4, // Absolute,X

        self::LSR_A   => 2, // Accumulator
        self::LSR_ZP  => 5, // Zero Page
        self::LSR_ZPX => 6, // Zero Page,X
        self::LSR_AB  => 6, // Absolute
        self::LSR_ABX => 7, // Absolute,X

        self::NOP     => 2,

        self::ORA_IM  => 2, // Immediate
        self::ORA_ZP  => 3, // Zero Page
        self::ORA_ZPX => 4, // Zero Page,X
        self::ORA_AB  => 4, // Absolute
        self::ORA_ABX => 4, // Absolute,X
        self::ORA_ABY => 4, // Absolute,Y
        self::ORA_IX  => 6, // Indirect,X
        self::ORA_IY  => 5, // Indirect,Y

        self::PHA     => 3,
        self::PHP     => 3,

        self::PLA     => 4,
        self::PLP     => 4,

        self::ROL_A   => 2, // Accumulator
        self::ROL_ZP  => 5, // Zero Page
        self::ROL_ZPX => 6, // Zero Page,X
        self::ROL_AB  => 6, // Absolute
        self::ROL_ABX => 7, // Absolute,X

        self::ROR_A   => 2, // Accumulator
        self::ROR_ZP  => 5, // Zero Page
        self::ROR_ZPX => 6, // Zero Page,X
        self::ROR_AB  => 6, // Absolute
        self::ROR_ABX => 7, // Absolute,X

        self::RTI     => 6,
        self::RTS     => 6,

        self::SBC_IM  => 2, // Immediate
        self::SBC_ZP  => 3, // Zero Page
        self::SBC_ZPX => 4, // Zero Page,X
        self::SBC_AB  => 4, // Absolute
        self::SBC_ABX => 4, // Absolute,X
        self::SBC_ABY => 4, // Absolute,Y
        self::SBC_IX  => 6, // Indirect,X
        self::SBC_IY  => 5, // Indirect,Y

        self::SEC     => 2,
        self::SED     => 2,
        self::SEI     => 2,

        self::STA_ZP  => 3, // Zero Page
        self::STA_ZPX => 4, // Zero Page,X
        self::STA_AB  => 4, // Absolute
        self::STA_ABX => 5, // Absolute,X
        self::STA_ABY => 5, // Absolute,Y
        self::STA_IX  => 6, // Indirect,X
        self::STA_IY  => 6, // Indirect,Y

        self::STX_ZP  => 3, // Zero Page
        self::STX_ZPY => 4, // Zero Page,Y
        self::STX_AB  => 4, // Absolute

        self::STY_ZP  => 3, // Zero Page
        self::STY_ZPX => 4, // Zero Page,Y
        self::STY_AB  => 4, // Absolute

        self::TAX     => 2,
        self::TAY     => 2,
        self::TSX     => 2,
        self::TXA     => 2,
        self::TXS     => 2,
        self::TYA     => 2,
    ];
}
