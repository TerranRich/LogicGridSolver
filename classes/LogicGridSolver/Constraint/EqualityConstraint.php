<?php
declare(strict_types=1);

namespace App\Classes\LogicGridSolver\Constraint;

use App\Classes\LogicGridSolver\Constraint;
use App\Classes\LogicGridSolver\Puzzle;

/**
 * EqualityConstraint class.
 *
 * Extends App\Classes\LogicGridSolver\Constraint.
 */
class EqualityConstraint extends Constraint {

  private string $a, $b;

  public function __construct(string $a, string $b) {
    $this->a = $a;
    $this->b = $b;
  }

  public function propagate(Puzzle $puzzle): bool {
    $varA = $puzzle->getVariable($this->a);
    $varB = $puzzle->getVariable($this->b);
    $intersection = array_values(array_intersect($varA->domain, $varB->domain));
    if (empty($intersection)) {
      throw new \RuntimeException(
        "Impossible equality {$this->a} == {$this->b}."
      );
    }
    $changed = false;
    if (count($intersection) !== count($varA->domain)) {
      $varA->domain = $intersection;
      $changed = true;
    }
    if (count($intersection) !== count($varB->domain)) {
      $varB->domain = $intersection;
      $changed = true;
    }

    return $changed;
  }

}
