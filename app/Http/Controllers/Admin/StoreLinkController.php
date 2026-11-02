<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\StoreLink;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class StoreLinkController extends Controller
{
    public function index(Store $store)
    {
        $this->authorize('viewAny', [StoreLink::class, $store->id]);

        $links = StoreLink::forStore($store->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.links.index', compact('store', 'links'));
    }

    public function store(Request $request, Store $store)
    {
        $this->authorize('create', [StoreLink::class, $store->id]);

        $data = $this->validated($request);
        $data['store_id'] = $store->id;
        $data['sort_order'] = (int) StoreLink::forStore($store->id)->max('sort_order') + 1;
        $data['is_active'] = $request->boolean('is_active');

        $link = StoreLink::create($data);

        AuditLogger::log('link.created', $link, "Tautan \"{$link->title}\" dibuat.", null, $request->user(), $store->id);

        return redirect()->route('admin.links.index', $store)->with('status', 'Tautan dibuat.');
    }

    public function update(Request $request, Store $store, StoreLink $link)
    {
        $this->ensureSameStore($store, $link);
        $this->authorize('update', $link);

        try {
            $data = $this->validated($request);
        } catch (ValidationException $e) {
            // Bawa penanda sedang mengedit supaya formulir kembali terbuka
            // dalam mode ubah, bukan jatuh kembali ke mode tambah.
            return back()
                ->withInput()
                ->withErrors($e->errors())
                ->with('editing', $link->id);
        }

        $data['is_active'] = $request->boolean('is_active');
        unset($data['store_id']);

        $link->update($data);

        AuditLogger::log('link.updated', $link, "Tautan \"{$link->title}\" diperbarui.", null, $request->user(), $store->id);

        return redirect()->route('admin.links.index', $store)->with('status', 'Tautan diperbarui.');
    }

    public function destroy(Request $request, Store $store, StoreLink $link)
    {
        $this->ensureSameStore($store, $link);
        $this->authorize('delete', $link);

        $name = $link->title;
        $link->delete();

        AuditLogger::log('link.deleted', null, "Tautan \"{$name}\" dihapus.", ['store_id' => $store->id], $request->user(), $store->id);

        return redirect()->route('admin.links.index', $store)->with('status', 'Tautan dihapus.');
    }

    public function move(Request $request, Store $store, StoreLink $link)
    {
        $this->ensureSameStore($store, $link);
        $this->authorize('update', $link);

        $direction = $request->input('direction');
        if (! in_array($direction, ['up', 'down'], true)) {
            return back()->withErrors(['direction' => 'Arah pergeseran tidak dikenal.']);
        }

        $ordered = StoreLink::forStore($store->id)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->values();

        $from = $ordered->search(fn (StoreLink $item) => (int) $item->id === (int) $link->id);

        if ($from === false) {
            return back()->withErrors(['link' => 'Tautan tidak ditemukan.']);
        }

        $to = $direction === 'up' ? $from - 1 : $from + 1;
        if ($to < 0 || $to >= $ordered->count()) {
            return back()->with('status', $direction === 'up'
                ? 'Tautan sudah berada di urutan paling atas.'
                : 'Tautan sudah berada di urutan paling bawah.');
        }

        // Nomor ulang seluruh daftar sesuai posisi baru, bukan sekadar menukar
        // dua nilai, supaya tidak pernah ada nomor urut yang kembar.
        $ids = $ordered->pluck('id')->all();
        [$ids[$from], $ids[$to]] = [$ids[$to], $ids[$from]];

        foreach ($ids as $position => $id) {
            StoreLink::whereKey($id)->update(['sort_order' => $position + 1]);
        }

        AuditLogger::log('link.reordered', $link, "Urutan tautan \"{$link->title}\" diubah.", null, $request->user(), $store->id);

        return back()->with('status', 'Urutan tautan diperbarui.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:80'],
            'url' => ['required', 'string', 'max:2000', function (string $attribute, mixed $value, $fail) {
                $value = trim((string) $value);
                $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

                if (! in_array($scheme, ['http', 'https'], true)) {
                    $fail('Alamat tautan harus diawali http:// atau https://.');

                    return;
                }

                if (filter_var($value, FILTER_VALIDATE_URL) === false) {
                    $fail('Alamat tautan tidak valid.');
                }
            }],
        ]);
    }

    protected function ensureSameStore(Store $store, StoreLink $link): void
    {
        if ((int) $link->store_id !== (int) $store->id) {
            abort(404);
        }
    }
}
