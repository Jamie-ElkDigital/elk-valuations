<?php
session_start();
// Uncomment below to enable password protection:
// if (!isset($_SESSION['authenticated'])) {
//     header('Location: login.php');
//     exit;
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Business Valuation | GTA Accounting</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Barlow:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
<style>
  :root {
    --navy: #0a0a12;
    --navy-mid: #12121f;
    --navy-light: #1c1c2e;
    --gold: #aa1a18;
    --gold-light: #d42a28;
    --gold-dim: rgba(170,26,24,0.15);
    --cream: #ffffff;
    --cream-dim: rgba(255,255,255,0.1);
    --text: #ffffff;
    --text-muted: #b0b0bc;
    --text-faint: #66667a;
    --success: #50fa7b;
    --border: rgba(170,26,24,0.4);
    --border-subtle: rgba(255,255,255,0.15);
    --input-bg: #000000;
    --input-border: #44445a;
    --input-focus: rgba(170,26,24,0.4);
    --radius: 4px;
    --radius-lg: 8px;
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  body {
    font-family: 'Barlow', sans-serif;
    background: var(--navy);
    color: var(--text);
    min-height: 100vh;
    font-size: 15px;
    line-height: 1.6;
  }

  /* Debug Modal */
  #debugModal {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 80%;
    max-width: 800px;
    max-height: 80vh;
    background: #111;
    border: 2px solid var(--gold);
    border-radius: 8px;
    z-index: 1000;
    padding: 24px;
    display: none;
    flex-direction: column;
    box-shadow: 0 0 50px rgba(0,0,0,0.8);
  }
  #debugModal pre {
    background: #000;
    padding: 16px;
    color: #50fa7b;
    font-family: 'DM Mono', monospace;
    font-size: 12px;
    overflow: auto;
    flex: 1;
    border: 1px solid #333;
  }
  #debugOverlay {
    position: fixed;
    top: 0; left: 0; width: 100%; height: 100%;
    background: rgba(0,0,0,0.85);
    z-index: 999;
    display: none;
  }

  /* ── HEADER ── */
  header {
    background: var(--navy-mid);
    border-bottom: 1px solid var(--border);
    padding: 0 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    height: 64px;
    position: sticky;
    top: 0;
    z-index: 100;
  }

  .logo {
    display: flex;
    align-items: center;
    gap: 14px;
  }

  .logo-mark {
    width: 36px;
    height: 36px;
    background: var(--gold);
    border-radius: 3px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-family: 'Barlow', sans-serif;
    font-weight: 800;
    font-size: 11px;
    letter-spacing: 0.05em;
    color: var(--navy);
    flex-shrink: 0;
  }

  .logo-text {
    display: flex;
    flex-direction: column;
    gap: 1px;
  }

  .logo-name {
    font-family: 'Barlow', sans-serif;
    font-size: 15px;
    font-weight: 600;
    color: var(--cream);
    letter-spacing: 0.02em;
  }

  .logo-sub {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    color: rgba(255,120,118,0.8);
    font-weight: 500;
  }

  .header-right {
    display: flex;
    align-items: center;
    gap: 20px;
  }

  .header-label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--text-muted);
  }

  /* ── LAYOUT ── */
  .app-wrapper {
    display: grid;
    grid-template-columns: 240px 1fr;
    min-height: calc(100vh - 64px);
  }

  /* ── SIDEBAR ── */
  .sidebar {
    background: var(--navy-mid);
    border-right: 1px solid var(--border-subtle);
    padding: 32px 0;
    position: sticky;
    top: 64px;
    height: calc(100vh - 64px);
    overflow-y: auto;
  }

  .sidebar-section {
    padding: 0 20px 24px;
    border-bottom: 1px solid var(--border-subtle);
    margin-bottom: 8px;
  }

  .sidebar-section-label {
    font-size: 9px;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    color: var(--text-faint);
    font-weight: 600;
    margin-bottom: 10px;
    padding: 0 4px;
  }

  .nav-item {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 9px 12px;
    border-radius: var(--radius);
    cursor: pointer;
    transition: all 0.15s;
    color: var(--text-muted);
    font-size: 13px;
    font-weight: 400;
    border: 1px solid transparent;
  }

  .nav-item:hover {
    background: var(--cream-dim);
    color: var(--text);
  }

  .nav-item.active {
    background: rgba(102,14,12,0.15);
    border-color: rgba(102,14,12,0.5);
    border-left: 3px solid var(--gold);
    color: #f08080;
    font-weight: 500;
  }

  .nav-step {
    width: 20px;
    height: 20px;
    border-radius: 50%;
    border: 1px solid currentColor;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    flex-shrink: 0;
    font-family: 'DM Mono', monospace;
    opacity: 0.7;
  }

  .nav-item.active .nav-step {
    background: var(--gold);
    border-color: var(--gold);
    color: #fff;
    opacity: 1;
  }

  .nav-item.complete .nav-step::before {
    content: '✓';
    font-size: 10px;
  }

  /* ── MAIN ── */
  .main {
    padding: 40px 48px;
    max-width: 960px;
  }

  .page { display: none; animation: fadeIn 0.2s ease; }
  .page.active { display: block; }

  @keyframes fadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to { opacity: 1; transform: translateY(0); }
  }

  /* ── PAGE HEADER ── */
  .page-header {
    margin-bottom: 32px;
    padding-bottom: 24px;
    border-bottom: 1px solid var(--border-subtle);
  }

  .page-eyebrow {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.14em;
    color: #f08080;
    font-weight: 600;
    margin-bottom: 8px;
  }

  .page-title {
    font-family: 'Barlow', sans-serif;
    font-size: 26px;
    font-weight: 600;
    color: var(--cream);
    line-height: 1.2;
  }

  .page-desc {
    margin-top: 8px;
    color: var(--text-muted);
    font-size: 14px;
    max-width: 560px;
  }

  /* ── FORM ELEMENTS ── */
  .form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 24px;
  }

  .form-grid-3 {
    grid-template-columns: 1fr 1fr 1fr;
  }

  .form-group {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }

  .form-group.full { grid-column: 1 / -1; }

  label {
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: var(--text-muted);
    font-weight: 500;
  }

  input[type="text"],
  input[type="number"],
  select,
  textarea {
    background: var(--input-bg);
    border: 1px solid var(--input-border);
    border-radius: var(--radius);
    color: var(--text);
    font-family: 'Barlow', sans-serif;
    font-size: 14px;
    padding: 10px 12px;
    transition: border-color 0.15s, box-shadow 0.15s;
    width: 100%;
    outline: none;
    -webkit-appearance: none;
  }

  input[type="number"] {
    font-family: 'DM Mono', monospace;
    font-size: 13px;
  }

  input:focus, select:focus, textarea:focus {
    border-color: var(--gold);
    box-shadow: 0 0 0 3px var(--input-focus);
  }

  select option { background: var(--navy-mid); }

  textarea { resize: vertical; min-height: 80px; }

  .input-prefix {
    position: relative;
  }

  .input-prefix::before {
    content: '£';
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gold);
    font-family: 'DM Mono', monospace;
    font-size: 13px;
    pointer-events: none;
    z-index: 1;
  }

  .input-prefix input {
    padding-left: 26px;
  }

  /* ── SECTION TITLES ── */
  .section-title {
    font-family: 'Barlow', sans-serif;
    font-size: 16px;
    font-weight: 600;
    color: var(--cream);
    margin-bottom: 16px;
    margin-top: 32px;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--border-subtle);
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .section-title:first-child { margin-top: 0; }

  .section-title .pill {
    font-family: 'Barlow', sans-serif;
    font-size: 10px;
    background: rgba(102,14,12,0.15);
    color: #f08080;
    border: 1px solid rgba(102,14,12,0.3);
    padding: 2px 8px;
    border-radius: 20px;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-weight: 600;
  }

  /* ── FINANCIALS TABLE ── */
  .fin-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 24px;
  }

  .fin-table th {
    text-align: center;
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--text-muted);
    font-weight: 600;
    padding: 8px 12px;
    border-bottom: 1px solid var(--border-subtle);
  }

  .fin-table th:first-child { text-align: left; }

  .fin-table td {
    padding: 6px 12px;
    border-bottom: 1px solid var(--border-subtle);
    vertical-align: middle;
  }

  .fin-table td:first-child {
    color: var(--text-muted);
    font-size: 13px;
  }

  .fin-table td:not(:first-child) {
    text-align: center;
  }

  .fin-table tr.subtotal td {
    background: var(--cream-dim);
    font-weight: 600;
    color: var(--cream);
    font-family: 'DM Mono', monospace;
    font-size: 13px;
  }

  .fin-table tr.total td {
    background: rgba(102,14,12,0.1);
    font-weight: 600;
    color: #f08080;
    font-family: 'DM Mono', monospace;
    font-size: 13px;
    border-top: 1px solid rgba(102,14,12,0.3);
  }

  .fin-table input[type="number"] {
    background: transparent;
    border: none;
    border-bottom: 1px solid var(--input-border);
    border-radius: 0;
    padding: 4px 6px;
    text-align: center;
    width: 130px;
    color: var(--text);
    font-size: 13px;
  }

  .fin-table input[type="number"]:focus {
    border-bottom-color: var(--gold);
    box-shadow: none;
    background: var(--gold-dim);
    border-radius: 3px;
  }

  /* ── ADJUSTMENTS ── */
  .adj-row {
    display: grid;
    grid-template-columns: 1fr 130px 130px 130px 130px;
    gap: 8px;
    align-items: center;
    padding: 8px 12px;
    border-bottom: 1px solid var(--border-subtle);
    transition: background 0.1s;
  }

  .adj-row:hover { background: var(--cream-dim); }

  .adj-row.header {
    font-size: 10px;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    color: var(--text-muted);
    font-weight: 600;
    padding-bottom: 8px;
    border-bottom: 1px solid var(--border);
  }

  .adj-row.total-row {
    background: rgba(102,14,12,0.12);
    border-top: 1px solid rgba(102,14,12,0.3);
    border-bottom: none;
    font-weight: 600;
    color: #f08080;
    margin-top: 4px;
  }

  .adj-label { font-size: 13px; color: var(--text-muted); }
  .adj-label.category { color: var(--text); font-weight: 500; }

  .adj-input {
    background: transparent;
    border: none;
    border-bottom: 1px solid var(--input-border);
    color: var(--text);
    font-family: 'DM Mono', monospace;
    font-size: 12px;
    padding: 3px 6px;
    text-align: right;
    width: 100%;
    outline: none;
    transition: all 0.15s;
    -webkit-appearance: none;
  }

  .adj-input:focus {
    border-bottom-color: var(--gold);
    background: var(--gold-dim);
    border-radius: 3px;
  }

  .adj-value {
    font-family: 'DM Mono', monospace;
    font-size: 12px;
    text-align: right;
    color: var(--text-muted);
  }

  .adj-value.positive { color: var(--success); }
  .adj-value.negative { color: #e57373; }

  /* ── MULTIPLE SELECTOR ── */
  .multiple-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 24px;
  }

  .multiple-card {
    background: var(--input-bg);
    border: 1px solid var(--input-border);
    border-radius: var(--radius-lg);
    padding: 16px;
    cursor: pointer;
    transition: all 0.15s;
    text-align: center;
  }

  .multiple-card:hover { border-color: var(--text-muted); }

  .multiple-card.selected {
    background: rgba(102,14,12,0.15);
    border-color: var(--gold);
  }

  .multiple-card .year { font-size: 11px; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 4px; }
  .multiple-card .weight { font-family: 'Barlow', sans-serif; font-size: 26px; color: var(--cream); }
  .multiple-card.selected .weight { color: var(--gold-light); }
  .multiple-card .label { font-size: 11px; color: var(--text-muted); margin-top: 4px; }

  /* ── RESULTS ── */
  .results-hero {
    background: linear-gradient(135deg, #1e1e30 0%, #16162a 100%);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 36px;
    margin-bottom: 28px;
    position: relative;
    overflow: hidden;
  }

  .results-hero::before {
    content: '';
    position: absolute;
    top: -60px; right: -60px;
    width: 200px; height: 200px;
    background: radial-gradient(circle, rgba(102,14,12,0.2) 0%, transparent 70%);
    pointer-events: none;
  }

  .valuation-range {
    display: flex;
    align-items: center;
    gap: 0;
    margin-top: 20px;
  }

  .val-point {
    flex: 1;
    text-align: center;
    padding: 16px;
    position: relative;
  }

  .val-point + .val-point::before {
    content: '';
    position: absolute;
    left: 0; top: 20%; height: 60%;
    width: 1px;
    background: var(--border);
  }

  .val-point .label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted); margin-bottom: 6px; }
  .val-point .amount { font-family: 'Barlow', sans-serif; font-size: 32px; color: var(--cream); }
  .val-point.mid .amount { font-size: 44px; color: #f08080; }
  .val-point .sublabel { font-size: 11px; color: var(--text-muted); margin-top: 4px; }

  .results-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
    margin-bottom: 28px;
  }

  .result-card {
    background: var(--navy-mid);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-lg);
    padding: 20px;
  }

  .result-card .card-label { font-size: 10px; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted); margin-bottom: 8px; }
  .result-card .card-value { font-family: 'DM Mono', monospace; font-size: 22px; color: var(--cream); }
  .result-card .card-sub { font-size: 12px; color: var(--text-muted); margin-top: 4px; }

  .ebitda-breakdown {
    background: var(--navy-mid);
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius-lg);
    padding: 24px;
    margin-bottom: 28px;
  }

  .breakdown-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid var(--border-subtle);
  }

  .breakdown-row:last-child { border-bottom: none; }
  .breakdown-row .year { font-size: 12px; color: var(--text-muted); }
  .breakdown-row .ebitda-val { font-family: 'DM Mono', monospace; font-size: 13px; }
  .breakdown-row .weight { font-size: 11px; color: var(--text-muted); background: var(--cream-dim); padding: 2px 8px; border-radius: 20px; }
  .breakdown-row .weighted { font-family: 'DM Mono', monospace; font-size: 13px; color: #f08080; }

  .share-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 12px;
  }

  .share-table th {
    font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em;
    color: var(--text-muted); text-align: left; padding: 8px 12px;
    border-bottom: 1px solid var(--border);
  }

  .share-table td {
    padding: 10px 12px;
    border-bottom: 1px solid var(--border-subtle);
    font-size: 13px;
  }

  .share-table td:last-child, .share-table th:last-child { text-align: right; font-family: 'DM Mono', monospace; }

  /* ── BUTTONS ── */
  .btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 10px 20px;
    border-radius: var(--radius);
    font-family: 'Barlow', sans-serif;
    font-size: 13px;
    font-weight: 500;
    cursor: pointer;
    transition: all 0.15s;
    border: none;
    letter-spacing: 0.02em;
  }

  .btn-primary {
    background: var(--gold);
    color: #ffffff;
  }

  .btn-primary:hover { background: #7a1210; }

  .btn-outline {
    background: transparent;
    border: 1px solid var(--border);
    color: var(--text);
  }

  .btn-outline:hover { border-color: var(--gold); color: var(--gold); }

  .btn-ghost {
    background: transparent;
    color: var(--text-muted);
    padding: 10px 0;
  }

  .btn-ghost:hover { color: var(--text); }

  .btn-lg {
    padding: 14px 28px;
    font-size: 14px;
  }

  .btn-row {
    display: flex;
    gap: 12px;
    align-items: center;
    margin-top: 32px;
    padding-top: 24px;
    border-top: 1px solid var(--border-subtle);
  }

  .btn-row .spacer { flex: 1; }

  /* ── SHAREHOLDERS ── */
  .shareholder-row {
    display: grid;
    grid-template-columns: 1fr 100px 1fr 80px;
    gap: 10px;
    align-items: center;
    padding: 8px 0;
    border-bottom: 1px solid var(--border-subtle);
  }

  .shareholder-row.header {
    font-size: 10px; text-transform: uppercase; letter-spacing: 0.08em;
    color: var(--text-muted); font-weight: 600;
    border-bottom: 1px solid var(--border);
    padding-bottom: 8px;
  }

  .add-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: var(--cream-dim);
    border: 1px dashed var(--input-border);
    border-radius: var(--radius);
    color: var(--text-muted);
    cursor: pointer;
    font-size: 12px;
    font-family: 'Barlow', sans-serif;
    transition: all 0.15s;
    margin-top: 10px;
  }

  .add-btn:hover { border-color: var(--gold); color: var(--gold); }

  .remove-btn {
    width: 24px; height: 24px;
    background: transparent;
    border: 1px solid var(--border-subtle);
    border-radius: 3px;
    color: var(--text-faint);
    cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    font-size: 14px;
    transition: all 0.15s;
  }

  .remove-btn:hover { border-color: #e57373; color: #e57373; }

  /* ── INFO BOX ── */
  .info-box {
    background: var(--gold-dim);
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    padding: 16px 20px;
    margin-bottom: 20px;
    display: flex;
    gap: 12px;
    align-items: flex-start;
  }

  .info-box .icon { color: var(--gold); font-size: 16px; flex-shrink: 0; margin-top: 1px; }
  .info-box .text { font-size: 13px; color: var(--text-muted); line-height: 1.5; }

  /* ── SCROLLBAR ── */
  ::-webkit-scrollbar { width: 6px; }
  ::-webkit-scrollbar-track { background: transparent; }
  ::-webkit-scrollbar-thumb { background: var(--border); border-radius: 3px; }
  ::-webkit-scrollbar-thumb:hover { background: var(--text-faint); }

  /* ── PRINT / REPORT STYLES ── */
  .report-disclaimer {
    font-size: 12px;
    color: var(--text-muted);
    line-height: 1.6;
    padding: 16px;
    border: 1px solid var(--border-subtle);
    border-radius: var(--radius);
    margin-top: 24px;
  }

  .report-disclaimer strong { color: var(--text); }

  /* narrative textarea */
  .narrative-box {
    width: 100%;
    min-height: 140px;
    background: var(--input-bg);
    border: 1px solid var(--input-border);
    border-radius: var(--radius);
    color: var(--text);
    font-family: 'Barlow', sans-serif;
    font-size: 14px;
    line-height: 1.7;
    padding: 14px;
    outline: none;
    resize: vertical;
  }

  .narrative-box:focus { border-color: var(--gold); box-shadow: 0 0 0 3px var(--input-focus); }

  .generating { opacity: 0.5; pointer-events: none; }

  .spinner {
    display: inline-block;
    width: 14px; height: 14px;
    border: 2px solid var(--navy);
    border-top-color: transparent;
    border-radius: 50%;
    animation: spin 0.6s linear infinite;
  }

  @keyframes spin { to { transform: rotate(360deg); } }

  /* status bar */
  .status-bar {
    position: fixed;
    bottom: 24px;
    right: 32px;
    background: var(--navy-mid);
    border: 1px solid var(--border);
    border-radius: 6px;
    padding: 10px 18px;
    font-size: 12px;
    color: var(--gold);
    display: flex;
    align-items: center;
    gap: 8px;
    opacity: 0;
    transform: translateY(8px);
    transition: all 0.3s;
    pointer-events: none;
    z-index: 200;
  }

  .status-bar.show { opacity: 1; transform: translateY(0); }
</style>
</head>
<body>

<header>
  <div class="logo">
    <img src="data:image/webp;base64,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" alt="GTA Accounting" style="height:32px; width:auto; display:block;">
    <span style="width:1px; height:28px; background:rgba(255,255,255,0.15); display:block; margin:0 4px;"></span>
    <span style="font-size:10px; text-transform:uppercase; letter-spacing:0.14em; color:rgba(255,120,118,0.7); font-weight:600; font-family:'Barlow',sans-serif;">Business Valuation</span>
  </div>
  <div class="header-right">
    <span class="header-label" id="clientNameDisplay">New Valuation</span>
  </div>
</header>

<div id="debugOverlay" onclick="closeDebug()"></div>
<div id="debugModal">
  <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
    <h3 style="color:var(--gold-light); margin:0;">AI Extraction Debug</h3>
    <button class="btn btn-outline" style="padding:4px 10px;" onclick="closeDebug()">Close</button>
  </div>
  <pre id="debugContent">Waiting for extraction...</pre>
</div>

<div class="app-wrapper">
  <nav class="sidebar">
    <div class="sidebar-section">
      <div class="sidebar-section-label">Steps</div>
      <div class="nav-item active" onclick="goTo(0)" id="nav0">
        <div class="nav-step">1</div>
        Business Details
      </div>
      <div class="nav-item" onclick="goTo(1)" id="nav1">
        <div class="nav-step">2</div>
        Financial Data
      </div>
      <div class="nav-item" onclick="goTo(2)" id="nav2">
        <div class="nav-step">3</div>
        Adjustments
      </div>
      <div class="nav-item" onclick="goTo(3)" id="nav3">
        <div class="nav-step">4</div>
        Shareholders
      </div>
      <div class="nav-item" onclick="goTo(4)" id="nav4">
        <div class="nav-step">5</div>
        Methodology
      </div>
      <div class="nav-item" onclick="goTo(5)" id="nav5">
        <div class="nav-step">6</div>
        Results &amp; Report
      </div>
    </div>
    <div style="padding: 16px 24px;">
      <div style="font-size:11px; color: var(--text-faint); line-height:1.6;">
        Prepared by<br>
        <span style="color: var(--text-muted);">ELK Design Services</span><br>
        Jamie Elkins
      </div>
    </div>

    <div style="margin: 24px 20px; padding: 16px; background: rgba(255,255,255,0.03); border: 1px solid var(--border-subtle); border-radius: 6px;">
      <div class="sidebar-section-label" style="margin-bottom:8px; color:var(--gold-light);">Project Roadmap</div>
      <ul style="list-style:none; font-size:11px; color:var(--text-muted); display:flex; flex-direction:column; gap:6px;">
        <li><span style="color:var(--success);">✓</span> Smart PDF Upload</li>
        <li><span style="color:var(--success);">✓</span> AI Data Extraction</li>
        <li><span style="color:var(--success);">✓</span> Net Debt Calculations</li>
        <li><span style="color:var(--gold);">○</span> Save to Database</li>
        <li><span style="color:var(--gold);">○</span> Client Dashboard</li>
        <li><span style="color:var(--gold);">○</span> High-Fidelity PDF Export</li>
        <li><span style="color:var(--gold);">○</span> Multi-User Auth</li>
      </ul>
    </div>
  </nav>

  <main class="main">

    <!-- PAGE 1: Business Details -->
    <div class="page active" id="page0">
      <div class="page-header">
        <div class="page-eyebrow">Step 1 of 6</div>
        <h1 class="page-title">Business Details</h1>
        <p class="page-desc">Upload statutory accounts to auto-fill the valuation setup, or enter the client company information manually.</p>
      </div>

      <div class="info-box" id="uploadBox" style="background: var(--navy-mid); border: 1px dashed var(--gold); padding: 32px; flex-direction: column; align-items: center; text-align: center; gap: 16px;">
        <div style="font-size: 24px;">📄</div>
        <div>
          <strong style="color: var(--cream); display: block; margin-bottom: 4px;">Smart PDF Upload</strong>
          <span class="text">Upload up to 3 years of Final Accounts. Gemini Pro will extract business details, financial figures, and share structure automatically.</span>
        </div>
        <input type="file" id="pdfUpload" multiple accept=".pdf" style="display: none;" onchange="handleFileUpload(event)">
        <button class="btn btn-outline" onclick="document.getElementById('pdfUpload').click()" id="uploadBtn">
          <span>Choose PDF Files</span>
        </button>
        <div id="uploadStatus" style="font-size: 11px; color: var(--gold); margin-top: 8px; display: none;">
          <span class="spinner"></span> Processing accounts please wait...
        </div>
      </div>

      <div class="form-grid">
        <div class="form-group full">
          <label>Company Name</label>
          <input type="text" id="companyName" placeholder="e.g. Acme Trading Limited" oninput="updateHeader()">
        </div>
        <div class="form-group">
          <label>Company Number</label>
          <input type="text" id="companyNumber" placeholder="e.g. 12345678">
        </div>
        <div class="form-group">
          <label>Sector / Industry</label>
          <select id="sector">
            <option value="">Select sector…</option>
            <option>Professional Services</option>
            <option>HR &amp; Recruitment</option>
            <option>IT &amp; Technology</option>
            <option>Construction &amp; Trades</option>
            <option>Retail</option>
            <option>Hospitality &amp; Leisure</option>
            <option>Manufacturing</option>
            <option>Healthcare</option>
            <option>Financial Services</option>
            <option>Property</option>
            <option>Other</option>
          </select>
        </div>
        <div class="form-group">
          <label>Year End</label>
          <select id="yearEnd">
            <option>30 April</option>
            <option>31 March</option>
            <option>31 December</option>
            <option>30 June</option>
            <option>31 January</option>
            <option>Other</option>
          </select>
        </div>
        <div class="form-group">
          <label>Years Trading</label>
          <input type="number" id="yearsTrading" placeholder="10" min="1">
        </div>
        <div class="form-group">
          <label>Number of Employees</label>
          <input type="number" id="employees" placeholder="8" min="1">
        </div>
        <div class="form-group">
          <label>Purpose of Valuation</label>
          <select id="purpose">
            <option>Share sale / exit</option>
            <option>Shareholder buyout</option>
            <option>Succession planning</option>
            <option>Keyman insurance</option>
            <option>Shareholder protection</option>
            <option>HMRC / tax planning</option>
            <option>Management buyout (MBO)</option>
            <option>Investment / funding</option>
            <option>Divorce / legal dispute</option>
            <option>General advisory</option>
          </select>
        </div>
        <div class="form-group">
          <label>Report Date</label>
          <input type="text" id="reportDate" placeholder="e.g. March 2025">
        </div>
        <div class="form-group full">
          <label>Business Description (optional)</label>
          <textarea id="businessDesc" placeholder="Brief description of the business — what it does, its market position, key clients or contracts…"></textarea>
        </div>
      </div>

      <div class="btn-row">
        <div class="spacer"></div>
        <button class="btn btn-primary" onclick="goTo(1)">Continue to Financial Data →</button>
      </div>
    </div>

    <!-- PAGE 2: Financial Data -->
    <div class="page" id="page1">
      <div class="page-header">
        <div class="page-eyebrow">Step 2 of 6</div>
        <h1 class="page-title">Financial Data</h1>
        <p class="page-desc">Enter three years of P&amp;L figures as reported in the statutory accounts. These may have been auto-filled by your Step 1 upload.</p>
      </div>

      <div class="section-title">Profit &amp; Loss <span class="pill">3 Years</span></div>

      <table class="fin-table">
        <thead>
          <tr>
            <th style="width:40%">Line Item</th>
            <th>Year 1 (oldest) £</th>
            <th>Year 2 £</th>
            <th>Year 3 (most recent) £</th>
          </tr>
        </thead>
        <tbody>
          <tr><td>Turnover</td>
            <td><input type="number" id="f_turn1" placeholder="0" oninput="calcFinancials()"></td>
            <td><input type="number" id="f_turn2" placeholder="0" oninput="calcFinancials()"></td>
            <td><input type="number" id="f_turn3" placeholder="0" oninput="calcFinancials()"></td>
          </tr>
          <tr><td>Cost of Sales</td>
            <td><input type="number" id="f_cos1" placeholder="0" oninput="calcFinancials()"></td>
            <td><input type="number" id="f_cos2" placeholder="0" oninput="calcFinancials()"></td>
            <td><input type="number" id="f_cos3" placeholder="0" oninput="calcFinancials()"></td>
          </tr>
          <tr class="subtotal">
            <td>Gross Profit</td>
            <td id="f_gp1">—</td><td id="f_gp2">—</td><td id="f_gp3">—</td>
          </tr>
          <tr><td>Administrative Expenses</td>
            <td><input type="number" id="f_admin1" placeholder="0" oninput="calcFinancials()"></td>
            <td><input type="number" id="f_admin2" placeholder="0" oninput="calcFinancials()"></td>
            <td><input type="number" id="f_admin3" placeholder="0" oninput="calcFinancials()"></td>
          </tr>
          <tr><td>Other Operating Income</td>
            <td><input type="number" id="f_other1" placeholder="0" oninput="calcFinancials()"></td>
            <td><input type="number" id="f_other2" placeholder="0" oninput="calcFinancials()"></td>
            <td><input type="number" id="f_other3" placeholder="0" oninput="calcFinancials()"></td>
          </tr>
          <tr class="total">
            <td>Operating Profit (EBIT)</td>
            <td id="f_op1">—</td><td id="f_op2">—</td><td id="f_op3">—</td>
          </tr>
          <tr><td>Depreciation &amp; Amortisation</td>
            <td><input type="number" id="f_dep1" placeholder="0" oninput="calcFinancials()"></td>
            <td><input type="number" id="f_dep2" placeholder="0" oninput="calcFinancials()"></td>
            <td><input type="number" id="f_dep3" placeholder="0" oninput="calcFinancials()"></td>
          </tr>
          <tr class="total">
            <td>EBITDA (pre-adjustment)</td>
            <td id="f_ebitda1">—</td><td id="f_ebitda2">—</td><td id="f_ebitda3">—</td>
          </tr>
        </tbody>
      </table>

      <div class="section-title">Balance Sheet <span class="pill">Most Recent Year</span></div>
      <div class="form-grid">
        <div class="form-group">
          <label>Net Assets (£)</label>
          <div class="input-prefix"><input type="number" id="b_netassets" placeholder="0"></div>
        </div>
        <div class="form-group">
          <label>Cash at Bank (£)</label>
          <div class="input-prefix"><input type="number" id="b_cash" placeholder="0"></div>
        </div>
        <div class="form-group">
          <label>Total Debtors (£)</label>
          <div class="input-prefix"><input type="number" id="b_debtors" placeholder="0"></div>
        </div>
        <div class="form-group">
          <label>Bank Loans Outstanding (£)</label>
          <div class="input-prefix"><input type="number" id="b_loans" placeholder="0"></div>
        </div>
      </div>

      <div class="btn-row">
        <button class="btn btn-ghost" onclick="goTo(0)">← Back</button>
        <div class="spacer"></div>
        <button class="btn btn-primary" onclick="goTo(2)">Continue to Adjustments →</button>
      </div>
    </div>

    <!-- PAGE 3: Adjustments -->
    <div class="page" id="page2">
      <div class="page-header">
        <div class="page-eyebrow">Step 3 of 6</div>
        <h1 class="page-title">Adjustments &amp; Addbacks</h1>
        <p class="page-desc">Normalise the accounts for EBITDA valuation purposes. Enter adjustments per year — positive values add back, negative values deduct.</p>
      </div>

      <div class="info-box">
        <span class="icon">ℹ</span>
        <span class="text">Common adjustments: director salary excess above market rate, one-off costs, personal expenses through the business, non-recurring income. Depreciation is already added back in step 2.</span>
      </div>

      <div class="adj-row header">
        <div>Adjustment Item</div>
        <div style="text-align:right">Year 1 £</div>
        <div style="text-align:right">Year 2 £</div>
        <div style="text-align:right">Year 3 £</div>
        <div style="text-align:right">Notes</div>
      </div>

      <div id="adjRows"></div>

      <button class="add-btn" onclick="addAdjRow()">+ Add adjustment</button>

      <div class="adj-row total-row" style="margin-top:16px">
        <div class="adj-label category">Net Adjustment</div>
        <div class="adj-value" id="adj_tot1">£0</div>
        <div class="adj-value" id="adj_tot2">£0</div>
        <div class="adj-value" id="adj_tot3">£0</div>
        <div></div>
      </div>

      <div class="adj-row total-row" style="background: rgba(76,175,130,0.1); border-color: rgba(76,175,130,0.3);">
        <div class="adj-label category" style="color: var(--success)">Adjusted EBITDA</div>
        <div class="adj-value positive" id="adj_ebitda1">£0</div>
        <div class="adj-value positive" id="adj_ebitda2">£0</div>
        <div class="adj-value positive" id="adj_ebitda3">£0</div>
        <div></div>
      </div>

      <div class="btn-row">
        <button class="btn btn-ghost" onclick="goTo(1)">← Back</button>
        <div class="spacer"></div>
        <button class="btn btn-primary" onclick="goTo(3)">Continue to Shareholders →</button>
      </div>
    </div>

    <!-- PAGE 4: Shareholders -->
    <div class="page" id="page3">
      <div class="page-header">
        <div class="page-eyebrow">Step 4 of 6</div>
        <h1 class="page-title">Share Structure</h1>
        <p class="page-desc">Enter each shareholder, their share class, and the number of shares held.</p>
      </div>

      <div class="shareholder-row header">
        <div>Shareholder Name</div>
        <div>Shares Held</div>
        <div>Share Class</div>
        <div></div>
      </div>

      <div id="shareholderRows"></div>

      <button class="add-btn" onclick="addShareholderRow()">+ Add shareholder</button>

      <div style="margin-top: 16px; padding: 12px 16px; background: var(--cream-dim); border-radius: var(--radius); display:flex; justify-content:space-between; align-items:center;">
        <span style="font-size:13px; color: var(--text-muted);">Total shares issued</span>
        <span style="font-family: 'DM Mono', monospace; font-size:15px; color: var(--cream);" id="totalSharesDisplay">0</span>
      </div>

      <div class="btn-row">
        <button class="btn btn-ghost" onclick="goTo(2)">← Back</button>
        <div class="spacer"></div>
        <button class="btn btn-primary" onclick="goTo(4)">Continue to Methodology →</button>
      </div>
    </div>

    <!-- PAGE 5: Methodology -->
    <div class="page" id="page4">
      <div class="page-header">
        <div class="page-eyebrow">Step 5 of 6</div>
        <h1 class="page-title">Methodology &amp; Multiples</h1>
        <p class="page-desc">Set the EBITDA weighting and multiple range for this valuation.</p>
      </div>

      <div class="section-title">Year Weighting</div>
      <p style="font-size:13px; color: var(--text-muted); margin-bottom:16px;">Most recent year carries more weight. Standard is 1×, 2×, 3× (oldest to most recent).</p>

      <div class="multiple-grid">
        <div class="multiple-card" onclick="setWeighting('1-2-3')" id="w123">
          <div class="year">Standard</div>
          <div class="weight">1:2:3</div>
          <div class="label">Oldest → Newest</div>
        </div>
        <div class="multiple-card" onclick="setWeighting('1-1-3')" id="w113">
          <div class="year">Recent-heavy</div>
          <div class="weight">1:1:3</div>
          <div class="label">Emphasise latest</div>
        </div>
        <div class="multiple-card" onclick="setWeighting('1-1-1')" id="w111">
          <div class="year">Equal</div>
          <div class="weight">1:1:1</div>
          <div class="label">Simple average</div>
        </div>
      </div>

      <div class="section-title">EBITDA Multiple Range</div>
      <p style="font-size:13px; color: var(--text-muted); margin-bottom:16px;">Set the low, mid, and high multiples for this sector. Professional services typically 3–6×.</p>

      <div class="form-grid form-grid-3">
        <div class="form-group">
          <label>Low Multiple (×)</label>
          <input type="number" id="multLow" value="2.5" step="0.25" oninput="calcResults()">
        </div>
        <div class="form-group">
          <label>Mid Multiple (×)</label>
          <input type="number" id="multMid" value="3.5" step="0.25" oninput="calcResults()">
        </div>
        <div class="form-group">
          <label>High Multiple (×)</label>
          <input type="number" id="multHigh" value="5" step="0.25" oninput="calcResults()">
        </div>
      </div>

      <div class="section-title">Value Reductions</div>
      <p style="font-size:13px; color: var(--text-muted); margin-bottom:16px;">Apply deductions to the headline valuation — e.g. earnout risk, pending liabilities, key-person dependency.</p>

      <div class="form-grid">
        <div class="form-group">
          <label>Deduction Amount (£)</label>
          <div class="input-prefix"><input type="number" id="deduction" placeholder="0" oninput="calcResults()"></div>
        </div>
        <div class="form-group">
          <label>Deduction Description</label>
          <input type="text" id="deductionDesc" placeholder="e.g. Pending shareholder loan repayment">
        </div>
      </div>

      <div class="section-title">
        Accountant's Commentary
        <button id="aiMethodBtn" class="btn btn-outline" style="margin-left:auto; font-size:11px; padding:4px 10px; color:var(--gold-light);" onclick="generateNarrative('accountantNotes')">✦ Generate with AI</button>
      </div>
      <div class="form-group">
        <label>Detailed commentary for report</label>
        <textarea class="narrative-box" id="accountantNotes" placeholder="Describe the valuation rationale and financial performance here…"></textarea>
      </div>

      <div class="btn-row">
        <button class="btn btn-ghost" onclick="goTo(3)">← Back</button>
        <div class="spacer"></div>
        <button class="btn btn-primary btn-lg" onclick="goTo(5); calcResults()">Calculate Valuation →</button>
      </div>
    </div>

    <!-- PAGE 6: Results -->
    <div class="page" id="page5">
      <div class="page-header">
        <div class="page-eyebrow">Step 6 of 6</div>
        <h1 class="page-title">Valuation Results</h1>
        <p class="page-desc">Review the valuation before generating the client report.</p>
      </div>

      <div class="results-hero">
        <div class="page-eyebrow" id="r_company">—</div>
        <div style="font-family:'Playfair Display',serif; font-size:14px; color:var(--text-muted); margin-top:4px;" id="r_purpose">—</div>
        <div class="valuation-range">
          <div class="val-point">
            <div class="label">Conservative</div>
            <div class="amount" id="r_low">—</div>
            <div class="sublabel" id="r_low_mult">—</div>
          </div>
          <div class="val-point mid">
            <div class="label">Mid-point</div>
            <div class="amount" id="r_mid">—</div>
            <div class="sublabel" id="r_mid_mult">—</div>
          </div>
          <div class="val-point">
            <div class="label">Optimistic</div>
            <div class="amount" id="r_high">—</div>
            <div class="sublabel" id="r_high_mult">—</div>
          </div>
        </div>
      </div>

      <div class="results-grid">
        <div class="result-card">
          <div class="card-label">Weighted EBITDA</div>
          <div class="card-value" id="r_ebitda">—</div>
          <div class="card-sub" id="r_weighting">—</div>
        </div>
        <div class="result-card">
          <div class="card-label">Most Recent Turnover</div>
          <div class="card-value" id="r_turnover">—</div>
          <div class="card-sub" id="r_margin">—</div>
        </div>
        <div class="result-card">
          <div class="card-label">Net Assets</div>
          <div class="card-value" id="r_netassets">—</div>
          <div class="card-sub">Balance sheet basis</div>
        </div>
        <div class="result-card">
          <div class="card-label">Cash at Bank</div>
          <div class="card-value" id="r_cash">—</div>
          <div class="card-sub">Most recent year end</div>
        </div>
      </div>

      <div class="section-title">EBITDA Breakdown by Year</div>
      <div class="ebitda-breakdown">
        <div class="breakdown-row">
          <span class="year">Year 1 (oldest)</span>
          <span class="ebitda-val" id="r_ebitda_y1">—</span>
          <span class="weight" id="r_w1">×1</span>
          <span class="weighted" id="r_wv1">—</span>
        </div>
        <div class="breakdown-row">
          <span class="year">Year 2</span>
          <span class="ebitda-val" id="r_ebitda_y2">—</span>
          <span class="weight" id="r_w2">×2</span>
          <span class="weighted" id="r_wv2">—</span>
        </div>
        <div class="breakdown-row">
          <span class="year">Year 3 (most recent)</span>
          <span class="ebitda-val" id="r_ebitda_y3">—</span>
          <span class="weight" id="r_w3">×3</span>
          <span class="weighted" id="r_wv3">—</span>
        </div>
        <div class="breakdown-row" style="padding-top:12px; border-top:1px solid var(--border);">
          <span style="font-weight:600; color:var(--cream)">Weighted Average EBITDA</span>
          <span></span>
          <span></span>
          <span class="weighted" style="font-size:15px; color:#f08080;" id="r_wAvg">—</span>
        </div>
      </div>

      <div class="section-title">Value per Share</div>
      <table class="share-table" id="r_shareTable">
        <thead>
          <tr>
            <th>Shareholder</th>
            <th>Class</th>
            <th>Shares</th>
            <th>Value (mid)</th>
          </tr>
        </thead>
        <tbody id="r_shareBody"></tbody>
      </table>

      <div class="section-title" style="margin-top:28px">
        Accountant's Commentary
        <button id="aiNarrativeBtn" class="btn btn-primary" style="margin-left:auto; font-size:12px; padding:7px 14px;" onclick="generateNarrative()">✦ Generate AI Narrative</button>
      </div>
      <textarea class="narrative-box" id="r_narrative" placeholder="Click 'Generate AI Narrative' for Gemini Pro commentary, or type your own…" style="min-height:140px;"></textarea>

      <div class="report-disclaimer">
        <strong>Important:</strong> This valuation report has been prepared for the purpose stated above and should not be relied upon for any other purpose. The valuation is based on information provided by the directors and has not been independently verified. This report constitutes an opinion, not a guarantee of the price achievable on any open market transaction. GTA Accounting accepts no liability to any third party in connection with this report.
      </div>

      <div class="btn-row">
        <button class="btn btn-ghost" onclick="goTo(4)">← Back</button>
        <div class="spacer"></div>
        <div id="saveInfo" style="font-size:11px; color:var(--text-faint); margin-right:12px; display:none;">Last saved: <span id="saveTime"></span></div>
        <button id="saveBtn" class="btn btn-outline" style="color:var(--success); border-color:var(--success);" onclick="saveValuation()">💾 Save Valuation</button>
        <button class="btn btn-outline" onclick="window.print()">🖨 Print Report</button>
        <button class="btn btn-primary btn-lg" onclick="showStatus('Report generation coming soon — PDF export will be available in the full version.')">Generate PDF Report</button>
      </div>
    </div>

  </main>
</div>

<div class="status-bar" id="statusBar">
  <span id="statusMsg"></span>
</div>

<script>
// ── STATE ──
let adjRows = [];
let shareholderRows = [];
let weighting = [1, 2, 3];
let adjRowCount = 0;
let shareRowCount = 0;

// ── PDF UPLOAD & EXTRACTION ──
async function handleFileUpload(event) {
  const files = event.target.files;
  if (!files.length) return;

  const status = document.getElementById('uploadStatus');
  const btn = document.getElementById('uploadBtn');
  const debugModal = document.getElementById('debugModal');
  const debugContent = document.getElementById('debugContent');
  const debugOverlay = document.getElementById('debugOverlay');

  status.style.display = 'block';
  btn.disabled = true;
  debugContent.textContent = 'Uploading and processing ' + files.length + ' files...';

  const fileData = [];
  for (const file of files) {
    const base64 = await toBase64(file);
    fileData.push({
      mimeType: file.type,
      data: base64.split(',')[1]
    });
  }

  try {
    const response = await fetch('vertex-proxy.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ 
        action: 'extract',
        files: fileData 
      })
    });

    const result = await response.json();
    console.log('Extraction Result:', result);
    
    // Show raw result in debug modal
    debugContent.textContent = JSON.stringify(result, null, 2);
    debugModal.style.display = 'flex';
    debugOverlay.style.display = 'block';

    if (result.error) throw new Error(result.error);
    
    if (!result.data || (typeof result.data === 'object' && Object.keys(result.data).length === 0)) {
      console.warn('No data found in result.data');
      showStatus('Extraction completed but no data was found.');
      return;
    }

    populateExtractedData(result.data);
    showStatus('Financial data extracted successfully ✓');
  } catch (err) {
    console.error(err);
    debugContent.textContent = 'ERROR: ' + err.message + '\n\n' + debugContent.textContent;
    showStatus('Extraction failed: ' + err.message);
  } finally {
    status.style.display = 'none';
    btn.disabled = false;
  }
}

