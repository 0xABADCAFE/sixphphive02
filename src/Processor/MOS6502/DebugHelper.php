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

class DebugHelper implements IInstructionSize, IInsructionDisassembly {

    public static function translateFlags(int $iFlags): string {
        $sFlags = '';
        $sFlags .= $iFlags & IConstants::F_NEGATIVE  ? 'N' : '-';
        $sFlags .= $iFlags & IConstants::F_OVERFLOW  ? 'V' : '-';
        $sFlags .= $iFlags & IConstants::F_UNUSED    ? 'X' : '-';
        $sFlags .= $iFlags & IConstants::F_BREAK     ? 'B' : '-';
        $sFlags .= $iFlags & IConstants::F_DECIMAL   ? 'D' : '-';
        $sFlags .= $iFlags & IConstants::F_INTERRUPT ? 'I' : '-';
        $sFlags .= $iFlags & IConstants::F_ZERO      ? 'Z' : '-';
        $sFlags .= $iFlags & IConstants::F_CARRY     ? 'C' : '-';
        return $sFlags;
    }

    /**
     * @param array<int, int> $aBytes - instructions keyed by address. Expect 3 bytes
     */
    protected static function decodeInstruction(array $aStream): string {
        $aBytes  = array_values($aStream);
        $iOpcode = (int)$aBytes[0];
        // Handle PC relative operand formatting
        if (isset(self::OP_DISASM_PCR[$iOpcode])) {
            $aAddresses = array_keys($aStream);
            $iFrom = $aAddresses[0];
            return sprintf(
                self::OP_DISASM_PCR[$iOpcode],
                ($iFrom + self::OP_SIZE[$iOpcode] + ($aBytes[1] ?? 0)) & self::ADDR_MASK
            );
        } else if (isset(self::OP_DISASM[$iOpcode])) {
            return sprintf(
                self::OP_DISASM[$iOpcode],
                $aBytes[1] ?? 0,
                $aBytes[2] ?? 0
            );
        } else {
            return sprintf("\$%02X", $iOpcode);
        }

    }

}
