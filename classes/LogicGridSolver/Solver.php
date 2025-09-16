<?php
declare(strict_types=1);

namespace App\Classes\LogicGridSolver;

use App\Classes\LogicGridSolver\Constraint;
use App\Classes\LogicGridSolver\Puzzle;

class Solver {

  /**
   * Recursive function that attempt to solve the given puzzle. Once solving is
   * complete, the completed puzzle is returned.
   *
   * @param Puzzle $puzzle Puzzle to solve.
   * @return array|null Completed puzzle (null if error or unsolveable).
   */
  public function solve(Puzzle $puzzle): ?array {
    try {
      $this->propagateAll($puzzle);
    } catch (\RuntimeException $e) {
      return null;
    }
    if ($this->isComplete($puzzle)) {
      return $this->resultAsRows($puzzle);
    }
    // Pick variable with smallest domain > 1.
    $varName = $this->selectVariable($puzzle);
    $var = $puzzle->getVariable($varName);
    foreach ($var->domain as $choice) {
      // Cheap deep-copy via serialize/deseriatlize for clarity (okay for
      //   moderateate puzzle sizes).
      $copy = unserialize(serialize($puzzle));
      $copy->getVariable($varName)->assign($choice);
      try {
        $result = $this->solve($copy);
        if ($result !== null) {
          return $result;
        }
      } catch (\RuntimeException $e) {
        // Contradiction -- try next choice.
      }
    }

    return null;
  }

  /**
   * Try every constraint possible on this puzzle.
   *
   * @param Puzzle $puzzle Puzzle to solve.
   * @return void
   */
  private function propagateAll(Puzzle $puzzle): void {
    $changed = true;
    while ($changed) {
      $changed = false;
      foreach ($puzzle->constraints as $constraint) {
        $diff = $constraint->propagate($puzzle);
        if ($diff) {
          $changed = true;
        }
      }
    }
  }

  /**
   * Returns whether or not the puzzle is completed.
   *
   * @param Puzzle $puzzle Puzzle to solve.
   * @return boolean Is puzzle complete?
   */
  private function isComplete(Puzzle $puzzle): bool {
    foreach ($puzzle->variables as $var) {
      if (!$var->isAssigned()) {
        return false;
      }
    }
    return true;
  }

  /**
   * Finds the clues with the smallest number of possibilities and returns the
   * variable.
   *
   * @param Puzzle $puzzle Puzzle to solve.
   * @return string Selected variable.
   */
  private function selectVariable(Puzzle $puzzle): string {
    $best = null;
    $bestSize = PHP_INT_MAX;
    foreach ($puzzle->variables as $name => $var) {
      $size = count($var->domain);
      if ($size > 1 && $size < $bestSize) {
        $best = $name;
        $bestSize = $size;
      }
    }
    if ($best === null) {
      throw new \RuntimeException(
        "Solver: No variable to select (should be complete or contradiction)."
      );
    }
    return $best;
  }

  /**
   * Returns mapping of rows => category => valueName.
   *
   * @param Puzzle $puzzle Puzzle to solve.
   * @return array Rows mapping.
   */
  private function resultAsRows(Puzzle $puzzle): array {
    $rows = array_fill(0, $puzzle->N, []);

    foreach ($puzzle->variables as $name => $var) {
      $row = $var->getAssigned();
      // Extract category: everything before the first digit
      if (preg_match('/^([A-Za-z]+)[0-9]+$/', $name, $m)) {
        $cat = $m[1];
      } else {
        $cat = ''; // fallback, should not happen
      }
      $rows[$row][$cat] = $name;
    }

    // Optionally, sort categories in each row for consistency
    foreach ($rows as &$rowArr) {
      ksort($rowArr);
    }

    return $rows;
  }

  private function resultAsRowsOLD(Puzzle $puzzle): array {
    $rows = array_fill(0, $puzzle->N, []);

    foreach ($puzzle->variables as $name => $var) {
      $row = $var->getAssigned();
      // Handle multi-char categories.
      $cat = preg_replace('/[0-9]+$', '', $name);
      $rows[$row][$cat] = $name;
    }

    return $rows;
  }

}
