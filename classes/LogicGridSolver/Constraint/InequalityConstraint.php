<?php
declare(strict_types=1);

namespace App\Classes\LogicGridSolver\Constraint;

use App\Classes\LogicGridSolver\Constraint;
use App\Classes\LogicGridSolver\Puzzle;

/**
 * InequalityConstraint class.
 *
 * Extends App\Classes\LogicGridSolver\Constraint.
 */
class InequalityConstraint extends Constraint {

  private string $a, $b;

  public function __construct(string $a, string $b) {
    $this->a = $a;
    $this->b = $b;
  }

  public function propagate(Puzzle $puzzle): bool {
    $varA = $puzzle->getVariable($this->a);
    $varB = $puzzle->getVariable($this->b);
    $changed = false;
    if ($varA->isAssigned()) {
      $val = $varA->getAssigned();
      if (in_array($val, $varB->domain, true)) {
        if ($varB->removeValue($val)) {
          $changed = true;
        }
      }
    }
    if ($varB->isAssigned()) {
      $val = $varB->getAssigned();
      if (in_array($val, $varA->domain, true)) {
        if ($varA->removeValue($val)) {
          $changed = true;
        }
      }
    }
    return $changed;
  }

}
