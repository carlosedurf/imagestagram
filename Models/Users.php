<?php

namespace Models;

use \Core\Model;
use \Models\Jwt;
use \Models\Photos;

class Users extends Model
{

    private $id_user;

    public function create($name, $email, $pass)
    {
        
        if(!$this->emailExists($email)){

            $hash = password_hash($pass, PASSWORD_DEFAULT);

            $sql = "INSERT INTO users (name, email, pass) VALUES (:name, :email, :pass)";
            $sql = $this->db->prepare($sql);
            $sql->bindValue(':name', $name);
            $sql->bindValue(':email', $email);
            $sql->bindValue(':pass', $hash);
            $sql->execute();

            $this->id_user = $this->db->lastInsertId();

            return true;
        }else{
            return false;
        }

    }

    public function checkCredencials($email, $pass)
    {
        $sql = "SELECT id, pass FROM users WHERE email = :email";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':email', $email);
        $sql->execute();

        if($sql->rowCount() > 0){

            $info = $sql->fetch();

            if(password_verify($pass, $info['pass'])){

                $this->id_user = $info['id'];

                return true;

            }else{
                return false;
            }

        }else{
            return false;
        }

    }

    public function getId()
    {
        return $this->id_user;
    }

    public function getInfo($id)
    {
        $array = array();

        $sql = "SELECT id, name, email, avatar FROM users WHERE id = :id";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id', $id);
        $sql->execute();


        if($sql->rowCount() > 0){
            $array = $sql->fetch(\PDO::FETCH_ASSOC);

            $photos = new Photos();

            if(!empty($array['avatar'])){
                $array['avatar'] = HOME.'/Media/avatar/'.$array['avatar'];
            }else{
                $array['avatar'] = HOME.'/Media/avatar/default.jpg';
            }

            $array['following'] = $this->getFollowingCount($id);
            $array['followers'] = $this->getFollowersCount($id);
            $array['photos_count'] = $photos->getPhotosCount($id);

        }

        return $array;
    }

    public function getFeed($offset = 0, $per_page = 10)
    {
        /*
        PASSO 1: Pegar os seguidores
        PASSO 2: Fazer uma lista das últimas fotos desses seguidores
        */
        $followingUsers = $this->getFollowing($this->getId());

        $p = new Photos();

        return $p->getFeedCollection($followingUsers, $offset, $per_page);
    }

    public function getFollowing($id_user)
    {
        $array = array();

        $sql = "SELECT id_user_passive FROM users_following WHERE id_user_active = :id";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id', $id_user);
        $sql->execute();

        if($sql->rowCount() > 0){
            $data = $sql->fetchAll();
            foreach($data as $item){
                $array[] = intval($item['id_user_passive']);
            }
        }

        return $array;
    }

    public function getFollowingCount($id_user)
    {
        $sql = "SELECT COUNT(*) AS c FROM users_following WHERE id_user_active = :id";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id', $id_user);
        $sql->execute();

        $info = $sql->fetch();

        return $info['c'];
    }

    public function getFollowersCount($id_user)
    {
        $sql = "SELECT COUNT(*) AS c FROM users_following WHERE id_user_passive = :id";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id', $id_user);
        $sql->execute();

        $info = $sql->fetch();
        
        return $info['c'];
    }

    public function createJwt()
    {
        $jwt = new Jwt();
        return $jwt->create(array('id_user' => $this->id_user));
    }

    public function validateJwt($token)
    {
        $jwt = new Jwt();
        $info = $jwt->validate($token);

        if(isset($info->id_user)){
            $this->id_user = $info->id_user;
            return true;
        }else{
            return false;
        }

    }

    private function emailExists($email)
    {
        $sql = "SELECT * FROM users WHERE email = :email";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':email', $email);
        $sql->execute();

        if($sql->rowCount() > 0){
            return true;
        }else{
            return false;
        }
    }

    public function editInfo($id, $data)
    {
        
        if($id === $this->getId()){
            
            $toChange = array();

            if(!empty($data['name'])){
                $toChange['name'] = $data['name'];
            }

            if(!empty($data['email'])){
                if(filter_var($data['email'], FILTER_VALIDATE_EMAIL)){
                    if(!$this->emailExists($data['email'])){
                        $toChange['email'] = $data['email'];
                    }else{
                        return 'E-mail já existente';
                    }
                    
                }else{
                    return 'E-mail inválido';
                }
            }

            if(!empty($data['pass'])){
                $toChange['pass'] = password_hash($data['pass'], PASSWORD_DEFAULT);
            }

            if(count($toChange) > 0){

                $fields = array();
                foreach($toChange as $k => $v){
                    $fields[] = $k.' = :'.$k;
                }

                $sql = "UPDATE users SET ".implode(', ', $fields)." WHERE id = :id";
                $sql = $this->db->prepare($sql);
                $sql->bindValue(':id', $id);

                foreach($toChange as $k => $v){
                    $sql->bindValue(':'.$k, $v);
                }

                $sql->execute();

                return '';

            }else{
                return 'preencha os dados corretamente';
            }
            
        }else{
            return 'Não é permitido editar outro usuário';
        }

    }

    public function delete($id)
    {
        if($id === $this->getId()){

            $p = new Photos();
            $p->deleteAll($id);

            /*
            Comentários nas fotos
            likes nas fotos
            Fotos

            seguidores
            usuário
            */ 

            $sql = "DELETE FROM users_following WHERE id_user_active = :id OR id_user_passive = :id";
            $sql = $this->db->prepare($sql);
            $sql->bindValue(':id', $id);
            $sql->execute();

            $sql = "DELETE FROM users WHERE id = :id";
            $sql = $this->db->prepare($sql);
            $sql->bindValue(':id', $id);
            $sql->execute();

            return '';

        }else{
            return 'Não é permitido deletar outro usuário';
        }
    }

    public function follow($id_user)
    {
        $sql = "SELECT * FROM users_following WHERE id_user_active = :id_user_active AND id_user_passive = :id_user_passive";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id_user_active', $this->getId());
        $sql->bindValue(':id_user_passive', $id_user);
        $sql->execute();

        if($sql->rowCount() === 0){

            $sql = "INSERT INTO users_following (id_user_active, id_user_passive) VALUES (:id_user_active, :id_user_passive)";
            $sql = $this->db->prepare($sql);
            $sql->bindValue(':id_user_active', $this->getId());
            $sql->bindValue(':id_user_passive', $id_user);
            $sql->execute();
            return true;
        }else{
            return false;
        }

    }

    public function unfollow($id_user)
    {
        $sql = "DELETE FROM users_following WHERE id_user_active = :id_user_active AND id_user_passive = :id_user_passive";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id_user_active', $this->getId());
        $sql->bindValue(':id_user_passive', $id_user);
        $sql->execute();
    }

}