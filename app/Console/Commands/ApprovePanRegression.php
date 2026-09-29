<?php

namespace App\Console\Commands;

use App\Filament\Resources\PanResource;
use App\Support\PanCreationData;
use App\Support\PanRegression;
use Illuminate\Console\Command;

class ApprovePanRegression extends Command
{
    protected $signature = 'pan:regression-approve {cases* : Exact case IDs to approve}';

    protected $description = 'Approve current output for explicitly named pan regression cases';

    public function handle(): int
    {
        $fixture = PanRegression::loadFixture();
        $caseIds = array_values(array_unique($this->argument('cases')));

        foreach ($caseIds as $caseId) {
            if (! isset($fixture['cases'][$caseId])) {
                $this->error("Unknown case ID: {$caseId}");

                return self::FAILURE;
            }
        }

        $updates = [];

        foreach ($caseIds as $caseId) {
            $case = $fixture['cases'][$caseId];
            PanResource::qipan($case['input']);
            $record = PanCreationData::fromCalculatedPan(session('pan'), $case['input']);
            $actual = PanRegression::normalize($record);
            $differences = PanRegression::fieldDifferences($case['expected'], $actual);

            if ($differences !== []) {
                $updates[$caseId] = ['actual' => $actual, 'differences' => $differences];
                $this->line($caseId);

                foreach ($differences as $field => $change) {
                    $this->line(sprintf(
                        '  %s: %s -> %s',
                        $field,
                        json_encode($change['before'], JSON_UNESCAPED_UNICODE),
                        json_encode($change['after'], JSON_UNESCAPED_UNICODE),
                    ));
                }
            }
        }

        if ($updates === []) {
            $this->info('The named cases have no changes.');

            return self::SUCCESS;
        }

        if (! $this->confirm('Approve only these displayed changes?')) {
            return self::FAILURE;
        }

        foreach ($updates as $caseId => $update) {
            $fixture['cases'][$caseId]['expected'] = $update['actual'];
        }

        file_put_contents(
            PanRegression::fixturePath(),
            json_encode($fixture, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL,
        );

        $this->info(sprintf('Approved %d explicitly named cases.', count($updates)));

        return self::SUCCESS;
    }
}
