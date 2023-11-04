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

require 'src/bootstrap.php';

require 'tests/Harness.php';

//require 'tests/Memory.php';



// Test\Harness::runNamedTests(
//     new Test\Memory(),
//     [
//         'testReadByte',
//         'testWriteByte',
//         'testReadWord',
//     ]
// );

require 'tests/MOS6502/Address.php';
Test\Harness::runNamedTests(
    new Test\MOS6502\Address(),
    [
        'testZeroPage',
        'testZeroPageX',
        'testZeroPageY',
        'testAbsolute',
        'testAbsoluteX',
        'testAbsoluteY',
        'testPreIndexZeroPageXByte',
        'testPostIndexZeroPageYByte',
    ]
);

require 'tests/MOS6502/Flags.php';
Test\Harness::runNamedTests(
    new Test\MOS6502\Flags(),
    [
        'testZeroNegative',
        'testOnlyZeroNegative',
        'testAddByteNoInitialCarry',
        'testAddByteWithInitialCarry',
        'testSubByteNoInitialCarry',
        'testSubByteWithInitialCarry',
        'testLSRMemory',
        'testROLMemory',

    ]
);
