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
$o6502 = new Processor\MOS6502Processor($oMemory, 0);
$o6502
    ->setInitialPC(0x400);
/*
   // Success breakpoints, infinite loops based on jump to self.
	$0625:	JMP $0625
	$0628:	JMP $0628
	$062B:	JMP $062B
	$062E:	JMP $062E
	$0631:	JMP $0631
*/
//     ->addBreakpoint(0x0625, 1)
//     ->addBreakpoint(0x0628, 1)
//     ->addBreakpoint(0x062B, 1)
//     ->addBreakpoint(0x062E, 1)
//     ->addBreakpoint(0x0631, 1);

$o6502->start();
