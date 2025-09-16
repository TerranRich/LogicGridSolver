<?php

namespace App\Classes;

/**
 * Sudoku class
 *
 * Based on https://github.com/xeeeveee/sudoku/, modified quite a bit.
 */

class Sudoku {
  /**
   * Holds the entire puzzle.
   *
   * @var array
   */
  protected $puzzle = [];

  protected $puzzleColumns = [];

  protected $puzzleBoxes = [];

  /**
   * Holds the solution.
   *
   * @var array
   */
  protected $solution = [];

  protected $solutionColumns = [];

  protected $solutionBoxes = [];

  /**
   * Number of rows/columns in each subgrid.
   *
   * @var int
   */
  protected $cellSize = 3;

  /**
   * Box lookup by row and column index.
   *
   * @var array
   */
  protected $boxLookup;

  /**
   * Constructor
   *
   * @param int $cellSize Number of rows/columns in each subgrid.
   * @param array $puzzle The puzzle.
   * @param array $solution The solution.
   */
  public function __construct(int $cellSize = 3, array $puzzle = [], array $solution = []) {
    $this->setCellSize($cellSize, $puzzle, $solution);
  }

  /**
   * Gets the grid size.
   *
   * @return int
   */
  public function getCellSize(): int {
    return $this->cellSize;
  }

  /**
   * Sets the grid size.
   *
   * Changing the grid size will essentially reset the object, setting the
   * `$puzzle` & `$solution` properties to valid empty values. The cell size
   * must be 2 or greater.
   *
   * @param int $cellSize Number of rows/columns in each subgrid.
   * @param array $puzzle The puzzle.
   * @param array $solution The solution.
   * @return bool True if the cell size was set, false otherwise.
   */
  public function setCellSize(int $cellSize, array $puzzle = [], array $solution = []): bool {
    if ($cellSize > 1) {
      $this->cellSize = $cellSize;
      $this->setPuzzle($puzzle);
      $this->setSolution($solution);
      return true;
    }
    return false;
  }

  /**
   * Gets the grid size.
   *
   * @return int
   */
  public function getGridSize(): int {
    return $this->cellSize ** 2;
  }

  /**
   * Returns the puzzle array.
   *
   * @return array
   */
  public function getPuzzle(): array {
    return $this->puzzle;
  }

  public function getPuzzleString(): string {
    return implode('', array_merge(...$this->puzzle));
  }

  /**
   * Sets the puzzle array.
   *
   * If an invalid puzzle is supplied, an empty puzzle is generated instead.
   *
   * @param array $puzzle The puzzle.
   * @return bool True if the puzzle was set, false otherwise.
   */
  public function setPuzzle(array $puzzle = []): bool {
    if ($this->isValidPuzzleFormat($puzzle)) {
      $this->puzzle = $puzzle;
      $success = true;
    } else {
      $this->puzzle = $this->generateEmptyPuzzle();
      $success = false;
    }
    $this->setSolution($this->puzzle);
    $this->prepareReferences();
    return $success;
  }

  /**
   * Gets the solution.
   *
   * @return array The solution.
   */
  public function getSolution(): array {
    return $this->solution;
  }

  public function getSolutionString(): string {
    return implode('', array_merge(...$this->solution));
  }

  /**
   * Sets the solution array.
   *
   * @param array $solution The solution.
   * @return bool True if the solution was set, false otherwise.
   */
  public function setSolution(array $solution = []): bool {
    if ($this->isValidPuzzleFormat($solution)) {
      $this->solution = $solution;
      $this->prepareReferences(false);
      return true;
    }
    return false;
  }

  /**
   * Solves the puzzle.
   *
   * @return bool True if the puzzle was solved, false otherwise.
   */
  public function solve(): bool {
    if ($this->isSolvable() && $this->calculateSolution($this->solution)) {
      return true;
    }

    return false;
  }

  /**
   * Returns whether the puzzle is solved.
   *
   * @return bool True if the puzzle is solved, false otherwise.
   */
  public function isSolved(): bool {
    if (!$this->checkConstraints($this->solution, $this->solutionColumns, $this->solutionBoxes)) {
      return false;
    }
    foreach ($this->puzzle as $rowIndex => $row) {
      foreach ($row as $colIndex => $column) {
        if (
          $column !== 0 &&
          $this->solution[$rowIndex][$colIndex] !=
            $this->puzzle[$rowIndex][$colIndex]
        ) {
          return false;
        }
      }
    }
    return true;
  }

  /**
   * Checks if a puzzle is solvable.
   *
   * Only ensures the current puzzle is valid and doesn't violate any
   * constraints. It does not guarantee a solution can be found.
   *
   * @return bool True if the puzzle is solvable, false otherwise.
   */
  public function isSolvable(): bool {
    return $this->checkConstraints(
      $this->puzzle,
      $this->puzzleColumns,
      $this->puzzleBoxes,
      true
    );
  }