function closeDebug() {
  document.getElementById('debugModal').style.display = 'none';
  document.getElementById('debugOverlay').style.display = 'none';
}

function toBase64(file) {
  return new Promise((resolve, reject) => {
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = () => resolve(reader.result);
    reader.onerror = error => reject(error);
  });
}

function populateExtractedData(data) {
  if (!data) {
    showStatus('Gemini returned an empty result. Please try again.');
    return;
  }
  // data is expected to be { year1: {...}, year2: {...}, year3: {...} }
  // Sorted oldest to newest
  const years = ['year1', 'year2', 'year3'];
  
  // 1. Populate Business Details (Step 1) from the most recent year
  const latest = data.year3 || data.year2 || data.year1;
  if (latest) {
    if (latest.companyName) {
      document.getElementById('companyName').value = latest.companyName;
      updateHeader();
    }
    if (latest.companyNumber) document.getElementById('companyNumber').value = latest.companyNumber;
    if (latest.yearEnd) document.getElementById('yearEnd').value = latest.yearEnd;
    if (latest.employees) document.getElementById('employees').value = latest.employees;
    if (latest.yearsTrading) document.getElementById('yearsTrading').value = latest.yearsTrading;
    
    if (latest.sector) {
      const sectorSelect = document.getElementById('sector');
      // Try to find a matching option
      Array.from(sectorSelect.options).forEach(opt => {
        if (opt.text.toLowerCase() === latest.sector.toLowerCase() || 
            latest.sector.toLowerCase().includes(opt.text.toLowerCase())) {
          sectorSelect.value = opt.text;
        }
      });
      // If no match found but a sector was provided, could default to "Other"
      if (!sectorSelect.value && latest.sector) sectorSelect.value = 'Other';
    }
    
    if (latest.description) document.getElementById('businessDesc').value = latest.description;
    if (latest.performanceCommentary) document.getElementById('accountantNotes').value = latest.performanceCommentary;
  }

  // 2. Populate Financial Data (Step 2)
  years.forEach((yKey, idx) => {
    const y = idx + 1;
    const d = data[yKey];
    if (!d) return;

    if (d.turnover) document.getElementById(`f_turn${y}`).value = d.turnover;
    if (d.cos) document.getElementById(`f_cos${y}`).value = d.cos;
    if (d.admin) document.getElementById(`f_admin${y}`).value = d.admin;
    if (d.other) document.getElementById(`f_other${y}`).value = d.other;
    if (d.depreciation) document.getElementById(`f_dep${y}`).value = d.depreciation;

    // Balance sheet (only for most recent year)
    if (idx === 2) {
      if (d.netAssets) document.getElementById('b_netassets').value = d.netAssets;
      if (d.cash) document.getElementById('b_cash').value = d.cash;
      if (d.debtors) document.getElementById('b_debtors').value = d.debtors;
      if (d.loans) document.getElementById('b_loans').value = d.loans;
    }
  });

  // 3. Handle adjustments (Step 3) - e.g. Director salaries
  const adjContainer = document.getElementById('adjRows');
  adjContainer.innerHTML = '';
  adjRowCount = 0;

  if ((data.year1 && data.year1.directorsSalaries) || 
      (data.year2 && data.year2.directorsSalaries) || 
      (data.year3 && data.year3.directorsSalaries)) {
    addAdjRow('Director salary adjustment', 
      (data.year1 && data.year1.directorsSalaries) ? data.year1.directorsSalaries : 0, 
      (data.year2 && data.year2.directorsSalaries) ? data.year2.directorsSalaries : 0, 
      (data.year3 && data.year3.directorsSalaries) ? data.year3.directorsSalaries : 0,
      'Extracted from accounts'
    );
  } else {
    addAdjRow('Director salary adjustment', '', '', '');
  }
  
  addAdjRow('Director pension addback', '', '', '');
  addAdjRow('Non-recurring / one-off costs', '', '', '');

  // 4. Handle Shareholders (Step 4)
  if (latest && latest.directors && Array.isArray(latest.directors) && latest.directors.length > 0) {
    const shContainer = document.getElementById('shareholderRows');
    shContainer.innerHTML = ''; // Clear existing
    shareRowCount = 0;
    
    let totalShares = 100; // Default
    if (latest.shareCapital && !isNaN(parseInt(latest.shareCapital))) {
       totalShares = parseInt(latest.shareCapital);
    }
    
    const numDirectors = latest.directors.length;
    const sharesPerDirector = Math.floor(totalShares / numDirectors);
    const remainder = totalShares % numDirectors;

    latest.directors.forEach((directorName, idx) => {
       // Give any remainder shares to the first director to ensure total matches
       const shares = sharesPerDirector + (idx === 0 ? remainder : 0);
       addShareholderRow(directorName, shares, 'Ordinary');
    });
  }

  calcFinancials();
}

// ── NAV ──
function goTo(idx) {
  document.querySelectorAll('.page').forEach((p, i) => p.classList.toggle('active', i === idx));
  document.querySelectorAll('.nav-item').forEach((n, i) => {
    n.classList.toggle('active', i === idx);
  });
  if (idx === 5) calcResults();
  window.scrollTo({ top: 0, behavior: 'smooth' });
}

function updateHeader() {
  const name = document.getElementById('companyName').value || 'New Valuation';
  document.getElementById('clientNameDisplay').textContent = name;
}

// ── FINANCIALS ──
function getNum(id) { return parseFloat(document.getElementById(id)?.value) || 0; }

function calcFinancials() {
  for (let y = 1; y <= 3; y++) {
    const turn = getNum(`f_turn${y}`);
    const cos = getNum(`f_cos${y}`);
    const admin = getNum(`f_admin${y}`);
    const other = getNum(`f_other${y}`);
    const dep = getNum(`f_dep${y}`);
    const gp = turn - cos;
    const op = gp - admin + other;
    const ebitda = op + dep;
    document.getElementById(`f_gp${y}`).textContent = fmt(gp);
    document.getElementById(`f_op${y}`).textContent = fmt(op);
    document.getElementById(`f_ebitda${y}`).textContent = fmt(ebitda);
  }
  calcAdjustments();
}

