# agents.md — Laravel Workspace Guardrails

## Workspace boundary (STRICT)
- Treat the repository root (the folder containing this `agents.md`) as the **only allowed workspace**.
- **Never** read, write, list, search, or reference files **outside** this workspace.
- **Never** use relative paths that traverse upward (e.g. `../`, `..\\`) or absolute paths (e.g. `/home/...`, `C:\...`) unless they clearly remain inside the workspace.
- If an instruction would require accessing anything outside the workspace, **refuse and explain** what’s needed inside the repo instead.

## Secrets & sensitive files (DO NOT ACCESS)
Do **not** open, read, parse, print, or summarize any of the following:
- `.env`, `.env.*` (including `.env.local`, `.env.production`, etc.)
- Any files containing credentials, tokens, keys, or certificates (even if referenced by config)
- `storage/oauth-*`, `storage/*.key`, `storage/*.pem`, `storage/*.crt`, `storage/*.p12`
- `*.pem`, `*.key`, `*.pfx`, `*.p12`, `id_rsa*`, `known_hosts`, `authorized_keys`
- Any “secrets” folders if present (e.g. `secrets/`, `.secrets/`)

If debugging requires config values, rely on:
- `config/*.php` (but do not try to resolve values via `.env`)
- Safe defaults and documented Laravel conventions
- Asking the user to provide *sanitized* snippets (with secrets removed)

Never echo secrets to logs/output. If any secret-like value is encountered accidentally, stop and warn.

## Scope: what to focus on in a Laravel repo
Prefer working within:
- `app/`, `routes/`, `config/`, `database/`, `resources/`, `tests/`, `public/`, `bootstrap/`
- `composer.json`, `phpunit.xml`, `artisan`, `vite.config.*`, `package.json`

Avoid heavy/vendor directories unless explicitly needed:
- Avoid scanning: `vendor/`, `node_modules/`, `storage/logs/`, `storage/framework/`, `.git/`

## Safe command usage
When suggesting or running commands (if applicable), prefer:
- `php artisan ...`
- `composer ...`
- `phpunit` / `pest`
- `npm run ...` (or `pnpm/yarn` if the project uses it)

Rules:
- Do not run commands that require external credentials or network access unless explicitly requested.
- Never run destructive commands without clear need (e.g., dropping databases, deleting storage).
- If a command might modify files, describe the expected changes first.

## Change policy
- Keep changes minimal and localized.
- Follow Laravel conventions (Service Container, Request validation, Eloquent best practices).
- Add/adjust tests when behavior changes.
- Prefer clear, maintainable code over cleverness.

## Output expectations
- Provide file paths **relative to the workspace**.
- Provide copy-pasteable snippets with enough surrounding context to apply safely.
- If unsure, propose options and pick the safest default.

## If boundaries conflict with a request
If asked to inspect parent folders, user home directories, system paths, or `.env`:
- Refuse that part.
- Offer an alternative approach that stays inside the workspace.
- Ask for a redacted snippet only if absolutely necessary (with secrets removed).