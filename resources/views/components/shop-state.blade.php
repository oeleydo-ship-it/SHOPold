{{-- Shop page Alpine state (filters panel, grid/list view, column count) shared by every theme's shop layout. --}}
<div x-data="{
    showFilters: false,
    viewMode: localStorage.getItem('shopViewMode') || 'grid',
    gridCols: localStorage.getItem('shopGridCols') || '{{ setting('shop.grid_columns', '4') }}',
    setViewMode(mode) { this.viewMode = mode; localStorage.setItem('shopViewMode', mode); },
    setGridCols(cols) { this.gridCols = cols; localStorage.setItem('shopGridCols', cols); }
}" {{ $attributes }}>
    {{ $slot }}
</div>