function getPreAdjEbitda(y) {
  const turn = getNum(`f_turn${y}`);
  const cos = getNum(`f_cos${y}`);
  const admin = getNum(`f_admin${y}`);
  const other = getNum(`f_other${y}`);
  const dep = getNum(`f_dep${y}`);
  const gp = turn - cos;
  const op = gp - admin + other;
  return op + dep;
}

// ── ADJUSTMENTS ──
function addAdjRow(label = '', v1 = '', v2 = '', v3 = '', notes = '') {
  const id = adjRowCount++;
  const row = document.createElement('div');
  row.className = 'adj-row';
  row.id = `adjRow${id}`;
  row.innerHTML = `
    <input type="text" class="adj-input" style="text-align:left; border-bottom: 1px solid var(--input-border); font-family:'DM Sans',sans-serif;" placeholder="e.g. Director salary addback" value="${label}" oninput="calcAdjustments()">
    <input type="number" class="adj-input" placeholder="0" value="${v1}" oninput="calcAdjustments()">
    <input type="number" class="adj-input" placeholder="0" value="${v2}" oninput="calcAdjustments()">
    <input type="number" class="adj-input" placeholder="0" value="${v3}" oninput="calcAdjustments()">
    <input type="text" class="adj-input" style="text-align:left; font-family:'DM Sans',sans-serif; font-size:11px;" placeholder="Note…" value="${notes}">
  `;
  document.getElementById('adjRows').appendChild(row);
  calcAdjustments();
}

