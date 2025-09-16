<?php
declare(strict_types=1);

namespace App\Classes\LogicGridSolver;

/**
 * Simple CSP-based logic grid puzzle solver.
 *   - Variables: each value like "A1" is a variable whose domain is row indices
 *     0..N-1
 *
 * This is intentionally a readable, extendable implementation. For big puzzles
 * or top performance:
 *   - Use bitsets for domains
 *   - Implement AC-3/Regin for AllDifferent
 *   - Use incremental trail-based backtracking.
 */

/**
 * Variable class.
 */
class Variable {
  /** @var string name of the puzzle */
  public string $name;

  /** @var int[] possible row indices */
  public array $domain;

  public function __construct(string $name, array $domain) {
    $this->name   = $name;
    $this->domain = array_values($domain);
  }

  public function isAssigned(): bool {
    return count($this->domain) === 1;
  }

  public function getAssigned(): int {
    if (!$this->isAssigned()) {
      throw new \RuntimeException("Variable {$this->name} not assigned.");
    }
    return $this->domain[0];
  }

  public function assign(int $v): void {
    if (!in_array($v, $this->domain, true)) {
      throw new \RuntimeException(
        "Cannot assign value {$v} to {$this->name}, not in domain."
      );
    }
    $this->domain = [$v];
  }

  public function removeValue(int $v): bool {
    $idx = array_search($v, $this->domain, true);
    if ($idx === false) return false;
    array_splice($this->domain, $idx, 1);
    if (empty($this->domain)) {
      throw new \RuntimeException("Domain wipeout for {$this->name}.");
    }
    return true;
  }

  /**
   * Intersect domain with $values; returns true if domain changed.
   *
   * @param array $values Values to intersect.
   * @return boolean
   */
  public function intersectDomain(array $values): bool {
    $new = array_values(array_intersect($this->domain, $values));
    if (empty($new)) {
      throw new \RuntimeException(
        "Domain wipeout for {$this->name} (intersect)."
      );
    }
    if (count($new) === count($this->domain)) return false;
    $this->domain = $new;
    return true;
  }

}
