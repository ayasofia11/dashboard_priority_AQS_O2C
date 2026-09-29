<x-layouts.app title="Tableau de bord">

    <h1 class="h3 mb-4">Tableau de bord</h1>

    @if ($stock_alert['has_alert'])
        <div class="d-flex align-items-center gap-3 p-3 mb-4 rounded" style="background-color: #FDECEA; border: 1px solid #E53935;">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none">
                <path d="M12 2L1 21H23L12 2Z" fill="#E53935" stroke="#fff" stroke-width="1"/>
                <rect x="11" y="9" width="2" height="6" rx="1" fill="#fff"/>
                <circle cx="12" cy="17.5" r="1.2" fill="#fff"/>
            </svg>
            <div>
                <strong style="color: #B71C1C;">Alerte stock</strong>
                <span class="text-muted">— {{ $stock_alert['count'] }} commande(s) bloquée(s) pour rupture de stock.</span>
            </div>
        </div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-6 col-md-2">
            <div class="card text-center h-100">
                <div class="card-body">
                    <div class="fs-3 fw-bold">{{ $total_evaluated }}</div>
                    <div class="small text-muted">Total évaluées</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-2">
            <div class="card text-center h-100" style="border-top: 4px solid #9E9E9E;">
                <div class="card-body">
                    <div class="fs-3 fw-bold">{{ $by_priority_level['bloquee'] }}</div>
                    <div class="small text-muted">Bloquées</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-2">
            <div class="card text-center h-100" style="border-top: 4px solid #E53935;">
                <div class="card-body">
                    <div class="fs-3 fw-bold">{{ $by_priority_level['critique'] }}</div>
                    <div class="small text-muted">Critiques</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-2">
            <div class="card text-center h-100" style="border-top: 4px solid #FB8C00;">
                <div class="card-body">
                    <div class="fs-3 fw-bold">{{ $by_priority_level['urgente'] }}</div>
                    <div class="small text-muted">Urgentes</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-2">
            <div class="card text-center h-100" style="border-top: 4px solid #FDD835;">
                <div class="card-body">
                    <div class="fs-3 fw-bold">{{ $by_priority_level['prioritaire'] }}</div>
                    <div class="small text-muted">Prioritaires</div>
                </div>
            </div>
        </div>

        <div class="col-6 col-md-2">
            <div class="card text-center h-100" style="border-top: 4px solid #43A047;">
                <div class="card-body">
                    <div class="fs-3 fw-bold">{{ $by_priority_level['normale'] }}</div>
                    <div class="small text-muted">Normales</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-body d-flex justify-content-between align-items-center">
            <span>Montant total TTC bloqué</span>
            <strong class="fs-5" style="color: #7B2942;">
                {{ number_format($blocked_amount_ttc, 0, ',', ' ') }} DZD
            </strong>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h2 class="h6 mb-3">Répartition des commandes par priorité</h2>
            <canvas id="priorityChart" height="90"></canvas>
        </div>
    </div>

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
    const ctx = document.getElementById('priorityChart');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Bloquée', 'Critique', 'Urgente', 'Prioritaire', 'Normale'],
            datasets: [{
                label: 'Nombre de commandes',
                data: [
                    {{ $by_priority_level['bloquee'] }},
                    {{ $by_priority_level['critique'] }},
                    {{ $by_priority_level['urgente'] }},
                    {{ $by_priority_level['prioritaire'] }},
                    {{ $by_priority_level['normale'] }}
                ],
                backgroundColor: ['#9E9E9E', '#E53935', '#FB8C00', '#FDD835', '#43A047'],
                borderRadius: 6,
            }]
        },
        options: {
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 20 } } }
        }
    });
</script>
@endpush

</x-layouts.app>

<style>
    .aqs-btn-primary { background-color: #2E7D4F; border-color: #2E7D4F; color: #fff; }
    .aqs-btn-primary:hover { background-color: #256341; border-color: #256341; color: #fff; }
    .aqs-link { color: #7B2942; text-decoration: none; }
    .aqs-link:hover { color: #5c1f32; text-decoration: underline; }
</style>
