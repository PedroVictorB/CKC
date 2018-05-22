<?php

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\RequestException as GuzzleRequestException;

/**
 * Created by PhpStorm.
 * User: Pedro
 * Date: 16/05/2018
 * Time: 12:11
 */
class CarController extends ControllerBase
{
    public function indexAction(){
        //$id = $this->request->get('id');
        $tag = $this->request->get('tag');
        $marca = $this->request->get('marca');
        $nome = $this->request->get('nome');
        $placa = $this->request->get('placa');
        $status = $this->request->get('status');

        $this->view->disable();
        
        if(!isset($tag) || empty($tag)){
            echo json_encode(array('ERROR' => 'TAG IS NULL'));
            return;
        }
        $car = Car::findFirst(array('conditions' => 'tag = ?1 ', 'bind' => array(1 => $tag)));


        if(empty($car)){
            //Não é cadastrado ainda
            $car = new Car();
            $car->tag = $tag;
            $car->marca = 'Hyundai';
            $car->nome = 'HB20';
            $car->placa = 'QUG-1921';
            $car->status = 0;
            $car->ptag = "E2001001250A001514707BC1";
            
            if(!$car->save()){
                echo json_encode(array('ERROR' => 'DB ERROR WHILE SAVING CAR'));
                return;
            }
        }
        
        // status 0 -> carro na garagem
        // status 1 -> carro na cancela
        // status 2 -> carro em movimento
        
        if($car->status == 0){
            if($status == 1){
                //Carro quer sair

                $client = new GuzzleClient();

                try{
                    $load = array(
                        'contextElements' => [
                            [
                                "type" => "CAR",
                                "isPattern" => "false",
                                "id" => "car".$car->id,
                                "attributes" => [
                                    [
                                        'name' => 'marca',
                                        'type' => 'String',
                                        'value' => ''.$car->marca
                                    ],
                                    [
                                        'name' => 'nome',
                                        'type' => 'String',
                                        'value' => ''.$car->nome
                                    ],
                                    [
                                        'name' => 'placa',
                                        'type' => 'String',
                                        'value' => ''.$car->placa
                                    ],
                                    [
                                        'name' => 'ptag',
                                        'type' => 'String',
                                        'value' => ''.$car->ptag
                                    ],
                                    [
                                        'name' => 'status',
                                        'type' => 'String',
                                        'value' => ''.$car->status
                                    ],
                                    [
                                        'name' => 'tag',
                                        'type' => 'String',
                                        'value' => ''.$car->tag
                                    ],
                                    [
                                        'name' => 'data',
                                        'type' => 'String',
                                        'value' => new DateTime()
                                    ]
                                ]
                            ]
                        ],
                        "updateAction" => "UPDATE"
                    );

                    $res = $client->post(
                        $this->config->CKC->ORION_CONFIGURATION->protocol.'://'
                        .$this->config->CKC->ORION_CONFIGURATION->url.':'
                        .$this->config->CKC->ORION_CONFIGURATION->port
                        .'/v1/updateContext'
                        , array(
                            'headers' => [
                                'Content-Type' => 'application/json',
                                'Accept' => 'application/json',
                                'X-Auth-Token' => ''
                            ],
                            "json" => $load
                        )

                    );
                }catch (GuzzleRequestException $e){
                    echo json_encode(
                        array("CKC_STATUS" =>
                            array(
                                "code" => $e->getResponse()->getStatusCode(),
                                "reasonPhrase" => $e->getResponse()->getReasonPhrase(),
                                "details" => "Error while communicating to ORION (Contact admin)"
                            )
                        )
                    );
                    return;
                }
                
                //Atualizar BD
                $car->status = $status;

                if(!$car->save()){
                    echo json_encode(array('ERROR' => 'DB ERROR WHILE UPDATING CAR'));
                    return;
                }
                $client = new GuzzleClient();

                $res = $client->get(
                    $this->config->CKC->ESP_CONFIGURATION->protocol.'://'
                    .$this->config->CKC->ESP_CONFIGURATION->url.':'
                    .$this->config->CKC->ESP_CONFIGURATION->port.'/'
                    .'6'
                );
            }
        }else if ($car->status == 1){
            if($status == 1){
                error_log("Carro tentando sair novamente (esperando cracha)");
                //Carro tentando sair novamente (esperando cracha)
            }
        }else if ($car->status == 2){
            if($status == 1){
                echo json_encode(array('ERROR' => 'CAR ALREADY APPROVED'));
                return;
            }
        }else{
            echo json_encode(array('ERROR' => 'CAR STATUS ????'));
            return;
        }

        echo json_encode(array('STATUS' => 'WAITING FOR PERSON ID'));
    }
}