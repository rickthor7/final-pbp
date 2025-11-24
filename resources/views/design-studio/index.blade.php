@extends('layouts.app')

@section('title', 'Design Studio - TailorCraft')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 via-white to-purple-50 py-8" 
     x-data="designStudio()" 
     x-init="init()">
    
    <!-- Header Section -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 mb-8">
        <div class="text-center">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-900 mb-4">
                Design <span class="gradient-text">Studio</span>
            </h1>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                Create your custom fashion masterpiece with our intuitive drag & drop designer. 
                Mix and match fabrics, customize measurements, and bring your vision to life.
            </p>
        </div>
    </div>

    <!-- Main Design Interface -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">
            
            <!-- Left Sidebar - Fabrics Library -->
            <div class="xl:col-span-3 space-y-6">
                
                <!-- Fabric Categories -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-layer-group text-purple-600 mr-2"></i>
                        Fabric Categories
                    </h3>
                    <div class="space-y-2">
                        <template x-for="category in fabricCategories" :key="category.id">
                            <button 
                                @click="setActiveCategory(category.id)"
                                class="w-full flex items-center justify-between p-3 rounded-lg transition-all duration-200"
                                :class="activeCategory === category.id 
                                    ? 'bg-purple-50 text-purple-700 border border-purple-200' 
                                    : 'text-gray-700 hover:bg-gray-50 border border-transparent'">
                                <div class="flex items-center space-x-3">
                                    <img :src="category.image_url" :alt="category.name" class="w-8 h-8 rounded-lg object-cover">
                                    <span x-text="category.name" class="font-medium"></span>
                                </div>
                                <span x-text="category.count" class="text-sm bg-gray-100 px-2 py-1 rounded-full"></span>
                            </button>
                        </template>
                    </div>
                </div>

                <!-- Fabric Filters -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-filter text-purple-600 mr-2"></i>
                        Filters
                    </h3>
                    <div class="space-y-4">
                        <!-- Material Type -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Material</label>
                            <select x-model="filters.material" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="">All Materials</option>
                                <template x-for="material in materialTypes" :key="material">
                                    <option x-text="material.charAt(0).toUpperCase() + material.slice(1)" :value="material"></option>
                                </template>
                            </select>
                        </div>
                        
                        <!-- Price Range -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Price Range</label>
                            <div class="flex space-x-2">
                                <input type="number" x-model="filters.minPrice" placeholder="Min" class="w-1/2 rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <input type="number" x-model="filters.maxPrice" placeholder="Max" class="w-1/2 rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>
                        </div>
                        
                        <!-- Pattern -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Pattern</label>
                            <select x-model="filters.pattern" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <option value="">All Patterns</option>
                                <template x-for="pattern in patternTypes" :key="pattern">
                                    <option x-text="pattern.charAt(0).toUpperCase() + pattern.slice(1)" :value="pattern"></option>
                                </template>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Recent Designs -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-history text-purple-600 mr-2"></i>
                        Recent Designs
                    </h3>
                    <div class="space-y-3">
                        <template x-for="design in recentDesigns" :key="design.id">
                            <div class="flex items-center space-x-3 p-3 rounded-lg border border-gray-200 hover:border-purple-300 transition-colors duration-200 cursor-pointer"
                                 @click="loadDesign(design.id)">
                                <img :src="design.preview_image_url" :alt="design.design_name" class="w-12 h-12 rounded-lg object-cover">
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm font-medium text-gray-900 truncate" x-text="design.design_name"></p>
                                    <p class="text-xs text-gray-500" x-text="design.garment_template.name"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- Center Area - Design Canvas & Controls -->
            <div class="xl:col-span-6 space-y-6">
                
                <!-- Design Canvas Container -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <!-- Canvas Header -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 space-y-3 sm:space-y-0">
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">Design Canvas</h3>
                            <p class="text-gray-600 text-sm">Drag fabrics to the avatar or part zones</p>
                        </div>
                        <div class="flex space-x-2">
                            <button @click="resetDesign()" class="px-4 py-2 text-sm border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200 flex items-center space-x-2">
                                <i class="fas fa-redo"></i>
                                <span>Reset</span>
                            </button>
                            <button @click="saveDraft()" class="px-4 py-2 text-sm bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors duration-200 flex items-center space-x-2">
                                <i class="fas fa-save"></i>
                                <span>Save Draft</span>
                            </button>
                            <button @click="generatePreview()" class="px-4 py-2 text-sm bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors duration-200 flex items-center space-x-2">
                                <i class="fas fa-eye"></i>
                                <span>Preview</span>
                            </button>
                        </div>
                    </div>

                    <!-- Avatar Canvas -->
                    <div class="flex justify-center mb-6">
                        <div class="relative">
                            <!-- Canvas Container -->
                            <div class="avatar-canvas overflow-hidden">
                                <canvas id="avatarCanvas" width="400" height="600"></canvas>
                            </div>
                            
                            <!-- Loading Overlay -->
                            <div x-show="isLoading" class="absolute inset-0 bg-white bg-opacity-80 flex items-center justify-center rounded-2xl">
                                <div class="text-center">
                                    <div class="w-8 h-8 border-2 border-purple-200 border-t-purple-600 rounded-full animate-spin mx-auto mb-2"></div>
                                    <p class="text-sm text-gray-600">Loading...</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Garment Parts Control -->
                    <div class="bg-gray-50 rounded-xl p-4">
                        <h4 class="font-medium text-gray-900 mb-4 text-center">Garment Parts</h4>
                        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <template x-for="part in currentTemplate.parts" :key="part">
                                <div class="part-drop-zone text-center p-3 rounded-lg transition-all duration-200"
                                     :class="{
                                         'drag-over': dragOverPart === part,
                                         'has-fabric': fabricAssignments[part]
                                     }"
                                     @dragover.prevent="handlePartDragOver(part)"
                                     @dragleave="handlePartDragLeave(part)"
                                     @drop="handlePartDrop(part, $event)"
                                     :data-part="part">
                                    <div class="w-12 h-12 mx-auto mb-2 rounded-lg bg-white border-2 border-dashed flex items-center justify-center"
                                         :class="{
                                             'border-green-500 bg-green-50': fabricAssignments[part],
                                             'border-gray-300': !fabricAssignments[part],
                                             'border-purple-500': dragOverPart === part
                                         }">
                                        <template x-if="fabricAssignments[part]">
                                            <img :src="getFabricImage(fabricAssignments[part])" alt="Fabric" class="w-10 h-10 rounded object-cover">
                                        </template>
                                        <template x-if="!fabricAssignments[part]">
                                            <i class="fas fa-plus text-gray-400"></i>
                                        </template>
                                    </div>
                                    <span class="text-xs font-medium text-gray-700 capitalize" x-text="part"></span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Design Information -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Design Details -->
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Design Details</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Design Name</label>
                                <input type="text" x-model="designName" placeholder="My Awesome Design" 
                                       class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                                <textarea x-model="designDescription" placeholder="Describe your design..." rows="3"
                                          class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent"></textarea>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Special Instructions</label>
                                <textarea x-model="specialInstructions" placeholder="Any special requirements for the tailor..." rows="2"
                                          class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent"></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Cost Estimation -->
                    <div class="bg-white rounded-2xl shadow-lg p-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Cost Estimation</h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Fabric Cost</span>
                                <span class="font-semibold" x-text="`Rp ${formatCurrency(estimatedCosts.fabric)}`"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Tailoring Cost</span>
                                <span class="font-semibold" x-text="`Rp ${formatCurrency(estimatedCosts.tailoring)}`"></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600">Service Fee</span>
                                <span class="font-semibold" x-text="`Rp ${formatCurrency(estimatedCosts.service)}`"></span>
                            </div>
                            <div class="border-t border-gray-200 pt-3">
                                <div class="flex justify-between items-center">
                                    <span class="text-lg font-semibold text-gray-900">Total Estimated</span>
                                    <span class="text-lg font-bold text-purple-600" x-text="`Rp ${formatCurrency(estimatedCosts.total)}`"></span>
                                </div>
                            </div>
                            <div class="bg-green-50 border border-green-200 rounded-lg p-3 mt-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm text-green-800">Fabric Required</span>
                                    <span class="text-sm font-semibold text-green-900" x-text="`${totalFabricRequired}m`"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar - Templates & Measurements -->
            <div class="xl:col-span-3 space-y-6">
                
                <!-- Garment Templates -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-vector-square text-purple-600 mr-2"></i>
                        Garment Templates
                    </h3>
                    <div class="space-y-4">
                        <template x-for="template in garmentTemplates" :key="template.id">
                            <div class="border border-gray-200 rounded-xl p-4 cursor-pointer transition-all duration-200 hover:border-purple-500 hover:shadow-md"
                                 :class="{ 'border-purple-500 bg-purple-50': currentTemplate.id === template.id }"
                                 @click="selectTemplate(template)">
                                <div class="flex items-start space-x-3">
                                    <img :src="template.preview_image_url" :alt="template.name" 
                                         class="w-16 h-16 rounded-lg object-cover flex-shrink-0">
                                    <div class="flex-1 min-w-0">
                                        <h4 class="font-semibold text-gray-900" x-text="template.name"></h4>
                                        <p class="text-sm text-gray-600 mt-1" x-text="template.description"></p>
                                        <div class="flex items-center justify-between mt-2">
                                            <span class="text-sm text-purple-600 font-semibold" 
                                                  x-text="`Rp ${formatCurrency(template.base_price)}`"></span>
                                            <span class="text-xs bg-gray-100 px-2 py-1 rounded-full capitalize" 
                                                  x-text="template.type"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Body Measurements -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-ruler-combined text-purple-600 mr-2"></i>
                        Body Measurements
                    </h3>
                    
                    <template x-if="bodyMeasurements.length === 0">
                        <div class="text-center py-8">
                            <i class="fas fa-ruler text-4xl text-gray-300 mb-3"></i>
                            <p class="text-gray-500 mb-4">No measurements saved yet</p>
                            <button class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors duration-200">
                                Add Measurements
                            </button>
                        </div>
                    </template>

                    <template x-if="bodyMeasurements.length > 0">
                        <div class="space-y-3">
                            <select x-model="selectedMeasurementId" class="w-full rounded-lg border border-gray-300 px-3 py-2 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
                                <template x-for="measurement in bodyMeasurements" :key="measurement.id">
                                    <option :value="measurement.id" x-text="measurement.measurement_name + (measurement.is_default ? ' (Default)' : '')"></option>
                                </template>
                            </select>
                            
                            <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                                <template x-if="selectedMeasurement">
                                    <div class="space-y-2 text-sm">
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Height:</span>
                                            <span class="font-medium" x-text="`${selectedMeasurement.height} cm`"></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Chest:</span>
                                            <span class="font-medium" x-text="`${selectedMeasurement.chest_bust} cm`"></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Waist:</span>
                                            <span class="font-medium" x-text="`${selectedMeasurement.waist} cm`"></span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span class="text-gray-600">Arm Length:</span>
                                            <span class="font-medium" x-text="`${selectedMeasurement.arm_length} cm`"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            
                            <button class="w-full px-4 py-2 border border-purple-600 text-purple-600 rounded-lg hover:bg-purple-50 transition-colors duration-200 flex items-center justify-center space-x-2">
                                <i class="fas fa-edit"></i>
                                <span>Edit Measurements</span>
                            </button>
                        </div>
                    </template>
                </div>

                <!-- Popular Fabrics -->
                <div class="bg-white rounded-2xl shadow-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-fire text-orange-500 mr-2"></i>
                        Popular Fabrics
                    </h3>
                    <div class="grid grid-cols-2 gap-3">
                        <template x-for="fabric in popularFabrics" :key="fabric.id">
                            <div class="fabric-item bg-gray-50 rounded-lg p-2 cursor-pointer border border-transparent hover:border-purple-300"
                                 draggable="true"
                                 @dragstart="handleFabricDragStart(fabric.id, $event)"
                                 @dragend="handleFabricDragEnd">
                                <img :src="fabric.main_image_url" :alt="fabric.name" 
                                     class="w-full h-16 object-cover rounded-md mb-2">
                                <div class="text-xs">
                                    <p class="font-medium text-gray-900 truncate" x-text="fabric.name"></p>
                                    <p class="text-purple-600 font-semibold" x-text="`Rp ${formatCurrency(fabric.current_price)}/m`"></p>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Fabrics Gallery Modal -->
    <div x-show="showFabricsModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">
        <div class="bg-white rounded-2xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
            <div class="flex justify-between items-center p-6 border-b border-gray-200">
                <h3 class="text-xl font-semibold text-gray-900">Fabric Library</h3>
                <button @click="showFabricsModal = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="p-6 overflow-y-auto max-h-[calc(90vh-120px)]">
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    <template x-for="fabric in filteredFabrics" :key="fabric.id">
                        <div class="fabric-item bg-white rounded-xl border border-gray-200 p-4 cursor-pointer hover:shadow-lg transition-all duration-300"
                             draggable="true"
                             @dragstart="handleFabricDragStart(fabric.id, $event)"
                             @dragend="handleFabricDragEnd"
                             @click="selectFabric(fabric)">
                            <div class="relative mb-3">
                                <img :src="fabric.main_image_url" :alt="fabric.name" 
                                     class="w-full h-32 object-cover rounded-lg">
                                <div class="absolute top-2 right-2 bg-white bg-opacity-90 rounded-full w-8 h-8 flex items-center justify-center">
                                    <i class="fas fa-grip-vertical text-gray-600"></i>
                                </div>
                                <template x-if="fabric.discount_price">
                                    <div class="absolute top-2 left-2 bg-red-500 text-white text-xs px-2 py-1 rounded-full">
                                        <span x-text="`-${fabric.discount_percentage}%`"></span>
                                    </div>
                                </template>
                            </div>
                            
                            <div class="space-y-2">
                                <h4 class="font-semibold text-gray-900 text-sm" x-text="fabric.name"></h4>
                                <p class="text-xs text-gray-600" x-text="fabric.material_type"></p>
                                <div class="flex items-center justify-between">
                                    <span class="text-purple-600 font-bold text-sm" 
                                          x-text="`Rp ${formatCurrency(fabric.current_price)}/m`"></span>
                                    <span class="text-xs bg-gray-100 px-2 py-1 rounded capitalize" 
                                          x-text="fabric.pattern"></span>
                                </div>
                                <div class="flex items-center text-xs text-gray-500">
                                    <i class="fas fa-store mr-1"></i>
                                    <span x-text="fabric.seller.shop_name" class="truncate"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
                
                <template x-if="filteredFabrics.length === 0">
                    <div class="text-center py-12">
                        <i class="fas fa-fabric-roll text-4xl text-gray-300 mb-3"></i>
                        <p class="text-gray-500">No fabrics found matching your criteria</p>
                        <button @click="clearFilters()" class="mt-3 px-4 py-2 text-purple-600 hover:text-purple-700">
                            Clear filters
                        </button>
                    </div>
                </template>
            </div>
        </div>
    </div>

    <!-- Design Preview Modal -->
    <div x-show="showPreviewModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">
        <div class="bg-white rounded-2xl max-w-2xl w-full">
            <div class="flex justify-between items-center p-6 border-b border-gray-200">
                <h3 class="text-xl font-semibold text-gray-900">Design Preview</h3>
                <button @click="showPreviewModal = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <div class="p-6">
                <div class="text-center mb-6">
                    <img :src="previewImageUrl" alt="Design Preview" class="max-w-full h-64 object-contain mx-auto rounded-lg">
                </div>
                
                <div class="bg-gray-50 rounded-xl p-4 mb-6">
                    <h4 class="font-semibold mb-3">Design Summary</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Template:</span>
                            <span class="font-medium" x-text="currentTemplate.name"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Fabrics Used:</span>
                            <span class="font-medium" x-text="Object.keys(fabricAssignments).length"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Estimated Fabric:</span>
                            <span class="font-medium" x-text="`${totalFabricRequired}m`"></span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Total Cost:</span>
                            <span class="font-semibold text-purple-600" x-text="`Rp ${formatCurrency(estimatedCosts.total)}`"></span>
                        </div>
                    </div>
                </div>
                
                <div class="flex space-x-3">
                    <button @click="showPreviewModal = false" 
                            class="flex-1 px-4 py-3 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors duration-200 flex items-center justify-center space-x-2">
                        <i class="fas fa-edit"></i>
                        <span>Continue Editing</span>
                    </button>
                    <button @click="saveDesign()" 
                            class="flex-1 px-4 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors duration-200 flex items-center justify-center space-x-2">
                        <i class="fas fa-save"></i>
                        <span>Save Design</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Action Button -->
    <div class="fixed bottom-6 right-6 z-30">
        <button @click="showFabricsModal = true" 
                class="bg-purple-600 text-white p-4 rounded-full shadow-lg hover:bg-purple-700 transition-all duration-300 hover:shadow-xl animate-float flex items-center space-x-2">
            <i class="fas fa-plus text-lg"></i>
            <span class="hidden sm:block">More Fabrics</span>
        </button>
    </div>
