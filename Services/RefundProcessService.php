<?php

namespace App\Services\Asaas;

use App\Builders\Asaas\RefundGatewayBuilder;
use App\Models\PaymentRefund;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class RefundProcessService
{
    private $refundGatewayBuild;
    private $gatewayReturn;

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
        $this->refundGatewayBuild = new RefundGatewayBuilder($request);
    }

    private function execute(): void
    {
        try {
            $this->sendToGateway();
            $this->checkRefundSuccess();
            $this->refundGatewayBuild->buildToDatabase(
                [
                    'statusGateway' => 'REFUNDED',
                    'statusDatabase' => 5,
                    'gateway' => $this->gatewayReturn
                ]
            );
            $this->save();
            $this->updatePaymentGatewayData();
            $this->updatePayment();
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    private function sendToGateway(): void
    {
        try {
            $gatewayPaymentId = $this->refundGatewayBuild
                ->payload['gateway_payment_id'];

            $client = new Client([
                'base_uri' => env('GATEWAY_URL')
            ]);
            $response = $client->post(
                '/api/v3/payments/' . $gatewayPaymentId . '/refund',
                [
                    'headers' =>
                    [
                        'Content-Type'   => "application/json",
                        'access_token' => env('GATEWAY_API_KEY')
                    ],
                    "json" => $this->refundGatewayBuild->payload['json']
                ]
            );

            $this->gatewayReturn =  $response->getBody()->getContents();
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    private function updatePayment(): void
    {
        try {
            $this->refundGatewayBuild->payloadDatabase->save();
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    private function updatePaymentGatewayData(): void
    {
        try {
            $this->refundGatewayBuild->payloadDatabase->save();
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public function checkRefundSuccess(): void
    {
        throw_if(
            $this->gatewayReturn == "400",
            'Erro ao gerar estorno no gateway'
        );
    }

    public function save()
    {
        try {
            PaymentRefund::create(
                $this->refundGatewayBuild->payloadRefundData
            );
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }
}
