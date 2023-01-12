<?php

namespace App\Services\Asaas;

use App\Builders\Asaas\PaymentDatabaseBuilder;
use App\Builders\Asaas\PaymentGatewayBuilder;
use App\Interfaces\Repositories\PaymentRepositoryInterface;
use App\Models\Payment;
use App\Models\PaymentGatewayData;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class PaymentProcessService
{
    private $paymentGatewayBuild;
    private $paymentDatabaseBuild;
    private $gatewayReturn;
    private $paymentId;

    public function __construct(
        private PaymentRepositoryInterface $paymentRepository
    ) {
    }

    public function start(Request $request)
    {
        try {
            $this->build($request);
            $this->execute();

            return 'ok tudo certo'; //TODO - Criar response builder
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    private function build(Request $request): void
    {
        $this->paymentDatabaseBuild = new PaymentDatabaseBuilder(
            $request
        );
        $this->paymentGatewayBuild = new PaymentGatewayBuilder(
            $request
        );
    }

    private function execute(): void
    {
        try {
            $payment = $this->save();
            $this->sendToGateway();
            $payment->status_id = 2;
            $this->update($payment);
            $this->savePaymentGatewayData($this->gatewayReturn);
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    private function sendToGateway(): void
    {
        try {
            $this->paymentGatewayBuild
                ->payload['externalReference'] = $this->paymentId;

            $client = new Client([
                'base_uri' => env('GATEWAY_URL')
            ]);
            $response = $client->post(
                '/api/v3/payments',
                [
                    'headers' =>
                    [
                        'Content-Type'   => "application/json",
                        'access_token' => env('GATEWAY_API_KEY')
                    ],
                    "json" => $this->paymentGatewayBuild->payload
                ]
            );

            $this->gatewayReturn =  $response->getBody()->getContents();
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    private function save(): Payment
    {
        try {
            $save = Payment::create(
                $this->paymentDatabaseBuild->payload
            );
            $this->paymentId = $save->id;

            return $save;
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    private function update(Payment $payment): void
    {
        try {
            $payment->save();
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    private function savePaymentGatewayData(
        $gatewayReturn
    ): void {
        try {
            $this->paymentGatewayBuild->buildToDatabase($gatewayReturn);

            PaymentGatewayData::create(
                $this->paymentGatewayBuild->payloadDatabase
            );
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public function list($request)
    {
        try {
            $list = Payment::where(
                'user_uuid',
                $request->user->uuid
            );

            if ($request->paginate) {
                $list->paginate($request->paginate);
            }

            return $list->get();
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }
}
