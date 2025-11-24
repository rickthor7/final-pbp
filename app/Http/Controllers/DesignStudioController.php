<?php
// app/Http/Controllers/DesignStudioController.php

namespace App\Http\Controllers;

use App\Models\Fabric;
use App\Models\GarmentTemplate;
use App\Models\CustomDesign;
use App\Models\BodyMeasurement;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Intervention\Image\Facades\Image;

class DesignStudioController extends Controller
{
    // Get Design Studio Initial Data
    public function getStudioData(Request $request)
    {
        try {
            $user = $request->user();
            
            $templates = GarmentTemplate::with(['customDesigns' => function($query) use ($user) {
                $query->where('user_id', $user->id)->where('status', 'completed');
            }])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();

            $fabrics = Fabric::with(['category', 'seller'])
                ->where('is_active', true)
                ->where('stock_meter', '>', 0)
                ->orderBy('is_featured', 'desc')
                ->orderBy('created_at', 'desc')
                ->get();

            $measurements = $user->bodyMeasurements()->orderBy('is_default', 'desc')->get();
            $defaultMeasurement = $user->defaultBodyMeasurement;

            $recentDesigns = CustomDesign::with(['garmentTemplate', 'bodyMeasurement'])
                ->where('user_id', $user->id)
                ->where('status', 'completed')
                ->orderBy('updated_at', 'desc')
                ->limit(5)
                ->get();

            $popularFabrics = Fabric::with(['category', 'seller'])
                ->where('is_active', true)
                ->where('stock_meter', '>', 0)
                ->orderBy('sales_count', 'desc')
                ->orderBy('view_count', 'desc')
                ->limit(8)
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'templates' => $templates,
                    'fabrics' => $fabrics,
                    'measurements' => $measurements,
                    'default_measurement' => $defaultMeasurement,
                    'recent_designs' => $recentDesigns,
                    'popular_fabrics' => $popularFabrics,
                    'user' => $user
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load design studio data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Create New Design
    public function createDesign(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'design_name' => 'required|string|max:255',
            'garment_template_id' => 'required|exists:garment_templates,id',
            'body_measurement_id' => 'nullable|exists:body_measurements,id',
            'fabric_assignments' => 'required|array',
            'custom_measurements' => 'required|array',
            'design_data' => 'required|array',
            'special_instructions' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();
            $template = GarmentTemplate::findOrFail($request->garment_template_id);

            // Generate unique design slug
            $designSlug = $this->generateUniqueDesignSlug($request->design_name);

            // Calculate fabric requirements
            $fabricRequirements = $this->calculateFabricRequirements(
                $request->fabric_assignments,
                $request->custom_measurements,
                $template
            );

            // Calculate costs
            $costs = $this->calculateDesignCosts($fabricRequirements, $template);

            // Generate preview image
            $previewImage = $this->generateDesignPreview(
                $template,
                $request->fabric_assignments,
                $request->design_data
            );

            $design = CustomDesign::create([
                'user_id' => $user->id,
                'garment_template_id' => $template->id,
                'body_measurement_id' => $request->body_measurement_id,
                'design_name' => $request->design_name,
                'design_slug' => $designSlug,
                'description' => $request->description,
                'special_instructions' => $request->special_instructions,
                'fabric_assignments' => $request->fabric_assignments,
                'custom_measurements' => $request->custom_measurements,
                'design_data' => $request->design_data,
                'fabric_requirements' => $fabricRequirements,
                'preview_image' => $previewImage,
                'fabric_cost' => $costs['fabric_cost'],
                'tailoring_cost' => $costs['tailoring_cost'],
                'total_estimated_cost' => $costs['total_cost'],
                'status' => 'completed',
                'is_public' => $request->is_public ?? false,
            ]);

            // Increment template usage count
            $template->incrementUsageCount();

            // Increment fabric view counts
            $this->incrementFabricViews($request->fabric_assignments);

            return response()->json([
                'success' => true,
                'message' => 'Design created successfully',
                'design' => $design->load(['garmentTemplate', 'bodyMeasurement']),
                'preview_url' => asset('storage/'.$previewImage)
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Design creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Generate Design Preview
    public function generatePreview(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'garment_template_id' => 'required|exists:garment_templates,id',
            'fabric_assignments' => 'required|array',
            'design_data' => 'required|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $template = GarmentTemplate::findOrFail($request->garment_template_id);
            
            $previewImage = $this->generateDesignPreview(
                $template,
                $request->fabric_assignments,
                $request->design_data
            );

            // Calculate fabric requirements for preview
            $fabricRequirements = $this->calculateFabricRequirements(
                $request->fabric_assignments,
                $request->custom_measurements ?? [],
                $template
            );

            $costs = $this->calculateDesignCosts($fabricRequirements, $template);

            return response()->json([
                'success' => true,
                'preview_url' => asset('storage/'.$previewImage),
                'fabric_requirements' => $fabricRequirements,
                'estimated_costs' => $costs,
                'completion_time' => $template->completion_time_days
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Preview generation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get User Designs
    public function getUserDesigns(Request $request)
    {
        try {
            $user = $request->user();
            $status = $request->get('status', 'all');
            $perPage = $request->get('per_page', 12);

            $query = CustomDesign::with(['garmentTemplate', 'bodyMeasurement', 'order'])
                ->where('user_id', $user->id);

            if ($status !== 'all') {
                $query->where('status', $status);
            }

            $designs = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'designs' => $designs,
                'stats' => [
                    'total' => CustomDesign::where('user_id', $user->id)->count(),
                    'completed' => CustomDesign::where('user_id', $user->id)->where('status', 'completed')->count(),
                    'ordered' => CustomDesign::where('user_id', $user->id)->where('status', 'ordered')->count(),
                    'draft' => CustomDesign::where('user_id', $user->id)->where('status', 'draft')->count(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch designs',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get Single Design
    public function getDesign($id)
    {
        try {
            $design = CustomDesign::with([
                'garmentTemplate',
                'bodyMeasurement',
                'user',
                'order.tailor',
                'order.orderFabrics.fabric',
                'order.orderFabrics.fabricSeller'
            ])->findOrFail($id);

            // Check if user owns the design or design is public
            if ($design->user_id !== Auth::id() && !$design->is_public) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied'
                ], 403);
            }

            // Increment view count if viewing someone else's public design
            if ($design->user_id !== Auth::id() && $design->is_public) {
                $design->incrementViewCount();
            }

            // Get assigned fabrics with details
            $assignedFabrics = [];
            foreach ($design->fabric_assignments as $part => $fabricId) {
                $fabric = Fabric::with('seller')->find($fabricId);
                if ($fabric) {
                    $assignedFabrics[$part] = $fabric;
                }
            }

            return response()->json([
                'success' => true,
                'design' => $design,
                'assigned_fabrics' => $assignedFabrics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch design',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update Design
    public function updateDesign(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'design_name' => 'sometimes|required|string|max:255',
            'fabric_assignments' => 'sometimes|required|array',
            'custom_measurements' => 'sometimes|required|array',
            'design_data' => 'sometimes|required|array',
            'special_instructions' => 'nullable|string',
            'is_public' => 'boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $design = CustomDesign::where('user_id', Auth::id())->findOrFail($id);

            $updateData = [];
            if ($request->has('design_name')) {
                $updateData['design_name'] = $request->design_name;
            }
            if ($request->has('special_instructions')) {
                $updateData['special_instructions'] = $request->special_instructions;
            }
            if ($request->has('is_public')) {
                $updateData['is_public'] = $request->is_public;
            }

            // If fabric assignments or measurements changed, recalculate
            if ($request->has('fabric_assignments') || $request->has('custom_measurements')) {
                $fabricAssignments = $request->fabric_assignments ?? $design->fabric_assignments;
                $customMeasurements = $request->custom_measurements ?? $design->custom_measurements;

                $fabricRequirements = $this->calculateFabricRequirements(
                    $fabricAssignments,
                    $customMeasurements,
                    $design->garmentTemplate
                );

                $costs = $this->calculateDesignCosts($fabricRequirements, $design->garmentTemplate);

                $updateData = array_merge($updateData, [
                    'fabric_assignments' => $fabricAssignments,
                    'custom_measurements' => $customMeasurements,
                    'fabric_requirements' => $fabricRequirements,
                    'fabric_cost' => $costs['fabric_cost'],
                    'tailoring_cost' => $costs['tailoring_cost'],
                    'total_estimated_cost' => $costs['total_cost'],
                ]);

                // Regenerate preview if design data changed
                if ($request->has('design_data')) {
                    $previewImage = $this->generateDesignPreview(
                        $design->garmentTemplate,
                        $fabricAssignments,
                        $request->design_data
                    );
                    $updateData['preview_image'] = $previewImage;
                    $updateData['design_data'] = $request->design_data;
                }
            }

            $design->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Design updated successfully',
                'design' => $design->fresh()
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Design update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete Design
    public function deleteDesign($id)
    {
        try {
            $design = CustomDesign::where('user_id', Auth::id())->findOrFail($id);

            // Check if design has an order
            if ($design->order) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot delete design that has an associated order'
                ], 422);
            }

            // Delete preview image
            if ($design->preview_image) {
                Storage::delete('public/' . $design->preview_image);
            }

            $design->delete();

            return response()->json([
                'success' => true,
                'message' => 'Design deleted successfully'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Design deletion failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Clone Design
    public function cloneDesign($id)
    {
        try {
            $originalDesign = CustomDesign::where('user_id', Auth::id())->findOrFail($id);

            $newDesign = $originalDesign->replicate();
            $newDesign->design_name = $originalDesign->design_name . ' (Copy)';
            $newDesign->design_slug = $this->generateUniqueDesignSlug($newDesign->design_name);
            $newDesign->status = 'completed';
            $newDesign->view_count = 0;
            $newDesign->like_count = 0;
            $newDesign->clone_count = 0;

            // Generate new preview image
            $newPreviewImage = $this->generateDesignPreview(
                $originalDesign->garmentTemplate,
                $originalDesign->fabric_assignments,
                $originalDesign->design_data
            );
            $newDesign->preview_image = $newPreviewImage;

            $newDesign->save();

            // Increment clone count of original design
            $originalDesign->incrementCloneCount();

            return response()->json([
                'success' => true,
                'message' => 'Design cloned successfully',
                'design' => $newDesign
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Design cloning failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Get Available Tailors for Design
    public function getAvailableTailors(Request $request, $designId)
    {
        try {
            $design = CustomDesign::where('user_id', Auth::id())->findOrFail($designId);

            $tailors = User::tailors()
                ->verified()
                ->active()
                ->withCount(['tailorOrders as active_orders_count' => function($query) {
                    $query->whereIn('status', ['in_production', 'quality_check']);
                }])
                ->orderBy('rating', 'desc')
                ->orderBy('active_orders_count')
                ->get()
                ->map(function($tailor) use ($design) {
                    $tailor->estimated_completion_time = $this->calculateEstimatedCompletion(
                        $tailor,
                        $design->garmentTemplate
                    );
                    return $tailor;
                });

            return response()->json([
                'success' => true,
                'tailors' => $tailors
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tailors',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Private helper methods
    private function generateUniqueDesignSlug($designName)
    {
        $baseSlug = Str::slug($designName);
        $slug = $baseSlug;
        $counter = 1;

        while (CustomDesign::where('design_slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    private function calculateFabricRequirements($fabricAssignments, $customMeasurements, $template)
    {
        $requirements = [];
        $templateRequirements = $template->fabric_requirements;

        foreach ($fabricAssignments as $part => $fabricId) {
            if (isset($templateRequirements[$part])) {
                $baseRequirement = $templateRequirements[$part];
                
                // Adjust based on custom measurements
                $adjustmentFactor = $this->calculateMeasurementAdjustment($part, $customMeasurements, $template);
                $adjustedRequirement = $baseRequirement * $adjustmentFactor;
                
                $requirements[$part] = [
                    'fabric_id' => $fabricId,
                    'base_requirement' => $baseRequirement,
                    'adjusted_requirement' => round($adjustedRequirement, 3),
                    'adjustment_factor' => $adjustmentFactor
                ];
            }
        }

        return $requirements;
    }

    private function calculateMeasurementAdjustment($part, $customMeasurements, $template)
    {
        $defaultMeasurements = $template->default_measurements;
        
        if (!isset($defaultMeasurements[$part]) || !isset($customMeasurements[$part])) {
            return 1.0;
        }
        
        $default = $defaultMeasurements[$part];
        $custom = $customMeasurements[$part];
        
        // Simple adjustment: custom / default with bounds
        return max(0.8, min(1.5, $custom / $default));
    }

    private function calculateDesignCosts($fabricRequirements, $template)
    {
        $fabricCost = 0;

        foreach ($fabricRequirements as $part => $requirement) {
            $fabric = Fabric::find($requirement['fabric_id']);
            if ($fabric) {
                $fabricCost += $requirement['adjusted_requirement'] * $fabric->current_price;
            }
        }
        
        $tailoringCost = $template->tailor_fee;
        $serviceFee = $template->service_fee;
        $totalCost = $fabricCost + $tailoringCost + $serviceFee;

        return [
            'fabric_cost' => $fabricCost,
            'tailoring_cost' => $tailoringCost,
            'service_fee' => $serviceFee,
            'total_cost' => $totalCost
        ];
    }

    private function generateDesignPreview($template, $fabricAssignments, $designData)
    {
        // Create a canvas for the design preview
        $canvas = Image::canvas(400, 600, '#f8fafc');
        
        // Load template base image
        if ($template->template_image) {
            $baseImage = Image::make(storage_path('app/public/' . $template->template_image));
            $canvas->insert($baseImage, 'center');
        }

        // Apply fabric textures to parts
        foreach ($fabricAssignments as $part => $fabricId) {
            $fabric = Fabric::find($fabricId);
            if ($fabric && $fabric->texture_image) {
                $texture = Image::make(storage_path('app/public/' . $fabric->texture_image));
                
                // Resize texture based on design data
                if (isset($designData['parts'][$part])) {
                    $partData = $designData['parts'][$part];
                    $texture->resize($partData['width'], $partData['height']);
                    $canvas->insert($texture, 'top-left', $partData['x'], $partData['y']);
                }
            }
        }

        // Add design name watermark
        $canvas->text('TailorCraft Design', 200, 580, function($font) {
            $font->file(public_path('fonts/Roboto-Regular.ttf'));
            $font->size(12);
            $font->color('#6b7280');
            $font->align('center');
        });

        $filename = 'design-previews/' . Str::uuid() . '.png';
        Storage::put('public/' . $filename, $canvas->encode('png'));

        return $filename;
    }

    private function incrementFabricViews($fabricAssignments)
    {
        $fabricIds = array_values($fabricAssignments);
        Fabric::whereIn('id', $fabricIds)->increment('view_count');
    }

    private function calculateEstimatedCompletion($tailor, $template)
    {
        $baseTime = $template->completion_time_days;
        $workloadFactor = min(2.0, 1 + ($tailor->active_orders_count / 5));
        
        return ceil($baseTime * $workloadFactor);
    }
}
