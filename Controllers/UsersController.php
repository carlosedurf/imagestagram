<?php

namespace Controllers;

use \Core\Controller;
use Models\Photos;
use \ Models\Users;

class UsersController extends Controller
{

    public function index()
    {}

    public function login()
    {
        $array = array('error'=>'');

        $method = $this->getMethod();
        $data = $this->getRequestData();

        if($method == 'POST'){
            
            if(!empty($data['email']) && !empty($data['pass'])){

                $users = new Users();

                if($users->checkCredencials($data['email'], $data['pass'])){
                    
                    // Gerar o JWT
                    $array['jwt'] = $users->createJwt();

                }else{
                    $array['error'] = 'Acesso Negado';
                }

                
            }else{
                $array['error'] = 'E-mail e/ou senha não preenchido.';
            }

        }else{
            $array['error'] = 'Método de requisição incompatível';
        }

        $this->returnJson($array);
    }

    public function new_record()
    {
        $array = array('error'=>'');

        $method = $this->getMethod();
        $data   = $this->getRequestData();

        if($method == 'POST'){

            if(!empty($data['name']) && !empty($data['email']) && !empty($data['pass'])){

                if(filter_var($data['email'], FILTER_VALIDATE_EMAIL)){

                    $users = new Users();

                    if($users->create($data['name'], $data['email'], $data['pass'])){

                        $array['jwt'] = $users->createJwt();

                    }else{
                        $array['error'] = 'E-mail já existente!';
                    }

                }else{
                    $array['error'] = 'E-mail inválido';
                }

            }else{
                $array['error'] = 'Dados não preenchido.';
            }

        }else{
            $array['error'] = 'Método de requisição incompatível';
        }

        $this->returnJson($array);
    }

    public function view($id)
    {
        $array = array('error'=>'', 'logged'=>false);

        $method = $this->getMethod();
        $data   = $this->getRequestData();

        $users = new Users();

        if(!empty($data['jwt']) && $users->validateJwt($data['jwt'])){

            $array['logged'] = true;

            $array['is_me'] = false;
            if($id == $users->getId()){
                $array['is_me'] = true;
            }

            switch($method){
                case 'GET':

                    $array['data'] = $users->getInfo($id);

                    if(count($array['data']) === 0){
                        $array['error'] = 'Usuário não existe';
                    }

                    break;

                case 'PUT':

                    $array['error'] = $users->editInfo($id, $data);

                    break;

                case 'DELETE':
                    
                    $array['error'] = $users->delete($id);

                    break;

                default:
                    $array['error'] = 'Método '.$method.' não disponível';
                    break;
            }

        }else{
            $array['error'] = 'Acesso Negado';
        }

        $this->returnJson($array);
    }

    public function feed()
    {
        $array = array('error'=>'', 'logged'=>false);

        $method = $this->getMethod();
        $data   = $this->getRequestData();

        $users = new Users();

        if(!empty($data['jwt']) && $users->validateJwt($data['jwt'])){

            $array['logged'] = true;

            if($method == 'GET'){

                $offset = 0;
                if(!empty($data['offset'])){
                    $offset = intval($data['offset']);
                }

                $per_page = 10;
                if(!empty($data['per_page'])){
                    $per_page = intval($data['per_page']);
                }

                $array['data'] = $users->getFeed($offset, $per_page);

            }else{
                $array['error'] = 'Método '.$method.' não disponível';
            }

        }else{
            $array['error'] = 'Acesso Negado';
        }

        $this->returnJson($array);
    }

    public function photos($id_user)
    {
        
        $array = array('error'=>'', 'logged'=>false);

        $method = $this->getMethod();
        $data   = $this->getRequestData();

        $users = new Users();
        $p = new Photos();

        if(!empty($data['jwt']) && $users->validateJwt($data['jwt'])){

            $array['logged'] = true;

            $array['is_me'] = false;
            if($id_user == $users->getId()){
                $array['is_me'] = true;
            }

            if($method == 'GET'){

                $offset = 0;
                if(!empty($data['offset'])){
                    $offset = intval($data['offset']);
                }

                $per_page = 10;
                if(!empty($data['per_page'])){
                    $per_page = intval($data['per_page']);
                }

                $array['data'] = $p->getPhotosFromUser($id_user, $offset, $per_page);

            }else{
                $array['error'] = 'Método '.$method.' não disponível';
            }

        }else{
            $array['error'] = 'Acesso Negado';
        }

        $this->returnJson($array);

    }

    public function follow($id_user)
    {
        $array = array('error'=>'', 'logged'=>false);

        $method = $this->getMethod();
        $data   = $this->getRequestData();

        $users = new Users();
        $p = new Photos();

        if(!empty($data['jwt']) && $users->validateJwt($data['jwt'])){

            $array['logged'] = true;

            switch($method){
                case 'POST':
                    $users->follow($id_user);
                break;

                case 'DELETE':
                    $users->unfollow($id_user);
                break;

                default:
                    $array['error'] = 'Método '.$method.' não disponível';
                break;
            }

        }else{
            $array['error'] = 'Acesso Negado';
        }

        $this->returnJson($array);
    }

}