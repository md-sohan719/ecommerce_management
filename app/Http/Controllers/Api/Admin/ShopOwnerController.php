<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShopOwnerRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class ShopOwnerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $search = trim((string) $request->query('search'));

        $owners = User::shopOwners()
            ->with('shops')
            ->withCount('shops')
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->latest('id')
            ->paginate($this->perPage($request));

        return UserResource::collection($owners);
    }

    /**
     * Lightweight list of all shop owners for <select> inputs.
     */
    public function options(): JsonResponse
    {
        return response()->json([
            'data' => User::shopOwners()->orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function store(ShopOwnerRequest $request): JsonResponse
    {
        $owner = User::create([
            ...$request->validated(),
            'role' => User::ROLE_SHOP_OWNER,
        ]);

        return UserResource::make($owner->load('shops')->loadCount('shops'))
            ->response()
            ->setStatusCode(201);
    }

    public function show(User $owner): UserResource
    {
        $this->ensureShopOwner($owner);

        return UserResource::make($owner->load('shops')->loadCount('shops'));
    }

    public function update(ShopOwnerRequest $request, User $owner): UserResource
    {
        $this->ensureShopOwner($owner);

        $data = $request->validated();

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $owner->update($data);

        return UserResource::make($owner->load('shops')->loadCount('shops'));
    }

    public function destroy(User $owner): Response
    {
        $this->ensureShopOwner($owner);

        // Their shops stay (owner set to NULL) so the admin can reassign them.
        $owner->tokens()->delete();
        $owner->delete();

        return response()->noContent();
    }

    private function ensureShopOwner(User $user): void
    {
        abort_unless($user->isShopOwner(), 404);
    }
}
