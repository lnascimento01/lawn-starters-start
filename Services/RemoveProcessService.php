<?php

namespace App\Services\Asaas;

use App\Builders\Asaas\RemoveGatewayBuilder;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Http\Request;

class RemoveProcessService
{
    private $removeGatewayBuild;
    private $gatewayReturn;

    public function start(Request $request)
    {
        try {
            $this->build($request);
            $this->execute();

            return 'ok tudo certo'; //Todo Criar response builder
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    private function build(Request $request): void
    {
        $this->removeGatewayBuild = new RemoveGatewayBuilder($request);
    }

    private function execute(): void
    {
        try {
            $this->sendToGateway();
            $this->checkRemoveSuccess();
            $this->removeGatewayBuild->buildToDatabase(
                [
                    'statusGateway' => 'CANCELED',
                    'statusDatabase' => 4
                ]
            );
            $this->updatePaymentGatewayData();
            $this->updatePayment();
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    private function sendToGateway(): void
    {
        try {
            $gatewayPaymentId = $this->removeGatewayBuild
                ->payload->gateway_payment_id;

            $client = new Client([
                'base_uri' => env('GATEWAY_URL')
            ]);
            $response = $client->delete(
                '/api/v3/payments/' . $gatewayPaymentId,
                [
                    'headers' =>
                    [
                        'Content-Type'   => "application/json",
                        'access_token' => env('GATEWAY_API_KEY')
                    ],
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
            $this->removeGatewayBuild->payload->save();
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    private function updatePaymentGatewayData(): void
    {
        try {
            $this->removeGatewayBuild->payloadDatabase->save();
        } catch (Exception $ex) {
            throw new Exception($ex->getMessage());
        }
    }

    public function checkRemoveSuccess(): void
    {
        $gatewayReturn = json_decode($this->gatewayReturn);
        throw_if(
            !isset($gatewayReturn->deleted) || !$gatewayReturn->deleted,
            $gatewayReturn
        );
    }
}
