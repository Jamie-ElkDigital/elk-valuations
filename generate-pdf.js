const puppeteer = require('puppeteer');
const fs = require('fs');

(async () => {
    const outputPath = process.argv[2];
    if (!outputPath) {
        console.error('Usage: node generate-pdf.js <output_path>');
        process.exit(1);
    }

    // Read HTML from stdin
    let html = '';
    process.stdin.on('data', data => {
        html += data;
    });

    process.stdin.on('end', async () => {
        try {
            const browser = await puppeteer.launch({
                executablePath: '/usr/bin/google-chrome-stable',
                headless: 'new',
                args: [
                    '--no-sandbox',
                    '--disable-setuid-sandbox',
                    '--disable-dev-shm-usage',
                    '--disable-gpu',
                    '--disable-extensions',
                    '--font-render-hinting=none'
                ]
            });

            const page = await browser.newPage();
            
            // Set viewport for better rendering
            await page.setViewport({ width: 1200, height: 1600 });

            // Set content and wait for network to be idle (ensure images/fonts load)
            await page.setContent(html, { 
                waitUntil: ['load', 'networkidle2'],
                timeout: 30000 
            });

            // Generate PDF
            await page.pdf({
                path: outputPath,
                format: 'A4',
                printBackground: true,
                margin: {
                    top: '10mm',
                    right: '10mm',
                    bottom: '10mm',
                    left: '10mm'
                }
            });

            await browser.close();
            console.log('PDF generated successfully at ' + outputPath);
        } catch (err) {
            console.error('Puppeteer Error:', err);
            process.exit(1);
        }
    });
})();
