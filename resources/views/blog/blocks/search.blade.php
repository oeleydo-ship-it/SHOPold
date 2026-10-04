{{-- Blog list: search form. Shared by every theme; replace at themes/<slug>/blog/blocks/search.blade.php --}}
                <!-- Search -->
                <form action="{{ route('blog.index') }}" method="GET" class="mb-8">
                    <div class="flex gap-2">
                        <input type="text" name="search" value="{{ request('search') }}" 
                               placeholder="Search articles..." 
                               class="flex-1 px-4 py-3 border rounded-lg focus:ring-2 focus:ring-primary-500">
                        <button type="submit" class="px-6 py-3 bg-primary-600 text-white rounded-lg hover:bg-primary-700">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
