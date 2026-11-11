@extends('layouts.app')
@section('is-auth-page', true)
@section('hide-appbar-footer', true)
@section('content')

@php
  $slides = [];
  if (isset($showcaseProducts) && $showcaseProducts->isNotEmpty()) {
      foreach ($showcaseProducts as $idx => $prod) {
          $slides[] = [
              'title' => $prod->name,
              'desc' => Str::limit(strip_tags($prod->description ?: ($store->slogan ?: $store->description)), 105),
              'badge' => '⭐ Produk Pilihan Byvii',
              'image' => $prod->imageUrl(),
              'category' => $prod->category ? $prod->category->name : 'Fashion',
              'price' => 'Rp ' . number_format($prod->selling_price, 0, ',', '.'),
              'stock' => $prod->stock > 0 ? 'Stok ' . $prod->stock . ' pcs' : 'Stok Habis',
              'rating' => '4.9',
              'sold' => '120+ Terjual',
          ];
      }
  } else {
      $slides = [
          [
              'title' => $store->name,
              'desc' => $store->slogan ?: ($store->description ?: 'Katalog produk resmi dan belanja online terpercaya.'),
              'badge' => '🏪 Toko Resmi Byvii',
              'image' => $store->logoUrl() ?: asset('favicon.ico'),
              'category' => 'Official Store',
              'price' => 'Katalog Aktif',
              'stock' => 'Buka',
              'rating' => '5.0',
              'sold' => 'Verified',
          ],
          [
              'title' => 'Pembayaran QRIS & Transfer Cepat',
              'desc' => 'Mendukung pembayaran langsung via scan QRIS e-wallet/m-banking serta transfer rekening toko resmi.',
              'badge' => '💳 Pembayaran Instan',
              'image' => $store->qrImageUrl() ?: ($store->logoUrl() ?: asset('favicon.ico')),
              'category' => 'QRIS & Transfer',
              'price' => 'Praktis & Aman',
              'stock' => '24 Jam',
              'rating' => '4.9',
              'sold' => 'Real-Time',
          ],
          [
              'title' => 'Transaksi Terhubung WhatsApp',
              'desc' => 'Konfirmasi pesanan real-time langsung ke WhatsApp penjual untuk proses pengiriman lebih cepat.',
              'badge' => '💬 Layanan WhatsApp',
              'image' => $store->logoUrl() ?: asset('favicon.ico'),
              'category' => 'Layanan Pelanggan',
              'price' => 'Respon Cepat',
              'stock' => 'Online',
              'rating' => '5.0',
              'sold' => 'Terpercaya',
          ],
      ];
  }
  $totalSlides = count($slides);
@endphp

