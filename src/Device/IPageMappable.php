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
 * Interface for devices that can be memory mapped into blocks of pages in the
 * 6502 sense, e.g. some multiple of 256 bytes at a 256 byte boundary.
 */
interface IPageMappable extends IByteAccessible {

    const PAGE_SIZE = 0x100; // 256 bytes x
    const MAX_PAGES = 0x100; // 256 pages = 64K
    const LAST_PAGE = 0x0FF;

    /**
     * Returns the size in pages
     */
    public function getLength(): int;

    /**
     * When mapped, sets the base page.
     */
    public function setBasePage(int $iPage): self;

}
