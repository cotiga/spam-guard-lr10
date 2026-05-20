<?php

namespace Cotiga\SpamGuard\Exceptions;

use Cotiga\SpamGuard\Models\BannedIp;
use Cotiga\SpamGuard\Models\ErrorIgnored;
use Cotiga\SpamGuard\Models\HttpError;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Throwable;

class SpamGuardHandler extends ExceptionHandler
{
    public function render($request, Throwable $e)
    {
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return parent::render($request, $e);
        }

        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            return redirect()->guest(route('login'));
        }

        $status = $this->isHttpException($e) ? $e->getStatusCode() : 500;
        $ip     = $request->ip();

        // 1) IP bannie
        try {
            if (BannedIp::where('ip', $ip)->exists()) {
                return response('Forbidden', 403);
            }
        } catch (\Throwable) {}

        // 2) URLs ignorées selon patterns
        try {
            $patterns = ErrorIgnored::pluck('pattern')->toArray();
            foreach ($patterns as $pattern) {
                $regex = '#^'.str_replace('\*', '.*', preg_quote($pattern, '#')).'$#i';
                if (preg_match($regex, $request->path())) {
                    return response('Ignored', 200);
                }
            }
        } catch (\Throwable) {}

        // 3) Log erreur + email si récurrente
        try {
            $message = $e->getMessage() ?: 'Erreur inconnue';
            $url     = Str::limit($request->fullUrl(), 255, '');

            $existing = HttpError::where('status_code', $status)->where('url', $url)->first();

            if ($existing) {
                $existing->increment('count');
                $existing->update(['error_message' => $message]);
                $count = $existing->count;
            } else {
                HttpError::create([
                    'status_code'   => $status,
                    'url'           => $url,
                    'ip'            => $ip,
                    'user_agent'    => $request->userAgent(),
                    'error_message' => $message,
                    'count'         => 1,
                ]);
                $count = 1;
            }

            $threshold = config('spam-guard.alert_threshold', 10);
            if ($count >= $threshold && $count % $threshold === 0) {
                $siteName = config('app.name', 'Site');
                $alertEmail = config('spam-guard.alert_email');
                Mail::raw(
                    "Erreur {$status} détectée {$count} fois sur : ".$request->fullUrl(),
                    fn ($m) => $m->to($alertEmail)->subject("{$siteName} - Erreur récurrente")
                );
            }
        } catch (\Throwable $ex) {
            \Log::error('SpamGuard — erreur journalisation : '.$ex->getMessage(), [
                'url'    => $request->fullUrl(),
                'status' => $status,
            ]);
        }

        // 4) Ban automatique si trop d'erreurs depuis cette IP aujourd'hui
        $banThreshold = config('spam-guard.ban_threshold', 30);
        $totalErrors  = HttpError::where('ip', $ip)
            ->whereDate('created_at', now()->toDateString())
            ->sum('count');

        if ($totalErrors >= $banThreshold) {
            BannedIp::firstOrCreate(['ip' => $ip]);
            return response()->view($this->resolveView(403), [
                'status' => 403,
                'reason' => "Votre adresse IP a été bloquée suite à trop d'erreurs détectées.",
            ], 403);
        }

        // 5) Vue d'erreur
        return response()->view($this->resolveView($status), ['status' => $status], $status);
    }

    protected function resolveView(int $status): string
    {
        if (view()->exists("errors.{$status}")) {
            return "errors.{$status}";
        }

        // Vue générique publiée par l'app (vendor:publish)
        if (view()->exists('errors.generic')) {
            return 'errors.generic';
        }

        // Fallback packagé (vue standalone sans layout)
        return 'spam-guard::errors.generic';
    }
}
