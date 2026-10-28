<?php

use Darvis\Printserver\Support\TestPage;

test('the test page is a pdf a printer accepts', function () {
    $pdf = TestPage::pdf('Printer ZEBRA01');

    expect($pdf)->toStartWith('%PDF-1.4')
        ->and($pdf)->toEndWith('%%EOF')
        ->and($pdf)->toContain('(Printer ZEBRA01) Tj')
        ->and($pdf)->toContain('/MediaBox [0 0 595.28 841.89]');

    // Every object must be findable through the xref table, or a printer refuses the file.
    preg_match('/startxref\n(\d+)/', $pdf, $match);

    expect(substr($pdf, (int) $match[1], 4))->toBe('xref');
});

test('a label gets the exact size of the label, in points', function (string $size, string $mediaBox) {
    expect(TestPage::pdf('Label', $size))->toContain('/MediaBox [0 0 '.$mediaBox.']');
})->with([
    '4x2' => ['4x2', '288.00 144.00'],
    '4x4' => ['4x4', '288.00 288.00'],
    '4x6' => ['4x6', '288.00 432.00'],
    'unknown size' => ['letter', '595.28 841.89'],
]);

test('every line of the text becomes a line on the page, with a border around it', function () {
    $pdf = TestPage::pdf("Test page\nZEBRA01\nSize: 4x2 inch", '4x2');

    expect($pdf)->toContain('(Test page) Tj')
        ->and($pdf)->toContain('(ZEBRA01) Tj')
        ->and($pdf)->toContain('(Size: 4x2 inch) Tj')
        ->and($pdf)->toContain(' re S');
});

test('a long line gets a smaller font, so it stays on a small label', function () {
    preg_match('/\/F1 ([\d.]+) Tf/', TestPage::pdf(str_repeat('x', 80), '4x2'), $small);
    preg_match('/\/F1 ([\d.]+) Tf/', TestPage::pdf('x', '4x2'), $large);

    expect((float) $small[1])->toBeLessThan((float) $large[1])
        ->and((float) $small[1] * 80 * 0.55)->toBeLessThan(288);
});

test('parentheses, backslashes and accents do not break the pdf', function () {
    $pdf = TestPage::pdf('Lijn 1 (voorin) \\ café');

    expect($pdf)->toContain('(Lijn 1 \\(voorin\\) \\\\ caf'."\xE9".') Tj')
        ->and($pdf)->toContain('/Encoding /WinAnsiEncoding');
});
