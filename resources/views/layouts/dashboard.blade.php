<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="robots" content="noindex, nofollow">
  <title>@yield('title', 'Dashboard') — {{ config('app.name') }}</title>
  <link rel="stylesheet" href="{{ asset('css/app.css') }}">
  <script src="{{ asset('js/admin-api.js') }}" defer></script>
</head>
<body>
@php($dashboardStore = $store ?? null)
<div class="dash" id="dash">
  <div class="sidebar-overlay" data-sidebar-close></div>
  <x-dashboard.sidebar :store="$dashboardStore" />
  <div class="dash-body">
    <x-dashboard.topbar :store="$dashboardStore" />
    <div class="dash-main">
      <x-dashboard.header :store="$dashboardStore" />
      @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
      @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
      @if(session('error'))<div class="alert alert-error">{{ session('error') }}</div>@endif
      @if($errors->any())<div class="alert alert-error"><ul style="margin:0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
      @yield('content')
    </div>
  </div>
</div>
<script>
    // Sidebar: buka/tutup (mobile off-canvas, desktop collapse + simpan preferensi)
    (function () {
        var dash = document.getElementById('dash');
        if (!dash) return;

        var mq = window.matchMedia('(max-width: 900px)');
        var storageKey = 'katalog.sidebar';
        var toggles = document.querySelectorAll('[data-sidebar-toggle]');
        var overlay = document.querySelector('[data-sidebar-close]');

        function syncAria() {
            var expanded = mq.matches
                ? dash.classList.contains('sidebar-open')
                : !dash.classList.contains('is-collapsed');
            toggles.forEach(function (btn) {
                btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
            });
        }

        function restoreDesktopState() {
            if (!mq.matches && localStorage.getItem(storageKey) === 'collapsed') {
                dash.classList.add('is-collapsed');
            }
        }

        restoreDesktopState();
        syncAria();

        toggles.forEach(function (btn) {
            btn.addEventListener('click', function () {
                if (mq.matches) {
                    dash.classList.toggle('sidebar-open');
                } else {
                    dash.classList.toggle('is-collapsed');
                    localStorage.setItem(storageKey, dash.classList.contains('is-collapsed') ? 'collapsed' : 'open');
                }
                syncAria();
            });
        });

        if (overlay) {
            overlay.addEventListener('click', function () {
                dash.classList.remove('sidebar-open');
                syncAria();
            });
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                dash.classList.remove('sidebar-open');
                syncAria();
            }
        });

        mq.addEventListener('change', function () {
            dash.classList.remove('sidebar-open');
            if (!mq.matches) {
                if (localStorage.getItem(storageKey) === 'collapsed') {
                    dash.classList.add('is-collapsed');
                } else {
                    dash.classList.remove('is-collapsed');
                }
            }
            syncAria();
        });
    })();

    // Refresh CSRF token every 20 minutes (before default 120-minute expiry)
    setInterval(function() {
        var tokenInput = document.querySelector('meta[name="csrf-token"]');
        var csrfInputs = document.querySelectorAll('input[name="_token"]');
        if (tokenInput) {
            var xhr = new XMLHttpRequest();
            xhr.open('GET', '/sanctum/csrf-cookie', true);
            xhr.onload = function() {
                if (xhr.status === 200) {
                    var newToken = xhr.getResponseHeader('X-CSRF-TOKEN');
                    // Update the meta tag
                    if (tokenInput) {
                        tokenInput.setAttribute('content', newToken);
                    }
                    // Update all hidden CSRF inputs in forms
                    csrfInputs.forEach(function(input) {
                        input.value = newToken;
                    });
                }
            };
            xhr.send();
        }
    }, 20 * 60 * 1000); // 20 minutes
</script>
</body>
</html>
