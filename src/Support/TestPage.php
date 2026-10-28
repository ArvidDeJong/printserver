<?php

namespace Darvis\Printserver\Support;

/**
 * Builds a test page as a small PDF, so a test needs no pdf library.
 *
 * The page gets the exact size of the paper or label, a border just inside the edge and
 * the given lines of text. The border shows at a glance whether a label printer is
 * calibrated: on a good label it runs around all four sides.
 */
class TestPage
{
    /**
     * Paper and label sizes in millimetres, as width and height. Labels are in inches
     * (4x2 is 4 by 2 inch), the way label printers and their rolls are sold.
     *
     * @var array<string, array{0: float, 1: float}>
     */
    public const SIZES = [
        '4x2' => [101.6, 50.8],
        '4x4' => [101.6, 101.6],
        '4x6' => [101.6, 152.4],
        'a4' => [210.0, 297.0],
    ];

    /**
     * @param  string  $text  The text on the page; every line of it becomes a line on the page
     * @param  string  $size  One of the keys of SIZES; an unknown size falls back to a4
     */
    public static function pdf(string $text, string $size = 'a4'): string
    {
        [$widthMm, $heightMm] = self::SIZES[$size] ?? self::SIZES['a4'];

        $width = $widthMm * 72 / 25.4;
        $height = $heightMm * 72 / 25.4;
        $margin = min($width, $height) * 0.08;

        $lines = array_map(self::latin1(...), explode("\n", str_replace("\r", '', $text)));
        $longest = max(1, ...array_map('strlen', $lines));

        // Fit the text both ways: every line on the page, and the longest line within the border.
        $fontSize = min(
            14.0,
            ($height - 2 * $margin) / (count($lines) * 1.4),
            ($width - 2 * $margin) / ($longest * 0.55),
        );

        $stream = sprintf('1 w %.2F %.2F %.2F %.2F re S', $margin / 2, $margin / 2, $width - $margin, $height - $margin);
        $stream .= sprintf(' BT /F1 %.2F Tf', $fontSize);

        foreach ($lines as $number => $line) {
            $y = $height - $margin - $fontSize * (1 + $number * 1.4);
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $line);
            $stream .= sprintf(' 1 0 0 1 %.2F %.2F Tm (%s) Tj', $margin, $y, $escaped);
        }

        $stream .= ' ET';

        $objects = [
            "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n",
            "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n",
            sprintf("3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n", $width, $height),
            "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>\nendobj\n",
            "5 0 obj\n<< /Length ".strlen($stream)." >>\nstream\n".$stream."\nendstream\nendobj\n",
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [];

        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $start = strlen($pdf);
        $pdf .= "xref\n0 ".(count($objects) + 1)."\n0000000000 65535 f \n";

        foreach ($offsets as $offset) {
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }

        return $pdf."trailer\n<< /Size ".(count($objects) + 1)." /Root 1 0 R >>\nstartxref\n".$start."\n%%EOF";
    }

    /**
     * Converts UTF-8 to the encoding of the standard PDF fonts, so an accent prints as an
     * accent and not as two odd characters.
     */
    private static function latin1(string $line): string
    {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $line);

        return $converted === false ? $line : $converted;
    }
}
