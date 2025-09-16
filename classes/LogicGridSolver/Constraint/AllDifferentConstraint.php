<?php
declare(strict_types=1);

namespace App\Classes\LogicGridSolver\Constraint;

use App\Classes\LogicGridSolver\Constraint;
use App\Classes\LogicGridSolver\Puzzle;

/**
 * AllDifferentConstraint class.
 *
 * Extends App\Classes\LogicGridSolver\Constraint.
 */
class AllDifferentConstraint extends Constraint {

  /** @var string[] */
  private array $vars;

  public function __construct(array $vars) {
    $this->vars = $vars;
  }

  public function propagate(Puzzle $puzzle): bool {
    $changed = false;
    // Simple propagation: if a variable is a singleton, remove that row from
    //   other vars in the same category.
    $singletons = [];
    foreach ($this->vars as $name) {
      $var = $puzzle->getVariable($name);
      if ($var->isAssigned()) {
        $singletons[$var->getAssigned()] = $name;
      }
    }
    foreach ($singletons as $val => $ownerName) {
      foreach ($this->vars as $name) {
        if ($name === $ownerName) continue;
        $var = $puzzle->getVariable($name);
        if (in_array($val, $var->domain, true)) {
          if ($var->removeValue($val)) $changed = true;
        }
      }
    }
    return $changed;
  }

}
