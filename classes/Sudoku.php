<?php

namespace App\Classes;

class Sudoku {

  private array $board = [];
  private array $solution = [];
  private int $subgridSize;

  private int $difficulty = 20;

  private const DIFFICULTY_EASY = 20;
  private const DIFFICULTY_MEDIUM = 40;
  private const DIFFICULTY_HARD = 60;

  public function __construct(int $subgridSize = 3) {
    $this->subgridSize = $subgridSize;
    $gridSize = $this->getGridSize();
    $this->board = array_fill(0, $gridSize, array_fill(0, $gridSize, 0));
    $this->solution = array_fill(0, $gridSize, array_fill(0, $gridSize, 0));
  }

  public function generatePuzzle($difficulty = self::DIFFICULTY_EASY) {
    $this->difficulty = max(min((int)$difficulty, $this->getMaxCellId()), 1);
    $this->fillDiagonal();
    $this->fillRemaining(0, $this->getSubgridSize());
    $this->solution = $this->board; // shallow copy is enough
    $this->removeDigits();
  }

  public function solve(): void {
    $this->board = $this->solution; // shallow copy is enough
  }

  public function getPuzzle(): array {
    return $this->board;
  }

  public function getPuzzleString(): string {
    return implode('', array_merge(...$this->board));
  }

  public function getSolution(): array {
    return $this->solution;
  }

  public function getSolutionString(): string {
    return implode('', array_merge(...$this->solution));
  }

  public function getDifficulty(): int {
    return $this->difficulty;
  }

  private function fillDiagonal(): void {
    for ($i = 0; $i < $this->getGridSize(); $i += $this->getSubgridSize()) {
      $this->fillBox($i, $i);
    }
  }

  private function fillBox($row, $col): void {
    $subgridSize = $this->getSubgridSize();
    $nums = range(1, $this->getGridSize());
    shuffle($nums);
    for ($i = 0; $i < $subgridSize; $i++) {
      for ($j = 0; $j < $subgridSize; $j++) {
        $this->board[$row + $i][$col + $j] = array_pop($nums);
      }
    }
  }

  private function fillRemaining($i, $j) {
    $gridSize = $this->getGridSize();
    $subgridSize = $this->getSubgridSize();

    if ($j >= $gridSize && $i < $gridSize - 1) {
      $i++;
      $j = 0;
    }
    if ($i >= $gridSize && $j >= $gridSize) {
      return true;
    }

    if ($i < $subgridSize) {
      if ($j < $subgridSize) {
        $j = $subgridSize;
      }
    } elseif ($i < $gridSize - $subgridSize) {
      if ($j == floor($i / $subgridSize) * $subgridSize) {
        $j += $subgridSize;
      }
    } else {
      if ($j == $gridSize - $subgridSize) {
        $i++;
        $j = 0;
        if ($i >= $gridSize) {
          return true;
        }
      }
    }

    for ($num = 1; $num <= $gridSize; $num++) {
      if ($this->isSafe($i, $j, $num)) {
        $this->board[$i][$j] = $num;
        if ($this->fillRemaining($i, $j + 1)) {
          return true;
        }
        $this->board[$i][$j] = 0;
      }
    }

    return false;
  }

  private function removeDigits() {
    $gridSize = $this->getGridSize();
    $count = $this->getRemoveCount();
    while ($count > 0) {
      $cellId = mt_rand(0, $this->getMaxCellId());
      $row = floor($cellId / $gridSize);
      $col = $cellId % $gridSize;

      if ($this->board[$row][$col] != 0) {
        $backup = $this->board[$row][$col];
        $this->board[$row][$col] = 0;

        if ($this->countSolutions($this->board, 2) != 1) {
          $this->board[$row][$col] = $backup;
        } else {
          $count--;
        }
      }
    }
  }

  private function countSolutions($board, $limit) {
    $row = $col = 0;
    if (!$this->findEmptyCell($board, $row, $col)) {
      return 1;
    }
    $solutions = 0;
    for ($num = 1; $num <= $this->getGridSize(); $num++) {
      if ($this->isSafe($row, $col, $num)) {
        $board[$row][$col] = $num;
        $solutions += $this->countSolutions($board, $limit - $solutions);
        if ($solutions >= $limit) {
          return $solutions;
        }
        $board[$row][$col] = 0;
      }
    }
    return $solutions;
  }

  private function getRemoveCount() {
    return $this->difficulty;
  }

  private function getMaxCellId() {
    return $this->getGridSize() ** 2 - 1;
  }

  private function findEmptyCell($board, &$row, &$col) {
    $gridSize = $this->getGridSize();

    for ($row = 0; $row < $gridSize; $row++) {
      for ($col = 0; $col < $gridSize; $col++) {
        if ($board[$row][$col] == 0) {
          return true;
        }
      }
    }
    return false;
  }

  private function isSafe($row, $col, $num) {
    $gridSize = $this->getGridSize();
    $subgridSize = $this->getSubgridSize();
    $boxStartRow = $row - $row % $subgridSize;
    $boxStartCol = $col - $col % $subgridSize;

    for ($i = 0; $i < $gridSize; $i++) {
      if ($this->board[$row][$i] == $num || $this->board[$i][$col] == $num) {
        return false;
      }
    }
    for ($i = 0; $i < $subgridSize; $i++) {
      for ($j = 0; $j < $subgridSize; $j++) {
        if ($this->board[$i + $boxStartRow][$j + $boxStartCol] == $num) {
          return false;
        }
      }
    }
    return true;
  }

  private function getSubgridSize(): int {
    return $this->subgridSize;
  }

  private function getGridSize(): int {
    return $this->getSubgridSize() ** 2;
  }
}
