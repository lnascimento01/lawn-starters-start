<?php

namespace App\Builders\Asaas;

use App\Builders\BaseBuilder;
use App\Models\CardToken;
use App\Models\Customer;
use App\Models\PaymentMethod;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Ramsey\Uuid\Uuid;

class PaymentGatewayBuilder extends BaseBuilder
{
    private $validations;
    public $payload;
    public $payloadDatabase;

    public function __construct(Request $request)
    {
        parent::__construct($request);
    }

    protected function setupValidations(Request $request = null): void
    {
        $this->validations = [
            'dueDate' => 'required|max:10',
            'value' =>  'required|max:15',
            'description' =>  'required|string|max:255',
            'dueDate' =>  'required|date_format:Y-m-d|after_or_equal:today',
            'creditCardId' => 'required|int|exists:card_token,id',
            'discount' => 'max:15',
            'fine' => 'max:15',
            'interest' => 'string',
            'postalService' => 'string',
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
            'customer' => $this->getCustomerId(
                $request->user->uuid
            ),
            'billingType' => $this->getPaymentMethod(
                $request->billingTypeId
            )->gateway_alias,
            'dueDate' => $request->dueDate,
            'value' => $request->value,
            'description' => $request->description,
            'externalReference' => $request->externalReference,
            'creditCardId' => $this->getCreditCardToken(
                $request->creditCardId
            )->token,
            'discount' => $request->discount,
            'fine' => $request->fine,
            'interest' => $request->interest,
            'postalService' => $request->postalService
        ];
    }

    protected function getPaymentMethod(int $billingTypeId): PaymentMethod
    {
        return PaymentMethod::find($billingTypeId);
    }

    protected function getCreditCardToken(int $creditCardId): CardToken
    {
        return CardToken::find($creditCardId);
    }

    public function buildToDatabase($json): void
    {
        $gatewayResponse = json_decode($json);
        $this->payloadDatabase = [
            'payment_id' => $this->payload['externalReference'],
            'gateway_payment_id' => $gatewayResponse->id,
            'due_date' => $this->payload['dueDate'],
            'billing_type' => $this->payload['billingType'],
            'transaction_receipt_url' => $gatewayResponse->transactionReceiptUrl,
            'gateway_response' => $json,
            'net_value' => $gatewayResponse->netValue,
            'status' => $gatewayResponse->status
        ];
    }
    
    protected function getCustomerId(Uuid $uuid)
    {
        return Customer::where('uuid', $uuid)->first()->customer_id;
    }
}
