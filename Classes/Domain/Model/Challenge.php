<?php
declare(strict_types=1);

namespace BBysaeth\Typo3Altcha\Domain\Model;
use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class Challenge extends AbstractEntity {
    protected string $challenge;
    protected int $tstamp = 0;
    protected bool $isSolved = false;

    public function __construct() {
        $this->tstamp = time();
    }

    public function getChallenge(): string {
        return $this->challenge;
    }
    public function setChallenge(string $challenge): void {
        $this->challenge = $challenge;
    }

    public function getIsSolved(): bool {
        return $this->isSolved;
    }
    
    public function setIsSolved(bool $isSolved): void {
        $this->isSolved = $isSolved;
    }

    public function getTstamp() {
        return $this->tstamp;
    }

}