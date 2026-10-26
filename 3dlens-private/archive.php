<?php
if (!defined('THREEDLENS_BOOTSTRAPPED')) { http_response_code(404); exit; }
require_once __DIR__ . '/release.php';
?>
<!DOCTYPE html>
<html lang="nl">
<head>
<meta charset="UTF-8" />
<meta name="robots" content="noindex,nofollow" />
<meta name="app-version" content="<?= h(app_release()['version']) ?>" />
<meta name="csrf-token" content="<?= h($csrf) ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>3DLens — STL · 3MF · SLA Archief</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=JetBrains+Mono:wght@300;400;500;700&family=Syne:wght@500;700;800&display=swap" rel="stylesheet">
<script type="importmap">
{
  "imports": {
    "three": "https://cdn.jsdelivr.net/npm/three@0.160.0/build/three.module.js",
    "three/addons/": "https://cdn.jsdelivr.net/npm/three@0.160.0/examples/jsm/",
    "fflate": "https://cdn.jsdelivr.net/npm/fflate@0.8.2/esm/browser.js"
  }
}
</script>
<style>
  :root {
    --bg: #0d0e0c;
    --bg-elev: #16181410;
    --panel: #1a1c19;
    --panel-2: #22241f;
    --line: #2d2f2a;
    --line-bright: #3d3f38;
    --ink: #e8e6df;
    --ink-dim: #b4b6ac;
    --ink-faint: #898b81;
    --accent: #d4ff3a;
    --accent-dim: #a8cc2a;
    --warn: #ff5c2a;
    --grid: rgba(255, 255, 255, 0.025);
  }

  * { box-sizing: border-box; margin: 0; padding: 0; }

  html, body {
    height: 100%;
    background: var(--bg);
    color: var(--ink);
    font-family: 'JetBrains Mono', monospace;
    font-size: 13px;
    overflow: hidden;
    -webkit-font-smoothing: antialiased;
  }

  body::before {
    content: '';
    position: fixed;
    inset: 0;
    background-image:
      linear-gradient(var(--grid) 1px, transparent 1px),
      linear-gradient(90deg, var(--grid) 1px, transparent 1px);
    background-size: 32px 32px;
    pointer-events: none;
    z-index: 0;
  }

  .app {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: 280px 1fr 380px;
    grid-template-rows: 56px 1fr 28px;
    height: 100vh;
    gap: 0;
  }

  /* HEADER */
  .header {
    grid-column: 1 / -1;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
    border-bottom: 1px solid var(--line);
    background: var(--panel);
  }

  .brand {
    display: flex;
    align-items: center;
    gap: 14px;
  }

  .logo-mark {
    width: 28px;
    height: 28px;
    border: 1.5px solid var(--accent);
    border-radius: 50%;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 0 0 2px var(--bg), 0 0 0 3px var(--accent);
  }

  .logo-mark::before {
    content: '';
    position: absolute;
    inset: 4px;
    border: 1px solid var(--accent);
    border-radius: 50%;
    opacity: 0.4;
  }

  .logo-mark::after {
    content: '';
    width: 6px;
    height: 6px;
    background: var(--accent);
    border-radius: 50%;
  }

  .brand-name {
    font-family: 'Syne', sans-serif;
    font-weight: 800;
    font-size: 22px;
    letter-spacing: 0.02em;
  }

  .brand-sub {
    color: var(--ink-faint);
    font-size: 11px;
    letter-spacing: 0.15em;
    text-transform: uppercase;
    border-left: 1px solid var(--line-bright);
    padding-left: 14px;
  }

  .header-actions {
    display: flex;
    gap: 8px;
    align-items: center;
  }

  .btn {
    background: transparent;
    border: 1px solid var(--line-bright);
    color: var(--ink);
    font-family: inherit;
    font-size: 11px;
    padding: 8px 14px;
    cursor: pointer;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 8px;
  }

  .btn:hover {
    border-color: var(--accent);
    color: var(--accent);
  }

  .btn-primary {
    background: var(--accent);
    color: var(--bg);
    border-color: var(--accent);
    font-weight: 700;
  }

  .btn-primary:hover {
    background: var(--accent-dim);
    border-color: var(--accent-dim);
    color: var(--bg);
  }

  /* SIDEBAR */
  .sidebar {
    border-right: 1px solid var(--line);
    background: var(--panel);
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }

  .sidebar-section {
    padding: 18px 20px 12px;
    border-bottom: 1px solid var(--line);
  }

  .section-label {
    color: var(--ink-faint);
    font-size: 10px;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    margin-bottom: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .section-label .count {
    color: var(--accent);
    font-weight: 700;
  }

  .new-collection {
    width: 100%;
    background: transparent;
    border: 1px dashed var(--line-bright);
    color: var(--ink-dim);
    padding: 10px;
    font-family: inherit;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.1em;
    cursor: pointer;
    transition: all 0.15s ease;
  }

  .new-collection:hover {
    border-color: var(--accent);
    color: var(--accent);
    border-style: solid;
  }

  .collections {
    flex: 1;
    overflow-y: auto;
    padding: 4px 0;
  }

  .collection-item {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 11px 20px;
    cursor: pointer;
    border-left: 2px solid transparent;
    transition: all 0.12s ease;
    position: relative;
  }

  .collection-item:hover {
    background: var(--panel-2);
  }

  .collection-item.active {
    background: var(--panel-2);
    border-left-color: var(--accent);
  }

  .collection-item.active .col-name {
    color: var(--accent);
  }

  .col-info {
    display: flex;
    align-items: center;
    gap: 10px;
    min-width: 0;
    flex: 1;
  }

  .col-icon {
    width: 14px;
    height: 14px;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .col-icon svg {
    width: 100%;
    height: 100%;
    stroke: currentColor;
    fill: none;
    stroke-width: 1.5;
  }

  .col-name {
    font-size: 12px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .col-count {
    font-size: 10px;
    color: var(--ink-faint);
    background: var(--bg);
    padding: 2px 7px;
    border: 1px solid var(--line);
    flex-shrink: 0;
  }

  .col-actions {
    display: flex;
    align-items: center;
    gap: 2px;
    margin-left: 4px;
  }

  .col-edit, .col-delete {
    opacity: 0;
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 4px;
    transition: opacity 0.15s, color 0.15s;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .col-edit {
    color: var(--ink-dim);
  }

  .col-delete {
    color: var(--warn);
    font-size: 14px;
  }

  .collection-item:hover .col-edit,
  .collection-item:hover .col-delete {
    opacity: 0.7;
  }

  .col-edit:hover {
    opacity: 1 !important;
    color: var(--accent);
  }

  .col-delete:hover {
    opacity: 1 !important;
  }

  .col-edit svg {
    width: 12px;
    height: 12px;
    stroke: currentColor;
    fill: none;
    stroke-width: 2;
  }

  .col-name-input {
    background: var(--bg);
    border: 1px solid var(--accent);
    color: var(--ink);
    font-family: inherit;
    font-size: 12px;
    padding: 2px 6px;
    width: 100%;
    outline: none;
  }

  /* MAIN */
  .main {
    overflow: hidden;
    display: flex;
    flex-direction: column;
    background: var(--bg);
  }

  .main-toolbar {
    height: 48px;
    border-bottom: 1px solid var(--line);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 24px;
    background: var(--panel);
  }

  .breadcrumb {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 12px;
  }

  .breadcrumb-current {
    font-family: 'Syne', sans-serif;
    font-weight: 700;
    font-size: 16px;
    letter-spacing: 0.01em;
  }

  .breadcrumb-meta {
    color: var(--ink-faint);
    font-size: 11px;
    letter-spacing: 0.1em;
    text-transform: uppercase;
  }

  .toolbar-right {
    display: flex;
    gap: 6px;
    align-items: center;
  }

  .search {
    background: var(--bg);
    border: 1px solid var(--line);
    padding: 7px 12px;
    color: var(--ink);
    font-family: inherit;
    font-size: 12px;
    width: 200px;
    transition: border-color 0.15s;
  }

  .search:focus {
    outline: none;
    border-color: var(--accent);
  }

  .search::placeholder {
    color: var(--ink-faint);
  }

  /* GRID */
  .bulk-toolbar { display:flex; flex-wrap:wrap; gap:10px; align-items:center; padding:10px 24px; background:var(--panel); border-bottom:1px solid var(--line); font-size:11px; }
  .bulk-toolbar label { display:flex; gap:7px; align-items:center; cursor:pointer; }
  .bulk-toolbar select { max-width:220px; padding:7px; color:var(--ink); background:var(--bg); border:1px solid var(--line-bright); font:inherit; }
  .bulk-toolbar input, .card-check { accent-color:var(--accent); }
  .bulk-toolbar button:disabled, .bulk-toolbar select:disabled { opacity:.4; cursor:default; }
  .bulk-hint { color:var(--ink-faint); font-size:10px; }
  .card.bulk-selected { outline:2px solid var(--accent); outline-offset:-2px; }
  .card-select { position:absolute; z-index:5; right:8px; top:8px; display:flex; padding:6px; background:var(--panel); border:1px solid var(--line-bright); cursor:pointer; }
  .card-check { width:17px; height:17px; margin:0; cursor:pointer; }
  .grid-container {
    flex: 1;
    overflow-y: auto;
    padding: 24px;
  }

  .grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 16px;
  }

  .empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    height: 100%;
    color: var(--ink-faint);
    text-align: center;
    padding: 40px;
  }

  .empty-icon {
    font-family: 'Syne', sans-serif;
    font-size: 80px;
    font-weight: 800;
    color: var(--line-bright);
    letter-spacing: -0.05em;
    margin-bottom: 16px;
  }

  .empty-title {
    font-family: 'Syne', sans-serif;
    font-size: 18px;
    color: var(--ink);
    margin-bottom: 8px;
  }

  .empty-text {
    font-size: 12px;
    line-height: 1.6;
    max-width: 320px;
  }

  .empty .btn {
    margin-top: 20px;
  }

  /* CARD */
  .card {
    background: var(--panel);
    border: 1px solid var(--line);
    cursor: pointer;
    transition: all 0.18s ease;
    position: relative;
    overflow: hidden;
  }

  .card:hover {
    border-color: var(--line-bright);
    transform: translateY(-2px);
  }

  .card.selected {
    border-color: var(--accent);
  }

  .card.selected::after {
    content: '';
    position: absolute;
    inset: 0;
    border: 1px solid var(--accent);
    pointer-events: none;
  }

  .card-preview {
    aspect-ratio: 1;
    background:
      radial-gradient(circle at 50% 40%, #1f221d 0%, #0a0b09 100%);
    position: relative;
    overflow: hidden;
  }

  .card-preview::after {
    content: 'Klik voor snelle preview';
    position: absolute;
    left: 8px;
    right: 8px;
    bottom: 8px;
    padding: 6px 8px;
    background: rgba(13, 14, 12, 0.82);
    border: 1px solid var(--line-bright);
    color: var(--accent);
    font-size: 9px;
    letter-spacing: 0.12em;
    text-transform: uppercase;
    text-align: center;
    opacity: 0;
    transform: translateY(4px);
    transition: opacity 0.15s ease, transform 0.15s ease;
    pointer-events: none;
    backdrop-filter: blur(6px);
  }

  .card-preview:hover::after {
    opacity: 1;
    transform: translateY(0);
  }

  .card-preview {
    cursor: zoom-in;
  }


  .card-preview canvas {
    width: 100%;
    height: 100%;
    display: block;
  }

  .card-loading {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--ink-faint);
    font-size: 10px;
    letter-spacing: 0.1em;
    text-transform: uppercase;
  }

  .card-placeholder {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 8px;
    color: var(--ink-faint);
    font-size: 9px;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    text-align: center;
    padding: 12px;
    transition: color 0.15s;
  }

  .card:hover .card-placeholder {
    color: var(--accent);
  }

  .card-placeholder svg {
    opacity: 0.5;
    transition: opacity 0.15s;
  }

  .card:hover .card-placeholder svg {
    opacity: 1;
  }

  .card-placeholder-failed {
    color: var(--warn);
    opacity: 0.8;
  }

  .card:hover .card-placeholder-failed {
    color: var(--warn);
  }

  .card-placeholder-failed svg {
    opacity: 0.7;
  }

  .card-loading::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 0;
    height: 1px;
    width: 100%;
    background: linear-gradient(90deg, transparent, var(--accent), transparent);
    animation: scan 1.5s infinite;
  }

  @keyframes scan {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
  }

  .card-info {
    padding: 12px 14px;
    border-top: 1px solid var(--line);
  }

  .card-name {
    font-size: 12px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    margin-bottom: 4px;
  }

  .card-meta {
    display: flex;
    justify-content: space-between;
    color: var(--ink-faint);
    font-size: 10px;
    letter-spacing: 0.05em;
  }

  .card-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    background: var(--bg);
    border: 1px solid var(--line-bright);
    padding: 3px 7px;
    font-size: 9px;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    color: var(--ink-dim);
  }

  /* INSPECTOR */
  .inspector {
    border-left: 1px solid var(--line);
    background: var(--panel);
    overflow: hidden;
    display: flex;
    flex-direction: column;
  }

  .inspector-empty {
    flex: 1;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 40px;
    color: var(--ink-faint);
    text-align: center;
  }

  .inspector-empty-mark {
    width: 60px;
    height: 60px;
    border: 1px dashed var(--line-bright);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--line-bright);
    font-size: 24px;
  }

  .inspector-content {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
  }

  .inspector-viewer {
    aspect-ratio: 1;
    background:
      radial-gradient(circle at 50% 40%, #1f221d 0%, #0a0b09 100%);
    position: relative;
    overflow: hidden;
    border-bottom: 1px solid var(--line);
  }

  .inspector-viewer canvas {
    width: 100%;
    height: 100%;
    display: block;
  }

  .viewer-controls {
    position: absolute;
    bottom: 10px;
    left: 10px;
    display: flex;
    gap: 4px;
  }

  .viewer-btn {
    width: 28px;
    height: 28px;
    background: rgba(13, 14, 12, 0.8);
    border: 1px solid var(--line);
    color: var(--ink-dim);
    cursor: pointer;
    font-family: inherit;
    font-size: 11px;
    transition: all 0.15s;
    backdrop-filter: blur(8px);
  }

  .viewer-btn:hover {
    border-color: var(--accent);
    color: var(--accent);
  }

  .viewer-btn.active {
    border-color: var(--accent);
    color: var(--accent);
  }

  .viewer-btn-wide {
    width: auto;
    padding: 0 9px;
    letter-spacing: 0.08em;
    text-transform: uppercase;
  }

  .preview-modal {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.84);
    backdrop-filter: blur(6px);
    z-index: 500;
    display: none;
    padding: 24px;
  }

  .preview-modal.show {
    display: grid;
    grid-template-rows: 48px 1fr;
    gap: 12px;
  }

  .preview-modal-header {
    background: var(--panel);
    border: 1px solid var(--line-bright);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 14px 0 18px;
  }

  .preview-modal-title {
    min-width: 0;
  }

  .preview-modal-kicker {
    color: var(--accent);
    font-size: 10px;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    margin-bottom: 2px;
  }

  .preview-modal-name {
    color: var(--ink);
    font-family: 'Syne', sans-serif;
    font-size: 18px;
    font-weight: 700;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    max-width: min(72vw, 980px);
  }

  .preview-modal-actions {
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .preview-modal-viewer {
    position: relative;
    overflow: hidden;
    border: 1px solid var(--line-bright);
    background:
      radial-gradient(circle at 50% 40%, #1f221d 0%, #0a0b09 100%);
  }

  .preview-modal-viewer canvas {
    width: 100%;
    height: 100%;
    display: block;
  }

  .preview-modal-hint {
    position: absolute;
    right: 14px;
    bottom: 12px;
    color: var(--ink-faint);
    font-size: 10px;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    background: rgba(13, 14, 12, 0.72);
    border: 1px solid var(--line);
    padding: 6px 9px;
  }


  .viewer-corners {
    position: absolute;
    inset: 8px;
    pointer-events: none;
  }

  .viewer-loading {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    background: rgba(13, 14, 12, 0.85);
    backdrop-filter: blur(2px);
    z-index: 5;
    pointer-events: none;
    opacity: 0;
    transition: opacity 0.15s;
  }

  .viewer-loading.show {
    opacity: 1;
  }

  .viewer-error {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    background: rgba(13, 14, 12, 0.92);
    backdrop-filter: blur(2px);
    z-index: 4;
    padding: 24px;
    text-align: center;
  }

  .viewer-error-icon {
    width: 36px;
    height: 36px;
    border: 1px solid var(--warn);
    color: var(--warn);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    font-weight: 700;
  }

  .viewer-error-title {
    color: var(--warn);
    font-size: 11px;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    font-weight: 700;
  }

  .viewer-error-text {
    color: var(--ink-dim);
    font-size: 11px;
    line-height: 1.5;
    max-width: 280px;
  }

  .viewer-prompt {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 12px;
    background: rgba(13, 14, 12, 0.9);
    backdrop-filter: blur(2px);
    z-index: 4;
    padding: 24px;
    text-align: center;
  }

  .viewer-prompt-icon {
    opacity: 0.85;
    margin-bottom: 4px;
  }

  .viewer-prompt-title {
    color: var(--accent);
    font-size: 11px;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    font-weight: 700;
  }

  .viewer-prompt-text {
    color: var(--ink-dim);
    font-size: 11px;
    line-height: 1.5;
    max-width: 280px;
  }

  /* SLA/resin preview overlay — replaces the 3D viewer for sliced resin files */
  .viewer-sla {
    position: absolute;
    inset: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 14px;
    background: var(--bg);
    z-index: 4;
    padding: 18px;
    overflow: auto;
  }

  .viewer-sla-image {
    max-width: 100%;
    max-height: 60%;
    object-fit: contain;
    border: 1px solid var(--line-bright);
    background:
      linear-gradient(45deg, #1a1c19 25%, transparent 25%),
      linear-gradient(-45deg, #1a1c19 25%, transparent 25%),
      linear-gradient(45deg, transparent 75%, #1a1c19 75%),
      linear-gradient(-45deg, transparent 75%, #1a1c19 75%);
    background-size: 14px 14px;
    background-position: 0 0, 0 7px, 7px -7px, -7px 0;
    image-rendering: pixelated;
  }

  .viewer-sla-noimage {
    width: 220px;
    height: 220px;
    border: 1px dashed var(--line-bright);
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--ink-faint);
    font-size: 10px;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    text-align: center;
    padding: 20px;
  }

  .viewer-sla-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 6px 12px;
    border: 1px solid var(--accent);
    color: var(--accent);
    font-size: 10px;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    font-weight: 700;
  }

  .viewer-sla-badge::before {
    content: '';
    width: 6px;
    height: 6px;
    background: var(--accent);
    border-radius: 50%;
    box-shadow: 0 0 8px var(--accent);
  }

  .viewer-sla-meta {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 6px 18px;
    width: 100%;
    max-width: 380px;
    font-size: 11px;
  }

  .viewer-sla-meta-row {
    display: flex;
    justify-content: space-between;
    gap: 8px;
    border-bottom: 1px dotted var(--line);
    padding: 3px 0;
  }

  .viewer-sla-meta-key {
    color: var(--ink-faint);
    text-transform: uppercase;
    letter-spacing: 0.08em;
    font-size: 9px;
  }

  .viewer-sla-meta-val {
    color: var(--ink);
    font-weight: 500;
    text-align: right;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .viewer-loading-text {
    font-size: 10px;
    color: var(--accent);
    letter-spacing: 0.18em;
    text-transform: uppercase;
  }

  .viewer-loading-spinner {
    width: 28px;
    height: 28px;
    border: 1px solid var(--line);
    border-top-color: var(--accent);
    border-right-color: var(--accent);
    border-radius: 50%;
    animation: viewerSpin 0.8s linear infinite;
  }

  @keyframes viewerSpin {
    to { transform: rotate(360deg); }
  }

  .viewer-corners::before,
  .viewer-corners::after {
    content: '';
    position: absolute;
    width: 12px;
    height: 12px;
    border-color: var(--accent);
    border-style: solid;
  }

  .viewer-corners::before {
    top: 0;
    left: 0;
    border-width: 1px 0 0 1px;
  }

  .viewer-corners::after {
    bottom: 0;
    right: 0;
    border-width: 0 1px 1px 0;
  }

  .inspector-details {
    flex: 1;
    overflow-y: auto;
    padding: 18px 20px;
  }

  .detail-name {
    font-family: 'Syne', sans-serif;
    font-size: 18px;
    font-weight: 700;
    margin-bottom: 4px;
    word-break: break-word;
    line-height: 1.2;
  }

  .detail-id {
    font-size: 10px;
    color: var(--ink-faint);
    letter-spacing: 0.1em;
    margin-bottom: 18px;
  }

  .detail-row {
    display: grid;
    grid-template-columns: 110px 1fr;
    padding: 9px 0;
    border-bottom: 1px solid var(--line);
    font-size: 11px;
  }

  .detail-row:last-child {
    border-bottom: none;
  }

  .detail-key {
    color: var(--ink-faint);
    text-transform: uppercase;
    letter-spacing: 0.1em;
    font-size: 10px;
  }

  .detail-val {
    color: var(--ink);
    text-align: right;
    word-break: break-word;
  }

  .detail-val.accent {
    color: var(--accent);
    font-weight: 500;
  }

  .inspector-actions {
    border-top: 1px solid var(--line);
    padding: 14px 20px;
    display: flex;
    flex-direction: column;
    gap: 8px;
  }

  .move-select {
    width: 100%;
    background: var(--bg);
    border: 1px solid var(--line);
    color: var(--ink);
    padding: 8px 10px;
    font-family: inherit;
    font-size: 11px;
    cursor: pointer;
  }

  .move-select:focus {
    outline: none;
    border-color: var(--accent);
  }

  /* DESCRIPTION EDITOR */
  .description-block {
    margin-top: 20px;
    padding-top: 18px;
    border-top: 1px solid var(--line);
  }

  .description-label {
    display: flex;
    justify-content: space-between;
    align-items: center;
    color: var(--ink-faint);
    font-size: 10px;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    margin-bottom: 10px;
  }

  .description-label .char-count {
    color: var(--ink-faint);
    font-size: 10px;
    letter-spacing: 0.05em;
    text-transform: none;
  }

  .description-label .char-count.dirty {
    color: var(--warn);
  }

  .description-textarea {
    width: 100%;
    background: var(--bg);
    border: 1px solid var(--line);
    color: var(--ink);
    font-family: inherit;
    font-size: 12px;
    line-height: 1.6;
    padding: 10px 12px;
    resize: vertical;
    min-height: 80px;
    max-height: 200px;
    transition: border-color 0.15s;
  }

  .description-textarea:focus {
    outline: none;
    border-color: var(--accent);
  }

  .description-textarea::placeholder {
    color: var(--ink-faint);
    font-style: italic;
  }

  .description-actions {
    display: flex;
    gap: 6px;
    margin-top: 8px;
    justify-content: flex-end;
    min-height: 28px;
  }

  .description-actions .btn {
    padding: 5px 10px;
    font-size: 10px;
  }

  .description-saved {
    color: var(--accent);
    font-size: 10px;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    align-self: center;
    opacity: 0;
    transition: opacity 0.3s;
  }

  .description-saved.show {
    opacity: 1;
  }


  /* USER TAGS */
  .tags-editor {
    margin-top: 20px;
    padding-top: 18px;
    border-top: 1px solid var(--line);
  }

  .tag-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 10px;
    min-height: 22px;
  }

  .tag-chip {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: rgba(212, 255, 58, 0.05);
    border: 1px solid var(--line-bright);
    color: var(--ink-dim);
    font-size: 10px;
    padding: 4px 8px;
    letter-spacing: 0.05em;
  }

  .tag-chip .tag-remove {
    background: transparent;
    border: none;
    color: var(--warn);
    cursor: pointer;
    font-family: inherit;
    font-size: 12px;
    line-height: 1;
    padding: 0;
  }

  .tag-input-row {
    display: flex;
    gap: 6px;
  }

  .tag-input {
    flex: 1;
    background: var(--bg);
    border: 1px solid var(--line);
    color: var(--ink);
    font-family: inherit;
    font-size: 12px;
    padding: 8px 10px;
  }

  .tag-input:focus {
    outline: none;
    border-color: var(--accent);
  }

  .tag-hint {
    color: var(--ink-faint);
    font-size: 10px;
    line-height: 1.5;
    margin-top: 8px;
  }

  .card-user-tags {
    display: flex;
    flex-wrap: wrap;
    gap: 4px;
    margin-top: 8px;
  }

  .card-user-tag {
    border: 1px solid var(--line);
    color: var(--ink-faint);
    font-size: 9px;
    padding: 2px 5px;
    max-width: 90px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }


  /* Card description preview */
  .card-desc-mark {
    position: absolute;
    top: 8px;
    right: 8px;
    background: var(--bg);
    border: 1px solid var(--accent);
    color: var(--accent);
    width: 18px;
    height: 18px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 700;
  }

  /* INSPECTOR TABS */
  .inspector-tabs {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 4px;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--line);
  }

  .inspector-tab {
    background: var(--bg);
    border: 1px solid var(--line);
    color: var(--ink-dim);
    font-family: inherit;
    font-size: 10px;
    padding: 8px 6px;
    cursor: pointer;
    text-transform: uppercase;
    letter-spacing: 0.12em;
    transition: all 0.15s ease;
  }

  .inspector-tab:hover {
    border-color: var(--line-bright);
    color: var(--ink);
  }

  .inspector-tab.active {
    border-color: var(--accent);
    color: var(--accent);
    background: rgba(212, 255, 58, 0.04);
  }

  .inspector-tab-panel {
    display: none;
  }

  .inspector-tab-panel.active {
    display: block;
  }

  .inspector-tab-panel[data-panel="preview"] {
    padding: 0;
  }

  .inspector-tab-panel[data-panel="preview"] .inspector-viewer {
    border-bottom: none;
  }

  /* MINI HOVER PREVIEW */
  /* STATUSBAR */
  .statusbar {
    grid-column: 1 / -1;
    background: var(--panel);
    border-top: 1px solid var(--line);
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0 20px;
    font-size: 10px;
    color: var(--ink-faint);
    letter-spacing: 0.1em;
    text-transform: uppercase;
  }

  .status-left, .status-right {
    display: flex;
    gap: 18px;
    align-items: center;
  }

  .status-dot {
    display: inline-block;
    width: 6px;
    height: 6px;
    background: var(--accent);
    border-radius: 50%;
    margin-right: 6px;
    box-shadow: 0 0 8px var(--accent);
    animation: pulse 2s infinite;
  }

  @keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.4; }
  }

  /* MODAL */
  .modal-backdrop {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(4px);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 100;
  }

  .modal-backdrop.show {
    display: flex;
  }

  .modal {
    background: var(--panel);
    border: 1px solid var(--line-bright);
    width: 420px;
    padding: 28px;
    position: relative;
  }

  .modal::before {
    content: '';
    position: absolute;
    top: -1px;
    left: -1px;
    width: 14px;
    height: 14px;
    border-top: 2px solid var(--accent);
    border-left: 2px solid var(--accent);
  }

  .modal::after {
    content: '';
    position: absolute;
    bottom: -1px;
    right: -1px;
    width: 14px;
    height: 14px;
    border-bottom: 2px solid var(--accent);
    border-right: 2px solid var(--accent);
  }

  .modal-title {
    font-family: 'Syne', sans-serif;
    font-size: 20px;
    font-weight: 700;
    margin-bottom: 18px;
  }

  .modal-input {
    width: 100%;
    background: var(--bg);
    border: 1px solid var(--line-bright);
    color: var(--ink);
    padding: 12px 14px;
    font-family: inherit;
    font-size: 13px;
    margin-bottom: 18px;
  }

  .modal-input:focus {
    outline: none;
    border-color: var(--accent);
  }

  .modal-actions {
    display: flex;
    gap: 8px;
    justify-content: flex-end;
  }

  /* SCROLLBAR */
  ::-webkit-scrollbar { width: 8px; height: 8px; }
  ::-webkit-scrollbar-track { background: var(--bg); }
  ::-webkit-scrollbar-thumb { background: var(--line-bright); }
  ::-webkit-scrollbar-thumb:hover { background: var(--ink-faint); }

  /* APP INITIAL LOADING */
  .app-loading {
    position: fixed;
    inset: 0;
    background: var(--bg);
    z-index: 1000;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 24px;
    pointer-events: all;
  }

  .app-loading.hide {
    opacity: 0;
    pointer-events: none;
    transition: opacity 0.3s ease-out;
  }

  .app-loading-logo {
    width: 56px;
    height: 56px;
    border: 2px solid var(--accent);
    border-radius: 50%;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    animation: appLoadPulse 1.5s ease-in-out infinite;
  }

  .app-loading-logo::before {
    content: '';
    position: absolute;
    inset: 8px;
    border: 1px solid var(--accent);
    border-radius: 50%;
    opacity: 0.4;
  }

  .app-loading-logo::after {
    content: '';
    width: 10px;
    height: 10px;
    background: var(--accent);
    border-radius: 50%;
  }

  @keyframes appLoadPulse {
    0%, 100% { transform: scale(1); filter: drop-shadow(0 0 12px rgba(212, 255, 58, 0.4)); }
    50% { transform: scale(1.06); filter: drop-shadow(0 0 24px rgba(212, 255, 58, 0.6)); }
  }

  .app-loading-name {
    font-family: 'Syne', sans-serif;
    font-size: 28px;
    font-weight: 800;
    letter-spacing: 0.01em;
    color: var(--ink);
  }

  .app-loading-text {
    font-size: 11px;
    color: var(--ink-dim);
    letter-spacing: 0.18em;
    text-transform: uppercase;
  }

  .app-loading-bar {
    width: 200px;
    height: 2px;
    background: var(--line);
    overflow: hidden;
    position: relative;
  }

  .app-loading-bar::after {
    content: '';
    position: absolute;
    height: 100%;
    width: 40%;
    background: var(--accent);
    animation: appLoadSlide 1.2s ease-in-out infinite;
  }

  @keyframes appLoadSlide {
    0% { left: -40%; }
    100% { left: 100%; }
  }

  /* SETTINGS DROPDOWN */
  .settings-picker {
    position: relative;
  }

  .settings-arrow {
    font-size: 8px;
    margin-left: 2px;
    transition: transform 0.15s;
  }

  #btn-settings.open .settings-arrow {
    transform: rotate(180deg);
  }

  #btn-settings.open {
    border-color: var(--accent);
    color: var(--accent);
  }

  .settings-menu {
    position: absolute;
    top: calc(100% + 8px);
    right: 0;
    background: var(--panel);
    border: 1px solid var(--line-bright);
    min-width: 240px;
    z-index: 50;
    display: none;
    box-shadow: 0 12px 32px rgba(0, 0, 0, 0.5);
    padding: 4px;
  }

  .settings-menu::before {
    content: '';
    position: absolute;
    top: -1px;
    left: -1px;
    width: 12px;
    height: 12px;
    border-top: 2px solid var(--accent);
    border-left: 2px solid var(--accent);
  }

  .settings-menu::after {
    content: '';
    position: absolute;
    bottom: -1px;
    right: -1px;
    width: 12px;
    height: 12px;
    border-bottom: 2px solid var(--accent);
    border-right: 2px solid var(--accent);
  }

  .settings-menu.open {
    display: block;
  }

  .settings-section {
    padding: 8px 4px;
  }

  .settings-section + .settings-section {
    border-top: 1px solid var(--line);
  }

  .settings-section-label {
    color: var(--ink-faint);
    font-size: 9px;
    letter-spacing: 0.18em;
    text-transform: uppercase;
    padding: 4px 12px 8px;
    font-weight: 700;
  }

  .settings-item {
    display: flex;
    align-items: center;
    gap: 12px;
    width: 100%;
    background: transparent;
    border: none;
    color: var(--ink);
    font-family: inherit;
    font-size: 12px;
    padding: 9px 12px;
    cursor: pointer;
    text-align: left;
    transition: background 0.12s, color 0.12s;
  }

  .settings-item:hover {
    background: var(--panel-2);
    color: var(--accent);
  }

  .settings-item svg {
    flex-shrink: 0;
    color: var(--ink-dim);
    transition: color 0.12s;
  }

  .settings-item:hover svg {
    color: var(--accent);
  }

  .settings-langs {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 2px;
    padding: 0 8px 4px;
  }

  .settings-lang {
    background: transparent;
    border: 1px solid var(--line);
    color: var(--ink-dim);
    font-family: inherit;
    font-size: 11px;
    padding: 7px 10px;
    cursor: pointer;
    text-align: left;
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 6px;
    transition: all 0.12s;
  }

  .settings-lang:hover {
    border-color: var(--line-bright);
    color: var(--ink);
  }

  .settings-lang.active {
    border-color: var(--accent);
    color: var(--accent);
    background: rgba(212, 255, 58, 0.04);
  }

  .settings-lang-flag {
    font-size: 9px;
    letter-spacing: 0.1em;
    color: var(--ink-faint);
    font-weight: 700;
  }

  .settings-lang.active .settings-lang-flag {
    color: var(--accent);
  }

  /* TOAST */
  .toast-container {
    position: fixed;
    bottom: 48px;
    left: 24px;
    z-index: 300;
    display: flex;
    flex-direction: column;
    gap: 8px;
    pointer-events: none;
    max-width: 420px;
  }

  .toast {
    background: var(--panel);
    border: 1px solid var(--line-bright);
    color: var(--ink);
    padding: 12px 16px;
    font-size: 12px;
    line-height: 1.5;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.4);
    pointer-events: auto;
    animation: toastIn 0.2s ease-out;
    position: relative;
    border-left-width: 3px;
  }

  .toast.toast-success { border-left-color: var(--accent); }
  .toast.toast-info { border-left-color: var(--accent); }
  .toast.toast-warn { border-left-color: var(--warn); }

  .toast.fade-out {
    animation: toastOut 0.3s ease-in forwards;
  }

  @keyframes toastIn {
    from { transform: translateY(20px); opacity: 0; }
    to { transform: translateY(0); opacity: 1; }
  }

  @keyframes toastOut {
    to { transform: translateY(20px); opacity: 0; }
  }

  /* DRAG & DROP */
  .card[draggable="true"] { cursor: grab; }
  .card.is-dragging { opacity: .45; }
  .collection-item.drop-target {
    outline: 2px dashed var(--accent);
    outline-offset: -2px;
    background: rgba(212, 255, 58, .12);
  }
  .drop-overlay {
    position: fixed;
    inset: 0;
    background: rgba(212, 255, 58, 0.08);
    border: 2px dashed var(--accent);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 200;
    pointer-events: none;
  }

  .drop-overlay.show {
    display: flex;
  }

  .drop-message {
    font-family: 'Syne', sans-serif;
    font-size: 32px;
    color: var(--accent);
    letter-spacing: 0.1em;
    text-transform: uppercase;
  }

  /* FOLDER MODE OPTIONS */
  #modal-folder label {
    transition: border-color 0.15s, background 0.15s;
  }

  #modal-folder label:hover {
    border-color: var(--line-bright) !important;
    background: var(--bg);
  }

  #modal-folder label:has(input:checked) {
    border-color: var(--accent) !important;
    background: rgba(212, 255, 58, 0.04);
  }

  /* HELP MODAL */
  .help-modal {
    width: 580px !important;
    max-height: 80vh;
    overflow-y: auto;
  }

  /* ABOUT MODAL */
  .about-modal {
    width: 460px !important;
    padding: 0 !important;
    max-width: calc(100vw - 32px);
    max-height: calc(100vh - 32px);
    max-height: calc(100dvh - 32px);
    overflow-y: auto;
  }
  .about-history { padding:14px 28px; text-align:center; border-bottom:1px solid var(--line); }
  .about-history a { color:var(--accent); font-size:12px; text-underline-offset:4px; }
  .about-history a:focus-visible { outline:2px solid var(--accent); outline-offset:4px; }

  .about-hero {
    padding: 36px 28px 24px;
    text-align: center;
    background:
      radial-gradient(circle at 50% 0%, rgba(212, 255, 58, 0.12) 0%, transparent 60%),
      var(--panel);
    border-bottom: 1px solid var(--line);
    position: relative;
  }

  .about-hero::before {
    content: '';
    position: absolute;
    inset: 0;
    background-image:
      linear-gradient(var(--grid) 1px, transparent 1px),
      linear-gradient(90deg, var(--grid) 1px, transparent 1px);
    background-size: 20px 20px;
    opacity: 0.5;
    pointer-events: none;
  }

  .about-logo {
    display: inline-block;
    margin-bottom: 16px;
    position: relative;
    animation: aboutPulse 3s ease-in-out infinite;
  }

  @keyframes aboutPulse {
    0%, 100% { transform: scale(1); filter: drop-shadow(0 0 12px rgba(212, 255, 58, 0.3)); }
    50% { transform: scale(1.04); filter: drop-shadow(0 0 18px rgba(212, 255, 58, 0.5)); }
  }

  .about-title {
    font-family: 'Syne', sans-serif;
    font-size: 36px;
    font-weight: 800;
    letter-spacing: -0.01em;
    margin-bottom: 6px;
    position: relative;
  }

  .about-tagline {
    font-size: 11px;
    color: var(--ink-dim);
    letter-spacing: 0.1em;
    text-transform: uppercase;
    position: relative;
  }

  .about-grid {
    padding: 20px 28px;
    border-bottom: 1px solid var(--line);
  }

  .about-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 7px 0;
    font-size: 12px;
    border-bottom: 1px solid var(--line);
  }

  .about-row:last-child {
    border-bottom: none;
  }

  .about-key {
    color: var(--ink-faint);
    font-size: 10px;
    letter-spacing: 0.15em;
    text-transform: uppercase;
  }

  .about-val {
    color: var(--ink);
    font-size: 12px;
  }

  .about-val.accent {
    color: var(--accent);
    font-weight: 700;
  }

  .about-credit {
    padding: 18px 28px;
    border-bottom: 1px solid var(--line);
    text-align: center;
  }

  .about-credit-label {
    font-size: 10px;
    color: var(--ink-faint);
    letter-spacing: 0.18em;
    text-transform: uppercase;
    margin-bottom: 10px;
  }

  .about-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
    color: var(--accent);
    font-family: 'Syne', sans-serif;
    font-size: 22px;
    font-weight: 700;
    padding: 6px 16px;
    border: 1px solid var(--accent);
    transition: all 0.18s ease;
    letter-spacing: 0.01em;
    position: relative;
    overflow: hidden;
  }

  .about-link::before {
    content: '';
    position: absolute;
    inset: 0;
    background: var(--accent);
    transform: translateX(-100%);
    transition: transform 0.25s ease;
    z-index: 0;
  }

  .about-link:hover::before {
    transform: translateX(0);
  }

  .about-link-text {
    position: relative;
    z-index: 1;
    transition: color 0.18s ease;
  }

  .about-link svg {
    position: relative;
    z-index: 1;
    transition: color 0.18s ease;
  }

  .about-link:hover .about-link-text,
  .about-link:hover svg {
    color: var(--bg);
  }

  .about-features {
    padding: 18px 28px 8px;
  }

  .about-features-label {
    font-size: 10px;
    color: var(--ink-faint);
    letter-spacing: 0.18em;
    text-transform: uppercase;
    margin-bottom: 10px;
  }

  .about-features-list {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
  }

  .about-tag {
    font-size: 10px;
    padding: 4px 9px;
    border: 1px solid var(--line-bright);
    color: var(--ink-dim);
    letter-spacing: 0.05em;
  }

  .about-modal .modal-actions {
    padding: 0 28px 24px;
    margin-top: 0;
  }

  .about-danger {
    padding: 18px 28px;
    border-top: 1px solid var(--line);
    background: rgba(255, 92, 42, 0.04);
  }

  .about-danger-label {
    font-size: 10px;
    color: var(--warn);
    letter-spacing: 0.18em;
    text-transform: uppercase;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  .about-danger-label::before {
    content: '!';
    width: 14px;
    height: 14px;
    border: 1px solid var(--warn);
    color: var(--warn);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
    font-weight: 700;
  }

  .about-danger-text {
    font-size: 11px;
    color: var(--ink-dim);
    line-height: 1.6;
    margin-bottom: 12px;
  }

  .btn-danger {
    background: transparent;
    border: 1px solid var(--warn);
    color: var(--warn);
    width: 100%;
  }

  .btn-danger:hover {
    background: var(--warn);
    border-color: var(--warn);
    color: var(--bg);
  }

  .help-section {
    margin-bottom: 22px;
  }

  .help-section:last-child {
    margin-bottom: 0;
  }

  .help-heading {
    font-family: 'Syne', sans-serif;
    font-size: 14px;
    font-weight: 700;
    color: var(--accent);
    margin-bottom: 10px;
    letter-spacing: 0.02em;
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .help-heading::before {
    content: '';
    width: 6px;
    height: 6px;
    background: var(--accent);
    transform: rotate(45deg);
  }

  .help-text {
    font-size: 12px;
    color: var(--ink);
    line-height: 1.7;
    margin-bottom: 8px;
  }

  .help-text strong {
    color: var(--accent);
    font-weight: 700;
  }

  .help-step {
    padding: 8px 0 8px 28px;
    font-size: 12px;
    line-height: 1.6;
    color: var(--ink);
    position: relative;
    border-bottom: 1px solid var(--line);
  }

  .help-step:last-child {
    border-bottom: none;
  }

  .help-step::before {
    content: counter(step);
    counter-increment: step;
    position: absolute;
    left: 0;
    top: 7px;
    width: 18px;
    height: 18px;
    border: 1px solid var(--accent);
    color: var(--accent);
    font-size: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
  }

  .help-steps {
    counter-reset: step;
    background: var(--bg);
    padding: 4px 14px;
    border: 1px solid var(--line);
  }

  .help-warn {
    background: rgba(255, 92, 42, 0.08);
    border: 1px solid var(--warn);
    padding: 10px 12px;
    font-size: 11px;
    line-height: 1.6;
    color: var(--ink);
    margin-top: 8px;
  }

  .help-warn strong {
    color: var(--warn);
  }

  .status-link {
    cursor: pointer;
    transition: color 0.15s;
  }

  .status-link:hover {
    color: var(--accent);
  }
.archive-dialog { color:var(--ink);background:var(--bg);border:1px solid var(--line);width:min(1050px,95vw);max-height:90vh;padding:24px;overflow:auto;margin:auto; }
.archive-dialog::backdrop { background:rgba(0,0,0,.75); }
.archive-dialog p,.model-check p { font-size:12px;line-height:1.65;color:var(--ink-dim); }
.feature-heading,.feature-actions { display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap; }
.project-layout { display:grid;grid-template-columns:240px minmax(0,1fr);gap:24px; }
.project-list { display:flex;flex-direction:column;gap:8px;margin-top:16px; }
.project-part { display:grid;grid-template-columns:minmax(100px,1fr) 80px minmax(100px,1fr) 35px;gap:8px;align-items:end;border-bottom:1px solid var(--line);padding:12px 0; }
.archive-dialog label { display:block;font-size:12px;margin:12px 0; }
.archive-dialog input,.archive-dialog textarea,.archive-dialog select { display:block;width:100%;margin:6px 0; }
.archive-dialog .btn { white-space:normal;overflow-wrap:anywhere; }
.model-check { border-top:1px solid var(--line);margin:16px 0;padding-top:14px; }
.duplicate-entry { border-top:1px solid var(--line);padding:12px 0;overflow-wrap:anywhere; }
@media(max-width:700px) { .project-layout { grid-template-columns:1fr; } .project-part { grid-template-columns:1fr 80px; } }
</style>
</head>
<body>
<div class="app-loading" id="app-loading">
  <div class="app-loading-logo"></div>
  <div class="app-loading-name">3DLens</div>
  <div class="app-loading-bar"></div>
  <div class="app-loading-text" id="app-loading-text">Loading archive...</div>
</div>
<div class="app">
  <header class="header">
    <div class="brand">
      <div class="logo-mark"></div>
      <div>
        <div class="brand-name">3DLens</div>
      </div>
      <div class="brand-sub" id="brand-sub-text">3D Archief // v<?= h(app_release()['version']) ?></div>
    </div>
    <div class="header-actions">
      <a href="onedrive.php" class="btn" style="text-decoration:none">OneDrive</a>
      <a href="account.php" class="btn" style="text-decoration:none">Account</a>
      <form action="logout.php" method="post" style="display:inline-flex">
        <input type="hidden" name="csrf" value="<?= h($csrf) ?>" />
        <button class="btn" type="submit">Uitloggen</button>
      </form>
      <button class="btn" id="btn-about">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <circle cx="12" cy="12" r="9"/><path d="M12 8h.01"/><path d="M11 12h1v4h1"/>
        </svg>
        <span id="btn-about-text">About</span>
      </button>
      <div class="settings-picker">
        <button class="btn" id="btn-settings">
          <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <circle cx="12" cy="12" r="3"/>
            <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
          </svg>
          <span id="btn-settings-text">Instellingen</span>
          <span class="settings-arrow">▼</span>
        </button>
        <div class="settings-menu" id="settings-menu">
          <div class="settings-section">
            <div class="settings-section-label" id="settings-data-label">Data</div>
            <button class="settings-item" id="btn-backup">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><path d="M7 10l5 5 5-5"/><path d="M12 15V3"/>
              </svg>
              <span id="btn-backup-text">Backup</span>
            </button>
            <button class="settings-item" id="btn-restore">
              <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path d="M21 12a9 9 0 1 1-3-6.7L21 8"/><path d="M21 3v5h-5"/>
              </svg>
              <span id="btn-restore-text">Restore</span>
            </button>
          </div>
          <div class="settings-section">
            <div class="settings-section-label" id="settings-language-label">Taal</div>
            <div class="settings-langs" id="settings-langs"></div>
          </div>
        </div>
      </div>
      <input type="file" id="restore-input" accept=".forge,.json" style="display:none" />
      <button class="btn" id="btn-import-folder">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z"/>
        </svg>
        <span id="btn-import-folder-text">Map Importeren</span>
      </button>
      <button class="btn btn-primary" id="btn-import">
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M12 5v14M5 12h14"/>
        </svg>
        <span id="btn-import-text">Bestanden Importeren</span>
      </button>
      <input type="file" id="file-input" accept=".stl,.3mf,.ctb,.cbddlp,.phz,.photon,.pws,.pw0,.pwmx,.pwms,.pwmo,.pwx,.sl1,.sl1s" multiple style="display:none" />
      <input type="file" id="folder-input" webkitdirectory directory multiple style="display:none" />
    </div>
  </header>

  <aside class="sidebar">
    <div class="sidebar-section">
      <div class="section-label">
        <span id="sidebar-collections-label">Collecties</span>
        <span class="count" id="col-count">0</span>
      </div>
      <button class="new-collection" id="btn-new-collection">+ Nieuwe Collectie</button>
      <button class="new-collection" id="btn-projects">Projecten / onderdelen</button>
      <button class="new-collection" id="btn-duplicate-report">Laatste importrapport</button>
    </div>
    <div class="collections" id="collections-list"></div>
  </aside>

  <main class="main">
    <div class="main-toolbar">
      <div class="breadcrumb">
        <span class="breadcrumb-current" id="current-collection">Alle Bestanden</span>
        <span class="breadcrumb-meta" id="current-count">0 items</span>
      </div>
      <div class="toolbar-right">
        <input type="text" class="search" id="search" placeholder="Zoek naam of beschrijving..." />
      </div>
    </div>
    <div class="bulk-toolbar" aria-label="Selectieacties">
      <label><input type="checkbox" id="bulk-select-all"><span id="bulk-all-label">Alles in beeld</span></label>
      <span id="bulk-count" role="status" aria-live="polite"></span>
      <select id="bulk-move-select" aria-label="Doelcollectie"></select>
      <button class="btn" id="bulk-move">Verplaatsen</button>
      <button class="btn" id="bulk-delete">Verwijderen</button>
      <button class="btn" id="bulk-clear">Deselecteren</button>
      <span class="bulk-hint" id="bulk-hint">Vink modellen aan of gebruik Ctrl+klik (Mac: Cmd+klik).</span>
    </div>
    <div class="grid-container" id="grid-container">
      <div class="empty" id="empty-state">
        <div class="empty-icon">∇</div>
        <div class="empty-title" id="empty-title">Nog Geen Bestanden</div>
        <div class="empty-text" id="empty-text">
          Importeer STL of 3MF bestanden om je archief op te bouwen. Drag &amp; drop werkt ook overal in het venster.
        </div>
        <button class="btn btn-primary" id="empty-import-btn" onclick="document.getElementById('file-input').click()">
          Bestanden Importeren
        </button>
      </div>
      <div class="grid" id="grid" style="display:none"></div>
    </div>
  </main>

  <aside class="inspector">
    <div class="inspector-empty" id="inspector-empty">
      <div class="inspector-empty-mark">⌖</div>
      <div style="font-size: 11px; letter-spacing: 0.15em; text-transform: uppercase;" id="inspector-empty-title">Geen Selectie</div>
      <div style="font-size: 10px; margin-top: 8px; max-width: 200px; line-height: 1.6;" id="inspector-empty-text">
        Selecteer een bestand om details en preview te zien
      </div>
    </div>
    <div class="inspector-content" id="inspector-content" style="display:none">
      <div class="inspector-viewer" id="inspector-viewer">
        <div class="viewer-corners"></div>
        <div class="viewer-loading" id="viewer-loading">
          <div class="viewer-loading-spinner"></div>
          <div class="viewer-loading-text" id="viewer-loading-text">3D Bestand Laden</div>
        </div>
        <div class="viewer-controls">
          <button class="viewer-btn" id="view-reset" title="Reset weergave">⟲</button>
          <button class="viewer-btn" id="view-wireframe" title="Wireframe">◇</button>
          <button class="viewer-btn active" id="view-spin" title="Auto rotatie">↻</button>
          <button class="viewer-btn viewer-btn-wide" id="view-fullscreen" title="Open fullscreen preview">⛶</button>
        </div>
      </div>
      <div class="inspector-details" id="inspector-details"></div>
      <div class="inspector-actions">
        <select class="move-select" id="move-select">
          <option value="">— Verplaats naar collectie —</option>
        </select>
        <button class="btn" id="btn-regenerate-preview" style="display:none">Preview Opnieuw Genereren</button>
        <button class="btn" id="btn-download">Download Bestand</button>
        <button class="btn" id="btn-delete-file" style="border-color: var(--warn); color: var(--warn);">Verwijder Bestand</button>
      </div>
    </div>
  </aside>

  <div class="statusbar">
    <div class="status-left">
      <span><span class="status-dot"></span><span id="status-connected">Verbonden</span></span>
      <span id="status-files">0 bestanden</span>
      <span id="status-size">0 KB</span>
    </div>
    <div class="status-right">
      <span class="status-link" id="status-help">? Backup &amp; Sync</span>
      <span>3DLENS // 3D ARCHIEF</span>
      <span id="status-time"></span>
    </div>
  </div>
</div>

<div class="modal-backdrop" id="modal-collection">
  <div class="modal">
    <div class="modal-title" id="new-col-title">Nieuwe Collectie</div>
    <input type="text" class="modal-input" id="collection-name" placeholder="Collectie naam..." maxlength="40" />
    <div class="modal-actions">
      <button class="btn" id="btn-create-collection-cancel" onclick="closeModal()">Annuleren</button>
      <button class="btn btn-primary" id="btn-create-collection">Aanmaken</button>
    </div>
  </div>
</div>

<div class="drop-overlay" id="drop-overlay">
  <div class="drop-message" id="drop-message">Laat los om te importeren</div>
</div>

<div class="toast-container" id="toast-container"></div>

<div class="modal-backdrop" id="modal-clear">
  <div class="modal" style="width: 460px;">
    <div class="modal-title" style="color: var(--warn);" id="clear-modal-title">Bibliotheek Legen?</div>
    <div id="clear-summary" style="font-size: 11px; color: var(--ink-dim); margin-bottom: 14px; line-height: 1.7; padding: 12px; background: var(--bg); border: 1px solid var(--line);"></div>
    <div style="font-size: 12px; color: var(--ink); line-height: 1.6; margin-bottom: 8px;" id="clear-warning-main">
      Dit verwijdert <strong style="color: var(--warn);">alles</strong> uit het 3DLens-archief: bestanden, collecties, beschrijvingen en thumbnails.
    </div>
    <div style="font-size: 11px; color: var(--ink-dim); line-height: 1.6; margin-bottom: 18px; padding: 10px 12px; background: rgba(212, 255, 58, 0.04); border-left: 2px solid var(--accent);" id="clear-warning-safe">
      Je <strong style="color: var(--accent);">originele 3D-bestanden op je schijf blijven onaangeraakt</strong>. 3DLens werkt met kopieën in zijn eigen database — die map waar je ze vandaan hebt geïmporteerd verandert niet.
    </div>
    <div style="font-size: 10px; color: var(--ink-faint); letter-spacing: 0.15em; text-transform: uppercase; margin-bottom: 8px;" id="clear-confirm-prompt">Type <strong style="color: var(--warn); font-weight: 700;">LEGEN</strong> om te bevestigen</div>
    <input type="text" class="modal-input" id="clear-confirm" placeholder="LEGEN" autocomplete="off" />
    <div class="modal-actions">
      <button class="btn" id="btn-clear-cancel" onclick="closeClearModal()">Annuleren</button>
      <button class="btn btn-danger" id="btn-confirm-clear" disabled style="width: auto;">Definitief Legen</button>
    </div>
  </div>
</div>

<div class="modal-backdrop" id="modal-about">
  <div class="modal about-modal">
    <div class="about-hero">
      <div class="about-logo">
        <svg width="64" height="64" viewBox="0 0 64 64" fill="none">
          <circle cx="32" cy="32" r="28" stroke="var(--accent)" stroke-width="2"/>
          <circle cx="32" cy="32" r="20" stroke="var(--accent)" stroke-width="1" opacity="0.5"/>
          <circle cx="32" cy="32" r="12" stroke="var(--accent)" stroke-width="1" opacity="0.3"/>
          <circle cx="32" cy="32" r="4" fill="var(--accent)"/>
          <path d="M32 4 L32 12 M32 52 L32 60 M4 32 L12 32 M52 32 L60 32" stroke="var(--accent)" stroke-width="1.5"/>
        </svg>
      </div>
      <div class="about-title">3DLens</div>
      <div class="about-tagline" id="about-tagline">Browser-based archief voor 3D-print bestanden</div>
    </div>

    <div class="about-grid">
      <div class="about-row">
        <span class="about-key" id="about-key-version">Versie</span>
        <span class="about-val accent"><?= h(app_release()['version']) ?></span>
      </div>
      <div class="about-row">
        <span class="about-key" id="about-key-release">Release</span>
        <span class="about-val"><?= h(app_release()['date_label']) ?></span>
      </div>
      <div class="about-row">
        <span class="about-key" id="about-key-supports">Ondersteunt</span>
        <span class="about-val">STL · 3MF · SLA</span>
      </div>
      <div class="about-row">
        <span class="about-key" id="about-key-engine">Engine</span>
        <span class="about-val">Three.js · WebGL</span>
      </div>
      <div class="about-row">
        <span class="about-key" id="about-key-storage">Opslag</span>
        <span class="about-val" id="about-val-storage">IndexedDB (lokaal)</span>
      </div>
    </div>

    <div class="about-history"><a id="about-changelog" href="changelog.php" target="_blank" rel="noopener">Wijzigingen per versie ↗</a></div>
    <div class="about-credit">
      <div class="about-credit-label" id="about-author-label">Gemaakt door</div>
      <a href="https://jroen.nl" target="_blank" rel="noopener noreferrer" class="about-link">
        <span class="about-link-text">jroen.nl</span>
        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
          <path d="M7 17L17 7M9 7h8v8"/>
        </svg>
      </a>
    </div>

    <div class="about-features">
      <div class="about-features-label" id="about-features-label">Functies</div>
      <div class="about-features-list">
        <span class="about-tag" id="about-feat-preview">3D Preview</span>
        <span class="about-tag" id="about-feat-collections">Collecties</span>
        <span class="about-tag" id="about-feat-folders">Mappen Importeren</span>
        <span class="about-tag" id="about-feat-descriptions">Beschrijvingen</span>
        <span class="about-tag" id="about-feat-backup">Volledige Backup</span>
        <span class="about-tag" id="about-feat-dragdrop">Drag &amp; Drop</span>
      </div>
    </div>

    <div class="about-danger">
      <div class="about-danger-label" id="about-danger-label">Gevarenzone</div>
      <div class="about-danger-text" id="about-danger-text">
        Verwijdert alle bestanden, collecties en beschrijvingen uit de bibliotheek. Je <strong style="color: var(--ink);">originele bestanden op je schijf blijven intact</strong> — 3DLens verwijdert alleen de kopieën uit zijn eigen archief. Maak eerst een backup als je wilt herstellen.
      </div>
      <button class="btn btn-danger" id="btn-clear-library">Bibliotheek Legen</button>
    </div>

    <div class="modal-actions">
      <button class="btn btn-primary" id="btn-about-close" onclick="closeAboutModal()">Sluiten</button>
    </div>
  </div>
</div>

<div class="modal-backdrop" id="modal-help">
  <div class="modal help-modal">
    <div class="modal-title" id="help-modal-title">Backup &amp; Synchronisatie</div>

    <div class="help-section">
      <div class="help-heading" id="help-backup-title">Backup maken</div>
      <div class="help-text" id="help-backup-text">Klik op <strong>Backup</strong> in de header. Er wordt een <strong>.forge</strong>-bestand gedownload met al je 3D-bestanden (STL, 3MF en resin/SLA), collecties, beschrijvingen en metadata. Dit is een complete kopie van je archief.</div>
    </div>

    <div class="help-section">
      <div class="help-heading" id="help-restore-title">Backup terugzetten</div>
      <div class="help-text" id="help-restore-text">Klik op <strong>Restore</strong> en selecteer een <strong>.forge</strong>-bestand. Je kunt kiezen om je huidige archief te <strong>vervangen</strong> of de backup ernaast te <strong>samenvoegen</strong>.</div>
    </div>

    <div class="help-section">
      <div class="help-heading" id="help-multipc-title">Op meerdere PC's gebruiken</div>
      <div class="help-text" id="help-multipc-text">Het programma slaat data lokaal op per browser per PC. Om je archief te delen tussen apparaten kun je een cloud-map (Dropbox, OneDrive, Google Drive, iCloud) gebruiken:</div>
      <div class="help-steps">
        <div class="help-step" id="help-step-1">Plaats <strong>stl-organizer.html</strong> in een gesynchroniseerde cloud-map.</div>
        <div class="help-step" id="help-step-2">Op elke PC: open het bestand vanuit die cloud-map (dubbelklik).</div>
        <div class="help-step" id="help-step-3">Maak je archief op (importeer STL, 3MF of resin/SLA en maak collecties).</div>
        <div class="help-step" id="help-step-4">Maak een <strong>Backup</strong> en sla die op in dezelfde cloud-map (of submap).</div>
        <div class="help-step" id="help-step-5">Op een andere PC: open hetzelfde HTML-bestand en doe <strong>Restore</strong> met die backup.</div>
      </div>
      <div class="help-warn" id="help-warn"><strong>Let op:</strong> open het HTML-bestand niet tegelijk op meerdere PC's met aanpassingen. Werk op één PC, maak een backup, en restore daarna op de andere. Anders raken je versies uit sync.</div>
    </div>

    <div class="help-section">
      <div class="help-heading" id="help-strategy-title">Backup-strategie</div>
      <div class="help-text" id="help-strategy-text">Maak regelmatig een backup. De <strong>.forge</strong>-bestanden bevatten je complete 3D-data, dus de bestandsgrootte is vergelijkbaar met de som van je modellen. Een datum in de bestandsnaam zit er automatisch in.</div>
    </div>

    <div class="modal-actions">
      <button class="btn btn-primary" id="btn-help-close" onclick="closeHelpModal()">Begrepen</button>
    </div>
  </div>
</div>

<div class="modal-backdrop" id="modal-restore">
  <div class="modal" style="width: 460px;">
    <div class="modal-title" id="restore-modal-title">Backup Terugzetten</div>
    <div id="restore-summary" style="font-size: 11px; color: var(--ink-dim); margin-bottom: 18px; line-height: 1.7; padding: 12px; background: var(--bg); border: 1px solid var(--line);"></div>
    <div style="font-size: 10px; color: var(--ink-faint); letter-spacing: 0.15em; text-transform: uppercase; margin-bottom: 10px;" id="restore-how-label">Hoe verwerken?</div>
    <label style="display: flex; align-items: flex-start; gap: 10px; padding: 10px; border: 1px solid var(--line); cursor: pointer; margin-bottom: 6px;" class="restore-opt">
      <input type="radio" name="restore-mode" value="merge" checked style="margin-top: 2px; accent-color: var(--accent);" />
      <div>
        <div style="font-size: 12px; margin-bottom: 3px;" id="restore-merge-title">Samenvoegen</div>
        <div style="font-size: 10px; color: var(--ink-faint); line-height: 1.5;" id="restore-merge-desc">Backup wordt toegevoegd aan je huidige archief. Bestaande items blijven behouden.</div>
      </div>
    </label>
    <label style="display: flex; align-items: flex-start; gap: 10px; padding: 10px; border: 1px solid var(--warn); cursor: pointer; margin-bottom: 18px; background: rgba(255, 92, 42, 0.04);" class="restore-opt">
      <input type="radio" name="restore-mode" value="replace" style="margin-top: 2px; accent-color: var(--warn);" />
      <div>
        <div style="font-size: 12px; margin-bottom: 3px; color: var(--warn);" id="restore-replace-title">Vervangen</div>
        <div style="font-size: 10px; color: var(--ink-faint); line-height: 1.5;" id="restore-replace-desc">Verwijdert al je huidige bestanden en collecties. Niet ongedaan te maken.</div>
      </div>
    </label>
    <div class="modal-actions">
      <button class="btn" id="btn-restore-cancel" onclick="closeRestoreModal()">Annuleren</button>
      <button class="btn btn-primary" id="btn-confirm-restore">Restore</button>
    </div>
  </div>
</div>

<div class="modal-backdrop" id="modal-folder">
  <div class="modal" style="width: 480px;">
    <div class="modal-title" id="folder-title">Map Importeren</div>
    <div id="folder-summary" style="font-size: 11px; color: var(--ink-dim); margin-bottom: 18px; line-height: 1.7; padding: 12px; background: var(--bg); border: 1px solid var(--line);"></div>
    <div style="font-size: 10px; color: var(--ink-faint); letter-spacing: 0.15em; text-transform: uppercase; margin-bottom: 10px;" id="folder-structure-label">Structuur</div>
    <label style="display: flex; align-items: flex-start; gap: 10px; padding: 10px; border: 1px solid var(--line); cursor: pointer; margin-bottom: 6px;" id="opt-folders">
      <input type="radio" name="folder-mode" value="folders" checked style="margin-top: 2px; accent-color: var(--accent);" />
      <div>
        <div style="font-size: 12px; margin-bottom: 3px;" id="folder-opt-folders-title">Submappen → Collecties</div>
        <div style="font-size: 10px; color: var(--ink-faint); line-height: 1.5;" id="folder-opt-folders-desc">Elke submap wordt een aparte collectie. Bestanden in de hoofdmap komen in een collectie met de naam van die map.</div>
      </div>
    </label>
    <label style="display: flex; align-items: flex-start; gap: 10px; padding: 10px; border: 1px solid var(--line); cursor: pointer; margin-bottom: 6px;" id="opt-single">
      <input type="radio" name="folder-mode" value="single" style="margin-top: 2px; accent-color: var(--accent);" />
      <div>
        <div style="font-size: 12px; margin-bottom: 3px;" id="folder-opt-single-title">Alles in één collectie</div>
        <div style="font-size: 10px; color: var(--ink-faint); line-height: 1.5;" id="folder-opt-single-desc">Alle bestanden worden samengevoegd onder de naam van de hoofdmap.</div>
      </div>
    </label>
    <label style="display: flex; align-items: flex-start; gap: 10px; padding: 10px; border: 1px solid var(--line); cursor: pointer; margin-bottom: 18px;" id="opt-flat">
      <input type="radio" name="folder-mode" value="flat" style="margin-top: 2px; accent-color: var(--accent);" />
      <div>
        <div style="font-size: 12px; margin-bottom: 3px;" id="folder-opt-flat-title">Geen collectie</div>
        <div style="font-size: 10px; color: var(--ink-faint); line-height: 1.5;" id="folder-opt-flat-desc">Importeer als losse bestanden zonder collectie.</div>
      </div>
    </label>
    <div class="modal-actions">
      <button class="btn" id="btn-folder-cancel" onclick="closeFolderModal()">Annuleren</button>
      <button class="btn btn-primary" id="btn-confirm-folder">Importeren</button>
    </div>
  </div>
</div>


<div class="preview-modal" id="preview-modal" aria-hidden="true">
  <div class="preview-modal-header">
    <div class="preview-modal-title">
      <div class="preview-modal-kicker">Snelle Preview</div>
      <div class="preview-modal-name" id="preview-modal-name">3D Preview</div>
    </div>
    <div class="preview-modal-actions">
      <button class="btn" id="preview-modal-reset">Reset</button>
      <button class="btn" id="preview-modal-wire">Wireframe</button>
      <button class="btn" id="preview-modal-spin">Rotatie aan</button>
      <button class="btn btn-primary" id="preview-modal-close">Sluiten</button>
    </div>
  </div>
  <div class="preview-modal-viewer" id="preview-modal-viewer">
    <div class="viewer-corners"></div>
    <div class="preview-modal-hint">Sleep om te roteren · Scroll om te zoomen · Esc om te sluiten</div>
  </div>
</div>

<div class="modal-backdrop" id="modal-progress">
  <div class="modal" style="width: 420px;">
    <div class="modal-title" id="progress-title">Importeren...</div>
    <div id="progress-current" style="font-size: 11px; color: var(--ink); margin-bottom: 12px; height: 16px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"></div>
    <div style="height: 4px; background: var(--bg); border: 1px solid var(--line); margin-bottom: 12px; position: relative; overflow: hidden;">
      <div id="progress-bar" style="height: 100%; width: 0%; background: var(--accent); transition: width 0.2s;"></div>
    </div>
    <div style="display: flex; justify-content: space-between; font-size: 10px; color: var(--ink); letter-spacing: 0.1em; text-transform: uppercase;">
      <span id="progress-count">0 / 0</span>
      <span id="progress-percent" style="color: var(--accent); font-weight: 700;">0%</span>
    </div>
  </div>
</div>

<script type="module">
import { createArchiveStorage } from './storage.js';
import { analyzeMesh, exactDuplicate, remapProject, threeMFUnitScale } from './model-analysis.js';
import { createProjectsUI } from './projects.js';
import * as THREE from 'three';
import { STLLoader } from 'three/addons/loaders/STLLoader.js';
import { ThreeMFLoader } from 'three/addons/loaders/3MFLoader.js';
import { OrbitControls } from 'three/addons/controls/OrbitControls.js';
import * as BufferGeometryUtils from 'three/addons/utils/BufferGeometryUtils.js';
import { unzipSync, strFromU8 } from 'fflate';

// ============ I18N ============
const TRANSLATIONS = {
  nl: {
    _name: 'Nederlands',
    _flag: 'NL',
    'app.subtitle': '3D Archief',
    'app.tagline': 'Browser-based archief voor 3D-print bestanden',
    'app.loading': 'Archief laden...',
    // Header
    'header.about': 'About',
    'header.settings': 'Instellingen',
    'header.settings_data': 'Data',
    'header.settings_language': 'Taal',
    'header.restore': 'Restore',
    'header.backup': 'Backup',
    'header.import_folder': 'Map Importeren',
    'header.import_files': 'Bestanden Importeren',
    // Sidebar
    'sidebar.collections': 'Collecties',
    'sidebar.new_collection': '+ Nieuwe Collectie',
    'sidebar.all_files': 'Alle Bestanden',
    'sidebar.uncategorized': 'Niet Gecategoriseerd',
    // Main toolbar
    'toolbar.search': 'Zoek naam, omschrijving of tag...',
    'toolbar.items': item => `${item} ${item === 1 ? 'item' : 'items'}`,
    // Empty states
    'empty.no_files_title': 'Nog Geen Bestanden',
    'empty.no_files_text': 'Importeer STL, 3MF of resin/SLA bestanden om je archief op te bouwen. Drag & drop werkt ook overal in het venster.',
    'empty.no_results_title': 'Geen Resultaten',
    'empty.no_results_search': 'Geen bestanden gevonden voor je zoekopdracht.',
    'empty.no_results_collection': 'Deze collectie is nog leeg.',
    // Inspector
    'inspector.no_selection': 'Geen Selectie',
    'inspector.no_selection_text': 'Selecteer een bestand om details en preview te zien',
    'inspector.loading': '3D Bestand Laden',
    'inspector.preview_failed': 'Preview Mislukt',
    'inspector.preview_failed_text': 'Het bestand kon niet worden gerenderd. Het is mogelijk beschadigd of een niet-ondersteunde 3MF-variant. Het bestand zelf is veilig opgeslagen en kan worden gedownload.',
    'inspector.preview_pending_title': 'Preview Niet Geladen',
    'inspector.preview_pending_text': '3MF-bestanden worden niet automatisch gerenderd om import snel te houden. Klik om de preview voor dit bestand handmatig te genereren.',
    // SLA / resin
    'sla.sliced': 'Gesliced',
    'sla.no_preview': 'Geen preview ingesloten',
    'sla.layer_height': 'Laagdikte',
    'sla.total_height': 'Totale hoogte',
    'sla.layer_count': 'Aantal lagen',
    'sla.exposure': 'Belichting',
    'sla.bottom_exposure': 'Bottom-belichting',
    'sla.bottom_layers': 'Bottom-lagen',
    'sla.print_time': 'Printtijd',
    'sla.resin_volume': 'Resin-volume',
    'sla.resolution': 'Resolutie',
    'sla.format': 'Formaat',
    'inspector.generate_preview': 'Genereer Preview',
    'inspector.retry_preview': 'Opnieuw Proberen',
    'inspector.preview_pending_card': 'Klik om te bekijken',
    'inspector.rendering': 'Rendering preview...',
    'inspector.viewer_reset': 'Reset weergave',
    'inspector.viewer_wireframe': 'Wireframe',
    'inspector.viewer_spin': 'Auto rotatie',
    'inspector.move_placeholder': '— Verplaats naar collectie —',
    'inspector.move_uncategorized': 'Niet Gecategoriseerd',
    'inspector.download': 'Download Bestand',
    'inspector.delete': 'Verwijder Bestand',
    'inspector.regenerate_preview': 'Preview Opnieuw Genereren',
    // Detail rows
    'detail.type': 'Type',
    'detail.file': 'Bestand',
    'detail.triangles': 'Driehoeken',
    'detail.width': 'Breedte',
    'detail.height': 'Hoogte',
    'detail.depth': 'Diepte',
    'detail.collection': 'Collectie',
    'detail.description': 'Beschrijving',
    'detail.description_placeholder': 'Voeg notities, bron, print-instellingen of opmerkingen toe...',
    'detail.has_description': 'Heeft beschrijving',
    'detail.saved': 'Opgeslagen',
    'detail.cancel': 'Annuleren',
    'detail.save': 'Opslaan',
    // Status bar
    'status.connected': 'Verbonden',
    'status.help_link': '? Backup & Sync',
    'status.files': n => `${n} bestand${n === 1 ? '' : 'en'}`,
    // New collection modal
    'modal.new_col_title': 'Nieuwe Collectie',
    'modal.new_col_placeholder': 'Collectie naam...',
    'modal.cancel': 'Annuleren',
    'modal.create': 'Aanmaken',
    'modal.confirm': 'Bevestigen',
    // Folder import
    'folder.title': 'Map Importeren',
    'folder.structure': 'Structuur',
    'folder.opt_folders_title': 'Submappen → Collecties',
    'folder.opt_folders_desc': 'Elke submap wordt een aparte collectie. Bestanden in de hoofdmap komen in een collectie met de naam van die map.',
    'folder.opt_single_title': 'Alles in één collectie',
    'folder.opt_single_desc': 'Alle bestanden worden samengevoegd onder de naam van de hoofdmap.',
    'folder.opt_flat_title': 'Geen collectie',
    'folder.opt_flat_desc': 'Importeer als losse bestanden zonder collectie.',
    'folder.import': 'Importeren',
    'folder.summary_files': (n, types) => `${n} bestand${n === 1 ? '' : 'en'} (${types})`,
    'folder.summary_subfolders': n => `${n} submap${n === 1 ? '' : 'pen'}: `,
    'folder.summary_root': n => `${n} bestand${n === 1 ? '' : 'en'} in hoofdmap`,
    // Progress
    'progress.importing': 'Bestanden Importeren',
    'progress.backup': 'Backup Maken',
    'progress.restore': 'Backup Terugzetten',
    'progress.writing': 'Bestand schrijven...',
    'progress.done': 'Klaar',
    // Help
    'help.title': 'Backup & Synchronisatie',
    'help.backup_title': 'Backup maken',
    'help.backup_text': 'Klik op <strong>Backup</strong> in de header. Er wordt een <strong>.forge</strong>-bestand gedownload met al je 3D-bestanden (STL, 3MF en resin/SLA), collecties, beschrijvingen en metadata. Dit is een complete kopie van je archief.',
    'help.restore_title': 'Backup terugzetten',
    'help.restore_text': 'Klik op <strong>Restore</strong> en selecteer een <strong>.forge</strong>-bestand. Je kunt kiezen om je huidige archief te <strong>vervangen</strong> of de backup ernaast te <strong>samenvoegen</strong>.',
    'help.multi_pc_title': "Op meerdere PC's gebruiken",
    'help.multi_pc_text': 'Het programma slaat data lokaal op per browser per PC. Om je archief te delen tussen apparaten kun je een cloud-map (Dropbox, OneDrive, Google Drive, iCloud) gebruiken:',
    'help.step1': 'Plaats <strong>stl-organizer.html</strong> in een gesynchroniseerde cloud-map.',
    'help.step2': 'Op elke PC: open het bestand vanuit die cloud-map (dubbelklik).',
    'help.step3': 'Maak je archief op (importeer STL, 3MF of resin/SLA en maak collecties).',
    'help.step4': 'Maak een <strong>Backup</strong> en sla die op in dezelfde cloud-map (of submap).',
    'help.step5': 'Op een andere PC: open hetzelfde HTML-bestand en doe <strong>Restore</strong> met die backup.',
    'help.warning': "<strong>Let op:</strong> open het HTML-bestand niet tegelijk op meerdere PC's met aanpassingen. Werk op één PC, maak een backup, en restore daarna op de andere. Anders raken je versies uit sync.",
    'help.strategy_title': 'Backup-strategie',
    'help.strategy_text': 'Maak regelmatig een backup. De <strong>.forge</strong>-bestanden bevatten je complete 3D-data, dus de bestandsgrootte is vergelijkbaar met de som van je modellen. Een datum in de bestandsnaam zit er automatisch in.',
    'help.understood': 'Begrepen',
    // Restore modal
    'restore.title': 'Backup Terugzetten',
    'restore.how': 'Hoe verwerken?',
    'restore.merge_title': 'Samenvoegen',
    'restore.merge_desc': 'Backup wordt toegevoegd aan je huidige archief. Bestaande items blijven behouden.',
    'restore.replace_title': 'Vervangen',
    'restore.replace_desc': 'Verwijdert al je huidige bestanden en collecties. Niet ongedaan te maken.',
    'restore.action': 'Restore',
    'restore.summary_date': date => `Backup van ${date}`,
    'restore.summary_files': n => `${n} 3D-bestand${n === 1 ? '' : 'en'}`,
    'restore.summary_collections': n => `${n} collectie${n === 1 ? '' : 's'}`,
    'restore.summary_total': size => `Totaal: ${size}`,
    // About
    'about.tagline': 'Browser-based archief voor 3D-print bestanden',
    'about.version': 'Versie',
    'about.release': 'Release',
    'about.supports': 'Ondersteunt',
    'about.engine': 'Engine',
    'about.storage': 'Opslag',
    'about.storage_value': 'IndexedDB (lokaal)',
    'about.author': 'Gemaakt door',
    'about.features': 'Functies',
    'about.feat_preview': '3D Preview',
    'about.feat_collections': 'Collecties',
    'about.feat_folders': 'Mappen Importeren',
    'about.feat_descriptions': 'Beschrijvingen',
    'about.feat_backup': 'Volledige Backup',
    'about.feat_dragdrop': 'Drag & Drop',
    'about.danger_zone': 'Gevarenzone',
    'about.danger_text': 'Verwijdert alle bestanden, collecties en beschrijvingen uit de bibliotheek. Je <strong>originele bestanden op je schijf blijven intact</strong> — 3DLens verwijdert alleen de kopieën uit zijn eigen archief. Maak eerst een backup als je wilt herstellen.',
    'about.clear': 'Bibliotheek Legen',
    'about.close': 'Sluiten',
    // Clear modal
    'clear.title': 'Bibliotheek Legen?',
    'clear.summary': 'Wordt verwijderd:',
    'clear.warning_main': 'Dit verwijdert <strong>alles</strong> uit het 3DLens-archief: bestanden, collecties, beschrijvingen en thumbnails.',
    'clear.warning_safe': 'Je <strong>originele 3D-bestanden op je schijf blijven onaangeraakt</strong>. 3DLens werkt met kopieën in zijn eigen database — die map waar je ze vandaan hebt geïmporteerd verandert niet.',
    'clear.confirm_prompt': 'Type <strong>LEGEN</strong> om te bevestigen',
    'clear.confirm_word': 'LEGEN',
    'clear.confirm_btn': 'Definitief Legen',
    // Drop overlay
    'drop.message': 'Laat los om te importeren',
    // Toasts & alerts
    'toast.empty_lib': 'De bibliotheek is al leeg.',
    'toast.no_files_found': 'Geen ondersteunde 3D-bestanden gevonden (STL, 3MF, of resin/SLA).',
    'toast.no_files_in_folder': 'Geen ondersteunde 3D-bestanden gevonden in deze map.',
    'toast.no_files_in_drop': 'Geen ondersteunde 3D-bestanden gevonden in de gesleepte mappen.',
    'toast.lib_cleared': (files, cols) => `Bibliotheek geleegd: ${files} bestand${files === 1 ? '' : 'en'} en ${cols} collectie${cols === 1 ? '' : 's'} verwijderd.`,
    'toast.imported': n => `${n} bestand${n === 1 ? '' : 'en'} geïmporteerd.`,
    'toast.imported_with_skipped': (imp, skip, examples, more) => `${imp} geïmporteerd, ${skip} overgeslagen (al aanwezig): ${examples}${more}`,
    'toast.all_skipped': n => `Alle ${n} bestand${n === 1 ? '' : 'en'} stond${n === 1 ? '' : 'en'} al in je archief. Niks geïmporteerd.`,
    'toast.skipped_more': n => ` (en ${n} meer)`,
    'toast.restore_done': (files, cols) => `Restore voltooid: ${files} bestand${files === 1 ? '' : 'en'} en ${cols} collectie${cols === 1 ? '' : 's'} teruggezet.`,
    'toast.restore_dups': n => ` ${n} duplica${n === 1 ? 'at' : 'ten'} overgeslagen.`,
    'alert.empty_backup': 'Niets om te back-uppen — je archief is leeg.',
    'alert.invalid_backup': 'Dit lijkt geen geldig 3DLens-backupbestand. Controleer of je het juiste .forge-bestand hebt geselecteerd.',
    'alert.old_backup': 'Dit is een oude metadata-only export (zonder bestandsdata). Sinds deze versie maakt Backup volledige backups inclusief bestanden. Importeer de bestanden opnieuw.',
    'alert.future_backup': v => `Deze backup is gemaakt met een nieuwere versie (v${v}). Toch proberen te restoren?`,
    'alert.read_backup_error': msg => `Kon het backup-bestand niet lezen: ${msg}`,
    'alert.confirm_replace': 'Weet je zeker dat je je huidige archief wilt vervangen? Dit kan niet ongedaan gemaakt worden.',
    'alert.confirm_delete_collection': 'Verwijder deze collectie? De bestanden blijven behouden (worden niet-gecategoriseerd).',
    'alert.confirm_delete_file': 'Bestand definitief verwijderen?',
    'alert.init_error': msg => `Fout bij initialisatie: ${msg}`,
    'alert.delete_collection_tooltip': 'Verwijder',
    'alert.rename_collection_tooltip': 'Hernoem',
    'toast.collection_name_exists': 'Een collectie met deze naam bestaat al.',
  },
  en: {
    _name: 'English',
    _flag: 'EN',
    'app.subtitle': '3D Archive',
    'app.tagline': 'Browser-based archive for 3D print files',
    'app.loading': 'Loading archive...',
    'header.about': 'About',
    'header.settings': 'Settings',
    'header.settings_data': 'Data',
    'header.settings_language': 'Language',
    'header.restore': 'Restore',
    'header.backup': 'Backup',
    'header.import_folder': 'Import Folder',
    'header.import_files': 'Import Files',
    'sidebar.collections': 'Collections',
    'sidebar.new_collection': '+ New Collection',
    'sidebar.all_files': 'All Files',
    'sidebar.uncategorized': 'Uncategorized',
    'toolbar.search': 'Search name, description or tag...',
    'toolbar.items': item => `${item} ${item === 1 ? 'item' : 'items'}`,
    'empty.no_files_title': 'No Files Yet',
    'empty.no_files_text': 'Import STL, 3MF or resin/SLA files to build your archive. Drag & drop works anywhere in the window.',
    'empty.no_results_title': 'No Results',
    'empty.no_results_search': 'No files match your search.',
    'empty.no_results_collection': 'This collection is empty.',
    'inspector.no_selection': 'No Selection',
    'inspector.no_selection_text': 'Select a file to see details and preview',
    'inspector.loading': 'Loading 3D File',
    'inspector.preview_failed': 'Preview Failed',
    'inspector.preview_failed_text': 'The file could not be rendered. It may be corrupted or an unsupported 3MF variant. The file itself is safely stored and can be downloaded.',
    'inspector.preview_pending_title': 'Preview Not Loaded',
    'inspector.preview_pending_text': '3MF files are not rendered automatically to keep imports fast. Click to manually generate the preview for this file.',
    // SLA / resin
    'sla.sliced': 'Sliced',
    'sla.no_preview': 'No embedded preview',
    'sla.layer_height': 'Layer height',
    'sla.total_height': 'Total height',
    'sla.layer_count': 'Layer count',
    'sla.exposure': 'Exposure',
    'sla.bottom_exposure': 'Bottom exposure',
    'sla.bottom_layers': 'Bottom layers',
    'sla.print_time': 'Print time',
    'sla.resin_volume': 'Resin volume',
    'sla.resolution': 'Resolution',
    'sla.format': 'Format',
    'inspector.generate_preview': 'Generate Preview',
    'inspector.retry_preview': 'Try Again',
    'inspector.preview_pending_card': 'Click to view',
    'inspector.rendering': 'Rendering preview...',
    'inspector.viewer_reset': 'Reset view',
    'inspector.viewer_wireframe': 'Wireframe',
    'inspector.viewer_spin': 'Auto rotation',
    'inspector.move_placeholder': '— Move to collection —',
    'inspector.move_uncategorized': 'Uncategorized',
    'inspector.download': 'Download File',
    'inspector.delete': 'Delete File',
    'inspector.regenerate_preview': 'Regenerate Preview',
    'detail.type': 'Type',
    'detail.file': 'File',
    'detail.triangles': 'Triangles',
    'detail.width': 'Width',
    'detail.height': 'Height',
    'detail.depth': 'Depth',
    'detail.collection': 'Collection',
    'detail.description': 'Description',
    'detail.description_placeholder': 'Add notes, source, print settings or comments...',
    'detail.has_description': 'Has description',
    'detail.saved': 'Saved',
    'detail.cancel': 'Cancel',
    'detail.save': 'Save',
    'status.connected': 'Connected',
    'status.help_link': '? Backup & Sync',
    'status.files': n => `${n} file${n === 1 ? '' : 's'}`,
    'modal.new_col_title': 'New Collection',
    'modal.new_col_placeholder': 'Collection name...',
    'modal.cancel': 'Cancel',
    'modal.create': 'Create',
    'modal.confirm': 'Confirm',
    'folder.title': 'Import Folder',
    'folder.structure': 'Structure',
    'folder.opt_folders_title': 'Subfolders → Collections',
    'folder.opt_folders_desc': 'Each subfolder becomes a separate collection. Files in the root folder go into a collection named after that folder.',
    'folder.opt_single_title': 'All in one collection',
    'folder.opt_single_desc': 'All files are merged under the name of the root folder.',
    'folder.opt_flat_title': 'No collection',
    'folder.opt_flat_desc': 'Import as loose files without a collection.',
    'folder.import': 'Import',
    'folder.summary_files': (n, types) => `${n} file${n === 1 ? '' : 's'} (${types})`,
    'folder.summary_subfolders': n => `${n} subfolder${n === 1 ? '' : 's'}: `,
    'folder.summary_root': n => `${n} file${n === 1 ? '' : 's'} in root folder`,
    'progress.importing': 'Importing Files',
    'progress.backup': 'Creating Backup',
    'progress.restore': 'Restoring Backup',
    'progress.writing': 'Writing file...',
    'progress.done': 'Done',
    'help.title': 'Backup & Synchronization',
    'help.backup_title': 'Creating a backup',
    'help.backup_text': 'Click <strong>Backup</strong> in the header. A <strong>.forge</strong> file is downloaded with all your 3D files (STL, 3MF and resin/SLA), collections, descriptions and metadata. This is a complete copy of your archive.',
    'help.restore_title': 'Restoring a backup',
    'help.restore_text': 'Click <strong>Restore</strong> and select a <strong>.forge</strong> file. You can choose to <strong>replace</strong> your current archive or <strong>merge</strong> the backup alongside it.',
    'help.multi_pc_title': 'Using on multiple PCs',
    'help.multi_pc_text': 'The program stores data locally per browser per PC. To share your archive between devices you can use a cloud folder (Dropbox, OneDrive, Google Drive, iCloud):',
    'help.step1': 'Place <strong>stl-organizer.html</strong> in a synced cloud folder.',
    'help.step2': 'On each PC: open the file from that cloud folder (double-click).',
    'help.step3': 'Build your archive (import STL, 3MF or resin/SLA and create collections).',
    'help.step4': 'Make a <strong>Backup</strong> and save it in the same cloud folder (or subfolder).',
    'help.step5': 'On another PC: open the same HTML file and do <strong>Restore</strong> with that backup.',
    'help.warning': "<strong>Note:</strong> don't open the HTML file simultaneously on multiple PCs with edits. Work on one PC, make a backup, then restore on the other. Otherwise your versions will get out of sync.",
    'help.strategy_title': 'Backup strategy',
    'help.strategy_text': 'Make regular backups. The <strong>.forge</strong> files contain your complete 3D data, so the file size is comparable to the sum of your models. A date is automatically included in the filename.',
    'help.understood': 'Got it',
    'restore.title': 'Restore Backup',
    'restore.how': 'How to process?',
    'restore.merge_title': 'Merge',
    'restore.merge_desc': 'Backup is added to your current archive. Existing items are kept.',
    'restore.replace_title': 'Replace',
    'restore.replace_desc': 'Removes all your current files and collections. Cannot be undone.',
    'restore.action': 'Restore',
    'restore.summary_date': date => `Backup from ${date}`,
    'restore.summary_files': n => `${n} 3D file${n === 1 ? '' : 's'}`,
    'restore.summary_collections': n => `${n} collection${n === 1 ? '' : 's'}`,
    'restore.summary_total': size => `Total: ${size}`,
    'about.tagline': 'Browser-based archive for 3D print files',
    'about.version': 'Version',
    'about.release': 'Release',
    'about.supports': 'Supports',
    'about.engine': 'Engine',
    'about.storage': 'Storage',
    'about.storage_value': 'IndexedDB (local)',
    'about.author': 'Made by',
    'about.features': 'Features',
    'about.feat_preview': '3D Preview',
    'about.feat_collections': 'Collections',
    'about.feat_folders': 'Folder Import',
    'about.feat_descriptions': 'Descriptions',
    'about.feat_backup': 'Full Backup',
    'about.feat_dragdrop': 'Drag & Drop',
    'about.danger_zone': 'Danger Zone',
    'about.danger_text': 'Removes all files, collections and descriptions from the library. Your <strong>original files on disk remain intact</strong> — 3DLens only deletes the copies from its own archive. Make a backup first if you want to recover.',
    'about.clear': 'Clear Library',
    'about.close': 'Close',
    'clear.title': 'Clear Library?',
    'clear.summary': 'Will be removed:',
    'clear.warning_main': 'This removes <strong>everything</strong> from the 3DLens archive: files, collections, descriptions and thumbnails.',
    'clear.warning_safe': 'Your <strong>original 3D files on disk remain untouched</strong>. 3DLens works with copies in its own database — the folder you imported them from will not change.',
    'clear.confirm_prompt': 'Type <strong>CLEAR</strong> to confirm',
    'clear.confirm_word': 'CLEAR',
    'clear.confirm_btn': 'Permanently Clear',
    'drop.message': 'Release to import',
    'toast.empty_lib': 'The library is already empty.',
    'toast.no_files_found': 'No supported 3D files found (STL, 3MF, or resin/SLA).',
    'toast.no_files_in_folder': 'No supported 3D files found in this folder.',
    'toast.no_files_in_drop': 'No supported 3D files found in the dropped folders.',
    'toast.lib_cleared': (files, cols) => `Library cleared: ${files} file${files === 1 ? '' : 's'} and ${cols} collection${cols === 1 ? '' : 's'} removed.`,
    'toast.imported': n => `${n} file${n === 1 ? '' : 's'} imported.`,
    'toast.imported_with_skipped': (imp, skip, examples, more) => `${imp} imported, ${skip} skipped (already present): ${examples}${more}`,
    'toast.all_skipped': n => `All ${n} file${n === 1 ? '' : 's'} ${n === 1 ? 'was' : 'were'} already in your archive. Nothing imported.`,
    'toast.skipped_more': n => ` (and ${n} more)`,
    'toast.restore_done': (files, cols) => `Restore complete: ${files} file${files === 1 ? '' : 's'} and ${cols} collection${cols === 1 ? '' : 's'} restored.`,
    'toast.restore_dups': n => ` ${n} duplicate${n === 1 ? '' : 's'} skipped.`,
    'alert.empty_backup': 'Nothing to back up — your archive is empty.',
    'alert.invalid_backup': 'This does not appear to be a valid 3DLens backup file. Check that you selected the right .forge file.',
    'alert.old_backup': 'This is an old metadata-only export (without file data). Since this version, Backup creates full backups including files. Re-import the files.',
    'alert.future_backup': v => `This backup was made with a newer version (v${v}). Try to restore anyway?`,
    'alert.read_backup_error': msg => `Could not read the backup file: ${msg}`,
    'alert.confirm_replace': 'Are you sure you want to replace your current archive? This cannot be undone.',
    'alert.confirm_delete_collection': 'Delete this collection? The files will be kept (uncategorized).',
    'alert.confirm_delete_file': 'Permanently delete file?',
    'alert.init_error': msg => `Initialization error: ${msg}`,
    'alert.delete_collection_tooltip': 'Delete',
    'alert.rename_collection_tooltip': 'Rename',
    'toast.collection_name_exists': 'A collection with this name already exists.',
  },
  de: {
    _name: 'Deutsch',
    _flag: 'DE',
    'app.subtitle': '3D-Archiv',
    'app.tagline': 'Browser-basiertes Archiv für 3D-Druckdateien',
    'app.loading': 'Archiv wird geladen...',
    'header.about': 'Über',
    'header.settings': 'Einstellungen',
    'header.settings_data': 'Daten',
    'header.settings_language': 'Sprache',
    'header.restore': 'Wiederherstellen',
    'header.backup': 'Sicherung',
    'header.import_folder': 'Ordner Importieren',
    'header.import_files': 'Dateien Importieren',
    'sidebar.collections': 'Sammlungen',
    'sidebar.new_collection': '+ Neue Sammlung',
    'sidebar.all_files': 'Alle Dateien',
    'sidebar.uncategorized': 'Nicht Kategorisiert',
    'toolbar.search': 'Name oder Beschreibung suchen...',
    'toolbar.items': item => `${item} ${item === 1 ? 'Eintrag' : 'Einträge'}`,
    'empty.no_files_title': 'Noch Keine Dateien',
    'empty.no_files_text': 'Importiere STL-, 3MF- oder Resin/SLA-Dateien, um dein Archiv aufzubauen. Drag & Drop funktioniert überall im Fenster.',
    'empty.no_results_title': 'Keine Ergebnisse',
    'empty.no_results_search': 'Keine Dateien für deine Suche gefunden.',
    'empty.no_results_collection': 'Diese Sammlung ist leer.',
    'inspector.no_selection': 'Keine Auswahl',
    'inspector.no_selection_text': 'Wähle eine Datei, um Details und Vorschau zu sehen',
    'inspector.loading': '3D-Datei Wird Geladen',
    'inspector.preview_failed': 'Vorschau Fehlgeschlagen',
    'inspector.preview_failed_text': 'Die Datei konnte nicht gerendert werden. Sie ist möglicherweise beschädigt oder eine nicht unterstützte 3MF-Variante. Die Datei selbst ist sicher gespeichert und kann heruntergeladen werden.',
    'inspector.preview_pending_title': 'Vorschau Nicht Geladen',
    'inspector.preview_pending_text': '3MF-Dateien werden nicht automatisch gerendert, um den Import schnell zu halten. Klicke, um die Vorschau für diese Datei manuell zu generieren.',
    // SLA / resin
    'sla.sliced': 'Gesliced',
    'sla.no_preview': 'Keine Vorschau eingebettet',
    'sla.layer_height': 'Schichtdicke',
    'sla.total_height': 'Gesamthöhe',
    'sla.layer_count': 'Schichtanzahl',
    'sla.exposure': 'Belichtung',
    'sla.bottom_exposure': 'Boden-Belichtung',
    'sla.bottom_layers': 'Bodenschichten',
    'sla.print_time': 'Druckzeit',
    'sla.resin_volume': 'Harz-Volumen',
    'sla.resolution': 'Auflösung',
    'sla.format': 'Format',
    'inspector.generate_preview': 'Vorschau Erstellen',
    'inspector.retry_preview': 'Erneut Versuchen',
    'inspector.preview_pending_card': 'Klicken zum Anzeigen',
    'inspector.rendering': 'Vorschau wird erstellt...',
    'inspector.viewer_reset': 'Ansicht zurücksetzen',
    'inspector.viewer_wireframe': 'Drahtgitter',
    'inspector.viewer_spin': 'Auto-Rotation',
    'inspector.move_placeholder': '— In Sammlung verschieben —',
    'inspector.move_uncategorized': 'Nicht Kategorisiert',
    'inspector.download': 'Datei Herunterladen',
    'inspector.delete': 'Datei Löschen',
    'inspector.regenerate_preview': 'Vorschau Neu Erstellen',
    'detail.type': 'Typ',
    'detail.file': 'Datei',
    'detail.triangles': 'Dreiecke',
    'detail.width': 'Breite',
    'detail.height': 'Höhe',
    'detail.depth': 'Tiefe',
    'detail.collection': 'Sammlung',
    'detail.description': 'Beschreibung',
    'detail.description_placeholder': 'Notizen, Quelle, Druckeinstellungen oder Bemerkungen hinzufügen...',
    'detail.has_description': 'Hat Beschreibung',
    'detail.saved': 'Gespeichert',
    'detail.cancel': 'Abbrechen',
    'detail.save': 'Speichern',
    'status.connected': 'Verbunden',
    'status.help_link': '? Backup & Sync',
    'status.files': n => `${n} ${n === 1 ? 'Datei' : 'Dateien'}`,
    'modal.new_col_title': 'Neue Sammlung',
    'modal.new_col_placeholder': 'Name der Sammlung...',
    'modal.cancel': 'Abbrechen',
    'modal.create': 'Erstellen',
    'modal.confirm': 'Bestätigen',
    'folder.title': 'Ordner Importieren',
    'folder.structure': 'Struktur',
    'folder.opt_folders_title': 'Unterordner → Sammlungen',
    'folder.opt_folders_desc': 'Jeder Unterordner wird zu einer eigenen Sammlung. Dateien im Hauptordner kommen in eine Sammlung mit dem Namen dieses Ordners.',
    'folder.opt_single_title': 'Alles in eine Sammlung',
    'folder.opt_single_desc': 'Alle Dateien werden unter dem Namen des Hauptordners zusammengeführt.',
    'folder.opt_flat_title': 'Keine Sammlung',
    'folder.opt_flat_desc': 'Als einzelne Dateien ohne Sammlung importieren.',
    'folder.import': 'Importieren',
    'folder.summary_files': (n, types) => `${n} ${n === 1 ? 'Datei' : 'Dateien'} (${types})`,
    'folder.summary_subfolders': n => `${n} ${n === 1 ? 'Unterordner' : 'Unterordner'}: `,
    'folder.summary_root': n => `${n} ${n === 1 ? 'Datei' : 'Dateien'} im Hauptordner`,
    'progress.importing': 'Dateien Werden Importiert',
    'progress.backup': 'Sicherung Wird Erstellt',
    'progress.restore': 'Sicherung Wird Wiederhergestellt',
    'progress.writing': 'Datei wird geschrieben...',
    'progress.done': 'Fertig',
    'help.title': 'Sicherung & Synchronisation',
    'help.backup_title': 'Sicherung erstellen',
    'help.backup_text': 'Klicke auf <strong>Sicherung</strong> in der Kopfleiste. Eine <strong>.forge</strong>-Datei wird heruntergeladen mit allen deinen 3D-Dateien (STL, 3MF und Resin/SLA), Sammlungen, Beschreibungen und Metadaten. Dies ist eine vollständige Kopie deines Archivs.',
    'help.restore_title': 'Sicherung wiederherstellen',
    'help.restore_text': 'Klicke auf <strong>Wiederherstellen</strong> und wähle eine <strong>.forge</strong>-Datei. Du kannst wählen, dein aktuelles Archiv zu <strong>ersetzen</strong> oder die Sicherung damit zu <strong>zusammenzuführen</strong>.',
    'help.multi_pc_title': 'Auf mehreren PCs verwenden',
    'help.multi_pc_text': 'Das Programm speichert Daten lokal pro Browser pro PC. Um dein Archiv zwischen Geräten zu teilen, kannst du einen Cloud-Ordner (Dropbox, OneDrive, Google Drive, iCloud) verwenden:',
    'help.step1': 'Lege <strong>stl-organizer.html</strong> in einen synchronisierten Cloud-Ordner.',
    'help.step2': 'Auf jedem PC: Öffne die Datei aus diesem Cloud-Ordner (Doppelklick).',
    'help.step3': 'Baue dein Archiv auf (importiere STL, 3MF oder Resin/SLA und erstelle Sammlungen).',
    'help.step4': 'Erstelle eine <strong>Sicherung</strong> und speichere sie im selben Cloud-Ordner (oder Unterordner).',
    'help.step5': 'Auf einem anderen PC: Öffne dieselbe HTML-Datei und führe <strong>Wiederherstellen</strong> mit dieser Sicherung aus.',
    'help.warning': '<strong>Hinweis:</strong> Öffne die HTML-Datei nicht gleichzeitig auf mehreren PCs mit Änderungen. Arbeite auf einem PC, erstelle eine Sicherung und stelle sie dann auf dem anderen wieder her. Sonst kommen deine Versionen aus dem Sync.',
    'help.strategy_title': 'Sicherungsstrategie',
    'help.strategy_text': 'Erstelle regelmäßig Sicherungen. Die <strong>.forge</strong>-Dateien enthalten deine vollständigen 3D-Daten, die Dateigröße ist also vergleichbar mit der Summe deiner Modelle. Ein Datum ist automatisch im Dateinamen enthalten.',
    'help.understood': 'Verstanden',
    'restore.title': 'Sicherung Wiederherstellen',
    'restore.how': 'Wie verarbeiten?',
    'restore.merge_title': 'Zusammenführen',
    'restore.merge_desc': 'Sicherung wird zu deinem aktuellen Archiv hinzugefügt. Vorhandene Einträge bleiben erhalten.',
    'restore.replace_title': 'Ersetzen',
    'restore.replace_desc': 'Entfernt alle deine aktuellen Dateien und Sammlungen. Kann nicht rückgängig gemacht werden.',
    'restore.action': 'Wiederherstellen',
    'restore.summary_date': date => `Sicherung vom ${date}`,
    'restore.summary_files': n => `${n} 3D-${n === 1 ? 'Datei' : 'Dateien'}`,
    'restore.summary_collections': n => `${n} ${n === 1 ? 'Sammlung' : 'Sammlungen'}`,
    'restore.summary_total': size => `Gesamt: ${size}`,
    'about.tagline': 'Browser-basiertes Archiv für 3D-Druckdateien',
    'about.version': 'Version',
    'about.release': 'Veröffentlichung',
    'about.supports': 'Unterstützt',
    'about.engine': 'Engine',
    'about.storage': 'Speicher',
    'about.storage_value': 'IndexedDB (lokal)',
    'about.author': 'Erstellt von',
    'about.features': 'Funktionen',
    'about.feat_preview': '3D-Vorschau',
    'about.feat_collections': 'Sammlungen',
    'about.feat_folders': 'Ordner-Import',
    'about.feat_descriptions': 'Beschreibungen',
    'about.feat_backup': 'Vollständige Sicherung',
    'about.feat_dragdrop': 'Drag & Drop',
    'about.danger_zone': 'Gefahrenzone',
    'about.danger_text': 'Entfernt alle Dateien, Sammlungen und Beschreibungen aus der Bibliothek. Deine <strong>Originaldateien auf der Festplatte bleiben unberührt</strong> — 3DLens löscht nur die Kopien aus seinem eigenen Archiv. Erstelle zuerst eine Sicherung, falls du wiederherstellen möchtest.',
    'about.clear': 'Bibliothek Leeren',
    'about.close': 'Schließen',
    'clear.title': 'Bibliothek Leeren?',
    'clear.summary': 'Wird entfernt:',
    'clear.warning_main': 'Dies entfernt <strong>alles</strong> aus dem 3DLens-Archiv: Dateien, Sammlungen, Beschreibungen und Vorschaubilder.',
    'clear.warning_safe': 'Deine <strong>Original-3D-Dateien auf der Festplatte bleiben unangetastet</strong>. 3DLens arbeitet mit Kopien in seiner eigenen Datenbank — der Ordner, aus dem du sie importiert hast, ändert sich nicht.',
    'clear.confirm_prompt': 'Tippe <strong>LEEREN</strong> zum Bestätigen',
    'clear.confirm_word': 'LEEREN',
    'clear.confirm_btn': 'Endgültig Leeren',
    'drop.message': 'Loslassen zum Importieren',
    'toast.empty_lib': 'Die Bibliothek ist bereits leer.',
    'toast.no_files_found': 'Keine unterstützten 3D-Dateien gefunden (STL, 3MF oder Resin/SLA).',
    'toast.no_files_in_folder': 'Keine unterstützten 3D-Dateien in diesem Ordner gefunden.',
    'toast.no_files_in_drop': 'Keine unterstützten 3D-Dateien in den abgelegten Ordnern gefunden.',
    'toast.lib_cleared': (files, cols) => `Bibliothek geleert: ${files} ${files === 1 ? 'Datei' : 'Dateien'} und ${cols} ${cols === 1 ? 'Sammlung' : 'Sammlungen'} entfernt.`,
    'toast.imported': n => `${n} ${n === 1 ? 'Datei' : 'Dateien'} importiert.`,
    'toast.imported_with_skipped': (imp, skip, examples, more) => `${imp} importiert, ${skip} übersprungen (bereits vorhanden): ${examples}${more}`,
    'toast.all_skipped': n => `Alle ${n} ${n === 1 ? 'Datei war' : 'Dateien waren'} bereits in deinem Archiv. Nichts importiert.`,
    'toast.skipped_more': n => ` (und ${n} weitere)`,
    'toast.restore_done': (files, cols) => `Wiederherstellung abgeschlossen: ${files} ${files === 1 ? 'Datei' : 'Dateien'} und ${cols} ${cols === 1 ? 'Sammlung' : 'Sammlungen'} wiederhergestellt.`,
    'toast.restore_dups': n => ` ${n} ${n === 1 ? 'Duplikat' : 'Duplikate'} übersprungen.`,
    'alert.empty_backup': 'Nichts zu sichern — dein Archiv ist leer.',
    'alert.invalid_backup': 'Dies scheint keine gültige 3DLens-Sicherungsdatei zu sein. Überprüfe, ob du die richtige .forge-Datei ausgewählt hast.',
    'alert.old_backup': 'Dies ist ein alter Metadaten-Export (ohne Dateidaten). Seit dieser Version erstellt Sicherung vollständige Sicherungen einschließlich Dateien. Importiere die Dateien neu.',
    'alert.future_backup': v => `Diese Sicherung wurde mit einer neueren Version (v${v}) erstellt. Trotzdem versuchen wiederherzustellen?`,
    'alert.read_backup_error': msg => `Sicherungsdatei konnte nicht gelesen werden: ${msg}`,
    'alert.confirm_replace': 'Bist du sicher, dass du dein aktuelles Archiv ersetzen möchtest? Dies kann nicht rückgängig gemacht werden.',
    'alert.confirm_delete_collection': 'Diese Sammlung löschen? Die Dateien bleiben erhalten (werden nicht-kategorisiert).',
    'alert.confirm_delete_file': 'Datei endgültig löschen?',
    'alert.init_error': msg => `Initialisierungsfehler: ${msg}`,
    'alert.delete_collection_tooltip': 'Löschen',
    'alert.rename_collection_tooltip': 'Umbenennen',
    'toast.collection_name_exists': 'Eine Sammlung mit diesem Namen existiert bereits.',
  },
  es: {
    _name: 'Español',
    _flag: 'ES',
    'app.subtitle': 'Archivo 3D',
    'app.tagline': 'Archivo en navegador para archivos de impresión 3D',
    'app.loading': 'Cargando archivo...',
    'header.about': 'Acerca de',
    'header.settings': 'Ajustes',
    'header.settings_data': 'Datos',
    'header.settings_language': 'Idioma',
    'header.restore': 'Restaurar',
    'header.backup': 'Copia',
    'header.import_folder': 'Importar Carpeta',
    'header.import_files': 'Importar Archivos',
    'sidebar.collections': 'Colecciones',
    'sidebar.new_collection': '+ Nueva Colección',
    'sidebar.all_files': 'Todos los Archivos',
    'sidebar.uncategorized': 'Sin Categoría',
    'toolbar.search': 'Buscar nombre o descripción...',
    'toolbar.items': item => `${item} ${item === 1 ? 'elemento' : 'elementos'}`,
    'empty.no_files_title': 'Sin Archivos Aún',
    'empty.no_files_text': 'Importa archivos STL, 3MF o resina/SLA para construir tu archivo. Arrastrar y soltar funciona en cualquier parte de la ventana.',
    'empty.no_results_title': 'Sin Resultados',
    'empty.no_results_search': 'No se encontraron archivos para tu búsqueda.',
    'empty.no_results_collection': 'Esta colección está vacía.',
    'inspector.no_selection': 'Sin Selección',
    'inspector.no_selection_text': 'Selecciona un archivo para ver detalles y vista previa',
    'inspector.loading': 'Cargando Archivo 3D',
    'inspector.preview_failed': 'Vista Previa Falló',
    'inspector.preview_failed_text': 'No se pudo renderizar el archivo. Puede estar dañado o ser una variante 3MF no compatible. El archivo se ha guardado de forma segura y se puede descargar.',
    'inspector.preview_pending_title': 'Vista Previa No Cargada',
    'inspector.preview_pending_text': 'Los archivos 3MF no se renderizan automáticamente para mantener la importación rápida. Haz clic para generar la vista previa manualmente.',
    // SLA / resin
    'sla.sliced': 'Laminado',
    'sla.no_preview': 'Sin vista previa incrustada',
    'sla.layer_height': 'Altura de capa',
    'sla.total_height': 'Altura total',
    'sla.layer_count': 'Número de capas',
    'sla.exposure': 'Exposición',
    'sla.bottom_exposure': 'Exposición inferior',
    'sla.bottom_layers': 'Capas inferiores',
    'sla.print_time': 'Tiempo de impresión',
    'sla.resin_volume': 'Volumen de resina',
    'sla.resolution': 'Resolución',
    'sla.format': 'Formato',
    'inspector.generate_preview': 'Generar Vista Previa',
    'inspector.retry_preview': 'Reintentar',
    'inspector.preview_pending_card': 'Clic para ver',
    'inspector.rendering': 'Renderizando vista previa...',
    'inspector.viewer_reset': 'Restablecer vista',
    'inspector.viewer_wireframe': 'Estructura',
    'inspector.viewer_spin': 'Rotación automática',
    'inspector.move_placeholder': '— Mover a colección —',
    'inspector.move_uncategorized': 'Sin Categoría',
    'inspector.download': 'Descargar Archivo',
    'inspector.delete': 'Eliminar Archivo',
    'inspector.regenerate_preview': 'Regenerar Vista Previa',
    'detail.type': 'Tipo',
    'detail.file': 'Archivo',
    'detail.triangles': 'Triángulos',
    'detail.width': 'Ancho',
    'detail.height': 'Alto',
    'detail.depth': 'Profundo',
    'detail.collection': 'Colección',
    'detail.description': 'Descripción',
    'detail.description_placeholder': 'Añade notas, fuente, ajustes de impresión o comentarios...',
    'detail.has_description': 'Tiene descripción',
    'detail.saved': 'Guardado',
    'detail.cancel': 'Cancelar',
    'detail.save': 'Guardar',
    'status.connected': 'Conectado',
    'status.help_link': '? Copia y Sinc.',
    'status.files': n => `${n} archivo${n === 1 ? '' : 's'}`,
    'modal.new_col_title': 'Nueva Colección',
    'modal.new_col_placeholder': 'Nombre de colección...',
    'modal.cancel': 'Cancelar',
    'modal.create': 'Crear',
    'modal.confirm': 'Confirmar',
    'folder.title': 'Importar Carpeta',
    'folder.structure': 'Estructura',
    'folder.opt_folders_title': 'Subcarpetas → Colecciones',
    'folder.opt_folders_desc': 'Cada subcarpeta se convierte en una colección separada. Los archivos en la carpeta raíz van a una colección con el nombre de esa carpeta.',
    'folder.opt_single_title': 'Todo en una colección',
    'folder.opt_single_desc': 'Todos los archivos se combinan bajo el nombre de la carpeta raíz.',
    'folder.opt_flat_title': 'Sin colección',
    'folder.opt_flat_desc': 'Importar como archivos sueltos sin colección.',
    'folder.import': 'Importar',
    'folder.summary_files': (n, types) => `${n} archivo${n === 1 ? '' : 's'} (${types})`,
    'folder.summary_subfolders': n => `${n} subcarpeta${n === 1 ? '' : 's'}: `,
    'folder.summary_root': n => `${n} archivo${n === 1 ? '' : 's'} en carpeta raíz`,
    'progress.importing': 'Importando Archivos',
    'progress.backup': 'Creando Copia',
    'progress.restore': 'Restaurando Copia',
    'progress.writing': 'Escribiendo archivo...',
    'progress.done': 'Listo',
    'help.title': 'Copia y Sincronización',
    'help.backup_title': 'Crear copia',
    'help.backup_text': 'Haz clic en <strong>Copia</strong> en la cabecera. Se descarga un archivo <strong>.forge</strong> con todos tus archivos 3D (STL, 3MF y resina/SLA), colecciones, descripciones y metadatos. Es una copia completa de tu archivo.',
    'help.restore_title': 'Restaurar copia',
    'help.restore_text': 'Haz clic en <strong>Restaurar</strong> y selecciona un archivo <strong>.forge</strong>. Puedes elegir <strong>reemplazar</strong> tu archivo actual o <strong>combinar</strong> la copia junto a él.',
    'help.multi_pc_title': 'Usar en varios PCs',
    'help.multi_pc_text': 'El programa guarda los datos localmente por navegador y por PC. Para compartir tu archivo entre dispositivos puedes usar una carpeta en la nube (Dropbox, OneDrive, Google Drive, iCloud):',
    'help.step1': 'Coloca <strong>stl-organizer.html</strong> en una carpeta sincronizada de la nube.',
    'help.step2': 'En cada PC: abre el archivo desde esa carpeta de la nube (doble clic).',
    'help.step3': 'Construye tu archivo (importa STL, 3MF o resina/SLA y crea colecciones).',
    'help.step4': 'Crea una <strong>Copia</strong> y guárdala en la misma carpeta de la nube (o subcarpeta).',
    'help.step5': 'En otro PC: abre el mismo archivo HTML y haz <strong>Restaurar</strong> con esa copia.',
    'help.warning': '<strong>Atención:</strong> no abras el archivo HTML simultáneamente en varios PCs con cambios. Trabaja en un PC, crea una copia, y luego restaura en el otro. De lo contrario tus versiones se desincronizarán.',
    'help.strategy_title': 'Estrategia de copia',
    'help.strategy_text': 'Haz copias de seguridad regularmente. Los archivos <strong>.forge</strong> contienen tus datos 3D completos, así que el tamaño es comparable a la suma de tus modelos. La fecha se incluye automáticamente en el nombre del archivo.',
    'help.understood': 'Entendido',
    'restore.title': 'Restaurar Copia',
    'restore.how': '¿Cómo procesar?',
    'restore.merge_title': 'Combinar',
    'restore.merge_desc': 'La copia se añade a tu archivo actual. Los elementos existentes se conservan.',
    'restore.replace_title': 'Reemplazar',
    'restore.replace_desc': 'Elimina todos tus archivos y colecciones actuales. No se puede deshacer.',
    'restore.action': 'Restaurar',
    'restore.summary_date': date => `Copia de ${date}`,
    'restore.summary_files': n => `${n} archivo${n === 1 ? '' : 's'} 3D`,
    'restore.summary_collections': n => `${n} ${n === 1 ? 'colección' : 'colecciones'}`,
    'restore.summary_total': size => `Total: ${size}`,
    'about.tagline': 'Archivo en navegador para archivos de impresión 3D',
    'about.version': 'Versión',
    'about.release': 'Lanzamiento',
    'about.supports': 'Compatible con',
    'about.engine': 'Motor',
    'about.storage': 'Almacenamiento',
    'about.storage_value': 'IndexedDB (local)',
    'about.author': 'Hecho por',
    'about.features': 'Funciones',
    'about.feat_preview': 'Vista 3D',
    'about.feat_collections': 'Colecciones',
    'about.feat_folders': 'Importar Carpetas',
    'about.feat_descriptions': 'Descripciones',
    'about.feat_backup': 'Copia Completa',
    'about.feat_dragdrop': 'Arrastrar y Soltar',
    'about.danger_zone': 'Zona de Peligro',
    'about.danger_text': 'Elimina todos los archivos, colecciones y descripciones de la biblioteca. Tus <strong>archivos originales en disco permanecen intactos</strong> — 3DLens solo borra las copias de su propio archivo. Haz una copia primero si quieres recuperar.',
    'about.clear': 'Vaciar Biblioteca',
    'about.close': 'Cerrar',
    'clear.title': '¿Vaciar Biblioteca?',
    'clear.summary': 'Se eliminará:',
    'clear.warning_main': 'Esto elimina <strong>todo</strong> del archivo 3DLens: archivos, colecciones, descripciones y miniaturas.',
    'clear.warning_safe': 'Tus <strong>archivos 3D originales en disco permanecen intactos</strong>. 3DLens trabaja con copias en su propia base de datos — la carpeta de donde los importaste no cambiará.',
    'clear.confirm_prompt': 'Escribe <strong>VACIAR</strong> para confirmar',
    'clear.confirm_word': 'VACIAR',
    'clear.confirm_btn': 'Vaciar Definitivamente',
    'drop.message': 'Suelta para importar',
    'toast.empty_lib': 'La biblioteca ya está vacía.',
    'toast.no_files_found': 'No se encontraron archivos 3D compatibles (STL, 3MF o resina/SLA).',
    'toast.no_files_in_folder': 'No se encontraron archivos 3D compatibles en esta carpeta.',
    'toast.no_files_in_drop': 'No se encontraron archivos 3D compatibles en las carpetas arrastradas.',
    'toast.lib_cleared': (files, cols) => `Biblioteca vaciada: ${files} archivo${files === 1 ? '' : 's'} y ${cols} ${cols === 1 ? 'colección' : 'colecciones'} eliminados.`,
    'toast.imported': n => `${n} archivo${n === 1 ? '' : 's'} importado${n === 1 ? '' : 's'}.`,
    'toast.imported_with_skipped': (imp, skip, examples, more) => `${imp} importados, ${skip} omitidos (ya presentes): ${examples}${more}`,
    'toast.all_skipped': n => `Los ${n} archivo${n === 1 ? '' : 's'} ya ${n === 1 ? 'estaba' : 'estaban'} en tu archivo. Nada importado.`,
    'toast.skipped_more': n => ` (y ${n} más)`,
    'toast.restore_done': (files, cols) => `Restauración completa: ${files} archivo${files === 1 ? '' : 's'} y ${cols} ${cols === 1 ? 'colección' : 'colecciones'} restaurados.`,
    'toast.restore_dups': n => ` ${n} duplicado${n === 1 ? '' : 's'} omitido${n === 1 ? '' : 's'}.`,
    'alert.empty_backup': 'Nada que copiar — tu archivo está vacío.',
    'alert.invalid_backup': 'No parece ser un archivo de copia 3DLens válido. Comprueba que has seleccionado el archivo .forge correcto.',
    'alert.old_backup': 'Esta es una exportación antigua solo de metadatos (sin datos de archivo). Desde esta versión, la Copia crea copias completas incluyendo archivos. Vuelve a importar los archivos.',
    'alert.future_backup': v => `Esta copia se hizo con una versión más nueva (v${v}). ¿Intentar restaurar de todos modos?`,
    'alert.read_backup_error': msg => `No se pudo leer el archivo de copia: ${msg}`,
    'alert.confirm_replace': '¿Seguro que quieres reemplazar tu archivo actual? Esto no se puede deshacer.',
    'alert.confirm_delete_collection': '¿Eliminar esta colección? Los archivos se conservarán (sin categoría).',
    'alert.confirm_delete_file': '¿Eliminar archivo definitivamente?',
    'alert.init_error': msg => `Error de inicialización: ${msg}`,
    'alert.delete_collection_tooltip': 'Eliminar',
    'alert.rename_collection_tooltip': 'Renombrar',
    'toast.collection_name_exists': 'Ya existe una colección con este nombre.',
  },
  pap: {
    _name: 'Papiamentu',
    _flag: 'PAP',
    'app.subtitle': 'Archivo 3D',
    'app.tagline': 'Archivo den browser pa archivonan di imprenta 3D',
    'app.loading': 'Ta karga archivo...',
    'header.about': 'Tokante',
    'header.settings': 'Konfigurashon',
    'header.settings_data': 'Data',
    'header.settings_language': 'Idioma',
    'header.restore': 'Restorá',
    'header.backup': 'Backup',
    'header.import_folder': 'Importá Karpeta',
    'header.import_files': 'Importá Archivonan',
    'sidebar.collections': 'Kolekshonnan',
    'sidebar.new_collection': '+ Kolekshon Nobo',
    'sidebar.all_files': 'Tur Archivonan',
    'sidebar.uncategorized': 'Sin Kategoria',
    'toolbar.search': 'Buska nòmber òf deskripshon...',
    'toolbar.items': item => `${item} ${item === 1 ? 'item' : 'itemnan'}`,
    'empty.no_files_title': 'Ainda Sin Archivonan',
    'empty.no_files_text': 'Importá archivonan STL, 3MF òf resin/SLA pa traha bo archivo. Lastra & laga kai ta funshoná na henter e bentana.',
    'empty.no_results_title': 'Sin Resultadonan',
    'empty.no_results_search': 'No a haña archivonan pa bo búskeda.',
    'empty.no_results_collection': 'E kolekshon aki ta bashí.',
    'inspector.no_selection': 'Sin Selekshon',
    'inspector.no_selection_text': 'Selektá un archivo pa mira detayes i preview',
    'inspector.loading': 'Ta Karga Archivo 3D',
    'inspector.preview_failed': 'Preview A Faya',
    'inspector.preview_failed_text': 'No por a renderisá e archivo. Por ta ku e ta dañá òf un variante 3MF ku no ta soportá. E archivo mes ta wardá sigur i por wòrdu downlodá.',
    'inspector.preview_pending_title': 'Preview No A Karga',
    'inspector.preview_pending_text': 'Archivonan 3MF no ta render outomátikamente pa keda lihé ku importashon. Klik pa genera e preview manualmente.',
    // SLA / resin
    'sla.sliced': 'Sliced',
    'sla.no_preview': 'No tin preview embed',
    'sla.layer_height': 'Altura di kapa',
    'sla.total_height': 'Altura total',
    'sla.layer_count': 'Kantidat di kapanan',
    'sla.exposure': 'Eksposishon',
    'sla.bottom_exposure': 'Eksposishon di abou',
    'sla.bottom_layers': 'Kapanan di abou',
    'sla.print_time': 'Tempu di prènt',
    'sla.resin_volume': 'Volumen di resin',
    'sla.resolution': 'Resolushon',
    'sla.format': 'Formato',
    'inspector.generate_preview': 'Genera Preview',
    'inspector.retry_preview': 'Probá Atrobe',
    'inspector.preview_pending_card': 'Klik pa wak',
    'inspector.rendering': 'Ta render preview...',
    'inspector.viewer_reset': 'Resetá vista',
    'inspector.viewer_wireframe': 'Wireframe',
    'inspector.viewer_spin': 'Rotashon outomátiko',
    'inspector.move_placeholder': '— Move pa kolekshon —',
    'inspector.move_uncategorized': 'Sin Kategoria',
    'inspector.download': 'Download Archivo',
    'inspector.delete': 'Kita Archivo',
    'inspector.regenerate_preview': 'Genera Preview Atrobe',
    'detail.type': 'Tipo',
    'detail.file': 'Archivo',
    'detail.triangles': 'Triangelnan',
    'detail.width': 'Hanchura',
    'detail.height': 'Haltura',
    'detail.depth': 'Profundidat',
    'detail.collection': 'Kolekshon',
    'detail.description': 'Deskripshon',
    'detail.description_placeholder': 'Agregá nota, fuente, konfigurashon di imprenta òf komentarionan...',
    'detail.has_description': 'Tin deskripshon',
    'detail.saved': 'Wardá',
    'detail.cancel': 'Kanselá',
    'detail.save': 'Wardá',
    'status.connected': 'Konektá',
    'status.help_link': '? Backup & Sync',
    'status.files': n => `${n} ${n === 1 ? 'archivo' : 'archivonan'}`,
    'modal.new_col_title': 'Kolekshon Nobo',
    'modal.new_col_placeholder': 'Nòmber di kolekshon...',
    'modal.cancel': 'Kanselá',
    'modal.create': 'Krea',
    'modal.confirm': 'Konfirmá',
    'folder.title': 'Importá Karpeta',
    'folder.structure': 'Struktura',
    'folder.opt_folders_title': 'Subkarpetanan → Kolekshonnan',
    'folder.opt_folders_desc': 'Tur subkarpeta ta bira un kolekshon separá. Archivonan den e karpeta prinsipal ta bai den un kolekshon ku e nòmber di e karpeta ei.',
    'folder.opt_single_title': 'Tur den un kolekshon',
    'folder.opt_single_desc': 'Tur archivonan ta wòrdu uní bou di e nòmber di e karpeta prinsipal.',
    'folder.opt_flat_title': 'Sin kolekshon',
    'folder.opt_flat_desc': 'Importá komo archivonan suelto sin kolekshon.',
    'folder.import': 'Importá',
    'folder.summary_files': (n, types) => `${n} ${n === 1 ? 'archivo' : 'archivonan'} (${types})`,
    'folder.summary_subfolders': n => `${n} ${n === 1 ? 'subkarpeta' : 'subkarpetanan'}: `,
    'folder.summary_root': n => `${n} ${n === 1 ? 'archivo' : 'archivonan'} den karpeta prinsipal`,
    'progress.importing': 'Ta Importá Archivonan',
    'progress.backup': 'Ta Krea Backup',
    'progress.restore': 'Ta Restorá Backup',
    'progress.writing': 'Ta skirbi archivo...',
    'progress.done': 'Kla',
    'help.title': 'Backup & Sinkronisashon',
    'help.backup_title': 'Krea un backup',
    'help.backup_text': 'Klik <strong>Backup</strong> den e header. Un archivo <strong>.forge</strong> ta wòrdu downlodá ku tur bo archivonan 3D (STL, 3MF i resin/SLA), kolekshonnan, deskripshonnan i metadata. Esaki ta un kopia kompleto di bo archivo.',
    'help.restore_title': 'Restorá un backup',
    'help.restore_text': 'Klik <strong>Restorá</strong> i selektá un archivo <strong>.forge</strong>. Bo por skohe pa <strong>reemplasá</strong> bo archivo aktual òf <strong>uni</strong> e backup banda di dje.',
    'help.multi_pc_title': 'Usa riba múltipel PC',
    'help.multi_pc_text': 'E programa ta warda data lokalmente pa kada browser pa kada PC. Pa kompartí bo archivo entre aparatonan bo por usa un karpeta di nubia (Dropbox, OneDrive, Google Drive, iCloud):',
    'help.step1': 'Pone <strong>stl-organizer.html</strong> den un karpeta di nubia sinkronisá.',
    'help.step2': 'Riba kada PC: habri e archivo for di e karpeta di nubia ei (dòbel klik).',
    'help.step3': 'Traha bo archivo (importá STL, 3MF òf resin/SLA i krea kolekshonnan).',
    'help.step4': 'Krea un <strong>Backup</strong> i wárdele den e mesun karpeta di nubia (òf subkarpeta).',
    'help.step5': 'Riba un otro PC: habri e mesun archivo HTML i hasi <strong>Restorá</strong> ku e backup ei.',
    'help.warning': '<strong>Atenshon:</strong> no habri e archivo HTML simultáneamente riba múltipel PC ku kambionan. Traha riba un PC, krea un backup, i despues restorá riba e otro. Sino bo vershonnan ta bai for di sync.',
    'help.strategy_title': 'Strategia di backup',
    'help.strategy_text': 'Hasi backup regularmente. E archivonan <strong>.forge</strong> ta kontené bo data 3D kompleto, asina ku e tamaño di archivo ta komparabel ku e suma di bo modelnan. E fecha ta wòrdu inkluí outomátikamente den e nòmber di archivo.',
    'help.understood': 'Komprendí',
    'restore.title': 'Restorá Backup',
    'restore.how': 'Kon pa prosesá?',
    'restore.merge_title': 'Uni',
    'restore.merge_desc': 'Backup ta wòrdu agregá na bo archivo aktual. Itemnan eksistente ta keda.',
    'restore.replace_title': 'Reemplasá',
    'restore.replace_desc': 'Ta kita tur bo archivonan i kolekshonnan aktual. No por wòrdu desashí.',
    'restore.action': 'Restorá',
    'restore.summary_date': date => `Backup di ${date}`,
    'restore.summary_files': n => `${n} ${n === 1 ? 'archivo' : 'archivonan'} 3D`,
    'restore.summary_collections': n => `${n} ${n === 1 ? 'kolekshon' : 'kolekshonnan'}`,
    'restore.summary_total': size => `Total: ${size}`,
    'about.tagline': 'Archivo den browser pa archivonan di imprenta 3D',
    'about.version': 'Vershon',
    'about.release': 'Lansamentu',
    'about.supports': 'Ta soportá',
    'about.engine': 'Motor',
    'about.storage': 'Algamasenamentu',
    'about.storage_value': 'IndexedDB (lokal)',
    'about.author': 'Trahá pa',
    'about.features': 'Funshonnan',
    'about.feat_preview': 'Preview 3D',
    'about.feat_collections': 'Kolekshonnan',
    'about.feat_folders': 'Importá Karpeta',
    'about.feat_descriptions': 'Deskripshonnan',
    'about.feat_backup': 'Backup Kompleto',
    'about.feat_dragdrop': 'Lastra & Laga Kai',
    'about.danger_zone': 'Sona Peligroso',
    'about.danger_text': 'Ta kita tur archivonan, kolekshonnan i deskripshonnan for di e biblioteka. Bo <strong>archivonan original riba bo disko ta keda intakto</strong> — 3DLens ta kita solamente e kopianan for di su propio archivo. Krea un backup promé si bo ke rekuperá.',
    'about.clear': 'Bashí Biblioteka',
    'about.close': 'Sera',
    'clear.title': '¿Bashí Biblioteka?',
    'clear.summary': 'Lo wòrdu kitá:',
    'clear.warning_main': 'Esaki ta kita <strong>tur kos</strong> for di e archivo 3DLens: archivonan, kolekshonnan, deskripshonnan i miniaturnan.',
    'clear.warning_safe': 'Bo <strong>archivonan original 3D riba disko ta keda intakto</strong>. 3DLens ta traha ku kopianan den su propio base di datos — e karpeta for di unda bo a importá nan no ta kambia.',
    'clear.confirm_prompt': 'Skibi <strong>BASHÍ</strong> pa konfirmá',
    'clear.confirm_word': 'BASHÍ',
    'clear.confirm_btn': 'Bashí Definitivamente',
    'drop.message': 'Laga kai pa importá',
    'toast.empty_lib': 'E biblioteka ta bashí kaba.',
    'toast.no_files_found': 'No a haña archivonan 3D ku ta sostené (STL, 3MF, òf resin/SLA).',
    'toast.no_files_in_folder': 'No a haña archivonan 3D ku ta sostené den e karpeta aki.',
    'toast.no_files_in_drop': 'No a haña archivonan 3D ku ta sostené den e karpetanan lastrá.',
    'toast.lib_cleared': (files, cols) => `Biblioteka bashí: ${files} ${files === 1 ? 'archivo' : 'archivonan'} i ${cols} ${cols === 1 ? 'kolekshon' : 'kolekshonnan'} kitá.`,
    'toast.imported': n => `${n} ${n === 1 ? 'archivo' : 'archivonan'} importá.`,
    'toast.imported_with_skipped': (imp, skip, examples, more) => `${imp} importá, ${skip} pasá over (ya tabata aki): ${examples}${more}`,
    'toast.all_skipped': n => `Tur ${n} ${n === 1 ? 'archivo tabata' : 'archivonan tabata'} kaba den bo archivo. Nada importá.`,
    'toast.skipped_more': n => ` (i ${n} mas)`,
    'toast.restore_done': (files, cols) => `Restorashon kompleto: ${files} ${files === 1 ? 'archivo' : 'archivonan'} i ${cols} ${cols === 1 ? 'kolekshon' : 'kolekshonnan'} restorá.`,
    'toast.restore_dups': n => ` ${n} ${n === 1 ? 'duplikado' : 'duplikadonan'} pasá over.`,
    'alert.empty_backup': 'Nada pa hasi backup — bo archivo ta bashí.',
    'alert.invalid_backup': 'Esaki no ta parse un archivo backup di 3DLens válido. Verifiká si bo a selektá e archivo .forge korekto.',
    'alert.old_backup': 'Esaki ta un eksportashon bieu di metadata so (sin data di archivo). Desde e vershon aki, Backup ta krea backup kompleto inkluí archivonan. Importá e archivonan di nobo.',
    'alert.future_backup': v => `E backup aki a wòrdu trahá ku un vershon mas nobo (v${v}). Probá restorá toch?`,
    'alert.read_backup_error': msg => `No por a lesa e archivo backup: ${msg}`,
    'alert.confirm_replace': '¿Bo ta sigur ku bo ke reemplasá bo archivo aktual? Esaki no por wòrdu desashí.',
    'alert.confirm_delete_collection': '¿Kita e kolekshon aki? E archivonan ta keda (lo bira sin kategoria).',
    'alert.confirm_delete_file': '¿Kita archivo definitivamente?',
    'alert.init_error': msg => `Eror di inisialisashon: ${msg}`,
    'alert.delete_collection_tooltip': 'Kita',
    'alert.rename_collection_tooltip': 'Kambia Nòmber',
    'toast.collection_name_exists': 'Tin kaba un kolekshon ku e nòmber aki.',
  },
};

let currentLang = 'nl';


// Server edition: replace obsolete browser-local help in every supported language.
Object.assign(TRANSLATIONS.nl, {"about.storage_value": "MySQL + privébestanden op de server", "help.multi_pc_text": "Je archief staat centraal op de server. Log op elk apparaat in met hetzelfde account.", "help.step1": "Open het HTTPS-adres van je 3DLens-website.", "help.step2": "Log in met je eigenaarsaccount.", "help.step3": "Importeer je 3D-bestanden en maak collecties.", "help.step4": "Wijzigingen worden op de server opgeslagen. Vernieuw de pagina op je andere apparaat.", "help.step5": "Gebruik Backup voor een extra kopie buiten je hosting.", "help.warning": "<strong>Let op:</strong> houd je wachtwoord privé en werk bij voorkeur in één tabblad tegelijk. Vernieuw voor je op een ander apparaat wijzigingen maakt.", "help.restore_text": "Klik op <strong>Restore</strong> en selecteer een <strong>.forge</strong>-bestand. De backup wordt samengevoegd met je serverarchief; bestaande bestanden blijven behouden."});
Object.assign(TRANSLATIONS.en, {"about.storage_value": "MySQL + private server files", "help.multi_pc_text": "Your archive is stored on the server. Sign in on each device with the same account.", "help.step1": "Open the HTTPS address of your 3DLens website.", "help.step2": "Sign in with your owner account.", "help.step3": "Import your 3D files and create collections.", "help.step4": "Changes are saved on the server. Refresh on your other device.", "help.step5": "Use Backup for an extra copy outside your hosting.", "help.warning": "<strong>Note:</strong> keep your password private and prefer one editing tab at a time. Refresh before editing on another device.", "help.restore_text": "Click <strong>Restore</strong> and select a <strong>.forge</strong> file. The backup is merged into your server archive; existing files are kept."});
Object.assign(TRANSLATIONS.de, {"about.storage_value": "MySQL + private Serverdateien", "help.multi_pc_text": "Dein Archiv liegt auf dem Server. Melde dich auf jedem Gerät mit demselben Konto an.", "help.step1": "Öffne die HTTPS-Adresse deiner 3DLens-Website.", "help.step2": "Melde dich mit deinem Eigentümerkonto an.", "help.step3": "Importiere deine 3D-Dateien und erstelle Sammlungen.", "help.step4": "Änderungen werden auf dem Server gespeichert. Lade die Seite am anderen Gerät neu.", "help.step5": "Nutze die Sicherung für eine zusätzliche Kopie außerhalb deines Hostings.", "help.warning": "<strong>Hinweis:</strong> Halte dein Passwort geheim und bearbeite das Archiv möglichst in einem Tab. Lade die Seite vor Änderungen auf einem anderen Gerät neu.", "help.restore_text": "Wähle unter <strong>Wiederherstellen</strong> eine <strong>.forge</strong>-Datei. Die Sicherung wird zusammengeführt; vorhandene Dateien bleiben erhalten."});
Object.assign(TRANSLATIONS.es, {"about.storage_value": "MySQL + archivos privados del servidor", "help.multi_pc_text": "Tu archivo se guarda en el servidor. Inicia sesión en cada dispositivo con la misma cuenta.", "help.step1": "Abre la dirección HTTPS de tu sitio 3DLens.", "help.step2": "Inicia sesión con tu cuenta de propietario.", "help.step3": "Importa tus archivos 3D y crea colecciones.", "help.step4": "Los cambios se guardan en el servidor. Actualiza la página en el otro dispositivo.", "help.step5": "Usa Copia para guardar otra copia fuera del hosting.", "help.warning": "<strong>Atención:</strong> mantén tu contraseña privada y edita preferiblemente en una sola pestaña. Actualiza antes de editar desde otro dispositivo.", "help.restore_text": "Selecciona un archivo <strong>.forge</strong> en <strong>Restaurar</strong>. La copia se combina con tu archivo del servidor y se conservan los archivos existentes."});
Object.assign(TRANSLATIONS.pap, {"about.storage_value": "MySQL + archivonan privá riba servidor", "help.multi_pc_text": "Bo archivo ta riba e servidor. Inisiá seshon riba kada aparato ku e mesun kuenta.", "help.step1": "Habri e adrès HTTPS di bo wèpsait 3DLens.", "help.step2": "Inisiá seshon ku bo kuenta di doño.", "help.step3": "Importá bo archivonan 3D i krea kolekshonnan.", "help.step4": "Kambionan ta wòrdu wardá riba servidor. Refreská e página riba e otro aparato.", "help.step5": "Usa Backup pa un kopia adishonal pafó di bo hosting.", "help.warning": "<strong>Atenshon:</strong> tene bo kontraseña privá i traha preferiblemente den un tab. Refreská promé ku bo hasi kambionan riba un otro aparato.", "help.restore_text": "Skohe un archivo <strong>.forge</strong> ku <strong>Restorá</strong>. E backup ta wòrdu uni ku bo archivo riba servidor; archivonan eksistente ta keda."});

for (const [lang, labels] of Object.entries({
  nl: ['Selecteer {name}', '{n} geselecteerd', 'Doelcollectie', 'Verplaatsen', 'Verwijderen', 'Deselecteren', 'Alles in beeld', 'Vink modellen aan of gebruik Ctrl+klik (Mac: Cmd+klik).', 'Bezig met opslaan…', '{n} bestanden verwerkt.', '{n} geselecteerde bestanden definitief verwijderen? De bestanden worden ook uit de gekoppelde opslag verwijderd. Dit kan niet ongedaan worden gemaakt.', '{done} van {total} verwerkt. Gestopt bij een fout; de resterende selectie kun je opnieuw proberen.'],
  en: ['Select {name}', '{n} selected', 'Destination collection', 'Move', 'Delete', 'Clear selection', 'All visible', 'Use checkboxes or Ctrl+click (Mac: Cmd+click).', 'Saving…', '{n} files processed.', 'Permanently delete {n} selected files? Files are also removed from connected storage. This cannot be undone.', '{done} of {total} processed. Stopped on an error; you can retry the remaining selection.'],
  de: ['{name} auswählen', '{n} ausgewählt', 'Zielsammlung', 'Verschieben', 'Löschen', 'Auswahl aufheben', 'Alle sichtbaren', 'Kontrollkästchen oder Strg+Klick (Mac: Cmd+Klick).', 'Wird gespeichert…', '{n} Dateien verarbeitet.', '{n} ausgewählte Dateien endgültig löschen? Sie werden auch aus dem verbundenen Speicher entfernt. Dies kann nicht rückgängig gemacht werden.', '{done} von {total} verarbeitet. Bei einem Fehler gestoppt; die übrige Auswahl kann erneut versucht werden.'],
  es: ['Seleccionar {name}', '{n} seleccionados', 'Colección de destino', 'Mover', 'Eliminar', 'Desmarcar', 'Todos los visibles', 'Usa las casillas o Ctrl+clic (Mac: Cmd+clic).', 'Guardando…', '{n} archivos procesados.', '¿Eliminar permanentemente {n} archivos seleccionados? También se eliminan del almacenamiento conectado. No se puede deshacer.', '{done} de {total} procesados. Se detuvo por un error; puedes reintentar con los restantes.'],
  pap: ['Skohe {name}', '{n} selektá', 'Kolekshon di destino', 'Move', 'Eliminá', 'Kita selekshon', 'Tur visibel', 'Usa e vinkinán òf Ctrl+klik (Mac: Cmd+klik).', 'Ta warda…', '{n} archivo prosesá.', 'Eliminá {n} archivo selektá permanentemente? Nan ta wòrdu kitá tambe for di e almacen konektá. Esaki no por wòrdu deshasí.', '{done} di {total} prosesá. A para pa un eror; por purba e selekshon restante atrobe.'],
})) {
  const keys = ['select_file','count','target','move','delete','clear','all','hint','busy','done','confirm_delete','partial'];
  keys.forEach((key, i) => {
    TRANSLATIONS[lang]['bulk.' + key] = key === 'select_file' ? name => labels[i].replace('{name}', name)
      : key === 'partial' ? (done, total) => labels[i].replace('{done}', done).replace('{total}', total)
      : ['count','done','confirm_delete'].includes(key) ? n => labels[i].replace('{n}', n) : labels[i];
  });
}

for (const [lang, label] of Object.entries({nl:'Wijzigingen per versie ↗',en:'Changes by version ↗',de:'Änderungen pro Version ↗',es:'Cambios por versión ↗',pap:'Kambionan pa vershon ↗'})) TRANSLATIONS[lang]['about.changelog'] = label;

function detectLanguage() {
  const stored = localStorage.getItem('3dlens_lang');
  if (stored && TRANSLATIONS[stored]) return stored;
  const browser = (navigator.language || 'nl').toLowerCase();
  if (browser.startsWith('en')) return 'en';
  if (browser.startsWith('de')) return 'de';
  if (browser.startsWith('es')) return 'es';
  if (browser.startsWith('pap')) return 'pap';
  return 'nl';
}

function t(key, ...args) {
  const dict = TRANSLATIONS[currentLang] || TRANSLATIONS.nl;
  let val = dict[key];
  if (val === undefined) val = TRANSLATIONS.nl[key];
  if (typeof val === 'function') return val(...args);
  return val ?? key;
}

function setLanguage(lang) {
  if (!TRANSLATIONS[lang]) return;
  currentLang = lang;
  localStorage.setItem('3dlens_lang', lang);
  applyStaticTranslations();
  // Re-render dynamic content if state is loaded
  if (typeof state !== 'undefined' && state.files !== undefined) render();
}

// Apply translations to static (non-rendered) DOM elements
function applyStaticTranslations() {
  document.title = `3DLens — ${t('app.subtitle')}`;
  // Header
  setText('brand-sub-text', `${t('app.subtitle')} // v${document.querySelector('meta[name="app-version"]').content}`);
  setText('about-changelog', t('about.changelog'));
  setText('btn-about-text', t('header.about'));
  setText('btn-settings-text', t('header.settings'));
  setText('settings-data-label', t('header.settings_data'));
  setText('settings-language-label', t('header.settings_language'));
  setText('btn-restore-text', t('header.restore'));
  setText('btn-backup-text', t('header.backup'));
  setText('btn-import-folder-text', t('header.import_folder'));
  setText('btn-import-text', t('header.import_files'));
  // Sidebar
  setText('sidebar-collections-label', t('sidebar.collections'));
  setText('btn-new-collection', t('sidebar.new_collection'));
  // Search
  const search = document.getElementById('search');
  if (search) search.placeholder = t('toolbar.search');
  // Inspector empty
  setText('inspector-empty-title', t('inspector.no_selection'));
  setText('inspector-empty-text', t('inspector.no_selection_text'));
  setText('viewer-loading-text', t('inspector.loading'));
  setAttr('view-reset', 'title', t('inspector.viewer_reset'));
  setAttr('view-wireframe', 'title', t('inspector.viewer_wireframe'));
  setAttr('view-spin', 'title', t('inspector.viewer_spin'));
  setText('btn-download', t('inspector.download'));
  setText('btn-delete-file', t('inspector.delete'));
  setText('btn-regenerate-preview', t('inspector.regenerate_preview'));
  // Status
  setText('status-connected', t('status.connected'));
  setText('status-help', t('status.help_link'));
  // Drop overlay
  setText('drop-message', t('drop.message'));
  // Modals — new collection
  setText('new-col-title', t('modal.new_col_title'));
  const colInput = document.getElementById('collection-name');
  if (colInput) colInput.placeholder = t('modal.new_col_placeholder');
  setText('btn-create-collection-cancel', t('modal.cancel'));
  setText('btn-create-collection', t('modal.create'));
  // Folder modal
  setText('folder-title', t('folder.title'));
  setText('folder-structure-label', t('folder.structure'));
  setText('folder-opt-folders-title', t('folder.opt_folders_title'));
  setText('folder-opt-folders-desc', t('folder.opt_folders_desc'));
  setText('folder-opt-single-title', t('folder.opt_single_title'));
  setText('folder-opt-single-desc', t('folder.opt_single_desc'));
  setText('folder-opt-flat-title', t('folder.opt_flat_title'));
  setText('folder-opt-flat-desc', t('folder.opt_flat_desc'));
  setText('btn-folder-cancel', t('modal.cancel'));
  setText('btn-confirm-folder', t('folder.import'));
  // Help modal
  setText('help-modal-title', t('help.title'));
  setText('help-backup-title', t('help.backup_title'));
  setHTML('help-backup-text', t('help.backup_text'));
  setText('help-restore-title', t('help.restore_title'));
  setHTML('help-restore-text', t('help.restore_text'));
  setText('help-multipc-title', t('help.multi_pc_title'));
  setText('help-multipc-text', t('help.multi_pc_text'));
  setHTML('help-step-1', t('help.step1'));
  setHTML('help-step-2', t('help.step2'));
  setHTML('help-step-3', t('help.step3'));
  setHTML('help-step-4', t('help.step4'));
  setHTML('help-step-5', t('help.step5'));
  setHTML('help-warn', t('help.warning'));
  setText('help-strategy-title', t('help.strategy_title'));
  setHTML('help-strategy-text', t('help.strategy_text'));
  setText('btn-help-close', t('help.understood'));
  // Restore modal
  setText('restore-modal-title', t('restore.title'));
  setText('restore-how-label', t('restore.how'));
  setText('restore-merge-title', t('restore.merge_title'));
  setText('restore-merge-desc', t('restore.merge_desc'));
  setText('restore-replace-title', t('restore.replace_title'));
  setText('restore-replace-desc', t('restore.replace_desc'));
  setText('btn-restore-cancel', t('modal.cancel'));
  setText('btn-confirm-restore', t('restore.action'));
  // About modal
  setText('about-tagline', t('about.tagline'));
  setText('about-key-version', t('about.version'));
  setText('about-key-release', t('about.release'));
  setText('about-key-supports', t('about.supports'));
  setText('about-key-engine', t('about.engine'));
  setText('about-key-storage', t('about.storage'));
  setText('about-val-storage', t('about.storage_value'));
  setText('about-author-label', t('about.author'));
  setText('about-features-label', t('about.features'));
  setText('about-feat-preview', t('about.feat_preview'));
  setText('about-feat-collections', t('about.feat_collections'));
  setText('about-feat-folders', t('about.feat_folders'));
  setText('about-feat-descriptions', t('about.feat_descriptions'));
  setText('about-feat-backup', t('about.feat_backup'));
  setText('about-feat-dragdrop', t('about.feat_dragdrop'));
  setText('about-danger-label', t('about.danger_zone'));
  setHTML('about-danger-text', t('about.danger_text'));
  setText('btn-clear-library', t('about.clear'));
  setText('btn-about-close', t('about.close'));
  // Clear modal
  setText('clear-modal-title', t('clear.title'));
  setHTML('clear-warning-main', t('clear.warning_main'));
  setHTML('clear-warning-safe', t('clear.warning_safe'));
  setHTML('clear-confirm-prompt', t('clear.confirm_prompt'));
  const clearInput = document.getElementById('clear-confirm');
  if (clearInput) clearInput.placeholder = t('clear.confirm_word');
  setText('btn-clear-cancel', t('modal.cancel'));
  setText('btn-confirm-clear', t('clear.confirm_btn'));
}

function setText(id, text) {
  const el = document.getElementById(id);
  if (el) el.textContent = text;
}

function setHTML(id, html) {
  const el = document.getElementById(id);
  if (el) el.innerHTML = html;
}

function setAttr(id, attr, value) {
  const el = document.getElementById(id);
  if (el) el.setAttribute(attr, value);
}

// ============ STATE ============
const state = {
  files: [],         // { id, name, size, data (ArrayBuffer), collectionId, stats }
  collections: [],   // { id, name }
  activeCollection: null, // null = all
  selectedFile: null,
  selectedIds: new Set(),
  bulkBusy: false,
  search: '',
  loading: true,     // true while the private server archive is loading
};

// ============ PRIVATE SERVER STORAGE ============
const serverStorage = createArchiveStorage({
  csrf: document.querySelector('meta[name="csrf-token"]').content,
  onUnauthorized: () => window.location.assign('login.php'),
  onError: error => {
    closeProgress();
    showToast(error.message, 'warn', 10000);
  },
});

async function openDB() { /* Server database is opened by PHP. */ }
const projectsUI = createProjectsUI({ storage: serverStorage, getFiles: () => state.files,
  getSelected: () => state.selectedIds.size ? [...state.selectedIds] : (state.selectedFile ? [state.selectedFile] : []),
  selectFile, toast: (...args) => showToast(...args), escapeHtml });
document.getElementById('btn-projects').addEventListener('click', () => projectsUI.open());
async function dbGetAll(store) { return serverStorage.getAll(store); }
async function reloadArchive() {
  if (state.loading) return;
  try {
    [state.collections, state.files] = await Promise.all([
      serverStorage.getAll('collections'), serverStorage.getAll('files'),
    ]);
    if (!state.files.some(f => f.id === state.selectedFile)) state.selectedFile = null;
    render();
  } catch { /* The storage client already displays the connection error. */ }
}
async function dbPut(store, item) {
  try { return await serverStorage.put(store, item); }
  catch (error) { await reloadArchive(); throw error; }
}
async function dbDelete(store, id) {
  try { return await serverStorage.delete(store, id); }
  catch (error) { await reloadArchive(); throw error; }
}
window.addEventListener('unhandledrejection', event => {
  closeProgress();
  showToast(event.reason?.message || 'De bewerking is mislukt. Vernieuw het archief.', 'warn', 10000);
  event.preventDefault();
});
// Clear rendered private content when returning through the browser's page cache.
window.addEventListener('pageshow', event => { if (event.persisted) window.location.reload(); });
window.addEventListener('pagehide', () => { document.documentElement.style.visibility = 'hidden'; });

// ============ UTILS ============
const uid = () => crypto.randomUUID();

function formatBytes(b) {
  if (b < 1024) return b + ' B';
  if (b < 1024 * 1024) return (b / 1024).toFixed(1) + ' KB';
  return (b / (1024 * 1024)).toFixed(2) + ' MB';
}

function shortId(id) { return id.slice(0, 6).toUpperCase(); }

async function hashBuffer(buffer) {
  const digest = await crypto.subtle.digest('SHA-256', buffer);
  const bytes = new Uint8Array(digest);
  let hex = '';
  for (let i = 0; i < bytes.length; i++) {
    hex += bytes[i].toString(16).padStart(2, '0');
  }
  return hex;
}

function showToast(message, type = 'info', duration = 4500) {
  const container = document.getElementById('toast-container');
  const toast = document.createElement('div');
  toast.className = `toast toast-${type}`;
  toast.textContent = message;
  container.appendChild(toast);

  setTimeout(() => {
    toast.classList.add('fade-out');
    setTimeout(() => toast.remove(), 300);
  }, duration);
}

// ============ MODEL PARSING (STL + 3MF + SLA) ============
const stlLoader = new STLLoader();
const threeMFLoader = new ThreeMFLoader();

// SLA/resin printer format extensions. These are sliced files (no 3D mesh) —
// we extract the embedded preview thumbnail and print metadata only.
const SLA_EXTENSIONS = ['ctb', 'cbddlp', 'phz', 'photon', 'pws', 'pw0', 'pwmx', 'pwms', 'pwmo', 'pwx', 'sl1', 'sl1s'];

function getFileType(filename) {
  const lower = filename.toLowerCase();
  if (lower.endsWith('.stl')) return 'stl';
  if (lower.endsWith('.3mf')) return '3mf';
  for (const ext of SLA_EXTENSIONS) {
    if (lower.endsWith('.' + ext)) return 'sla';
  }
  return null;
}

// Returns the specific SLA sub-format (ctb, sl1, pws, etc.) for a given filename,
// or null if it's not an SLA file. Used by the SLA parser to dispatch to the
// right header decoder.
function getSLASubFormat(filename) {
  const lower = filename.toLowerCase();
  for (const ext of SLA_EXTENSIONS) {
    if (lower.endsWith('.' + ext)) return ext;
  }
  return null;
}

// Walk a Three.js Object3D tree and collect all mesh geometries (with world transform applied)
function collectGeometriesFromObject(object) {
  const geometries = [];
  object.updateMatrixWorld(true);
  object.traverse(child => {
    if (child.isMesh && child.geometry && child.geometry.attributes && child.geometry.attributes.position) {
      // Clone so we don't mutate the loader's geometry, then bake the world transform in
      const geo = child.geometry.clone();
      geo.applyMatrix4(child.matrixWorld);

      // Strip everything except position (and index if present) so geometries can be merged
      // even if the source 3MF has heterogeneous attributes (uv, color, normal, etc.).
      const positions = geo.getAttribute('position');
      const cleanGeo = new THREE.BufferGeometry();
      cleanGeo.setAttribute('position', positions.clone());
      if (geo.index) cleanGeo.setIndex(geo.index.clone());
      geometries.push(cleanGeo);

      geo.dispose();
    }
  });
  return geometries;
}

// Custom 3MF parser — handles Bambu/Orca/Prusa variants that Three.js's loader struggles with.
// 3MF is a ZIP container with XML files describing meshes. We extract all .model files
// (the spec only requires one but slicers often have multiple), parse <vertex> and <triangle>
// elements directly with regex (much faster than DOMParser for large files), and build
// BufferGeometries.
function parseThreeMFCustom(arrayBuffer) {
  const zipped = new Uint8Array(arrayBuffer);
  let unzipped;
  try {
    unzipped = unzipSync(zipped);
  } catch (e) {
    console.error('Failed to unzip 3MF:', e);
    throw new Error('Not a valid ZIP/3MF file');
  }

  // Find every .model file in the archive — Bambu Studio and OrcaSlicer
  // put per-object .model files in Metadata/, while the spec puts them in 3D/
  const modelFiles = [];
  for (const path in unzipped) {
    const lower = path.toLowerCase();
    if (lower.endsWith('.model') && !lower.includes('_rels')) {
      modelFiles.push(path);
    }
  }

  if (modelFiles.length === 0) {
    throw new Error('No .model files found in 3MF archive');
  }

  // Sort so the main 3dmodel.model (usually in 3D/) comes first if present
  modelFiles.sort((a, b) => {
    const aMain = a.toLowerCase().includes('3dmodel.model');
    const bMain = b.toLowerCase().includes('3dmodel.model');
    if (aMain && !bMain) return -1;
    if (!aMain && bMain) return 1;
    return a.localeCompare(b);
  });

  const geometries = [];

  for (const modelPath of modelFiles) {
    const xmlText = strFromU8(unzipped[modelPath]);
    const meshes = extractMeshesFromXML(xmlText);
    for (const mesh of meshes) {
      if (mesh.vertices.length === 0 || mesh.triangles.length === 0) continue;
      const geo = buildBufferGeometry(mesh.vertices, mesh.triangles);
      if (geo) {
        // 3MF is Z-up; rotate so Y becomes up to match STL orientation
        const m = new THREE.Matrix4().makeRotationX(-Math.PI / 2);
        geo.applyMatrix4(m);
        geometries.push(geo);
      }
    }
  }

  return geometries;
}

// Extract all <mesh> blocks from a 3MF .model XML string. Each mesh has a list of
// vertices and triangles. We use regex parsing because DOMParser is much slower
// on large 3MF model files (which can be tens of MB of XML).
function extractMeshesFromXML(xml) {
  const meshes = [];

  // Find all <mesh>...</mesh> blocks
  const meshRegex = /<mesh\b[^>]*>([\s\S]*?)<\/mesh>/g;
  let meshMatch;
  while ((meshMatch = meshRegex.exec(xml)) !== null) {
    const meshContent = meshMatch[1];
    const vertices = [];
    const triangles = [];

    // Parse vertices: <vertex x="..." y="..." z="..." />
    const vertexRegex = /<vertex\s+([^/>]+)\/?>/g;
    let vMatch;
    while ((vMatch = vertexRegex.exec(meshContent)) !== null) {
      const attrs = vMatch[1];
      const x = parseFloat(extractAttr(attrs, 'x'));
      const y = parseFloat(extractAttr(attrs, 'y'));
      const z = parseFloat(extractAttr(attrs, 'z'));
      if (!isNaN(x) && !isNaN(y) && !isNaN(z)) {
        vertices.push(x, y, z);
      } else {
        // Push a degenerate vertex rather than throwing off all subsequent indices
        vertices.push(0, 0, 0);
      }
    }

    // Parse triangles: <triangle v1="0" v2="1" v3="2" />
    const triangleRegex = /<triangle\s+([^/>]+)\/?>/g;
    let tMatch;
    while ((tMatch = triangleRegex.exec(meshContent)) !== null) {
      const attrs = tMatch[1];
      const v1 = parseInt(extractAttr(attrs, 'v1'), 10);
      const v2 = parseInt(extractAttr(attrs, 'v2'), 10);
      const v3 = parseInt(extractAttr(attrs, 'v3'), 10);
      if (!isNaN(v1) && !isNaN(v2) && !isNaN(v3)) {
        triangles.push(v1, v2, v3);
      }
    }

    if (vertices.length > 0 && triangles.length > 0) {
      meshes.push({ vertices, triangles });
    }
  }

  return meshes;
}

function extractAttr(attrString, name) {
  // Match name="value" or name='value'
  const re = new RegExp(`\\b${name}\\s*=\\s*["']([^"']*)["']`);
  const m = attrString.match(re);
  return m ? m[1] : '';
}

function buildBufferGeometry(verticesFlat, trianglesFlat) {
  if (verticesFlat.length === 0 || trianglesFlat.length === 0) return null;
  const geo = new THREE.BufferGeometry();
  const vertexCount = verticesFlat.length / 3;

  // Validate triangle indices
  for (let i = 0; i < trianglesFlat.length; i++) {
    if (trianglesFlat[i] >= vertexCount || trianglesFlat[i] < 0) {
      console.warn('3MF: triangle index out of range, clamping');
      trianglesFlat[i] = 0;
    }
  }

  geo.setAttribute('position', new THREE.Float32BufferAttribute(verticesFlat, 3));
  // Use Uint32Array for triangle indices since 3MF can have >65k vertices
  const indexArray = vertexCount > 65535
    ? new Uint32Array(trianglesFlat)
    : new Uint16Array(trianglesFlat);
  geo.setIndex(new THREE.BufferAttribute(indexArray, 1));
  return geo;
}

function parseModel(arrayBuffer, filename) {
  const type = getFileType(filename);
  try {
    let geometry;
    let approximate = false;

    if (type === 'stl') {
      geometry = stlLoader.parse(arrayBuffer);
      // STL convention: Z is print height; the viewer uses Y-up. STL has no unit metadata.
      geometry.rotateX(-Math.PI / 2);
    } else if (type === '3mf') {
      // Try Three.js's loader first — works for spec-compliant 3MF files
      let geometries = null;
      try {
        const group = threeMFLoader.parse(arrayBuffer);
        if (group) {
          // ThreeMFLoader r160 reads but does not apply model units.
          const modelEntries = unzipSync(new Uint8Array(arrayBuffer), { filter: entry => /\.model$/i.test(entry.name) });
          const scales = Object.values(modelEntries).map(bytes => {
            const header = strFromU8(bytes.subarray(0, 4096));
            const modelTag = header.match(/<(?:\w+:)?model\b[^>]*>/)?.[0];
            return modelTag ? threeMFUnitScale(extractAttr(modelTag, 'unit') || 'millimeter') : null;
          });
          if (scales.length && scales.every(scale => scale !== null && scale === scales[0])) group.scale.setScalar(scales[0]);
          else approximate = true;
          group.rotation.set(-Math.PI / 2, 0, 0);
          group.updateMatrixWorld(true);
          const collected = collectGeometriesFromObject(group);
          if (collected.length > 0) geometries = collected;
        }
      } catch (mfErr) {
        console.warn('Three.js ThreeMFLoader failed, falling back to custom parser:', mfErr);
      }

      // Fallback: custom 3MF parser. Many slicers (Bambu, Orca, Prusa) produce
      // 3MF files that the standard loader can't handle — vendor extensions,
      // <components> references, or non-standard file paths.
      if (!geometries || geometries.length === 0) {
        try {
          geometries = parseThreeMFCustom(arrayBuffer);
          approximate = true; // Legacy fallback ignores component transforms and model units.
        } catch (customErr) {
          console.error('Custom 3MF parser also failed:', customErr);
          return null;
        }
      }

      if (!geometries || geometries.length === 0) {
        console.error('3MF parse: no meshes found in', filename);
        return null;
      }

      // Merge all geometries into one for consistent preview
      if (geometries.length === 1) {
        geometry = geometries[0];
      } else {
        try {
          const merged = BufferGeometryUtils.mergeGeometries(geometries, false);
          if (merged) {
            geometry = merged;
            geometries.forEach(g => g.dispose());
          } else {
            geometry = geometries[0];
            approximate = true;
            for (let i = 1; i < geometries.length; i++) geometries[i].dispose();
          }
        } catch (mergeErr) {
          console.warn('3MF merge failed, using first mesh:', mergeErr);
          geometry = geometries[0];
          approximate = true;
          for (let i = 1; i < geometries.length; i++) geometries[i].dispose();
        }
      }
    } else {
      return null;
    }

    if (!geometry || !geometry.attributes || !geometry.attributes.position) {
      console.error('Parse produced invalid geometry for', filename);
      return null;
    }

    const positions = geometry.attributes.position;
    for (let i = 0; i < positions.count; i++) {
      if (![positions.getX(i), positions.getY(i), positions.getZ(i)].every(Number.isFinite)) {
        geometry.dispose(); return null;
      }
    }
    const modelCheck = analyzeMesh(positions, geometry.index);
    geometry.computeBoundingBox();
    geometry.computeVertexNormals();
    const bb = geometry.boundingBox;
    const size = new THREE.Vector3();
    bb.getSize(size);
    const triangles = geometry.index
      ? geometry.index.count / 3
      : geometry.attributes.position.count / 3;
    return {
      geometry,
      type,
      modelCheck,
      stats: {
        triangles: Math.round(triangles),
        width: size.x,
        height: size.y,
        depth: size.z,
        approximate: approximate ? 1 : 0,
      }
    };
  } catch (e) {
    console.error(`Parse error (${type}) for ${filename}:`, e);
    return null;
  }
}

// ============ SLA / RESIN PRINTER FILE PARSING ============
// SLA files (CTB, CBDDLP, PHZ, PWS-family, SL1) are pre-sliced — they contain
// per-layer PNG/encoded images plus print metadata, not a 3D mesh. We extract:
//   1. The embedded preview thumbnail (slicers always include one)
//   2. Print metadata: layer height, print height, exposure times, layer count, etc.
//
// Two sub-format families are handled:
//   - ChiTuBox family (CTB, CBDDLP, PHZ): binary header at offset 0, RLE15-encoded
//     RGB565 preview images. CTB v4 files are XOR-encrypted but the header/preview
//     section is unencrypted.
//   - PhotonWorkshop family (PWS, PW0, PWMX, etc.): "ANYCUBIC" marker at offset 0
//     followed by section-based layout. Older .photon files use the CBDDLP format.
//   - PrusaSlicer SL1/SL1S: ZIP container with config.ini + thumbnail/*.png + layer PNGs.
//
// All multi-byte fields in binary formats are little-endian.
const SLA_MAGIC_CBDDLP = 0x12FD0019; // .photon, .cbddlp
const SLA_MAGIC_CTB = 0x12FD0086;    // .ctb (v2/v3/v4)
const SLA_MAGIC_PHZ = 0x9FDA83AE;    // .phz

function parseSLA(arrayBuffer, filename) {
  const subFormat = getSLASubFormat(filename);
  if (!subFormat) return null;

  try {
    let result = null;

    if (subFormat === 'sl1' || subFormat === 'sl1s') {
      result = parseSL1(arrayBuffer);
    } else if (subFormat === 'pws' || subFormat === 'pw0' || subFormat === 'pwmx' ||
               subFormat === 'pwms' || subFormat === 'pwmo' || subFormat === 'pwx') {
      // Photon Workshop family. Try the new ANYCUBIC format first; fall back to
      // the CBDDLP-style header that older Anycubic files used.
      result = parsePhotonWorkshop(arrayBuffer) || parseChituFormat(arrayBuffer, subFormat);
    } else {
      // CTB / CBDDLP / PHZ / PHOTON — all share the ChiTuBox header layout
      result = parseChituFormat(arrayBuffer, subFormat);
    }

    if (!result) return null;

    return {
      type: 'sla',
      subFormat,
      thumbnail: result.thumbnail || null,
      slaStats: result.slaStats || {},
    };
  } catch (e) {
    console.error(`SLA parse error (${subFormat}) for ${filename}:`, e);
    return null;
  }
}

// Parse ChiTuBox family files (CTB / CBDDLP / PHZ / PHOTON).
// Header layout (little-endian, all u32 unless noted, offsets in bytes):
//   0x00 magic            — 0x12FD0019 (cbddlp/photon), 0x12FD0086 (ctb), 0x9FDA83AE (phz)
//   0x04 version
//   0x08 bed_size_x        (f32, mm)
//   0x0C bed_size_y        (f32, mm)
//   0x10 bed_size_z        (f32, mm)
//   0x14-0x1C reserved/unknown
//   0x20 total_height      (f32, mm)
//   0x24 layer_height      (f32, mm)
//   0x28 exposure_time     (f32, s)
//   0x2C bottom_exposure   (f32, s)
//   0x30 light_off_time    (f32, s)
//   0x34 bottom_count      (u32)
//   0x38 resolution_x      (u32, px)
//   0x3C resolution_y      (u32, px)
//   0x40 large_preview_off (u32, file offset)
//   0x44 layer_table_off   (u32)
//   0x48 layer_count       (u32)
//   0x4C small_preview_off (u32)
//   0x50 print_time_s      (u32)
function parseChituFormat(arrayBuffer, subFormat) {
  const view = new DataView(arrayBuffer);
  if (arrayBuffer.byteLength < 0x60) return null;

  const magic = view.getUint32(0, true);
  // Accept any of the known ChiTu-family magics. We don't strictly require the
  // magic to match the extension because some slicers/converters mix them.
  const knownMagics = [SLA_MAGIC_CBDDLP, SLA_MAGIC_CTB, SLA_MAGIC_PHZ];
  if (!knownMagics.includes(magic)) {
    return null;
  }

  const slaStats = {
    format: subFormat.toUpperCase(),
    version: view.getUint32(0x04, true),
    bedSizeX: view.getFloat32(0x08, true),
    bedSizeY: view.getFloat32(0x0C, true),
    bedSizeZ: view.getFloat32(0x10, true),
    totalHeight: view.getFloat32(0x20, true),
    layerHeight: view.getFloat32(0x24, true),
    exposureTime: view.getFloat32(0x28, true),
    bottomExposureTime: view.getFloat32(0x2C, true),
    lightOffTime: view.getFloat32(0x30, true),
    bottomLayers: view.getUint32(0x34, true),
    resolutionX: view.getUint32(0x38, true),
    resolutionY: view.getUint32(0x3C, true),
    layerCount: view.getUint32(0x48, true),
    printTimeSeconds: view.getUint32(0x50, true),
  };

  // Sanity check: if values look insane the magic was probably misread. Bail
  // gracefully so the file is still imported (just without metadata).
  if (slaStats.layerCount > 500000 || slaStats.layerHeight > 10 ||
      slaStats.layerHeight <= 0 || !isFinite(slaStats.totalHeight)) {
    return { thumbnail: null, slaStats: { format: subFormat.toUpperCase() } };
  }

  // Try the large preview first, fall back to the small one.
  const largePreviewOffset = view.getUint32(0x40, true);
  const smallPreviewOffset = view.getUint32(0x4C, true);
  let thumbnail = null;
  for (const offset of [largePreviewOffset, smallPreviewOffset]) {
    if (offset > 0 && offset < arrayBuffer.byteLength - 16) {
      thumbnail = decodeChituPreview(arrayBuffer, offset);
      if (thumbnail) break;
    }
  }

  return { thumbnail, slaStats };
}

// Decode a ChiTuBox preview image. Preview header layout at the given offset:
//   +0x00 width_px       (u32)
//   +0x04 height_px      (u32)
//   +0x08 image_offset   (u32, absolute file offset of pixel data)
//   +0x0C image_length   (u32, bytes)
//   +0x10 padding (16 bytes, ignored)
// Pixel data is RLE-encoded RGB555:
//   - Each 16-bit word is little-endian
//   - Format: rrrrr ggggg b bbbbb  (R: bits 11-15, G: bits 6-10, B: bits 0-4)
//   - The bit at position 5 (mask 0x20) is a "repeat" flag: when set, the
//     NEXT 16-bit value's low 12 bits give the run length (the colour itself
//     repeats run+1 times in total).
function decodeChituPreview(arrayBuffer, headerOffset) {
  try {
    const view = new DataView(arrayBuffer);
    const width = view.getUint32(headerOffset + 0x00, true);
    const height = view.getUint32(headerOffset + 0x04, true);
    const dataOffset = view.getUint32(headerOffset + 0x08, true);
    const dataLength = view.getUint32(headerOffset + 0x0C, true);

    // Sanity bounds — preview images are small (e.g. 400x300), refuse anything wild
    if (width === 0 || height === 0 || width > 4096 || height > 4096) return null;
    if (dataOffset + dataLength > arrayBuffer.byteLength) return null;
    if (dataLength === 0 || dataLength > 16 * 1024 * 1024) return null;

    const pixelCount = width * height;
    const rgba = new Uint8ClampedArray(pixelCount * 4);
    let outIdx = 0;
    let i = dataOffset;
    const end = dataOffset + dataLength;

    while (i < end - 1 && outIdx < rgba.length) {
      const word = view.getUint16(i, true);
      i += 2;
      // Extract RGB555 channels and expand to 8 bits per channel
      const r = ((word >> 11) & 0x1F) << 3;
      const g = ((word >> 6) & 0x1F) << 3;
      const b = (word & 0x1F) << 3;
      const repeatFlag = (word & 0x20) !== 0;

      let runLength = 1;
      if (repeatFlag && i < end - 1) {
        const runWord = view.getUint16(i, true);
        i += 2;
        // Run-length is the low 12 bits + 1 (the original pixel itself)
        runLength = (runWord & 0x0FFF) + 1;
      }

      for (let k = 0; k < runLength && outIdx < rgba.length; k++) {
        rgba[outIdx++] = r;
        rgba[outIdx++] = g;
        rgba[outIdx++] = b;
        rgba[outIdx++] = 255;
      }
    }

    // Render the decoded pixels to a canvas and export as PNG data URL
    return rgbaToDataURL(rgba, width, height);
  } catch (e) {
    console.warn('Chitu preview decode failed:', e);
    return null;
  }
}

// Parse Photon Workshop format (PWS / PW0 / PWMX / etc.).
// Layout: starts with "ANYCUBIC" (8 bytes) or section-based with named sections.
// Newer PWS files start with a HEADER section marker. We scan for known section
// names and extract the FILEMARK + HEADER + PREVIEW sections.
//
// Section format:
//   12 bytes: section name (null-padded ASCII, e.g. "HEADER\0\0\0\0\0\0")
//   4 bytes:  section payload length (u32 LE)
//   N bytes:  payload
//
// HEADER payload contains print params; PREVIEW payload contains a raw RGB565 image.
function parsePhotonWorkshop(arrayBuffer) {
  if (arrayBuffer.byteLength < 32) return null;
  const view = new DataView(arrayBuffer);
  const u8 = new Uint8Array(arrayBuffer);

  // Photon Workshop files start with "ANYCUBIC" + null padding (12 bytes total)
  // followed by a u32 version. If we don't find this marker, this isn't a PWS file.
  const marker = readAsciiAt(u8, 0, 8);
  if (marker !== 'ANYCUBIC') return null;

  // Scan for known sections: HEADER, PREVIEW, LAYERDEF, EXTRA, MACHINE
  const sections = scanPhotonWorkshopSections(arrayBuffer);
  const slaStats = { format: 'PWS' };
  let thumbnail = null;

  if (sections.HEADER) {
    const h = sections.HEADER;
    // PWS HEADER layout (offsets relative to payload start):
    //   0x00 pixel_size_um   (f32, micrometres per pixel)
    //   0x04 layer_height    (f32, mm)
    //   0x08 exposure_time   (f32, s)
    //   0x0C off_delay       (f32, s)
    //   0x10 bottom_exposure (f32, s)
    //   0x14 bottom_layers   (f32 — yes, stored as float in PWS)
    //   0x18 lift_distance   (f32, mm)
    //   0x1C lift_speed      (f32)
    //   0x20 retract_speed   (f32)
    //   0x24 volume_ml       (f32)
    //   0x28 anti_alias      (u32)
    //   0x2C res_x           (u32)
    //   0x30 res_y           (u32)
    //   0x34 weight_g        (f32)
    //   0x38 price           (f32)
    //   0x3C price_currency  (u32)
    //   0x40 per_layer_override (u32)
    //   0x44 print_time_s    (u32)
    //   0x48 transition_layers (u32)
    //   0x4C layer_count     (u32 — sometimes located here, varies by version)
    if (h.length >= 0x50) {
      const o = h.offset;
      slaStats.layerHeight = view.getFloat32(o + 0x04, true);
      slaStats.exposureTime = view.getFloat32(o + 0x08, true);
      slaStats.lightOffTime = view.getFloat32(o + 0x0C, true);
      slaStats.bottomExposureTime = view.getFloat32(o + 0x10, true);
      slaStats.bottomLayers = Math.round(view.getFloat32(o + 0x14, true));
      slaStats.resinVolumeML = view.getFloat32(o + 0x24, true);
      slaStats.resolutionX = view.getUint32(o + 0x2C, true);
      slaStats.resolutionY = view.getUint32(o + 0x30, true);
      slaStats.resinWeightG = view.getFloat32(o + 0x34, true);
      slaStats.printTimeSeconds = view.getUint32(o + 0x44, true);
      // Layer count and total height aren't always at fixed offsets in PWS;
      // try a couple of reasonable spots
      if (h.length >= 0x54) {
        const lc = view.getUint32(o + 0x4C, true);
        if (lc > 0 && lc < 500000) slaStats.layerCount = lc;
      }
      if (slaStats.layerCount && slaStats.layerHeight) {
        slaStats.totalHeight = slaStats.layerCount * slaStats.layerHeight;
      }
    }
  }

  if (sections.PREVIEW) {
    thumbnail = decodePhotonWorkshopPreview(arrayBuffer, sections.PREVIEW.offset, sections.PREVIEW.length);
  }

  return { thumbnail, slaStats };
}

function scanPhotonWorkshopSections(arrayBuffer) {
  const u8 = new Uint8Array(arrayBuffer);
  const view = new DataView(arrayBuffer);
  const sections = {};
  // Known section names (12-byte null-padded). We look for these as ASCII patterns.
  const names = ['HEADER', 'PREVIEW', 'LAYERDEF', 'EXTRA', 'MACHINE', 'SOFTWARE'];

  for (const name of names) {
    const idx = findAsciiSequence(u8, name);
    if (idx < 0) continue;
    // After a 12-byte name field comes a u32 length, then payload
    const lengthOffset = idx + 12;
    if (lengthOffset + 4 > u8.length) continue;
    const length = view.getUint32(lengthOffset, true);
    const payloadOffset = lengthOffset + 4;
    if (payloadOffset + length > u8.length || length > arrayBuffer.byteLength) continue;
    sections[name] = { offset: payloadOffset, length };
  }
  return sections;
}

// Find an ASCII sequence preceded by start-of-section alignment.
// PWS sections are name-padded to 12 bytes, so we search for the name followed
// by enough null bytes that we can be reasonably confident it's a section header.
function findAsciiSequence(u8, name) {
  const nameBytes = new TextEncoder().encode(name);
  outer: for (let i = 0; i < u8.length - nameBytes.length - 4; i++) {
    for (let j = 0; j < nameBytes.length; j++) {
      if (u8[i + j] !== nameBytes[j]) continue outer;
    }
    // Verify the byte right after the name is null (section names are null-padded)
    if (u8[i + nameBytes.length] !== 0) continue;
    return i;
  }
  return -1;
}

// Decode a Photon Workshop preview. Layout:
//   +0x00 width_px   (u32)
//   +0x04 res        (u32, sometimes "marker" — varies)
//   +0x08 height_px  (u32)
//   +0x0C image_data (raw RGB565 LE pixels, width*height*2 bytes)
function decodePhotonWorkshopPreview(arrayBuffer, payloadOffset, payloadLength) {
  try {
    const view = new DataView(arrayBuffer);
    const width = view.getUint32(payloadOffset + 0x00, true);
    const height = view.getUint32(payloadOffset + 0x08, true);
    if (width === 0 || height === 0 || width > 4096 || height > 4096) return null;

    const pixelDataOffset = payloadOffset + 0x10;
    const pixelDataLength = width * height * 2;
    if (pixelDataOffset + pixelDataLength > payloadOffset + payloadLength + 16) return null;

    const pixelCount = width * height;
    const rgba = new Uint8ClampedArray(pixelCount * 4);
    for (let p = 0; p < pixelCount; p++) {
      const word = view.getUint16(pixelDataOffset + p * 2, true);
      // RGB565: rrrrr ggggggg bbbbb
      const r = ((word >> 11) & 0x1F) << 3;
      const g = ((word >> 5) & 0x3F) << 2;
      const b = (word & 0x1F) << 3;
      rgba[p * 4 + 0] = r;
      rgba[p * 4 + 1] = g;
      rgba[p * 4 + 2] = b;
      rgba[p * 4 + 3] = 255;
    }
    return rgbaToDataURL(rgba, width, height);
  } catch (e) {
    console.warn('PWS preview decode failed:', e);
    return null;
  }
}

// Parse a PrusaSlicer SL1 or SL1S file. These are ZIP archives containing:
//   - config.ini       — print configuration
//   - prusaslicer.ini  — slicer settings (optional)
//   - thumbnail/thumbnail400x400.png (or similar) — preview image
//   - NNNNN.png        — per-layer slice images
function parseSL1(arrayBuffer) {
  let unzipped;
  try {
    unzipped = unzipSync(new Uint8Array(arrayBuffer));
  } catch (e) {
    console.error('SL1 unzip failed:', e);
    return null;
  }

  const slaStats = { format: 'SL1' };

  // Read config.ini for print metadata
  const configKey = Object.keys(unzipped).find(k => k.toLowerCase().endsWith('config.ini'));
  if (configKey) {
    const configText = strFromU8(unzipped[configKey]);
    const cfg = parseIniLike(configText);
    if (cfg.layerHeight) slaStats.layerHeight = cfg.layerHeight;
    if (cfg.expTime) slaStats.exposureTime = cfg.expTime;
    if (cfg.expTimeFirst) slaStats.bottomExposureTime = cfg.expTimeFirst;
    if (cfg.numFast) slaStats.layerCount = cfg.numFast + (cfg.numSlow || 0);
    if (cfg.numSlow) slaStats.bottomLayers = cfg.numSlow;
    if (cfg.printTime) slaStats.printTimeSeconds = cfg.printTime;
    if (cfg.usedMaterial) slaStats.resinVolumeML = cfg.usedMaterial;
    if (cfg.printerModel) slaStats.printerModel = cfg.printerModel;
    if (slaStats.layerCount && slaStats.layerHeight) {
      slaStats.totalHeight = slaStats.layerCount * slaStats.layerHeight;
    }
  }

  // Find the largest thumbnail in the archive — SL1 typically has both a
  // 400x400 and a smaller variant; we prefer the bigger one for display.
  const thumbnailKeys = Object.keys(unzipped).filter(k => {
    const lower = k.toLowerCase();
    return lower.endsWith('.png') && lower.includes('thumbnail');
  });
  thumbnailKeys.sort((a, b) => unzipped[b].length - unzipped[a].length);

  let thumbnail = null;
  if (thumbnailKeys.length > 0) {
    const pngBytes = unzipped[thumbnailKeys[0]];
    thumbnail = bytesToPngDataURL(pngBytes);
  }

  return { thumbnail, slaStats };
}

// Parse a simple INI-style config (key=value, one per line, # or ; comments).
// Used for SL1 config.ini. Returns a normalised object with camelCase keys
// for the fields we care about.
function parseIniLike(text) {
  const result = {};
  const lines = text.split(/\r?\n/);
  for (const line of lines) {
    const trimmed = line.trim();
    if (!trimmed || trimmed.startsWith('#') || trimmed.startsWith(';') || trimmed.startsWith('[')) continue;
    const eq = trimmed.indexOf('=');
    if (eq < 0) continue;
    const key = trimmed.slice(0, eq).trim().toLowerCase();
    const value = trimmed.slice(eq + 1).trim();

    // Map known SL1 keys to our canonical names
    if (key === 'layerheight') result.layerHeight = parseFloat(value);
    else if (key === 'exptime') result.expTime = parseFloat(value);
    else if (key === 'exptimefirst') result.expTimeFirst = parseFloat(value);
    else if (key === 'numfast') result.numFast = parseInt(value, 10);
    else if (key === 'numslow') result.numSlow = parseInt(value, 10);
    else if (key === 'printtime') result.printTime = parseFloat(value);
    else if (key === 'usedmaterial') result.usedMaterial = parseFloat(value);
    else if (key === 'printermodel') result.printerModel = value;
    else if (key === 'jobdir') result.jobDir = value;
  }
  return result;
}

// Convert a raw RGBA Uint8ClampedArray to a PNG data URL via a temporary canvas.
// Used by both Chitu and PWS preview decoders.
function rgbaToDataURL(rgba, width, height) {
  const canvas = document.createElement('canvas');
  canvas.width = width;
  canvas.height = height;
  const ctx = canvas.getContext('2d');
  const imageData = ctx.createImageData(width, height);
  imageData.data.set(rgba);
  ctx.putImageData(imageData, 0, 0);
  return canvas.toDataURL('image/png');
}

// Convert raw PNG bytes (from a ZIP) to a data URL we can stuff into an <img>.
// We use a Blob + FileReader-free synchronous-ish path via base64 conversion,
// which is fine for the small thumbnails (~50-200KB) found in SL1 files.
function bytesToPngDataURL(bytes) {
  let binary = '';
  const chunkSize = 0x8000; // avoid stack overflow on large inputs
  for (let i = 0; i < bytes.length; i += chunkSize) {
    binary += String.fromCharCode.apply(null, bytes.subarray(i, i + chunkSize));
  }
  return 'data:image/png;base64,' + btoa(binary);
}

// Read `length` bytes starting at `offset` and return them as ASCII, stopping
// at the first null byte. Used for reading section name fields.
function readAsciiAt(u8, offset, length) {
  let s = '';
  for (let i = 0; i < length; i++) {
    const b = u8[offset + i];
    if (b === 0) break;
    s += String.fromCharCode(b);
  }
  return s;
}

// ============ SMART SCAN (local AI-like analysis) ============
function analyzeModelLocally(file, parsed = null) {
  const stats = parsed?.stats || file.stats || {};
  const fileType = (file.type || getFileType(file.name) || '').toLowerCase();

  // SLA/resin: skip mesh-specific analysis (overhangs, orientation, print difficulty
  // from geometry — none of that applies to a pre-sliced file). Instead, derive a
  // simpler set of tags from the slice metadata.
  if (fileType === 'sla') {
    return analyzeSLAFile(file, stats);
  }

  const geometry = parsed?.geometry || null;
  const modelType = guessModelType(file.name, stats);
  const printAdvice = estimatePrintAdvice(geometry, stats, modelType);
  const tags = generateSmartTags(file.name, stats, modelType, printAdvice);

  return {
    displayName: generateReadableModelName(file.name, modelType),
    modelType,
    tags,
    printAdvice,
    confidence: estimateSmartConfidence(file.name, stats, modelType, geometry),
    generatedAt: Date.now(),
    engine: 'local-smart-scan-v1'
  };
}

// Smart-scan equivalent for SLA/resin files. We build tags from slice metadata
// (height, layer count, exposure) and the filename, and replace the print advice
// with resin-printer-relevant guidance.
function analyzeSLAFile(file, stats) {
  const sla = (stats && stats.sla) || {};
  const subFormat = (sla.format || file.subFormat || getSLASubFormat(file.name) || 'sla').toUpperCase();
  const tags = new Set();

  // Format tag is always useful for filtering
  tags.add('resin');
  tags.add(subFormat.toLowerCase());

  // Tall print? SLA prints over 100mm are non-trivial
  if (sla.totalHeight) {
    if (sla.totalHeight > 150) tags.add('tall-print');
    else if (sla.totalHeight < 30) tags.add('short-print');
  }

  // High detail = thin layers
  if (sla.layerHeight) {
    if (sla.layerHeight <= 0.025) tags.add('high-detail');
    else if (sla.layerHeight >= 0.08) tags.add('fast-print');
  }

  // Heuristic from filename
  const name = file.name.toLowerCase();
  if (/mini|figure|figurine|statue|character|bust/.test(name)) tags.add('miniature');
  if (/dental|teeth|tooth|crown/.test(name)) tags.add('dental');
  if (/jewel|ring|earring|pendant/.test(name)) tags.add('jewelry');
  if (/test|calibration|cones of calibration/.test(name)) tags.add('calibration');

  const printAdvice = {
    orientation: 'Use angled supports — flat-on-bed prints risk suction failure',
    supportNeeded: true, // almost always true for resin
    difficulty: sla.totalHeight && sla.totalHeight > 150 ? 'Hard'
      : sla.layerHeight && sla.layerHeight <= 0.025 ? 'Medium'
      : 'Easy',
    reason: sla.totalHeight && sla.totalHeight > 150
      ? 'Tall resin prints can fail mid-print due to peel forces; ensure supports are dense'
      : 'Standard resin print — make sure FEP film is in good shape and resin is mixed',
    overhangScore: 0,
  };

  return {
    displayName: generateReadableModelName(file.name, 'unknown-model'),
    modelType: 'resin-print',
    tags: [...tags].slice(0, 8),
    printAdvice,
    confidence: 0.7,
    generatedAt: Date.now(),
    engine: 'local-smart-scan-v1-sla'
  };
}

function generateReadableModelName(filename, modelType = 'unknown-model') {
  const base = filename
    .replace(/\.(stl|3mf|ctb|cbddlp|phz|photon|pws|pw0|pwmx|pwms|pwmo|pwx|sl1|sl1s)$/i, '')
    .replace(/[_]+/g, ' ')
    .replace(/[-]+/g, ' ')
    .replace(/(final|fixed|fix|export|copy|kopie|new|nieuw|backup)/gi, '')
    .replace(/(v\s*\d+|rev\s*\d+)/gi, m => m.replace(/\s+/g, ''))
    .replace(/\s{2,}/g, ' ')
    .trim();

  const title = base ? base.replace(/\w/g, c => c.toUpperCase()) : 'Untitled Model';
  const typeLabel = smartTypeLabel(modelType);
  return modelType === 'unknown-model' ? title : `${title} · ${typeLabel}`;
}

function smartTypeLabel(modelType) {
  const labels = {
    'phone-stand': 'Phone stand',
    'gear': 'Gear',
    'bracket': 'Bracket',
    'enclosure': 'Enclosure',
    'clip': 'Clip',
    'figurine': 'Figurine',
    'flat-part': 'Flat part',
    'stand-like-object': 'Stand-like object',
    'tall-object': 'Tall object',
    'unknown-model': 'Unknown model'
  };
  return labels[modelType] || modelType;
}

function guessModelType(filename, stats = {}) {
  const name = filename.toLowerCase();
  const w = Number(stats.width) || 0;
  const h = Number(stats.height) || 0;
  const d = Number(stats.depth) || 0;
  const max = Math.max(w, h, d);
  const min = Math.min(w || Infinity, h || Infinity, d || Infinity);

  if (/phone|telefoon|stand|dock|houder/.test(name)) return 'phone-stand';
  if (/gear|cog|tandwiel|pulley|wheel/.test(name)) return 'gear';
  if (/bracket|mount|beugel|houder|support/.test(name)) return 'bracket';
  if (/case|box|enclosure|behuizing|housing/.test(name)) return 'enclosure';
  if (/clip|clamp|klem/.test(name)) return 'clip';
  if (/mini|figure|figurine|statue|character|toy/.test(name)) return 'figurine';

  if (h > w * 1.8 && h > d * 1.8) return 'tall-object';
  if (h < max * 0.22 && w > 0 && d > 0) return 'flat-part';
  if (h > w * 1.4 && d < w * 0.75) return 'stand-like-object';
  if (min !== Infinity && max / Math.max(min, 0.001) > 5) return 'bracket';

  return 'unknown-model';
}

function generateSmartTags(filename, stats = {}, modelType, printAdvice = {}) {
  const name = filename.toLowerCase();
  const tags = new Set();
  const triangles = Number(stats.triangles) || 0;
  const w = Number(stats.width) || 0;
  const h = Number(stats.height) || 0;
  const d = Number(stats.depth) || 0;

  if (modelType && modelType !== 'unknown-model') tags.add(modelType);
  if (/gear|bracket|mount|clip|hinge|holder|houder|beugel|klem/.test(name)) tags.add('functional');
  if (/mini|figure|statue|decor|ornament|toy/.test(name)) tags.add('decorative');
  if (/case|box|enclosure|behuizing|housing/.test(name)) tags.add('housing');
  if (triangles > 120000) tags.add('high-detail');
  if (triangles > 0 && triangles < 5000) tags.add('low-poly');
  if (w && h && d && Math.max(w, h, d) < 35) tags.add('small-part');
  if (w && h && d && Math.max(w, h, d) > 180) tags.add('large-print');
  if (printAdvice.supportNeeded) tags.add('needs-support');
  if (printAdvice.difficulty) tags.add(`difficulty-${printAdvice.difficulty.toLowerCase()}`);

  return [...tags].slice(0, 8);
}

function estimatePrintAdvice(geometry, stats = {}, modelType = 'unknown-model') {
  const w = Number(stats.width) || 0;
  const h = Number(stats.height) || 0;
  const d = Number(stats.depth) || 0;
  const overhangScore = geometry ? estimateOverhangScore(geometry) : 0;
  const tallRatio = h / Math.max(w, d, 1);
  const flatRatio = h / Math.max(w, d, 1);

  const advice = {
    orientation: 'Flat side down',
    supportNeeded: false,
    difficulty: 'Easy',
    reason: 'No major risk detected by local geometry scan',
    overhangScore: Number(overhangScore.toFixed(2))
  };

  if (modelType === 'phone-stand' || modelType === 'stand-like-object') {
    advice.orientation = 'Back or largest flat face on the bed';
    advice.reason = 'Stand-like models are usually more stable on the largest rear face';
  } else if (modelType === 'gear' || modelType === 'flat-part') {
    advice.orientation = 'Largest flat face on the bed';
    advice.reason = 'Flat functional parts usually print cleanest on their widest face';
  } else if (modelType === 'tall-object' || tallRatio > 1.8) {
    advice.orientation = 'Lay on side if strength and stability matter';
    advice.difficulty = 'Medium';
    advice.reason = 'Tall narrow geometry can wobble or detach during printing';
  } else if (flatRatio < 0.25) {
    advice.orientation = 'Keep flat on the build plate';
  }

  if (overhangScore > 0.38) {
    advice.supportNeeded = true;
    advice.difficulty = overhangScore > 0.55 ? 'Hard' : 'Medium';
    advice.reason = 'Downward-facing overhangs detected above the support threshold';
  } else if (overhangScore > 0.22) {
    advice.supportNeeded = true;
    advice.difficulty = advice.difficulty === 'Easy' ? 'Medium' : advice.difficulty;
    advice.reason = 'Some overhangs detected; supports may be needed depending on material and layer height';
  }

  return advice;
}

function estimateOverhangScore(geometry) {
  if (!geometry || !geometry.attributes || !geometry.attributes.position) return 0;
  const geo = geometry.clone();
  try {
    geo.computeVertexNormals();
    const normals = geo.attributes.normal;
    if (!normals || normals.count === 0) return 0;

    let risky = 0;
    const sampleEvery = Math.max(1, Math.floor(normals.count / 20000));
    let total = 0;
    for (let i = 0; i < normals.count; i += sampleEvery) {
      const ny = normals.getY(i);
      if (ny < -0.45) risky++;
      total++;
    }
    return total ? risky / total : 0;
  } finally {
    geo.dispose();
  }
}

function estimateSmartConfidence(filename, stats = {}, modelType, geometry) {
  let confidence = 0.45;
  const name = filename.toLowerCase();
  if (modelType !== 'unknown-model') confidence += 0.2;
  if (/phone|stand|gear|bracket|mount|case|box|clip|mini|figure|houder|beugel/.test(name)) confidence += 0.15;
  if (stats.width && stats.height && stats.depth) confidence += 0.1;
  if (geometry) confidence += 0.05;
  return Math.min(0.95, Number(confidence.toFixed(2)));
}

function ensureSmartScan(file, parsed = null) {
  if (!file) return null;
  if (!file.ai || parsed) {
    file.ai = analyzeModelLocally(file, parsed);
  }
  return file.ai;
}

// Backwards-compatibility alias for older calls
function parseSTL(arrayBuffer, filename = 'model.stl') {
  return parseModel(arrayBuffer, filename);
}

// ============ GEOMETRY CACHE & ASYNC PARSE ============
// LRU cache so repeatedly clicking the same file doesn't re-parse it.
// 3MF parsing in particular is expensive (zip + XML + merge).
const geometryCache = new Map(); // fileId -> { geometry: BufferGeometry, type, stats }
const GEOMETRY_CACHE_MAX = 5;

function cacheGeometry(fileId, parsed) {
  // Evict oldest if at limit
  if (geometryCache.size >= GEOMETRY_CACHE_MAX && !geometryCache.has(fileId)) {
    const oldestKey = geometryCache.keys().next().value;
    const oldest = geometryCache.get(oldestKey);
    if (oldest && oldest.geometry) oldest.geometry.dispose();
    geometryCache.delete(oldestKey);
  }
  // Move to end (most recent)
  geometryCache.delete(fileId);
  geometryCache.set(fileId, parsed);
}

function getCachedGeometry(fileId) {
  const cached = geometryCache.get(fileId);
  if (cached) {
    // Move to end (mark as recently used)
    geometryCache.delete(fileId);
    geometryCache.set(fileId, cached);
  }
  return cached;
}

// Parse off the main thread idle slot — yields once before parsing
// so the loading spinner has a chance to render.
async function parseModelAsync(arrayBuffer, filename) {
  await new Promise(r => setTimeout(r, 16)); // let UI paint
  return parseModel(arrayBuffer, filename);
}

// Track current load to prevent stale loads from overwriting newer ones
// (e.g. user clicks file A, then quickly clicks file B before A finished)
let currentLoadToken = 0;

function showViewerError(filename, detail = '') {
  const container = document.getElementById('inspector-viewer');
  if (!container) return;
  hideViewerError();
  hideViewerPrompt();
  const overlay = document.createElement('div');
  overlay.className = 'viewer-error';
  overlay.id = 'viewer-error-overlay';
  overlay.innerHTML = `
    <div class="viewer-error-icon">!</div>
    <div class="viewer-error-title">${t('inspector.preview_failed')}</div>
    <div class="viewer-error-text">${t('inspector.preview_failed_text')}</div>
    <button class="btn" id="btn-retry-preview" style="margin-top: 8px;">${t('inspector.retry_preview')}</button>
  `;
  container.appendChild(overlay);

  // Wire up retry button
  const retryBtn = overlay.querySelector('#btn-retry-preview');
  if (retryBtn) {
    retryBtn.addEventListener('click', () => {
      const f = state.files.find(x => x.id === state.selectedFile);
      if (f) generatePreview(f);
    });
  }

  console.warn('Viewer error for', filename, detail || '');
}

function hideViewerError() {
  const existing = document.getElementById('viewer-error-overlay');
  if (existing) existing.remove();
}

function showViewerPrompt(file) {
  const container = document.getElementById('inspector-viewer');
  if (!container) return;
  hideViewerError();
  hideViewerPrompt();
  const overlay = document.createElement('div');
  overlay.className = 'viewer-prompt';
  overlay.id = 'viewer-prompt-overlay';

  const isFailed = file.previewStatus === 'failed';
  const title = isFailed ? t('inspector.preview_failed') : t('inspector.preview_pending_title');
  const text = isFailed ? t('inspector.preview_failed_text') : t('inspector.preview_pending_text');
  const btnLabel = isFailed ? t('inspector.retry_preview') : t('inspector.generate_preview');

  overlay.innerHTML = `
    <div class="viewer-prompt-icon">
      <svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="${isFailed ? 'var(--warn)' : 'var(--accent)'}" stroke-width="1.5">
        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/>
        <circle cx="12" cy="12" r="3"/>
      </svg>
    </div>
    <div class="viewer-prompt-title" style="${isFailed ? 'color: var(--warn);' : ''}">${title}</div>
    <div class="viewer-prompt-text">${text}</div>
    <button class="btn btn-primary" id="btn-generate-preview" style="margin-top: 8px;">${btnLabel}</button>
  `;
  container.appendChild(overlay);

  overlay.querySelector('#btn-generate-preview').addEventListener('click', () => {
    generatePreview(file);
  });
}

function hideViewerPrompt() {
  const existing = document.getElementById('viewer-prompt-overlay');
  if (existing) existing.remove();
}

// Show the SLA/resin preview overlay. SLA files have no 3D mesh, so instead of
// running the Three.js viewer we display the embedded thumbnail (extracted at
// import time) plus a metadata grid pulled from the slice header.
function showSLAPreview(file) {
  const container = document.getElementById('inspector-viewer');
  if (!container) return;
  hideViewerError();
  hideViewerPrompt();
  hideSLAPreview();

  // Hide the 3D viewer toolbar — wireframe/spin/fullscreen don't apply to a
  // static SLA preview image. They get re-shown the next time loadIntoInspector
  // runs for a mesh-based file.
  const controls = container.querySelector('.viewer-controls');
  if (controls) controls.style.display = 'none';

  const sla = (file.stats && file.stats.sla) || {};
  const formatLabel = (sla.format || file.subFormat || getSLASubFormat(file.name) || 'SLA').toUpperCase();
  const thumb = file.thumbnail;

  const overlay = document.createElement('div');
  overlay.className = 'viewer-sla';
  overlay.id = 'viewer-sla-overlay';

  const thumbHtml = thumb
    ? `<img class="viewer-sla-image" src="${escapeHtml(thumb)}" alt="Slice preview" />`
    : `<div class="viewer-sla-noimage">${t('sla.no_preview')}</div>`;

  overlay.innerHTML = `
    <div class="viewer-sla-badge">${escapeHtml(formatLabel)} · ${t('sla.sliced')}</div>
    ${thumbHtml}
    <div class="viewer-sla-meta">
      ${slaMetaRow(t('sla.layer_height'), sla.layerHeight ? `${sla.layerHeight.toFixed(3)} mm` : null)}
      ${slaMetaRow(t('sla.total_height'), sla.totalHeight ? `${sla.totalHeight.toFixed(2)} mm` : null)}
      ${slaMetaRow(t('sla.layer_count'), sla.layerCount ? sla.layerCount.toLocaleString() : null)}
      ${slaMetaRow(t('sla.exposure'), sla.exposureTime ? `${sla.exposureTime.toFixed(2)} s` : null)}
      ${slaMetaRow(t('sla.bottom_exposure'), sla.bottomExposureTime ? `${sla.bottomExposureTime.toFixed(2)} s` : null)}
      ${slaMetaRow(t('sla.bottom_layers'), sla.bottomLayers ? sla.bottomLayers.toString() : null)}
      ${slaMetaRow(t('sla.print_time'), sla.printTimeSeconds ? formatPrintTime(sla.printTimeSeconds) : null)}
      ${slaMetaRow(t('sla.resin_volume'), sla.resinVolumeML ? `${sla.resinVolumeML.toFixed(1)} ml` : null)}
    </div>
  `;
  container.appendChild(overlay);
}

function slaMetaRow(key, value) {
  if (!value) return '';
  return `
    <div class="viewer-sla-meta-row">
      <span class="viewer-sla-meta-key">${escapeHtml(key)}</span>
      <span class="viewer-sla-meta-val">${escapeHtml(value)}</span>
    </div>
  `;
}

function hideSLAPreview() {
  const existing = document.getElementById('viewer-sla-overlay');
  if (existing) existing.remove();
}

// Format a print duration in seconds as e.g. "3h 42m" or "12m 5s"
function formatPrintTime(seconds) {
  if (!seconds || !isFinite(seconds)) return '—';
  const h = Math.floor(seconds / 3600);
  const m = Math.floor((seconds % 3600) / 60);
  const s = Math.floor(seconds % 60);
  if (h > 0) return `${h}h ${m}m`;
  if (m > 0) return `${m}m ${s}s`;
  return `${s}s`;
}

async function generatePreview(file) {
  const loadingEl = document.getElementById('viewer-loading');
  hideViewerPrompt();
  hideViewerError();

  if (loadingEl) loadingEl.classList.add('show');

  const myToken = ++currentLoadToken;

  try {
    const parsed = await parseModelAsync((await serverStorage.fileData(file)).slice(0), file.name);

    // Bail if user moved on
    if (myToken !== currentLoadToken) {
      if (parsed && parsed.geometry) parsed.geometry.dispose();
      return;
    }

    if (parsed) {
      // Render thumbnail and persist it so we don't have to re-parse next time
      try {
        const thumb = renderThumbnail(parsed.geometry, parsed.type);
        if (thumb) file.thumbnail = thumb;
      } catch (thumbErr) {
        console.warn('Thumbnail render failed:', thumbErr);
      }
      file.stats = parsed.stats;
      file.modelCheck = parsed.modelCheck;
      file.ai = analyzeModelLocally(file, parsed);
      file.previewStatus = 'ready';
      await saveFile(file);

      // Cache and display
      cacheGeometry(file.id, {
        geometry: parsed.geometry.clone(),
        type: parsed.type,
        stats: parsed.stats,
      });
      loadIntoInspector(parsed.geometry);

      // Refresh card to show new thumbnail and updated stats
      renderGrid();
      // Update inspector to show stats that may have just been computed
      const updated = state.files.find(x => x.id === file.id);
      if (updated && state.selectedFile === file.id) {
        // Only re-render the details, not the full inspector (would re-trigger load)
        renderInspectorDetails(updated);
      }
    } else {
      file.previewStatus = 'failed';
      await saveFile(file);
      showViewerPrompt(file);
    }
  } catch (e) {
    console.error('Generate preview error:', e);
    // A connection/authentication failure is not a corrupt model. Do not persist
    // a failed-preview state or retry a write after a failed storage request.
    if (e.name === 'ArchiveStorageError') return;
    file.previewStatus = 'failed';
    await saveFile(file);
    showViewerPrompt(file);
  } finally {
    if (myToken === currentLoadToken && loadingEl) {
      loadingEl.classList.remove('show');
    }
  }
}

async function loadFileIntoInspector(file) {
  const myToken = ++currentLoadToken;
  const loadingEl = document.getElementById('viewer-loading');

  // Remove any previous error/prompt/SLA overlay
  hideViewerError();
  hideViewerPrompt();
  hideSLAPreview();

  // SLA/resin files have no 3D mesh — handle these before checking the geometry
  // cache (which is mesh-only). The thumbnail and metadata were already extracted
  // at import time, so this is just a render of stored data.
  const earlyType = file.type || getFileType(file.name);
  if (earlyType === 'sla') {
    if (loadingEl) loadingEl.classList.remove('show');
    showSLAPreview(file);
    return;
  }

  // Check cache first — instant load if we've seen this file recently
  const cached = getCachedGeometry(file.id);
  if (cached) {
    if (loadingEl) loadingEl.classList.remove('show');
    loadIntoInspector(cached.geometry);
    return;
  }

  // Clear current mesh while preparing the next view
  if (inspector.mesh) {
    inspector.scene.remove(inspector.mesh);
    inspector.mesh.geometry.dispose();
    inspector.mesh.material.dispose();
    inspector.mesh = null;
  }
  if (inspector.wire) {
    inspector.scene.remove(inspector.wire);
    inspector.wire.geometry.dispose();
    inspector.wire.material.dispose();
    inspector.wire = null;
  }

  // For 3MF with no preview yet (or a previously failed attempt), don't auto-parse —
  // 3MF parsing is slow and unreliable, so we let the user trigger it manually.
  const fileType = file.type || getFileType(file.name);
  if (fileType === '3mf' && (file.previewStatus === 'pending' || file.previewStatus === 'failed')) {
    showViewerPrompt(file);
    return;
  }

  // Not cached — show loader, then parse async
  if (loadingEl) loadingEl.classList.add('show');

  try {
    const parsed = await parseModelAsync((await serverStorage.fileData(file)).slice(0), file.name);

    // If user selected a different file in the meantime, abandon this result
    if (myToken !== currentLoadToken) {
      if (parsed && parsed.geometry) parsed.geometry.dispose();
      return;
    }

    if (parsed) {
      const cacheEntry = {
        geometry: parsed.geometry.clone(),
        type: parsed.type,
        stats: parsed.stats,
      };
      cacheGeometry(file.id, cacheEntry);

      loadIntoInspector(parsed.geometry);
      // Existing models gain diagnostics without requiring a new thumbnail.
      const needsThumbnail = !file.thumbnail || file.previewStatus !== 'ready' || !file.modelCheck?.checked;
      if (!file.modelCheck?.checked) {
        file.modelCheck = parsed.modelCheck;
        file.stats = parsed.stats;
        await saveFile(file);
        if (state.selectedFile === file.id) renderInspectorDetails(file);
      }

      // Persist a thumbnail for the card if we don't have one yet, or if the
      // status indicates a previously failed/pending state. This is a one-time
      // cost per file: next time you open the app the card already has its preview.
      if (needsThumbnail) {
        try {
          const thumb = renderThumbnail(parsed.geometry, parsed.type);
          if (thumb) {
            file.thumbnail = thumb;
            file.stats = parsed.stats;
            file.modelCheck = parsed.modelCheck;
            file.ai = analyzeModelLocally(file, parsed);
            file.previewStatus = 'ready';
            await saveFile(file);
            renderGrid();
            // Refresh the inspector details since stats may have changed
            if (state.selectedFile === file.id) {
              renderInspectorDetails(file);
            }
          }
        } catch (thumbErr) {
          console.warn('Auto thumbnail generation failed:', thumbErr);
        }
      }
    } else {
      showViewerError(file.name);
    }
  } catch (e) {
    console.error('Inspector load error:', e);
    showViewerError(file.name, e.message);
  } finally {
    if (myToken === currentLoadToken && loadingEl) {
      loadingEl.classList.remove('show');
    }
  }
}

// ============ THREE.JS PREVIEW (thumbnail renderer) ============
const thumbRenderer = new THREE.WebGLRenderer({ antialias: true, alpha: true, preserveDrawingBuffer: false });
thumbRenderer.setSize(400, 400);
thumbRenderer.setPixelRatio(1);

function renderThumbnail(geometry, type = 'stl') {
  const scene = new THREE.Scene();
  const camera = new THREE.PerspectiveCamera(35, 1, 0.1, 10000);

  const geo = geometry.clone();
  geo.center();
  geo.computeBoundingSphere();
  const radius = geo.boundingSphere.radius;

  // If bounding sphere has no useful size, abort — invisible mesh would render as empty
  if (!radius || !isFinite(radius) || radius < 1e-9) {
    geo.dispose();
    console.warn('Thumbnail: geometry has zero/invalid bounding sphere');
    return null;
  }

  const material = new THREE.MeshStandardMaterial({
    color: 0xd4ff3a,
    metalness: 0.2,
    roughness: 0.55,
    flatShading: false,
  });

  const mesh = new THREE.Mesh(geo, material);
  // STL geometries are typically authored Z-up, so we rotate the mesh into Y-up.
  // 3MF geometries already have the Z-up→Y-up rotation baked in by our parsers,
  // so we skip the rotation step for them — otherwise the model ends up on its
  // side or upside down and falls outside the camera frustum.
  if (type !== '3mf') {
    mesh.rotation.x = -Math.PI / 2;
  }
  mesh.rotation.z = Math.PI / 6;
  scene.add(mesh);

  // Edges overlay
  const edges = new THREE.EdgesGeometry(geo, 30);
  const lineMat = new THREE.LineBasicMaterial({ color: 0x0a0b09, transparent: true, opacity: 0.4 });
  const wire = new THREE.LineSegments(edges, lineMat);
  wire.rotation.x = mesh.rotation.x;
  wire.rotation.z = mesh.rotation.z;
  scene.add(wire);

  const dist = radius * 2.6;
  camera.position.set(dist * 0.7, dist * 0.6, dist);
  camera.lookAt(0, 0, 0);

  scene.add(new THREE.AmbientLight(0xffffff, 0.4));
  const dir1 = new THREE.DirectionalLight(0xffffff, 1.2);
  dir1.position.set(5, 10, 7);
  scene.add(dir1);
  const dir2 = new THREE.DirectionalLight(0xd4ff3a, 0.3);
  dir2.position.set(-5, -5, 3);
  scene.add(dir2);

  thumbRenderer.render(scene, camera);
  const dataURL = thumbRenderer.domElement.toDataURL('image/png');

  // cleanup
  geo.dispose();
  edges.dispose();
  material.dispose();
  lineMat.dispose();

  return dataURL;
}

// ============ INSPECTOR VIEWER ============
let inspector = {
  scene: null,
  camera: null,
  renderer: null,
  controls: null,
  mesh: null,
  wire: null,
  raf: null,
  spin: true,
  wireframe: false,
};

let fullscreenPreview = {
  scene: null,
  camera: null,
  renderer: null,
  controls: null,
  mesh: null,
  wire: null,
  raf: null,
  spin: true,
  wireframe: false,
  geometry: null,
  resizeObserver: null,
};

function initFullscreenPreviewViewer() {
  const container = document.getElementById('preview-modal-viewer');
  if (!container || fullscreenPreview.renderer) return;

  const w = Math.max(1, container.clientWidth);
  const h = Math.max(1, container.clientHeight);

  fullscreenPreview.scene = new THREE.Scene();
  fullscreenPreview.camera = new THREE.PerspectiveCamera(35, w / h, 0.1, 10000);
  fullscreenPreview.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
  fullscreenPreview.renderer.setSize(w, h);
  fullscreenPreview.renderer.setPixelRatio(window.devicePixelRatio);
  container.appendChild(fullscreenPreview.renderer.domElement);

  fullscreenPreview.controls = new OrbitControls(fullscreenPreview.camera, fullscreenPreview.renderer.domElement);
  fullscreenPreview.controls.enableDamping = true;
  fullscreenPreview.controls.dampingFactor = 0.08;

  fullscreenPreview.scene.add(new THREE.AmbientLight(0xffffff, 0.45));
  const dir1 = new THREE.DirectionalLight(0xffffff, 1.25);
  dir1.position.set(5, 10, 7);
  fullscreenPreview.scene.add(dir1);
  const dir2 = new THREE.DirectionalLight(0xd4ff3a, 0.35);
  dir2.position.set(-5, -5, 3);
  fullscreenPreview.scene.add(dir2);

  const grid = new THREE.GridHelper(260, 26, 0x2d2f2a, 0x1a1c19);
  grid.position.y = -0.01;
  fullscreenPreview.scene.add(grid);

  function loop() {
    fullscreenPreview.raf = requestAnimationFrame(loop);
    if (fullscreenPreview.spin && fullscreenPreview.mesh) {
      fullscreenPreview.mesh.rotation.z += 0.004;
      if (fullscreenPreview.wire) fullscreenPreview.wire.rotation.z = fullscreenPreview.mesh.rotation.z;
    }
    fullscreenPreview.controls.update();
    fullscreenPreview.renderer.render(fullscreenPreview.scene, fullscreenPreview.camera);
  }
  loop();

  fullscreenPreview.resizeObserver = new ResizeObserver(() => resizeFullscreenPreview());
  fullscreenPreview.resizeObserver.observe(container);
}

function resizeFullscreenPreview() {
  const container = document.getElementById('preview-modal-viewer');
  if (!container || !fullscreenPreview.renderer) return;
  const w = container.clientWidth;
  const h = container.clientHeight;
  if (w === 0 || h === 0) return;
  fullscreenPreview.camera.aspect = w / h;
  fullscreenPreview.camera.updateProjectionMatrix();
  fullscreenPreview.renderer.setSize(w, h);
}

function clearFullscreenPreviewModel() {
  if (fullscreenPreview.mesh) {
    fullscreenPreview.scene.remove(fullscreenPreview.mesh);
    fullscreenPreview.mesh.geometry.dispose();
    fullscreenPreview.mesh.material.dispose();
    fullscreenPreview.mesh = null;
  }
  if (fullscreenPreview.wire) {
    fullscreenPreview.scene.remove(fullscreenPreview.wire);
    fullscreenPreview.wire.geometry.dispose();
    fullscreenPreview.wire.material.dispose();
    fullscreenPreview.wire = null;
  }
}

function loadIntoFullscreenPreview(geometry) {
  initFullscreenPreviewViewer();
  clearFullscreenPreviewModel();
  fullscreenPreview.geometry = geometry;

  const geo = geometry.clone();
  geo.center();
  geo.computeBoundingSphere();
  const radius = geo.boundingSphere.radius || 1;

  const material = new THREE.MeshStandardMaterial({
    color: 0xd4ff3a,
    metalness: 0.2,
    roughness: 0.5,
    wireframe: fullscreenPreview.wireframe,
  });

  fullscreenPreview.mesh = new THREE.Mesh(geo, material);
  fullscreenPreview.mesh.rotation.x = -Math.PI / 2;
  fullscreenPreview.scene.add(fullscreenPreview.mesh);

  const bb = new THREE.Box3().setFromObject(fullscreenPreview.mesh);
  fullscreenPreview.mesh.position.y -= bb.min.y;

  if (!fullscreenPreview.wireframe) {
    const edges = new THREE.EdgesGeometry(geo, 30);
    const lineMat = new THREE.LineBasicMaterial({ color: 0x0a0b09, transparent: true, opacity: 0.5 });
    fullscreenPreview.wire = new THREE.LineSegments(edges, lineMat);
    fullscreenPreview.wire.rotation.x = fullscreenPreview.mesh.rotation.x;
    fullscreenPreview.wire.position.copy(fullscreenPreview.mesh.position);
    fullscreenPreview.scene.add(fullscreenPreview.wire);
  }

  const dist = radius * 2.4;
  fullscreenPreview.camera.position.set(dist * 0.8, dist * 0.65, dist);
  fullscreenPreview.controls.target.set(0, radius * 0.5, 0);
  fullscreenPreview.controls.update();
  setTimeout(resizeFullscreenPreview, 0);
}

async function openFullscreenPreview(fileId = state.selectedFile) {
  if (!fileId) return;
  const file = state.files.find(x => x.id === fileId);
  if (!file) return;

  state.selectedFile = file.id;
  renderGrid();
  renderInspector();

  document.getElementById('preview-modal-name').textContent = file.ai?.displayName || file.name;
  document.getElementById('preview-modal').classList.add('show');
  document.getElementById('preview-modal').setAttribute('aria-hidden', 'false');

  let cached = getCachedGeometry(file.id);
  if (!cached) {
    try {
      cached = await parseModelAsync((await serverStorage.fileData(file)).slice(0), file.name);
      if (!cached?.geometry) throw new Error('Preview niet beschikbaar.');
      cacheGeometry(file.id, cached);
    } catch (err) {
      showToast('Kon fullscreen preview niet laden.', 'warn');
      closeFullscreenPreview();
      return;
    }
  }
  loadIntoFullscreenPreview(cached.geometry);
}

function closeFullscreenPreview() {
  const modal = document.getElementById('preview-modal');
  modal.classList.remove('show');
  modal.setAttribute('aria-hidden', 'true');
}

function resetFullscreenPreview() {
  if (fullscreenPreview.geometry) loadIntoFullscreenPreview(fullscreenPreview.geometry);
}

function toggleFullscreenWireframe(btn) {
  fullscreenPreview.wireframe = !fullscreenPreview.wireframe;
  btn.classList.toggle('btn-primary', fullscreenPreview.wireframe);
  if (fullscreenPreview.geometry) loadIntoFullscreenPreview(fullscreenPreview.geometry);
}

function toggleFullscreenSpin(btn) {
  fullscreenPreview.spin = !fullscreenPreview.spin;
  btn.textContent = fullscreenPreview.spin ? 'Rotatie aan' : 'Rotatie uit';
  btn.classList.toggle('btn-primary', fullscreenPreview.spin);
}


function initInspectorViewer() {
  const container = document.getElementById('inspector-viewer');
  const w = container.clientWidth;
  const h = container.clientHeight;

  inspector.scene = new THREE.Scene();
  inspector.camera = new THREE.PerspectiveCamera(35, w / h, 0.1, 10000);
  inspector.renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
  inspector.renderer.setSize(w, h);
  inspector.renderer.setPixelRatio(window.devicePixelRatio);
  container.appendChild(inspector.renderer.domElement);

  inspector.controls = new OrbitControls(inspector.camera, inspector.renderer.domElement);
  inspector.controls.enableDamping = true;
  inspector.controls.dampingFactor = 0.08;

  inspector.scene.add(new THREE.AmbientLight(0xffffff, 0.4));
  const dir1 = new THREE.DirectionalLight(0xffffff, 1.2);
  dir1.position.set(5, 10, 7);
  inspector.scene.add(dir1);
  const dir2 = new THREE.DirectionalLight(0xd4ff3a, 0.3);
  dir2.position.set(-5, -5, 3);
  inspector.scene.add(dir2);

  // Grid floor
  const grid = new THREE.GridHelper(200, 20, 0x2d2f2a, 0x1a1c19);
  grid.position.y = -0.01;
  inspector.scene.add(grid);

  function loop() {
    inspector.raf = requestAnimationFrame(loop);
    if (inspector.spin && inspector.mesh) {
      inspector.mesh.rotation.z += 0.005;
      if (inspector.wire) inspector.wire.rotation.z = inspector.mesh.rotation.z;
    }
    inspector.controls.update();
    inspector.renderer.render(inspector.scene, inspector.camera);
  }
  loop();

  // Resize observer
  new ResizeObserver(() => {
    const w = container.clientWidth;
    const h = container.clientHeight;
    if (w === 0 || h === 0) return;
    inspector.camera.aspect = w / h;
    inspector.camera.updateProjectionMatrix();
    inspector.renderer.setSize(w, h);
  }).observe(container);
}

function loadIntoInspector(geometry) {
  // Re-show the 3D viewer toolbar in case it was hidden by a previous SLA file.
  // Also clear any leftover SLA overlay that might still be in the DOM.
  hideSLAPreview();
  const inspectorContainer = document.getElementById('inspector-viewer');
  if (inspectorContainer) {
    const controls = inspectorContainer.querySelector('.viewer-controls');
    if (controls) controls.style.display = '';
  }

  if (inspector.mesh) {
    inspector.scene.remove(inspector.mesh);
    inspector.mesh.geometry.dispose();
    inspector.mesh.material.dispose();
    inspector.mesh = null;
  }
  if (inspector.wire) {
    inspector.scene.remove(inspector.wire);
    inspector.wire.geometry.dispose();
    inspector.wire.material.dispose();
    inspector.wire = null;
  }

  const geo = geometry.clone();
  geo.center();
  geo.computeBoundingSphere();
  const radius = geo.boundingSphere.radius;

  const material = new THREE.MeshStandardMaterial({
    color: 0xd4ff3a,
    metalness: 0.2,
    roughness: 0.5,
    wireframe: inspector.wireframe,
  });

  inspector.mesh = new THREE.Mesh(geo, material);
  inspector.mesh.rotation.x = -Math.PI / 2;
  inspector.scene.add(inspector.mesh);

  // place on grid
  const bb = new THREE.Box3().setFromObject(inspector.mesh);
  inspector.mesh.position.y -= bb.min.y;

  if (!inspector.wireframe) {
    const edges = new THREE.EdgesGeometry(geo, 30);
    const lineMat = new THREE.LineBasicMaterial({ color: 0x0a0b09, transparent: true, opacity: 0.5 });
    inspector.wire = new THREE.LineSegments(edges, lineMat);
    inspector.wire.rotation.x = inspector.mesh.rotation.x;
    inspector.wire.position.copy(inspector.mesh.position);
    inspector.scene.add(inspector.wire);
  }

  const dist = radius * 2.8;
  inspector.camera.position.set(dist * 0.8, dist * 0.7, dist);
  inspector.controls.target.set(0, radius * 0.5, 0);
  inspector.controls.update();
}

// ============ RENDERING ============
function getFilesForView() {
  let files = state.files;
  if (state.activeCollection !== null) {
    files = files.filter(f => f.collectionId === state.activeCollection);
  }
  if (state.search) {
    const q = state.search.toLowerCase();
    files = files.filter(f =>
      f.name.toLowerCase().includes(q) ||
      (f.description && f.description.toLowerCase().includes(q)) ||
      (Array.isArray(f.tags) && f.tags.join(' ').toLowerCase().includes(q)) ||
      (f.ai && [f.ai.displayName, f.ai.modelType, ...(f.ai.tags || [])].join(' ').toLowerCase().includes(q))
    );
  }
  return files;
}

function renderCollections() {
  const list = document.getElementById('collections-list');
  document.getElementById('col-count').textContent = state.collections.length;

  const allCount = state.files.length;
  const uncatCount = state.files.filter(f => !f.collectionId).length;

  let html = `
    <div class="collection-item ${state.activeCollection === null ? 'active' : ''}" data-id="">
      <div class="col-info">
        <div class="col-icon"><svg viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg></div>
        <span class="col-name">${t('sidebar.all_files')}</span>
      </div>
      <span class="col-count">${allCount}</span>
    </div>
    <div class="collection-item ${state.activeCollection === 'uncategorized' ? 'active' : ''}" data-id="uncategorized">
      <div class="col-info">
        <div class="col-icon"><svg viewBox="0 0 24 24"><path d="M3 7h18M3 12h18M3 17h12"/></svg></div>
        <span class="col-name">${t('sidebar.uncategorized')}</span>
      </div>
      <span class="col-count">${uncatCount}</span>
    </div>
  `;

  state.collections.forEach(c => {
    const count = state.files.filter(f => f.collectionId === c.id).length;
    html += `
      <div class="collection-item ${state.activeCollection === c.id ? 'active' : ''}" data-id="${c.id}">
        <div class="col-info">
          <div class="col-icon"><svg viewBox="0 0 24 24"><path d="M3 7l9-4 9 4-9 4-9-4z"/><path d="M3 12l9 4 9-4"/><path d="M3 17l9 4 9-4"/></svg></div>
          <span class="col-name" data-name="${c.id}">${escapeHtml(c.name)}</span>
        </div>
        <span class="col-count">${count}</span>
        <div class="col-actions">
          <button class="col-edit" data-edit="${c.id}" title="${t('alert.rename_collection_tooltip')}">
            <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
          </button>
          <button class="col-delete" data-delete="${c.id}" title="${t('alert.delete_collection_tooltip')}">×</button>
        </div>
      </div>
    `;
  });

  list.innerHTML = html;

  list.querySelectorAll('.collection-item').forEach(el => {
    if (el.dataset.id) {
      el.addEventListener('dragover', e => {
        if (!draggedArchiveFile && !Array.from(e.dataTransfer?.types || []).includes('Files')) return;
        e.preventDefault();
        if (e.dataTransfer) e.dataTransfer.dropEffect = draggedArchiveFile ? 'move' : 'copy';
        document.querySelectorAll('.collection-item.drop-target').forEach(other => {
          if (other !== el) other.classList.remove('drop-target');
        });
        el.classList.add('drop-target');
      });
      el.addEventListener('dragleave', e => {
        if (!el.contains(e.relatedTarget)) el.classList.remove('drop-target');
      });
    }
    el.addEventListener('click', e => {
      if (e.target.closest('[data-delete]')) return;
      if (e.target.closest('[data-edit]')) return;
      if (e.target.classList.contains('col-name-input')) return;
      const id = el.dataset.id;
      if (state.bulkBusy) return;
      state.selectedIds.clear();
      state.activeCollection = id === '' ? null : id;
      render();
    });
  });

  // Double-click on name to edit
  list.querySelectorAll('[data-name]').forEach(span => {
    span.addEventListener('dblclick', e => {
      e.stopPropagation();
      startCollectionEdit(span.dataset.name);
    });
  });

  // Edit button
  list.querySelectorAll('[data-edit]').forEach(btn => {
    btn.addEventListener('click', e => {
      e.stopPropagation();
      startCollectionEdit(btn.dataset.edit);
    });
  });

  list.querySelectorAll('[data-delete]').forEach(btn => {
    btn.addEventListener('click', async e => {
      e.stopPropagation();
      const id = btn.dataset.delete;
      if (!confirm(t('alert.confirm_delete_collection'))) return;
      await dbDelete('collections', id);
      for (const f of state.files.filter(f => f.collectionId === id)) f.collectionId = null;
      state.collections = state.collections.filter(c => c.id !== id);
      if (state.activeCollection === id) state.activeCollection = null;
      render();
    });
  });

  // Update move select
  const sel = document.getElementById('move-select');
  let optHtml = `<option value="">${t('inspector.move_placeholder')}</option>`;
  optHtml += `<option value="__none__">${t('inspector.move_uncategorized')}</option>`;
  state.collections.forEach(c => {
    optHtml += `<option value="${c.id}">${escapeHtml(c.name)}</option>`;
  });
  sel.innerHTML = optHtml;
}

function escapeHtml(s) {
  return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function startCollectionEdit(collectionId) {
  const collection = state.collections.find(c => c.id === collectionId);
  if (!collection) return;

  const span = document.querySelector(`[data-name="${collectionId}"]`);
  if (!span) return;

  // Replace the span with an input field
  const input = document.createElement('input');
  input.type = 'text';
  input.className = 'col-name-input';
  input.value = collection.name;
  input.maxLength = 40;
  span.replaceWith(input);
  input.focus();
  input.select();

  let finished = false;

  const commit = async () => {
    if (finished) return;
    finished = true;
    const newName = input.value.trim();
    if (newName && newName !== collection.name) {
      // Check for duplicate names (case-insensitive)
      const conflict = state.collections.find(c =>
        c.id !== collectionId && c.name.toLowerCase() === newName.toLowerCase()
      );
      if (conflict) {
        showToast(t('toast.collection_name_exists'), 'warn');
        cancel();
        return;
      }
      collection.name = newName;
      await dbPut('collections', collection);
    }
    render();
  };

  const cancel = () => {
    if (finished) return;
    finished = true;
    render();
  };

  input.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
      e.preventDefault();
      commit();
    } else if (e.key === 'Escape') {
      e.preventDefault();
      cancel();
    }
  });

  input.addEventListener('blur', () => {
    // Small delay so click on edit button on a different collection doesn't double-fire
    setTimeout(commit, 50);
  });

  input.addEventListener('click', e => e.stopPropagation());
}

