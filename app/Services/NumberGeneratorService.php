<?php

namespace App\Services;

use App\Enums\DocumentType;
use App\Models\DocumentSequence;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class NumberGeneratorService
{
    public function next(DocumentType $docType, ?Carbon $date = null): string
    {
        $date = $date ?? Carbon::now();
        $ymd = $date->format('Ymd');

        return DB::transaction(function () use ($docType, $ymd) {
            $sequence = DocumentSequence::query()
                ->where('doc_type', $docType)
                ->where('ymd', $ymd)
                ->lockForUpdate()
                ->first();

            if (! $sequence) {
                $sequence = DocumentSequence::create([
                    'doc_type' => $docType,
                    'ymd' => $ymd,
                    'seq' => 0,
                ]);
            }

            $sequence->increment('seq');

            return sprintf('%s%s/%05d', $docType->value, $ymd, $sequence->seq);
        });
    }
}
