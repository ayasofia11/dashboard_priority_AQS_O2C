<x-layouts.app title="Importer les fichiers">

    <h1 class="h3 mb-4">Importer les fichiers</h1>

    <div class="row g-3">

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6">Clients</h2>
                    <form method="POST" action="{{ route('imports.customers') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="file" accept=".xlsx,.xls" class="form-control mb-2" required>
                        <button type="submit" class="btn aqs-btn-primary w-100">Importer</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6">Produits</h2>
                    <form method="POST" action="{{ route('imports.products') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="file" accept=".xlsx,.xls" class="form-control mb-2" required>
                        <button type="submit" class="btn aqs-btn-primary w-100">Importer</button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card h-100">
                <div class="card-body">
                    <h2 class="h6">Commandes</h2>
                    <form method="POST" action="{{ route('imports.orders') }}" enctype="multipart/form-data">
                        @csrf
                        <input type="file" name="file" accept=".xlsx,.xls" class="form-control mb-2" required>
                        <button type="submit" class="btn aqs-btn-primary w-100">Importer</button>
                    </form>
                </div>
            </div>
        </div>

    </div>

    <p class="text-muted small mt-3">
        Importez chaque fichier séparément, dans l'ordre : clients, puis produits, puis commandes.
    </p>

</x-layouts.app>

<style>
    .aqs-btn-primary { background-color: #2E7D4F; border-color: #2E7D4F; color: #fff; }
    .aqs-btn-primary:hover { background-color: #256341; border-color: #256341; color: #fff; }
</style>
