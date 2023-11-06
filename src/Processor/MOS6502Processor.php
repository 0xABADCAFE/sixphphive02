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

namespace ABadCafe\SixPHPhive02\Processor;

use ABadCafe\SixPHPhive02\Device\IByteAccessible;
use ABadCafe\SixPHPhive02\I8BitProcessor;
use LogicException;

/**
 * MOS6502Processor
 *
 * Basic implementation.
 */
class MOS6502Processor implements
    I8BitProcessor,
    MOS6502\IConstants,
    MOS6502\IOpcodeEnum,
    MOS6502\IInstructionSize,
    MOS6502\IInstructionCycles
{
    // Registers
    protected int
        $iAccumulator,      // 8-bit
        $iXIndex,           // 8-bit
        $iYIndex,           // 8-bit
        $iStackPointer,     // 8-bit
        $iProgramCounter,   // 16-bit
        $iStatus            // 8-bit,
    ;

    protected IByteAccessible $oOutside;

    public function __construct(IByteAccessible $oOutside) {
        $this->oOutside = $oOutside;
        $this->hardReset();
    }

    /**
     * @inheritDoc
     * @see IDevice
     */
    public function getName(): string {
        return 'MOS 6502 (simple)';
    }

    /**
     * @inheritDoc
     * @see IDevice
     */
    public function softReset(): self {
        $this->oOutside->softReset();
        $this->reset();
        return $this;
    }

    /**
     * @inheritDoc
     * @see IDevice
     */
    public function hardReset(): self {
        $this->oOutside->hardReset();
        $this->reset();
        return $this;
    }

    /**
     * @inheritDoc
     * @see I8BitProcessor
     */
    public function start(): self {
        $this->run();
        return $this;
    }

    /**
     * @inheritDoc
     * @see I8BitProcessor
     */
    public function setInitialPC(int $iAddress): self {
        assert($iAddress >= 0 && $iAddress < self::MEM_SIZE, new LogicException());
        $this->iProgramCounter = $iAddress & self::MEM_MASK;
        return $this;
    }

    public function setInitialSR(int $iFlags): self {
        $this->iStatus = $iFlags & 0xFF;
        return $this;
    }

    public function setInitialSP(int $iPos): self {
        $this->iStackPointer = $iPos & 0xFF;
        return $this;
    }

    /**
     * Attach to the outside world.
     */
    public function setAddressSpace(IByteAccessible $oOutside): self {
        $this->oOutside = $oOutside;
        return $this;
    }

    protected function reset(): void {
        $this->iAccumulator    = 0;
        $this->iXIndex         = 0;
        $this->iYIndex         = 0;
        $this->iStackPointer   = self::STACK_TOP - self::STACK_BASE; // offset in the page at STACK_BASE
        $this->iProgramCounter = $this->readWord(self::VEC_RES);     // load from reset vector
        $this->iStatus         = 0;
    }

    protected static function signByte(int $iValue): int {
        $iValue &= 0xFF;
        return ($iValue & self::F_NEGATIVE) ? $iValue - 256 : $iValue;
    }

    protected function addByteWithCarry(int $iValue): void {
        $iSum = ($this->iStatus & self::F_CARRY) + ($iValue & 0xFF) + ($this->iAccumulator & 0xFF);
        $iRes = $iSum & 0xFF;

        // Deal with the result
        $this->iStatus &= ~(self::F_NEGATIVE | self::F_ZERO | self::F_CARRY | self::F_OVERFLOW);
        $this->iStatus |= ($iRes & self::F_NEGATIVE) | ($iRes ? 0 : self::F_ZERO);
        $this->iStatus |= ($iSum & 0x100) ? self::F_CARRY : 0;
        $this->iStatus |= (
            ($iValue & self::F_NEGATIVE) == ($this->iAccumulator & self::F_NEGATIVE) &&
            ($iValue & self::F_NEGATIVE) != ($iRes & self::F_NEGATIVE)
        ) ? self::F_OVERFLOW : 0;

        $this->iAccumulator = $iRes;
    }

    protected function subByteWithCarry(int $iValue): void {
        $iDiff = ($this->iAccumulator & 0xFF) - ($iValue & 0xFF) - (~$this->iStatus & self::F_CARRY);
        $iRes = $iDiff & 0xFF;

        // Deal with the result
        $this->iStatus &= ~(self::F_NEGATIVE | self::F_ZERO | self::F_CARRY | self::F_OVERFLOW);
        $this->iStatus |= ($iRes & self::F_NEGATIVE) | ($iRes ? 0 : self::F_ZERO);
        $this->iStatus |= ($iDiff & 0x100) ? 0 : self::F_CARRY;
        $this->iStatus |= (
            ($iValue & self::F_NEGATIVE) != ($this->iAccumulator & self::F_NEGATIVE) &&
            ($this->iAccumulator & self::F_NEGATIVE) != ($iRes & self::F_NEGATIVE)
        ) ? self::F_OVERFLOW : 0;

        $this->iAccumulator = $iRes;
    }

    protected function cmpByte(int $iTo, int $iValue): void {
        $iDiff = ($iTo & 0xFF) - ($iValue & 0xFF) ;//- (~$this->iStatus & self::F_CARRY);
        $iRes = $iDiff & 0xFF;

        // Deal with the result
        $this->iStatus &= ~(self::F_NEGATIVE | self::F_ZERO | self::F_CARRY);
        $this->iStatus |= ($iRes & self::F_NEGATIVE) | ($iRes ? 0 : self::F_ZERO);
        $this->iStatus |= ($iDiff & 0x100) ? 0 : self::F_CARRY;
    }


//     protected function readByteSigned(int $iAddress): int {
//         $iValue = ord($this->sMemory[$iAddress & self::MEM_MASK]);
//         return ($iValue & self::F_NEGATIVE) ? $iValue - 256 : $iValue;
//     }

    /**
     * Read a raw 16-bit value from the given address. Returns an unsigned value. Automatically handles addresses that
     * would wrap the address space.
     */
    protected function readWord(int $iAddress): int {
        return $this->oOutside->readByte($iAddress & self::MEM_MASK) |
               $this->oOutside->readByte(($iAddress + 1) & self::MEM_MASK) << 8;
    }

    /**
     * $NN
     */
    protected function addrZeroPageByte(): int {
        return $this->oOutside->readByte( // unsigned 8-bit value loaded from...
            $this->iProgramCounter + 1    // operand byte
        );
    }


    /**
     * $NNNN
     */
    protected function addrAbsoluteByte(): int {
        return $this->readWord(         // unsigned 16-bit value loaded from...
            $this->iProgramCounter + 1  // operand bytes
        );
    }

    /**
     * $NNNN,X
     */
    protected function addrAbsoluteXByte(): int {
        return $this->readWord(         // unsigned 16-bit value loaded from...
            $this->iProgramCounter + 1  // operand bytes, offset by...
        ) + ($this->iXIndex & 0xFF);    // unsigned 8-bit index in X register
    }

    /**
     * $NNNN,Y
     */
    protected function addrAbsoluteYByte(): int {
        return $this->readWord(         // unsigned 16-bit value loaded from...
            $this->iProgramCounter + 1  // operand bytes, offset by...
        ) + ($this->iYIndex & 0xFF);    // unsigned 8-bit index in Y register
    }

    /**
     * $NN,X (wraps in zero page)
     */
    protected function addrZeroPageXByte(): int {
        return (
            $this->oOutside->readByte(      // unsigned 8-bit value loaded from...
                $this->iProgramCounter + 1  //   operand byte, offset by...
            ) + $this->iXIndex              //     unsigned 8-bit value in X register...
        ) & 0xFF;                           //   wrapped to zero page
    }

    /**
     * $NN,Y (wraps in zero page)
     */
    protected function addrZeroPageYByte(): int {
        return (
            $this->oOutside->readByte(      // unsigned 8-bit value loaded from...
                $this->iProgramCounter + 1  //   operand byte, offset by...
            ) + $this->iYIndex              //     unsigned 8-bit value in Y register...
        ) & 0xFF;                           //   wrapped to zero page
    }

    /**
     * ($NN,X) (wraps in zero page)
     */
    protected function addrPreIndexZeroPageXByte(): int {
        return $this->readWord(                 // unsigned 16-bit value at address indicated by...
            (
                $this->oOutside->readByte(      //   unsigned 8-bit value loaded from...
                    $this->iProgramCounter + 1  //     operand byte, offset by...
                ) + $this->iXIndex              //       unsigned 8-bit value in X register...
            ) & 0xFF                            //     wrapped to zero page
        );
    }

    /**
     * ($NN),Y
     */
    protected function addrPostIndexZeroPageYByte(): int {
        return $this->readWord(                 // unsigned 16-bit value at address indicated by...
            $this->oOutside->readByte(          //   unsigned 8-bit value loaded from
                $this->iProgramCounter + 1      //     operand byte
            )                                   // offset by...
        ) + ($this->iYIndex & 0xFF);            //   unsigned 8-bit value in Y register
    }


    /**
     * Pull a raw byte off the stack
     */
    protected function pullByte(): int {
        $this->iStackPointer = ($this->iStackPointer + 1) & 0xFF;
        return $this->oOutside->readByte($this->iStackPointer + self::STACK_BASE);
    }

    /**
     * Push a byte on the stack
     */
    protected function pushByte(int $iValue): void {
        $this->oOutside->writeByte(self::STACK_BASE + $this->iStackPointer, $iValue & 0xFF);
        $this->iStackPointer = ($this->iStackPointer - 1) & 0xFF;
    }

    /**
     * Set the N and Z flags based on the operand
     */
    protected function updateNZ(int $iValue): void {
        $this->iStatus &= ~(self::F_NEGATIVE | self::F_ZERO);
        $this->iStatus |= ($iValue & self::F_NEGATIVE) | (($iValue & 0xFF) ? 0 : self::F_ZERO);
    }

    protected function lsrMemory(int $iAddress): void {
        $iValue   = $this->oOutside->readByte($iAddress);
        $this->iStatus &= ~self::F_CARRY;
        $this->iStatus |= ($iValue & self::F_CARRY);
        $this->updateNZ($iValue >>= 1);
        $this->oOutside->writeByte($iAddress, $iValue);
    }

    protected function aslMemory(int $iAddress): void {
        $iValue   = $this->oOutside->readByte($iAddress);
        $this->iStatus &= ~self::F_CARRY;
        $this->iStatus |= ($iValue & self::F_NEGATIVE) >> 7; // sign -> carry
        $this->updateNZ($iValue = (($iValue << 1) & 0xFF));
        $this->oOutside->writeByte($iAddress, $iValue);
    }

    protected function rolMemory(int $iAddress): void {
        $iValue   = $this->oOutside->readByte($iAddress);
        $iCarry = ($this->iStatus & self::F_CARRY);
        $this->iStatus &= ~self::F_CARRY;
        $this->iStatus |= ($iValue & self::F_NEGATIVE) >> 7; // sign -> carry
        $this->updateNZ( $iValue = ((($iValue << 1) | $iCarry) & 0xFF) );
        $this->oOutside->writeByte($iAddress, $iValue);
    }

    protected function rorMemory(int $iAddress): void {
        $iValue   = $this->oOutside->readByte($iAddress);
        $iCarry = ($this->iStatus & self::F_CARRY) << 7; // carry -> sign
        $this->iStatus &= ~self::F_CARRY;
        $this->iStatus |= ($iValue & self::F_CARRY); // carry -> carry
        $this->updateNZ( $iValue = (($iValue >> 1) | $iCarry) );
        $this->oOutside->writeByte($iAddress, $iValue);
    }

    protected function run() {
        $bRunning = true;
        $iCycles  = 0;
        $iOps     = 0;
        //$fMark    = microtime(true);
        while ($bRunning) {
            $iOpcode = $this->oOutside->readByte($this->iProgramCounter);
            $bRunning = $this->executeOpcode($iOpcode);
            $iCycles += self::OP_CYCLES[$iOpcode];
            ++$iOps;
        }
        //$fTime = microtime(true) - $fMark;

        //printf("Completed %d ops in %.6f seconds, %.2f op/s\n", $iOps, $fTime, $iOps/$fTime);
    }

    public function executeOpcode(int $iOpcode): bool {
        switch ($iOpcode) {
            case self::NOP: break;

            // Status mangling
            case self::CLC: $this->iStatus &= ~self::F_CARRY;     break;
            case self::CLD: $this->iStatus &= ~self::F_DECIMAL;   break;
            case self::CLI: $this->iStatus &= ~self::F_INTERRUPT; break;
            case self::CLV: $this->iStatus &= ~self::F_OVERFLOW;  break;
            case self::SEC: $this->iStatus |= self::F_CARRY;      break;
            case self::SED: $this->iStatus |= self::F_DECIMAL;    break;
            case self::SEI: $this->iStatus |= self::F_INTERRUPT;  break;

            // Register transfer
            case self::TAX: $this->updateNZ($this->iXIndex = $this->iAccumulator & 0xFF);  break;
            case self::TAY: $this->updateNZ($this->iYIndex = $this->iAccumulator & 0xFF);  break;
            case self::TSX: $this->updateNZ($this->iXIndex = $this->iStackPointer & 0xFF); break;
            case self::TXA: $this->updateNZ($this->iAccumulator  = $this->iXIndex & 0xFF); break;
            // klausd tests: TXS does not update NZ
            case self::TXS: $this->iStackPointer = $this->iXIndex & 0xFF; break;
            case self::TYA: $this->updateNZ($this->iAccumulator  = $this->iYIndex & 0xFF); break;

            // Stack
            case self::PHA: $this->pushByte($this->iAccumulator); break;
            case self::PHP: $this->pushByte($this->iStatus | self::F_BREAK | self::F_UNUSED); break;
            case self::PLA: $this->updateNZ($this->iAccumulator = $this->pullByte()); break;
            case self::PLP: {
                $iStatus = $this->pullByte() & ~(self::F_BREAK | self::F_UNUSED);
                $this->iStatus = ($this->iStatus & (self::F_BREAK | self::F_UNUSED)) | $iStatus;
                break;
            }

            // Decrement
            case self::DEX: $this->updateNZ($this->iXIndex = (($this->iXIndex - 1) & 0xFF)); break;
            case self::DEY: $this->updateNZ($this->iYIndex = (($this->iYIndex - 1) & 0xFF)); break;

            case self::DEC_ZP: {
                $iAddress = $this->addrZeroPageByte();
                $iValue   = ($this->oOutside->readByte($iAddress) - 1) & 0xFF;
                $this->updateNZ($iValue);
                $this->oOutside->writeByte($iAddress, $iValue);
                break;
            }

            case self::DEC_ZPX: {
                $iAddress = $this->addrZeroPageXByte();
                $iValue   = ($this->oOutside->readByte($iAddress) - 1) & 0xFF;
                $this->updateNZ($iValue);
                $this->oOutside->writeByte($iAddress, $iValue);
                break;
            }

            case self::DEC_AB: {
                $iAddress = $this->addrAbsoluteByte();
                $iValue   = ($this->oOutside->readByte($iAddress) - 1) & 0xFF;
                $this->updateNZ($iValue);
                $this->oOutside->writeByte($iAddress, $iValue);
                break;
            }

            case self::DEC_ABX: {
                $iAddress = $this->addrAbsoluteXByte();
                $iValue   = ($this->oOutside->readByte($iAddress) - 1) & 0xFF;
                $this->updateNZ($iValue);
                $this->oOutside->writeByte($iAddress, $iValue);
                break;
            }


            // Increment
            case self::INX: $this->updateNZ($this->iXIndex = (($this->iXIndex + 1) & 0xFF)); break;
            case self::INY: $this->updateNZ($this->iYIndex = (($this->iYIndex + 1) & 0xFF)); break;

            case self::INC_ZP: {
                $iAddress = $this->addrZeroPageByte();
                $iValue   = ($this->oOutside->readByte($iAddress) + 1) & 0xFF;
                $this->updateNZ($iValue );
                $this->oOutside->writeByte($iAddress, $iValue);
                break;
            }

            case self::INC_ZPX: {
                $iAddress = $this->addrZeroPageXByte();
                $iValue   = ($this->oOutside->readByte($iAddress) + 1) & 0xFF;
                $this->updateNZ($iValue);
                $this->oOutside->writeByte($iAddress, $iValue);
                break;
            }

            case self::INC_AB: {
                $iAddress = $this->addrAbsoluteByte();
                $iValue   = ($this->oOutside->readByte($iAddress) + 1) & 0xFF;
                $this->updateNZ($iValue);
                $this->oOutside->writeByte($iAddress, $iValue);
                break;
            }

            case self::INC_ABX: {
                $iAddress = $this->addrAbsoluteXByte();
                $iValue   = ($this->oOutside->readByte($iAddress) + 1) & 0xFF;
                $this->updateNZ($iValue);
                $this->oOutside->writeByte($iAddress, $iValue);
                break;
            }

            // Load Accumulator
            case self::LDA_IM:  $this->updateNZ(
                $this->iAccumulator = $this->oOutside->readByte($this->iProgramCounter + 1)
            ); break;

            case self::LDA_ZP:  $this->updateNZ(
                $this->iAccumulator = $this->oOutside->readByte($this->addrZeroPageByte())
            ); break;

            case self::LDA_ZPX: $this->updateNZ(
                $this->iAccumulator = $this->oOutside->readByte($this->addrZeroPageXByte())
            ); break;

            case self::LDA_AB:  $this->updateNZ(
                $this->iAccumulator = $this->oOutside->readByte($this->addrAbsoluteByte())
            ); break;

            case self::LDA_ABX: $this->updateNZ(
                $this->iAccumulator = $this->oOutside->readByte($this->addrAbsoluteXByte())
            ); break;

            case self::LDA_ABY: $this->updateNZ(
                $this->iAccumulator = $this->oOutside->readByte($this->addrAbsoluteYByte())
            ); break;

            case self::LDA_IX:  $this->updateNZ(
                $this->iAccumulator = $this->oOutside->readByte($this->addrPreIndexZeroPageXByte())
            ); break;

            case self::LDA_IY:  $this->updateNZ(
                $this->iAccumulator = $this->oOutside->readByte($this->addrPostIndexZeroPageYByte())
            ); break;

            // Store Accumulator
            case self::STA_ZP:  $this->oOutside->writeByte($this->addrZeroPageByte(),  $this->iAccumulator); break;
            case self::STA_ZPX: $this->oOutside->writeByte($this->addrZeroPageXByte(), $this->iAccumulator); break;
            case self::STA_AB:  $this->oOutside->writeByte($this->addrAbsoluteByte(),  $this->iAccumulator); break;
            case self::STA_ABX: $this->oOutside->writeByte($this->addrAbsoluteXByte(), $this->iAccumulator); break;
            case self::STA_ABY: $this->oOutside->writeByte($this->addrAbsoluteYByte(), $this->iAccumulator); break;
            case self::STA_IX:  $this->oOutside->writeByte($this->addrPreIndexZeroPageXByte(),  $this->iAccumulator); break;
            case self::STA_IY:  $this->oOutside->writeByte($this->addrPostIndexZeroPageYByte(), $this->iAccumulator); break;

            // Load X
            case self::LDX_IM:  $this->updateNZ($this->iXIndex = $this->oOutside->readByte($this->iProgramCounter + 1)); break;
            case self::LDX_ZP:  $this->updateNZ($this->iXIndex = $this->oOutside->readByte($this->addrZeroPageByte()));  break;
            case self::LDX_ZPY: $this->updateNZ($this->iXIndex = $this->oOutside->readByte($this->addrZeroPageYByte())); break;
            case self::LDX_AB:  $this->updateNZ($this->iXIndex = $this->oOutside->readByte($this->addrAbsoluteByte()));  break;
            case self::LDX_ABY: $this->updateNZ($this->iXIndex = $this->oOutside->readByte($this->addrAbsoluteYByte())); break;

            // Store X
            case self::STX_ZP:  $this->oOutside->writeByte($this->addrZeroPageByte(),  $this->iXIndex); break;
            case self::STX_ZPY: $this->oOutside->writeByte($this->addrZeroPageYByte(), $this->iXIndex); break;
            case self::STX_AB:  $this->oOutside->writeByte($this->addrAbsoluteByte(),  $this->iXIndex); break;

            // Load Y
            case self::LDY_IM:  $this->updateNZ($this->iYIndex = $this->oOutside->readByte($this->iProgramCounter + 1)); break;
            case self::LDY_ZP:  $this->updateNZ($this->iYIndex = $this->oOutside->readByte($this->addrZeroPageByte()));  break;
            case self::LDY_ZPX: $this->updateNZ($this->iYIndex = $this->oOutside->readByte($this->addrZeroPageXByte())); break;
            case self::LDY_AB:  $this->updateNZ($this->iYIndex = $this->oOutside->readByte($this->addrAbsoluteByte()));  break;
            case self::LDY_ABX: $this->updateNZ($this->iYIndex = $this->oOutside->readByte($this->addrAbsoluteXByte())); break;

            // Store Y
            case self::STY_ZP:  $this->oOutside->writeByte($this->addrZeroPageByte(),  $this->iYIndex); break;
            case self::STY_ZPX: $this->oOutside->writeByte($this->addrZeroPageXByte(), $this->iYIndex); break;
            case self::STY_AB:  $this->oOutside->writeByte($this->addrAbsoluteByte(),  $this->iYIndex); break;

            // Logic Ops...
            case self::AND_IM:  $this->updateNZ($this->iAccumulator &= $this->oOutside->readByte($this->iProgramCounter + 1)); break;
            case self::AND_ZP:  $this->updateNZ($this->iAccumulator &= $this->oOutside->readByte($this->addrZeroPageByte()));  break;
            case self::AND_ZPX: $this->updateNZ($this->iAccumulator &= $this->oOutside->readByte($this->addrZeroPageXByte())); break;
            case self::AND_AB:  $this->updateNZ($this->iAccumulator &= $this->oOutside->readByte($this->addrAbsoluteByte()));  break;
            case self::AND_ABX: $this->updateNZ($this->iAccumulator &= $this->oOutside->readByte($this->addrAbsoluteXByte())); break;
            case self::AND_ABY: $this->updateNZ($this->iAccumulator &= $this->oOutside->readByte($this->addrAbsoluteYByte())); break;
            case self::AND_IX:  $this->updateNZ($this->iAccumulator &= $this->oOutside->readByte($this->addrPreIndexZeroPageXByte()));  break;
            case self::AND_IY:  $this->updateNZ($this->iAccumulator &= $this->oOutside->readByte($this->addrPostIndexZeroPageYByte())); break;
            case self::ORA_IM:  $this->updateNZ($this->iAccumulator |= $this->oOutside->readByte($this->iProgramCounter + 1)); break;
            case self::ORA_ZP:  $this->updateNZ($this->iAccumulator |= $this->oOutside->readByte($this->addrZeroPageByte()));  break;
            case self::ORA_ZPX: $this->updateNZ($this->iAccumulator |= $this->oOutside->readByte($this->addrZeroPageXByte())); break;
            case self::ORA_AB:  $this->updateNZ($this->iAccumulator |= $this->oOutside->readByte($this->addrAbsoluteByte()));  break;
            case self::ORA_ABX: $this->updateNZ($this->iAccumulator |= $this->oOutside->readByte($this->addrAbsoluteXByte())); break;
            case self::ORA_ABY: $this->updateNZ($this->iAccumulator |= $this->oOutside->readByte($this->addrAbsoluteYByte())); break;
            case self::ORA_IX:  $this->updateNZ($this->iAccumulator |= $this->oOutside->readByte($this->addrPreIndexZeroPageXByte()));  break;
            case self::ORA_IY:  $this->updateNZ($this->iAccumulator |= $this->oOutside->readByte($this->addrPostIndexZeroPageYByte())); break;
            case self::EOR_IM:  $this->updateNZ($this->iAccumulator ^= $this->oOutside->readByte($this->iProgramCounter + 1)); break;
            case self::EOR_ZP:  $this->updateNZ($this->iAccumulator ^= $this->oOutside->readByte($this->addrZeroPageByte()));  break;
            case self::EOR_ZPX: $this->updateNZ($this->iAccumulator ^= $this->oOutside->readByte($this->addrZeroPageXByte())); break;
            case self::EOR_AB:  $this->updateNZ($this->iAccumulator ^= $this->oOutside->readByte($this->addrAbsoluteByte()));  break;
            case self::EOR_ABX: $this->updateNZ($this->iAccumulator ^= $this->oOutside->readByte($this->addrAbsoluteXByte())); break;
            case self::EOR_ABY: $this->updateNZ($this->iAccumulator ^= $this->oOutside->readByte($this->addrAbsoluteYByte())); break;
            case self::EOR_IX:  $this->updateNZ($this->iAccumulator ^= $this->oOutside->readByte($this->addrPreIndexZeroPageXByte()));  break;
            case self::EOR_IY:  $this->updateNZ($this->iAccumulator ^= $this->oOutside->readByte($this->addrPostIndexZeroPageYByte())); break;

            // Arithmetuc shift left
            case self::ASL_A: {
                $this->iStatus &= ~self::F_CARRY;
                $this->iStatus |= ($this->iAccumulator & self::F_NEGATIVE) >> 7; // sign -> carry
                $this->updateNZ($this->iAccumulator = (($this->iAccumulator << 1) & 0xFF));
                break;
            }

            case self::ASL_ZP:  $this->aslMemory($this->addrZeroPageByte()); break;
            case self::ASL_ZPX: $this->aslMemory($this->addrZeroPageXByte()); break;
            case self::ASL_AB:  $this->aslMemory($this->addrAbsoluteByte()); break;
            case self::ASL_ABX: $this->aslMemory($this->addrAbsoluteXByte()); break;

            // Logical shift right
            case self::LSR_A: {
                $this->iStatus &= ~self::F_CARRY;
                $this->iStatus |= ($this->iAccumulator & self::F_CARRY);
                $this->updateNZ($this->iAccumulator >>= 1);
                break;
            }

            case self::ROL_A: {
                $iCarry = ($this->iStatus & self::F_CARRY);
                $this->iStatus &= ~self::F_CARRY;
                $this->iStatus |= ($this->iAccumulator & self::F_NEGATIVE) >> 7; // sign -> carry
                $this->updateNZ( $this->iAccumulator = ((($this->iAccumulator << 1) | $iCarry) & 0xFF) );
                break;
            }

            case self::ROL_ZP:  $this->rolMemory($this->addrZeroPageByte()); break;
            case self::ROL_ZPX: $this->rolMemory($this->addrZeroPageXByte()); break;
            case self::ROL_AB:  $this->rolMemory($this->addrAbsoluteByte()); break;
            case self::ROL_ABX: $this->rolMemory($this->addrAbsoluteXByte()); break;

            case self::ROR_A: {
                $iCarry = ($this->iStatus & self::F_CARRY) << 7; // carry -> sign
                $this->iStatus &= ~self::F_CARRY;
                $this->iStatus |= ($this->iAccumulator & self::F_CARRY); // carry -> carry
                $this->updateNZ($this->iAccumulator = (($this->iAccumulator >> 1) | $iCarry));
                break;
            }

            case self::ROR_ZP:  $this->rorMemory($this->addrZeroPageByte()); break;
            case self::ROR_ZPX: $this->rorMemory($this->addrZeroPageXByte()); break;
            case self::ROR_AB:  $this->rorMemory($this->addrAbsoluteByte()); break;
            case self::ROR_ABX: $this->rorMemory($this->addrAbsoluteXByte()); break;

            case self::LSR_ZP:  $this->lsrMemory($this->addrZeroPageByte()); break;
            case self::LSR_ZPX: $this->lsrMemory($this->addrZeroPageXByte()); break;
            case self::LSR_AB:  $this->lsrMemory($this->addrAbsoluteByte()); break;
            case self::LSR_ABX: $this->lsrMemory($this->addrAbsoluteXByte()); break;


            // Addition
            // A + M + C
            case self::ADC_IM:  $this->addByteWithCarry($this->oOutside->readByte($this->iProgramCounter + 1)); break;
            case self::ADC_ZP:  $this->addByteWithCarry($this->oOutside->readByte($this->addrZeroPageByte()));  break;
            case self::ADC_ZPX: $this->addByteWithCarry($this->oOutside->readByte($this->addrZeroPageXByte())); break;
            case self::ADC_AB:  $this->addByteWithCarry($this->oOutside->readByte($this->addrAbsoluteByte()));  break;
            case self::ADC_ABX: $this->addByteWithCarry($this->oOutside->readByte($this->addrAbsoluteXByte())); break;
            case self::ADC_ABY: $this->addByteWithCarry($this->oOutside->readByte($this->addrAbsoluteYByte())); break;
            case self::ADC_IX:  $this->addByteWithCarry($this->oOutside->readByte($this->addrPreIndexZeroPageXByte()));  break;
            case self::ADC_IY:  $this->addByteWithCarry($this->oOutside->readByte($this->addrPostIndexZeroPageYByte())); break;

            // Subtract
            // A - M - B => A + (255 - M) - (1 - C) => A + ~M + C
            case self::SBC_IM:  $this->subByteWithCarry($this->oOutside->readByte($this->iProgramCounter + 1)); break;
            case self::SBC_ZP:  $this->subByteWithCarry($this->oOutside->readByte($this->addrZeroPageByte()));  break;
            case self::SBC_ZPX: $this->subByteWithCarry($this->oOutside->readByte($this->addrZeroPageXByte())); break;
            case self::SBC_AB:  $this->subByteWithCarry($this->oOutside->readByte($this->addrAbsoluteByte()));  break;
            case self::SBC_ABX: $this->subByteWithCarry($this->oOutside->readByte($this->addrAbsoluteXByte())); break;
            case self::SBC_ABY: $this->subByteWithCarry($this->oOutside->readByte($this->addrAbsoluteYByte())); break;
            case self::SBC_IX:  $this->subByteWithCarry($this->oOutside->readByte($this->addrPreIndexZeroPageXByte()));  break;
            case self::SBC_IY:  $this->subByteWithCarry($this->oOutside->readByte($this->addrPostIndexZeroPageYByte())); break;

            // Compare
            // A - M
            case self::CMP_IM:  $this->cmpByte($this->iAccumulator, $this->oOutside->readByte($this->iProgramCounter + 1)); break;
            case self::CMP_ZP:  $this->cmpByte($this->iAccumulator, $this->oOutside->readByte($this->addrZeroPageByte()));  break;
            case self::CMP_ZPX: $this->cmpByte($this->iAccumulator, $this->oOutside->readByte($this->addrZeroPageXByte())); break;
            case self::CMP_AB:  $this->cmpByte($this->iAccumulator, $this->oOutside->readByte($this->addrAbsoluteByte()));  break;
            case self::CMP_ABX: $this->cmpByte($this->iAccumulator, $this->oOutside->readByte($this->addrAbsoluteXByte())); break;
            case self::CMP_ABY: $this->cmpByte($this->iAccumulator, $this->oOutside->readByte($this->addrAbsoluteYByte())); break;
            case self::CMP_IX:  $this->cmpByte($this->iAccumulator, $this->oOutside->readByte($this->addrPreIndexZeroPageXByte()));  break;
            case self::CMP_IY:  $this->cmpByte($this->iAccumulator, $this->oOutside->readByte($this->addrPostIndexZeroPageYByte())); break;

            case self::CPX_IM:  $this->cmpByte($this->iXIndex, $this->oOutside->readByte($this->iProgramCounter + 1)); break;
            case self::CPX_ZP:  $this->cmpByte($this->iXIndex, $this->oOutside->readByte($this->addrZeroPageByte()));  break;
            case self::CPX_AB:  $this->cmpByte($this->iXIndex, $this->oOutside->readByte($this->addrAbsoluteByte()));  break;

            case self::CPY_IM:  $this->cmpByte($this->iYIndex, $this->oOutside->readByte($this->iProgramCounter + 1)); break;
            case self::CPY_ZP:  $this->cmpByte($this->iYIndex, $this->oOutside->readByte($this->addrZeroPageByte()));  break;
            case self::CPY_AB:  $this->cmpByte($this->iYIndex, $this->oOutside->readByte($this->addrAbsoluteByte()));  break;

            case self::BIT_ZP: {
                $iMem = $this->oOutside->readByte($this->addrZeroPageByte());
                $this->iStatus &= ~(self::F_NEGATIVE | self::F_OVERFLOW | self::F_ZERO);
                $this->iStatus |= ($iMem & (self::F_NEGATIVE|self::F_OVERFLOW)) | (
                    $iMem & $this->iAccumulator ? 0 : self::F_ZERO
                );
                break;
            }
            case self::BIT_AB: {
                $iMem = $this->oOutside->readByte($this->addrAbsoluteByte());
                $this->iStatus &= ~(self::F_NEGATIVE | self::F_OVERFLOW | self::F_ZERO);
                $this->iStatus |= ($iMem & (self::F_NEGATIVE|self::F_OVERFLOW)) | (
                    $iMem & $this->iAccumulator ? 0 : self::F_ZERO
                );
                break;

            }


            // Conditional
            case self::BCC: {
                $this->iProgramCounter += (!($this->iStatus & self::F_CARRY)) ?
                    self::signByte($this->oOutside->readByte($this->iProgramCounter + 1))
                    : 0;
                break;
            }

            case self::BCS: {
                $this->iProgramCounter += ($this->iStatus & self::F_CARRY) ?
                    self::signByte($this->oOutside->readByte($this->iProgramCounter + 1))
                    : 0;
                break;
            }

            case self::BEQ: {
                $this->iProgramCounter += ($this->iStatus & self::F_ZERO) ?
                    self::signByte($this->oOutside->readByte($this->iProgramCounter + 1))
                    : 0;
                break;
            }

            case self::BNE: {
                $this->iProgramCounter += (!($this->iStatus & self::F_ZERO)) ?
                    self::signByte($this->oOutside->readByte($this->iProgramCounter + 1))
                    : 0;
                break;
            }

            case self::BMI: {
                $this->iProgramCounter += ($this->iStatus & self::F_NEGATIVE) ?
                    self::signByte($this->oOutside->readByte($this->iProgramCounter + 1))
                    : 0;
                break;
            }

            case self::BPL: {
                $this->iProgramCounter += (!($this->iStatus & self::F_NEGATIVE)) ?
                    self::signByte($this->oOutside->readByte($this->iProgramCounter + 1))
                    : 0;
                break;
            }

            case self::BVC: {
                $this->iProgramCounter += (!($this->iStatus & self::F_OVERFLOW)) ?
                    self::signByte($this->oOutside->readByte($this->iProgramCounter + 1))
                    : 0;
                break;
            }

            case self::BVS: {
                $this->iProgramCounter += ($this->iStatus & self::F_OVERFLOW) ?
                    self::signByte($this->oOutside->readByte($this->iProgramCounter + 1))
                    : 0;
                break;
            }

            // unconditional
            case self::JMP_AB: {
                //$iCycles += self::OP_CYCLES[$iOpcode];

                $iNewProgramCounter = $this->readWord($this->iProgramCounter + 1);

                if ($iNewProgramCounter === $this->iProgramCounter) {
                    // Hard Infinite Loop
                    return false;
                }

                 $this->iProgramCounter = $iNewProgramCounter;

                // Avoid the program counter update, since we releaded it anyway
                return true;
            }

            case self::JMP_IN: {
                // Emulate the 6502 indirect jump bug with respect to page boundaries.
                $iPointerAddress = $this->readWord($this->iProgramCounter + 1);
                if (0xFF === ($iPointerAddress & 0xFF)) {
                    $iAddress = $this->oOutside->readByte($iPointerAddress);
                    $iAddress |= $this->oOutside->readByte($iPointerAddress & 0xFF00) << 8;
                    $this->iProgramCounter = $this->readWord($iAddress);
                } else {
                    $this->iProgramCounter = $this->readWord($iPointerAddress);
                }
                return true;
            }

            case self::JSR_AB: {
                // Note the 6502 notion of the return address is actually the address of the last byte of
                // the operation.
                $iReturnAddress = ($this->iProgramCounter + 2) & self::MEM_MASK;
                $this->pushByte($iReturnAddress >> 8);
                $this->pushByte($iReturnAddress & 0xFF);
                $this->iProgramCounter = $this->readWord($this->iProgramCounter + 1);
                return true;
            }

            case self::RTS: {
                $iReturnAddress  = $this->pullByte();
                $iReturnAddress |= ($this->pullByte() << 8);
                $this->iProgramCounter = $iReturnAddress + 1;
                return true;
            }

            case self::RTI: {
                // Pull SR but ignore bit 5
                $iStatus = $this->pullByte() & ~(self::F_UNUSED|self::F_BREAK); // clear unused only
                $this->iStatus &= (self::F_UNUSED|self::F_BREAK); // clear all but unused flag
                $this->iStatus |= $iStatus;

                // Pull PC
                $iReturnAddress  = $this->pullByte();
                $iReturnAddress |= ($this->pullByte() << 8);
                $this->iProgramCounter = $iReturnAddress;// + 1;
                return true;
            }

            case self::BRK: {
                // Push PC+2 as return address
                $iValAddress   = $this->iProgramCounter + 1;
                $iReturnAddress = ($this->iProgramCounter + 2) & self::MEM_MASK;
                $this->pushByte($iReturnAddress >> 8);
                $this->pushByte($iReturnAddress & 0xFF);

                // Push SR
                $this->pushByte($this->iStatus|self::F_BREAK|self::F_UNUSED);

                // Reload PC from IRQ vector
                $this->iProgramCounter = $this->readWord(self::VEC_IRQ);

                // Set interrupted status. Is this the correct location?
                $this->iStatus |= self::F_INTERRUPT;
                return true;
            }

            default:
                return false;
                break;
        }
        $this->iProgramCounter = $this->iProgramCounter + self::OP_SIZE[$iOpcode];
        return true;
    }
}
