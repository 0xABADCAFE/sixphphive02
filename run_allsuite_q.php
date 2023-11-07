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

$o6502 = new Processor\MOS6502ProcessorQuick();
$o6502->setMemory(file_get_contents('data/AllSuiteA.bin'), 0x4000);
$o6502->setInitialPC(0x4000);
$o6502->start();
