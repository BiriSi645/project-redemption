<?php

namespace App\Libraries;

use InvalidArgumentException;

final class SudokuPuzzles
{
    private const PUZZLES = [
        'beginner' => [
            'puzzle' => '530070000600195000098000060800060003400803001700020006060000280000419005000080079',
            'solution' => '534678912672195348198342567859761423426853791713924856961537284287419635345286179',
        ],
        'medium' => [
            'puzzle' => '000260701680070090190004500820100040004602900050003028009300074040050036703018000',
            'solution' => '435269781682571493197834562826195347374682915951743628519326874248957136763418259',
        ],
        'expert' => [
            'puzzle' => '300000000005009000200504000020000700160000058704310600000890100000067080000005437',
            'solution' => '397681524645279813218534976823956741169742358754318692472893165531467289986125437',
        ],
    ];

    public static function all(): array
    {
        return self::PUZZLES;
    }

    public static function has(string $difficulty): bool
    {
        return isset(self::PUZZLES[$difficulty]);
    }

    public static function random(string $difficulty): array
    {
        $data = self::get($difficulty);

        // Sudoku simetrileri geçerli ve tek çözümlü yapıyı korur.
        // Böylece sabit kaynak bulmacadan çok sayıda farklı tahta üretebiliriz.
        do {
            $digitMap = range(1, 9);
            shuffle($digitMap);
            $digitMap = array_combine(range(1, 9), $digitMap);

            $rowOrder = self::axisOrder();
            $columnOrder = self::axisOrder();
            $transpose = (bool) random_int(0, 1);

            $puzzle = self::transform(
                $data['puzzle'],
                $digitMap,
                $rowOrder,
                $columnOrder,
                $transpose
            );

            $solution = self::transform(
                $data['solution'],
                $digitMap,
                $rowOrder,
                $columnOrder,
                $transpose
            );
        } while ($puzzle === $data['puzzle']);

        return [
            'puzzle' => $puzzle,
            'solution' => $solution,
        ];
    }

    private static function axisOrder(): array
    {
        $groups = [0, 1, 2];
        shuffle($groups);

        $order = [];

        foreach ($groups as $group) {
            $inside = [0, 1, 2];
            shuffle($inside);

            foreach ($inside as $offset) {
                $order[] = ($group * 3) + $offset;
            }
        }

        return $order;
    }

    private static function transform(
        string $grid,
        array $digitMap,
        array $rowOrder,
        array $columnOrder,
        bool $transpose
    ): string {
        $result = '';

        for ($targetRow = 0; $targetRow < 9; $targetRow++) {
            for ($targetColumn = 0; $targetColumn < 9; $targetColumn++) {
                $sourceRow = $rowOrder[$targetRow];
                $sourceColumn = $columnOrder[$targetColumn];

                if ($transpose) {
                    [$sourceRow, $sourceColumn] = [$sourceColumn, $sourceRow];
                }

                $value = $grid[($sourceRow * 9) + $sourceColumn];

                $result .= $value === '0'
                    ? '0'
                    : (string) $digitMap[(int) $value];
            }
        }

        return $result;
    }

    public static function get(string $difficulty): array
    {
        if (! self::has($difficulty)) {
            throw new InvalidArgumentException('Geçersiz Sudoku zorluğu.');
        }

        return self::PUZZLES[$difficulty];
    }
}