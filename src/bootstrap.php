<?php

/**
 *   ___ _     ___ _  _ ___ _    _          __ ___
 *  / __(_)_ _| _ \ || | _ \ |_ (_)_ _____ /  \_  )
 *  \__ \ \ \ /  _/ __ |  _/ ' \| \ V / -_) () / /
 *  |___/_/_\_\_| |_||_|_| |_||_|_|\_/\___|\__/___|
 *
 *   - The world\'s least sensible 6502 emulator -
 */

declare(strict_types=1);

namespace ABadCafe\SixPHPhive02;
use \RuntimeException;
use function \spl_autoload_register;

if (PHP_VERSION_ID < 70400) {
    throw new RuntimeException('Requires at least PHP 7.4');
}

const CLASS_MAP = [
  'ABadCafe\\SixPHPhive02\\IDevice' => '/IDevice.php',
  'ABadCafe\\SixPHPhive02\\I8BitProcessor' => '/IProcessor.php',
  'ABadCafe\\SixPHPhive02\\Processor\\MOS6502\\IDebugHook' => '/Processor/MOS6502/Diagnostic.php',
  'ABadCafe\\SixPHPhive02\\Processor\\MOS6502\\IDiagnosticObserver' => '/Processor/MOS6502/Diagnostic.php',
  'ABadCafe\\SixPHPhive02\\Processor\\MOS6502\\StandardOutputDiagnosticObserver' => '/Processor/MOS6502/Diagnostic.php',
  'ABadCafe\\SixPHPhive02\\Processor\\MOS6502\\Diagnostic' => '/Processor/MOS6502/Diagnostic.php',
  'ABadCafe\\SixPHPhive02\\Processor\\MOS6502\\IOpcodeEnum' => '/Processor/MOS6502/IOpcodeEnum.php',
  'ABadCafe\\SixPHPhive02\\Processor\\MOS6502\\IConstants' => '/Processor/MOS6502/IConstants.php',
  'ABadCafe\\SixPHPhive02\\Processor\\MOS6502\\IInstructionCycles' => '/Processor/MOS6502/IInstructionCycles.php',
  'ABadCafe\\SixPHPhive02\\Processor\\MOS6502\\Quick' => '/Processor/MOS6502/Quick.php',
  'ABadCafe\\SixPHPhive02\\Processor\\MOS6502\\Standard' => '/Processor/MOS6502/Standard.php',
  'ABadCafe\\SixPHPhive02\\Processor\\MOS6502\\DebugHelper' => '/Processor/MOS6502/DebugHelper.php',
  'ABadCafe\\SixPHPhive02\\Processor\\MOS6502\\Base' => '/Processor/MOS6502/Base.php',
  'ABadCafe\\SixPHPhive02\\Processor\\MOS6502\\IInsructionDisassembly' => '/Processor/MOS6502/IInstructionDisassembly.php',
  'ABadCafe\\SixPHPhive02\\Processor\\MOS6502\\IInstructionSize' => '/Processor/MOS6502/IInstructionSize.php',
  'ABadCafe\\SixPHPhive02\\Processor\\Z80\\IOpcodeEnum' => '/Processor/Z80/IOpcodeEnum.php',
  'ABadCafe\\SixPHPhive02\\Processor\\Z80\\IConstants' => '/Processor/Z80/IConstants.php',
  'ABadCafe\\SixPHPhive02\\Processor\\Z80\\RegPair' => '/Processor/Z80/Base.php',
  'ABadCafe\\SixPHPhive02\\Processor\\Z80\\AFPair' => '/Processor/Z80/Base.php',
  'ABadCafe\\SixPHPhive02\\Processor\\Z80\\UserRegs' => '/Processor/Z80/Base.php',
  'ABadCafe\\SixPHPhive02\\Processor\\Z80\\Base' => '/Processor/Z80/Base.php',
  'ABadCafe\\SixPHPhive02\\Device\\AddressMap' => '/Device/AddressMap.php',
  'ABadCafe\\SixPHPhive02\\Device\\IByteAccessible' => '/Device/IByteAccessible.php',
  'ABadCafe\\SixPHPhive02\\Device\\ReadOnlyMemory' => '/Device/ReadOnlyMemory.php',
  'ABadCafe\\SixPHPhive02\\Device\\Memory' => '/Device/Memory.php',
  'ABadCafe\\SixPHPhive02\\Device\\IPageMappable' => '/Device/IPageMappable.php',
  'ABadCafe\\SixPHPhive02\\Device\\IByteConv' => '/Device/IByteConv.php',
  'ABadCafe\\SixPHPhive02\\Device\\NonVolatileMemory' => '/Device/NonVolatileMemory.php',
  'ABadCafe\\SixPHPhive02\\Device\\BusSnooper' => '/Device/BusSnooper.php',
];

spl_autoload_register(function(string $str_class): void {
    if (isset(CLASS_MAP[$str_class])) {
        require_once __DIR__ . CLASS_MAP[$str_class];
    }
});
