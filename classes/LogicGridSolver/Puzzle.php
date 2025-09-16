<?php
declare(strict_types=1);

namespace App\Classes\LogicGridSolver;

use App\Classes\LogicGridSolver\Variable;
use App\Classes\LogicGridSolver\Constraint;
use App\Classes\LogicGridSolver\Constraint\AllDifferentConstraint;

/**
 * Puzzle object. Holds variables and constraints.
 */

class Puzzle {

  /** @var int number of rows (values per category) */
  public int $N;

  /** @var Variable[] name => Variable */
  public array $variables = [];

  /** @var string[][] category => [varNames...] */
  public array $varsByCategory = [];

  /** @var Constraint[] */
  public array $constraints = [];

  public function __construct(int $N) {
    $this->N = $N;
  }

  /**
   * Categories are single-letter for this library; adjust if needed.
   *
   * @param string $cat Category letter.
   * @return void
   */
  public function addCategory(string $cat): void {
    if (isset($this->varsByCategory[$cat])) {
      throw new \RuntimeException("Category {$cat} already exists.");
    }
    $this->varsByCategory[$cat] = [];
    for ($i = 0; $i < $this->N; $i++) {
      $name = $cat . ($i + 1);
      $this->variables[$name] = new Variable($name, range(0, $this->N - 1));
      $this->varsByCategory[$cat][] = $name;
    }
    // Ensure uniqueness within category.
    $this->constraints[] = new AllDifferentConstraint(
      $this->varsByCategory[$cat]
    );
  }

  public function getVariable(string $name): Variable {
    if (!isset($this->variables[$name])) {
      throw new \RuntimeException("Unknown variable {$name}.");
    }
    return $this->variables[$name];
  }

  public function addConstraint(Constraint $c): void {
    $this->constraints[] = $c;
  }

  /**
   * Get possible rank numbers (1..N) that can be in given row index, for a
   * rank-category like 'B' => possible _k_ vales such that variable B_k_ could
   * be in row $row.
   *
   * @param string $category Category letter.
   * @param integer $row Row index.
   * @return array Ranks possible for row at given index.
   */
  public function getRanksPossibleForRow(string $category, int $row): array {
    if (!isset($this->varsByCategory[$category])) {
      throw new \RuntimeException("Unknown rank category {$category}.");
    }
    $out = [];
    foreach ($this->varsByCategory[$category] as $varName) {
      $var = $this->getVariable($varName);
      if (in_array($row, $var->domain, true)) {
        // A varName like "B3" -> parse trailing number.
        $rank = (int)substr($varName, strlen($category)); //robust to multidigit
        $out[] = $rank;
      }
    }
    return $out;
  }

}
