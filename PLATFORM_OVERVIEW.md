# ELK Valuations: The Intelligence-First Valuation Platform
*Proprietary Professional Intelligence by ELK Digital Limited*

## 1. Executive Summary
ELK Valuations is a high-security, multi-tenant SaaS platform designed specifically for accounting firms to automate the delivery of professional business valuations. Unlike traditional calculators, ELK leverages **Hybrid Data Ingestion** and **Senior Accountant AI Personas** to build a comprehensive corporate biography before performing a single calculation.

## 2. Key Capabilities

### 🛡️ Corporate Intelligence (Direct CH Integration)
The platform doesn't just look at a spreadsheet; it performs a deep-dive forensic audit of the UK Companies House Document Vault.
*   **Verified-First Workflow:** To maintain professional integrity, every valuation begins with a mandatory lookup of public records to build the structural knowledge base.
*   **Persistent Supplemental Truth:** A dedicated PDF uploader remains available at all times, allowing firms to upload internal "Full" accounts to supplement filleted public records, ensuring maximum financial precision while maintaining the verified corporate structure from CH.
*   **Historical Knowledge Base:** Scans the last 40 statutory filings (Incorporation, Officer Changes, Confirmation Statements).
*   **Shareholder Integrity:** Automatically reconciles **CS01 Confirmation Statements** against Statutory Accounts to identify exact share splits, bypassing the limitations of aggregated data in filleted accounts.

### 🧠 Senior Accountant AI Engine
Powered by **Gemini 3.1 Pro (Vertex AI)**, the platform adopts the persona of a Senior Chartered Accountant.
*   **Multi-Document Reconciliation:** Cross-references different filing types to find the "truth" behind the numbers.
*   **Hybrid Ingestion:** Seamlessly combines raw Companies House data with local, high-detail full statutory accounts uploaded by the user.
*   **Professional Commentary:** Generates 4-5 paragraphs of flowing, professional rationale for the valuation, covering revenue trends, operational efficiency, and liquidity.

### 💎 Enterprise-Grade Infrastructure
*   **High-Fidelity PDF Export:** Uses a headless Puppeteer engine to generate vector-based, print-ready branded reports.
*   **Non-Repudiable Audit Trail:** Every report generated is snapshotted to Google Cloud Storage (GCS), creating a permanent version history for every client.
*   **Secure Multi-Tenancy:** Each firm operates in a strictly isolated environment with UUID-based security, ensuring client data never crosses firm boundaries.

## 3. The Problem We Solved
Most firms struggle with **"Filleted Accounts"** which hide the Profit & Loss statement. ELK Valuations solves this through our **Hybrid Pipeline**:
1.  **Look up** the company to build the structural knowledge (Owners, History, Sectors).
2.  **Upload** the internal Full Accounts (the "truth").
3.  **The AI Reconciles** both, using the history to inform the risk and the full accounts to ensure financial precision.

## 4. Security & Compliance
*   **GDPR Ready:** Designed with firm-level isolation and encrypted session handling.
*   **No Local State:** Serverless architecture ensures that no data persists on ephemeral instances.
*   **Admin Control:** Super-Admins can manage firms, reset credentials, and toggle global security features like 2FA.

---
**Developed & Supported by ELK Digital Limited**  
*Jamie Elkins, Founder*  
*March 2026*
