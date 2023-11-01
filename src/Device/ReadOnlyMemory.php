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
class ReadOnlyMemory implements IPageMappable {

    private int    $iBaseAddress = 0;
    private int    $iLength      = 0;
    private string $sBinary;
    private string $sName;

    private static int $iUnit = 0;

    public function __construct(string $sBinary) {
        $iByteLength = strlen($sBinary);
        if ($iByteLength < 1) {
            throw new LogicException('Empty binary');
        }
        if ($iByteLength & 0xFF) {
            $iByteLength = ($iByteLength & 0xFF00) + self::PAGE_SIZE;
            $sBinary = str_pad($sBinary, $iByteLength, "\0");
        }
        $this->sBinary = $sBinary;
        $this->iLength = $iByteLength >> 8;

        $this->sName = sprintf("ROM unit %d (%d bytes)", self::$iUnit++, $iByteLength);
    }

    public function softReset(): self {
        return $this;
    }

    public function hardReset(): self {
        return $this;
    }

    public function readByte(int $iAddress): int {
        $iIndex = ($iAddress - $this->iBaseAddress) & 0xFFFF;
        //printf("%s[%d] hit for access $%04X\n", $this->getName(), $iIndex, $iAddress);
        return ord($this->sBinary[$iIndex]);
    }

    public function writeByte(int $iAddress, int $iValue): void {
        // no op
    }

    public function getLength(): int {
        return $this->iLength;
    }

    public function setBasePage(int $iPage): self {
        $this->iBaseAddress = $iPage << 8;
        return $this;
    }

    public function getName(): string {
        return $this->sName;
    }
}
