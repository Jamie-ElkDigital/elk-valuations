# Privacy Policy Draft: ELK Valuations Platform
*Provider: ELK Digital Limited*

## 1. Introduction
The ELK Valuations Platform ("the Platform") is a proprietary financial intelligence tool provided by ELK Digital Limited ("ELK"). This policy explains how we process data when you use our AI-driven valuation services.

## 2. Roles & Responsibilities
*   **Data Controller:** The subscribing Accounting Firm (the "Firm") is the Data Controller for client data uploaded to the platform.
*   **Data Processor:** ELK Digital Limited acts as the Data Processor, providing the infrastructure, AI logic, and maintenance for the platform.
*   **Sub-Processors:** We utilize **Google Cloud Platform (GCP)** for hosting (London Region) and **Google Vertex AI** for financial data extraction.

## 3. Data We Collect
*   **Account Data:** Names, email addresses, and firm affiliations of authorized users.
*   **Client Financial Data:** UK Statutory Accounts (PDFs), share structures, and profitability figures uploaded for valuation purposes.
*   **Technical Logs:** IP addresses, browser types, and AI token usage metadata (for auditing and billing).

## 4. How We Use AI (Google Vertex AI)
The platform uses Gemini 3.1 Pro via Google Vertex AI to extract financial data from PDF documents. 
*   **Data Sovereignty:** All processing occurs within the Google Cloud `europe-west2` (London) region.
*   **No Training:** Under our Enterprise Agreement with Google, your data is **NOT** used to train global AI models. It is processed in an isolated environment and discarded after extraction is complete.

## 5. Data Retention
In accordance with UK accounting regulations and the Limitation Act 1980, valuation data is typically retained for a period of **6 years** from the date of the report, unless a firm administrator requests earlier deletion.

## 6. Security Measures
*   **Isolation:** We employ strict multi-tenant isolation; one firm cannot access another's data.
*   **Encryption:** All data is encrypted at rest (AES-256) and in transit (TLS 1.2+).
*   **UUIDs:** We use secure, unguessable UUIDs for all public report links to prevent unauthorized access.

## 7. Contact
For data protection inquiries, please contact the GTA Accounting Data Protection Officer or ELK Digital Technical Support.
