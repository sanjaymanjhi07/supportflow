<?php

namespace App\Console\Commands;

use App\Services\SlaService;
use Illuminate\Console\Command;

class CheckSlaBreaches extends Command
{
    protected $signature = 'sla:check-breaches';

    protected $description = 'Scan open tickets across all tenants and flag any that have breached their SLA.';

    public function handle(SlaService $sla): int
    {
        $this->info('Scanning for SLA breaches...');

        $count = $sla->detectBreaches();

        $this->info("Done. {$count} ticket(s) newly marked as breached.");

        return self::SUCCESS;
    }
}
