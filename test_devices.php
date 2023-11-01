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

namespace ABadCafe\SixPHPhive02;

require_once 'src/bootstrap.php';

$oMap = new Device\AddressMap();

$oMap
    ->attach(new Device\Memory(1024), 0x0000)
    ->attach(new Device\NonVolatileMemory(256), 0xFF00)
    ->attach(new Device\ReadOnlyMemory("\0mock rom contents"), 0x4000)
    ->test();


