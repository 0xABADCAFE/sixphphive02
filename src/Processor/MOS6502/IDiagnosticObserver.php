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

namespace ABadCafe\SixPHPhive02\Processor\MOS6502;

use ABadCafe\SixPHPhive02\Device\IByteAccessible;

/**
 * Interface for implementations that observe the inner behaviour of the Diagnostic CPU implementation as it executes.
 */
interface IDiagnosticObserver {
    /**
     * Attach this observer to the Diagnostic CPU implementation
     */
    public function attach(Diagnostic $oCPU) : void;

    /**
     * Called after reset
     */
    public function postReset(): void;

    /**
     * Called prior to instruction fetch/excute
     */
    public function preInstruction() : void;

    /**
     * Called after instruction execution
     */
    public function postInstruction(): void;

    /**
     * Called by the Diagnostic CPU implementation when attached, in case the observer wants to modify the read/write
     * behaviour, e.g. monitoring IO, etc.
     */
    public function insertIOObserver(IByteAccessible $oOutside): IByteAccessible;
}


