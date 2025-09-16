<?php

namespace App\Classes;

class MagicSquare {
  private int $size = 5;
  private array $board = [
    [17, 24,  1,  8, 15],
    [23,  5,  7, 14, 16],
    [ 4,  6, 13, 20, 22],
    [10, 12, 19, 21,  3],
    [11, 18, 25,  2,  9],
  ];
  private array $magicSquare = [];
  private int $maxAttempts = 5000;

  public function __construct($gridSize = false) {
    $this->size = $gridSize ?? $this->size;
    $this->magicSquare = $this->board;
  }

  public function generateMagicSquare($flatten = true): array {
    // Shuffle rows.
    shuffle($this->magicSquare);

    // Transpose to columns.
    $transposed = array_map(null, ...$this->magicSquare);

    // Shuffle columns (by shuffling transposed rows).
    shuffle($transposed);

    // Transpose back to get new square.
    $this->magicSquare = array_map(null, ...$transposed);

    return ($flatten === true)
      ? array_merge(...$this->magicSquare)
      : $this->magicSquare;
  }
}
