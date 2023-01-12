<?php

namespace App\Builders\Asaas;

use App\Builders\BaseBuilder;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class PaymentDatabaseBuilder extends BaseBuilder
{
    private $validations;
    public $payload;
    public $paymentStatus = 1;
    public $gatewayId = 1;

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    protected function setupValidations(Request $request = null): void
    {
        $this->validations = [
            'billingTypeId' => 'required|int|exists:payment_methods,id|max:3',
            'description' =>  'required|string|max:255',
            'value' =>  'required|max:15'
        ];
    }

    protected function validation(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            $this->validations
        );
        
        if ($validator->fails()) {
            throw new Exception(
                json_encode(
                    $validator->errors()->all()
                )
            );
        }
    }

    protected function build(Request $request): void
    {
        $this->payload = [
            'user_uuid' => $request->user->id,
            'payment_method_id' => $request->billingTypeId,
            'description' => $request->description,
            'value' => $request->value,
            'gateway_id' => $this->gatewayId,
            'status_id' => $this->paymentStatus
        ];
    }
}
