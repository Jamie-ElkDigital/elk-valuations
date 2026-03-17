# Gemini CLI Context for ELK Valuations Platform

## General Preferences
- **Model:** Always use `gemini-3.1-pro-preview` for tasks in this project.

## Architecture & Infrastructure
- **Hosting:** Google Cloud Run (Serverless) deployed via Cloud Build.
- **Stack:** PHP 8.x, Vanilla JavaScript (ES6+), CSS3 (CSS Variables for dynamic theming). No heavy frontend frameworks.
- **Database:** Google Cloud SQL (MySQL). Strict multi-tenancy enforced via `firm_id` on ALL queries.
- **Storage:** Google Cloud Storage (GCS) bucket `gta-valuations-reports` is used for permanent PDF snapshotting.

## Security Mandates (Critical)
1. **Multi-Tenancy:** Never write a database query without `WHERE firm_id = ?`.
2. **UUIDs Only:** Public endpoints (`view-valuation.php`, `export-pdf.php`) must use 36-character unguessable UUIDs. Never expose auto-incrementing integer IDs.
3. **No Local State:** Cloud Run instances are ephemeral. Rely on GCS for file storage, and use `--session-affinity` in Cloud Build to manage PHP sessions.

## Development Workflow
- **Live Environment:** Development happens directly against the live environment/database. Be incredibly careful with `DELETE` or `DROP` statements.
- **Doc Updates:** If significant architectural changes occur, manually update `PROJECT_STATUS.md` and `SYSTEM_ARCHITECTURE.md`, but *do not* `git push` those specific markdown files unless explicitly asked.
- **Deployment:** Committing and pushing to `main` triggers a ~2 minute Cloud Build deployment to Cloud Run.