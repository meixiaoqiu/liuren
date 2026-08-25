<?php

namespace App\Console\Commands;

use App\Filament\Resources\PanResource;
use App\Support\PanCreationData;
use App\Support\PanRegression;
use com\tyme\solar\SolarTime;
use DateTimeImmutable;
use Illuminate\Console\Command;
use RuntimeException;

class InitializePanRegression extends Command
{
    protected $signature = 'pan:regression-initialize';

    protected $description = 'Create the initial immutable 1440-pan regression fixture';

    public function handle(): int
    {
        $path = PanRegression::fixturePath();

        if (is_file($path)) {
            $this->error('The 1440-pan fixture already exists and will not be overwritten.');

            return self::FAILURE;
        }

        $inputs = $this->representativeInputs();
        $cases = [];

        foreach ($inputs as $caseId => $input) {
            PanResource::qipan($input);
            $pan = session('pan');
            $record = PanCreationData::fromCalculatedPan($pan, $input);

            if (PanRegression::caseId($pan) !== $caseId) {
                throw new RuntimeException("Representative input changed state: {$caseId}");
            }

            $cases[$caseId] = [
                'input' => $input,
                'expected' => PanRegression::normalize($record),
            ];
        }

        ksort($cases);
        file_put_contents($path, json_encode([
            'version' => 1,
            'case_count' => PanRegression::CASE_COUNT,
            'excluded_fields' => PanRegression::EXCLUDED_FIELDS,
            'cases' => $cases,
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).PHP_EOL);

        $this->info(sprintf('Created %d protected pan cases.', count($cases)));

        return self::SUCCESS;
    }

    private function representativeInputs(): array
    {
        $inputs = [];
        $date = new DateTimeImmutable('2000-01-01');
        $end = $date->modify('+60 years');
        $hours = [0, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19, 21];

        while ($date < $end && count($inputs) < PanRegression::CASE_COUNT) {
            foreach ($hours as $hour) {
                $input = $date->format('Y-m-d').' '.sprintf('%02d:00:00', $hour);
                $solar = SolarTime::fromYmdHms(
                    (int) $date->format('Y'),
                    (int) $date->format('m'),
                    (int) $date->format('d'),
                    $hour,
                    0,
                    0,
                );
                $yuejiang = PanResource::$jieqi2Yuejiang[$solar->getTerm()->getIndex()];
                [$rigan, $rizhi] = PanResource::$jiazi2Ganzhi[
                    $solar->getLunarHour()->getEightChar()->getDay()->getIndex()
                ];
                $shizhi = PanResource::$hour2Shichen[$hour];
                $pointer = ($yuejiang - $shizhi + 12) % 12;
                $period = in_array($shizhi, [3, 4, 5, 6, 7, 8], true) ? 'day' : 'night';
                $dayIndex = array_search([$rigan, $rizhi], PanResource::$jiazi2Ganzhi, true);
                $caseId = sprintf('pointer-%02d_day-%02d_%s', $pointer, $dayIndex, $period);
                $inputs[$caseId] ??= $input;
            }

            $date = $date->modify('+1 day');
        }

        if (count($inputs) !== PanRegression::CASE_COUNT) {
            throw new RuntimeException(sprintf(
                'Found %d of %d required regression states.',
                count($inputs),
                PanRegression::CASE_COUNT,
            ));
        }

        ksort($inputs);

        return $inputs;
    }
}
