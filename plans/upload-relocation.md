# Plan: Relocate PDF Upload and Expand Data Extraction (COMPLETED)

## Status: COMPLETED (15 March 2026)
This plan has been fully implemented.

1.  **UI Relocation (`index.php`)**: The Smart PDF Upload box is now correctly positioned at the top of Step 1.
2.  **Prompt Expansion (`vertex-proxy.php`)**: Gemini 1.5 Pro now extracts 6 additional fields, including Directors and Share Capital.
3.  **Auto-population Logic (`index.php`)**:
    *   `populateExtractedData()` now fills the entire Business Details form.
    *   It also intelligently generates the **Shareholders** list in Step 4 based on the extracted Directors and Share Capital.
4.  **Verification**: Successfully tested with GTA Accounting accounts. All fields populated, and the Net Debt Bridge logic remains intact.

---
*Owner: ELK Digital | estrategic Partner: GTA Accounting*