function calcAdjustments() {
  const container = document.getElementById('adjRows');
  const rows = container.querySelectorAll('.adj-row');
  const totals = [0, 0, 0];
  rows.forEach(row => {
    const inputs = row.querySelectorAll('input[type="number"]');
    inputs.forEach((inp, i) => {
      totals[i] += parseFloat(inp.value) || 0;
    });
  });
  for (let i = 0; i < 3; i++) {
    const el = document.getElementById(`adj_tot${i+1}`);
    el.textContent = fmt(totals[i]);
    el.className = 'adj-value ' + (totals[i] >= 0 ? 'positive' : 'negative');
    const adjEbitda = getPreAdjEbitda(i + 1) + totals[i];
    document.getElementById(`adj_ebitda${i+1}`).textContent = fmt(adjEbitda);
  }
}

function getAdjEbitda(y) {
  const container = document.getElementById('adjRows');
  const rows = container.querySelectorAll('.adj-row');
  let total = 0;
  rows.forEach(row => {
    const inputs = row.querySelectorAll('input[type="number"]');
    if (inputs[y - 1]) total += parseFloat(inputs[y - 1].value) || 0;
  });
  return getPreAdjEbitda(y) + total;
}

// ── SHAREHOLDERS ──
function addShareholderRow(name = '', shares = '', cls = 'Ordinary') {
  const id = shareRowCount++;
  const row = document.createElement('div');
  row.className = 'shareholder-row';
  row.id = `shRow${id}`;
  row.innerHTML = `
    <input type="text" style="background:var(--input-bg); border:1px solid var(--input-border); border-radius:var(--radius); padding:8px 10px; color:var(--text); font-family:'DM Sans',sans-serif; font-size:13px; width:100%; outline:none;" placeholder="Shareholder name" value="${name}" oninput="updateShareTotal()">
    <input type="number" style="background:var(--input-bg); border:1px solid var(--input-border); border-radius:var(--radius); padding:8px 10px; color:var(--text); font-family:'DM Mono',monospace; font-size:13px; width:100%; outline:none;" placeholder="100" value="${shares}" min="1" oninput="updateShareTotal()">
    <select style="background:var(--navy-mid); border:1px solid var(--input-border); border-radius:var(--radius); padding:8px 10px; color:var(--text); font-family:'DM Sans',sans-serif; font-size:13px; width:100%; outline:none;">
      <option ${cls==='Ordinary'?'selected':''}>Ordinary</option>
      <option ${cls==='Ordinary A'?'selected':''}>Ordinary A</option>
      <option ${cls==='Ordinary B'?'selected':''}>Ordinary B</option>
      <option ${cls==='Ordinary C'?'selected':''}>Ordinary C</option>
      <option ${cls==='Preference'?'selected':''}>Preference</option>
    </select>
    <button class="remove-btn" onclick="this.closest('.shareholder-row').remove(); updateShareTotal()">×</button>
  `;
  document.getElementById('shareholderRows').appendChild(row);
  updateShareTotal();
}

