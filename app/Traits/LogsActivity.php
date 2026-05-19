<?php
namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

trait LogsActivity
{
    /**
     * Write one row to the activity_logs table.
     *
     * Auto-generates a human-readable `notes` sentence when none is provided.
     * Status-changed calls that pass an explicit `$notes` (e.g. "Pending → In-Progress")
     * will use that string as-is.
     *
     * @param  string       $action        Verb describing what happened: 'created', 'updated',
     *                                     'deleted', 'archived', 'restored', 'status_changed',
     *                                     'compiled', 'login', 'logout', 'login_failed', 'password_changed'
     * @param  string       $subjectType   Snake-cased model/module name: 'driver', 'truck',
     *                                     'trip_ticket', 'maintenance_record', 'report_compilation'
     * @param  int|null     $subjectId     Primary key of the affected row (null for auth events)
     * @param  string|null  $subjectLabel  Human-readable name at the time of the action
     *                                     (e.g. driver full name, truck code, trip number)
     * @param  array|null   $oldValues     Full model snapshot BEFORE the change (updates/deletes)
     * @param  array|null   $newValues     Full model snapshot AFTER the change (creates/updates)
     * @param  string|null  $notes         Override the auto-generated sentence; pass null to auto-generate.
     *                                     Useful for status transitions: "Pending → In-Progress"
     * @param  Request|null $request       When provided, captures ip_address and user_agent
     */
    protected function writeLog(
        string   $action,
        string   $subjectType,
        ?int     $subjectId    = null,
        ?string  $subjectLabel = null,
        ?array   $oldValues    = null,
        ?array   $newValues    = null,
        ?string  $notes        = null,
        ?Request $request      = null
    ): void {
        // Auto-generate a plain-English sentence when no notes are explicitly supplied.
        if ($notes === null) {
            $notes = $this->buildNotes($action, $subjectType, $subjectLabel, $oldValues, $newValues);
        }

        try {
            ActivityLog::create([
                'action'        => $action,
                'subject_type'  => $subjectType,
                'subject_id'    => $subjectId,
                'subject_label' => $subjectLabel,
                'performed_by'  => 'admin',
                'old_values'    => $oldValues,
                'new_values'    => $newValues,
                'notes'         => $notes,
                'ip_address'    => $request?->ip(),
                'user_agent'    => $request?->userAgent(),
                'logged_at'     => now(),
            ]);
        } catch (\Throwable $e) {
            // Never let a logging failure break the main request.
            \Illuminate\Support\Facades\Log::error('ActivityLog write failed', [
                'action'  => $action,
                'subject' => "{$subjectType}#{$subjectId}",
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Build a human-readable sentence describing what the admin did.
     *
     * For 'updated' actions, diffs old vs. new values and lists changed fields.
     * Up to 3 changes are shown in full; additional changes are summarised as
     * "... (N more)" to keep notes concise.
     *
     * Examples:
     *   created  → "Admin added driver Juan dela Cruz."
     *   updated  → "You updated driver Juan dela Cruz: full name 'Jane' → 'John'; phone number '09171234567' → '09179999999'."
     *   updated  → "You updated driver Juan dela Cruz: full name '...' → '...'; phone number '...' → '...'; license number '...' → '...'; ... (2 more)."
     *   archived → "Admin archived driver Juan dela Cruz."
     *   login    → "Admin logged in."
     *
     * @param  string       $action        Same verb passed to writeLog()
     * @param  string       $subjectType   Same snake-cased module name passed to writeLog()
     * @param  string|null  $subjectLabel  Human-readable name of the affected record
     * @param  array|null   $oldValues     Snapshot before the change (may be null for creates)
     * @param  array|null   $newValues     Snapshot after the change (may be null for deletes)
     *
     * @return string  Plain-English sentence suitable for the `notes` column
     */
    protected function buildNotes(
        string  $action,
        string  $subjectType,
        ?string $subjectLabel,
        ?array  $oldValues = null,
        ?array  $newValues = null
    ): string {
        // Convert snake_case type to a readable word, e.g. "trip_ticket" → "trip ticket"
        $type = str_replace('_', ' ', $subjectType);

        // Prefix label with a space so it slots naturally into sentences
        $label = $subjectLabel ? " {$subjectLabel}" : '';

        // ── Updated: diff old vs new and list what changed ────────────────────
        if ($action === 'updated' && $oldValues && $newValues) {
            // Fields that are internal or irrelevant to human-readable output
            $skip = ['created_at', 'updated_at', 'deleted_at', 'password', 'value', 'file_path'];

            $changes = [];

            foreach ($newValues as $key => $newVal) {
                if (in_array($key, $skip, true)) {
                    continue;
                }

                $oldVal = $oldValues[$key] ?? null;

                // Skip fields that did not actually change
                if ((string) $oldVal === (string) $newVal) {
                    continue;
                }

                // Display empty/null values as the word "empty" for readability
                $oldDisplay = ($oldVal !== null && $oldVal !== '') ? "'{$oldVal}'" : 'empty';
                $newDisplay = ($newVal !== null && $newVal !== '') ? "'{$newVal}'" : 'empty';

                $changes[] = str_replace('_', ' ', $key) . " {$oldDisplay} → {$newDisplay}";
            }

            if (empty($changes)) {
                return "Admin updated {$type}{$label} (no changes detected).";
            }

            // Show up to 3 changed fields in full, then summarise the rest
            $MAX  = 3;
            $note = implode('; ', array_slice($changes, 0, $MAX));

            if (count($changes) > $MAX) {
                $note .= ' ... (' . (count($changes) - $MAX) . ' more)';
            }

            return "You updated {$type}{$label}: {$note}.";
        }

        // ── All other actions: plain verb sentence ────────────────────────────
        return match ($action) {
            'created'          => "Admin added {$type}{$label}.",
            'deleted'          => "Admin deleted {$type}{$label}.",
            'archived'         => "Admin archived {$type}{$label}.",
            'restored'         => "Admin restored {$type}{$label}.",
            'compiled'         => "Admin compiled report{$label}.",
            'status_changed'   => "Admin changed status of {$type}{$label}.",
            'login'            => "Admin logged in.",
            'logout'           => "Admin logged out.",
            'login_failed'     => "Failed login attempt.",
            'password_changed' => "Admin changed the password.",
            default            => "Admin performed '{$action}' on {$type}{$label}.",
        };
    }

    /**
     * Capture a flat snapshot of a model's current scalar attributes.
     *
     * Strips timestamps and any extra fields you want to exclude so that
     * old_values / new_values stored in the log stay clean and diffable.
     *
     * @param  Model    $model   The Eloquent model instance to snapshot
     * @param  string[] $except  Additional attribute keys to exclude beyond timestamps
     *
     * @return array<string, mixed>  Key-value map of the model's loggable attributes
     */
    protected function modelSnapshot(Model $model, array $except = []): array
    {
        $skip = array_merge(['created_at', 'updated_at', 'deleted_at'], $except);

        return collect($model->getAttributes())
            ->except($skip)
            ->toArray();
    }
}