<style>
  /* =========================================================
     REMOTION-INSPIRED MOTION DESIGN & AESTHETIC LOGIN PAGE
     ========================================================= */

  .store-auth-page-bg {
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 36px 18px;
    background:
      radial-gradient(circle at 10% 15%, rgba(244, 63, 94, 0.08) 0%, transparent 45%),
      radial-gradient(circle at 90% 85%, rgba(225, 29, 72, 0.08) 0%, transparent 45%),
      radial-gradient(circle at 50% 50%, rgba(251, 113, 133, 0.04) 0%, transparent 60%),
      #f8fafc;
    background-image: radial-gradient(#e2e8f0 1.2px, transparent 1.2px);
    background-size: 26px 26px;
    box-sizing: border-box;
    position: relative;
    overflow-x: hidden;
  }

  /* Decorative Ambient Floating Mesh Orbs */
  .store-auth-page-bg::before {
    content: '';
    position: fixed;
    top: -120px;
    right: -120px;
    width: 520px;
    height: 520px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(244, 63, 94, 0.14) 0%, rgba(244, 63, 94, 0) 70%);
    filter: blur(55px);
    pointer-events: none;
    z-index: 0;
    animation: remotionFloat 16s ease-in-out infinite alternate;
  }

  .store-auth-page-bg::after {
    content: '';
    position: fixed;
    bottom: -120px;
    left: -120px;
    width: 460px;
    height: 460px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(168, 85, 247, 0.12) 0%, rgba(168, 85, 247, 0) 70%);
    filter: blur(55px);
    pointer-events: none;
    z-index: 0;
    animation: remotionFloat 20s ease-in-out infinite alternate-reverse;
  }

  @keyframes remotionFloat {
    0% { transform: translate(0, 0) scale(1); }
    50% { transform: translate(32px, -28px) scale(1.08); }
    100% { transform: translate(-24px, 36px) scale(0.95); }
  }

  /* Main Container Card */
  .store-auth-card {
    width: 100%;
    max-width: 980px;
    background: #ffffff;
    border-radius: 30px;
    box-shadow:
      0 32px 70px -15px rgba(15, 23, 42, 0.15),
      0 0 0 1px rgba(226, 232, 240, 0.9),
      0 12px 32px -8px rgba(244, 63, 94, 0.08);
    display: grid;
    grid-template-columns: 1.05fr 1.15fr;
    overflow: hidden;
    min-height: 580px;
    box-sizing: border-box;
    position: relative;
    z-index: 1;
    backdrop-filter: blur(10px);
    transition: transform 0.3s ease, box-shadow 0.3s ease;
  }

  /* -------------------------------------------------------------
     LEFT COLUMN: FORM & BRAND IDENTITY
     ------------------------------------------------------------- */
  .store-auth-left {
    padding: 42px 46px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-sizing: border-box;
    background: #ffffff;
    position: relative;
    z-index: 2;
  }

  .store-auth-brand-row {
    display: flex;
    align-items: center;
    gap: 13px;
    margin-bottom: 22px;
  }

  .store-auth-logo-img {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    object-fit: cover;
    border: 1.5px solid #fecdd3;
    box-shadow: 0 4px 14px rgba(244, 63, 94, 0.18);
    flex-shrink: 0;
    transition: transform 0.25s ease;
  }
  .store-auth-logo-img:hover {
    transform: scale(1.06) rotate(-2deg);
  }

  .store-auth-logo-fallback {
    width: 46px;
    height: 46px;
    border-radius: 14px;
    background: linear-gradient(135deg, #f43f5e 0%, #e11d48 100%);
    color: #ffffff;
    font-size: 21px;
    font-weight: 800;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 14px rgba(244, 63, 94, 0.22);
    flex-shrink: 0;
  }

  .store-auth-brand-info {
    display: flex;
    flex-direction: column;
  }

  .store-auth-brand-name-wrap {
    display: flex;
    align-items: center;
    gap: 7px;
    flex-wrap: wrap;
  }

  .store-auth-brand-name {
    font-size: 16.5px;
    font-weight: 800;
    color: #0f172a;
    line-height: 1.2;
    letter-spacing: -0.015em;
  }

  .store-auth-brand-badge {
    font-size: 10px;
    font-weight: 800;
    text-transform: uppercase;
    background: linear-gradient(135deg, #ffe4e6 0%, #fecdd3 100%);
    color: #e11d48;
    border: 1px solid #fda4af;
    padding: 2px 7px;
    border-radius: 6px;
    letter-spacing: 0.05em;
    display: inline-flex;
    align-items: center;
    gap: 3px;
  }

  .store-auth-brand-sub {
    font-size: 12px;
    color: #64748b;
    margin-top: 2px;
    font-weight: 500;
  }

  .store-auth-title {
    font-size: 24px;
    font-weight: 800;
    color: #0f172a;
    letter-spacing: -0.025em;
    margin: 0 0 5px;
    line-height: 1.25;
  }

  .store-auth-title .brand-accent {
    background: linear-gradient(135deg, #f43f5e 0%, #be123c 100%);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
  }

  .store-auth-subtitle {
    font-size: 13px;
    color: #64748b;
    margin: 0 0 22px;
    line-height: 1.45;
  }

  .store-auth-form {
    display: flex;
    flex-direction: column;
    gap: 16px;
  }

  .store-auth-field {
    display: flex;
    flex-direction: column;
    gap: 6px;
  }

  .store-auth-label {
    font-size: 12px;
    font-weight: 600;
    color: #334155;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }

  .store-auth-input-wrap {
    position: relative;
    width: 100%;
  }

  .store-auth-input-icon {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: #94a3b8;
    pointer-events: none;
    transition: color 0.2s ease;
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .store-auth-input {
    width: 100%;
    height: 46px;
    border-radius: 13px;
    border: 1.5px solid #cbd5e1;
    background: #f8fafc;
    padding: 0 14px 0 42px;
    font-size: 13.5px;
    color: #0f172a;
    outline: none;
    transition: all 0.2s ease;
    box-sizing: border-box;
    font-family: inherit;
  }

  .store-auth-input::placeholder {
    color: #94a3b8;
  }

  .store-auth-input:focus {
    background: #ffffff;
    border-color: #f43f5e;
    box-shadow: 0 0 0 4px rgba(244, 63, 94, 0.12), 0 2px 8px rgba(244, 63, 94, 0.06);
  }

  .store-auth-input:focus + .store-auth-input-icon,
  .store-auth-input-wrap:focus-within .store-auth-input-icon {
    color: #e11d48;
  }

  .store-auth-input-pass {
    padding-right: 44px;
  }

  .btn-toggle-password {
    position: absolute;
    right: 8px;
    top: 50%;
    transform: translateY(-50%);
    background: transparent;
    border: none;
    cursor: pointer;
    padding: 7px;
    color: #94a3b8;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    transition: all 0.15s ease;
  }

  .btn-toggle-password:hover {
    color: #0f172a;
    background: #f1f5f9;
  }

  .store-auth-check-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: 12.5px;
    color: #475569;
    margin-top: 2px;
  }

  .store-auth-forgot-link {
    color: #e11d48;
    text-decoration: none;
    font-weight: 600;
    font-size: 12.5px;
    transition: all 0.15s ease;
    display: inline-flex;
    align-items: center;
    gap: 2px;
  }

  .store-auth-forgot-link:hover {
    color: #be123c;
    text-decoration: underline;
  }

  /* Submit Button with Shimmer Sweep Motion */
  .store-auth-submit-btn {
    width: 100%;
    height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    border-radius: 13px;
    border: none;
    background: linear-gradient(135deg, #ff3366 0%, #f43f5e 50%, #e11d48 100%);
    color: #ffffff;
    padding: 0 24px;
    font-size: 14px;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 8px 24px -4px rgba(244, 63, 94, 0.42);
    text-decoration: none;
    font-family: inherit;
    margin-top: 6px;
    position: relative;
    overflow: hidden;
  }

  /* Shimmer sweep animation */
  .store-auth-submit-btn::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 60%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
    transform: skewX(-20deg);
    transition: none;
  }

  .store-auth-submit-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 30px -4px rgba(244, 63, 94, 0.52);
    background: linear-gradient(135deg, #fb7185 0%, #e11d48 100%);
  }

  .store-auth-submit-btn:hover::before {
    left: 140%;
    transition: left 0.8s ease;
  }

  .store-auth-submit-btn:active {
    transform: translateY(0);
    box-shadow: 0 4px 12px rgba(244, 63, 94, 0.3);
  }

  .store-auth-submit-btn svg {
    transition: transform 0.2s ease;
  }

  .store-auth-submit-btn:hover svg {
    transform: translateX(4px);
  }

  /* Divider & Social Login */
  .store-auth-divider {
    display: flex;
    align-items: center;
    margin: 18px 0 14px 0;
    text-align: center;
  }
  .store-auth-divider::before,
  .store-auth-divider::after {
    content: '';
    flex: 1;
    border-bottom: 1px solid #e2e8f0;
  }
  .store-auth-divider span {
    padding: 0 12px;
    font-size: 11.5px;
    font-weight: 600;
    letter-spacing: 0.04em;
    color: #94a3b8;
    text-transform: uppercase;
  }

  .store-auth-google-btn {
    width: 100%;
    height: 48px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    border-radius: 13px;
    border: 1.5px solid #e2e8f0;
    background: #ffffff;
    color: #1e293b;
    padding: 0 20px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
    box-sizing: border-box;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 2px 6px -1px rgba(15, 23, 42, 0.05);
    font-family: inherit;
    position: relative;
  }
  .store-auth-google-btn:hover {
    background: #f8fafc;
    border-color: #cbd5e1;
    color: #0f172a;
    transform: translateY(-2px);
    box-shadow: 0 8px 20px -3px rgba(15, 23, 42, 0.08);
  }
  .store-auth-google-btn:active {
    transform: translateY(0);
    box-shadow: 0 2px 4px rgba(15, 23, 42, 0.06);
  }
  .store-auth-google-btn svg {
    flex-shrink: 0;
  }

  .store-auth-nav-links {
    text-align: center;
    margin-top: 15px;
    display: flex;
    flex-direction: column;
    gap: 7px;
  }

  .store-auth-switch-link {
    font-size: 12.5px;
    color: #64748b;
    margin: 0;
  }

  .store-auth-switch-link a {
    color: #e11d48;
    font-weight: 700;
    text-decoration: none;
    transition: color 0.15s;
  }

  .store-auth-switch-link a:hover {
    text-decoration: underline;
    color: #be123c;
  }

  .store-auth-return-link {
    font-size: 12px;
    color: #94a3b8;
    text-decoration: none;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
  }

  .store-auth-return-link:hover {
    color: #0f172a;
    transform: translateX(-3px);
  }

  .store-auth-trust-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding-top: 18px;
    margin-top: 20px;
    border-top: 1px solid #f1f5f9;
    font-size: 11.5px;
    color: #64748b;
    flex-wrap: wrap;
    gap: 8px;
  }

  .trust-pulse-dot {
    width: 7px;
    height: 7px;
    border-radius: 50%;
    background: #10b981;
    display: inline-block;
    box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7);
    animation: trustPulse 2s infinite;
  }

  @keyframes trustPulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
  }

  /* -------------------------------------------------------------
     RIGHT COLUMN: REMOTION-INSPIRED MOTION PRODUCT REEL
     ------------------------------------------------------------- */
  .store-auth-right {
    background: linear-gradient(145deg, #180918 0%, #2e091b 25%, #4c0519 55%, #881337 80%, #be123c 100%) !important;
    padding: 34px 32px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    position: relative;
    overflow: hidden;
    color: #ffffff;
    box-sizing: border-box;
  }

  /* Override legacy pseudo circles */
  .store-auth-right::before,
  .store-auth-right::after {
    display: none !important;
  }

  /* Ambient Aurora Glow in Right Showcase */
  .remotion-aurora-orb-1 {
    position: absolute;
    top: -70px;
    right: -70px;
    width: 340px;
    height: 340px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(255, 75, 120, 0.42) 0%, transparent 70%);
    filter: blur(45px);
    pointer-events: none;
    animation: auroraPulse 8s ease-in-out infinite alternate;
  }

  .remotion-aurora-orb-2 {
    position: absolute;
    bottom: -90px;
    left: -50px;
    width: 360px;
    height: 360px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(168, 85, 247, 0.38) 0%, transparent 70%);
    filter: blur(50px);
    pointer-events: none;
    animation: auroraPulse 10s ease-in-out infinite alternate-reverse;
  }

  @keyframes auroraPulse {
    0% { transform: scale(0.9) translate(0, 0); opacity: 0.8; }
    50% { transform: scale(1.15) translate(18px, -18px); opacity: 1; }
    100% { transform: scale(0.95) translate(-12px, 12px); opacity: 0.85; }
  }

  /* Remotion Story Timeline Bars at the Top */
  .remotion-timeline-container {
    position: relative;
    z-index: 5;
    margin-bottom: 10px;
  }

  .remotion-timeline-track-row {
    display: flex;
    gap: 7px;
    width: 100%;
    margin-bottom: 14px;
  }

  .remotion-timeline-bar-wrapper {
    flex: 1;
    height: 3.5px;
    background: rgba(255, 255, 255, 0.22);
    border-radius: 9999px;
    overflow: hidden;
    cursor: pointer;
    position: relative;
    transition: height 0.15s ease, background 0.15s ease;
  }

  .remotion-timeline-bar-wrapper:hover {
    height: 5px;
    background: rgba(255, 255, 255, 0.35);
  }

  .remotion-timeline-progress-fill {
    height: 100%;
    width: 0%;
    background: linear-gradient(90deg, #ffffff 0%, #ffe4e6 100%);
    border-radius: 9999px;
    box-shadow: 0 0 10px rgba(255, 255, 255, 0.9);
    transition: width 0.05s linear;
  }

  .showcase-top-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: relative;
    z-index: 5;
  }

  .showcase-pill-badge {
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    color: #ffffff;
    padding: 5px 14px;
    border-radius: 9999px;
    font-size: 11.5px;
    font-weight: 700;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    border: 1px solid rgba(255, 255, 255, 0.25);
    box-shadow: 0 4px 14px rgba(0, 0, 0, 0.12);
    transition: transform 0.2s ease;
  }

  .showcase-pill-badge:hover {
    transform: scale(1.03);
    background: rgba(255, 255, 255, 0.22);
  }

  .showcase-controls-top {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .btn-remotion-playpause {
    background: rgba(255, 255, 255, 0.16);
    border: 1px solid rgba(255, 255, 255, 0.28);
    color: #ffffff;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    font-size: 10px;
    transition: all 0.2s ease;
    backdrop-filter: blur(8px);
  }

  .btn-remotion-playpause:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: scale(1.1);
  }

  .showcase-counter {
    font-size: 11.5px;
    font-weight: 700;
    color: rgba(255, 255, 255, 0.9);
    letter-spacing: 0.06em;
    font-variant-numeric: tabular-nums;
  }

  /* -------------------------------------------------------------
     3D MOTION STAGE & INTERACTIVE PARALLAX CARD
     ------------------------------------------------------------- */
  .remotion-stage-viewport {
    perspective: 1200px;
    margin: 10px 0;
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    z-index: 4;
  }

  .remotion-3d-card-wrapper {
    position: relative;
    width: 100%;
    max-width: 285px;
    transform-style: preserve-3d;
    transition: transform 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  }

  /* Floating micro-badges around the product card */
  .remotion-floating-pill-top {
    position: absolute;
    top: 10px;
    right: -15px;
    background: rgba(255, 255, 255, 0.95);
    color: #0f172a;
    padding: 5px 13px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 800;
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.25);
    display: inline-flex;
    align-items: center;
    gap: 5px;
    z-index: 10;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.8);
    animation: floatingBob 4s ease-in-out infinite alternate;
  }

  .remotion-floating-pill-bottom {
    position: absolute;
    bottom: 10px;
    left: -15px;
    background: linear-gradient(135deg, rgba(15, 23, 42, 0.92) 0%, rgba(30, 41, 59, 0.92) 100%);
    color: #ffffff;
    padding: 5px 13px;
    border-radius: 9999px;
    font-size: 11px;
    font-weight: 700;
    box-shadow: 0 10px 24px rgba(0, 0, 0, 0.3);
    display: inline-flex;
    align-items: center;
    gap: 5px;
    z-index: 10;
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.25);
    animation: floatingBob 4.8s ease-in-out infinite alternate-reverse;
  }

  @keyframes floatingBob {
    0% { transform: translateY(0); }
    100% { transform: translateY(-7px); }
  }

  /* The Main Product Card */
  .showcase-card {
    background: #ffffff;
    border-radius: 24px;
    padding: 14px;
    width: 100%;
    box-shadow:
      0 24px 50px -10px rgba(0, 0, 0, 0.4),
      0 0 0 1px rgba(255, 255, 255, 0.25);
    box-sizing: border-box;
    position: relative;
    overflow: hidden;
    transition: all 0.4s cubic-bezier(0.16, 1, 0.3, 1);
  }

  /* Subtle card sheen sweep */
  .showcase-card::after {
    content: '';
    position: absolute;
    top: 0;
    left: -120%;
    width: 80%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.35), transparent);
    transform: skewX(-25deg);
    pointer-events: none;
    transition: left 0.9s ease;
  }

  .remotion-3d-card-wrapper:hover .showcase-card::after {
    left: 150%;
  }

  .showcase-thumb-frame {
    width: 100%;
    height: 190px;
    border-radius: 18px;
    overflow: hidden;
    background: #f1f5f9;
    position: relative;
  }

  .showcase-thumb-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    transition: transform 0.6s cubic-bezier(0.16, 1, 0.3, 1), opacity 0.35s ease;
  }

  /* Continuous subtle Ken-Burns zoom on active image */
  .showcase-thumb-frame.is-animating .showcase-thumb-img {
    animation: kenBurnsMotion 5s ease-in-out infinite alternate;
  }

  @keyframes kenBurnsMotion {
    0% { transform: scale(1.0); }
    100% { transform: scale(1.08); }
  }

  /* Refined Meta Details Below Image */
  .showcase-card-meta {
    margin-top: 12px;
    display: flex;
    flex-direction: column;
    gap: 7px;
  }

  .showcase-card-top-tags {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
  }

  .showcase-card-tag {
    font-size: 11px;
    font-weight: 700;
    padding: 3px 9px;
    border-radius: 8px;
    background: #f1f5f9;
    color: #475569;
  }

  .showcase-card-tag.stock {
    background: #ecfdf5;
    color: #059669;
    font-weight: 700;
  }

  .showcase-card-price-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
    padding-top: 2px;
  }

  .showcase-card-tag.price {
    background: #ffe4e6;
    color: #e11d48;
    font-size: 13px;
    font-weight: 800;
    padding: 4px 10px;
    border-radius: 8px;
    letter-spacing: -0.01em;
  }

  .showcase-verified-pill {
    font-size: 10.5px;
    font-weight: 700;
    color: #64748b;
    display: inline-flex;
    align-items: center;
    gap: 3px;
  }

  /* Navigation Arrows */
  .showcase-nav-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.95);
    color: #0f172a;
    border: none;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.25);
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
    z-index: 15;
  }

  .showcase-nav-arrow.prev {
    left: 4px;
  }

  .showcase-nav-arrow.next {
    right: 4px;
  }

  .showcase-nav-arrow:hover {
    transform: translateY(-50%) scale(1.12);
    background: #ffffff;
    box-shadow: 0 8px 22px rgba(0, 0, 0, 0.35);
  }

  .showcase-nav-arrow:active {
    transform: translateY(-50%) scale(0.95);
  }

  /* -------------------------------------------------------------
     MOTION TEXT INFO (BOTTOM OF REEL)
     ------------------------------------------------------------- */
  .showcase-content-info {
    position: relative;
    z-index: 5;
    text-align: center;
    padding: 0 12px;
  }

  .showcase-headline {
    font-size: 17px;
    font-weight: 800;
    color: #ffffff;
    margin: 0 0 6px;
    line-height: 1.35;
    letter-spacing: -0.015em;
    min-height: 46px;
    display: flex;
    align-items: center;
    justify-content: center;
    text-shadow: 0 2px 10px rgba(0, 0, 0, 0.3);
    transition: opacity 0.3s ease, transform 0.3s ease;
  }

  .showcase-description {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.85);
    line-height: 1.5;
    margin: 0 0 14px;
    height: 36px;
    overflow: hidden;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    text-shadow: 0 1px 4px rgba(0, 0, 0, 0.2);
    transition: opacity 0.3s ease, transform 0.3s ease;
  }

  .showcase-dots-row {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
  }

  .showcase-dot {
    width: 8px;
    height: 8px;
    border-radius: 9999px;
    background: rgba(255, 255, 255, 0.3);
    border: none;
    padding: 0;
    cursor: pointer;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
  }

  .showcase-dot.active {
    width: 26px;
    background: #ffffff;
    box-shadow: 0 0 12px rgba(255, 255, 255, 0.9);
  }

  /* Outer Footer */
  .store-auth-outer-footer {
    margin-top: 22px;
    font-size: 12px;
    color: #94a3b8;
    text-align: center;
    position: relative;
    z-index: 2;
    display: flex;
    align-items: center;
    gap: 6px;
  }

  /* -------------------------------------------------------------
     RESPONSIVE BREAKPOINTS
     ------------------------------------------------------------- */
  @media (max-width: 860px) {
    .store-auth-card {
      grid-template-columns: 1fr;
      max-width: 480px;
      border-radius: 24px;
    }

    .store-auth-left {
      padding: 34px 26px;
    }

    .store-auth-right {
      padding: 26px 20px;
    }

    .showcase-top-row {
      gap: 10px;
      flex-wrap: wrap;
    }

    .remotion-3d-card-wrapper {
      max-width: 250px;
    }

    .showcase-thumb-frame {
      height: 165px;
    }
  }

  @media (max-width: 480px) {
    .store-auth-page-bg {
      padding: 16px 12px;
    }

    .store-auth-title {
      font-size: 21px;
    }

    .remotion-3d-card-wrapper {
      max-width: 230px;
    }
  }
