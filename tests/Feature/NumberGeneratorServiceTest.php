<?php

use App\Enums\DocumentType;
use App\Models\DocumentSequence;
use App\Services\NumberGeneratorService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

// Test format correctness
it('generates GRN codes in the correct format', function () {
    $service = new NumberGeneratorService;
    $code = $service->next(DocumentType::GRN, Carbon::parse('2025-01-15'));
    expect($code)->toMatch('/^GRN20250115\/\d{5}$/');
});

it('generates INV codes in the correct format', function () {
    $service = new NumberGeneratorService;
    $code = $service->next(DocumentType::INV, Carbon::parse('2025-03-20'));
    expect($code)->toMatch('/^INV20250320\/\d{5}$/');
});

// Test uniqueness and incrementing
it('generates unique and incrementing codes for the same day and type', function () {
    $service = new NumberGeneratorService;
    $date = Carbon::now();

    $code1 = $service->next(DocumentType::GRN, $date);
    $code2 = $service->next(DocumentType::GRN, $date);
    $code3 = $service->next(DocumentType::GRN, $date);

    expect($code1)->toEqual(sprintf('GRN%s/%05d', $date->format('Ymd'), 1));
    expect($code2)->toEqual(sprintf('GRN%s/%05d', $date->format('Ymd'), 2));
    expect($code3)->toEqual(sprintf('GRN%s/%05d', $date->format('Ymd'), 3));
});

it('generates codes starting from 1 for a new day or type', function () {
    $service = new NumberGeneratorService;
    $date1 = Carbon::parse('2025-01-01');
    $date2 = Carbon::parse('2025-01-02');

    $codeGRN1 = $service->next(DocumentType::GRN, $date1);
    $codeINV1 = $service->next(DocumentType::INV, $date1);
    $codeGRN2 = $service->next(DocumentType::GRN, $date2);

    expect($codeGRN1)->toEqual(sprintf('GRN%s/%05d', $date1->format('Ymd'), 1));
    expect($codeINV1)->toEqual(sprintf('INV%s/%05d', $date1->format('Ymd'), 1));
    expect($codeGRN2)->toEqual(sprintf('GRN%s/%05d', $date2->format('Ymd'), 1));
});

// Test concurrency safety
it('generates unique codes under concurrent access', function () {
    $service = new NumberGeneratorService;
    $date = Carbon::now();
    $docType = DocumentType::GRN;

    $codes = [];
    $promises = [];

    // Simulate concurrent calls by running them in separate database transactions
    // This is a simplified simulation as true concurrency requires process/thread management
    // which is outside the scope of a single PHPUnit test execution.
    // However, DB::transaction with lockForUpdate() should handle this correctly.
    for ($i = 0; $i < 10; $i++) {
        $codes[] = DB::transaction(function () use ($service, $docType, $date) {
            return $service->next($docType, $date);
        });
    }

    // Assert that all generated codes are unique
    expect($codes)->toHaveCount(10);
    expect(array_unique($codes))->toHaveCount(10);

    // Assert that the sequence in the database is correct
    $sequence = DocumentSequence::where('doc_type', $docType)->where('ymd', $date->format('Ymd'))->first();
    expect($sequence->seq)->toEqual(10);
});
