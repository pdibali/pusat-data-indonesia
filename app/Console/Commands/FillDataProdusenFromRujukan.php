<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FillDataProdusenFromRujukan extends Command
{
    protected $signature = 'data:fill-produsen {--force : Overwrite existing produsen_id in data}';

    protected $description = 'Isi kolom produsen_id pada tabel data berdasarkan produsen_id dari rujukan terkait';

    public function handle(): int
    {
        $force = (bool) $this->option('force');

        $countQuery = DB::table('data')
            ->join('rujukan', 'data.rujukan_id', '=', 'rujukan.rujukan_id')
            ->whereNotNull('rujukan.produsen_id');

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
            DB::statement('UPDATE `data` JOIN `rujukan` ON `data`.`rujukan_id` = `rujukan`.`rujukan_id` SET `data`.`produsen_id` = `rujukan`.`produsen_id` WHERE `rujukan`.`produsen_id` IS NOT NULL');
        } else {
            DB::statement('UPDATE `data` JOIN `rujukan` ON `data`.`rujukan_id` = `rujukan`.`rujukan_id` SET `data`.`produsen_id` = `rujukan`.`produsen_id` WHERE `data`.`produsen_id` IS NULL AND `rujukan`.`produsen_id` IS NOT NULL');
        }

        $this->info("Selesai. {$count} baris di-update.");

        return self::SUCCESS;
    }
}