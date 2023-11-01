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
 * Memory
 *
 * Volatile RAM, cleared on hardReset(), but retained on softReset(). Byte accessible memory is implemented
 * as a basic string, since these are indexable in PHP. An array of ints is another plausible implementation
 * but needs around 48x as much memory (the typical size of a PHP7+ zval on 64-bit).
 */
class Memory implements IPageMappable {

    protected int    $iBaseAddress = 0;
    protected int    $iLastAddress = 0;
    protected int    $iLength      = 0;
    protected string $sName;

    protected static int $iUnit = 0;

    protected string $sBinary;

    /**
     * Requested byte lenght is rounded to the IPageMappable::PAGE_SIZE.
     */
    public function __construct(int $iByteLength) {
        if ($iByteLength < 1) {
            throw new LogicException('Invalid size');
        }
        if ($iByteLength & 0xFF) {
            $iByteLength = ($iByteLength & 0xFF00) + self::PAGE_SIZE;
        }
        $this->iLength = $iByteLength >> 8;
        $this->hardReset();
        $this->sName = sprintf("%s [unit %d] (%d bytes)", $this->getType(), self::$iUnit++, $iByteLength);
    }

    public function softReset(): self {
        return $this;
    }

    public function hardReset(): self {
        $this->sBinary = str_repeat("\0", $this->iLength << 8);
        return $this;
    }

    public function readByte(int $iAddress): int {
        $iIndex = ($iAddress - $this->iBaseAddress) & 0xFFFF;
        //printf("%s[%d] hit for access $%04X\n", $this->getName(), $iIndex, $iAddress);
        return ord($this->sBinary[$iIndex]);
    }

    public function writeByte(int $iAddress, int $iValue): void {
        $iIndex = ($iAddress - $this->iBaseAddress) & 0xFFFF;
        $this->sBinary[$iIndex] = chr($iValue & 0xFF);
    }

    public function getLength(): int {
        return $this->iLength;
    }

    public function setBasePage(int $iPage): self {
        $this->iBaseAddress = $iPage << 8;
        $this->iLastAddress = $this->iBaseAddress + ($this->iLength << 8) - 1;
        return $this;
    }

    public function getPageDump(int $iAddress): ?string {
        if ($iAddress < $this->iBaseAddress || $iAddress > $this->iLastAddress) {
            return null;
        }

        $sOutput = '';

        $iAddress &= 0xFF00;
        $iStart = ($iAddress - $this->iBaseAddress);
        $iRows  = self::PAGE_SIZE>>5;
        while ($iRows--) {
            $sRow = substr($this->sBinary, $iStart, 32);
            $sOutput .= sprintf(
                "\t\$%04X: %s\n",
                $iAddress,
                chunk_split(bin2hex($sRow), 2, " ")
            );
            $iStart += 32;
            $iAddress += 32;
        }

        return $sOutput;
    }

    public function getName(): string {
        return $this->sName;
    }

    protected function getType(): string {
        return 'RAM';
    }
}
