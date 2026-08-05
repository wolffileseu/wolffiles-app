<?php

namespace App\Http\Controllers;

use App\Mail\NdaSignedMail;
use App\Models\Nda;
use App\Models\NdaInvitation;
use App\Models\NdaTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class NdaSigningController extends Controller
{
    public function show(Request $request, string $token)
    {
        $invitation = NdaInvitation::findByToken($token);

        if ($invitation === null) {
            return $this->invalid('unknown');
        }

        if ($invitation->used_at !== null) {
            return $this->invalid('used');
        }

        if ($invitation->revoked_at !== null) {
            return $this->invalid('revoked');
        }

        if ($invitation->expires_at !== null && $invitation->expires_at->isPast()) {
            return $this->invalid('expired');
        }

        $template = $invitation->template;

        if ($template === null) {
            return $this->invalid('no_template');
        }

        return view('nda.sign', [
            'token' => $token,
            'invitation' => $invitation,
            'html' => $this->renderHtml($template, $this->previewData($invitation, $template)),
        ]);
    }

    public function store(Request $request, string $token)
    {
        $invitation = NdaInvitation::findByToken($token);

        if ($invitation === null || ! $invitation->isUsable() || $invitation->template === null) {
            return $this->invalid('unknown');
        }

        $minBirthdate = now()->subYears(18)->toDateString();

        $validated = $request->validate([
            'volunteer_name' => ['required', 'string', 'max:255'],
            'volunteer_email' => ['required', 'email', 'max:255'],
            'volunteer_username' => ['nullable', 'string', 'max:255'],
            'volunteer_discord' => ['nullable', 'string', 'max:255'],
            'volunteer_birthdate' => ['required', 'date', 'before_or_equal:' . $minBirthdate],
            'volunteer_country' => ['required', 'string', 'max:100'],
            'confirm_read' => ['accepted'],
            'confirm_age' => ['accepted'],
            'confirm_secrecy' => ['accepted'],
            'confirm_unpublished' => ['accepted'],
            'confirm_logging' => ['accepted'],
            'confirm_penalty' => ['accepted'],
        ], [
            'volunteer_birthdate.before_or_equal' => 'Du musst mindestens 18 Jahre alt sein.',
            'confirm_read.accepted' => 'Alle Bestaetigungen muessen angehakt sein.',
            'confirm_age.accepted' => 'Alle Bestaetigungen muessen angehakt sein.',
            'confirm_secrecy.accepted' => 'Alle Bestaetigungen muessen angehakt sein.',
            'confirm_unpublished.accepted' => 'Alle Bestaetigungen muessen angehakt sein.',
            'confirm_logging.accepted' => 'Alle Bestaetigungen muessen angehakt sein.',
            'confirm_penalty.accepted' => 'Alle Bestaetigungen muessen angehakt sein.',
        ]);

        $ip = (string) $request->ip();
        $userAgent = Str::limit((string) $request->userAgent(), 500, '');

        $nda = DB::transaction(function () use ($invitation, $validated, $ip, $userAgent) {
            // Erneut sperren und pruefen: verhindert doppeltes Absenden.
            $locked = NdaInvitation::query()
                ->whereKey($invitation->getKey())
                ->lockForUpdate()
                ->first();

            if ($locked === null || ! $locked->isUsable()) {
                return null;
            }

            $template = $locked->template;
            $signedAt = now();

            $data = $this->contractData($locked, $template, $validated, $signedAt, $ip, $userAgent);
            $body = NdaTemplate::renderBody($template->body, $data);

            $nda = Nda::create([
                'nda_invitation_id' => $locked->id,
                'user_id' => auth()->id(),
                'nda_template_id' => $template->id,
                'template_version' => $template->version,
                'locale' => $locked->locale,
                'volunteer_name' => $validated['volunteer_name'],
                'volunteer_username' => $validated['volunteer_username'] ?? null,
                'volunteer_email' => $validated['volunteer_email'],
                'volunteer_discord' => $validated['volunteer_discord'] ?? null,
                'volunteer_birthdate' => $validated['volunteer_birthdate'],
                'volunteer_country' => $validated['volunteer_country'],
                'role_name' => $locked->role_name,
                'role_names' => $locked->role_names,
                'permissions' => $locked->permissions,
                'penalty_amount' => $locked->penalty_amount,
                'log_retention_months' => $locked->log_retention_months,
                'authoritative_language' => $locked->authoritative_language,
                'rendered_body' => $body,
                'document_hash' => Nda::hashBody($body),
                'confirmations' => [
                    'read' => true,
                    'age' => true,
                    'secrecy' => true,
                    'unpublished' => true,
                    'logging' => true,
                    'penalty' => true,
                ],
                'signed_at' => $signedAt,
                'signed_ip' => $ip,
                'signed_user_agent' => $userAgent,
            ]);

            $locked->update(['used_at' => $signedAt]);

            return $nda;
        });

        if ($nda === null) {
            return $this->invalid('used');
        }

        $this->dispatchMails($nda);

        return view('nda.signed', [
            'nda' => $nda,
            'html' => $this->toHtml($nda->rendered_body),
        ]);
    }

    protected function contractData(
        NdaInvitation $invitation,
        NdaTemplate $template,
        array $validated,
        $signedAt,
        string $ip,
        string $userAgent
    ): array {
        return [
            'version' => (string) $template->version,
            'version_date' => optional($template->created_at)->format('d.m.Y') ?? '',
            'volunteer_name' => $validated['volunteer_name'],
            'volunteer_username' => $validated['volunteer_username'] ?: '-',
            'volunteer_email' => $validated['volunteer_email'],
            'volunteer_discord' => $validated['volunteer_discord'] ?: '-',
            'volunteer_birthdate' => date('d.m.Y', strtotime($validated['volunteer_birthdate'])),
            'volunteer_country' => $validated['volunteer_country'],
            'role_name' => $invitation->role_name,
            'permissions_list' => $this->permissionsList($invitation),
            'penalty_amount' => $this->money($invitation->penalty_amount),
            'log_retention_months' => (string) $invitation->log_retention_months,
            'authoritative_language' => $invitation->authoritative_language,
            'signed_at' => $signedAt->format('d.m.Y H:i:s') . ' UTC' . $signedAt->format('P'),
            'signed_ip' => $ip,
            'signed_user_agent' => $userAgent,
        ];
    }

    protected function previewData(NdaInvitation $invitation, NdaTemplate $template): array
    {
        $blank = '__________';

        return [
            'version' => (string) $template->version,
            'version_date' => optional($template->created_at)->format('d.m.Y') ?? '',
            'volunteer_name' => $blank,
            'volunteer_username' => $blank,
            'volunteer_email' => $blank,
            'volunteer_discord' => $blank,
            'volunteer_birthdate' => $blank,
            'volunteer_country' => $blank,
            'role_name' => $invitation->role_name,
            'permissions_list' => $this->permissionsList($invitation),
            'penalty_amount' => $this->money($invitation->penalty_amount),
            'log_retention_months' => (string) $invitation->log_retention_months,
            'authoritative_language' => $invitation->authoritative_language,
            'signed_at' => $blank,
            'signed_ip' => $blank,
            'signed_user_agent' => $blank,
        ];
    }

    protected function permissionsList(NdaInvitation $invitation): array
    {
        $lines = [];

        foreach ((array) $invitation->role_names as $role) {
            $lines[] = 'Rolle: ' . $role;
        }

        foreach ((array) $invitation->permissions as $permission) {
            $lines[] = $permission;
        }

        if ($lines === []) {
            $lines[] = 'Werden im Einzelfall vom Betreiber festgelegt und koennen jederzeit angepasst werden.';
        }

        return $lines;
    }

    protected function money($amount): string
    {
        return number_format((float) $amount, 2, ',', '.');
    }

    protected function renderHtml(NdaTemplate $template, array $data): string
    {
        return $this->toHtml(NdaTemplate::renderBody($template->body, $data));
    }

    protected function toHtml(string $markdown): string
    {
        return Str::markdown($markdown);
    }

    protected function dispatchMails(Nda $nda): void
    {
        $operator = config('nda.operator_email');

        try {
            Mail::to($nda->volunteer_email)->send(new NdaSignedMail($nda, false));

            if (! empty($operator)) {
                Mail::to($operator)->send(new NdaSignedMail($nda, true));
            }
        } catch (\Throwable $e) {
            // Der Vertrag ist gespeichert - ein Mailfehler darf ihn nicht kippen.
            Log::error('NDA-Mail fehlgeschlagen', [
                'nda_id' => $nda->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    protected function invalid(string $reason)
    {
        return response()->view('nda.invalid', ['reason' => $reason], 410);
    }
}