function updateShareTotal() {
  let total = 0;
  document.querySelectorAll('#shareholderRows .shareholder-row').forEach(row => {
    const inp = row.querySelectorAll('input')[1];
    total += parseInt(inp?.value) || 0;
  });
  document.getElementById('totalSharesDisplay').textContent = total.toLocaleString();
}

// ── WEIGHTING ──
function setWeighting(key) {
  document.querySelectorAll('.multiple-card').forEach(c => c.classList.remove('selected'));
  if (key === '1-2-3') { weighting = [1,2,3]; document.getElementById('w123').classList.add('selected'); }
  else if (key === '1-1-3') { weighting = [1,1,3]; document.getElementById('w113').classList.add('selected'); }
  else { weighting = [1,1,1]; document.getElementById('w111').classList.add('selected'); }
  calcResults();
}

// ── RESULTS ──
function calcResults() {
  const e1 = getAdjEbitda(1);
  const e2 = getAdjEbitda(2);
  const e3 = getAdjEbitda(3);
  const [w1, w2, w3] = weighting;
  const totalWeight = w1 + w2 + w3;
  const wAvg = (e1 * w1 + e2 * w2 + e3 * w3) / totalWeight;

  const deduction = getNum('deduction');
  const multLow = getNum('multLow') || 2.5;
  const multMid = getNum('multMid') || 3.5;
  const multHigh = getNum('multHigh') || 5;

  // Calculate Net Debt (Loans - Cash)
  const cash = getNum('b_cash');
  const loans = getNum('b_loans');
  const netDebt = loans - cash; // Negative net debt means net cash

  const valLow = (wAvg * multLow) - netDebt - deduction;
  const valMid = (wAvg * multMid) - netDebt - deduction;
  const valHigh = (wAvg * multHigh) - netDebt - deduction;

  // hero
  document.getElementById('r_company').textContent = document.getElementById('companyName')?.value || '—';
  document.getElementById('r_purpose').textContent = document.getElementById('purpose')?.value || '—';

  document.getElementById('r_low').textContent = fmtShort(valLow);
  document.getElementById('r_mid').textContent = fmtShort(valMid);
  document.getElementById('r_high').textContent = fmtShort(valHigh);
  document.getElementById('r_low_mult').textContent = `${multLow}× EBITDA (Adj for Net Debt)`;
  document.getElementById('r_mid_mult').textContent = `${multMid}× EBITDA (Adj for Net Debt)`;
  document.getElementById('r_high_mult').textContent = `${multHigh}× EBITDA (Adj for Net Debt)`;

  // cards
  document.getElementById('r_ebitda').textContent = fmt(wAvg);
  document.getElementById('r_weighting').textContent = `Weighting: ${w1}:${w2}:${w3}`;
  const turn3 = getNum('f_turn3');
  document.getElementById('r_turnover').textContent = fmt(turn3);
  if (turn3) {
    const margin = ((getPreAdjEbitda(3) / turn3) * 100).toFixed(1);
    document.getElementById('r_margin').textContent = `EBITDA margin ${margin}%`;
  }
  document.getElementById('r_netassets').textContent = fmt(getNum('b_netassets'));
  document.getElementById('r_cash').textContent = fmt(getNum('b_cash'));

  // breakdown
  document.getElementById('r_ebitda_y1').textContent = fmt(e1);
  document.getElementById('r_ebitda_y2').textContent = fmt(e2);
  document.getElementById('r_ebitda_y3').textContent = fmt(e3);
  document.getElementById('r_w1').textContent = `×${w1}`;
  document.getElementById('r_w2').textContent = `×${w2}`;
  document.getElementById('r_w3').textContent = `×${w3}`;
  document.getElementById('r_wv1').textContent = fmt(e1 * w1);
  document.getElementById('r_wv2').textContent = fmt(e2 * w2);
  document.getElementById('r_wv3').textContent = fmt(e3 * w3);
  document.getElementById('r_wAvg').textContent = fmt(wAvg);

  // shares
  const totalShares = Array.from(document.querySelectorAll('#shareholderRows .shareholder-row'))
    .reduce((t, row) => t + (parseInt(row.querySelectorAll('input')[1]?.value) || 0), 0);
  const pricePerShare = totalShares > 0 ? valMid / totalShares : 0;

  const tbody = document.getElementById('r_shareBody');
  tbody.innerHTML = '';
  document.querySelectorAll('#shareholderRows .shareholder-row').forEach(row => {
    const inputs = row.querySelectorAll('input');
    const name = inputs[0]?.value || '—';
    const shares = parseInt(inputs[1]?.value) || 0;
    const cls = row.querySelector('select')?.value || '—';
    const val = pricePerShare * shares;
    const tr = document.createElement('tr');
    tr.innerHTML = `<td>${name}</td><td>${cls}</td><td>${shares.toLocaleString()}</td><td>${fmt(val)}</td>`;
    tbody.appendChild(tr);
  });

  // Automatic generation removed - manual button only
}

