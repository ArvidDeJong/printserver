<?php

namespace Darvis\Printserver\Http\Middleware;

use Closure;
use Darvis\Printserver\Support\PrintserverConfig;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the endpoints: only a box with a valid token gets through, and only from an
 * allowed address when printserver.allowed_ips is filled.
 *
 * Tokens are configured as name:administration:token separated by commas, or in the short
 * form name:token. The administration is the lock: a box of one branch that points at the
 * installation of another gets a 403 instead of its work.
 */
class VerifyPrintboxToken
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->allowedAddress($request)) {
            return response()->json(['message' => 'This address is not allowed here.'], Response::HTTP_FORBIDDEN);
        }

        $box = $this->boxForToken((string) $request->bearerToken());

        if ($box === null) {
            return response()->json(['message' => 'No valid token.'], Response::HTTP_UNAUTHORIZED);
        }

        $administration = PrintserverConfig::administration();

        if ($box['administration'] !== '' && $box['administration'] !== (string) $administration) {
            return response()->json([
                'message' => 'This token belongs to administration '.$box['administration'].', this installation is '
                    .($administration === null ? 'without an administration' : $administration).'.',
            ], Response::HTTP_FORBIDDEN);
        }

        $request->attributes->set('printbox', $box['name']);

        return $next($request);
    }

    /**
     * Checks the address of the caller against printserver.allowed_ips.
     */
    private function allowedAddress(Request $request): bool
    {
        $allowed = PrintserverConfig::allowedIps();

        return $allowed === [] || in_array((string) $request->ip(), $allowed, true);
    }

    /**
     * Finds the box belonging to a token, or null when the token does not match.
     *
     * @return array{administration: string, name: string, token: string}|null
     */
    private function boxForToken(string $token): ?array
    {
        if ($token === '') {
            return null;
        }

        foreach (PrintserverConfig::boxes() as $box) {
            // hash_equals keeps the comparison constant in time.
            if (hash_equals($box['token'], $token)) {
                return $box;
            }
        }

        return null;
    }
}
