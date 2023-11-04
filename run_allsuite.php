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
    ->attach(new Device\Memory(0x4000), 0x0000)
    ->attach(new Device\ReadOnlyMemory(file_get_contents('data/AllSuiteA.bin')), 0x4000);

// Set the ROM address in the IRQ vector
$oMap->writeByte(Processor\MOS6502Processor::VEC_IRQ,     0x00);
$oMap->writeByte(Processor\MOS6502Processor::VEC_IRQ + 1, 0x40);


$o6502 = new Processor\MOS6502ProcessorDebug($oMap, 0);
//$o6502 = new Processor\MOS6502Processor($oMap);
$o6502->start();
