<?php

namespace App\Http\Controllers\Api\V1\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Account\StoreCustomerAddressRequest;
use App\Http\Requests\Api\V1\Account\UpdateCustomerAddressRequest;
use App\Http\Resources\Api\V1\CustomerAddressResource;
use App\Models\CustomerAddress;
use App\Services\CustomerAddressService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    use ApiResponse;

    public function __construct(private CustomerAddressService $service) {}

    public function index(Request $r): JsonResponse
    {
        return $this->successResponse(CustomerAddressResource::collection($r->user()->addresses()->orderByDesc('is_default')->latest('updated_at')->get()));
    }

    public function store(StoreCustomerAddressRequest $r): JsonResponse
    {
        return $this->successResponse(new CustomerAddressResource($this->service->create($r->user(), $r->validated())), 'Address created.', 201);
    }

    public function show(Request $r, CustomerAddress $address): JsonResponse
    {
        $this->own($r, $address);

        return $this->successResponse(new CustomerAddressResource($address));
    }

    public function update(UpdateCustomerAddressRequest $r, CustomerAddress $address): JsonResponse
    {
        $this->own($r, $address);

        return $this->successResponse(new CustomerAddressResource($this->service->update($r->user(), $address, $r->validated())), 'Address updated.');
    }

    public function destroy(Request $r, CustomerAddress $address): JsonResponse
    {
        $this->own($r, $address);
        $this->service->delete($r->user(), $address);

        return $this->successResponse(null, 'Address deleted.');
    }

    public function setDefault(Request $r, CustomerAddress $address): JsonResponse
    {
        $this->own($r, $address);

        return $this->successResponse(new CustomerAddressResource($this->service->setDefault($r->user(), $address)), 'Default address updated.');
    }

    private function own(Request $r, CustomerAddress $address): void
    {
        abort_unless($address->user_id === $r->user()->id, 404);
    }
}
