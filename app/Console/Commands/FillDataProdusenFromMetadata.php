<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FillDataProdusenFromMetadata extends Command
{
    protected $signature = 'data:fill-produsen {--force : Overwrite existing produsen_id in data}';

    protected $description = 'Isi kolom produsen_id pada tabel data berdasarkan produsen_id dari metadata terkait';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $countQuery = DB::table('data')
            ->join('metadata', 'data.metadata_id', '=', 'metadata.metadata_id');

        if (!$force) {
            $countQuery->whereNull('data.produsen_id');
        }

        $count = $countQuery->count();

        if ($count === 0) {
            $this->info('Tidak ada baris yang perlu di-update.');
            return self::SUCCESS;
        }

        if (!$this->confirm("Akan mengupdate {$count} baris. Lanjutkan?")) {
            $this->info('Dibatalkan.');
            return self::SUCCESS;
        }

        if ($force) {
            DB::statement('UPDATE `data` JOIN `metadata` ON `data`.`metadata_id` = `metadata`.`metadata_id` SET `data`.`produsen_id` = `metadata`.`produsen_id`');
        } else {
            DB::statement('UPDATE `data` JOIN `metadata` ON `data`.`metadata_id` = `metadata`.`metadata_id` SET `data`.`produsen_id` = `metadata`.`produsen_id` WHERE `data`.`produsen_id` IS NULL');
        }

        $this->info("Selesai. {$count} baris di-update.");

        return self::SUCCESS;
    }
}
