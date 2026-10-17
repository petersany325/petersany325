<?php

namespace App\Support;

use Illuminate\Support\Facades\Schema;

/**
 * Lightweight schema helper used by Catalog Plugin in this sparse tree.
 */
class SchemaHealer
{
    public static function ensureColumns(string $table, array $columns): void
    {
        try {
            if (! Schema::hasTable($table)) {
                return;
            }
            foreach ($columns as $column => $definition) {
                if (is_int($column)) {
                    continue;
                }
                if (! Schema::hasColumn($table, $column)) {
                    Schema::table($table, function ($blueprint) use ($column, $definition) {
                        if (is_callable($definition)) {
                            $definition($blueprint);
                        }
                    });
                }
            }
        } catch (\Throwable) {
            //
        }
    }
}
