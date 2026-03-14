# Plan: Relocate PDF Upload and Expand Data Extraction

## Objective
Move the "Smart PDF Upload" feature from Step 2 to Step 1 of the Business Valuation tool. Enhance the AI prompt to extract additional business details, share capital, and director information from the statutory accounts to pre-populate more of the form automatically.

## Scope of Changes

1.  **UI Relocation (`index.php`)**
    *   Move the HTML for `#uploadBox` from `id="page1"` (Financial Data) to the top of `id="page0"` (Business Details).
    *   Update the descriptive text in the UI to reflect that it now auto-fills the entire valuation setup.

2.  **Prompt Expansion (`vertex-proxy.php`)**
    *   Update the `extract` prompt sent to Gemini Pro.
    *   Add instructions to extract:
        *   `yearEnd` (e.g., "30 April")
        *   `employees` (from notes, e.g., "Average number of employees")
        *   `sector` (infer the closest match from a provided list based on the company's nature)
        *   `description` (generate a 1-sentence summary of the business based on the financials)
        *   `directors` (array of names from the company info page)
        *   `shareCapital` (total number of shares from the Share Capital note)
    *   Update the JSON schema requirement in the prompt to include these new keys alongside the financial data.

3.  **Auto-population Logic (`index.php`)**
    *   Update the `populateExtractedData` javascript function.
    *   Map the new `yearEnd`, `employees`, `sector`, and `description` data to the Step 1 inputs.
    *   If `directors` and `shareCapital` are found in the most recent year's data, use them to automatically generate rows in the Step 4 (Shareholders) section, distributing the `shareCapital` evenly among the `directors` as a sensible default.
    *   Ensure existing financial and adjustment data population remains intact.

## Verification & Testing
*   Upload the `Final Accounts 30.04.24.pdf` on Step 1.
*   Verify that Company Name, Number, Year End, and Employees populate correctly in Step 1.
*   Check that a reasonable Sector and Description are generated in Step 1.
*   Verify that Financials (Step 2) and Adjustments (Step 3) are populated as before.
*   Check that the Shareholder list (Step 4) defaults to the extracted Directors with an estimated share split.