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
 * NonVolatileMemory
 *
 * Not cleared by reset()
 */
class NonVolatileMemory extends Memory {

    public function __construct(string $sBinary) {
        $iByteLength = strlen($sBinary);
        if ($iByteLength & 0xFF) {
            $iByteLength = (($iByteLength + 256) & 0xFF00);
            $sBinary = str_pad($sBinary, $iByteLength);
        }
        parent::__construct($iByteLength);
        $this->sBinary = $sBinary;
    }

    public function hardReset(): self {
        return $this;
    }

    protected function getType(): string {
        return 'RAM (NV)';
    }
}
