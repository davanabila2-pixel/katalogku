@props(['order'])

@if($order->shipping_courier || $order->tracking_number)
  <div style="margin-top:8px;padding:8px 10px;border-radius:10px;background:#eff6ff;border:1px solid #bfdbfe;display:inline-flex;flex-wrap:wrap;align-items:center;gap:8px;font-size:12.5px;color:#1e3a8a;">
    @if($order->shipping_courier)
      <span>🚚 <strong>{{ $order->shipping_courier }}</strong>{{ $order->shipping_service ? ' · ' . $order->shipping_service : '' }}</span>
    @endif
    @if($order->tracking_number)
      <span>📦 No. Resi: <strong style="font-family:monospace;color:#0f172a;">{{ $order->tracking_number }}</strong></span>
      <button type="button" onclick="window.copyOrderResi && window.copyOrderResi('{{ e($order->tracking_number) }}', this)" style="border:1px solid #93c5fd;background:#fff;color:#1d4ed8;border-radius:6px;padding:2px 8px;font-size:11px;font-weight:700;cursor:pointer;">Salin Resi</button>
    @endif
  </div>
@endif

@once
  <script>
    window.copyOrderResi = function (resi, btn) {
      if (!resi) return;
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(resi).then(function () {
          var orig = btn.textContent;
          btn.textContent = '✓ Disalin';
          setTimeout(function () { btn.textContent = orig; }, 1600);
        }).catch(function () {
          alert('No. Resi: ' + resi);
        });
      } else {
        alert('No. Resi: ' + resi);
      }
    };
  </script>
@endonce

