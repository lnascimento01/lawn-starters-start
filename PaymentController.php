<?php

namespace App\Http\Controllers\Asaas;

use App\Http\Controllers\Controller;
use App\Services\Asaas\PaymentProcessService;
use App\Services\Asaas\RefundProcessService;
use App\Services\Asaas\RemoveProcessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
  public function __construct(
    private PaymentProcessService $paymentsService,
    private RefundProcessService $refundProcessService,
    private RemoveProcessService $removeProcessService
  ) {
  }

  public function charge(Request $request): JsonResponse
  {
    $response = $this->paymentsService->start($request);

    return response()->json($response, 200);
  }

  public function refund(Request $request): JsonResponse
  {
    $response = $this->refundProcessService->start($request);

    return response()->json($response, 200);
  }

  public function list(Request $request): JsonResponse
  {
    $response = $this->paymentsService->list($request);

    return response()->json($response, 200);
  }

  public function remove(Request $request): JsonResponse
  {
    $response = $this->removeProcessService->start($request);

    return response()->json($response, 200);
  }
}
