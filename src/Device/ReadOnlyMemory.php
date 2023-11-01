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

/**
 * ReadOnlyMemory
 *
 * Writes ignored.
 */
class ReadOnlyMemory extends Memory {

    public function __construct(string $sBinary) {
        $iByteLength = strlen($sBinary);

        parent::__construct($iByteLength);

        if ($iByteLength & 0xFF) {
            $iByteLength = ($iByteLength & 0xFF00) + self::PAGE_SIZE;
            $sBinary = str_pad($sBinary, $iByteLength, "\0");
        }
        $this->sBinary = $sBinary;


//         if ($iByteLength < 1) {
//             throw new LogicException('Empty binary');
//         }
//         if ($iByteLength & 0xFF) {
//             $iByteLength = ($iByteLength & 0xFF00) + self::PAGE_SIZE;
//             $sBinary = str_pad($sBinary, $iByteLength, "\0");
//         }
//         $this->sBinary = $sBinary;
//         $this->iLength = $iByteLength >> 8;
//
//         $this->sName = sprintf("ROM unit %d (%d bytes)", self::$iUnit++, $iByteLength);
    }

    public function softReset(): self {
        return $this;
    }

    public function hardReset(): self {
        return $this;
    }

    public function writeByte(int $iAddress, int $iValue): void {
        // no op
    }

    protected function getType(): string {
        return 'ROM';
    }
}