function renderGrid() {
  const grid = document.getElementById('grid');
  const empty = document.getElementById('empty-state');
  const files = getFilesForView();

  // header
  let label = t('sidebar.all_files');
  if (state.activeCollection === 'uncategorized') label = t('sidebar.uncategorized');
  else if (state.activeCollection) {
    const c = state.collections.find(c => c.id === state.activeCollection);
    if (c) label = c.name;
  }
  document.getElementById('current-collection').textContent = label;
  document.getElementById('current-count').textContent = t('toolbar.items', files.length);

  if (files.length === 0) {
    grid.style.display = 'none';
    empty.style.display = 'flex';
    if (state.files.length > 0) {
      empty.querySelector('.empty-title').textContent = t('empty.no_results_title');
      empty.querySelector('.empty-text').textContent = state.search
        ? t('empty.no_results_search')
        : t('empty.no_results_collection');
      empty.querySelector('.btn').style.display = 'none';
    } else {
      empty.querySelector('.empty-title').textContent = t('empty.no_files_title');
      empty.querySelector('.empty-text').textContent = t('empty.no_files_text');
      empty.querySelector('.btn').textContent = t('header.import_files');
      empty.querySelector('.btn').style.display = '';
    }
    return;
  }

  empty.style.display = 'none';
  grid.style.display = 'grid';

  grid.innerHTML = files.map(f => {
    const fileType = (f.type || getFileType(f.name) || 'stl').toUpperCase();
    const hasDescription = f.description && f.description.trim().length > 0;
    const descPreview = hasDescription ? f.description.slice(0, 200) : '';
    const status = f.previewStatus || (f.thumbnail ? 'ready' : 'pending');
    const userTags = Array.isArray(f.tags) ? f.tags : [];

    let previewContent;
    if (f.thumbnail) {
      previewContent = `<img src="${escapeHtml(f.thumbnail)}" style="width:100%;height:100%;object-fit:cover" />`;
    } else if (status === 'failed') {
      previewContent = `<div class="card-placeholder card-placeholder-failed">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <circle cx="12" cy="12" r="9"/><path d="M12 8v4M12 16h.01"/>
        </svg>
        <div>${t('inspector.preview_failed')}</div>
      </div>`;
    } else if (status === 'pending' && fileType === '3MF') {
      previewContent = `<div class="card-placeholder">
        <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
          <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7z"/>
          <circle cx="12" cy="12" r="3"/>
        </svg>
        <div>${t('inspector.preview_pending_card')}</div>
      </div>`;
    } else {
      previewContent = `<div class="card-loading">${t('inspector.rendering')}</div>`;
    }

    return `
    <div class="card ${state.selectedFile === f.id ? 'selected' : ''} ${state.selectedIds.has(f.id) ? 'bulk-selected' : ''}" draggable="true" data-id="${f.id}" title="${escapeHtml([f.name, descPreview, ...userTags.map(tag => '#' + tag)].filter(Boolean).join(' · '))}">
      <label class="card-select" draggable="false"><input class="card-check" type="checkbox" aria-label="${escapeHtml(t('bulk.select_file', f.name))}" ${state.selectedIds.has(f.id) ? 'checked' : ''} ${state.bulkBusy ? 'disabled' : ''}></label>
      <div class="card-preview">
        <div class="card-badge">${fileType}</div>
        ${hasDescription ? `<div class="card-desc-mark" title="${t('detail.has_description')}">≡</div>` : ''}
        ${previewContent}
      </div>
      <div class="card-info">
        <div class="card-name" title="${escapeHtml(f.name)}">${escapeHtml(f.name)}</div>
        <div class="card-meta">
          <span>${formatBytes(f.size)}</span>
          <span>${typeof f.stats?.triangles === 'number' ? f.stats.triangles.toLocaleString() + ' △' : '—'}</span>
        </div>
        ${userTags.length ? `<div class="card-user-tags">${userTags.slice(0, 3).map(tag => `<span class="card-user-tag">#${escapeHtml(tag)}</span>`).join('')}${userTags.length > 3 ? `<span class="card-user-tag">+${userTags.length - 3}</span>` : ''}</div>` : ''}
      </div>
    </div>
  `;}).join('');

  grid.querySelectorAll('.card').forEach(el => {
    el.querySelectorAll('img').forEach(img => { img.draggable = false; });
    el.addEventListener('dragstart', e => {
      if (state.bulkBusy || e.target.closest('.card-select')) { e.preventDefault(); return; }
      if (!e.dataTransfer) return;
      draggedArchiveFile = el.dataset.id;
      draggedArchiveFiles = state.selectedIds.has(draggedArchiveFile) ? [...state.selectedIds] : [draggedArchiveFile];
      e.dataTransfer.setData(ARCHIVE_DRAG_TYPE, draggedArchiveFile);
      e.dataTransfer.effectAllowed = 'move';
      el.classList.add('is-dragging');
    });
    el.addEventListener('dragend', clearArchiveDrag);
    el.querySelector('.card-select').addEventListener('click', e => e.stopPropagation());
    el.querySelector('.card-check').addEventListener('change', () => toggleFileSelection(el.dataset.id));
    el.addEventListener('click', e => {
      if (e.ctrlKey || e.metaKey) toggleFileSelection(el.dataset.id);
      else if (!state.bulkBusy) { state.selectedIds.clear(); selectFile(el.dataset.id); }
    });

    const preview = el.querySelector('.card-preview');
    if (preview) {
      preview.addEventListener('click', e => {
        e.stopPropagation();
        if (state.bulkBusy) return;
        if (e.ctrlKey || e.metaKey) { toggleFileSelection(el.dataset.id); return; }
        openFullscreenPreview(el.dataset.id);
      });
    }
  });
}

