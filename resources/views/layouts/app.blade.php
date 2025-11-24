<!DOCTYPE html>
<html lang="id" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'TailorCraft - Custom Fashion Platform')</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Fabric.js for Canvas Manipulation -->
    <script src="https://cdn.jsdelivr.net/npm/fabric@5.3.0/dist/fabric.min.js"></script>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Custom Styles -->
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        
        .glass-effect {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .fabric-item {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            cursor: grab;
        }
        
        .fabric-item:hover {
            transform: translateY(-5px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        
        .fabric-item.dragging {
            opacity: 0.6;
            transform: scale(0.95);
        }
        
        .avatar-canvas {
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            border-radius: 20px;
            background: linear-gradient(145deg, #ffffff, #f0f4ff);
        }
        
        .part-drop-zone {
            transition: all 0.3s ease;
            border: 2px dashed #d1d5db;
        }
        
        .part-drop-zone.drag-over {
            border-color: #8b5cf6;
            background-color: #faf5ff;
            transform: scale(1.05);
        }
        
        .part-drop-zone.has-fabric {
            border-style: solid;
            border-color: #10b981;
            background-color: #ecfdf5;
        }
        
        .scrollbar-thin {
            scrollbar-width: thin;
            scrollbar-color: #cbd5e1 #f1f5f9;
        }
        
        .scrollbar-thin::-webkit-scrollbar {
            width: 6px;
        }
        
        .scrollbar-thin::-webkit-scrollbar-track {
            background: #f1f5f9;
            border-radius: 3px;
        }
        
        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        
        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
        
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        
        .gradient-text {
            background: linear-gradient(135deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
    
    @stack('styles')
</head>
<body class="h-full bg-gray-50 font-sans antialiased">
    <!-- Loading Spinner -->
    <div id="globalLoader" class="fixed inset-0 bg-white z-50 flex items-center justify-center transition-opacity duration-300">
        <div class="text-center">
            <div class="w-16 h-16 border-4 border-purple-200 border-t-purple-600 rounded-full animate-spin mx-auto mb-4"></div>
            <p class="text-gray-600 font-medium">Loading TailorCraft...</p>
        </div>
    </div>

    <!-- Main Layout -->
    <div id="app" class="min-h-full" x-data="{ 
        mobileMenuOpen: false,
        userMenuOpen: false,
        notificationsOpen: false,
        cartItems: 0,
        notifications: []
    }">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg sticky top-0 z-40">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <!-- Logo -->
                    <div class="flex items-center">
                        <a href="{{ route('home') }}" class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-gradient-to-r from-purple-600 to-indigo-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-cut text-white text-lg"></i>
                            </div>
                            <span class="text-2xl font-bold gradient-text">TailorCraft</span>
                        </a>
                    </div>

                    <!-- Desktop Navigation -->
                    <div class="hidden md:flex items-center space-x-8">
                        <a href="{{ route('design-studio') }}" class="text-gray-700 hover:text-purple-600 font-medium transition-colors duration-200 flex items-center space-x-1">
                            <i class="fas fa-palette"></i>
                            <span>Design Studio</span>
                        </a>
                        <a href="{{ route('designs.index') }}" class="text-gray-700 hover:text-purple-600 font-medium transition-colors duration-200 flex items-center space-x-1">
                            <i class="fas fa-project-diagram"></i>
                            <span>My Designs</span>
                        </a>
                        <a href="{{ route('orders.index') }}" class="text-gray-700 hover:text-purple-600 font-medium transition-colors duration-200 flex items-center space-x-1">
                            <i class="fas fa-shopping-bag"></i>
                            <span>Orders</span>
                        </a>
                        <a href="{{ route('fabrics.index') }}" class="text-gray-700 hover:text-purple-600 font-medium transition-colors duration-200 flex items-center space-x-1">
                            <i class="fas fa-tshirt"></i>
                            <span>Fabrics</span>
                        </a>
                    </div>

                    <!-- Right Menu -->
                    <div class="flex items-center space-x-4">
                        <!-- Notifications -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="p-2 text-gray-600 hover:text-purple-600 relative transition-colors duration-200">
                                <i class="fas fa-bell text-lg"></i>
                                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center" x-show="notifications.length > 0" x-text="notifications.length"></span>
                            </button>
                            
                            <!-- Notifications Dropdown -->
                            <div x-show="open" @click.away="open = false" 
                                 class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-50"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 transform scale-95"
                                 x-transition:enter-end="opacity-100 transform scale-100">
                                <div class="px-4 py-2 border-b border-gray-100">
                                    <h3 class="font-semibold text-gray-800">Notifications</h3>
                                </div>
                                <div class="max-h-96 overflow-y-auto">
                                    <template x-if="notifications.length === 0">
                                        <div class="px-4 py-8 text-center text-gray-500">
                                            <i class="fas fa-bell-slash text-3xl mb-2"></i>
                                            <p>No notifications</p>
                                        </div>
                                    </template>
                                    <template x-for="notification in notifications" :key="notification.id">
                                        <div class="px-4 py-3 hover:bg-gray-50 border-b border-gray-100 last:border-b-0">
                                            <p class="text-sm text-gray-800" x-text="notification.message"></p>
                                            <span class="text-xs text-gray-500" x-text="notification.time"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <!-- User Menu -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                                <img src="{{ Auth::user()->avatar_url }}" alt="Profile" class="w-8 h-8 rounded-full border-2 border-purple-200">
                                <span class="text-gray-700 font-medium hidden sm:block">{{ Auth::user()->name }}</span>
                                <i class="fas fa-chevron-down text-gray-400 text-sm"></i>
                            </button>
                            
                            <!-- User Dropdown -->
                            <div x-show="open" @click.away="open = false" 
                                 class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 py-2 z-50"
                                 x-transition:enter="transition ease-out duration-200"
                                 x-transition:enter-start="opacity-0 transform scale-95"
                                 x-transition:enter-end="opacity-100 transform scale-100">
                                <a href="{{ route('profile') }}" class="flex items-center space-x-3 px-4 py-2 text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-colors duration-200">
                                    <i class="fas fa-user text-gray-400 w-5"></i>
                                    <span>Profile</span>
                                </a>
                                <a href="{{ route('orders.index') }}" class="flex items-center space-x-3 px-4 py-2 text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-colors duration-200">
                                    <i class="fas fa-shopping-bag text-gray-400 w-5"></i>
                                    <span>My Orders</span>
                                </a>
                                <a href="{{ route('designs.index') }}" class="flex items-center space-x-3 px-4 py-2 text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-colors duration-200">
                                    <i class="fas fa-project-diagram text-gray-400 w-5"></i>
                                    <span>My Designs</span>
                                </a>
                                <div class="border-t border-gray-100 my-1"></div>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="flex items-center space-x-3 w-full px-4 py-2 text-gray-700 hover:bg-red-50 hover:text-red-600 transition-colors duration-200">
                                        <i class="fas fa-sign-out-alt text-gray-400 w-5"></i>
                                        <span>Logout</span>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Mobile menu button -->
                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden p-2 rounded-lg hover:bg-gray-100 transition-colors duration-200">
                            <i class="fas fa-bars text-gray-600 text-lg"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Mobile Navigation -->
            <div x-show="mobileMenuOpen" class="md:hidden border-t border-gray-200 bg-white">
                <div class="px-2 pt-2 pb-3 space-y-1">
                    <a href="{{ route('design-studio') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-colors duration-200">
                        <i class="fas fa-palette w-5"></i>
                        <span>Design Studio</span>
                    </a>
                    <a href="{{ route('designs.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-colors duration-200">
                        <i class="fas fa-project-diagram w-5"></i>
                        <span>My Designs</span>
                    </a>
                    <a href="{{ route('orders.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-colors duration-200">
                        <i class="fas fa-shopping-bag w-5"></i>
                        <span>Orders</span>
                    </a>
                    <a href="{{ route('fabrics.index') }}" class="flex items-center space-x-3 px-3 py-2 rounded-lg text-gray-700 hover:bg-purple-50 hover:text-purple-600 transition-colors duration-200">
                        <i class="fas fa-tshirt w-5"></i>
                        <span>Fabrics</span>
                    </a>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-1">
            @yield('content')
        </main>

        <!-- Footer -->
        <footer class="bg-gray-800 text-white py-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                    <div class="col-span-1 md:col-span-2">
                        <div class="flex items-center space-x-3 mb-4">
                            <div class="w-10 h-10 bg-gradient-to-r from-purple-600 to-indigo-600 rounded-lg flex items-center justify-center">
                                <i class="fas fa-cut text-white text-lg"></i>
                            </div>
                            <span class="text-2xl font-bold text-white">TailorCraft</span>
                        </div>
                        <p class="text-gray-300 mb-4 max-w-md">
                            Platform custom fashion terdepan yang menghubungkan Anda dengan penjahit terbaik dan kain berkualitas tinggi. 
                            Desain pakaian impian Anda dengan mudah.
                        </p>
                        <div class="flex space-x-4">
                            <a href="#" class="text-gray-300 hover:text-white transition-colors duration-200">
                                <i class="fab fa-facebook-f text-lg"></i>
                            </a>
                            <a href="#" class="text-gray-300 hover:text-white transition-colors duration-200">
                                <i class="fab fa-instagram text-lg"></i>
                            </a>
                            <a href="#" class="text-gray-300 hover:text-white transition-colors duration-200">
                                <i class="fab fa-twitter text-lg"></i>
                            </a>
                            <a href="#" class="text-gray-300 hover:text-white transition-colors duration-200">
                                <i class="fab fa-tiktok text-lg"></i>
                            </a>
                        </div>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Quick Links</h3>
                        <ul class="space-y-2">
                            <li><a href="{{ route('design-studio') }}" class="text-gray-300 hover:text-white transition-colors duration-200">Design Studio</a></li>
                            <li><a href="{{ route('fabrics.index') }}" class="text-gray-300 hover:text-white transition-colors duration-200">Fabric Gallery</a></li>
                            <li><a href="#" class="text-gray-300 hover:text-white transition-colors duration-200">Find Tailors</a></li>
                            <li><a href="#" class="text-gray-300 hover:text-white transition-colors duration-200">How It Works</a></li>
                        </ul>
                    </div>
                    
                    <div>
                        <h3 class="text-lg font-semibold mb-4">Support</h3>
                        <ul class="space-y-2">
                            <li><a href="#" class="text-gray-300 hover:text-white transition-colors duration-200">Help Center</a></li>
                            <li><a href="#" class="text-gray-300 hover:text-white transition-colors duration-200">Contact Us</a></li>
                            <li><a href="#" class="text-gray-300 hover:text-white transition-colors duration-200">Privacy Policy</a></li>
                            <li><a href="#" class="text-gray-300 hover:text-white transition-colors duration-200">Terms of Service</a></li>
                        </ul>
                    </div>
                </div>
                
                <div class="border-t border-gray-700 mt-8 pt-8 text-center text-gray-400">
                    <p>&copy; 2024 TailorCraft. All rights reserved. Crafted with ❤️ for fashion enthusiasts.</p>
                </div>
            </div>
        </footer>
    </div>

    <!-- Global Scripts -->
    <script>
        // Hide loader when page is loaded
        window.addEventListener('load', function() {
            const loader = document.getElementById('globalLoader');
            loader.style.opacity = '0';
            setTimeout(() => {
                loader.style.display = 'none';
            }, 300);
        });

        // Global notification system
        window.showNotification = function(message, type = 'info') {
            const notification = {
                id: Date.now(),
                message: message,
                type: type,
                time: new Date().toLocaleTimeString()
            };
            
            // Add to Alpine.js state if available
            if (window.Alpine && Alpine.$data(document.getElementById('app')).notifications) {
                Alpine.$data(document.getElementById('app')).notifications.unshift(notification);
                
                // Auto remove after 5 seconds
                setTimeout(() => {
                    const index = Alpine.$data(document.getElementById('app')).notifications.indexOf(notification);
                    if (index > -1) {
                        Alpine.$data(document.getElementById('app')).notifications.splice(index, 1);
                    }
                }, 5000);
            }
            
            // Also show browser notification if available
            if ('Notification' in window && Notification.permission === 'granted') {
                new Notification('TailorCraft', {
                    body: message,
                    icon: '/logo.png'
                });
            }
        };

        // Request notification permission
        if ('Notification' in window && Notification.permission === 'default') {
            Notification.requestPermission();
        }
    </script>

    @stack('scripts')
</body>
</html>
