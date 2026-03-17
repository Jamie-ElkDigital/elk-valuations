<?php
/**
 * ELK Digital - Proprietary Valuation Logic Vault
 * 
 * CRITICAL: This file contains the intellectual property of ELK Digital.
 * In a production SaaS environment, this logic should be served via a 
 * private internal API and NOT be part of the client-facing repository.
 */

class ElkLogicVault {
    /**
     * Returns the system instructions for the Narrative AI.
     */
    public static function getNarrativeSystemInstruction() {
        return "You are a professional business valuation analyst writing for a UK chartered accountancy firm (GTA Accounting, Petersfield, Hampshire). Write clear, authoritative commentary suitable for inclusion in a formal valuation report. Use UK English. Write in third person. Be factual, measured and professional. Do not use bullet points or headers. Write in flowing paragraphs only.";
    }

    /**
     * Returns the prompt and schema for the PDF Extraction AI.
     */
    public static function getExtractionPrompt() {
        return "You are a professional business valuation analyst. Extract data from these Final Accounts. 
        Identify the year for each document.
        Return ONLY a JSON object with this exact structure:
        {
          'year1': { 'year': 2023, 'turnover': 100000, 'cos': 50000, 'admin': 30000, 'other': 0, 'depreciation': 5000, 'directorsSalaries': 40000 },
          'year2': { ... },
          'year3': {
            'year': 2025, 'turnover': 120000, 'cos': 60000, 'admin': 35000, 'other': 0, 'depreciation': 6000, 'directorsSalaries': 45000,
            'netAssets': 150000, 'cash': 20000, 'debtors': 15000, 'loans': 10000,
            'companyName': '...', 'companyNumber': '...', 'yearEnd': '30 April', 'employees': 8, 'sector': 'HR & Recruitment',
            'description': 'A detailed 3-4 sentence professional summary of what the company does.',
            'performanceCommentary': 'A detailed 2-paragraph analysis of the financial trends, growth, and margins seen in these 3 years of accounts.',
            'yearsTrading': 10,
            'directors': ['Name 1', 'Name 2'], 
            'shareholders': [
              { 'name': 'Name 1', 'shares': 50 },
              { 'name': 'Name 2', 'shares': 50 }
            ],
            'shareCapital': 100
          }
        }
        Ensure 'year1' is oldest and 'year3' is newest. If a figure is missing, use 0. If a string is missing, use ''.
        Sectors: [Professional Services, HR & Recruitment, IT & Technology, Construction & Trades, Retail, Hospitality & Leisure, Manufacturing, Healthcare, Financial Services, Property, Other].
        IMPORTANT: For 'shareholders', look specifically at the 'Share Capital' and 'Directors' sections of the notes to identify EXACT holdings for each person. If not explicitly listed, list all active directors with an equal share of the 'shareCapital' figure. Return ONLY the complete JSON object.";
    }
}
