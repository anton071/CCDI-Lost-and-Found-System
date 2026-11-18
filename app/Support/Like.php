<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Portable, injection-safe "contains" matching. The wildcard characters % and _ typed by a user are
 * escaped with an explicit ESCAPE clause, which behaves the same on MySQL, MariaDB and SQLite
 * (a plain backslash escape is only the default on MySQL).
 */
final class Like
{
    public static function contains(string $value): string
    {
        return '%'.str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $value).'%';
    }

    /** @param \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Query\Builder $query */
    public static function where($query, string $column, string $value, string $boolean = 'and')
    {
        $wrapped = DB::getQueryGrammar()->wrap($column);   // $column is always a code constant, never user input

        return $query->whereRaw("{$wrapped} LIKE ? ESCAPE '!'", [self::contains($value)], $boolean);
    }
}