function renderInspector() {
  const empty = document.getElementById('inspector-empty');
  const content = document.getElementById('inspector-content');
  if (!state.selectedFile) {
    empty.style.display = 'flex';
    content.style.display = 'none';
    return;
  }

  const f = state.files.find(x => x.id === state.selectedFile);
  if (!f) {
    state.selectedFile = null;
    return renderInspector();
  }

  empty.style.display = 'none';
  content.style.display = 'flex';

  // Init viewer if needed
  if (!inspector.scene) initInspectorViewer();

  // Load geometry into viewer asynchronously so the UI stays responsive
  // and the loading indicator gets a chance to render.
  loadFileIntoInspector(f);

  // Render the detail panel (separate so generatePreview can refresh it)
  renderInspectorDetails(f);
}

function renderSmartScanPanel(f) {
  const ai = f.ai || analyzeModelLocally(f, null);
  if (!ai) return '';
  const advice = ai.printAdvice || {};
  const tags = Array.isArray(ai.tags) ? ai.tags : [];
  const support = advice.supportNeeded ? 'Ja' : 'Nee';
  const confidencePct = Math.round((ai.confidence || 0) * 100);

  return `
    <div class="description-block">
      <div class="description-label">
        <span>Smart Scan</span>
        <span class="char-count">${confidencePct}% confidence</span>
      </div>
      <div class="detail-row">
        <span class="detail-key">Naam</span>
        <span class="detail-val accent">${escapeHtml(ai.displayName || f.name)}</span>
      </div>
      <div class="detail-row">
        <span class="detail-key">Model</span>
        <span class="detail-val">${escapeHtml(smartTypeLabel(ai.modelType || 'unknown-model'))}</span>
      </div>
      <div class="detail-row">
        <span class="detail-key">Oriëntatie</span>
        <span class="detail-val">${escapeHtml(advice.orientation || '—')}</span>
      </div>
      <div class="detail-row">
        <span class="detail-key">Support</span>
        <span class="detail-val ${advice.supportNeeded ? 'accent' : ''}">${support}</span>
      </div>
      <div class="detail-row">
        <span class="detail-key">Moeilijkheid</span>
        <span class="detail-val">${escapeHtml(advice.difficulty || '—')}</span>
      </div>
      <div class="detail-row">
        <span class="detail-key">Reden</span>
        <span class="detail-val">${escapeHtml(advice.reason || '—')}</span>
      </div>
      ${tags.length ? `<div style="margin-top:10px;display:flex;flex-wrap:wrap;gap:6px;">${tags.map(tag => `<span class="about-tag">${escapeHtml(tag)}</span>`).join('')}</div>` : ''}
    </div>
  `;
}

