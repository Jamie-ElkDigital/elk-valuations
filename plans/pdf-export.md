# Plan: Phase 4 - High-Fidelity PDF Export (Puppeteer)

## Objective
Upgrade the reporting functionality to generate pixel-perfect, highly styled PDF documents using Puppeteer, running within a single unified container (PHP + Node.js). This will replace the basic browser print dialog with a professional, one-click PDF download.

## Architectural Approach
1. **Single Container Setup:**
   * Modify the existing `Dockerfile` (PHP 8.2 Apache) to install Node.js and the necessary system dependencies for running Chromium headlessly in a Debian/Ubuntu environment.
   * Add a `package.json` to manage the `puppeteer` dependency.

2. **Puppeteer Worker (`generate-pdf.js`):**
   * Create a lightweight Node.js script that accepts HTML content (either via a temporary file or standard input) and a destination path.
   * The script will launch a headless browser, inject the HTML, wait for styles/fonts to load, and render an A4 PDF with proper margins and print backgrounds enabled.

3. **PHP Integration (`export-pdf.php`):**
   * Create a new endpoint that accepts a valuation ID.
   * It will fetch the valuation data (reusing logic from `view-valuation.php`) and render the complete HTML structure into a string.
   * It will execute the Node script using PHP's `exec()` or `proc_open()`, passing the HTML.
   * The resulting PDF will be served directly to the user as an inline download (`Content-Type: application/pdf`).

4. **UI & Styling Enhancements:**
   * **CSS Print Media:** Refine the `@media print` rules in `style.css` to ensure elements like the "Results Hero" gradient and custom firm colors render accurately on the PDF. We will enforce page breaks before key sections (e.g., Shareholder Allocation, Accountant's Commentary).
   * **Button Update:** Replace the current browser `window.print()` functionality in `view-valuation.php` with a robust "Download PDF" button pointing to `export-pdf.php`.

## Execution Steps
1. **Infrastructure:** Update `Dockerfile` and create `package.json`.
2. **Backend Engine:** Write the `generate-pdf.js` worker.
3. **Integration Layer:** Write `export-pdf.php` to bridge PHP and the Node.js worker.
4. **Styling & UI:** Update `style.css` for optimal print layout and wire up the new download buttons in the frontend.

## Verification
* Test generating a PDF for a complete valuation.
* Ensure custom firm branding (colors, logos) is correctly applied in the PDF.
* Verify all pages break cleanly and text is fully legible.
