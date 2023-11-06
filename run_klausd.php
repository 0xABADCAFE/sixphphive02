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


$oMemory = new Device\NonVolatileMemory(file_get_contents('data/6502_functional_test.bin'));
$o6502 = new Processor\MOS6502ProcessorDebug($oMemory, 0);
$o6502
    ->addBreakpoint(0x37C9, 1)
    ->addBreakpoint(0x0D96, 1)
    ->addBreakpoint(0x1C21, 1)
    ->setInitialPC(0x400)
    ->start();