// ── SAVE TO DB ──
async function saveValuation() {
  const btn = document.getElementById('saveBtn');
  
  // Gather all data
  const data = {
    companyName: document.getElementById('companyName')?.value,
    companyNumber: document.getElementById('companyNumber')?.value,
    sector: document.getElementById('sector')?.value,
    yearEnd: document.getElementById('yearEnd')?.value,
    yearsTrading: document.getElementById('yearsTrading')?.value,
    employees: document.getElementById('employees')?.value,
    purpose: document.getElementById('purpose')?.value,
    reportDate: document.getElementById('reportDate')?.value,
    businessDesc: document.getElementById('businessDesc')?.value,
    
    // Financials
    financials: {
      turnover: [getNum('f_turn1'), getNum('f_turn2'), getNum('f_turn3')],
      cos: [getNum('f_cos1'), getNum('f_cos2'), getNum('f_cos3')],
      admin: [getNum('f_admin1'), getNum('f_admin2'), getNum('f_admin3')],
      other: [getNum('f_other1'), getNum('f_other2'), getNum('f_other3')],
      depreciation: [getNum('f_dep1'), getNum('f_dep2'), getNum('f_dep3')],
      balanceSheet: {
        netAssets: getNum('b_netassets'),
        cash: getNum('b_cash'),
        debtors: getNum('b_debtors'),
        loans: getNum('b_loans')
      }
    },
    
    // Adjustments
    adjustments: Array.from(document.querySelectorAll('#adjRows .adj-row')).map(row => {
      const inputs = row.querySelectorAll('input');
      return {
        label: inputs[0].value,
        v1: parseFloat(inputs[1].value) || 0,
        v2: parseFloat(inputs[2].value) || 0,
        v3: parseFloat(inputs[3].value) || 0,
        note: inputs[4].value
      };
    }),
    
    // Shareholders
    shareholders: Array.from(document.querySelectorAll('#shareholderRows .shareholder-row')).map(row => {
      const inputs = row.querySelectorAll('input');
      return {
        name: inputs[0].value,
        shares: parseInt(inputs[1].value) || 0,
        class: row.querySelector('select').value
      };
    }),
    
    // Methodology
    weighting: weighting,
    multiples: {
      low: getNum('multLow'),
      mid: getNum('multMid'),
      high: getNum('multHigh')
    },
    deduction: getNum('deduction'),
    deductionDesc: document.getElementById('deductionDesc').value,
    
    // Narratives
    accountantNotes: document.getElementById('accountantNotes').value,
    aiNarrative: document.getElementById('r_narrative').value,
    
    // Result
    valuationMid: parseFloat(document.getElementById('r_mid').textContent.replace(/[£,mk]/g, '')) * (document.getElementById('r_mid').textContent.includes('m') ? 1000000 : (document.getElementById('r_mid').textContent.includes('k') ? 1000 : 1))
  };

  btn.disabled = true;
  btn.innerHTML = '<span class="spinner"></span> Saving…';

  try {
    const response = await fetch('save-valuation.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(data)
    });

    const result = await response.json();
    if (result.error) throw new Error(result.error);
    
    showStatus('Valuation saved to ELK Database ✓');
    btn.innerHTML = '💾 Saved';
    document.getElementById('saveInfo').style.display = 'block';
    document.getElementById('saveTime').textContent = new Date().toLocaleTimeString();
    setTimeout(() => {
      btn.innerHTML = '💾 Save Valuation';
      btn.disabled = false;
    }, 3000);
  } catch (err) {
    showStatus('Save failed: ' + err.message);
    btn.disabled = false;
    btn.innerHTML = '💾 Save Valuation';
  }
}

