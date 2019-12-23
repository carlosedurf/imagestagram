<?php

namespace Controllers;

use \Core\Controller;
use Models\Photos;
use Models\Users;

class PhotosController extends Controller
{
    public function index()
    {}

    public function random()
    {
        $array = array('error'=>'', 'logged'=>false);

        $method = $this->getMethod();
        $data   = $this->getRequestData();

        $users = new Users();
        $p = new Photos();

        if(!empty($data['jwt']) && $users->validateJwt($data['jwt'])){

            $array['logged'] = true;

            if($method == 'GET'){

                $per_page = 10;
                if(!empty($data['per_page'])){
                    $per_page = intval($data['per_page']);
                }

                $excludes = array();
                if(!empty($data['excludes'])){
                    $excludes = explode(',', $data['excludes']);
                }

                $array['data'] = $p->getRandomPhotos($per_page, $excludes);

            }else{
                $array['error'] = 'Método '.$method.' não disponível';
            }

        }else{
            $array['error'] = 'Acesso Negado';
        }

        $this->returnJson($array);
    }

    public function view($id_photo)
    {
        $array = array('error'=>'', 'logged'=>false);

        $method = $this->getMethod();
        $data   = $this->getRequestData();

        $users = new Users();
        $p = new Photos();

        if(!empty($data['jwt']) && $users->validateJwt($data['jwt'])){

            $array['logged'] = true;

            switch($method){
                case 'GET':
                    $array['data'] = $p->getPhoto($id_photo);
                break;

                case 'DELETE':
                    $array['error'] = $p->deletePhoto($id_photo, $users->getId());
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

    public function comment($id_photo)
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
                    if(!empty($data['txt'])){
                        $array['error'] = $p->addComment($id_photo, $users->getId(), $data['txt']);
                    }else{
                        $array['error'] = 'Comentário vazio';
                    }
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

    public function delete_comment($id)
    {
        $array = array('error'=>'', 'logged'=>false);

        $method = $this->getMethod();
        $data   = $this->getRequestData();

        $users = new Users();
        $p = new Photos();

        if(!empty($data['jwt']) && $users->validateJwt($data['jwt'])){

            $array['logged'] = true;

            switch($method){
                case 'DELETE':
                    $array['error'] = $p->deleteComment($id, $users->getId());
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

    public function like($id_photo)
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
                    $array['error'] = $p->like($id_photo, $users->getId());
                break;

                case 'DELETE':
                    $array['error'] = $p->unlike($id_photo, $users->getId());
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