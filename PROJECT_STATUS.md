# ELK Valuations Platform - Project Status
*Proprietary Platform by ELK Digital Limited*

## 🚀 Current Status (Phase 3 Core Complete)
The application has successfully transitioned from a single-firm tool into a multi-tenant SaaS foundation.

### Key Features Implemented:
*   **ELK AI Extraction Engine:** Powered by **Gemini 3.1 Pro Preview** via Vertex AI.
*   **Universal PDF Parser:** Specialized in UK Statutory Accounts (3-year P&L + Balance Sheet).
*   **Smart PDF Relocation:** Upload moved to Step 1 for full auto-population.
*   **Multi-Tenant Authentication:** Secure `login.php` tying users to their specific `firm_id`.
*   **SaaS Dashboard:** `dashboard.php` provides a centralized view of a firm's valuation pipeline.
*   **Dynamic Branding Portal:** `settings.php` allows firms to customize their name, primary/surface colors, and logos.
*   **Color Intelligence:** Automatic brightness adjustment for UI surface variants (Mid/Light) based on brand choice.

---

## 🗺️ Roadmap: The ELK Digital SaaS Platform

### Phase 3: Multi-Tenant Foundation (ELK Core) - FINALIZING
*   [x] **Firm-Based Database Schema:** `firms` and `users` tables live in Cloud SQL.
*   [x] **Multi-User Authentication:** Session-based security and firm isolation.
*   [x] **Dynamic Theming Engine:** CSS-variable injection with professional presets.
*   [x] **Self-Serve Branding Portal:** Firm admins can manage their own corporate identity.
*   [x] **View & Edit Mode:** Ability to reopen and refine saved valuations from the dashboard.
*   [x] **ELK Super-Admin:** Dashboard for ELK Digital to manage subscriptions and monitor AI costs.

### Phase 4: Professionalization & Scale
*   [x] **High-Fidelity PDF Export:** Professional, branded PDF report generation via Puppeteer.
*   [ ] **Dynamic Whitelabeling:** Support for custom subdomains (e.g., `gta.valuations.elkdigital.co.uk`).
*   [ ] **AI Logic Protection:** Moving core prompts behind a protected ELK Digital internal API.

### Phase 5: Commercial SaaS Launch
*   **Subscription Engine:** 12-month term enforcement and automated billing via Stripe.
*   **Pricing Strategy:** Positioning at £250/month per firm.
*   **Usage-Based Auditing:** Tracking extraction volume for profitability management.

---
*Owner: ELK Digital Limited | Strategic Partner: GTA Accounting | Last Updated: 15 March 2026*
