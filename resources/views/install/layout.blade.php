<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Installation') - Laravel Shop</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: { 500: '#3b82f6', 600: '#2563eb', 700: '#1d4ed8' }
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen">
    <div class="min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-2xl">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-primary-600 rounded-2xl mb-4">
                    <i class="fas fa-shopping-bag text-white text-2xl"></i>
                </div>
                <h1 class="text-2xl font-bold text-gray-800">Laravel Shop Installer</h1>
            </div>

            <!-- Progress Steps -->
            <div class="mb-8">
                @php
                    $steps = [
                        ['route' => 'install.welcome', 'label' => 'Welcome', 'icon' => 'fa-home'],
                        ['route' => 'install.requirements', 'label' => 'Requirements', 'icon' => 'fa-check-circle'],
                        ['route' => 'install.database', 'label' => 'Database', 'icon' => 'fa-database'],
                        ['route' => 'install.admin', 'label' => 'Setup', 'icon' => 'fa-cog'],
                        ['route' => 'install.complete', 'label' => 'Complete', 'icon' => 'fa-flag-checkered'],
                    ];
                    $currentRoute = request()->route()->getName();
                    $currentIndex = collect($steps)->search(fn($s) => $s['route'] === $currentRoute);
                @endphp
                <div class="flex items-center justify-between">
                    @foreach($steps as $index => $step)
                        <div class="flex items-center {{ $index < count($steps) - 1 ? 'flex-1' : '' }}">
                            <div class="flex flex-col items-center">
                                <div class="w-10 h-10 rounded-full flex items-center justify-center 
                                    {{ $index < $currentIndex ? 'bg-green-500 text-white' : ($index === $currentIndex ? 'bg-primary-600 text-white' : 'bg-gray-200 text-gray-400') }}">
                                    @if($index < $currentIndex)
                                        <i class="fas fa-check"></i>
                                    @else
                                        <i class="fas {{ $step['icon'] }}"></i>
                                    @endif
                                </div>
                                <span class="text-xs mt-1 {{ $index <= $currentIndex ? 'text-gray-700' : 'text-gray-400' }}">{{ $step['label'] }}</span>
                            </div>
                            @if($index < count($steps) - 1)
                                <div class="flex-1 h-1 mx-2 {{ $index < $currentIndex ? 'bg-green-500' : 'bg-gray-200' }}"></div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Content Card -->
            <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
                @yield('content')
            </div>

            <!-- Footer -->
            <p class="text-center text-gray-500 text-sm mt-6">
                Laravel Shop &copy; {{ date('Y') }}
            </p>
        </div>
    </div>
</body>
</html>
