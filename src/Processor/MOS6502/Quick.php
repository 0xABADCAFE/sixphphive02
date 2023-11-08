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
use ABadCafe\SixPHPhive02\Device\IByteConv;
use ABadCafe\SixPHPhive02\I8BitProcessor;
use LogicException;

/**
 * MOS6502ProcessorQuick
 *
 * Faster implementation. Foregoes the ability to use an external address space. Memory is internalised directly
 * removing a significant indirection. A number of simpler addressing modes (absolute, zeropage) are inlined.
 *
 * The end result is rather a lot less readable.
 */
class Quick extends Base implements IByteConv {

    protected string $sMemory;

    public function __construct() {
        $this->hardReset();
    }

    /**
     * @inheritDoc
     * @see IDevice
     */
    public function getName(): string {
        return 'MOS 6502 (quicker)';
    }

    /**
     * @inheritDoc
     * @see IDevice
     */
    public function softReset(): self {
        $this->reset();
        return $this;
    }

    /**
     * @inheritDoc
     * @see IDevice
     */
    public function hardReset(): self {
        $this->sMemory = str_repeat("\0", self::MEM_SIZE);
        $this->reset();
        return $this;
    }

    /**
     * Attach to the outside world.
     */
    public function setAddressSpace(IByteAccessible $oOutside): self {
        return $this;
    }

    public function setMemory(string $sBinary, int $iAddress): self {
        $iAddress &= self::MEM_MASK;
        $iByteLength = strlen($sBinary);

        if ($iAddress + $iByteLength > self::MEM_SIZE) {
            $iByteLength = self::MEM_SIZE - $iAddress;
        }
        $this->sMemory = substr_replace($this->sMemory, $sBinary, $iAddress, $iByteLength);
        return $this;
    }

    /**
     * Read a raw 16-bit value from the given address. Returns an unsigned value. Automatically handles addresses that
     * would wrap the address space.
     */
    protected function readWord(int $iAddress): int {
        return self::AORD[$this->sMemory[$iAddress & self::MEM_MASK]] |
               self::AORD[$this->sMemory[($iAddress + 1) & self::MEM_MASK]] << 8;
    }

