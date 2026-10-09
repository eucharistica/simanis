<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SIMANIS</title>

  <link rel="icon" href="/favicon.ico">

  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">

  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap4.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

  <style>
    body { overflow-x: hidden; background:#f8f9fa; }
    #wrapper { display:flex; min-height:100vh; width:100%; }

    #sidebar{
      width: 240px;
      position: sticky;
      top: 0;
      height: 100vh;
      min-height: 100vh;
      background:#1f2d3d;
      color:#cfd8dc;
      transition: width .2s;
      flex: 0 0 auto;
      display:flex;
      flex-direction:column;
    }
    #sidebar.collapsed { width: 64px; }

    #content { flex:1 1 auto; min-width:0; }

    .brand {
      padding: 12px 12px;
      font-weight:600;
      border-bottom:1px solid rgba(255,255,255,.08);
    }

    .sidebar-link{
      color:#cfd8dc;
      display:flex;
      align-items:center;
      gap:10px;
      padding:10px 14px;
      text-decoration:none;
      border-left:3px solid transparent;
    }
    .sidebar-link:hover{
      background:rgba(255,255,255,.08);
      color:#fff;
      text-decoration:none;
    }
    .sidebar-link.active{
      background:rgba(255,255,255,.10);
      color:#fff;
      border-left-color:#0d6efd;
    }
    .sidebar-link i{
      width:20px;
      text-align:center;
      flex:0 0 20px;
    }

    #sidebar.collapsed .menu-text,
    #sidebar.collapsed .brand-text,
    #sidebar.collapsed .sidebar-search-wrap,
    #sidebar.collapsed .sidebar-userbox,
    #sidebar.collapsed .menu-section {
      display:none !important;
    }

    #sidebar.collapsed .sidebar-link{
      justify-content:center;
      padding:12px 0;
    }

    #sidebar-backdrop{
      display:none;
      position:fixed;
      inset:0;
      background: rgba(0,0,0,.35);
      z-index:1030;
    }
    #sidebar-backdrop.show{ display:block; }

    @media (max-width: 767.98px){
      #sidebar{
        position: fixed;
        left:0; top:0;
        height:100vh;
        z-index:1040;
        transform: translateX(-100%);
        transition: transform .2s;
        width: 260px;
      }
      #sidebar.mobile-open{ transform: translateX(0); }
      #content{ width:100%; }
    }

    #sidebar .text-uppercase.small{
      white-space: normal;
      line-height: 1.2;
    }

    .chart-container {
      position: relative;
      height: 400px;
      min-height: 350px;
      overflow-x: auto;
      overflow-y: hidden;
    }
    .chart-container canvas {
      max-height: 100%;
      width: 100% !important;
    }

    .dokter-scroll {
      scrollbar-width: thin;
      scrollbar-color: #0d6efd #f8f9fa;
    }
    .dokter-scroll::-webkit-scrollbar {
      height: 8px;
    }
    .dokter-scroll::-webkit-scrollbar-track {
      background: #f8f9fa;
    }
    .dokter-scroll::-webkit-scrollbar-thumb {
      background: #0d6efd;
      border-radius: 4px;
    }

    .card-header h6 { font-size: 1rem !important; }

    .card-soft {
      border: 0;
      border-radius: 0.75rem;
      box-shadow: 0 8px 18px rgba(15, 23, 42, 0.07);
    }
    .card-soft-header {
      border-bottom: 0;
      background: linear-gradient(135deg, #f9fafb, #eef2ff);
    }
    .card-soft-header-primary {
      background: linear-gradient(135deg, #e0f2fe, #eff6ff);
      color: #0f172a;
    }
    .card-soft-header-info {
      background: linear-gradient(135deg, #ecfeff, #e0f2fe);
      color: #0f172a;
    }
    .card-soft-header-danger {
      background: linear-gradient(135deg, #fee2e2, #fef2f2);
      color: #0f172a;
    }

    .metric-chip {
      display: inline-flex;
      align-items: center;
      padding: 0.15rem 0.5rem;
      border-radius: 999px;
      font-size: 0.7rem;
      font-weight: 600;
      background-color: rgba(15, 23, 42, 0.04);
      color: #6b7280;
    }
    .metric-chip i {
      font-size: 0.65rem;
      margin-right: 0.25rem;
    }
    .metric-label {
      font-size: 0.7rem;
      letter-spacing: .08em;
      text-transform: uppercase;
      color: #6b7280;
      font-weight: 600;
    }
    .metric-value {
      font-size: 1.7rem;
      font-weight: 700;
      color: #0f172a;
    }
    .metric-sub {
      font-size: 0.8rem;
      color: #9ca3af;
    }
    .card-section-title {
      font-size: 0.9rem;
      font-weight: 600;
      color: #111827;
    }

    .sidebar-search-wrap {
      border-bottom: 1px solid rgba(255,255,255,.08);
    }
    .sidebar-search-wrap .form-control {
      background: rgba(255,255,255,.08);
      border: 1px solid rgba(255,255,255,.12);
      color: #fff;
    }
    .sidebar-search-wrap .form-control::placeholder {
      color: rgba(255,255,255,.65);
    }
    .sidebar-search-wrap .form-control:focus {
      background: rgba(255,255,255,.12);
      border-color: rgba(255,255,255,.25);
      box-shadow: none;
      color: #fff;
    }

    .sidebar-userbox{
      border-top:1px solid rgba(255,255,255,.08);
      color:#cfd8dc;
    }

    .sidebar-footer-link{
      color:#cfd8dc;
      display:flex;
      align-items:center;
      gap:10px;
      text-decoration:none;
    }
    .sidebar-footer-link:hover{
      color:#fff;
      text-decoration:none;
    }
  </style>
</head>
<body>
<div id="wrapper">
  <div id="sidebar-backdrop"></div>
