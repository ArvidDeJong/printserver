<?php

namespace Darvis\Printserver\Models;

use Darvis\Printserver\Support\PrintserverConfig;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * A print job waiting for a print box.
 *
 * The content itself is not stored in the database but on the configured disk,
 * at {@see $path}.
 *
 * @property int $id
 * @property string $uuid
 * @property string|null $administration
 * @property string $printer
 * @property string|null $title
 * @property string $format
 * @property string $path
 * @property string $status
 * @property string|null $claimed_by
 * @property Carbon|null $claimed_at
 * @property Carbon|null $printed_at
 * @property string|null $error
 */
class PrintJob extends Model
{
    public const STATUS_CLAIMED = 'claimed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_PENDING = 'pending';

    public const STATUS_PRINTED = 'printed';

    /** @var list<string> */
    protected $fillable = [
        'administration',
        'claimed_at',
        'claimed_by',
        'error',
        'format',
        'path',
        'printed_at',
        'printer',
        'status',
        'title',
        'uuid',
    ];

    /**
     * Returns the content of the job, or null once the file has been cleaned up.
     */
    public function contents(): ?string
    {
        $disk = Storage::disk(PrintserverConfig::disk());

        return $disk->exists($this->path) ? $disk->get($this->path) : null;
    }

    /**
     * Removes the file of the job. The row stays as history.
     */
    public function forgetFile(): void
    {
        $disk = Storage::disk(PrintserverConfig::disk());

        if ($this->path !== '' && $disk->exists($this->path)) {
            $disk->delete($this->path);
        }
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'claimed_at' => 'datetime',
            'printed_at' => 'datetime',
        ];
    }
}
