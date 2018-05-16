<?php

/**
 * Created by PhpStorm.
 * User: Pedro
 * Date: 16/05/2018
 * Time: 12:11
 */
class PersonController extends ControllerBase
{
    public function indexAction(){
        $id =$this->request->get('id');
        $tag = $this->request->get('tag');
        $nome = $this->request->get('nome');
        $cpf = $this->request->get('cpf');
        
        $key = Key::findFirst(array('conditions' => 'ptag = ?1 ', 'bind' => array(1 => $tag)));

        if(empty($key)){
            echo json_encode(array('ERROR' => 'NO KEY FOUND FOR TAG '.$tag));
            return;
        }
        
        $car = Car::findFirst(array('conditions' => 'ptag = ?1 ', 'bind' => array(1 => $tag)));

        if(empty($car)){
            echo json_encode(array('ERROR' => 'NO CAR FOUND FOR TAG '.$tag));
            return;
        }

        if( $car->status == 0){
            echo json_encode(array('ERROR' => 'CAR WAITING IN THE GARAGE'));
            return;
        } else if($car->status == 1){
            //carro tentando sair -> verificar motorista
            if($car->ptag == $tag){
                $car->status = 2;
                
                if(!$car->save()){
                    echo json_encode(array('ERROR' => 'DB ERROR WHILE UPDATING CAR STATUS'));
                    return;
                }
                //liberado
                echo json_encode(array('STATUS' => 'APPROVED'));
                return;
            }
        } else if($car->status == 2){
            echo json_encode(array('ERROR' => 'CARRO ALREADY MOVING'));
            return;
        }

        echo json_encode(array('ERROR' => 'SOMETHING WRONG IS NOT RIGHT'));
    }
}