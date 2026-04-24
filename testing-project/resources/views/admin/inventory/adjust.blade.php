@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3>Ajuste Manual de Stock</h3>
                </div>
                <div class="card-body">
                    <h5 class="card-title">Produto: {{ $product->name }}</h5>
                    <p class="card-text">Stock Atual: <strong>{{ $product->stock }}</strong> unidades.</p>
                    
                    <form action="{{ route('admin.inventory.adjust.store', $product) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="new_stock">Nova Quantidade em Stock:</label>
                            <input type="number" 
                                   id="new_stock" 
                                   name="new_stock" 
                                   class="form-control @error('new_stock') is-invalid @enderror" 
                                   value="{{ old('new_stock', $product->stock) }}"
                                   min="0"
                                   required>
                            @error('new_stock')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">Guardar Ajuste</button>
                            <a href="{{ route('admin.inventory.index') }}" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection