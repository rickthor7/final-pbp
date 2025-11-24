@extends('layouts.app')

@section('title', 'Dashboard - TailorCraft')

@section('content')
<div class="min-h-screen bg-gray-50 py-8" x-data="dashboard()">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Welcome Section -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                Welcome back, <span class="gradient-text">{{ Auth::user()->name }}!</span>
            </h1>
            <p class="text-gray-600">Here's what's happening with your fashion creations today.</p>
        </div>

        <!-- Stats Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Designs -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-purple-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Designs</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.total_designs">0</p>
                    </div>
                    <div class="p-3 bg-purple-100 rounded-lg">
                        <i class="fas fa-palette text-purple-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center text-sm text-green-600">
                        <i class="fas fa-arrow-up mr-1"></i>
                        <span x-text="`${stats.designs_growth}% from last month`"></span>
                    </div>
                </div>
            </div>

            <!-- Active Orders -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Active Orders</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.active_orders">0</p>
                    </div>
                    <div class="p-3 bg-blue-100 rounded-lg">
                        <i class="fas fa-shopping-bag text-blue-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-clock mr-1"></i>
                        <span x-text="`${stats.orders_in_production} in production`"></span>
                    </div>
                </div>
            </div>

            <!-- Total Spent -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Total Spent</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="`Rp ${formatCurrency(stats.total_spent)}`">0</p>
                    </div>
                    <div class="p-3 bg-green-100 rounded-lg">
                        <i class="fas fa-credit-card text-green-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-tag mr-1"></i>
                        <span x-text="`${stats.completed_orders} orders completed`"></span>
                    </div>
                </div>
            </div>

            <!-- Favorite Tailors -->
            <div class="bg-white rounded-2xl shadow-lg p-6 border-l-4 border-orange-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600">Favorite Tailors</p>
                        <p class="text-2xl font-bold text-gray-900" x-text="stats.favorite_tailors">0</p>
                    </div>
                    <div class="p-3 bg-orange-100 rounded-lg">
                        <i class="fas fa-user-secret text-orange-600 text-xl"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-star mr-1"></i>
                        <span>Based on your orders</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Recent Activity -->
            <div class="lg:col-span-2 space-y-8">
                
                <!-- Quick Actions -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Quick Actions</h2>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <a href="{{ route('design-studio') }}" 
                           class="flex flex-col items-center p-4 border border-gray-200 rounded-xl hover:border-purple-300 hover:shadow-md transition-all duration-200 group">
                            <div class="p-3 bg-purple-100 rounded-lg mb-3 group-hover:bg-purple-200 transition-colors duration-200">
                                <i class="fas fa-plus text-purple-600 text-lg"></i>
                            </div>
                            <span class="text-sm font-medium text-gray-900 text-center">New Design</span>
                        </a>
                        
                        <a href="{{ route('designs.index') }}" 
                           class="flex flex-col items-center p-4 border border-gray-200 rounded-xl hover:border-blue-300 hover:shadow-md transition-all duration-200 group">
                            <div class="p-3 bg-blue-100 rounded-lg mb-3 group-hover:bg-blue-200 transition-colors duration-200">
                                <i class="fas fa-project-diagram text-blue-600 text-lg"></i>
                            </div>
                            <span class="text-sm font-medium text-gray-900 text-center">My Designs</span>
                        </a>
                        
                        <a href="{{ route('orders.index') }}" 
                           class="flex flex-col items-center p-4 border border-gray-200 rounded-xl hover:border-green-300 hover:shadow-md transition-all duration-200 group">
                            <div class="p-3 bg-green-100 rounded-lg mb-3 group-hover:bg-green-200 transition-colors duration-200">
                                <i class="fas fa-shopping-bag text-green-600 text-lg"></i>
                            </div>
                            <span class="text-sm font-medium text-gray-900 text-center">My Orders</span>
                        </a>
                        
                        <a href="{{ route('fabrics.index') }}" 
                           class="flex flex-col items-center p-4 border border-gray-200 rounded-xl hover:border-orange-300 hover:shadow-md transition-all duration-200 group">
                            <div class="p-3 bg-orange-100 rounded-lg mb-3 group-hover:bg-orange-200 transition-colors duration-200">
                                <i class="fas fa-tshirt text-orange-600 text-lg"></i>
                            </div>
                            <span class="text-sm font-medium text-gray-900 text-center">Fabrics</span>
                        </a>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-semibold text-gray-900">Recent Orders</h2>
                        <a href="{{ route('orders.index') }}" class="text-purple-600 hover:text-purple-700 text-sm font-medium">
                            View All
                        </a>
                    </div>
                    
                    <div class="space-y-4">
                        <template x-for="order in recentOrders" :key="order.id">
                            <div class="flex items-center justify-between p-4 border border-gray-200 rounded-xl hover:border-purple-200 hover:shadow-sm transition-all duration-200">
                                <div class="flex items-center space-x-4">
                                    <img :src="order.design.preview_image_url" :alt="order.design.design_name" 
                                         class="w-12 h-12 rounded-lg object-cover">
                                    <div>
                                        <h3 class="font-medium text-gray-900" x-text="order.design.design_name"></h3>
                                        <p class="text-sm text-gray-600" x-text="order.design.garment_template.name"></p>
                                    </div>
                                </div>
                                
                                <div class="text-right">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium"
                                          :class="getStatusBadgeClass(order.status)">
                                        <span x-text="getStatusText(order.status)"></span>
                                    </span>
                                    <p class="text-sm text-gray-600 mt-1" x-text="`Rp ${formatCurrency(order.total_amount)}`"></p>
                                </div>
                            </div>
                        </template>
                        
                        <template x-if="recentOrders.length === 0">
                            <div class="text-center py-8">
                                <i class="fas fa-shopping-bag text-4xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500">No orders yet</p>
                                <a href="{{ route('design-studio') }}" class="inline-block mt-3 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors duration-200">
                                    Create Your First Design
                                </a>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="space-y-8">
                
                <!-- Recent Designs -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-xl font-semibold text-gray-900">Recent Designs</h2>
                        <a href="{{ route('designs.index') }}" class="text-purple-600 hover:text-purple-700 text-sm font-medium">
                            View All
                        </a>
                    </div>
                    
                    <div class="space-y-4">
                        <template x-for="design in recentDesigns" :key="design.id">
                            <div class="group cursor-pointer" @click="viewDesign(design.id)">
                                <div class="relative overflow-hidden rounded-xl mb-2">
                                    <img :src="design.preview_image_url" :alt="design.design_name" 
                                         class="w-full h-32 object-cover group-hover:scale-105 transition-transform duration-300">
                                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-20 transition-all duration-300 flex items-center justify-center">
                                        <i class="fas fa-eye text-white text-xl opacity-0 group-hover:opacity-100 transform translate-y-2 group-hover:translate-y-0 transition-all duration-300"></i>
                                    </div>
                                </div>
                                <h3 class="font-medium text-gray-900 truncate" x-text="design.design_name"></h3>
                                <p class="text-sm text-gray-600" x-text="design.garment_template.name"></p>
                            </div>
                        </template>
                        
                        <template x-if="recentDesigns.length === 0">
                            <div class="text-center py-8">
                                <i class="fas fa-palette text-4xl text-gray-300 mb-3"></i>
                                <p class="text-gray-500">No designs yet</p>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Recommended Tailors -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Recommended Tailors</h2>
                    
                    <div class="space-y-4">
                        <template x-for="tailor in recommendedTailors" :key="tailor.id">
                            <div class="flex items-center space-x-3 p-3 border border-gray-200 rounded-xl hover:border-purple-200 hover:shadow-sm transition-all duration-200">
                                <img :src="tailor.shop_logo_url" :alt="tailor.shop_name" 
                                     class="w-12 h-12 rounded-lg object-cover border border-gray-200">
                                <div class="flex-1 min-w-0">
                                    <h3 class="font-medium text-gray-900 truncate" x-text="tailor.shop_name"></h3>
                                    <div class="flex items-center space-x-2 mt-1">
                                        <div class="flex items-center">
                                            <i class="fas fa-star text-yellow-400 text-sm"></i>
                                            <span class="text-sm text-gray-600 ml-1" x-text="tailor.rating"></span>
                                        </div>
                                        <span class="text-sm text-gray-500">•</span>
                                        <span class="text-sm text-gray-600" x-text="`${tailor.total_reviews} reviews`"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function dashboard() {
    return {
        stats: {
            total_designs: 0,
            designs_growth: 0,
            active_orders: 0,
            orders_in_production: 0,
            total_spent: 0,
            completed_orders: 0,
            favorite_tailors: 0
        },
        recentOrders: [],
        recentDesigns: [],
        recommendedTailors: [],
        
        init() {
            this.loadDashboardData();
        },
        
        async loadDashboardData() {
            try {
                // Simulate API call
                await new Promise(resolve => setTimeout(resolve, 1000));
                
                // Mock data - in real app, this would come from API
                this.stats = {
                    total_designs: 12,
                    designs_growth: 15,
                    active_orders: 3,
                    orders_in_production: 2,
                    total_spent: 2450000,
                    completed_orders: 8,
                    favorite_tailors: 2
                };
                
                this.recentOrders = [
                    {
                        id: 1,
                        design: {
                            design_name: 'Summer Floral Dress',
                            preview_image_url: 'https://via.placeholder.com/100/8b5cf6/ffffff?text=DF',
                            garment_template: { name: 'Summer Dress' }
                        },
                        status: 'in_production',
                        total_amount: 450000
                    },
                    {
                        id: 2,
                        design: {
                            design_name: 'Business Shirt',
                            preview_image_url: 'https://via.placeholder.com/100/3b82f6/ffffff?text=BS',
                            garment_template: { name: 'Classic Shirt' }
                        },
                        status: 'quality_check',
                        total_amount: 320000
                    }
                ];
                
                this.recentDesigns = [
                    {
                        id: 1,
                        design_name: 'Evening Gown',
                        preview_image_url: 'https://via.placeholder.com/300/8b5cf6/ffffff?text=EG',
                        garment_template: { name: 'Elegant Gown' }
                    },
                    {
                        id: 2,
                        design_name: 'Casual Pants',
                        preview_image_url: 'https://via.placeholder.com/300/10b981/ffffff?text=CP',
                        garment_template: { name: 'Relaxed Pants' }
                    }
                ];
                
                this.recommendedTailors = [
                    {
                        id: 1,
                        shop_name: 'Budi Master Tailor',
                        shop_logo_url: 'https://via.placeholder.com/100/8b5cf6/ffffff?text=BM',
                        rating: 4.8,
                        total_reviews: 127
                    },
                    {
                        id: 2,
                        shop_name: 'Sari Fashion House',
                        shop_logo_url: 'https://via.placeholder.com/100/3b82f6/ffffff?text=SF',
                        rating: 4.9,
                        total_reviews: 89
                    }
                ];
                
            } catch (error) {
                console.error('Failed to load dashboard data:', error);
                showNotification('Failed to load dashboard data', 'error');
            }
        },
        
        getStatusBadgeClass(status) {
            const classes = {
                'pending': 'bg-yellow-100 text-yellow-800',
                'payment_pending': 'bg-orange-100 text-orange-800',
                'paid': 'bg-blue-100 text-blue-800',
                'in_production': 'bg-purple-100 text-purple-800',
                'quality_check': 'bg-cyan-100 text-cyan-800',
                'completed': 'bg-green-100 text-green-800'
            };
            return classes[status] || 'bg-gray-100 text-gray-800';
        },
        
        getStatusText(status) {
            const texts = {
                'pending': 'Pending',
                'payment_pending': 'Payment Pending',
                'paid': 'Paid',
                'in_production': 'In Production',
                'quality_check': 'Quality Check',
                'completed': 'Completed'
            };
            return texts[status] || status;
        },
        
        viewDesign(designId) {
            window.location.href = `/designs/${designId}`;
        },
        
        formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID').format(amount);
        }
    };
}
</script>
@endpush
