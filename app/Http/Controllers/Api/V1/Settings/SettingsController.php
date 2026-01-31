<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Settings;

use App\Http\Controllers\Controller;
use App\Models\ClientStatus;
use App\Models\Source;
use App\Models\Behavior;
use App\Models\InvalidReason;
use App\Models\Region;
use App\Models\City;
use App\Models\Tag;
use App\Models\InvoiceTag;
use App\Models\CommentType;
use App\Models\Team;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    use \App\Traits\ApiResponse;

    // ===================== CLIENT STATUSES =====================
    
    public function getStatuses()
    {
        $statuses = ClientStatus::orderBy('order')->get();
        return $this->successResponse($statuses);
    }

    public function storeStatus(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:7',
            'order' => 'nullable|integer',
            'weight' => 'nullable|integer', // Added weight
            'is_default' => 'boolean',
        ]);

        if ($validated['is_default'] ?? false) {
            ClientStatus::where('is_default', true)->update(['is_default' => false]);
        }

        $status = ClientStatus::create($validated);

        return $this->createdResponse($status, 'تم إنشاء الحالة بنجاح');
    }

    public function updateStatus(Request $request, int $id)
    {
        $status = ClientStatus::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'color' => 'sometimes|required|string|max:7',
            'order' => 'nullable|integer',
            'weight' => 'nullable|integer', // Added weight
            'is_default' => 'boolean',
        ]);

        if ($validated['is_default'] ?? false) {
            ClientStatus::where('is_default', true)->update(['is_default' => false]);
        }

        $status->update($validated);

        return $this->successResponse($status, 'تم تحديث الحالة بنجاح');
    }

    public function deleteStatus(int $id)
    {
        $status = ClientStatus::findOrFail($id);
        
        // Check if status is in use
        $clientsCount = DB::table('clients')->where('status_id', $id)->count();
        if ($clientsCount > 0) {
            return $this->errorResponse("لا يمكن حذف هذه الحالة لأنها مستخدمة من قبل {$clientsCount} عميل");
        }

        $status->delete();

        return $this->deletedResponse('تم حذف الحالة بنجاح');
    }

    // ===================== SOURCES =====================
    
    public function getSources()
    {
        return $this->successResponse(Source::get());
    }

    public function storeSource(Request $request)
    {
        $validated = $request->validate(['name' => 'required|string|max:255', 'is_active' => 'boolean']);
        return $this->createdResponse(Source::create($validated), 'تم إنشاء المصدر بنجاح');
    }

    public function updateSource(Request $request, int $id)
    {
        $source = Source::findOrFail($id);
        $source->update($request->validate(['name' => 'sometimes|required|string|max:255', 'is_active' => 'boolean']));
        return $this->successResponse($source, 'تم تحديث المصدر بنجاح');
    }

    public function deleteSource(int $id)
    {
        Source::findOrFail($id)->delete();
        return $this->deletedResponse('تم حذف المصدر بنجاح');
    }

    // ===================== BEHAVIORS =====================
    
    public function getBehaviors()
    {
        return $this->successResponse(Behavior::get());
    }

    public function storeBehavior(Request $request)
    {
        $behavior = Behavior::create($request->validate(['name' => 'required|string|max:255', 'color' => 'nullable|string|max:7']));
        return $this->createdResponse($behavior);
    }

    public function updateBehavior(Request $request, int $id)
    {
        $behavior = Behavior::findOrFail($id);
        $behavior->update($request->validate(['name' => 'sometimes|required|string|max:255', 'color' => 'nullable|string|max:7']));
        return $this->successResponse($behavior);
    }

    public function deleteBehavior(int $id)
    {
        Behavior::findOrFail($id)->delete();
        return $this->deletedResponse();
    }

    // ===================== INVALID REASONS =====================
    
    public function getInvalidReasons()
    {
        return $this->successResponse(InvalidReason::get());
    }

    public function storeInvalidReason(Request $request)
    {
        $reason = InvalidReason::create($request->validate(['name' => 'required|string|max:255']));
        return $this->createdResponse($reason);
    }

    public function updateInvalidReason(Request $request, int $id)
    {
        $reason = InvalidReason::findOrFail($id);
        $reason->update($request->validate(['name' => 'sometimes|required|string|max:255']));
        return $this->successResponse($reason);
    }

    public function deleteInvalidReason(int $id)
    {
        InvalidReason::findOrFail($id)->delete();
        return $this->deletedResponse();
    }

    // ===================== REGIONS =====================
    
    public function getRegions()
    {
        return $this->successResponse(Region::get());
    }

    public function storeRegion(Request $request)
    {
        $region = Region::create($request->validate(['name' => 'required|string|max:255']));
        return $this->createdResponse($region);
    }

    public function updateRegion(Request $request, int $id)
    {
        $region = Region::findOrFail($id);
        $region->update($request->validate(['name' => 'sometimes|required|string|max:255']));
        return $this->successResponse($region);
    }

    public function deleteRegion(int $id)
    {
        Region::findOrFail($id)->delete();
        return $this->deletedResponse();
    }

    // ===================== CITIES =====================
    
    public function getCities(Request $request)
    {
        $query = City::query();
        if ($request->has('region_id')) $query->where('region_id', $request->region_id);
        return $this->successResponse($query->get());
    }

    public function storeCity(Request $request)
    {
        $city = City::create($request->validate(['region_id' => 'required|exists:regions,id', 'name' => 'required|string|max:255']));
        return $this->createdResponse($city);
    }

    public function updateCity(Request $request, int $id)
    {
        $city = City::findOrFail($id);
        $city->update($request->validate(['region_id' => 'sometimes|required|exists:regions,id', 'name' => 'sometimes|required|string|max:255']));
        return $this->successResponse($city);
    }

    public function deleteCity(int $id)
    {
        City::findOrFail($id)->delete();
        return $this->deletedResponse();
    }

    // ===================== TAGS =====================
    
    public function getTags()
    {
        return $this->successResponse(Tag::get());
    }

    public function storeTag(Request $request)
    {
        $tag = Tag::create($request->validate(['name' => 'required|string|max:255', 'color' => 'nullable|string|max:7']));
        return $this->createdResponse($tag);
    }

    public function updateTag(Request $request, int $id)
    {
        $tag = Tag::findOrFail($id);
        $tag->update($request->validate(['name' => 'sometimes|required|string|max:255', 'color' => 'nullable|string|max:7']));
        return $this->successResponse($tag);
    }

    public function deleteTag(int $id)
    {
        Tag::findOrFail($id)->delete();
        return $this->deletedResponse();
    }

    // ===================== PRODUCTS =====================
    
    public function getProducts()
    {
        return $this->successResponse(Product::get());
    }

    public function storeProduct(Request $request)
    {
        $product = Product::create($request->validate(['name' => 'required|string|max:255', 'price' => 'nullable|numeric', 'is_active' => 'boolean']));
        return $this->createdResponse($product);
    }

    public function updateProduct(Request $request, int $id)
    {
        $product = Product::findOrFail($id);
        $product->update($request->validate(['name' => 'sometimes|required|string|max:255', 'price' => 'nullable|numeric', 'is_active' => 'boolean']));
        return $this->successResponse($product);
    }

    public function deleteProduct(int $id)
    {
        Product::findOrFail($id)->delete();
        return $this->deletedResponse();
    }

    // ===================== INVOICE TAGS =====================
    
    public function getInvoiceTags()
    {
        return $this->successResponse(InvoiceTag::get());
    }

    public function storeInvoiceTag(Request $request)
    {
        $tag = InvoiceTag::create($request->validate(['name' => 'required|string|max:255', 'color' => 'nullable|string|max:7']));
        return $this->createdResponse($tag);
    }

    public function updateInvoiceTag(Request $request, int $id)
    {
        $tag = InvoiceTag::findOrFail($id);
        $tag->update($request->validate(['name' => 'sometimes|required|string|max:255', 'color' => 'nullable|string|max:7']));
        return $this->successResponse($tag);
    }

    public function deleteInvoiceTag(int $id)
    {
        InvoiceTag::findOrFail($id)->delete();
        return $this->deletedResponse();
    }

    // ===================== COMMENT TYPES =====================

    public function getCommentTypes()
    {
        return $this->successResponse(CommentType::get());
    }

    public function storeCommentType(Request $request)
    {
        $type = CommentType::create($request->validate(['name' => 'required|string|max:255', 'icon' => 'nullable|string|max:255', 'color' => 'nullable|string|max:7']));
        return $this->createdResponse($type);
    }

    public function updateCommentType(Request $request, int $id)
    {
        $type = CommentType::findOrFail($id);
        $type->update($request->validate(['name' => 'sometimes|required|string|max:255', 'icon' => 'nullable|string|max:255', 'color' => 'nullable|string|max:7']));
        return $this->successResponse($type);
    }

    public function deleteCommentType(int $id)
    {
        CommentType::findOrFail($id)->delete();
        return $this->deletedResponse();
    }

    // ===================== TEAMS =====================
    
    public function getTeams()
    {
        return $this->successResponse(Team::with('roles')->get());
    }

    public function storeTeam(Request $request)
    {
        $team = Team::create($request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]));
        return $this->createdResponse($team);
    }

    public function updateTeam(Request $request, int $id)
    {
        $team = Team::findOrFail($id);
        $team->update($request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean'
        ]));
        return $this->successResponse($team);
    }

    public function deleteTeam(int $id)
    {
        $team = Team::findOrFail($id);
        // Check if team has users
        if (DB::table('users')->where('team_id', $id)->exists()) {
            return $this->errorResponse("لا يمكن حذف الفريق لوجود موظفين مرتبسين به");
        }
        $team->delete();
        return $this->deletedResponse();
    }

    // ===================== ROLES =====================
    
    public function getRoles(Request $request)
    {
        $query = Role::query()->with('permissions:id,name,display_name');
        if ($request->has('team_id')) $query->where('team_id', $request->team_id);
        return $this->successResponse($query->get());
    }

    public function storeRole(Request $request)
    {
        $role = Role::create($request->validate([
            'team_id' => 'required|exists:teams,id',
            'name' => 'required|string|max:255',
            'is_default' => 'boolean'
        ]));
        
        // Sync permissions if provided
        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return $this->createdResponse($role->load('permissions'));
    }

    public function updateRole(Request $request, int $id)
    {
        $role = Role::findOrFail($id);
        $role->update($request->validate([
            'team_id' => 'sometimes|required|exists:teams,id',
            'name' => 'sometimes|required|string|max:255',
            'is_default' => 'boolean'
        ]));

        if ($request->has('permissions')) {
            $role->permissions()->sync($request->permissions);
        }

        return $this->successResponse($role->load('permissions'));
    }

    public function deleteRole(int $id)
    {
        $role = Role::findOrFail($id);
        if (DB::table('users')->where('role_id', $id)->exists()) {
            return $this->errorResponse("لا يمكن حذف الدور لوجود موظفين مرتبطين به");
        }
        $role->delete();
        return $this->deletedResponse();
    }

    // ===================== PERMISSIONS =====================

    public function getPermissions()
    {
        return $this->successResponse(Permission::get(['id', 'name', 'display_name', 'group']));
    }
}
