<?php
declare(strict_types=1);

namespace App\Classes\LogicGridSolver\Constraint;

use App\Classes\LogicGridSolver\Constraint;
use App\Classes\LogicGridSolver\Puzzle;

/**
 * RankGreaterConstraint class.
 *
 * Extends App\Classes\LogicGridSolver\Constraint.
 *
 * Rank comparison: left.rank(category) > right.rank(category) [unknown amount].
 */
class RankGreaterConstraint extends Constraint {

  private string $left, $right, $category;

  public function __construct(string $left, string $right, string $category) {
    $this->left     = $left;
    $this->right    = $right;
    $this->category = $category;
  }

  public function propagate(Puzzle $puzzle): bool {
    $varA = $puzzle->getVariable($this->left);
    $varB = $puzzle->getVariable($this->right);
    $changed = false;

    // Prune varA: candidate row *i* only stays if *j* exists in varB.domain and
    //   ranks *ra* in ranks(i) and *rb* in ranks(j) with rankA > rankB.
    $newA = [];
    foreach ($varA->domain as $i) {
      $raList = $puzzle->getRanksPossibleForRow($this->category, $i);
      // If no rank is possible, skip it (it will be eliminated).
      if (empty($raList)) continue;
      $possible = false;
      foreach ($varB->domain as $j) {
        $rbList = $puzzle->getRanksPossibleForRow($this->category, $j);
        foreach ($raList as $rankA) {
          foreach ($rbList as $rankB) {
            if ($rankA > $rankB) {
              $possible = true;
              break 3;
            }
          }
        }
      }
      if ($possible) $newA[] = $i;
    }

    if (empty($newA)) {
      throw new \RuntimeException(
        "RankGreater contradiction for {$this->left} > {$this->right}."
      );
    }
    if (count($newA) !== count($varA->domain)) {
      $varA->domain = $newA;
      $changed = true;
    }

    // Symmetric prune for varB: candidate row *j* only stays if *i* exists in
    //   varA.domain and ranks rankA > rankB.
    $newB = [];
    foreach ($varB->domain as $j) {
      $rbList = $puzzle->getRanksPossibleForRow($this->category, $j);
      if (empty($rbList)) continue;
      $possible = false;
      foreach ($varA->domain as $i) {
        $raList = $puzzle->getRanksPossibleForRow($this->category, $i);
        foreach ($raList as $rankA) {
          foreach ($rbList as $rankB) {
            if ($rankA > $rankB) {
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
        "RankGreater contradiction for {$this->left} > {$this->right}."
      );
    }
    if (count($newB) !== count($varB->domain)) {
      $varB->domain = $newB;
      $changed = true;
    }

    return $changed;
  }

}
