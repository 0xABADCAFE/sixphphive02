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
 * AddressMap
 *
 * Manages a set of IPageMappable instances
 */
class AddressMap implements IPageMappable {

    /** @var array<int, IPageMappable> */
    protected array $aDevices = [];

    /** @var array<int, IPageMappable> */
    protected array $aPageMap = [];

    /**
     * @inheritDoc
     */
    public function getName(): string {
        return 'AddressMap';
    }

    /**
     * @inheritDoc
     */
    public function softReset(): self {
        foreach ($this->aDevices as $oDevice) {
            $oDevice->softReset();
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function hardReset(): self {
        foreach ($this->aDevices as $oDevice) {
            $oDevice->hardReset();
        }
        return $this;
    }

    public function test(): self {
        echo "Testing bus\n";
        for ($i = 0; $i < 0x10000; $i += 32) {
            $this->readByte($i);
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setBasePage(int $iPage): self {
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function getLength(): int {
        return self::MAX_PAGES;
    }

    /**
     * @inheritDoc
     */
    public function readByte(int $iAddress): int {
        $iAddress &= 0xFFFF;
        $iPage = $iAddress >> 8;
        return isset($this->aPageMap[$iPage]) ? $this->aPageMap[$iPage]->readByte($iAddress) : 0;
    }

    /**
     * @inheritDoc
     */
    public function writeByte(int $iAddress, int $iValue): void {
        $iAddress &= 0xFFFF;
        $iPage = $iAddress >> 8;
        if (isset($this->aPageMap[$iPage])) {
            $this->aPageMap[$iPage]->writeByte($iAddress, $iValue);
        }
    }


    /**
     * Attach a IPageMappable to this bus at the requested address. The address must be page aligned and
     * within the address space of the AddressMap and the device length must fit at the requested location without
     * overlapping anything else already attached.
     */
    public function attach(IPageMappable $oDevice, int $iBaseAddress): self {
        if ($this === $oDevice) {
            throw new LogicException('AddressMap cannot attach to itself');
        }

        // Simple assertions

        if ($iBaseAddress & 0xFF) {
            throw new LogicException(
                sprintf(
                    'Requested base address %d for %s is not page aligned',
                    $iBaseAddress,
                    $oDevice->getName()
                )
            );
        }

        $iBasePage = $iBaseAddress >> 8;

        if ($iBasePage < 0 || $iBasePage > self::LAST_PAGE) {
            throw new LogicException('Page ' . $iBasePage . ' out of range');
        }
        if ($oDevice->getLength() < 1 || ($iBasePage + $oDevice->getLength()) > self::MAX_PAGES) {
            throw new LogicException('Device to large to fit');
        }

        // Make sure that the device isn't going to overlap with anything.
        $iPage     = $iBasePage;
        $iNumPages = $oDevice->getLength();
        while ($iNumPages--) {
            if (isset($this->aPageMap[$iPage])) {
                throw new LogicException('Page Conflict at ' . $iPage);
            }
            ++$iPage;
        }

        // Now mark the span of pages that point at our device.
        $iPage     = $iBasePage;
        $iNumPages = $oDevice->getLength();

        printf(
            "%s: Mapping %s to $%04X - $%04X\n",
            $this->getName(),
            $oDevice->getName(),
            $iBasePage << 8,
            (($iBasePage + $iNumPages) << 8) - 1
        );

        $this->aDevices[] = $oDevice;

        while ($iNumPages--) {
            $this->aPageMap[$iPage++] = $oDevice;
        }

        $oDevice->setBasePage($iBasePage);
        return $this;
    }
}
