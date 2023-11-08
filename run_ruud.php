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
    ->attach(new Device\Memory(0xE000), 0x0000)
    ->attach(new Device\ReadOnlyMemory(file_get_contents('data/roms/diagnostic/TTL6502.BIN')), 0xE000);
$o6502 = new Processor\MOS6502\Diagnostic($oMap, 0);
$o6502
    ->setInitialPC(0xE000)
    ->start();