// Render the stats rows in the Info tab. STL/3MF files get bounding-box dimensions;
// SLA files get slice metadata (layer height, layer count, exposure) since their
// stats.width/depth/height are bed/print-volume placeholders, not a real bounding box.
function renderInfoStatsRows(f, stats) {
  const fileType = (f.type || getFileType(f.name) || '').toLowerCase();
  const sla = stats && stats.sla;

  if (fileType === 'sla' && sla) {
    const rows = [];
    if (sla.layerHeight) {
      rows.push(slaInfoRow(t('sla.layer_height'), `${sla.layerHeight.toFixed(3)} mm`));
    }
    if (sla.totalHeight) {
      rows.push(slaInfoRow(t('sla.total_height'), `${sla.totalHeight.toFixed(2)} mm`));
    }
    if (sla.layerCount) {
      rows.push(slaInfoRow(t('sla.layer_count'), sla.layerCount.toLocaleString()));
    }
    if (sla.exposureTime) {
      rows.push(slaInfoRow(t('sla.exposure'), `${sla.exposureTime.toFixed(2)} s`));
    }
    if (sla.printTimeSeconds) {
      rows.push(slaInfoRow(t('sla.print_time'), formatPrintTime(sla.printTimeSeconds)));
    }
    if (sla.resinVolumeML) {
      rows.push(slaInfoRow(t('sla.resin_volume'), `${sla.resinVolumeML.toFixed(1)} ml`));
    }
    if (rows.length === 0) {
      // Header parsed but no usable fields — fall back to "no data"
      rows.push(slaInfoRow(t('sla.format'), (sla.format || f.subFormat || 'SLA').toUpperCase()));
    }
    return rows.join('');
  }

  // STL / 3MF: bounding box dimensions
  return `
    <div class="detail-row">
      <span class="detail-key">${t('detail.width')}</span>
      <span class="detail-val">${Number.isFinite(stats?.width) ? stats.width.toFixed(2) + ' mm' : '—'}</span>
    </div>
    <div class="detail-row">
      <span class="detail-key">${t('detail.height')}</span>
      <span class="detail-val">${Number.isFinite(stats?.height) ? stats.height.toFixed(2) + ' mm' : '—'}</span>
    </div>
    <div class="detail-row">
      <span class="detail-key">${t('detail.depth')}</span>
      <span class="detail-val">${Number.isFinite(stats?.depth) ? stats.depth.toFixed(2) + ' mm' : '—'}</span>
    </div>
  `;
}

