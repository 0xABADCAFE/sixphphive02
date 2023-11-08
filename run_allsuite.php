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
    // Add some RAM from 0x0000 to 0x3FFF
    ->attach(new Device\Memory(0x4000), 0x0000)

    // Add a ROM at 0x4000, using the AllSuiteA binary imate
    ->attach(new Device\ReadOnlyMemory(file_get_contents('data/roms/diagnostic/AllSuiteA.bin')), 0x4000);

// Set the ROM address in the IRQ vector
// The ROM code will be executed as a consequence of soft interrupt when the processor starts and
// executes a BRK at address 0x0000.
$oMap->writeByte(Processor\MOS6502\Standard::VEC_IRQ,     0x00);
$oMap->writeByte(Processor\MOS6502\Standard::VEC_IRQ + 1, 0x40);

// Create the debug version of the CPU, which logs to screen what is happening, including the disassembly,
// processor status and memory read/write.
$o6502 = new Processor\MOS6502\Diagnostic($oMap, 15000);

// And off we go. Execution stops at any jump-to-self deadend. AllsuiteA is passed when it hits an infinite
// loop at 0x45C0 and the value at 0x0210 is 0xFF
$o6502->start();

if ($o6502->getPC() === 0x45C0 && $oMap->readByte(0x0210) === 0xFF) {
    print("AllSuiteA PASSED\n");
} else {
    print("AllSuiteA FAILED\n");
}
