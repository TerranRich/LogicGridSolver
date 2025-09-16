# Logic Grid Problem Solver

Are you a fan of puzzles like the following?

![Logic grid problem example](https://i.imgur.com/ibmPPE5.png)

This tool solves most logic puzzles of this type by accepting a series of clues in the form of "constraints". Each constraint class here handles a different clue type.

## How to Use

In PHP, make sure that you are autoloading classes. Then, take your puzzle and break it down into categories and values in the form of "A3", "C2", "D5", etc. For numerical values or any values that involve sequential values (e.g., days of the week), that category letter will be used as the "rank" category for clues that reference it.

For the example above, the categories and values would be:
* Customers: **A**
  * Jeff: **A1**
  * Kelly: **A2**
  * Lucas: **A3**
  * Margarita: **A4**
  * Nicole: **A5**
* Dummy Names: **B**
  * Sniffletoe: **B1**
  * ...
  * Xavier: **B5**
* Cities: **C**
  * Baltimore: **C1**
  * ...
  * Vancouver: **C5**
* Prices: **D**
  * $750: **D1**
  * ...
  * $1,750: **D5**

For ranked categories, like "Prices" above, the increments between each successive value must be the same. For example, if a clue said that one item is "$500 greater than" another, we count that as **2** increments, and pass that value into the solver. I plan to support unevenly-incremented values in the future, but for now this is the one main limitation of the solver.

The clues for the above puzzle are:
1. The $1,750 piece is "Tombawomba".
2. Margarita's piece cost $750.
3. Lucas's puppet isn't going to St. Moritz.
4. The piece going to St. Moritz isn't "Utrecht".
5. Lucas's dummy, the $1,000 puppet, and the piece going to Mexico City are three different dummies.
6. The dummy going to Vancouver cost 250 dollars more than "Utrecht".
7. "Xavier" cost 500 dollars less than "Waldarama".
8. "Utrecht" cost somewhat more than the piece going to Oslo.
9. Kelly's dummy is either the piece going to St. Moritz or the $1,250 dummy.
10. Of the $1,250 piece and Jeff's piece, one is "Tombawomba" and the other is going to Vancouver.

What we must do next is break down the clues into basic constraints:
1. "$1,750 is Tombawoma" → `D5 = B2`. (We'll see why this format is important later on.)
2. "Margarita is $750" → `A4 = D1`.
3. "Lucas is not St. Moritz" → `A3 ≠ C4`.
4. "St. Moritz is not Utrecht" → `C4 ≠ B3`.
5. "Lucas, $1,000, and Mexico City are distinct items" → `(A3 | D2 | C2)`.
6. "Vancouver has a greater price than Utrecht by 1 increment" (each price is $250 greater than the one before) → `C5 > B3 by 1, rank: D`.
7. "Xavier has a lower price than Waldarama by 2 increments" (remember, each increment is $250) → `B5 < B4 by 2, rank: D`.
8. "Utrecht has a greater price than Oslo by an unknown increment" → `B3 > C3 by ?, rank: D`.
9. "Kelly is either St. Moritz or $1,250" → `A2 = (C4 or D3)`.
10. "Of $1,250 and Jeff, one is Tombawomba and the other is Vancouver" → `(D3 / A1) = (B2 / C5)`.

Now that we have only the crucial information required, let's begin. Make sure to `use` the `Puzzle` and `Solution` classes, as well as each of the `*Constraint` classes you will be needing. In our case, we need each one:

```php
use App\Classes\LogicGridSolver\Constraint\AllDifferentConstraint;
use App\Classes\LogicGridSolver\Constraint\EitherOrConstraint;
use App\Classes\LogicGridSolver\Constraint\EqualityConstraint;
use App\Classes\LogicGridSolver\Constraint\InequalityConstraint;
use App\Classes\LogicGridSolver\Constraint\RankExactDiffConstraint;
use App\Classes\LogicGridSolver\Constraint\RankGreaterConstraint;
use App\Classes\LogicGridSolver\Puzzle;
use App\Classes\LogicGridSolver\Solver;
```

We start by instantiating both the `Puzzle` and the `Solver` individually, passing the number of values in each category as the sole argument to `Puzzle`:
```php
$puzzle = new Puzzle(5); // 5 values per category
$solver = new Solver();  // used later
```

Then, we add each category as letters:
```php
foreach (['A', 'B', 'C', 'D'] as $category) {
  $puzzle->addCategory($category);
}
```

To convert clues into constraints, we must go over each one. Luckily, our puzzle above requires each of the constraints available. Let's look through each clue in order, which also uses successively more complex constraints. Starting with the simplest:

### Equality Constraint
This constraint specifies that two separate items are in fact the same item. In the case of the first clue, D5 and B2 are the same item (`D5 = B2`). To pass this along to the puzzle object as a constraint:
```php
$puzzle->addConstraint(new EqualityConstraint('D5', 'B2'));
```

As you can see, each item's category-value pairing are set as the two attributes passed into the `Puzzle` instance.

Clue #2 (`A4 = D1`) is also an Equality constraint:
```php
$puzzle->addConstraint(new EqualityConstraint('A4', 'D1'));
```

### Inequality Constraint
This constraint specifies that two items are known to be separate from each other. For clue #3 (`A3 ≠ C4`):
```php
$puzzle->addConstraint(new InequalityConstraint('A3', 'C4'));
```

The next clue (`C4 ≠ B3`) is also an inequality:
```php
$puzzle->addConstraint(new InequalityConstraint('C4', 'B3'));
```

### AllDifferent Constraint
This constraint specifies that both/all items passed along are distinct, separate items. Clue #5 defines Lucas, $1,000, and Mexico City as distinct from one another (`(A3 | D2 | C2)`). Since there can be any number of items defined as seprate from one another, we pass the items along as an array:
```php
$puzzle->addConstraint(new AllDifferentConstraint(['A3', 'D2', 'C2']);
```

### RankExactDiff Constraint
This constraint specifies that one item in the puzzle has a greater ranking than another, and defines which category acts as the rank and how many increments by which it is greater. In the case of the first clue, C5 is greater than B3 by 1 increment, with D as the ranked category (`C5 > B3 by 1, rank: D`). To define this constraint, we pass along both items, the ranked category, and the increment amount. If the increment is a *positive* value, then the left number is *greater than* the right. If it's *negative*, then left is less than right.
```php
$puzzle->addConstraint(new RankExactDiffConstraint('C5', 'B3', 'D', 1));
```

We do the same for clue #7 (`B5 < B4 by 2, rank: D`). Since the lefthand item is *less than* the righthand one, the increment would be **-2**:
```php
$puzzle->addConstraint(new RankExactDiffConstraint('B5', 'B4', 'D', -2));
```

> **NOTE:** If we wanted to keep the increment positive, swap the items, which also swaps the inequality (from `<` to `>`). Then, we could use **2** as the increment value.

### RankGreater Constraint
This constraint specifies that one item in the puzzle has a greater ranking than another, and defines which category acts as the rank. In the case of clue #8 (`B3 > C3 by ?, rank: D`), we would define this constraint as such:
```php
$puzzle->addConstraint(new RankGreaterConstraint('B3', 'C3', 'D')); // note the lack of increment value, since it's unknown here
```

### EitherOr Constraint
This constraint specifies that any number of items, in some order, are equal to any number of other items. This constraint requires each *combination* of possible equalities to be passed along. For clue #9 (`A2 = (C4 or D3)`), we would pass the *two* possible equalities:
```php
$puzzle->addConstraint(new EitherOrConstraint([
  [ ['A2', 'C4'] ],
  [ ['A2', 'D3'] ].
]);
```

It may seem confusing at first to have double sets of array brackets, but we'll see why this is the case. For the final clue (`(D3 / A1) = (B2 / C5)`), we have two *sets* of possibilities — either: `D3 = B2` and `A1 = C5`, *or* `D3 = C5` and `A1 = B2`. This is defined like so:
```php
$puzzle->addConstraint(new EitherOrConstraint([
  [ ['D3', 'B2'], ['A1', 'C5'] ], // possibility 1: D3 = B2 and A1 = C5
  [ ['D3', 'C5'], ['A1', 'B2'] ], // possibility 2: D3 = C5 and A1 = B2
]));
```

As you can see above, the first item in the array passed to the constraint defines the first possibility, itself being an array of each equality in the possibility, each equality being a pair (array) of component items in that equality. Basically, `possibilities → equalities → components`.

### Wrapping it All Up
Now that we're all done definining all 10 constraints, all we do is call the solver's `solve` method on the puzzle object:
```php
$result = $solver->solve($puzzle);
```

This `$result` will be an array of item sets, with each set composed of all items that are equal to each other. In the case of the puzzle above (spoilers!) the solution would be returned as such (this is a `var_dump` but condensed for readability):
```php
array(5) {
  [0] => array(4) {
    ["A"] => string(2) "A1"
    ["B"] => string(2) "B2"
    ["C"] => string(2) "C2"
    ["D"] => string(2) "D5"
  }
  [1] => array(4) {
    ["A"] => string(2) "A2"
    ["B"] => string(2) "B1"
    ["C"] => string(2) "C4"
    ["D"] => string(2) "D4"
  }
  [2] => array(4) {
    ["A"] => string(2) "A3"
    ["B"] => string(2) "B4"
    ["C"] => string(2) "C5"
    ["D"] => string(2) "D3"
  }
  [3] => array(4) {
    ["A"] => string(2) "A4"
    ["B"] => string(2) "B5"
    ["C"] => string(2) "C3"
    ["D"] => string(2) "D1"
  }
  [4] => array(4) {
    ["A"] => string(2) "A5"
    ["B"] => string(2) "B3"
    ["C"] => string(2) "C1"
    ["D"] => string(2) "D2"
  }
}
```

The first grouping specifies that `A1 = B2 = C2 = D5`, the second speificies `A2 = B1 = C4 = D4`, and so on. And this is the correct solution:

![Completed logic problem from above](https://i.imgur.com/VFDD2IA.png)