  /**
   * Generates a new random puzzle.
   *
   * Difficulty is specified by the number of cells pre-populated in the puzzle.
   * These are assigned randomly, so this does not necessarily guarantee a
   * difficult or easy puzzle.
   *
   * @param int $cellCount The number of cells to pre-populate.
   * @return array|bool The puzzle if generated, false otherwise.
   */
  public function generatePuzzle(int $cellCount = 15): array|bool {
    if ($cellCount < 0 || $cellCount > $this->getCellCount()) {
      return false;
    }
    $this->solution = $this->generateEmptyPuzzle();
    $this->solve(); // Generate a full solution
    $this->puzzle = $this->solution; // Start with a full solution
    $this->removeNumbersUntilUnique($cellCount);
    $this->prepareReferences();
    return true;
  }

  protected function removeNumbersUntilUnique(int $cellCount): void {
    $cells = [];
    $gridSize = $this->getGridSize();
    for ($i = 0; $i < $gridSize; $i++) {
      for ($j = 0; $j < $gridSize; $j++) {
        if ($this->puzzle[$i][$j] !== 0) {
          $cells[] = [$i, $j];
        }
      }
    }
    shuffle($cells);
    $removedCells = 0;
    foreach ($cells as [$i, $j]) {
      if ($removedCells >= $gridSize * $gridSize - $cellCount) {
        break;
      }
      $backup = $this->puzzle[$i][$j];
      $this->puzzle[$i][$j] = 0;
      if ($this->countSolutions($this->puzzle) !== 1) {
        $this->puzzle[$i][$j] = $backup;
      } else {
        $removedCells++;
      }
    }
  }

  protected function countSolutions(array $puzzle): int {
    $emptyCells = [];
    foreach ($puzzle as $rowIndex => $row) {
      foreach ($row as $colIndex => $cell) {
        if ($cell === 0) {
          $emptyCells[] = [$rowIndex, $colIndex];
        }
      }
    }
    return $this->countSolutionsRecursive($puzzle, $emptyCells);
  }

  protected function countSolutionsRecursive(array &$puzzle, array &$emptyCells): int {
    if (empty($emptyCells)) {
      return 1;
    }
    [$rowIndex, $colIndex] = array_shift($emptyCells);
    $options = $this->getValidOptions($rowIndex, $colIndex);
    $count = 0;
    foreach ($options as $value) {
      $puzzle[$rowIndex][$colIndex] = $value;
      $count += $this->countSolutionsRecursive($puzzle, $emptyCells);
      if ($count > 1) {
        break;
      }
      $puzzle[$rowIndex][$colIndex] = 0;
    }
    array_unshift($emptyCells, [$rowIndex, $colIndex]);
    return $count;
  }

  /**
   * Check constraints of a puzzle or solution to ensure it is valid.
   *
   * @param array $rows The rows of the puzzle.
   * @param array $columns The columns of the puzzle.
   * @param array $boxes The boxes of the puzzle.
   * @param bool $allowZeros Whether to allow 0 values.
   * @return bool True if the puzzle is valid, false otherwise.
   */
  protected function checkConstraints(array $rows, array $columns, array $boxes, bool $allowZeros = false): bool {
    foreach ($rows as $rowIndex => $row) {
      if (!$this->checkContainerForViolations($row, $allowZeros)) {
        return false;
      }
      foreach ($columns as $colIndex => $column) {
        if (!$this->checkContainerForViolations($column, $allowZeros) || !$this->checkContainerForViolations($boxes[$this->boxLookup[$rowIndex][$colIndex]], $allowZeros)) {
          return false;
        }
      }
    }
    return true;
  }

  /**
   * Generates an empty puzzle array.
   *
   * @return array The empty puzzle.
   */
  protected function generateEmptyPuzzle(): array {
    return array_fill(0, $this->getGridSize(), array_fill(0, $this->getGridSize(), 0));
  }

  /**
   * Ensures the puzzle array is of the correct size.
   *
   * @param array $puzzle The puzzle to validate.
   * @return bool True if the puzzle is valid, false otherwise.
   */
  protected function isValidPuzzleFormat(array $puzzle): bool {
    if (count($puzzle) != $this->getGridSize()) {
      return false;
    }

    foreach ($puzzle as $row) {
      if (count($row) != $this->getGridSize()) {
        return false;
      }
    }

    return true;
  }

  /**
   * Calculates the solution.
   *
   * A brute force backtracking algorithm that starts at the 0 value cell
   * closest to A1 on the grid and calculates is available options based on the
   * game's constraints. It will then populate the cell with the first option
   * and move on to the next cell by calling itself recursively. Should it
   * eventually find itself with no available options for a cell, it will
   * "backtrack" to the previous cell and try the next option until either a
   * solution is found or all options are exhausted.
   *
   * @param array $puzzle The puzzle to solve.
   * @return bool|array False if no solution is found, the solution otherwise.
   */
  protected function calculateSolution(array $puzzle): bool|array {
    $emptyCells = [];
    foreach ($puzzle as $rowIndex => $row) {
      foreach ($row as $colIndex => $cell) {
        if ($cell === 0) {
          $emptyCells[] = [$rowIndex, $colIndex];
        }
      }
    }
    return $this->backtrack($puzzle, $emptyCells);
  }