// ── FORMAT ──
function fmt(n) {
  if (isNaN(n) || n === null) return '—';
  const abs = Math.abs(n);
  const str = abs.toLocaleString('en-GB', { maximumFractionDigits: 0 });
  return (n < 0 ? '-£' : '£') + str;
}

function fmtShort(n) {
  if (isNaN(n) || !n) return '—';
  if (Math.abs(n) >= 1000000) return '£' + (n / 1000000).toFixed(2) + 'm';
  if (Math.abs(n) >= 1000) return '£' + (n / 1000).toFixed(0) + 'k';
  return fmt(n);
}

// ── STATUS ──
let statusTimer;
function showStatus(msg) {
  const bar = document.getElementById('statusBar');
  document.getElementById('statusMsg').textContent = msg;
  bar.classList.add('show');
  clearTimeout(statusTimer);
  statusTimer = setTimeout(() => bar.classList.remove('show'), 4000);
}

// ── AI NARRATIVE ──
async function generateNarrative(targetId = 'r_narrative') {
  const btnId = targetId === 'accountantNotes' ? 'aiMethodBtn' : 'aiNarrativeBtn';
  const btn = document.getElementById(btnId);
  const textarea = document.getElementById(targetId);
  
  // Build prompt from all current data
  const company   = document.getElementById('companyName')?.value || 'the company';
  const sector    = document.getElementById('sector')?.value || 'unspecified sector';
  const purpose   = document.getElementById('purpose')?.value || 'general advisory';
  const employees = document.getElementById('employees')?.value || 'unknown';
  const years     = document.getElementById('yearsTrading')?.value || 'unknown';
  const desc      = document.getElementById('businessDesc')?.value || '';
  const notes     = document.getElementById('accountantNotes')?.value || '';

  const e1 = getAdjEbitda(1), e2 = getAdjEbitda(2), e3 = getAdjEbitda(3);
  const [w1,w2,w3] = weighting;
  const wAvg = (e1*w1 + e2*w2 + e3*w3) / (w1+w2+w3);
  const turn3    = getNum('f_turn3');
  const multLow  = getNum('multLow') || 2.5;
  const multMid  = getNum('multMid') || 3.5;
  const multHigh = getNum('multHigh') || 5;
  
  const cash = getNum('b_cash');
  const loans = getNum('b_loans');
  const netDebt = loans - cash;
  const deduction = getNum('deduction');
  const deductDesc = document.getElementById('deductionDesc')?.value || '';
  
  const valLow  = (wAvg * multLow) - netDebt - deduction;
  const valMid  = (wAvg * multMid) - netDebt - deduction;
  const valHigh = (wAvg * multHigh) - netDebt - deduction;
  
  const margin = turn3 ? ((getPreAdjEbitda(3) / turn3) * 100).toFixed(1) : 'unknown';

  const prompt = `Write a comprehensive professional business valuation commentary. Use the following data:

Company: ${company}
Sector: ${sector}
Purpose: ${purpose}
Years trading: ${years}
Employees: ${employees}
Description: ${desc}

Financials (3 yrs):
- EBITDA: ${fmt(e1)} (Y1), ${fmt(e2)} (Y2), ${fmt(e3)} (Y3)
- Weighted Avg: ${fmt(wAvg)}
- Margin: ${margin}%
- Net Debt: ${fmt(netDebt)} (Loans: ${fmt(loans)}, Cash: ${fmt(cash)})

Valuation:
- Multiples: ${multLow}x - ${multHigh}x (Mid: ${multMid}x)
- Equity Value Range: ${fmtShort(valLow)} to ${fmtShort(valHigh)}
- Other Deductions: ${fmt(deduction)} (${deductDesc})

Write 4-5 detailed paragraphs. Analyze the growth trends, discuss the balance sheet strength (especially the net cash/debt position), justify the EBITDA multiple for this sector, and provide a professional conclusion. No bullet points or headers.`;

  // Show loading state
  btn.disabled = true;
  const originalHtml = btn.innerHTML;
  btn.innerHTML = '<span class="spinner"></span> Generating…';
  textarea.value = 'Generating professional AI commentary...';

  try {
    const response = await fetch('vertex-proxy.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ prompt })
    });

    const data = await response.json();
    if (data.error) throw new Error(data.error);
    
    textarea.value = data.narrative;
    textarea.dataset.aiGenerated = '1';
    showStatus('AI commentary generated ✓');
  } catch (err) {
    textarea.value = 'Error: ' + err.message;
  } finally {
    btn.disabled = false;
    btn.innerHTML = originalHtml;
  }
}

// ── INIT ──
function init() {
  // Default adjustment rows based on View HR pattern
  addAdjRow('Director salary adjustment', '', '', '');
  addAdjRow('Director pension addback', '', '', '');
  addAdjRow('Non-recurring / one-off costs', '', '', '');

  // Default shareholders
  addShareholderRow('', '', 'Ordinary');

  // Default weighting
  document.getElementById('w123').classList.add('selected');

  // Set default report date
  const now = new Date();
  document.getElementById('reportDate').value = now.toLocaleDateString('en-GB', { month: 'long', year: 'numeric' });
}

init();
</script>

  <footer style="
    background: #0d0d18;
    border-top: 1px solid rgba(102,14,12,0.25);
    padding: 16px 40px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-family: 'Barlow', sans-serif;
    font-size: 11px;
    color: rgba(237,237,240,0.3);
    letter-spacing: 0.04em;
  ">
    <span>&copy; 2026 GTA Accounting (Garrett Adam Accountants Ltd). All rights reserved.</span>
    <span>Design &amp; Development by <a href="https://elkdesignservices.com" target="_blank" style="color:rgba(237,237,240,0.5); text-decoration:none; border-bottom:1px solid rgba(102,14,12,0.4);">ELK Digital</a> &mdash; elkdesignservices.com <span style="margin-left:8px; opacity:0.5;">
      <?php 
        $version = getenv('APP_VERSION') ?: '3.1.x';
        $buildTime = getenv('BUILD_TIME') ?: date('j M Y H:i');
        echo "v{$version} (Built: {$buildTime})"; 
      ?>
    </span></span>
  </footer>
</body>
</html>
