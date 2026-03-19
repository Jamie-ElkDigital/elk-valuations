# ELK Valuations Platform: Technical Architecture & System Capabilities
*Proprietary Intelligence Platform by ELK Digital Limited*

## 1. Executive Summary
The ELK Valuations Platform is a high-security, multi-tenant SaaS application designed for accounting firms to automate and professionalize the business valuation process. It leverages Google Vertex AI (Gemini 3.1 Pro) for deep financial data extraction and Puppeteer for pixel-perfect PDF report generation.

---

## 2. Core Capabilities
### 2.1 Universal PDF Extraction (AI-Powered)
*   **Process**: The system accepts UK Statutory Accounts (PDF).
*   **Engine**: Gemini 3.1 Pro via Vertex AI.
*   **Output**: Automatic extraction of 3 years of P&L data, Balance Sheet items (Cash, Debtors, Loans), Company Metadata (SIC codes, Directors, Share Capital), and an AI-generated business description.
*   **Intelligence**: Infers "Years Trading" and intelligently maps disparate accounting formats into a unified valuation schema.

### 2.2 Dynamic Valuation Engine
*   **Logic**: Implements a weighted EBITDA multiple approach.
*   **Net Debt Bridge**: Automatically calculates Enterprise Value to Equity Value bridges, accounting for surplus cash, director loans, and specific valuation deductions.
*   **Narrative Generation**: AI-driven professional commentary that analyzes 3-year trends, margins, and performance context.

### 2.3 Professional PDF Export
*   **Engine**: Puppeteer running in Headless Chrome.
*   **Quality**: Vector-based, print-ready PDFs with dynamic branding.
*   **Customization**: Injects firm-specific logos, primary/secondary colors, and signatures in real-time.

### 2.4 Companies House Intelligence (Direct Ingestion)
*   **Lookup**: Direct integration with the UK Companies House API via `ch-proxy.php`.
*   **Intelligence**: Automatic retrieval of incorporation dates, share allotment history, and director/officer churn.
*   **Extraction**: Direct ingestion of statutory accounts from the CH Document Vault, processed via an optimized "Hybrid Data Ingestion" pipeline (CH URL + Gemini 3.1 Pro). To ensure system stability and low latency, the system prioritizes the most recent accounts and a single confirmation statement for shareholder reconciliation.
*   **Verified-First Workflow**: To ensure professional integrity, the system prioritizes public record lookups as the primary data source.
*   **Persistent Supplemental Truth**: A dedicated PDF uploader remains available at all times, allowing firms to upload internal "Full" accounts to supplement filleted public records, ensuring maximum financial precision while maintaining the verified corporate structure from CH.

---

## 3. Technical Architecture

### 3.1 Frontend & UX
*   **Stack**: PHP 8.x, Vanilla JavaScript (ES6+), CSS3 with CSS Variables for theming.
*   **Dashboard**: AJAX-powered searchable grid with instant company filtering.
*   **Theming Engine**: A centralized `theme-engine.php` that injects dynamic CSS variables based on the firm's settings, allowing for "White Label" SaaS delivery.

### 3.2 Security & Multi-Tenancy
*   **Firm Isolation**: Strict row-level isolation via `firm_id` across all database queries.
*   **UUID Migration**: Public-facing endpoints (PDFs, Viewers) use 36-character unguessable UUIDs rather than sequential IDs to prevent "Insecure Direct Object Reference" (IDOR) attacks.
*   **Permission Tiers**: 
    *   **Super-Admin (ELK)**: Global visibility, firm creation, and AI cost auditing.
    *   **Firm-Admin**: Team management and branding control.
    *   **User**: Standard valuation entry and report generation.
*   **Authoritative Disclaimers**: Every report includes a "Data Verification" footer that professionally frames the AI extraction as high-end analytical tooling, protecting the firm's liability and perceived value.

### 3.3 The "Hydration" AI Pattern (AI Logic Protection)
To protect ELK Digital's Intellectual Property (IP), the system uses a **Hydration Pattern**:
1.  **Proxy**: `vertex-proxy.php` handles the secure connection to Google Cloud.
2.  **Vault**: `proprietary-logic.php` contains the proprietary prompts and schemas.
3.  **Process**: When a request is made, the Proxy "hydrates" the user's data with ELK's secret prompts before sending it to the AI. This allows ELK to move the "Vault" to a private internal API in the future without breaking the client-facing application.

---

## 4. Security Framework
*   **Multi-Tenancy**: Strict `firm_id` isolation at the DB layer via prepared statements.
*   **Authentication**: Session-based with cryptographically secure UUIDs for public endpoints.
*   **CSRF Protection**: All POST/PUT/DELETE requests require a valid `X-CSRF-Token` header, verified against the user's session.
*   **Secrets Management**: Database credentials and API keys are injected via Environment Variables (Cloud Run Secret Manager integration).
*   **Output Sanitization**: `htmlspecialchars()` for PHP rendering and safe DOM manipulation (no `innerHTML`) for JS extraction results.

---

## 5. Infrastructure & DevOps
### 4.1 Deployment Pipeline
*   **Environment**: Google Cloud Run (Serverless) with `--session-affinity` enabled to support stable PHP sessions across multiple horizontally scaled instances.
*   **Containerization**: Docker-based builds optimized with native caching.
*   **Database**: Google Cloud SQL (MySQL) accessed via Private IP for maximum security.
*   **Storage**: Google Cloud Storage (GCS) bucket (`gta-valuations-reports`) used as an immutable audit trail for permanently storing PDF snapshots.
*   **Build Optimization**: Uses `E2_HIGHCPU_8` machines for ~2 minute deployment cycles.

### 4.2 Usage Auditing
*   **Token Tracking**: Every AI request logs `prompt_tokens`, `completion_tokens`, and `total_tokens`.
*   **Cost Monitoring**: Real-time GBP cost estimation (£12 per 1M tokens) provided to ELK Digital to monitor SaaS margins.

---

## 5. Security Mandates
*   **No Cleartext Secrets**: All API authentication is handled via Google Service Accounts and the Metadata Server; no API keys are stored in the codebase.
*   **Git Integrity**: Versioning is baked into the UI footer via Git Short SHAs, ensuring traceability of every deployment.

---
*Document Version: 1.3 (Phase 6.1 In Progress)*  
*Last Updated: 17 March 2026*
