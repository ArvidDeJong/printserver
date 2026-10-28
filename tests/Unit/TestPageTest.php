<?php

use Darvis\Printserver\Support\TestPage;

test('the test page is a pdf a printer accepts', function () {
    $pdf = TestPage::pdf('Printer ZEBRA01');

    expect($pdf)->toStartWith('%PDF-1.4')
        ->and($pdf)->toEndWith('%%EOF')
        ->and($pdf)->toContain('(Printer ZEBRA01) Tj')
        ->and($pdf)->toContain('/MediaBox [0 0 595 842]');

    // Every object must be findable through the xref table, or a printer refuses the file.
    preg_match('/startxref\n(\d+)/', $pdf, $match);

    expect(substr($pdf, (int) $match[1], 4))->toBe('xref');
});

test('parentheses and backslashes in the text do not break the pdf', function () {
    $pdf = TestPage::pdf('Line 1 (left) \\ right');

    expect($pdf)->toContain('(Line 1 \\(left\\) \\\\ right) Tj');
});
