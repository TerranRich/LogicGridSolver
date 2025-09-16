<?php

namespace App\Classes;

/**
 * Knight's Tour Algorithm Using Warnsdorff's Rule
 *
 * The knight is placed on the first block of an empty board and, moving
 * according to the rules of chess, must visit each square exactly once.
 * Warnsdorff's rule is a heuristic for finding a single knight's tour. The
 * knight is moved so that it always proceeds to the square from which the
 * knight will have the fewest onward moves. When calculating the number of
 * onward moves for each candidate square, we do not count moves that revisit
 * any square already visited. It is possible to have multiple solutions. The
 * algorithm is non-deterministic and may not always find a solution.
 */
class KnightTour {
  private $cellCount = 8;
  private $knightPos;
  private $success;
  private $jumps;
  private $visited = [];
  private $path = [];
  private $maxAttempts = 100;
  private $directions = [
    ['x' => -1, 'y' => -2],
    ['x' => -2, 'y' => -1],
    ['x' =>  1, 'y' => -2],
    ['x' =>  2, 'y' => -1],
    ['x' => -1, 'y' =>  2],
    ['x' => -2, 'y' =>  1],
    ['x' =>  1, 'y' =>  2],
    ['x' =>  2, 'y' =>  1],
  ];

  public function __construct(int $numCellsPerSide = 8) {
    $this->cellCount = $numCellsPerSide;
    $this->reset();
  }

  /**
   * Reset the board and the knight's starting position.
   *
   * @return void
   */
  private function reset() {
    $this->path = [];
    $this->jumps = 1;
    $this->success = false;
    $this->visited = array_fill(0, $this->cellCount * $this->cellCount, false);

    // Set the starting position of the knight.
    $this->knightPos = [
      'x' => rand(0, $this->cellCount - 1),
      'y' => rand(0, $this->cellCount - 1)
    ];

    // Mark the starting position as visited.
    $this->visited[$this->knightPos['x'] + $this->knightPos['y'] * $this->cellCount] = true;
    $this->path[] = $this->knightPos;
  }

  /**
   * Create an array of possible moves for the knight. A move is possible if
   * the knight is within the board bounds and the position has not been
   * visited. The knight can move in 8 possible directions. The function
   * returns an array of possible moves.
   *
   * @param array $pos The current position of the knight.
   * @return array An array of possible moves.
   */
  private function createMoves($pos) {
    $possibles = [];

    // Check if the knight can move to each of the 8 possible directions.
    foreach ($this->directions as $direction) {
      // Calculate the new position of the knight.
      $x = $pos['x'] + $direction['x'];
      $y = $pos['y'] + $direction['y'];

      // Check if the move is within the board bounds and has not been visited.
      if (
        $x > -1 &&
        $x < $this->cellCount &&
        $y > -1 &&
        $y < $this->cellCount &&
        !$this->visited[$x + $y * $this->cellCount]
      ) {
        // Add the possible move to the array.
        $possibles[] = [
          'x' => $x,
          'y' => $y,
        ];
      }
    }

    // Return the array of possible moves.
    return $possibles;
  }

  /**
   * Get the possible moves for the knight based on Warnsdorff's rule. The
   * function returns an array of possible moves sorted in ascending order
   * based on the number of onward moves for each move.
   *
   * @param array $pos The current position of the knight.
   * @return array An array of possible moves sorted based on Warnsdorff's rule.
   */
  private function getWarnsdorff($pos) {
    // Get an array of possible moves for the knight.
    $possibles = $this->createMoves($pos);

    // If there are no possible moves, return an empty array.
    if (count($possibles) < 1) {
      return [];
    }

    $moves = [];

    // Get the number of onward moves for each possible move.
    foreach ($possibles as $possible) {
      // Get an array of possible moves for the current move.
      $ps = $this->createMoves($possible);
      // Add the move to the array of moves.
      $moves[] = [
        'len' => count($ps),
        'pos' => $possible
      ];
    }
    // Sort the array of moves based on the number of onward moves.
    usort(array: $moves, callback: function($a, $b): int {
      return $a['len'] - $b['len']; // ascending order as per Warnsdorff's rule
    });

    // Return the array of moves sorted based on Warnsdorff's rule.
    return $moves;
  }

  /**
   * Get the board spaces array. The function returns an array of board space
   * indexes that represent the path of the knight. The path is generated
   * based on Warnsdorff's rule. The function returns an empty array if no
   * successful path is found.
   *
   * @return array An array of board space indexes that represent the path.
   */
  public function getBoardSpacesArray() {
    // Retry mechanism to attempt finding a solution multiple times.
    $attempts = $this->maxAttempts;
    while($attempts-- > 0) {
      // Reset the board and the knight's starting position.
      $this->reset();

      // Get the starting position of the knight.
      $pos = $this->knightPos;
      // Get the possible moves for the knight based on Warnsdorff's rule.
      $moves = $this->getWarnsdorff($pos);

      // Continue moving the knight until there are no more moves.
      while (count($moves) > 0) {
        $this->jumps++;
        $pos = $moves[0]['pos'];
        $this->visited[$pos['x'] + $pos['y'] * $this->cellCount] = true;
        $this->path[] = $pos;
        $moves = $this->getWarnsdorff($pos);
      }

      // Check if the path is successful.
      if (count($this->path) == $this->cellCount * $this->cellCount) {
        $this->success = true;
        break;
      }
    }

    // Convert the path positions to board space indexes.
    return array_map(function($pos) {
      return $pos['x'] + $pos['y'] * $this->cellCount;
    }, $this->path);
  }

  /**
   * Check if the path was successful. The function returns true if the path
   * was successful and false otherwise.
   *
   * @return bool True if the path was successful, false otherwise.
   */
  public function wasSuccess() {
    return $this->success;
  }

}
