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
 * IInsructionDisassembly
 *
 * sprint() style formatting templates for decoding a sequence of bytes following an opcode.
 */
interface IInsructionDisassembly extends IOpcodeEnum {
    const OP_DISASM = [

//        self::_IM  => " #\$%02X", // Immediate
//        self::_ZP  => " \$%02X", // Zero Page
//        self::_ZPX => " \$%02X,X", // Zero Page,X
//        self::_AB  => " \$%2\$02X%1\$02X", // Absolute
//        self::_ABX => " \$%2\$02X%1\$02X,X", // Absolute,X
//        self::_ABY => " \$%2\$02X%1\$02X,Y", // Absolute,Y
//        self::_IX  => " (\$%02X,X)", // Indirect,X
//        self::_IY  => " (\$%02X),Y", // Indirect,Y

        self::ADC_IM  => "ADC #\$%02X", // Immediate
        self::ADC_ZP  => "ADC \$%02X", // Zero Page
        self::ADC_ZPX => "ADC \$%02X,X", // Zero Page,X
        self::ADC_AB  => "ADC \$%2\$02X%1\$02X", // Absolute
        self::ADC_ABX => "ADC \$%2\$02X%1\$02X,X", // Absolute,X
        self::ADC_ABY => "ADC \$%2\$02X%1\$02X,Y", // Absolute,Y
        self::ADC_IX  => "ADC (\$%02X,X)", // Indirect,X
        self::ADC_IY  => "ADC (\$%02X),Y", // Indirect,Y

        self::AND_IM  => "AND #\$%02X", // Immediate
        self::AND_ZP  => "AND \$%02X", // Zero Page
        self::AND_ZPX => "AND \$%02X,X", // Zero Page,X
        self::AND_AB  => "AND \$%2\$02X%1\$02X", // Absolute
        self::AND_ABX => "AND \$%2\$02X%1\$02X,X", // Absolute,X
        self::AND_ABY => "AND \$%2\$02X%1\$02X,Y", // Absolute,Y
        self::AND_IX  => "AND (\$%02X,X)", // Indirect,X
        self::AND_IY  => "AND (\$%02X),Y", // Indirect,Y

        self::ASL_A   => "ASL A", // Immediate
        self::ASL_ZP  => "ASL \$%02X", // Zero Page
        self::ASL_ZPX => "ASL \$%02X,X", // Zero Page,X
        self::ASL_AB  => "ASL \$%2\$02X%1\$02X", // Absolute
        self::ASL_ABX => "ASL \$%2\$02X%1\$02X,X", // Absolute,X

        self::BCC     => "BCC %d",
        self::BCS     => "BCS %d",
        self::BEQ     => "BEQ %d",

        self::BIT_ZP  => "BIT \$%02X", // Zero Page
        self::BIT_AB  => "BIT \$%2\$02X%1\$02X", // Absolute

        self::BMI     => "BMI %d",
        self::BNE     => "BNE %d",
        self::BPL     => "BPL %d",
        self::BRK     => "BRK %d",
        self::BVC     => "BVC %d",
        self::BVS     => "BVS %d",

        self::CLC     => "CLC",
        self::CLD     => "CLD",
        self::CLI     => "CLI",
        self::CLV     => "CLV",

        self::CMP_IM  => "CMP #\$%02X", // Immediate
        self::CMP_ZP  => "CMP \$%02X", // Zero Page
        self::CMP_ZPX => "CMP \$%02X,X", // Zero Page,X
        self::CMP_AB  => "CMP \$%2\$02X%1\$02X", // Absolute
        self::CMP_ABX => "CMP \$%2\$02X%1\$02X,X", // Absolute,X
        self::CMP_ABY => "CMP \$$04X,Y", // Absolute,Y
        self::CMP_IX  => "CMP (\$%02X,X)", // Indirect,X
        self::CMP_IY  => "CMP (\$%02X),Y", // Indirect,Y

        self::CPX_IM  => "CPX #\$%02X", // Immediate
        self::CPX_ZP  => "CPX \$%02X", // Zero Page
        self::CPX_AB  => "CPX \$%2\$02X%1\$02X", // Absolute

        self::CPY_IM  => "CPY #\$%02X", // Immediate
        self::CPY_ZP  => "CPY \$%02X", // Zero Page
        self::CPY_AB  => "CPY \$%2\$02X%1\$02X", // Absolute

        self::DEC_ZP  => "DEC \$%02X", // Zero Page
        self::DEC_ZPX => "DEC \$%02X,X", // Zero Page,X
        self::DEC_AB  => "DEC \$%2\$02X%1\$02X", // Absolute
        self::DEC_ABX => "DEC \$%2\$02X%1\$02X,X", // Absolute,X

        self::DEX     => "DEX",
        self::DEY     => "DEY",

        self::EOR_IM  => "EOR #\$%02X", // Immediate
        self::EOR_ZP  => "EOR \$%02X", // Zero Page
        self::EOR_ZPX => "EOR \$%02X,X", // Zero Page,X
        self::EOR_AB  => "EOR \$%2\$02X%1\$02X", // Absolute
        self::EOR_ABX => "EOR \$%2\$02X%1\$02X,X", // Absolute,X
        self::EOR_ABY => "EOR \$%2\$02X%1\$02X,Y", // Absolute,Y
        self::EOR_IX  => "EOR (\$%02X,X)", // Indirect,X
        self::EOR_IY  => "EOR (\$%02X),Y", // Indirect,Y

        self::INC_ZP  => "INC \$%02X", // Zero Page
        self::INC_ZPX => "INC \$%02X,X", // Zero Page,X
        self::INC_AB  => "INC \$%2\$02X%1\$02X", // Absolute
        self::INC_ABX => "INC \$%2\$02X%1\$02X,X", // Absolute,X

        self::INX     => "INX",
        self::INY     => "INY",

        self::JMP_AB  => "JMP \$%2\$02X%1\$02X",
        self::JMP_IN  => "JMP (\$%2\$02X%1\$02X)",

        self::JSR_AB  => "JSR \$%2\$02X%1\$02X",

        self::LDA_IM  => "LDA #\$%02X", // Immediate
        self::LDA_ZP  => "LDA \$%02X", // Zero Page
        self::LDA_ZPX => "LDA \$%02X,X", // Zero Page,X
        self::LDA_AB  => "LDA \$%2\$02X%1\$02X", // Absolute
        self::LDA_ABX => "LDA \$%2\$02X%1\$02X,X", // Absolute,X
        self::LDA_ABY => "LDA \$%2\$02X%1\$02X,Y", // Absolute,Y
        self::LDA_IX  => "LDA (\$%02X,X)", // Indirect,X
        self::LDA_IY  => "LDA (\$%02X),Y", // Indirect,Y

        self::LDX_IM  => "LDX #\$%02X", // Immediate
        self::LDX_ZP  => "LDX \$%02X", // Zero Page
        self::LDX_ZPY => "LDX \$%02X,Y", // Zero Page
        self::LDX_AB  => "LDX \$%2\$02X%1\$02X", // Absolute
        self::LDX_ABY => "LDX \$%2\$02X%1\$02X,Y", // Absolute,Y

        self::LDY_IM  => "LDY #\$%02X", // Immediate
        self::LDY_ZP  => "LDY \$%02X", // Zero Page
        self::LDY_ZPX => "LDY \$%02X,X", // Zero Page,X
        self::LDY_AB  => "LDY \$%2\$02X%1\$02X", // Absolute
        self::LDY_ABX => "LDY \$%2\$02X%1\$02X,X", // Absolute,X

        self::LSR_A   => "LSR A", // Accumulator
        self::LSR_ZP  => "LSR \$%02X", // Zero Page
        self::LSR_ZPX => "LSR \$%02X,X", // Zero Page,X
        self::LSR_AB  => "LSR \$%2\$02X%1\$02X", // Absolute
        self::LSR_ABX => "LSR \$%2\$02X%1\$02X,X", // Absolute,X

        self::NOP     => "NOP",

        self::ORA_IM  => "ORA #\$%02X", // Immediate
        self::ORA_ZP  => "ORA \$%02X", // Zero Page
        self::ORA_ZPX => "ORA \$%02X,X", // Zero Page,X
        self::ORA_AB  => "ORA \$%2\$02X%1\$02X", // Absolute
        self::ORA_ABX => "ORA \$%2\$02X%1\$02X,X", // Absolute,X
        self::ORA_ABY => "ORA \$%2\$02X%1\$02X,Y", // Absolute,Y
        self::ORA_IX  => "ORA (\$%02X,X)", // Indirect,X
        self::ORA_IY  => "ORA (\$%02X),Y", // Indirect,Y

        self::PHA     => "PHA",
        self::PHP     => "PHP",

        self::PLA     => "PLA",
        self::PLP     => "PLP",

        self::ROL_A   => "ROL A", // Accumulator
        self::ROL_ZP  => "ROL \$%02X", // Zero Page
        self::ROL_ZPX => "ROL \$%02X,X", // Zero Page,X
        self::ROL_AB  => "ROL \$%2\$02X%1\$02X", // Absolute
        self::ROL_ABX => "ROL \$%2\$02X%1\$02X,X", // Absolute,X

        self::ROR_A   => "ROR A", // Accumulator
        self::ROR_ZP  => "ROR \$%02X", // Zero Page
        self::ROR_ZPX => "ROR \$%02X,X", // Zero Page,X
        self::ROR_AB  => "ROR \$%2\$02X%1\$02X", // Absolute
        self::ROR_ABX => "ROR \$%2\$02X%1\$02X,X", // Absolute,X

        self::RTI     => "RTI",
        self::RTS     => "RTS",

        self::SBC_IM  => "SBC #\$%02X", // Immediate
        self::SBC_ZP  => "SBC \$%02X", // Zero Page
        self::SBC_ZPX => "SBC \$%02X,X", // Zero Page,X
        self::SBC_AB  => "SBC \$%2\$02X%1\$02X", // Absolute
        self::SBC_ABX => "SBC \$%2\$02X%1\$02X,X", // Absolute,X
        self::SBC_ABY => "SBC \$%2\$02X%1\$02X,Y", // Absolute,Y
        self::SBC_IX  => "SBC (\$%02X,X)", // Indirect,X
        self::SBC_IY  => "SBC (\$%02X),Y", // Indirect,Y

        self::SEC     => "SEC",
        self::SED     => "SED",
        self::SEI     => "SEI",

        self::STA_ZP  => "STA \$%02X", // Zero Page
        self::STA_ZPX => "STA \$%02X,X", // Zero Page,X
        self::STA_AB  => "STA \$%2\$02X%1\$02X", // Absolute
        self::STA_ABX => "STA \$%2\$02X%1\$02X,X", // Absolute,X
        self::STA_ABY => "STA \$%2\$02X%1\$02X,Y", // Absolute,Y
        self::STA_IX  => "STA (\$%02X,X)", // Indirect,X
        self::STA_IY  => "STA (\$%02X),Y", // Indirect,Y

        self::STX_ZP  => "STX \$%02X", // Zero Page
        self::STX_AB  => "STX \$%2\$02X%1\$02X", // Absolute
        self::STX_ZPY => "STX \$%02X,Y", // Zero Page,Y

        self::STY_ZP  => "STY \$%02X", // Zero Page
        self::STY_ZPX => "STY \$%02X,X", // Zero Page,X
        self::STY_AB  => "STY \$%2\$02X%1\$02X", // Absolute

        self::TAX     => "TAX",
        self::TAY     => "TAY",
        self::TSX     => "TSX",
        self::TXA     => "TXA",
        self::TXS     => "TXS",
        self::TYA     => "TYA",
    ];

    const OP_DISASM_PCR = [
        self::BCC     => "BCC $%04X",
        self::BCS     => "BCS $%04X",
        self::BEQ     => "BEQ $%04X",
        self::BMI     => "BMI $%04X",
        self::BNE     => "BNE $%04X",
        self::BPL     => "BPL $%04X",
        self::BRK     => "BRK $%04X",
        self::BVC     => "BVC $%04X",
        self::BVS     => "BVS $%04X",
    ];
}
