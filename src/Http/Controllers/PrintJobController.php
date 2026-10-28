<?php

namespace Darvis\Printserver\Http\Controllers;

use Darvis\Printserver\Models\PrintJob;
use Darvis\Printserver\Services\PrintQueue;
use Darvis\Printserver\Support\PrintserverConfig;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * The endpoints a print box uses to fetch its work.
 *
 * The box posts to claim(), fetches the content with file() and reports back with
 * done() or failed(). All traffic is outgoing from the location.
 */
class PrintJobController extends Controller
{
    public function __construct(private readonly PrintQueue $queue) {}

    /**
     * Returns the waiting jobs and marks them claimed in the same transaction.
     *
     * The lock matters: without it a second box, or a second round, could take the
     * same job again and it would print twice.
     */
    public function claim(Request $request): JsonResponse
    {
        $box = (string) $request->attributes->get('printbox', 'onbekend');
        $administration = $this->queue->administration();
        $limit = PrintserverConfig::claimLimit();

        $jobs = DB::transaction(function () use ($box, $administration, $limit) {
            $jobs = PrintJob::where('status', PrintJob::STATUS_PENDING)
                ->when($administration !== null, fn ($query) => $query->where('administration', $administration))
                ->orderBy('id')
                ->limit($limit)
                ->lockForUpdate()
                ->get();

            if ($jobs->isNotEmpty()) {
                PrintJob::whereIn('id', $jobs->pluck('id'))->update([
                    'claimed_at' => now(),
                    'claimed_by' => $box,
                    'status' => PrintJob::STATUS_CLAIMED,
                ]);
            }

            return $jobs;
        });

        return response()->json([
            'jobs' => $jobs->map(fn (PrintJob $job) => [
                'format' => $job->format,
                'printer' => $job->printer,
                'title' => $job->title,
                'uuid' => $job->uuid,
            ])->values(),
        ]);
    }

    /**
     * Returns the content of one job.
     */
    public function file(string $uuid): Response
    {
        $job = PrintJob::where('uuid', $uuid)->firstOrFail();
        $contents = $job->contents();

        if ($contents === null) {
            return response('This file has already been cleaned up.', Response::HTTP_GONE);
        }

        return response($contents, Response::HTTP_OK, ['Content-Type' => 'application/octet-stream']);
    }

    /**
     * Marks the job as delivered to the printer and drops the file.
     */
    public function done(string $uuid): JsonResponse
    {
        $job = PrintJob::where('uuid', $uuid)->firstOrFail();

        $job->update([
            'printed_at' => now(),
            'status' => PrintJob::STATUS_PRINTED,
        ]);

        $job->forgetFile();

        return response()->json(['status' => PrintJob::STATUS_PRINTED]);
    }

    /**
     * Marks the job as failed.
     *
     * A failed job is deliberately not put back on pending: better a sheet that
     * does not come out than one that comes out twice.
     */
    public function failed(Request $request, string $uuid): JsonResponse
    {
        $job = PrintJob::where('uuid', $uuid)->firstOrFail();

        $job->update([
            'error' => substr((string) $request->input('error'), 0, 1000),
            'status' => PrintJob::STATUS_FAILED,
        ]);

        Log::warning('A print job failed on the print box', [
            'error' => $job->error,
            'printbox' => $request->attributes->get('printbox'),
            'printer' => $job->printer,
            'uuid' => $job->uuid,
        ]);

        return response()->json(['status' => PrintJob::STATUS_FAILED]);
    }
}