function slaInfoRow(key, value) {
  return `
    <div class="detail-row">
      <span class="detail-key">${escapeHtml(key)}</span>
      <span class="detail-val">${escapeHtml(value)}</span>
    </div>
  `;
}

// In the File tab, mesh-based files show triangle count; SLA files show resolution
// in pixels instead, since they have no triangles.
function renderFileTabExtraRow(f, stats) {
  const fileType = (f.type || getFileType(f.name) || '').toLowerCase();
  const sla = stats && stats.sla;
  if (fileType === 'sla' && sla && sla.resolutionX && sla.resolutionY) {
    return `
      <div class="detail-row">
        <span class="detail-key">${escapeHtml(t('sla.resolution'))}</span>
        <span class="detail-val">${escapeHtml(sla.resolutionX)} × ${escapeHtml(sla.resolutionY)} px</span>
      </div>
    `;
  }
  return `
    <div class="detail-row">
      <span class="detail-key">${t('detail.triangles')}</span>
      <span class="detail-val">${stats && stats.triangles ? stats.triangles.toLocaleString() : '—'}</span>
    </div>
  `;
}

function renderInspectorDetails(f) {
  const stats = f.stats || {};
  const collection = state.collections.find(c => c.id === f.collectionId);
  const fileType = (f.type || getFileType(f.name) || 'stl').toUpperCase();
  const description = f.description || '';
  const userTags = Array.isArray(f.tags) ? f.tags : [];

  // Map our lang code to a date locale
  const dateLocale = currentLang === 'pap' ? 'nl-NL'
    : currentLang === 'en' ? 'en-US'
    : currentLang === 'de' ? 'de-DE'
    : currentLang === 'es' ? 'es-ES'
    : 'nl-NL';

  const detailsEl = document.getElementById('inspector-details');
  const viewerEl = document.getElementById('inspector-viewer');
  const contentEl = document.getElementById('inspector-content');

  // Preserve the live Three.js viewer when re-rendering tabs.
  // If the viewer is currently inside the details pane, move it out before
  // replacing innerHTML so the canvas, controls and rotation loop stay alive.
  if (viewerEl && contentEl && detailsEl && detailsEl.contains(viewerEl)) {
    contentEl.insertBefore(viewerEl, detailsEl);
  }

  detailsEl.innerHTML = `
    <div class="detail-name">${escapeHtml(f.name)}</div>
    <div class="detail-id">ID: ${shortId(f.id)} · ${new Date(f.added).toLocaleDateString(dateLocale)}</div>

    <div class="inspector-tabs" role="tablist" aria-label="Bestandsdetails">
      <button class="inspector-tab active" data-tab="preview" type="button">Preview</button>
      <button class="inspector-tab" data-tab="info" type="button">Info</button>
      <button class="inspector-tab" data-tab="ai" type="button">AI</button>
      <button class="inspector-tab" data-tab="file" type="button">Bestand</button>
    </div>

    <div class="inspector-tab-panel active" data-panel="preview"></div>

    <div class="inspector-tab-panel" data-panel="info">
      <div class="detail-row">
        <span class="detail-key">${t('detail.collection')}</span>
        <span class="detail-val">${collection ? escapeHtml(collection.name) : '—'}</span>
      </div>
      ${renderInfoStatsRows(f, stats)}
      ${renderModelCheck(f)}
      <button class="btn" id="model-projects">Projecten / onderdelen</button>

      <div class="tags-editor">
        <div class="description-label">
          <span>Tags</span>
          <span class="char-count">${userTags.length} tag${userTags.length === 1 ? '' : 's'}</span>
        </div>
        <div class="tag-list" id="user-tags-list">
          ${userTags.length ? userTags.map(tag => `<span class="tag-chip">#${escapeHtml(tag)} <button class="tag-remove" data-tag="${escapeHtml(tag)}" type="button" title="Verwijder tag">×</button></span>`).join('') : '<span class="tag-hint">Nog geen tags toegevoegd.</span>'}
        </div>
        <div class="tag-input-row">
          <input class="tag-input" id="tag-input" type="text" placeholder="Nieuwe tag, bv. school, robotica, reserveonderdeel..." maxlength="40" />
          <button class="btn btn-primary" id="tag-add" type="button">Toevoegen</button>
        </div>
        <div class="tag-hint">Tip: druk op Enter om toe te voegen. Zoeken werkt ook met #tags.</div>
      </div>

      <div class="description-block">
        <div class="description-label">
          <span>${t('detail.description')}</span>
          <span class="char-count" id="desc-char-count">${description.length} / 2000</span>
        </div>
        <textarea
          class="description-textarea"
          id="desc-textarea"
          placeholder="${t('detail.description_placeholder')}"
          maxlength="2000"
        >${escapeHtml(description)}</textarea>
        <div class="description-actions">
          <span class="description-saved" id="desc-saved">${t('detail.saved')}</span>
          <button class="btn" id="desc-cancel" style="display:none">${t('detail.cancel')}</button>
          <button class="btn btn-primary" id="desc-save" style="display:none">${t('detail.save')}</button>
        </div>
      </div>
    </div>

    <div class="inspector-tab-panel" data-panel="ai">
      ${renderSmartScanPanel(f)}
    </div>

    <div class="inspector-tab-panel" data-panel="file">
      <div class="detail-row">
        <span class="detail-key">${t('detail.type')}</span>
        <span class="detail-val accent">${fileType}</span>
      </div>
      <div class="detail-row">
        <span class="detail-key">${t('detail.file')}</span>
        <span class="detail-val">${formatBytes(f.size)}</span>
      </div>
      ${renderFileTabExtraRow(f, stats)}
      <div class="detail-row">
        <span class="detail-key">Bestandsnaam</span>
        <span class="detail-val">${escapeHtml(f.name)}</span>
      </div>
      <div class="detail-row">
        <span class="detail-key">Toegevoegd</span>
        <span class="detail-val">${new Date(f.added).toLocaleString(dateLocale)}</span>
      </div>
    </div>
  `;

  document.getElementById('move-select').value = f.collectionId || (f.collectionId === null ? '' : '');

  // Show "Regenerate Preview" button only for 3MF files (where 3D parsing is opt-in)
  const fileTypeLower = (f.type || getFileType(f.name) || '').toLowerCase();
  const regenBtn = document.getElementById('btn-regenerate-preview');
  if (regenBtn) {
    regenBtn.style.display = fileTypeLower === '3mf' ? '' : 'none';
  }

  setupInspectorTabs();
  moveViewerToPreviewTab();
  setupDescriptionEditor(f);
  setupTagEditor(f);
  document.getElementById('model-projects').onclick = () => projectsUI.open();
  const checkButton = document.getElementById('model-check-run');
  if (checkButton) checkButton.onclick = async () => {
    checkButton.disabled = true;
    let parsed;
    try {
      parsed = await parseModelAsync((await serverStorage.fileData(f)).slice(0), f.name);
      if (!parsed) throw new Error('Model niet leesbaar. Afmetingen en modelcontrole zijn niet beschikbaar.');
      f.stats = parsed.stats; f.modelCheck = parsed.modelCheck;
      await saveFile(f);
      if (state.selectedFile === f.id) { renderInspectorDetails(f); setInspectorTab('info'); }
    } finally { parsed?.geometry?.dispose(); checkButton.disabled = false; }
  };
}

function renderModelCheck(f) {
  if ((f.type || getFileType(f.name)) === 'sla') return '<section class="model-check"><h3>Modelcontrole</h3><p>Dit is een gesliced resinbestand zonder mesh. Geometriecontrole is niet beschikbaar.</p></section>';
  const check = f.modelCheck;
  const labels = { boundaryEdges: 'Open randen', nonManifoldEdges: 'Niet-manifold randen', windingEdges: 'Afwijkende vlakrichting',
    degenerateTriangles: 'Vlakken zonder oppervlak', duplicateTriangles: 'Dubbele vlakken', invalidTriangles: 'Ongeldige vlakken' };
  const issues = check ? Object.entries(labels).filter(([key]) => check[key] > 0) : [];
  return `<section class="model-check"><h3>Afmetingen en modelcontrole</h3>
    <p>${f.type === 'stl' ? 'STL heeft geen eenheid; afmetingen worden geïnterpreteerd als millimeters.' : 'Afmetingen gelden voor de geladen 3MF-geometrie, inclusief plaatsing van onderdelen.'}
    ${f.stats?.approximate ? 'Let op: vereenvoudigde 3MF-parser gebruikt. Schaal en plaatsing zijn niet betrouwbaar; controleer de afmetingen in Bambu Studio.' : ''}</p>
    <p>${!check?.checked ? 'Nog niet gecontroleerd. Klik op controleren om de geometrie te laden.' : check.topologySkipped ? 'Randcontrole overgeslagen: model heeft meer dan 200.000 driehoeken. Controleer het model in je slicer.' : issues.length ? 'Mogelijke meshproblemen gevonden:' : 'Geen problemen gevonden bij de basiscontrole van randen en vlakken.'}</p>
    ${issues.map(([key,label]) => slaInfoRow(label, check[key].toLocaleString())).join('')}
    <p>Basiscontrole met een punttolerantie van 0,00001 mm. Zelfdoorsnijdingen, wanddikte en printbaarheid worden niet gecontroleerd. Er wordt niets gerepareerd.</p>
    <button class="btn" id="model-check-run">${check?.checked ? 'Opnieuw controleren' : 'Afmetingen en mesh controleren'}</button></section>`;
}

function moveViewerToPreviewTab() {
  const viewer = document.getElementById('inspector-viewer');
  const previewPanel = document.querySelector('.inspector-tab-panel[data-panel="preview"]');

  if (viewer && previewPanel && !previewPanel.contains(viewer)) {
    previewPanel.appendChild(viewer);
  }

  // Three.js needs a resize pass after moving a canvas in the DOM.
  setTimeout(() => window.dispatchEvent(new Event('resize')), 50);
}

function setInspectorTab(target) {
  const details = document.getElementById('inspector-details');
  if (!details) return;

  const tabs = details.querySelectorAll('.inspector-tab');
  const panels = details.querySelectorAll('.inspector-tab-panel');

  tabs.forEach(tab => tab.classList.toggle('active', tab.dataset.tab === target));
  panels.forEach(panel => panel.classList.toggle('active', panel.dataset.panel === target));

  if (target === 'preview') {
    moveViewerToPreviewTab();
    setTimeout(() => window.dispatchEvent(new Event('resize')), 50);
  }
}

function setupInspectorTabs() {
  const details = document.getElementById('inspector-details');
  if (!details) return;

  const tabs = details.querySelectorAll('.inspector-tab');

  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      setInspectorTab(tab.dataset.tab);
    });
  });
}

function normalizeTag(value) {
  return (value || '')
    .trim()
    .replace(/^#+/, '')
    .replace(/\s+/g, '-')
    .replace(/[^a-zA-Z0-9_\-\.\u00C0-\u024F]/g, '')
    .toLowerCase()
    .slice(0, 40);
}

function setupTagEditor(file) {
  const input = document.getElementById('tag-input');
  const addBtn = document.getElementById('tag-add');
  const list = document.getElementById('user-tags-list');
  if (!input || !addBtn || !list) return;

  const addTag = async () => {
    const tag = normalizeTag(input.value);
    if (!tag) return;

    const tags = Array.isArray(file.tags) ? [...file.tags] : [];
    if (!tags.includes(tag)) {
      tags.push(tag);
      file.tags = tags.sort((a, b) => a.localeCompare(b));
      await saveFile(file);
      renderGrid();
      renderInspectorDetails(file);
    } else {
      input.value = '';
    }
  };

  addBtn.addEventListener('click', addTag);
  input.addEventListener('keydown', e => {
    if (e.key === 'Enter') {
      e.preventDefault();
      addTag();
    }
  });

  list.querySelectorAll('.tag-remove').forEach(btn => {
    btn.addEventListener('click', async () => {
      const tag = btn.dataset.tag;
      file.tags = (Array.isArray(file.tags) ? file.tags : []).filter(t => t !== tag);
      await saveFile(file);
      renderGrid();
      renderInspectorDetails(file);
    });
  });
}

function setupDescriptionEditor(file) {
  const textarea = document.getElementById('desc-textarea');
  const saveBtn = document.getElementById('desc-save');
  const cancelBtn = document.getElementById('desc-cancel');
  const charCount = document.getElementById('desc-char-count');
  const savedIndicator = document.getElementById('desc-saved');

  const original = file.description || '';
  let saveTimeout = null;

  const setDirty = (dirty) => {
    saveBtn.style.display = dirty ? '' : 'none';
    cancelBtn.style.display = dirty ? '' : 'none';
    charCount.classList.toggle('dirty', dirty);
  };

  textarea.addEventListener('input', () => {
    charCount.textContent = `${textarea.value.length} / 2000`;
    const dirty = textarea.value !== original;
    setDirty(dirty);
    savedIndicator.classList.remove('show');
  });

  // Auto-save on blur after a short delay
  textarea.addEventListener('blur', () => {
    if (saveTimeout) clearTimeout(saveTimeout);
    saveTimeout = setTimeout(() => {
      if (textarea.value !== file.description) {
        saveDescription(file, textarea.value);
      }
    }, 200);
  });

  // Ctrl+Enter to save
  textarea.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'Enter') {
      e.preventDefault();
      saveDescription(file, textarea.value);
    } else if (e.key === 'Escape') {
      textarea.value = file.description || '';
      charCount.textContent = `${textarea.value.length} / 2000`;
      setDirty(false);
      textarea.blur();
    }
  });

  saveBtn.addEventListener('click', () => {
    saveDescription(file, textarea.value);
  });

  cancelBtn.addEventListener('click', () => {
    textarea.value = file.description || '';
    charCount.textContent = `${textarea.value.length} / 2000`;
    setDirty(false);
  });
}

async function saveDescription(file, newValue) {
  const trimmed = newValue.trim();
  file.description = trimmed;
  await saveFile(file);

  const saveBtn = document.getElementById('desc-save');
  const cancelBtn = document.getElementById('desc-cancel');
  const charCount = document.getElementById('desc-char-count');
  const savedIndicator = document.getElementById('desc-saved');

  if (saveBtn) saveBtn.style.display = 'none';
  if (cancelBtn) cancelBtn.style.display = 'none';
  if (charCount) charCount.classList.remove('dirty');
  if (savedIndicator) {
    savedIndicator.classList.add('show');
    setTimeout(() => savedIndicator.classList.remove('show'), 1500);
  }

  // Re-render the grid card so the description badge updates
  renderGrid();
}

function renderStatusbar() {
  const totalSize = state.files.reduce((a, f) => a + f.size, 0);
  const stlCount = state.files.filter(f => (f.type || getFileType(f.name)) === 'stl').length;
  const mfCount = state.files.filter(f => (f.type || getFileType(f.name)) === '3mf').length;

  const total = state.files.length;
  let label = t('status.files', total);
  if (stlCount > 0 && mfCount > 0) {
    label += ` · ${stlCount} STL · ${mfCount} 3MF`;
  }
  document.getElementById('status-files').textContent = label;
  document.getElementById('status-size').textContent = formatBytes(totalSize);
}

function render() {
  renderCollections();
  renderBulkToolbar();
  renderGrid();
  renderInspector();
  renderStatusbar();
}

// ============ ACTIONS ============
// ============ BULK SELECTION ============
function toggleFileSelection(id) {
  if (state.bulkBusy || !state.files.some(f => f.id === id)) return;
  if (state.selectedIds.has(id)) state.selectedIds.delete(id);
  else state.selectedIds.add(id);
  renderBulkToolbar();
  renderGrid();
}

function renderBulkToolbar() {
  const visible = getFilesForView();
  const visibleIds = new Set(visible.map(f => f.id));
  for (const id of state.selectedIds) if (!visibleIds.has(id)) state.selectedIds.delete(id);
  const count = state.selectedIds.size;
  document.getElementById('bulk-count').textContent = t('bulk.count', count);
  const all = document.getElementById('bulk-select-all');
  all.checked = visible.length > 0 && count === visible.length;
  all.indeterminate = count > 0 && count < visible.length;
  all.disabled = state.bulkBusy || !visible.length;
  const sel = document.getElementById('bulk-move-select');
  const previous = sel.value;
  sel.innerHTML = `<option value="">${t('inspector.move_placeholder')}</option><option value="__none__">${t('sidebar.uncategorized')}</option>` + state.collections.map(c => `<option value="${escapeHtml(c.id)}">${escapeHtml(c.name)}</option>`).join('');
  if ([...sel.options].some(o => o.value === previous)) sel.value = previous;
  sel.disabled = state.bulkBusy || !count;
  sel.setAttribute('aria-label', t('bulk.target'));
  for (const action of ['move', 'delete', 'clear']) {
    const button = document.getElementById('bulk-' + action);
    button.textContent = t('bulk.' + action);
    button.disabled = state.bulkBusy || !count;
  }
  document.getElementById('bulk-all-label').textContent = t('bulk.all');
  document.getElementById('bulk-hint').textContent = state.bulkBusy ? t('bulk.busy') : t('bulk.hint');
  document.getElementById('search').disabled = state.bulkBusy;
  document.querySelectorAll('.card-check').forEach(box => { box.disabled = state.bulkBusy; });
}

async function runBulkAction(action, collectionId, ids = [...state.selectedIds]) {
  if (state.bulkBusy || !['move', 'delete'].includes(action)) return;
  const target = collectionId === '__none__' || collectionId === 'uncategorized' ? null : (collectionId || null);
  if (action === 'move' && target && !state.collections.some(c => c.id === target)) return;
  const files = [...new Set(ids)].map(id => state.files.find(f => f.id === id)).filter(Boolean);
  if (!files.length) return;
  if (action === 'delete' && !confirm(t('bulk.confirm_delete', files.length))) return;
  state.bulkBusy = true;
  let completed = 0;
  renderBulkToolbar();
  try {
    for (const original of files) {
      // Re-resolve after each write; storage error handling may reload the archive.
      const file = state.files.find(f => f.id === original.id);
      if (!file) continue;
      if (action === 'move') {
        if ((file.collectionId || null) !== target) {
          const updated = { ...file, collectionId: target };
          await saveFile(updated);
          Object.assign(file, updated);
        }
      } else {
        await dbDelete('files', file.id);
        state.files = state.files.filter(f => f.id !== file.id);
        const cached = geometryCache.get(file.id);
        if (cached?.geometry) cached.geometry.dispose();
        geometryCache.delete(file.id);
        if (state.selectedFile === file.id) state.selectedFile = null;
      }
      state.selectedIds.delete(file.id);
      completed++;
      document.getElementById('bulk-count').textContent = `${completed} / ${files.length}`;
    }
    showToast(t('bulk.done', completed));
  } catch (error) {
    // Stop at the first failed request. Only confirmed writes are deselected.
    showToast(t('bulk.partial', completed, files.length) + ' ' + (error.message || ''), 'warn', 12000);
  } finally {
    state.bulkBusy = false;
    render();
  }
}
// ============ END BULK SELECTION ============

async function selectFile(id) {
  state.selectedFile = id;
  render();
}

async function saveFile(f) {
  await dbPut('files', f);
}

let lastImportReport = null;
document.getElementById('btn-duplicate-report').onclick = () => showDuplicateReport();
function showDuplicateReport() {
  const dialog = document.createElement('dialog');
  dialog.className = 'archive-dialog';
  dialog.setAttribute('aria-label', 'Importrapport');
  const report = lastImportReport;
  dialog.innerHTML = `<div class="feature-heading"><h2>Importrapport</h2><button class="btn" data-close>Sluiten</button></div>
    <p>Identieke bestandsinhoud wordt herkend, ook bij een andere bestandsnaam. Een andere export of schaal kan een ander bestand opleveren en wordt niet als identiek beschouwd.</p>
    <p>${report ? `${report.imported} geïmporteerd · ${report.duplicates.length} identieke bestanden overgeslagen.` : 'Nog geen import uitgevoerd in deze sessie.'}</p>
    ${report?.duplicates.map((entry,i) => {
      const file = state.files.find(f => f.id === entry.id);
      const collection = state.collections.find(c => c.id === file?.collectionId);
      return `<div class="duplicate-entry"><strong>${escapeHtml(entry.incoming)}</strong><p>Staat al in het archief als ${escapeHtml(entry.existing)} · ${escapeHtml(collection?.name || 'Zonder collectie')}</p>
        ${file ? `<button class="btn" data-match="${i}">Bestaand model bekijken</button>` : '<p>Model is inmiddels verwijderd.</p>'}</div>`;
    }).join('') || ''}`;
  document.body.append(dialog);
  dialog.querySelector('[data-close]').onclick = () => dialog.close();
  dialog.querySelectorAll('[data-match]').forEach(button => button.onclick = () => {
    dialog.close(); selectFile(report.duplicates[Number(button.dataset.match)].id);
  });
  dialog.addEventListener('close', () => dialog.remove()); dialog.showModal();
}

async function importSTLFiles(fileList, options = {}) {
  const validFiles = Array.from(fileList).filter(f => getFileType(f.name) !== null);
  if (validFiles.length === 0) {
    alert(t('toast.no_files_found'));
    return;
  }

  // Build set of existing hashes for fast duplicate detection
  // Backfill: older files without a hash get one computed now (one-time per file)
  const existingHashes = new Set();
  for (const existing of state.files) {
    if (!existing.hash && existing.data) {
      try {
        existing.hash = await hashBuffer(existing.data.slice(0));
        await saveFile(existing);
      } catch (e) {
        console.warn('Could not hash existing file', existing.name, e);
      }
    }
    if (existing.hash) existingHashes.add(existing.hash);
  }

  const showProgress = validFiles.length > 3;
  if (showProgress) openProgress(t('progress.importing'), validFiles.length);

  let importedCount = 0;
  let skippedCount = 0;
  const skippedNames = [];
  const duplicates = [];

  for (let i = 0; i < validFiles.length; i++) {
    const file = validFiles[i];
    if (showProgress) updateProgress(i, validFiles.length, file.name);

    const buf = await file.arrayBuffer();
    const hash = await hashBuffer(buf.slice(0));

    // Skip if we already have this exact content
    if (existingHashes.has(hash)) {
      const match = exactDuplicate(state.files, hash);
      duplicates.push({ incoming: file.name, id: match?.id, existing: match?.name || file.name });
      skippedCount++;
      if (skippedNames.length < 10) skippedNames.push(file.name);
      if (i % 3 === 0) await new Promise(r => setTimeout(r, 0));
      continue;
    }
    existingHashes.add(hash); // also dedupe within this same import batch

    // Determine collection
    let collectionId = options.collectionId !== undefined
      ? options.collectionId
      : (state.activeCollection && state.activeCollection !== 'uncategorized' ? state.activeCollection : null);

    // Folder mode: derive collection from file path
    if (options.folderMode === 'folders' && file.webkitRelativePath) {
      const parts = file.webkitRelativePath.split('/');
      const collectionName = parts.length > 2 ? parts[parts.length - 2] : parts[0];
      collectionId = await ensureCollection(collectionName);
    }

    const fileType = getFileType(file.name);
    let parsed = null;
    let slaParsed = null;

    // Parse during import based on file type:
    //  - STL: full mesh parse (fast, gives geometry + 3D thumbnail)
    //  - 3MF: deferred (slow, opt-in)
    //  - SLA: header parse only (fast, gives metadata + embedded thumbnail)
    if (fileType === 'stl') {
      parsed = parseModel(buf.slice(0), file.name);
    } else if (fileType === 'sla') {
      slaParsed = parseSLA(buf.slice(0), file.name);
    }

    // Build the stats object the rest of the app expects. For SLA files we
    // synthesise a stats object from the slice metadata so existing UI like
    // size labels and search keep working.
    let stats = null;
    if (parsed) {
      stats = parsed.stats;
    } else if (slaParsed && slaParsed.slaStats) {
      const s = slaParsed.slaStats;
      stats = {
        // Use printer-bed dimensions for width/depth and total print height for height.
        // These are only used for display — SLA files have no real bounding box.
        width: s.bedSizeX || null,
        depth: s.bedSizeY || null,
        height: s.totalHeight || null,
        triangles: null,
        sla: s,
      };
    }

    const fileObj = {
      id: uid(),
      name: file.name,
      size: file.size,
      data: buf,
      hash,
      type: parsed?.type || fileType,
      subFormat: slaParsed?.subFormat || null,
      collectionId,
      stats,
      modelCheck: parsed?.modelCheck || null,
      thumbnail: slaParsed?.thumbnail || null,
      // SLA files are "ready" once we've extracted their metadata; we never need
      // the deferred-preview flow because there's no 3D mesh to parse.
      previewStatus: fileType === '3mf'
        ? 'pending'
        : (parsed || slaParsed ? 'ready' : 'failed'),
      description: '',
      tags: [],
      ai: null,
      added: Date.now() + i,
    };

    if (parsed) {
      fileObj.ai = analyzeModelLocally(fileObj, parsed);
    } else {
      fileObj.ai = analyzeModelLocally(fileObj, null);
    }

    if (parsed) {
      try {
        const thumb = renderThumbnail(parsed.geometry, parsed.type);
        if (thumb) fileObj.thumbnail = thumb;
      } catch (e) {
        console.error('Thumbnail error', e);
      }
      parsed.geometry.dispose();
    }

    await saveFile(fileObj);
    state.files.push(fileObj);
    importedCount++;

    if (!showProgress || i % 5 === 0 || i === validFiles.length - 1) {
      render();
    }

    if (i % 3 === 0) await new Promise(r => setTimeout(r, 0));
  }

  if (showProgress) {
    updateProgress(validFiles.length, validFiles.length, t('progress.done'));
    setTimeout(closeProgress, 400);
  }
  render();

  // Report what happened
  lastImportReport = { imported: importedCount, duplicates };
  if (duplicates.length) showDuplicateReport();
  if (skippedCount > 0) {
    const examples = skippedNames.slice(0, 5).join(', ');
    const more = skippedCount > 5 ? t('toast.skipped_more', skippedCount - 5) : '';
    if (importedCount === 0) {
      showToast(t('toast.all_skipped', skippedCount), 'warn');
    } else {
      showToast(t('toast.imported_with_skipped', importedCount, skippedCount, examples, more), 'info');
    }
  } else if (importedCount > 0 && validFiles.length > 1) {
    showToast(t('toast.imported', importedCount), 'success');
  }
}

async function ensureCollection(name) {
  const trimmed = name.trim();
  if (!trimmed) return null;
  const existing = state.collections.find(c => c.name.toLowerCase() === trimmed.toLowerCase());
  if (existing) return existing.id;
  const c = { id: uid(), name: trimmed };
  await dbPut('collections', c);
  state.collections.push(c);
  return c.id;
}

function openProgress(title, total) {
  document.getElementById('progress-title').textContent = title;
  document.getElementById('progress-count').textContent = `0 / ${total}`;
  document.getElementById('progress-percent').textContent = '0%';
  document.getElementById('progress-bar').style.width = '0%';
  document.getElementById('progress-current').textContent = '';
  document.getElementById('modal-progress').classList.add('show');
}

function updateProgress(current, total, label) {
  const pct = Math.round((current / total) * 100);
  document.getElementById('progress-bar').style.width = pct + '%';
  document.getElementById('progress-count').textContent = `${current} / ${total}`;
  document.getElementById('progress-percent').textContent = pct + '%';
  if (label) document.getElementById('progress-current').textContent = label;
}

function closeProgress() {
  document.getElementById('modal-progress').classList.remove('show');
}

async function createCollection(name) {
  const c = { id: uid(), name: name.trim() };
  await dbPut('collections', c);
  state.collections.push(c);
  state.activeCollection = c.id;
  render();
}

async function moveFileToCollection(fileId, collectionId) {
  const f = state.files.find(x => x.id === fileId);
  const target = collectionId === '__none__' || collectionId === 'uncategorized' ? null : (collectionId || null);
  if (!f || (target && !state.collections.some(c => c.id === target))) return false;
  if ((f.collectionId || null) === target) return false;
  const updated = { ...f, collectionId: target };
  await saveFile(updated);
  Object.assign(f, updated);
  render();
  return true;
}

async function deleteFile(id) {
  if (!confirm(t('alert.confirm_delete_file'))) return;
  await dbDelete('files', id);
  state.files = state.files.filter(f => f.id !== id);
  state.selectedIds.delete(id);
  // Clear cached geometry for this file
  const cached = geometryCache.get(id);
  if (cached && cached.geometry) cached.geometry.dispose();
  geometryCache.delete(id);
  if (state.selectedFile === id) state.selectedFile = null;
  render();
}

function downloadFile(id) {
  const f = state.files.find(x => x.id === id);
  if (!f) return;
  // Stream through the authenticated endpoint rather than loading a second copy into RAM.
  const a = document.createElement('a');
  a.href = serverStorage.downloadUrl(id);
  a.download = f.name;
  a.click();
}

// ============ BACKUP & RESTORE ============
const BACKUP_VERSION = 2;
const BACKUP_MAGIC = 'FORGE_STL_BACKUP';

// Convert ArrayBuffer to base64 in chunks (to avoid stack overflow on big files)
function bufferToBase64(buffer) {
  const bytes = new Uint8Array(buffer);
  const chunkSize = 8192;
  let binary = '';
  for (let i = 0; i < bytes.length; i += chunkSize) {
    const chunk = bytes.subarray(i, i + chunkSize);
    binary += String.fromCharCode.apply(null, chunk);
  }
  return btoa(binary);
}

function base64ToBuffer(base64) {
  const binary = atob(base64);
  const bytes = new Uint8Array(binary.length);
  for (let i = 0; i < binary.length; i++) {
    bytes[i] = binary.charCodeAt(i);
  }
  return bytes.buffer;
}

async function createBackup() {
  const projects = await serverStorage.getAll('projects');
  if (state.files.length === 0 && state.collections.length === 0 && projects.length === 0) {
    alert(t('alert.empty_backup'));
    return;
  }

  openProgress(t('progress.backup'), state.files.length);

  const backup = {
    magic: BACKUP_MAGIC,
    version: BACKUP_VERSION,
    createdAt: new Date().toISOString(),
    collections: state.collections,
    projects,
    files: [],
  };

  for (let i = 0; i < state.files.length; i++) {
    const f = state.files[i];
    updateProgress(i, state.files.length, f.name);

    backup.files.push({
      id: f.id,
      name: f.name,
      size: f.size,
      hash: f.hash,
      type: f.type || getFileType(f.name),
      subFormat: f.subFormat || null,
      collectionId: f.collectionId,
      stats: f.stats,
      modelCheck: f.modelCheck || null,
      thumbnail: f.thumbnail, // base64 PNG dataURL — already a string
      previewStatus: f.previewStatus,
      description: f.description || '',
      tags: Array.isArray(f.tags) ? f.tags : [],
      ai: f.ai || null,
      added: f.added,
      data: bufferToBase64(await serverStorage.fileData(f)),
    });

    // Yield every few files
    if (i % 3 === 0) await new Promise(r => setTimeout(r, 0));
  }

  updateProgress(state.files.length, state.files.length, t('progress.writing'));
  await new Promise(r => setTimeout(r, 0));

  const json = JSON.stringify(backup);
  const blob = new Blob([json], { type: 'application/octet-stream' });
  const url = URL.createObjectURL(blob);

  const date = new Date().toISOString().slice(0, 10);
  const a = document.createElement('a');
  a.href = url;
  a.download = `3dlens-archive-${date}.forge`;
  a.click();
  URL.revokeObjectURL(url);

  closeProgress();
}

function validateBackup(backup) {
  const validId = value => typeof value === 'string' && /^[a-zA-Z0-9_-]{1,80}$/.test(value);
  if (!Array.isArray(backup.collections) || !Array.isArray(backup.files)) throw new Error('Ongeldige backup.');
  const collections = new Set();
  for (const c of backup.collections) {
    if (!validId(c.id) || collections.has(c.id) || typeof c.name !== 'string' || !c.name.trim()) throw new Error('Ongeldige collectie in backup.');
    collections.add(c.id);
  }
  const ids = new Set();
  for (const f of backup.files) {
    if (!validId(f.id) || ids.has(f.id) || typeof f.name !== 'string' || !getFileType(f.name)
      || typeof f.data !== 'string' || !f.data.length || f.data.length % 4 !== 0
      || !/^[A-Za-z0-9+/]*={0,2}$/.test(f.data)
      || (f.collectionId != null && !collections.has(f.collectionId))) throw new Error('Ongeldig bestand in backup.');
    ids.add(f.id);
  }
  if (backup.version >= 2 && !Array.isArray(backup.projects)) throw new Error('Projecten ontbreken in de backup.');
  const projectIds = new Set();
  for (const project of backup.projects || []) {
    if (!validId(project.id) || projectIds.has(project.id) || typeof project.name !== 'string' || !project.name.trim()
      || new TextEncoder().encode(project.name).length > 200 || typeof project.description !== 'string'
      || new TextEncoder().encode(project.description).length > 8000 || !Array.isArray(project.parts) || project.parts.length > 500) throw new Error('Ongeldig project in backup.');
    projectIds.add(project.id);
    const seen = new Set();
    for (const part of project.parts) {
      if (!ids.has(part.fileId) || seen.has(part.fileId) || !Number.isInteger(part.quantity) || part.quantity < 1 || part.quantity > 9999
        || typeof part.notes !== 'string' || new TextEncoder().encode(part.notes).length > 2000) throw new Error('Ongeldig projectonderdeel in backup.');
      seen.add(part.fileId);
    }
  }
}

let pendingRestore = null;

async function startRestore(file) {
  try {
    const text = await file.text();
    const backup = JSON.parse(text);

    if (backup.magic !== BACKUP_MAGIC) {
      // Maybe it's an old metadata-only export
      if (backup.collections && backup.files && !backup.files[0]?.data) {
        alert(t('alert.old_backup'));
        return;
      }
      alert(t('alert.invalid_backup'));
      return;
    }

    if (backup.version > BACKUP_VERSION) {
      if (!confirm(t('alert.future_backup', backup.version))) return;
    }

    validateBackup(backup);
    pendingRestore = backup;

    const dateLocale = currentLang === 'pap' ? 'nl-NL'
      : currentLang === 'en' ? 'en-US'
      : currentLang === 'de' ? 'de-DE'
      : currentLang === 'es' ? 'es-ES'
      : 'nl-NL';

    const fileCount = backup.files?.length || 0;
    const colCount = backup.collections?.length || 0;
    const date = backup.createdAt ? new Date(backup.createdAt).toLocaleString(dateLocale) : '—';
    const totalSize = backup.files?.reduce((a, f) => a + (f.size || 0), 0) || 0;

    document.getElementById('restore-summary').innerHTML = `
      <div><strong style="color: var(--accent); font-weight: 700;">${escapeHtml(t('restore.summary_date', date))}</strong></div>
      <div style="margin-top: 8px;">
        ${t('restore.summary_files', fileCount)}<br>
        ${t('restore.summary_collections', colCount)}<br>
        ${t('restore.summary_total', formatBytes(totalSize))}
      </div>
    `;
    document.getElementById('modal-restore').classList.add('show');
  } catch (e) {
    console.error(e);
    alert(t('alert.read_backup_error', e.message));
  }
}

async function performRestore(mode) {
  if (!pendingRestore) return;
  if (mode !== 'merge') throw new Error('Gebruik samenvoegen om je bestaande serverarchief te behouden.');
  const backup = pendingRestore;
  pendingRestore = null;
  closeRestoreModal();

  openProgress(t('progress.restore'), backup.files.length);

  // Build a map of existing IDs to detect collisions in merge mode
  const existingFileIds = new Set(state.files.map(f => f.id));
  const existingFileHashes = new Set(state.files.map(f => f.hash).filter(Boolean));
  const existingCollectionIds = new Set(state.collections.map(c => c.id));
  const existingCollectionNames = new Map(state.collections.map(c => [c.name.toLowerCase(), c.id]));

  // Restore collections (with ID remapping if needed)
  const collectionIdMap = Object.create(null); // old id -> new id; IDs are untrusted backup data
  for (const c of (backup.collections || [])) {
    if (mode === 'merge' && existingCollectionIds.has(c.id)) {
      // ID collision — give it a new ID
      const newId = uid();
      collectionIdMap[c.id] = newId;
      const newC = { ...c, id: newId };
      // If a collection with the same name already exists, merge into it
      const existingByName = existingCollectionNames.get(c.name.toLowerCase());
      if (existingByName) {
        collectionIdMap[c.id] = existingByName;
      } else {
        await dbPut('collections', newC);
        state.collections.push(newC);
        existingCollectionNames.set(newC.name.toLowerCase(), newC.id);
      }
    } else if (mode === 'merge' && existingCollectionNames.has(c.name.toLowerCase())) {
      // Same name exists — merge
      collectionIdMap[c.id] = existingCollectionNames.get(c.name.toLowerCase());
    } else {
      collectionIdMap[c.id] = c.id;
      await dbPut('collections', c);
      state.collections.push(c);
      existingCollectionNames.set(c.name.toLowerCase(), c.id);
    }
  }

  // Restore files
  let restoredCount = 0;
  let dupSkipped = 0;
  const fileIdMap = new Map();
  for (let i = 0; i < backup.files.length; i++) {
    const f = backup.files[i];
    updateProgress(i, backup.files.length, f.name);

    let id = f.id;
    if (mode === 'merge' && existingFileIds.has(id)) {
      id = uid();
    }

    let collectionId = f.collectionId;
    if (collectionId && collectionIdMap[collectionId] !== undefined) {
      collectionId = collectionIdMap[collectionId];
    }

    const buf = base64ToBuffer(f.data);
    // Compute our own hash; imported metadata is not authoritative.
    const hash = await hashBuffer(buf.slice(0));
    if (existingFileHashes.has(hash)) {
      fileIdMap.set(f.id, exactDuplicate(state.files, hash).id);
      dupSkipped++; continue;
    }
    existingFileHashes.add(hash);

    const fileObj = {
      id,
      name: f.name,
      size: f.size,
      data: buf,
      hash,
      type: f.type || getFileType(f.name),
      subFormat: f.subFormat || (getFileType(f.name) === 'sla' ? getSLASubFormat(f.name) : null),
      collectionId,
      stats: f.stats,
      modelCheck: f.modelCheck || null,
      thumbnail: f.thumbnail,
      previewStatus: f.previewStatus || (f.thumbnail ? 'ready' : (getFileType(f.name) === '3mf' ? 'pending' : 'ready')),
      description: f.description || '',
      tags: Array.isArray(f.tags) ? f.tags : [],
      ai: f.ai || null,
      added: f.added || Date.now() + i,
    };

    await saveFile(fileObj);
    state.files.push(fileObj);
    fileIdMap.set(f.id, fileObj.id);
    restoredCount++;

    if (i % 3 === 0) await new Promise(r => setTimeout(r, 0));
  }

  // Fresh IDs preserve existing projects when a backup is merged, including same-ID projects.
  for (const project of backup.projects || []) {
    await serverStorage.put('projects', remapProject(project, fileIdMap, uid()));
  }
  closeProgress();
  render();

  let msg = t('toast.restore_done', restoredCount, backup.collections.length);
  if (dupSkipped > 0) {
    msg += t('toast.restore_dups', dupSkipped);
  }
  showToast(msg, 'success', 6000);
}

async function dbClear(store) {
  try { return await serverStorage.clear(store); }
  catch (error) { await reloadArchive(); throw error; }
}

// ============ EVENTS ============
document.getElementById('btn-import').addEventListener('click', () => {
  document.getElementById('file-input').click();
});

document.getElementById('file-input').addEventListener('change', e => {
  importSTLFiles(e.target.files);
  e.target.value = '';
});

// Folder import
let pendingFolderFiles = null;

document.getElementById('btn-import-folder').addEventListener('click', () => {
  document.getElementById('folder-input').click();
});

document.getElementById('folder-input').addEventListener('change', e => {
  const allFiles = Array.from(e.target.files);
  const validFiles = allFiles.filter(f => getFileType(f.name) !== null);

  if (validFiles.length === 0) {
    alert(t('toast.no_files_in_folder'));
    e.target.value = '';
    return;
  }

  pendingFolderFiles = validFiles;
  showFolderSummary(validFiles);
  document.getElementById('modal-folder').classList.add('show');

  e.target.value = '';
});

function showFolderSummary(validFiles) {
  // Analyze structure
  const rootFolder = validFiles[0].webkitRelativePath.split('/')[0];
  const subfolderSet = new Set();
  let rootLevelCount = 0;
  const stlCount = validFiles.filter(f => getFileType(f.name) === 'stl').length;
  const mfCount = validFiles.filter(f => getFileType(f.name) === '3mf').length;
  validFiles.forEach(f => {
    const parts = f.webkitRelativePath.split('/');
    if (parts.length === 2) rootLevelCount++;
    else if (parts.length > 2) subfolderSet.add(parts[parts.length - 2]);
  });

  const typeBreakdown = [];
  if (stlCount > 0) typeBreakdown.push(`${stlCount} STL`);
  if (mfCount > 0) typeBreakdown.push(`${mfCount} 3MF`);

  const summary = `
    <div><strong style="color: var(--accent); font-weight: 700;">${escapeHtml(rootFolder)}</strong></div>
    <div style="margin-top: 8px;">
      ${t('folder.summary_files', validFiles.length, typeBreakdown.join(', '))}
      ${subfolderSet.size > 0 ? `<br>${t('folder.summary_subfolders', subfolderSet.size)}${[...subfolderSet].slice(0, 5).map(escapeHtml).join(', ')}${subfolderSet.size > 5 ? '...' : ''}` : ''}
      ${rootLevelCount > 0 ? `<br>${t('folder.summary_root', rootLevelCount)}` : ''}
    </div>
  `;
  document.getElementById('folder-summary').innerHTML = summary;
}

document.getElementById('btn-confirm-folder').addEventListener('click', async () => {
  if (!pendingFolderFiles) return;
  const mode = document.querySelector('input[name="folder-mode"]:checked').value;
  const files = pendingFolderFiles;
  pendingFolderFiles = null;
  closeFolderModal();

  let options = {};
  if (mode === 'folders') {
    options.folderMode = 'folders';
  } else if (mode === 'single') {
    const rootFolder = files[0].webkitRelativePath.split('/')[0];
    options.collectionId = await ensureCollection(rootFolder);
  } else {
    options.collectionId = null;
  }

  await importSTLFiles(files, options);
});

window.closeFolderModal = () => {
  document.getElementById('modal-folder').classList.remove('show');
  pendingFolderFiles = null;
};

document.getElementById('modal-folder').addEventListener('click', e => {
  if (e.target.id === 'modal-folder') closeFolderModal();
});

document.getElementById('btn-new-collection').addEventListener('click', () => {
  document.getElementById('modal-collection').classList.add('show');
  document.getElementById('collection-name').focus();
});

document.getElementById('btn-create-collection').addEventListener('click', async () => {
  const name = document.getElementById('collection-name').value.trim();
  if (!name) return;
  await createCollection(name);
  closeModal();
});

document.getElementById('collection-name').addEventListener('keydown', e => {
  if (e.key === 'Enter') document.getElementById('btn-create-collection').click();
  if (e.key === 'Escape') closeModal();
});

window.closeModal = () => {
  document.getElementById('modal-collection').classList.remove('show');
  document.getElementById('collection-name').value = '';
};

document.getElementById('modal-collection').addEventListener('click', e => {
  if (e.target.id === 'modal-collection') closeModal();
});

document.getElementById('bulk-select-all').addEventListener('change', e => {
  if (state.bulkBusy) return;
  state.selectedIds.clear();
  if (e.target.checked) getFilesForView().forEach(file => state.selectedIds.add(file.id));
  renderBulkToolbar();
  renderGrid();
});
document.getElementById('bulk-clear').addEventListener('click', () => {
  if (state.bulkBusy) return;
  state.selectedIds.clear();
  renderBulkToolbar();
  renderGrid();
});
document.getElementById('bulk-move').addEventListener('click', () => {
  const select = document.getElementById('bulk-move-select');
  if (!select.value) { select.focus(); return; }
  runBulkAction('move', select.value);
});
document.getElementById('bulk-delete').addEventListener('click', () => runBulkAction('delete'));

document.getElementById('search').addEventListener('input', e => {
  state.selectedIds.clear();
  state.search = e.target.value;
  renderBulkToolbar();
  renderGrid();
});

document.getElementById('move-select').addEventListener('change', e => {
  if (state.selectedFile) {
    moveFileToCollection(state.selectedFile, e.target.value);
  }
});

document.getElementById('btn-download').addEventListener('click', () => {
  if (state.selectedFile) downloadFile(state.selectedFile);
});

document.getElementById('btn-delete-file').addEventListener('click', () => {
  if (state.selectedFile) deleteFile(state.selectedFile);
});

document.getElementById('btn-regenerate-preview').addEventListener('click', () => {
  if (!state.selectedFile) return;
  const f = state.files.find(x => x.id === state.selectedFile);
  if (!f) return;
  // Reset status and clear cache so generatePreview will re-parse from scratch
  f.previewStatus = 'pending';
  f.thumbnail = null;
  const cached = geometryCache.get(f.id);
  if (cached && cached.geometry) cached.geometry.dispose();
  geometryCache.delete(f.id);
  generatePreview(f);
});

document.getElementById('btn-backup').addEventListener('click', createBackup);

document.getElementById('btn-restore').addEventListener('click', () => {
  document.getElementById('restore-input').click();
});

document.getElementById('restore-input').addEventListener('change', e => {
  const file = e.target.files[0];
  if (file) startRestore(file);
  e.target.value = '';
});

document.getElementById('btn-confirm-restore').addEventListener('click', () => {
  const mode = document.querySelector('input[name="restore-mode"]:checked').value;
  performRestore(mode);
});

window.closeRestoreModal = () => {
  document.getElementById('modal-restore').classList.remove('show');
  pendingRestore = null;
};

document.getElementById('modal-restore').addEventListener('click', e => {
  if (e.target.id === 'modal-restore') closeRestoreModal();
});

document.getElementById('btn-about').addEventListener('click', () => {
  document.getElementById('modal-about').classList.add('show');
});

window.closeAboutModal = () => {
  document.getElementById('modal-about').classList.remove('show');
};

document.getElementById('modal-about').addEventListener('click', e => {
  if (e.target.id === 'modal-about') closeAboutModal();
});

// Clear library flow
document.getElementById('btn-clear-library').addEventListener('click', () => {
  const fileCount = state.files.length;
  const colCount = state.collections.length;
  const totalSize = state.files.reduce((a, f) => a + f.size, 0);

  if (fileCount === 0 && colCount === 0) {
    showToast(t('toast.empty_lib'), 'info');
    return;
  }

  document.getElementById('clear-summary').innerHTML = `
    <div><strong style="color: var(--warn); font-weight: 700;">${t('clear.summary')}</strong></div>
    <div style="margin-top: 8px;">
      ${t('status.files', fileCount)}<br>
      ${t('restore.summary_collections', colCount)}<br>
      ${t('restore.summary_total', formatBytes(totalSize))}
    </div>
  `;
  document.getElementById('clear-confirm').value = '';
  document.getElementById('btn-confirm-clear').disabled = true;
  closeAboutModal();
  document.getElementById('modal-clear').classList.add('show');
  setTimeout(() => document.getElementById('clear-confirm').focus(), 50);
});

document.getElementById('clear-confirm').addEventListener('input', e => {
  document.getElementById('btn-confirm-clear').disabled = e.target.value.trim().toUpperCase() !== t('clear.confirm_word');
});

document.getElementById('clear-confirm').addEventListener('keydown', e => {
  if (e.key === 'Enter' && !document.getElementById('btn-confirm-clear').disabled) {
    document.getElementById('btn-confirm-clear').click();
  } else if (e.key === 'Escape') {
    closeClearModal();
  }
});

document.getElementById('btn-confirm-clear').addEventListener('click', async () => {
  const fileCount = state.files.length;
  const colCount = state.collections.length;

  // Clear the authenticated server archive after explicit confirmation
  await dbClear('files');
  await dbClear('collections');

  // Clear in-memory state
  state.files = [];
  state.collections = [];
  state.selectedFile = null;
  state.activeCollection = null;
  state.search = '';

  // Clear geometry cache
  for (const [, entry] of geometryCache) {
    if (entry && entry.geometry) entry.geometry.dispose();
  }
  geometryCache.clear();

  // Reset UI
  document.getElementById('search').value = '';

  closeClearModal();
  render();
  showToast(t('toast.lib_cleared', fileCount, colCount), 'success', 5000);
});

window.closeClearModal = () => {
  document.getElementById('modal-clear').classList.remove('show');
};

document.getElementById('modal-clear').addEventListener('click', e => {
  if (e.target.id === 'modal-clear') closeClearModal();
});

document.getElementById('status-help').addEventListener('click', () => {
  document.getElementById('modal-help').classList.add('show');
});

window.closeHelpModal = () => {
  document.getElementById('modal-help').classList.remove('show');
};

document.getElementById('modal-help').addEventListener('click', e => {
  if (e.target.id === 'modal-help') closeHelpModal();
});

document.getElementById('view-reset').addEventListener('click', () => {
  if (state.selectedFile) {
    const f = state.files.find(x => x.id === state.selectedFile);
    if (f) {
      const cached = getCachedGeometry(f.id);
      if (cached) {
        loadIntoInspector(cached.geometry);
      } else {
        loadFileIntoInspector(f);
      }
    }
  }
});

document.getElementById('view-wireframe').addEventListener('click', e => {
  inspector.wireframe = !inspector.wireframe;
  e.currentTarget.classList.toggle('active', inspector.wireframe);
  if (state.selectedFile) {
    const f = state.files.find(x => x.id === state.selectedFile);
    if (f) {
      const cached = getCachedGeometry(f.id);
      if (cached) {
        loadIntoInspector(cached.geometry);
      } else {
        loadFileIntoInspector(f);
      }
    }
  }
});

document.getElementById('view-spin').addEventListener('click', e => {
  inspector.spin = !inspector.spin;
  e.currentTarget.classList.toggle('active', inspector.spin);
});

document.getElementById('view-fullscreen').addEventListener('click', () => openFullscreenPreview());
document.getElementById('preview-modal-close').addEventListener('click', closeFullscreenPreview);
document.getElementById('preview-modal-reset').addEventListener('click', resetFullscreenPreview);
document.getElementById('preview-modal-wire').addEventListener('click', e => toggleFullscreenWireframe(e.currentTarget));
document.getElementById('preview-modal-spin').addEventListener('click', e => toggleFullscreenSpin(e.currentTarget));
document.getElementById('preview-modal').addEventListener('click', e => {
  if (e.target.id === 'preview-modal') closeFullscreenPreview();
});
document.addEventListener('keydown', e => {
  if (e.key === 'Escape' && document.getElementById('preview-modal').classList.contains('show')) {
    closeFullscreenPreview();
  }
});


// Drag & drop
const ARCHIVE_DRAG_TYPE = 'application/x-3dlens-model';
let draggedArchiveFile = null;
let draggedArchiveFiles = [];
let dragCounter = 0;
const dropOverlay = document.getElementById('drop-overlay');

function clearArchiveDrag() {
  draggedArchiveFile = null;
  draggedArchiveFiles = [];
  dragCounter = 0;
  dropOverlay.classList.remove('show');
  document.querySelectorAll('.is-dragging, .drop-target').forEach(el => {
    el.classList.remove('is-dragging', 'drop-target');
  });
}

function dropCollectionId(target) {
  const id = target?.closest?.('.collection-item')?.dataset.id;
  if (id === 'uncategorized') return null;
  return state.collections.some(c => c.id === id) ? id : undefined;
}

window.addEventListener('dragenter', e => {
  e.preventDefault();
  if (e.dataTransfer.types.includes('Files')) {
    dragCounter++;
    dropOverlay.classList.add('show');
  }
});

window.addEventListener('dragleave', e => {
  if (draggedArchiveFile) return;
  dragCounter--;
  if (dragCounter <= 0) {
    dragCounter = 0;
    dropOverlay.classList.remove('show');
  }
});

window.addEventListener('dragover', e => e.preventDefault());

window.addEventListener('drop', async e => {
  e.preventDefault();
  const collectionId = dropCollectionId(e.target);
  const fileId = draggedArchiveFile;
  const fileIds = draggedArchiveFiles.length ? [...draggedArchiveFiles] : (fileId ? [fileId] : []);
  const transferredId = e.dataTransfer?.getData(ARCHIVE_DRAG_TYPE);
  clearArchiveDrag();
  if (state.bulkBusy) return;
  if (fileId) {
    if (transferredId === fileId && collectionId !== undefined) {
      if (fileIds.length > 1) await runBulkAction('move', collectionId, fileIds);
      else await moveFileToCollection(fileId, collectionId);
    }
    return;
  }
  // Internal drags from a different tab must never be treated as uploads.
  if (Array.from(e.dataTransfer?.types || []).includes(ARCHIVE_DRAG_TYPE)) return;

  // Check for folders via DataTransferItems
  const items = e.dataTransfer.items;
  if (items && items.length > 0 && items[0].webkitGetAsEntry) {
    const entries = [];
    for (let i = 0; i < items.length; i++) {
      const entry = items[i].webkitGetAsEntry();
      if (entry) entries.push(entry);
    }

    const hasFolder = entries.some(en => en.isDirectory);

    if (hasFolder) {
      // Walk folders, build file list with synthetic webkitRelativePath
      const collected = [];
      for (const entry of entries) {
        await walkEntry(entry, '', collected);
      }
      const validFiles = collected.filter(f => getFileType(f.name) !== null);

      if (validFiles.length === 0) {
        alert(t('toast.no_files_in_drop'));
        return;
      }

      if (collectionId !== undefined) {
        await importSTLFiles(validFiles, { collectionId });
        return;
      }

      pendingFolderFiles = validFiles;
      showFolderSummary(validFiles);
      document.getElementById('modal-folder').classList.add('show');
      return;
    }
  }

  // Plain file drop
  if (e.dataTransfer.files.length) await importSTLFiles(e.dataTransfer.files,
    collectionId !== undefined ? { collectionId } : {});
});

// Recursively walk a FileSystemEntry tree
async function walkEntry(entry, path, collected) {
  if (entry.isFile) {
    const file = await new Promise((res, rej) => entry.file(res, rej));
    // Attach a synthetic webkitRelativePath so importSTLFiles can use it
    const relativePath = path ? `${path}/${file.name}` : file.name;
    Object.defineProperty(file, 'webkitRelativePath', { value: relativePath, configurable: true });
    collected.push(file);
  } else if (entry.isDirectory) {
    const reader = entry.createReader();
    const readBatch = () => new Promise((res, rej) => reader.readEntries(res, rej));
    let entries;
    do {
      entries = await readBatch();
      for (const child of entries) {
        await walkEntry(child, path ? `${path}/${entry.name}` : entry.name, collected);
      }
    } while (entries.length > 0);
  }
}

// Clock
function updateClock() {
  const now = new Date();
  const t = now.toLocaleTimeString('nl-NL', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
  document.getElementById('status-time').textContent = t;
}
setInterval(updateClock, 1000);
updateClock();

// ============ INIT ============
function setupSettingsMenu() {
  const btn = document.getElementById('btn-settings');
  const menu = document.getElementById('settings-menu');
  const langsContainer = document.getElementById('settings-langs');

  function buildLangs() {
    langsContainer.innerHTML = Object.entries(TRANSLATIONS).map(([code, dict]) => `
      <button class="settings-lang ${code === currentLang ? 'active' : ''}" data-lang="${code}">
        <span>${dict._name}</span>
        <span class="settings-lang-flag">${dict._flag}</span>
      </button>
    `).join('');
    langsContainer.querySelectorAll('.settings-lang').forEach(el => {
      el.addEventListener('click', () => {
        setLanguage(el.dataset.lang);
        buildLangs();
      });
    });
  }

  function openMenu() {
    btn.classList.add('open');
    menu.classList.add('open');
  }

  function closeMenu() {
    btn.classList.remove('open');
    menu.classList.remove('open');
  }

  btn.addEventListener('click', e => {
    e.stopPropagation();
    if (menu.classList.contains('open')) closeMenu();
    else openMenu();
  });

  // Close when clicking outside
  document.addEventListener('click', e => {
    if (!menu.contains(e.target) && !btn.contains(e.target)) closeMenu();
  });

  // Close when an action is clicked (Backup/Restore) — they trigger flows
  // that we want the menu out of the way for
  menu.querySelectorAll('.settings-item').forEach(el => {
    el.addEventListener('click', () => closeMenu());
  });

  buildLangs();
}

async function init() {
  // Detect & apply language before anything renders
  currentLang = detectLanguage();
  applyStaticTranslations();
  setupSettingsMenu();
  // Hosted restores are additive; never delete the live archive before uploading a backup.
  const replaceOption = document.querySelector('input[name="restore-mode"][value="replace"]');
  if (replaceOption) { replaceOption.disabled = true; replaceOption.closest('label').hidden = true; }

  // Update loading text in user's language
  const loadingTextEl = document.getElementById('app-loading-text');
  if (loadingTextEl) loadingTextEl.textContent = t('app.loading');

  await openDB();
  state.collections = await dbGetAll('collections') || [];
  state.files = await dbGetAll('files') || [];
  state.loading = false;
  render();

  // Hide the loading overlay with a brief fade-out
  const overlay = document.getElementById('app-loading');
  if (overlay) {
    overlay.classList.add('hide');
    setTimeout(() => overlay.remove(), 350);
  }
}

init().catch(err => {
  console.error(err);
  const overlay = document.getElementById('app-loading');
  if (overlay) overlay.remove();
  showToast(t('alert.init_error', err.message), 'warn', 15000);
});
</script>
</body>
</html>