  protected function backtrack(array &$puzzle, array &$emptyCells): bool {
    if (empty($emptyCells)) {
      return true;
    }
    [$rowIndex, $colIndex] = array_shift($emptyCells);
    $options = $this->getValidOptions($rowIndex, $colIndex);
    foreach ($options as $value) {
      $puzzle[$rowIndex][$colIndex] = $value;
      if ($this->backtrack($puzzle, $emptyCells)) {
        return true;
      }
      $puzzle[$rowIndex][$colIndex] = 0;
    }
    array_unshift($emptyCells, [$rowIndex, $colIndex]);
    return false;
  }

  /**
   * Gets the valid options for a cell based on the constraints of the game.
   *
   * @param integer $rowIndex The row index of the cell.
   * @param integer $colIndex The column index of the cell.
   * @return array The valid options for the cell.
   */
  protected function getValidOptions(int $rowIndex, int $colIndex): array {
    $invalid = array_merge($this->solution[$rowIndex], $this->solutionColumns[$colIndex], $this->solutionBoxes[$this->boxLookup[$rowIndex][$colIndex]]);
    $invalid = array_unique($invalid);
    $valid = array_diff(range(1, $this->getGridSize()), $invalid);
    shuffle($valid);
    return $valid;
  }

  /**
   * Checks an array for violations.
   *
   * A array is deemed to contain violations if it contains any duplicate
   * values. The inclusion of 0 values can be specified via the $allowZeros
   * parameter.
   *
   * @param array $container The array to check.
   * @param bool $allowZeros Whether to allow 0 values.
   * @return bool True if the array is valid, false otherwise.
   */
  protected function checkContainerForViolations(array $container, bool $allowZeros = false): bool {
    if (!$allowZeros && in_array(0, $container)) {
      return false;
    }
    $container = array_filter($container, fn($value) => $value !== 0);
    if (count($container) !== count(array_unique($container))) {
      return false;
    }
    foreach (range(1, $this->getGridSize()) as $index) {
      if (isset($container[$index]) && $container[$index] > $this->getGridSize()) {
        return false;
      }
    }
    return true;
  }

  /**
   * Gets the total number of cells in the puzzle.
   *
   * @return int The total number of cells in the puzzle.
   */
  protected function getCellCount() {
    return $this->getGridSize() ** 2;
  }

  /**
   * Prepares references for the puzzle or solution. This is used to speed up
   * the process of checking constraints.
   *
   * @param bool $puzzle Whether to prepare the puzzle or solution.
   * @return void
   */
  protected function prepareReferences(bool $puzzle = true): void {
    if ($puzzle) {
      $source  = &$this->puzzle;
      $columns = &$this->puzzleColumns;
      $boxes   = &$this->puzzleBoxes;
    } else {
      $source  = &$this->solution;
      $columns = &$this->solutionColumns;
      $boxes   = &$this->solutionBoxes;
    }
    $this->setColumns($source, $columns);
    $this->setBoxes($source, $boxes);
  }

  /**
   * Sets a columns array linked to the puzzle by reference.
   *
   * Rebuilds the array from scratch to prevent unwanted cells from lingering
   * when shrinking the cell count.
   *
   * @param array $source The source array.
   * @param array $columns The columns array.
   * @return void
   */
  protected function setColumns(array &$source, array &$columns): void {
    $columns = [];
    $gridSize = $this->getGridSize();
    for ($i = 0; $i < $gridSize; $i++) {
      for ($j = 0; $j < $gridSize; $j++) {
        $columns[$j][$i] = &$source[$i][$j];
      }
    }
  }

  /**
   * Sets a boxes array linked to the puzzle by reference.
   *
   * Rebuilds the array from scratch to prevent unwanted cells from lingering
   * when shrinking the cell count.
   *
   * @param array $source The source array.
   * @param array $boxes The boxes array.
   * @return void
   */
  protected function setBoxes(array &$source, array &$boxes): void {
    $boxes = [];
    $gridSize = $this->getGridSize();
    for ($i = 0; $i < $gridSize; $i++) {
      for ($j = 0; $j < $gridSize; $j++) {
        $row = floor($i / $this->cellSize);
        $column = floor($j / $this->cellSize);
        $box = (int) floor($row * $this->cellSize + $column);
        $cell = ($i % $this->cellSize) * ($this->cellSize) + ($j % $this->cellSize);
        $boxes[$box][$cell] = &$source[$i][$j];
        $this->boxLookup[$i][$j] = $box;
      }
    }
  }
}