</style>

<div class="store-auth-page-bg">
  <div class="store-auth-card">
    {{-- Left: Form Column --}}
    <div class="store-auth-left">
      <div>
        {{-- Store Brand Header --}}
        <div class="store-auth-brand-row">
          @if($store->logoUrl())
            <img src="{{ $store->logoUrl() }}" alt="{{ $store->name }}" class="store-auth-logo-img">
          @else
            <div class="store-auth-logo-fallback">
              {{ mb_substr($store->name, 0, 1) }}
            </div>
          @endif
          <div class="store-auth-brand-info">
            <div class="store-auth-brand-name-wrap">
              <span class="store-auth-brand-name">{{ $store->name }}</span>
              <span class="store-auth-brand-badge">
                <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                RESMI
              </span>
            </div>
            <span class="store-auth-brand-sub">{{ $store->slogan ?: 'Katalog & Belanja Toko' }}</span>
          </div>
        </div>

        {{-- Titles --}}
        <h1 class="store-auth-title">Masuk ke <span class="brand-accent">{{ $store->name }}</span></h1>
        <p class="store-auth-subtitle">Silakan masukkan email & password akun pelanggan Anda:</p>

        @if (session('status') || session('info'))
          <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 12px; padding: 11px 15px; margin-bottom: 16px; font-size: 13px; color: #1e40af; display: flex; align-items: center; gap: 9px; line-height: 1.45;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
            <span>{{ session('status') ?? session('info') }}</span>
          </div>
        @endif

        @if ($errors->any())
          <div style="background: #fef2f2; border: 1px solid #fecaca; border-radius: 12px; padding: 10px 14px; margin-bottom: 16px; font-size: 13px; color: #dc2626; display: flex; align-items: center; gap: 8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span>{{ $errors->first() }}</span>
          </div>
        @endif

        {{-- Form --}}
        <form method="POST" action="{{ route('store.login.submit', $store->slug) }}" class="store-auth-form">
          @csrf

          <div class="store-auth-field">
            <label class="store-auth-label" for="loginEmail">Alamat Email / ID Pelanggan</label>
            <div class="store-auth-input-wrap">
              <span class="store-auth-input-icon">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="20" height="16" x="2" y="4" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
              </span>
              <input class="store-auth-input" type="email" id="loginEmail" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" placeholder="nama@email.com">
            </div>
          </div>

          <div class="store-auth-field">
            <label class="store-auth-label" for="loginPassword">Kata Sandi</label>
            <div class="store-auth-input-wrap">
              <span class="store-auth-input-icon">
                <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="11" x="3" y="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
              </span>
              <input class="store-auth-input store-auth-input-pass" type="password" id="loginPassword" name="password" required autocomplete="current-password" placeholder="••••••••">
              <button type="button" class="btn-toggle-password" onclick="togglePasswordVisibility('loginPassword', this)" title="Lihat kata sandi" aria-label="Toggle kata sandi">
                <svg class="eye-open" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="eye-closed" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display:none;"><path d="M9.88 9.88a3 3 0 1 0 4.24 4.24"/><path d="M10.73 5.08A10.43 10.43 0 0 1 12 5c7 0 10 7 10 7a13.16 13.16 0 0 1-1.67 2.68"/><path d="M6.61 6.61A13.526 13.526 0 0 0 2 12s3 7 10 7a9.74 9.74 0 0 0 5.39-1.61"/><line x1="2" x2="22" y1="2" y2="22"/></svg>
              </button>
            </div>
          </div>

          <div class="store-auth-check-row">
            <label style="display: inline-flex; align-items: center; gap: 7px; cursor: pointer; font-size: 13px;">
              <input type="checkbox" name="remember" {{ old('remember') ? 'checked' : '' }} style="accent-color: #e11d48; width: 16px; height: 16px; border-radius: 4px; cursor: pointer;">
              <span>Ingat sesi</span>
            </label>
            @if($store->cleanPhoneNumber())
              <a href="{{ $store->whatsappUrl('Halo ' . $store->name . ', saya membutuhkan bantuan untuk reset/lupa kata sandi akun pelanggan saya.') }}" target="_blank" rel="noopener" class="store-auth-forgot-link">
                Lupa password?
              </a>
            @else
              <span class="store-auth-forgot-link" style="color: #94a3b8; cursor: default;">Lupa password?</span>
            @endif
          </div>

          <button type="submit" class="store-auth-submit-btn">
            <span>Masuk ke Akun Toko</span>
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </button>
        </form>

        {{-- Divider & Google OAuth --}}
        <div class="store-auth-divider">
          <span>atau masuk lebih cepat</span>
        </div>

        <a href="{{ route('store.login.google', ['storeSlug' => $store->slug, 'return_url' => request('return_url')]) }}" class="store-auth-google-btn">
          <svg class="google-icon" width="19" height="19" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M23.745 12.27c0-.7-.06-1.4-.19-2.07H12v4.51h6.6c-.29 1.52-1.14 2.82-2.4 3.68v3.05h3.88c2.27-2.09 3.665-5.17 3.665-9.17Z"/>
            <path fill="#34A853" d="M12 24c3.24 0 5.95-1.08 7.93-2.91l-3.88-3.05c-1.08.72-2.45 1.16-4.05 1.16-3.12 0-5.77-2.1-6.72-4.93H1.25v3.15C3.26 21.36 7.33 24 12 24Z"/>
            <path fill="#FBBC05" d="M5.28 14.27c-.25-.72-.38-1.49-.38-2.27s.13-1.55.38-2.27V6.58H1.25C.45 8.18 0 9.97 0 12s.45 3.82 1.25 5.42l4.03-3.15Z"/>
            <path fill="#EA4335" d="M12 4.75c1.77 0 3.35.61 4.6 1.8l3.42-3.42C17.95 1.19 15.24 0 12 0 7.33 0 3.26 2.64 1.25 6.58l4.03 3.15c.95-2.83 3.6-4.98 6.72-4.98Z"/>
          </svg>
          <span>Masuk dengan Google</span>
        </a>

        <div class="store-auth-nav-links">
          <p class="store-auth-switch-link">
            Belum punya akun di toko ini?
            <a href="{{ route('store.register', ['storeSlug' => $store->slug, 'return_url' => request('return_url')]) }}">Daftar sekarang</a>
          </p>
          <a href="{{ $store->url() }}" class="store-auth-return-link">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            Kembali ke Katalog {{ $store->name }}
          </a>
        </div>
      </div>

      {{-- Trust & Verified Security Footer --}}
      <div class="store-auth-trust-footer">
        <span style="display: inline-flex; align-items: center; gap: 6px;">
          <span class="trust-pulse-dot"></span>
          <span>256-Bit SSL Enkripsi</span>
        </span>
        <span style="display: inline-flex; align-items: center; gap: 4px;">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><path d="M20 6 9 17l-5-5"/></svg>
          <span>{{ $store->name }} Verified</span>
        </span>
      </div>
    </div>

    {{-- Right: Remotion-Inspired Motion Product Reel Column --}}
    <div class="store-auth-right" id="remotionRightPanel">
      <div class="remotion-aurora-orb-1"></div>
      <div class="remotion-aurora-orb-2"></div>

      {{-- Top: Remotion Story Timeline & Control Row --}}
      <div class="remotion-timeline-container">
        {{-- Segmented Story Progress Bars --}}
        <div class="remotion-timeline-track-row" id="remotionTimelineTrack">
          @for($i = 0; $i < $totalSlides; $i++)
            <div class="remotion-timeline-bar-wrapper" onclick="goToAuthSlide({{ $i }})" title="Lihat reel {{ $i + 1 }}">
              <div class="remotion-timeline-progress-fill" id="remotionProgressFill-{{ $i }}"></div>
            </div>
          @endfor
        </div>

        <div class="showcase-top-row">
          <div class="showcase-pill-badge" id="showcaseBadgePill">
            <span>✨</span>
            <span id="showcaseBadgeText">{{ $slides[0]['badge'] }}</span>
          </div>

          <div class="showcase-controls-top">
            <button type="button" class="btn-remotion-playpause" id="btnPlayPause" onclick="togglePlayPause()" title="Jeda / Putar Reel" aria-label="Jeda atau Putar Reel">
              <span id="playPauseIcon">⏸</span>
            </button>
            <div class="showcase-counter" id="showcaseCounterText">
              01 / {{ sprintf('%02d', $totalSlides) }}
            </div>
          </div>
        </div>
      </div>

      {{-- Center: Interactive 3D Parallax Card Stage --}}
      <div class="remotion-stage-viewport" id="remotionStage">
        <button type="button" class="showcase-nav-arrow prev" onclick="moveAuthSlide(-1)" aria-label="Slide sebelumnya">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
        </button>
        <button type="button" class="showcase-nav-arrow next" onclick="moveAuthSlide(1)" aria-label="Slide selanjutnya">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </button>

        <div class="remotion-3d-card-wrapper" id="remotion3dCard">
          {{-- Floating Top-Right Pill --}}
          <div class="remotion-floating-pill-top">
            <span>🔥</span>
            <span id="showcaseSoldText">{{ $slides[0]['sold'] }}</span>
          </div>

          {{-- Floating Bottom-Left Pill --}}
          <div class="remotion-floating-pill-bottom">
            <span>⭐</span>
            <span id="showcaseRatingText">{{ $slides[0]['rating'] }} Rating Toko</span>
          </div>

          <div class="showcase-card">
            <div class="showcase-thumb-frame is-animating" id="thumbFrame">
              <img id="showcaseCardImg" src="{{ $slides[0]['image'] }}" alt="{{ $slides[0]['title'] }}" class="showcase-thumb-img">
            </div>
            <div class="showcase-card-meta">
              <div class="showcase-card-top-tags">
                <span class="showcase-card-tag" id="showcaseTag1">{{ $slides[0]['category'] }}</span>
                <span class="showcase-card-tag stock" id="showcaseTagStock">{{ $slides[0]['stock'] }}</span>
              </div>
              <div class="showcase-card-price-row">
                <span class="showcase-card-tag price" id="showcaseTagPrice">{{ $slides[0]['price'] }}</span>
                <span class="showcase-verified-pill">
                  <svg width="11" height="11" viewBox="0 0 24 24" fill="#e11d48"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                  Kualitas Terjamin
                </span>
              </div>
            </div>
          </div>
        </div>
      </div>

      {{-- Bottom: Motion Typography & Reel Dots --}}
      <div class="showcase-content-info">
        <h3 class="showcase-headline" id="showcaseHeadline">{{ $slides[0]['title'] }}</h3>
        <p class="showcase-description" id="showcaseDesc">{{ $slides[0]['desc'] }}</p>

        {{-- Dots --}}
        <div class="showcase-dots-row" id="showcaseDotsRow">
          @for($i = 0; $i < $totalSlides; $i++)
            <button type="button" class="showcase-dot {{ $i === 0 ? 'active' : '' }}" onclick="goToAuthSlide({{ $i }})" aria-label="Ke slide {{ $i + 1 }}"></button>
          @endfor
        </div>
      </div>
    </div>
  </div>

  {{-- Outer Copyright Footer --}}
  <div class="store-auth-outer-footer">
    <span>© {{ date('Y') }} {{ $store->name }}</span>
    <span>•</span>
    <span>Didukung oleh Katalog</span>
  </div>
