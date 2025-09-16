<?php
declare(strict_types=1);

namespace App\Classes\LogicGridSolver\Constraint;

use App\Classes\LogicGridSolver\Constraint;
use App\Classes\LogicGridSolver\Puzzle;

/**
 * RankExactDiffConstraint class.
 *
 * Extends App\Classes\LogicGridSolver\Constraint.
 *
 * Rank exact difference: rank(left) - rank(right) == diff [diff can be < 0].
 */
class RankExactDiffConstraint extends Constraint {

  private string $left, $right, $category;

  private int $diff;

  public function __construct(
    string $left, string $right, string $category, int $diff
  ) {
    $this->left     = $left;
    $this->right    = $right;
    $this->category = $category;
    $this->diff     = $diff;
  }

  public function propagate(Puzzle $puzzle): bool {
    $varA = $puzzle->getVariable($this->left);
    $varB = $puzzle->getVariable($this->right);
    $changed = false;

    $newA = [];
    foreach ($varA->domain as $i) {
      $raList = $puzzle->getRanksPossibleForRow($this->category, $i);
      if (empty($raList)) continue;
      $possible = false;
      foreach ($varB->domain as $j) {
        $rbList = $puzzle->getRanksPossibleForRow($this->category, $j);
        foreach ($raList as $rankA) {
          foreach ($rbList as $rankB) {
            if ($rankA - $rankB === $this->diff) {
              $possible = true;
              break 3;
            }
          }
        }
      }
      if ($possible) {
        $newA[] = $i;
      }
    }
    if (empty($newA)) {
      throw new \RuntimeException(
        "RankExactDiff contradiction for " .
        "{$this->left} - {$this->right} == {$this->diff}"
      );
    }
    if (count($newA) !== count($varA->domain)) {
      $varA->domain = $newA;
      $changed = true;
    }

    // Symmetric for B.
    $newB = [];
    foreach ($varB->domain as $j) {
      $rbList = $puzzle->getRanksPossibleForRow($this->category, $j);
      if (empty($raList)) continue;
      $possible = false;
      foreach ($varA->domain as $i) {
        $raList = $puzzle->getRanksPossibleForRow($this->category, $i);
        foreach ($raList as $rankA) {
          foreach ($rbList as $rankB) {
            if ($rankA - $rankB === $this->diff) {
              $possible = true;
              break 3;
            }
          }
        }
      }
      if ($possible) {
        $newB[] = $j;
      }
    }
    if (empty($newB)) {
      throw new \RuntimeException(
        "RankExactDiff contradiction for " .
        "{$this->left} - {$this->right} == {$this->diff}"
      );
    }
    if (count($newB) !== count($varB->domain)) {
      $varB->domain = $newB;
      $changed = true;
    }

    return $changed;
  }

}
