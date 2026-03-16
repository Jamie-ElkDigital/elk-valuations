<?php
/**
 * ELK Valuations - Theme Engine
 * Dynamically generates CSS variables based on firm branding.
 */

function getLuminance($hex) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
    }
    $r = hexdec(substr($hex, 0, 2)) / 255;
    $g = hexdec(substr($hex, 2, 2)) / 255;
    $b = hexdec(substr($hex, 4, 2)) / 255;
    return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
}

function adjustBrightness($hex, $steps) {
    $steps = max(-255, min(255, $steps));
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $hex = str_repeat(substr($hex, 0, 1), 2) . str_repeat(substr($hex, 1, 1), 2) . str_repeat(substr($hex, 2, 1), 2);
    }
    $r = hexdec(substr($hex, 0, 2));
    $g = hexdec(substr($hex, 2, 2));
    $b = hexdec(substr($hex, 4, 2));
    $r = max(0, min(255, $r + $steps));
    $g = max(0, min(255, $g + $steps));
    $b = max(0, min(255, $b + $steps));
    return '#' . str_pad(dechex($r), 2, '0', STR_PAD_LEFT) . str_pad(dechex($g), 2, '0', STR_PAD_LEFT) . str_pad(dechex($b), 2, '0', STR_PAD_LEFT);
}

function injectTheme($primary, $secondary, $isReport = false) {
    // If it's a report, we force a light, print-friendly background regardless of firm setting
    if ($isReport) {
        $secondary = '#ffffff';
    }

    $isLight = getLuminance($secondary) > 0.5;
    
    // Derived Surfaces
    $surface_mid = $isLight ? '#f8fafc' : adjustBrightness($secondary, 15);
    $surface_light = $isLight ? '#f1f5f9' : adjustBrightness($secondary, 30);
    
    // Text Colors
    $text_main = $isLight ? '#0f172a' : '#ffffff';
    $text_muted = $isLight ? '#475569' : '#d1d1d6';
    $text_faint = $isLight ? '#94a3b8' : '#71717a';
    
    // Functional Neutrals
    $bg_dim = $isLight ? 'rgba(15, 23, 42, 0.03)' : 'rgba(255, 255, 255, 0.05)';
    $border_subtle = $isLight ? 'rgba(15, 23, 42, 0.08)' : 'rgba(255, 255, 255, 0.08)';
    $input_bg = $isLight ? '#ffffff' : '#000000';
    $input_border = $isLight ? '#e2e8f0' : '#27272a';
    
    // Accent Variations
    $accent_light = adjustBrightness($primary, 20);
    $accent_dim = $isLight ? hexToRgba($primary, 0.1) : hexToRgba($primary, 0.15);
    $accent_border = hexToRgba($primary, 0.3);
    $accent_glow = hexToRgba($primary, 0.2);
    
    echo "
    <style>
    :root {
      --brand-surface: $secondary;
      --brand-surface-mid: $surface_mid;
      --brand-surface-light: $surface_light;
      --brand-accent: $primary;
      --brand-accent-light: $accent_light;
      --brand-accent-hover: " . adjustBrightness($primary, -20) . ";
      --brand-accent-dim: $accent_dim;
      --brand-accent-border: $accent_border;
      --brand-accent-glow: $accent_glow;
      --text-main: $text_main;
      --text-muted: $text_muted;
      --text-faint: $text_faint;
      --bg-dim: $bg_dim;
      --border-subtle: $border_subtle;
      --input-bg: $input_bg;
      --input-border: $input_border;
      
      /* Typography */
      --font-primary: 'Open Sans', sans-serif;
      --title-font: 'Open Sans', sans-serif;
      --secondary-title-font: 'Open Sans', sans-serif;
    }
    
    /* Global font refinements */
    body, input, select, textarea, button {
        font-family: var(--font-primary);
        letter-spacing: -0.01em;
    }
    h1, h2, h3, .page-title {
        font-family: var(--title-font);
        font-weight: 700;
        letter-spacing: -0.02em;
    }
    </style>";
}

function hexToRgba($hex, $alpha) {
    $hex = str_replace('#', '', $hex);
    if (strlen($hex) == 3) {
        $r = hexdec(substr($hex,0,1).substr($hex,0,1));
        $g = hexdec(substr($hex,1,1).substr($hex,1,1));
        $b = hexdec(substr($hex,2,1).substr($hex,2,1));
    } else {
        $r = hexdec(substr($hex,0,2));
        $g = hexdec(substr($hex,2,2));
        $b = hexdec(substr($hex,4,2));
    }
    return "rgba($r, $g, $b, $alpha)";
}