</div>

<script>
  /* ====================================================================
     REMOTION MOTION CONTROLLER: TIMELINE, 3D PARALLAX & SPRING ANIMATION
     ==================================================================== */
  const authSlides = @js($slides);
  let currentAuthIndex = 0;
  let isPlaying = true;
  const slideDurationMs = 5000;
  let slideStartTime = Date.now();
  let elapsedBeforePause = 0;
  let animationFrameId = null;

  function updateAuthSlideUI(animate = true) {
    if (!authSlides || authSlides.length === 0) return;
    const item = authSlides[currentAuthIndex];

    const img = document.getElementById('showcaseCardImg');
    const badgeText = document.getElementById('showcaseBadgeText');
    const counterText = document.getElementById('showcaseCounterText');
    const tag1 = document.getElementById('showcaseTag1');
    const tagPrice = document.getElementById('showcaseTagPrice');
    const tagStock = document.getElementById('showcaseTagStock');
    const headline = document.getElementById('showcaseHeadline');
    const desc = document.getElementById('showcaseDesc');
    const soldText = document.getElementById('showcaseSoldText');
    const ratingText = document.getElementById('showcaseRatingText');
    const cardWrapper = document.getElementById('remotion3dCard');

    if (animate && cardWrapper) {
      // Spring pop animation on card switch
      cardWrapper.style.transform = 'scale(0.94) translateY(8px)';
      cardWrapper.style.opacity = '0.7';
      if (headline) {
        headline.style.opacity = '0';
        headline.style.transform = 'translateY(6px)';
      }
      if (desc) {
        desc.style.opacity = '0';
        desc.style.transform = 'translateY(6px)';
      }

      setTimeout(() => {
        if (img) img.src = item.image;
        if (badgeText) badgeText.textContent = item.badge;
        if (counterText) {
          const cur = (currentAuthIndex + 1).toString().padStart(2, '0');
          const tot = authSlides.length.toString().padStart(2, '0');
          counterText.textContent = cur + ' / ' + tot;
        }
        if (tag1) tag1.textContent = item.category;
        if (tagPrice) tagPrice.textContent = item.price;
        if (tagStock) tagStock.textContent = item.stock;
        if (soldText) soldText.textContent = item.sold;
        if (ratingText) ratingText.textContent = item.rating + ' Rating Toko';
        if (headline) headline.textContent = item.title;
        if (desc) desc.textContent = item.desc;

        cardWrapper.style.transform = 'scale(1) translateY(0)';
        cardWrapper.style.opacity = '1';
        if (headline) {
          headline.style.opacity = '1';
          headline.style.transform = 'translateY(0)';
        }
        if (desc) {
          desc.style.opacity = '1';
          desc.style.transform = 'translateY(0)';
        }
      }, 140);
    } else {
      if (img) img.src = item.image;
      if (badgeText) badgeText.textContent = item.badge;
      if (counterText) {
        const cur = (currentAuthIndex + 1).toString().padStart(2, '0');
        const tot = authSlides.length.toString().padStart(2, '0');
        counterText.textContent = cur + ' / ' + tot;
      }
      if (tag1) tag1.textContent = item.category;
      if (tagPrice) tagPrice.textContent = item.price;
      if (tagStock) tagStock.textContent = item.stock;
      if (soldText) soldText.textContent = item.sold;
      if (ratingText) ratingText.textContent = item.rating + ' Rating Toko';
      if (headline) headline.textContent = item.title;
      if (desc) desc.textContent = item.desc;
    }

    // Update bottom dots
    const dots = document.querySelectorAll('.showcase-dot');
    dots.forEach((dot, idx) => {
      if (idx === currentAuthIndex) {
        dot.classList.add('active');
      } else {
        dot.classList.remove('active');
      }
    });

    // Reset timelines
    updateTimelineBars(0);
  }

  /* Remotion Timeline Progress Engine */
  function updateTimelineBars(progressRatio) {
    for (let i = 0; i < authSlides.length; i++) {
      const fill = document.getElementById('remotionProgressFill-' + i);
      if (!fill) continue;
      if (i < currentAuthIndex) {
        fill.style.width = '100%';
      } else if (i === currentAuthIndex) {
        fill.style.width = Math.min(100, Math.max(0, progressRatio * 100)) + '%';
      } else {
        fill.style.width = '0%';
      }
    }
  }

  function timelineTick() {
    if (!isPlaying) return;

    const now = Date.now();
    const elapsed = (now - slideStartTime) + elapsedBeforePause;
    const progressRatio = elapsed / slideDurationMs;

    updateTimelineBars(progressRatio);

    if (progressRatio >= 1.0) {
      moveAuthSlide(1);
    } else {
      animationFrameId = requestAnimationFrame(timelineTick);
    }
  }

  function startTimeline() {
    slideStartTime = Date.now();
    elapsedBeforePause = 0;
    if (animationFrameId) cancelAnimationFrame(animationFrameId);
    if (isPlaying) {
      animationFrameId = requestAnimationFrame(timelineTick);
    }
  }

  function moveAuthSlide(step) {
    if (!authSlides || authSlides.length === 0) return;
    currentAuthIndex = (currentAuthIndex + step + authSlides.length) % authSlides.length;
    updateAuthSlideUI(true);
    startTimeline();
  }

  function goToAuthSlide(idx) {
    if (!authSlides || idx < 0 || idx >= authSlides.length) return;
    currentAuthIndex = idx;
    updateAuthSlideUI(true);
    startTimeline();
  }

  function togglePlayPause() {
    const icon = document.getElementById('playPauseIcon');
    if (isPlaying) {
      // Pause
      isPlaying = false;
      if (animationFrameId) cancelAnimationFrame(animationFrameId);
      elapsedBeforePause += (Date.now() - slideStartTime);
      if (icon) icon.textContent = '▶';
    } else {
      // Play
      isPlaying = true;
      slideStartTime = Date.now();
      if (icon) icon.textContent = '⏸';
      animationFrameId = requestAnimationFrame(timelineTick);
    }
  }

  /* Interactive 3D Card Parallax on Mouse Move */
  function init3DParallax() {
    const rightPanel = document.getElementById('remotionRightPanel');
    const card = document.getElementById('remotion3dCard');
    if (!rightPanel || !card) return;

    rightPanel.addEventListener('mousemove', (e) => {
      const rect = rightPanel.getBoundingClientRect();
      const x = e.clientX - rect.left - (rect.width / 2);
      const y = e.clientY - rect.top - (rect.height / 2);

      const rotateY = (x / (rect.width / 2)) * 14; // max 14 deg
      const rotateX = -(y / (rect.height / 2)) * 14; // max 14 deg

      card.style.transform = `perspective(1000px) rotateX(${rotateX.toFixed(2)}deg) rotateY(${rotateY.toFixed(2)}deg) translateZ(12px)`;
    });

    rightPanel.addEventListener('mouseleave', () => {
      card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) translateZ(0)';
    });
  }

  /* Password Toggle */
  function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (!input) return;
    const isPass = input.type === 'password';
    input.type = isPass ? 'text' : 'password';

    const openIcon = btn.querySelector('.eye-open');
    const closedIcon = btn.querySelector('.eye-closed');
    if (openIcon && closedIcon) {
      openIcon.style.display = isPass ? 'none' : 'block';
      closedIcon.style.display = isPass ? 'block' : 'none';
    }
  }

  document.addEventListener('DOMContentLoaded', () => {
    updateAuthSlideUI(false);
    startTimeline();
    init3DParallax();
  });
</script>
@endsection
