<?php

/*
 * A print box on location fetches its print jobs here over HTTPS and prints them
 * on its own network. All traffic is outgoing from that location, so no port has
 * to be open on its router.
 */

return [

    /*
     * The administration this installation belongs to, for a codebase that runs
     * one installation per branch. A token may name an administration; when it
     * does and it differs from this value the box is refused, so a box of one
     * branch can never pick up the work of another. Leave it null when there is
     * only one administration.
     */
    'administration' => env('PRINTSERVER_ADMINISTRATION', env('ADMINISTRATION')),

    /*
     * Only these addresses may reach the endpoints. Empty means every address is
     * allowed and the token does all the work. Put the fixed address of the
     * location here. Behind a proxy TrustProxies has to be right.
     */
    'allowed_ips' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('PRINTSERVER_ALLOWED_IPS', ''))
    ))),

    /*
     * How many jobs one box may claim in a single round.
     */
    'claim_limit' => (int) env('PRINTSERVER_CLAIM_LIMIT', 5),

    /*
     * The filesystem disk that holds the content of a job. The row in the
     * database only keeps the path.
     */
    'disk' => env('PRINTSERVER_DISK', 'local'),

    /*
     * Days after which print:cleanup removes jobs and their files.
     */
    'keep_days' => (int) env('PRINTSERVER_KEEP_DAYS', 7),

    /*
     * Extra middleware for the endpoints, on top of the token check.
     */
    'middleware' => [],

    /*
     * Where the files of a job are stored on that disk.
     */
    'path' => env('PRINTSERVER_PATH', 'print-jobs'),

    /*
     * The url prefix of the endpoints. The box calls <prefix>/claim and friends.
     */
    'prefix' => env('PRINTSERVER_PREFIX', 'print'),

    /*
     * The printers print:test accepts, as key => description. The key is the one
     * the application uses and is also the name of the queue on the box. Leave it
     * empty to accept any name.
     */
    'printers' => [],

    /*
     * Register the routes of this package. Set it to false to wire them up
     * yourself, for instance inside an existing route group.
     */
    'routes' => true,

    /*
     * One token per box, as name:administration:token separated by commas, or the
     * short form name:token. The name ends up in the claimed_by column, so you
     * can see which box picked up a job.
     */
    'tokens' => env('PRINTSERVER_TOKENS', ''),

];
