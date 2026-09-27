<?php

namespace App\Http\Controllers\Api;

use App\Actions\ReceiveLead;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Resources\LeadResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class LeadIntakeController extends Controller
{
    public function store(StoreLeadRequest $request, ReceiveLead $receiveLead): JsonResponse
    {
        $lead = $receiveLead->handle(
            $request->attributes->get('leadSource'),
            $request->validated(),
            (string) $request->ip(),
        );

        return LeadResource::make($lead)
            ->response()
            ->setStatusCode($lead->wasRecentlyCreated ? Response::HTTP_CREATED : Response::HTTP_OK);
    }
}