    /**
     * $NN
     *
     * Note: usages of this have been inlined for performance
     */
    protected function addrZeroPageByte(): int {
        return self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]];
    }

    /**
     * $NNNN
     *
     * Note: usages of this have been inlined for performance
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
            self::AORD[$this->sMemory[      // unsigned 8-bit value loaded from...
                ($this->iProgramCounter + 1) & self::MEM_MASK  //   operand byte, offset by...
            ]] + $this->iXIndex              //     unsigned 8-bit value in X register...
        ) & 0xFF;                           //   wrapped to zero page
    }

    /**
     * $NN,Y (wraps in zero page)
     */
    protected function addrZeroPageYByte(): int {
        return (
            self::AORD[$this->sMemory[     // unsigned 8-bit value loaded from...
                ($this->iProgramCounter + 1) & self::MEM_MASK  //   operand byte, offset by...
            ]] + $this->iYIndex              //     unsigned 8-bit value in Y register...
        ) & 0xFF;                           //   wrapped to zero page
    }

    /**
     * ($NN,X) (wraps in zero page)
     */
    protected function addrPreIndexZeroPageXByte(): int {
        return $this->readWord(                 // unsigned 16-bit value at address indicated by...
            (
                self::AORD[$this->sMemory[      //   unsigned 8-bit value loaded from...
                    ($this->iProgramCounter + 1) & self::MEM_MASK  //     operand byte, offset by...
                ]] + $this->iXIndex              //       unsigned 8-bit value in X register...
            ) & 0xFF                            //     wrapped to zero page
        );
    }

    /**
     * ($NN),Y
     */
    protected function addrPostIndexZeroPageYByte(): int {
        return $this->readWord(                 // unsigned 16-bit value at address indicated by...
            self::AORD[$this->sMemory[          //   unsigned 8-bit value loaded from
                ($this->iProgramCounter + 1) & self::MEM_MASK      //     operand byte
            ]]                                   // offset by...
        ) + ($this->iYIndex & 0xFF);            //   unsigned 8-bit value in Y register
    }


    /**
     * Pull a raw byte off the stack
     */
    protected function pullByte(): int {
        $this->iStackPointer = ($this->iStackPointer + 1) & 0xFF;
        return self::AORD[$this->sMemory[$this->iStackPointer + self::STACK_BASE]];
    }

    /**
     * Push a byte on the stack
     */
    protected function pushByte(int $iValue): void {
        $this->writeByte(self::STACK_BASE + $this->iStackPointer, $iValue & 0xFF);
        $this->iStackPointer = ($this->iStackPointer - 1) & 0xFF;
    }

    /**
     * Set the N and Z flags based on the operand
     */
    protected function updateNZ(int $iValue): void {
        $this->iStatus &= self::F_CLR_NZ;
        $this->iStatus |= (($iValue & 0xFF) ? ($iValue & self::F_NEGATIVE) : self::F_ZERO);
    }

    protected function lsrMemory(int $iAddress): void {
        $this->writeByte($iAddress, $this->shiftRightWithCarry(self::AORD[$this->sMemory[$iAddress]]));
    }

    protected function aslMemory(int $iAddress): void {
        $this->writeByte($iAddress, $this->shiftLeftWithCarry(self::AORD[$this->sMemory[$iAddress]]));
    }

    protected function rolMemory(int $iAddress): void {
        $this->writeByte($iAddress, $this->rotateLeftWithCarry(self::AORD[$this->sMemory[$iAddress]]));
    }

    protected function rorMemory(int $iAddress): void {
        $this->writeByte($iAddress, $this->rotateRightWithCarry(self::AORD[$this->sMemory[$iAddress]]));
    }

    protected function decodeInstruction(int $iFrom): string {
        $iFrom &= self::MEM_MASK;
        $iOpcode = self::AORD[$this->sMemory[$iFrom]];
        if (isset(IInsructionDisassembly::OP_DISASM[$iOpcode])) {
            return sprintf(
                IInsructionDisassembly::OP_DISASM[$iOpcode],
                self::AORD[$this->sMemory[($iFrom + 1) & self::MEM_MASK]],
                self::AORD[$this->sMemory[($iFrom + 2) & self::MEM_MASK]]
            );
        }
        return sprintf("\$%02X", $iOpcode);
    }

    protected function run() {
        $bRunning = true;
        $iCycles  = 0;
        $fMark    = microtime(true);
        while ($bRunning) {
            $iLastPC = $this->iProgramCounter;
            $iOpcode = self::AORD[$this->sMemory[$this->iProgramCounter & self::MEM_MASK]];

            // Exit on infinite loop detection
            $bRunning = $this->executeOpcode($iOpcode) && $iLastPC != $this->iProgramCounter;
            $iCycles += self::OP_CYCLES[$iOpcode];
        }
        $fTime = microtime(true) - $fMark;

        printf("%4X: %s\n", $this->iProgramCounter, $this->decodeInstruction($this->iProgramCounter));

        printf("Completed %d cycles in %.6f seconds, %.2f op/s\n", $iCycles, $fTime, $iCycles/$fTime);
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
            case self::TAX: $this->updateNZ($this->iXIndex = $this->iAccumulator);  break;
            case self::TAY: $this->updateNZ($this->iYIndex = $this->iAccumulator);  break;
            case self::TSX: $this->updateNZ($this->iXIndex = $this->iStackPointer); break;
            case self::TXA: $this->updateNZ($this->iAccumulator  = $this->iXIndex); break;
            // klausd tests: TXS does not update NZ
            case self::TXS: $this->iStackPointer = $this->iXIndex /*& 0xFF*/; break;
            case self::TYA: $this->updateNZ($this->iAccumulator  = $this->iYIndex); break;

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
                $iAddress = self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]];
                $iValue   = (self::AORD[$this->sMemory[$iAddress]] - 1) & 0xFF;
                $this->updateNZ($iValue);
                $this->writeByte($iAddress, $iValue);
                break;
            }

            case self::DEC_ZPX: {
                $iAddress = $this->addrZeroPageXByte();
                $iValue   = (self::AORD[$this->sMemory[$iAddress]] - 1) & 0xFF;
                $this->updateNZ($iValue);
                $this->writeByte($iAddress, $iValue);
                break;
            }

            case self::DEC_AB: {
                $iAddress = $this->readWord($this->iProgramCounter + 1);
                $iValue   = (self::AORD[$this->sMemory[$iAddress]] - 1) & 0xFF;
                $this->updateNZ($iValue);
                $this->writeByte($iAddress, $iValue);
                break;
            }

            case self::DEC_ABX: {
                $iAddress = $this->addrAbsoluteXByte();
                $iValue   = (self::AORD[$this->sMemory[$iAddress]] - 1) & 0xFF;
                $this->updateNZ($iValue);
                $this->writeByte($iAddress, $iValue);
                break;
            }


            // Increment
            case self::INX: $this->updateNZ($this->iXIndex = (($this->iXIndex + 1) & 0xFF)); break;
            case self::INY: $this->updateNZ($this->iYIndex = (($this->iYIndex + 1) & 0xFF)); break;

            case self::INC_ZP: {
                $iAddress = self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]];
                $iValue   = (self::AORD[$this->sMemory[$iAddress]] + 1) & 0xFF;
                $this->updateNZ($iValue );
                $this->writeByte($iAddress, $iValue);
                break;
            }

            case self::INC_ZPX: {
                $iAddress = $this->addrZeroPageXByte();
                $iValue   = (self::AORD[$this->sMemory[$iAddress]] + 1) & 0xFF;
                $this->updateNZ($iValue);
                $this->writeByte($iAddress, $iValue);
                break;
            }

            case self::INC_AB: {
                $iAddress = $this->readWord($this->iProgramCounter + 1);
                $iValue   = (self::AORD[$this->sMemory[$iAddress]] + 1) & 0xFF;
                $this->updateNZ($iValue);
                $this->writeByte($iAddress, $iValue);
                break;
            }

            case self::INC_ABX: {
                $iAddress = $this->addrAbsoluteXByte();
                $iValue   = (self::AORD[$this->sMemory[$iAddress]] + 1) & 0xFF;
                $this->updateNZ($iValue);
                $this->writeByte($iAddress, $iValue);
                break;
            }

            // Load Accumulator
            case self::LDA_IM:
                $this->updateNZ(
                    $this->iAccumulator = self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]
                );
                break;

            case self::LDA_ZP:
                $this->updateNZ(
                    $this->iAccumulator = self::AORD[$this->sMemory[self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]]]
                );
                break;

            case self::LDA_ZPX:
                $this->updateNZ(
                    $this->iAccumulator = self::AORD[$this->sMemory[$this->addrZeroPageXByte()]]
                );
                break;

            case self::LDA_AB:
                $this->updateNZ(
                    $this->iAccumulator = self::AORD[$this->sMemory[$this->readWord($this->iProgramCounter + 1)]]
                );
                break;

            case self::LDA_ABX:
                $this->updateNZ(
                    $this->iAccumulator = self::AORD[$this->sMemory[$this->addrAbsoluteXByte()]]
                );
                break;

            case self::LDA_ABY:
                $this->updateNZ(
                    $this->iAccumulator = self::AORD[$this->sMemory[$this->addrAbsoluteYByte()]]
                );
                break;

            case self::LDA_IX:
                $this->updateNZ(
                    $this->iAccumulator = self::AORD[$this->sMemory[$this->addrPreIndexZeroPageXByte()]]
                );
                break;

            case self::LDA_IY:
                $this->updateNZ(
                    $this->iAccumulator = self::AORD[$this->sMemory[$this->addrPostIndexZeroPageYByte()]]
                );
                break;

            // Store Accumulator
            case self::STA_ZP:  $this->writeByte(self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]],  $this->iAccumulator); break;
            case self::STA_ZPX: $this->writeByte($this->addrZeroPageXByte(), $this->iAccumulator); break;
            case self::STA_AB:  $this->writeByte($this->readWord($this->iProgramCounter + 1),  $this->iAccumulator); break;
            case self::STA_ABX: $this->writeByte($this->addrAbsoluteXByte(), $this->iAccumulator); break;
            case self::STA_ABY: $this->writeByte($this->addrAbsoluteYByte(), $this->iAccumulator); break;
            case self::STA_IX:  $this->writeByte($this->addrPreIndexZeroPageXByte(),  $this->iAccumulator); break;
            case self::STA_IY:  $this->writeByte($this->addrPostIndexZeroPageYByte(), $this->iAccumulator); break;

            // Load X
            case self::LDX_IM:
                $this->updateNZ(
                    $this->iXIndex = self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]
                );
                break;

            case self::LDX_ZP:
                $this->updateNZ(
                    $this->iXIndex = self::AORD[$this->sMemory[self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]]]
                );
                break;

            case self::LDX_ZPY:
                $this->updateNZ(
                    $this->iXIndex = self::AORD[$this->sMemory[$this->addrZeroPageYByte()]]
                );
                break;

            case self::LDX_AB:
                $this->updateNZ(
                    $this->iXIndex = self::AORD[$this->sMemory[$this->readWord($this->iProgramCounter + 1)]]
                );
                break;

            case self::LDX_ABY:
                $this->updateNZ(
                    $this->iXIndex = self::AORD[$this->sMemory[$this->addrAbsoluteYByte()]]
                );
                break;

            // Store X
            case self::STX_ZP:  $this->writeByte(self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]],  $this->iXIndex); break;
            case self::STX_ZPY: $this->writeByte($this->addrZeroPageYByte(), $this->iXIndex); break;
            case self::STX_AB:  $this->writeByte($this->readWord($this->iProgramCounter + 1),  $this->iXIndex); break;

            // Load Y
            case self::LDY_IM:
                $this->updateNZ(
                    $this->iYIndex = self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]
                );
                break;

            case self::LDY_ZP:
                $this->updateNZ(
                    $this->iYIndex = self::AORD[$this->sMemory[self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]]]
                );
                break;

            case self::LDY_ZPX:
                $this->updateNZ(
                    $this->iYIndex = self::AORD[$this->sMemory[$this->addrZeroPageXByte()]]
                );
                break;

            case self::LDY_AB:  $this->updateNZ(
                $this->iYIndex = self::AORD[$this->sMemory[$this->readWord($this->iProgramCounter + 1)]]
            );  break;

            case self::LDY_ABX:
                $this->updateNZ(
                    $this->iYIndex = self::AORD[$this->sMemory[$this->addrAbsoluteXByte()]]
                );
                break;

            // Store Y
            case self::STY_ZP:  $this->writeByte(self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]],  $this->iYIndex); break;
            case self::STY_ZPX: $this->writeByte($this->addrZeroPageXByte(), $this->iYIndex); break;
            case self::STY_AB:  $this->writeByte($this->readWord($this->iProgramCounter + 1),  $this->iYIndex); break;

            // Logic Ops...
            case self::AND_IM:
                $this->updateNZ(
                    $this->iAccumulator &= self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]
                );
                break;

            case self::AND_ZP:
                $this->updateNZ(
                    $this->iAccumulator &= self::AORD[$this->sMemory[self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]]]
                );
                break;

            case self::AND_ZPX:
                $this->updateNZ(
                    $this->iAccumulator &= self::AORD[$this->sMemory[$this->addrZeroPageXByte()]]
                );
                break;

            case self::AND_AB:
                $this->updateNZ(
                    $this->iAccumulator &= self::AORD[$this->sMemory[$this->readWord($this->iProgramCounter + 1)]]
                );
                break;

            case self::AND_ABX:
                $this->updateNZ(
                    $this->iAccumulator &= self::AORD[$this->sMemory[$this->addrAbsoluteXByte()]]
                );
                break;

            case self::AND_ABY:
                $this->updateNZ(
                    $this->iAccumulator &= self::AORD[$this->sMemory[$this->addrAbsoluteYByte()]]
                );
                break;

            case self::AND_IX:
                $this->updateNZ(
                    $this->iAccumulator &= self::AORD[$this->sMemory[$this->addrPreIndexZeroPageXByte()]]
                );
                break;

            case self::AND_IY:
                $this->updateNZ(
                    $this->iAccumulator &= self::AORD[$this->sMemory[$this->addrPostIndexZeroPageYByte()]]
                );
                break;

            case self::ORA_IM:
                $this->updateNZ(
                    $this->iAccumulator |= self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]
                );
                break;

            case self::ORA_ZP:
                $this->updateNZ(
                    $this->iAccumulator |= self::AORD[$this->sMemory[self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]]]
                );
                break;

            case self::ORA_ZPX:
                $this->updateNZ(
                    $this->iAccumulator |= self::AORD[$this->sMemory[$this->addrZeroPageXByte()]]
                );
                break;

            case self::ORA_AB:
                $this->updateNZ(
                    $this->iAccumulator |= self::AORD[$this->sMemory[$this->readWord($this->iProgramCounter + 1)]]
                );
                break;

            case self::ORA_ABX:
                $this->updateNZ(
                    $this->iAccumulator |= self::AORD[$this->sMemory[$this->addrAbsoluteXByte()]]
                );
                break;

            case self::ORA_ABY:
                $this->updateNZ(
                    $this->iAccumulator |= self::AORD[$this->sMemory[$this->addrAbsoluteYByte()]]
                );
                break;

            case self::ORA_IX:
                $this->updateNZ(
                    $this->iAccumulator |= self::AORD[$this->sMemory[$this->addrPreIndexZeroPageXByte()]]
                );
                break;

            case self::ORA_IY:
                $this->updateNZ(
                    $this->iAccumulator |= self::AORD[$this->sMemory[$this->addrPostIndexZeroPageYByte()]]
                );
                break;

            case self::EOR_IM:
                $this->updateNZ(
                    $this->iAccumulator ^= self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]
                );
                break;

            case self::EOR_ZP:
                $this->updateNZ(
                    $this->iAccumulator ^= self::AORD[$this->sMemory[self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]]]
                );
                break;

            case self::EOR_ZPX:
                $this->updateNZ(
                    $this->iAccumulator ^= self::AORD[$this->sMemory[$this->addrZeroPageXByte()]]
                );
                break;

            case self::EOR_AB:
                $this->updateNZ(
                    $this->iAccumulator ^= self::AORD[$this->sMemory[$this->readWord($this->iProgramCounter + 1)]]
                );
                break;

            case self::EOR_ABX:
                $this->updateNZ(
                    $this->iAccumulator ^= self::AORD[$this->sMemory[$this->addrAbsoluteXByte()]]
                );
                break;

            case self::EOR_ABY:
                $this->updateNZ(
                    $this->iAccumulator ^= self::AORD[$this->sMemory[$this->addrAbsoluteYByte()]]
                );
                break;

            case self::EOR_IX:
                $this->updateNZ(
                    $this->iAccumulator ^= self::AORD[$this->sMemory[$this->addrPreIndexZeroPageXByte()]]
                );
                break;

            case self::EOR_IY:
                $this->updateNZ(
                    $this->iAccumulator ^= self::AORD[$this->sMemory[$this->addrPostIndexZeroPageYByte()]]
                );
                break;

            // Arithmetuc shift left
            case self::ASL_A: {
                $this->iStatus &= ~self::F_CARRY;
                $this->iStatus |= ($this->iAccumulator & self::F_NEGATIVE) >> 7; // sign -> carry
                $this->updateNZ($this->iAccumulator = (($this->iAccumulator << 1) & 0xFF));
                break;
            }

            case self::ASL_ZP:  $this->aslMemory(self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]); break;
            case self::ASL_ZPX: $this->aslMemory($this->addrZeroPageXByte()); break;
            case self::ASL_AB:  $this->aslMemory($this->readWord($this->iProgramCounter + 1)); break;
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

            case self::ROL_ZP:  $this->rolMemory(self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]); break;
            case self::ROL_ZPX: $this->rolMemory($this->addrZeroPageXByte()); break;
            case self::ROL_AB:  $this->rolMemory($this->readWord($this->iProgramCounter + 1)); break;
            case self::ROL_ABX: $this->rolMemory($this->addrAbsoluteXByte()); break;

            case self::ROR_A: {
                $iCarry = ($this->iStatus & self::F_CARRY) << 7; // carry -> sign
                $this->iStatus &= ~self::F_CARRY;
                $this->iStatus |= ($this->iAccumulator & self::F_CARRY); // carry -> carry
                $this->updateNZ($this->iAccumulator = (($this->iAccumulator >> 1) | $iCarry));
                break;
            }

            case self::ROR_ZP:  $this->rorMemory(self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]); break;
            case self::ROR_ZPX: $this->rorMemory($this->addrZeroPageXByte()); break;
            case self::ROR_AB:  $this->rorMemory($this->readWord($this->iProgramCounter + 1)); break;
            case self::ROR_ABX: $this->rorMemory($this->addrAbsoluteXByte()); break;

            case self::LSR_ZP:  $this->lsrMemory(self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]); break;
            case self::LSR_ZPX: $this->lsrMemory($this->addrZeroPageXByte()); break;
            case self::LSR_AB:  $this->lsrMemory($this->readWord($this->iProgramCounter + 1)); break;
            case self::LSR_ABX: $this->lsrMemory($this->addrAbsoluteXByte()); break;


            // Addition
            // A + M + C
            case self::ADC_IM:
                $this->addByteWithCarry(
                    self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]
                );
                break;

            case self::ADC_ZP:
                $this->addByteWithCarry(
                    self::AORD[$this->sMemory[self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]]]
                );
                break;

            case self::ADC_ZPX:
                $this->addByteWithCarry(
                    self::AORD[$this->sMemory[$this->addrZeroPageXByte()]]
                );
                break;

            case self::ADC_AB:
                $this->addByteWithCarry(
                    self::AORD[$this->sMemory[$this->readWord($this->iProgramCounter + 1)]]
                );
                break;

            case self::ADC_ABX:
                $this->addByteWithCarry(
                    self::AORD[$this->sMemory[$this->addrAbsoluteXByte()]]
                );
                break;

            case self::ADC_ABY:
                $this->addByteWithCarry(
                    self::AORD[$this->sMemory[$this->addrAbsoluteYByte()]]
                );
                break;

            case self::ADC_IX:
                $this->addByteWithCarry(
                    self::AORD[$this->sMemory[$this->addrPreIndexZeroPageXByte()]]
                );
                break;

            case self::ADC_IY:
                $this->addByteWithCarry(
                    self::AORD[$this->sMemory[$this->addrPostIndexZeroPageYByte()]]
                );
                break;

            // Subtract
            // A - M - B => A + (255 - M) - (1 - C) => A + ~M + C
            case self::SBC_IM:
                $this->subByteWithCarry(
                    self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]
                );
                break;

            case self::SBC_ZP:
                $this->subByteWithCarry(
                    self::AORD[$this->sMemory[self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]]]
                );
                break;

            case self::SBC_ZPX:
                $this->subByteWithCarry(
                    self::AORD[$this->sMemory[$this->addrZeroPageXByte()]]
                );
                break;

            case self::SBC_AB:
                $this->subByteWithCarry(
                    self::AORD[$this->sMemory[$this->readWord($this->iProgramCounter + 1)]]
                );
                break;

            case self::SBC_ABX:
                $this->subByteWithCarry(
                    self::AORD[$this->sMemory[$this->addrAbsoluteXByte()]]
                );
                break;

            case self::SBC_ABY:
                $this->subByteWithCarry(
                    self::AORD[$this->sMemory[$this->addrAbsoluteYByte()]]
                );
                break;

            case self::SBC_IX:
                $this->subByteWithCarry(
                    self::AORD[$this->sMemory[$this->addrPreIndexZeroPageXByte()]]
                );
                break;

            case self::SBC_IY:
                $this->subByteWithCarry(
                    self::AORD[$this->sMemory[$this->addrPostIndexZeroPageYByte()]]
                );
                break;

            // Compare
            // A - M
            case self::CMP_IM:
                $this->cmpByte(
                    $this->iAccumulator,
                    self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]
                );
                break;

            case self::CMP_ZP:
                $this->cmpByte(
                    $this->iAccumulator,
                    self::AORD[$this->sMemory[self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]]]
                );
                break;

            case self::CMP_ZPX:
                $this->cmpByte(
                    $this->iAccumulator,
                    self::AORD[$this->sMemory[$this->addrZeroPageXByte()]]
                );
                break;

            case self::CMP_AB:
                $this->cmpByte(
                    $this->iAccumulator,
                    self::AORD[$this->sMemory[$this->readWord($this->iProgramCounter + 1)]]
                );
                break;

            case self::CMP_ABX:
                $this->cmpByte(
                    $this->iAccumulator,
                    self::AORD[$this->sMemory[$this->addrAbsoluteXByte()]]
                );
                break;

            case self::CMP_ABY:
                $this->cmpByte(
                    $this->iAccumulator,
                    self::AORD[$this->sMemory[$this->addrAbsoluteYByte()]]
                );
                break;

            case self::CMP_IX:
                $this->cmpByte(
                    $this->iAccumulator,
                    self::AORD[$this->sMemory[$this->addrPreIndexZeroPageXByte()]]
                );
                break;

            case self::CMP_IY:
                $this->cmpByte(
                    $this->iAccumulator,
                    self::AORD[$this->sMemory[$this->addrPostIndexZeroPageYByte()]]
                );
                break;

            case self::CPX_IM:
                $this->cmpByte(
                    $this->iXIndex,
                    self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]
                );
                break;

            case self::CPX_ZP:
                $this->cmpByte(
                    $this->iXIndex,
                    self::AORD[$this->sMemory[self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]]]
                );
                break;

            case self::CPX_AB:
                $this->cmpByte(
                    $this->iXIndex,
                    self::AORD[$this->sMemory[$this->readWord($this->iProgramCounter + 1)]]
                );
                break;

            case self::CPY_IM:
                $this->cmpByte(
                    $this->iYIndex,
                    self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]
                );
                break;

            case self::CPY_ZP:
                $this->cmpByte(
                    $this->iYIndex,
                    self::AORD[$this->sMemory[self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]]]
                );
                break;

            case self::CPY_AB:
                $this->cmpByte(
                    $this->iYIndex,
                    self::AORD[$this->sMemory[$this->readWord($this->iProgramCounter + 1)]]
                );
                break;

            case self::BIT_ZP: {
                $iMem = self::AORD[$this->sMemory[self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]]]];
                $this->iStatus &= ~(self::F_NEGATIVE | self::F_OVERFLOW | self::F_ZERO);
                $this->iStatus |= ($iMem & (self::F_NEGATIVE|self::F_OVERFLOW)) | (
                    $iMem & $this->iAccumulator ? 0 : self::F_ZERO
                );
                break;
            }
            case self::BIT_AB: {
                $iMem = self::AORD[$this->sMemory[$this->readWord($this->iProgramCounter + 1)]];
                $this->iStatus &= ~(self::F_NEGATIVE | self::F_OVERFLOW | self::F_ZERO);
                $this->iStatus |= ($iMem & (self::F_NEGATIVE|self::F_OVERFLOW)) | (
                    $iMem & $this->iAccumulator ? 0 : self::F_ZERO
                );
                break;
            }

            // Conditional
            case self::BCC: {
                $this->iProgramCounter += (!($this->iStatus & self::F_CARRY)) ?
                    self::signByte(self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]])
                    : 0;
                break;
            }

            case self::BCS: {
                $this->iProgramCounter += ($this->iStatus & self::F_CARRY) ?
                    self::signByte(self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]])
                    : 0;
                break;
            }

            case self::BEQ: {
                $this->iProgramCounter += ($this->iStatus & self::F_ZERO) ?
                    self::signByte(self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]])
                    : 0;
                break;
            }

            case self::BNE: {
                $this->iProgramCounter += (!($this->iStatus & self::F_ZERO)) ?
                    self::signByte(self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]])
                    : 0;
                break;
            }

            case self::BMI: {
                $this->iProgramCounter += ($this->iStatus & self::F_NEGATIVE) ?
                    self::signByte(self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]])
                    : 0;
                break;
            }

            case self::BPL: {
                $this->iProgramCounter += (!($this->iStatus & self::F_NEGATIVE)) ?
                    self::signByte(self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]])
                    : 0;
                break;
            }

            case self::BVC: {
                $this->iProgramCounter += (!($this->iStatus & self::F_OVERFLOW)) ?
                    self::signByte(self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]])
                    : 0;
                break;
            }

            case self::BVS: {
                $this->iProgramCounter += ($this->iStatus & self::F_OVERFLOW) ?
                    self::signByte(self::AORD[$this->sMemory[($this->iProgramCounter + 1) & self::MEM_MASK]])
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
                    $iAddress = self::AORD[$this->sMemory[$iPointerAddress]];
                    $iAddress |= self::AORD[$this->sMemory[($iPointerAddress & 0xFF00)]] << 8;
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
                //$iValAddress    = $this->iProgramCounter + 1;
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

    protected function writeByte(int $iAddress, int $iValue): void {
        $this->sMemory[$iAddress & self::MEM_MASK] = self::ACHR[$iValue];
    }
}
