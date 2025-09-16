<?php
declare(strict_types=1);

namespace App\Classes\LogicGridSolver\Constraint;

use App\Classes\LogicGridSolver\Constraint;
use App\Classes\LogicGridSolver\Puzzle;

/**
 * EitherOrConstraint class.
 *
 * Extends App\Classes\LogicGridSolver\Constraint.
 *
 * Either-or constraint: list of alternatives. Each alternative is a list of
 * equality pairs [[X, Y], ...].
 *
 * Example: B1 is either D3 or C2 → alternatives: [
 *   [
 *     ['B1', 'D3']
 *   ],
 *   [
 *     ['B1', 'C2']
 *   ]
 * ].
 */
class EitherOrConstraint extends Constraint {

  /** @var array[] */
  private array $alternatives;

  public function __construct(array $alternatives) {
    $this->alternatives = $alternatives;
  }

  public function propagate(Puzzle $puzzle): bool {
    $possible = [];
    foreach ($this->alternatives as $alt) {
      $ok = true;
      // Check equickly that each equality pair has non-empty intersection.
      foreach ($alt as [$x, $y]) {
        $varX = $puzzle->getVariable($x);
        $varY = $puzzle->getVariable($y);
        if (empty(array_intersect($varX->domain, $varY->domain))) {
          $ok = false;
          break;
        }
      }
      if ($ok) {
        $possible[] = $alt;
      }
    }
    if (empty($possible)) {
      throw new \RuntimeException("EitherOr: no alternatives possible.");
    }
    $changed = false;
    if (count($possible) === 1) {
      // Enforce the equalities from the sole remaining alternative.
      foreach ($possible[0] as [$x, $y]) {
        $constraint = new EqualityConstraint($x, $y);
        $changed = $constraint->propagate($puzzle) || $changed;
      }
    }

    return $changed;
  }

}
