<?php

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
            $car->marca = $marca;
            $car->nome = $nome;
            $car->placa = $placa;
            $car->status = 0;
            $car->ptag = "";
            
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
                
                //Atualizar BD
                $car->status = $status;

                if(!$car->save()){
                    echo json_encode(array('ERROR' => 'DB ERROR WHILE UPDATING CAR'));
                    return;
                }
            }
        }else if ($car->status == 1){
            if($status == 1){
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