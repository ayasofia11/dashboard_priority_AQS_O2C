<x-layouts.app title="Commandes">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <h1 class="h3 mb-0">Commandes</h1>

        <div class="d-flex gap-2">
        @can('export-orders')
            <a href="{{ route('orders.export', request()->query()) }}" class="btn aqs-btn-primary">
                Exporter
            </a>
        @endcan

        @can('import-orders')
            <a href="{{ route('imports.create') }}" class="btn aqs-btn-primary">
                Importer un fichier
            </a>
        @endcan
        </div>

    </div>

    {{-- Barre de recherche et filtres --}}
    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" action="{{ route('orders.index') }}" class="row g-2 align-items-end">

                <div class="col-md-4">
                    <label class="form-label small">Recherche</label>
                    <input type="text" name="search" value="{{ request('search') }}"
                           class="form-control form-control-sm"
                           placeholder="N° commande, client, produit...">
                </div>

                <div class="col-md-2">
                    <label class="form-label small">Priorité</label>
                    <select name="priority" class="form-select form-select-sm">
                        <option value="">Toutes</option>
                        <option value="bloquee"     @selected(request('priority')==='bloquee')>Bloquée</option>
                        <option value="critique"    @selected(request('priority')==='critique')>Critique</option>
                        <option value="urgente"     @selected(request('priority')==='urgente')>Urgente</option>
                        <option value="prioritaire" @selected(request('priority')==='prioritaire')>Prioritaire</option>
                        <option value="normale"     @selected(request('priority')==='normale')>Normale</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label small">Du</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}"
                           class="form-control form-control-sm">
                </div>

                <div class="col-md-2">
                    <label class="form-label small">Au</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}"
                           class="form-control form-control-sm">
                </div>

                <div class="col-md-1 form-check ms-2 mb-1">
                    <input class="form-check-input" type="checkbox" name="stock_alert" value="1"
                           id="stockAlert" @checked(request('stock_alert'))>
                    <label class="form-check-label small" for="stockAlert">Stock</label>
                </div>

                <div class="col-md-1 d-flex gap-1">
                    <button type="submit" class="btn btn-sm aqs-btn-primary w-100">Filtrer</button>
                </div>

            </form>

            @if (request()->anyFilled(['search', 'priority', 'date_from', 'date_to', 'stock_alert']))
                <a href="{{ route('orders.index') }}" class="small d-inline-block mt-2 aqs-link">
                    Réinitialiser les filtres
                </a>
            @endif
        </div>
    </div>

    {{-- Résultats --}}
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>N° commande</th>
                        <th>Client</th>
                        <th>Date</th>
                        <th>Priorité</th>
                        <th>Score</th>
                        <th>Motif</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orders as $order)
                        @php
                            $level = $order->priority_level->value ?? $order->priority_level;
                            [$bgColor, $textColor, $label] = match($level) {
                                'bloquee'     => ['#9E9E9E', '#fff', 'Bloquée'],
                                'critique'    => ['#E53935', '#fff', 'Critique'],
                                'urgente'     => ['#FB8C00', '#fff', 'Urgente'],
                                'prioritaire' => ['#FDD835', '#1A1A1A', 'Prioritaire'],
                                'normale'     => ['#43A047', '#fff', 'Normale'],
                                default       => ['#f5f5f5', '#333', '—'],
                            };
                        @endphp
                        <tr>
                            <td>
                                <a href="{{ route('orders.show', $order->id) }}" class="aqs-link fw-semibold">
                                    {{ $order->order_number }}
                                </a>
                            </td>
                            <td>{{ $order->customer->name }}</td>
                            <td>{{ $order->order_date->format('d/m/Y') }}</td>
                            <td>
                                <span class="px-3 py-1 rounded-pill fw-semibold d-inline-block"
                                      style="background-color: {{ $bgColor }}; color: {{ $textColor }}; font-size: 0.85rem;">
                                    {{ $label }}
                                </span>
                            </td>
                            <td>{{ $order->final_score !== null ? number_format($order->final_score, 1) : '—' }}</td>
                            <td class="small text-muted">{{ $order->reason ?? '—' }}</td>
                            <td>
                                <a href="{{ route('orders.show', $order->id) }}" class="btn btn-sm btn-outline-secondary">
                                    Détail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Aucune commande trouvée.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">
            {{ $orders->onEachSide(1)->links('pagination::bootstrap-5') }}
        </div>
    </div>

</x-layouts.app>

<style>
    .aqs-btn-primary { background-color: #2E7D4F; border-color: #2E7D4F; color: #fff; }
    .aqs-btn-primary:hover { background-color: #256341; border-color: #256341; color: #fff; }
    .aqs-link { color: #7B2942; text-decoration: none; }
    .aqs-link:hover { color: #5c1f32; text-decoration: underline; }
    .form-control:focus { border-color: #2E7D4F; box-shadow: 0 0 0 0.2rem rgba(46, 125, 79, 0.2); }
</style>
