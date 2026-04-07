<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Migration to ensure all FontControl columns exist.
 * This replaces the old ensureSchemaIsReady() logic from ServiceProvider.
 */
return new class extends Migration {

    public function up(): void
    {


        if (!Schema::hasTable('font_control_settings')) {
            return;
        }

        //If it does, then proceed
        try {
            DB::statement("DROP TEMPORARY TABLE IF EXISTS tmp_file_storage_delete_ids");

            DB::statement("
  CREATE TEMPORARY TABLE tmp_file_storage_delete_ids (id BIGINT PRIMARY KEY)
  ENGINE=InnoDB
  AS
  SELECT fs.id
  FROM file_storage fs
  JOIN (
    SELECT restaurant_id, filename, MIN(id) AS keep_id
    FROM file_storage
    GROUP BY restaurant_id, filename
    HAVING COUNT(*) > 1
  ) k
    ON k.restaurant_id = fs.restaurant_id
   AND k.filename = fs.filename
  WHERE fs.id <> k.keep_id
");


            do {
                $deleted = DB::affectingStatement("
      DELETE fs
      FROM file_storage fs
      JOIN (
        SELECT id FROM tmp_file_storage_delete_ids LIMIT 10000
      ) d ON d.id = fs.id
    ");

                DB::statement("DELETE FROM tmp_file_storage_delete_ids LIMIT 10000");
            } while ($deleted > 0);

            // Remove transaction -- DDL statements like CREATE TABLE, RENAME, DROP TABLE auto-commit in MySQL.
            DB::statement("CREATE TABLE file_storage_new LIKE file_storage");
            DB::statement("ALTER TABLE file_storage_new AUTO_INCREMENT = 1");
            DB::statement("
            INSERT INTO file_storage_new (
              restaurant_id, filename,
              path, type, size, storage_location,
              created_at, updated_at
            )
            SELECT
              restaurant_id, filename,
              path, type, size, storage_location,
              created_at, updated_at
            FROM file_storage
            ORDER BY id ASC
        ");

            DB::statement("RENAME TABLE file_storage TO file_storage_old, file_storage_new TO file_storage");
            DB::statement("DROP TEMPORARY TABLE IF EXISTS tmp_file_storage_delete_ids");
            DB::statement("DROP TABLE file_storage_old");
        } catch (\Exception $e) {
            dd($e->getMessage());
        }
    }

    public function down(): void
    {
        // Don't remove columns in down() - safer to keep data
    }
};