</div>
@endsection

@push('scripts')
<script>
function designStudio() {
    return {
        // State
        isLoading: false,
        showFabricsModal: false,
        showPreviewModal: false,
        designName: 'My Custom Design',
        designDescription: '',
        specialInstructions: '',
        previewImageUrl: '',
        
        // Data
        garmentTemplates: @json($templates),
        fabrics: @json($fabrics),
        fabricCategories: @json($categories),
        bodyMeasurements: @json($measurements),
        popularFabrics: @json($popularFabrics),
        recentDesigns: @json($recentDesigns),
        
        // Selections
        currentTemplate: @json($templates->first()),
        selectedMeasurementId: @json($measurements->first() ? $measurements->first()->id : null),
        fabricAssignments: {},
        activeCategory: 'all',
        
        // Filters
        filters: {
            material: '',
            minPrice: '',
            maxPrice: '',
            pattern: ''
        },
        
        // UI State
        dragOverPart: null,
        
        // Computed
        get selectedMeasurement() {
            return this.bodyMeasurements.find(m => m.id == this.selectedMeasurementId) || null;
        },
        
        get filteredFabrics() {
            let filtered = this.fabrics;
            
            // Filter by category
            if (this.activeCategory !== 'all') {
                filtered = filtered.filter(f => f.category_id == this.activeCategory);
            }
            
            // Filter by material
            if (this.filters.material) {
                filtered = filtered.filter(f => f.material_type === this.filters.material);
            }
            
            // Filter by price
            if (this.filters.minPrice) {
                filtered = filtered.filter(f => f.current_price >= parseFloat(this.filters.minPrice));
            }
            if (this.filters.maxPrice) {
                filtered = filtered.filter(f => f.current_price <= parseFloat(this.filters.maxPrice));
            }
            
            // Filter by pattern
            if (this.filters.pattern) {
                filtered = filtered.filter(f => f.pattern === this.filters.pattern);
            }
            
            return filtered;
        },
        
        get materialTypes() {
            return [...new Set(this.fabrics.map(f => f.material_type))];
        },
        
        get patternTypes() {
            return [...new Set(this.fabrics.map(f => f.pattern))];
        },
        
        get estimatedCosts() {
            const fabricCost = Object.values(this.fabricAssignments).reduce((total, fabricId) => {
                const fabric = this.fabrics.find(f => f.id == fabricId);
                return total + (fabric ? fabric.current_price * 1.5 : 0); // 1.5m estimate per part
            }, 0);
            
            const tailoringCost = this.currentTemplate ? this.currentTemplate.tailor_fee : 0;
            const serviceFee = this.currentTemplate ? this.currentTemplate.service_fee : 0;
            const total = fabricCost + tailoringCost + serviceFee;
            
            return {
                fabric: fabricCost,
                tailoring: tailoringCost,
                service: serviceFee,
                total: total
            };
        },
        
        get totalFabricRequired() {
            return (Object.keys(this.fabricAssignments).length * 1.5).toFixed(1);
        },

        // Methods
        init() {
            this.initializeCanvas();
            this.loadInitialData();
        },
        
        initializeCanvas() {
            const canvas = new fabric.Canvas('avatarCanvas', {
                width: 400,
                height: 600,
                backgroundColor: '#f8fafc'
            });
            
            this.canvas = canvas;
            this.drawDefaultAvatar();
        },
        
        drawDefaultAvatar() {
            // Clear canvas
            this.canvas.clear();
            
            // Draw basic avatar shape
            const body = new fabric.Rect({
                left: 150,
                top: 200,
                width: 100,
                height: 300,
                fill: '#e5e7eb',
                stroke: '#9ca3af',
                strokeWidth: 1,
                rx: 10,
                ry: 10
            });
            
            const head = new fabric.Circle({
                left: 175,
                top: 150,
                radius: 25,
                fill: '#fbbf24',
                stroke: '#f59e0b',
                strokeWidth: 1
            });
            
            this.canvas.add(body);
            this.canvas.add(head);
            this.canvas.renderAll();
        },
        
        loadInitialData() {
            // Simulate loading data
            this.isLoading = true;
            setTimeout(() => {
                this.isLoading = false;
            }, 1000);
        },
        
        setActiveCategory(categoryId) {
            this.activeCategory = categoryId;
        },
        
        selectTemplate(template) {
            this.currentTemplate = template;
            this.fabricAssignments = {};
            this.drawDefaultAvatar();
            showNotification(`Template "${template.name}" selected`, 'success');
        },
        
        handleFabricDragStart(fabricId, event) {
            event.dataTransfer.setData('text/plain', fabricId);
            event.target.classList.add('dragging');
        },
        
        handleFabricDragEnd(event) {
            document.querySelectorAll('.fabric-item').forEach(item => {
                item.classList.remove('dragging');
            });
        },
        
        handlePartDragOver(part) {
            this.dragOverPart = part;
            event.preventDefault();
        },
        
        handlePartDragLeave(part) {
            if (this.dragOverPart === part) {
                this.dragOverPart = null;
            }
        },
        
        handlePartDrop(part, event) {
            event.preventDefault();
            this.dragOverPart = null;
            
            const fabricId = event.dataTransfer.getData('text/plain');
            this.assignFabricToPart(part, fabricId);
        },
        
        assignFabricToPart(part, fabricId) {
            this.fabricAssignments[part] = fabricId;
            
            // Update canvas with fabric texture
            this.applyFabricToCanvas(part, fabricId);
            
            showNotification(`Fabric applied to ${part}`, 'success');
        },
        
        applyFabricToCanvas(part, fabricId) {
            const fabric = this.fabrics.find(f => f.id == fabricId);
            if (!fabric) return;
            
            // In a real implementation, you would apply the fabric texture to the specific part
            // This is a simplified version
            fabric.Image.fromURL(fabric.texture_image_url, (img) => {
                img.set({
                    left: 100 + Math.random() * 200,
                    top: 100 + Math.random() * 400,
                    scaleX: 0.3,
                    scaleY: 0.3,
                    angle: Math.random() * 10 - 5
                });
                this.canvas.add(img);
                this.canvas.renderAll();
            });
        },
        
        getFabricImage(fabricId) {
            const fabric = this.fabrics.find(f => f.id == fabricId);
            return fabric ? fabric.main_image_url : '';
        },
        
        selectFabric(fabric) {
            showNotification(`Selected ${fabric.name}`, 'info');
        },
        
        generatePreview() {
            if (Object.keys(this.fabricAssignments).length === 0) {
                showNotification('Please assign fabrics to at least one part', 'warning');
                return;
            }
            
            this.isLoading = true;
            
            // Simulate API call to generate preview
            setTimeout(() => {
                this.previewImageUrl = 'https://via.placeholder.com/400x600/8b5cf6/ffffff?text=Design+Preview';
                this.showPreviewModal = true;
                this.isLoading = false;
            }, 1500);
        },
        
        saveDesign() {
            if (!this.designName.trim()) {
                showNotification('Please enter a design name', 'warning');
                return;
            }
            
            this.isLoading = true;
            
            // Simulate API call to save design
            setTimeout(() => {
                showNotification('Design saved successfully!', 'success');
                this.showPreviewModal = false;
                this.isLoading = false;
                
                // Redirect to designs page or show success message
                window.location.href = '{{ route("designs.index") }}';
            }, 2000);
        },
        
        saveDraft() {
            showNotification('Draft saved successfully', 'success');
        },
        
        resetDesign() {
            this.fabricAssignments = {};
            this.drawDefaultAvatar();
            showNotification('Design reset', 'info');
        },
        
        loadDesign(designId) {
            showNotification('Loading design...', 'info');
            // Implementation for loading existing design
        },
        
        clearFilters() {
            this.filters = {
                material: '',
                minPrice: '',
                maxPrice: '',
                pattern: ''
            };
            this.activeCategory = 'all';
        },
        
        formatCurrency(amount) {
            return new Intl.NumberFormat('id-ID').format(amount);
        }
    };
}
</script>
@endpush
