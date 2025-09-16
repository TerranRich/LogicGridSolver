<?php
declare(strict_types=1);

namespace App\Classes\LogicGridSolver;

use App\Classes\LogicGridSolver\Puzzle;

/**
 * Constraint parent class.
 *
 * Propagate domain reductions; return true if any domain changed.
 *
 * @throws RuntimeException on contradiction (empty domain).
 */
abstract class Constraint {

  abstract public function propagate(Puzzle $puzzle): bool;

}
