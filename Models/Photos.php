<?php

namespace Models;

use \Core\Model;

class Photos extends Model
{

    public function getRandomPhotos($per_page, $excludes)
    {
        $array = array();
        
        foreach($excludes as $k => $item){
            $excludes[$k] = intval($item);
        }

        if(count($excludes) > 0){
            $sql = "SELECT * FROM photos WHERE id NOT IN(".implode(',', $excludes).") ORDER BY RAND() LIMIT $per_page";
        }else{
            $sql = "SELECT * FROM photos ORDER BY RAND() LIMIT $per_page";
              
        }

        $sql = $this->db->query($sql);  

        if($sql->rowCount() > 0){
            $array = $sql->fetchAll(\PDO::FETCH_ASSOC);

            foreach($array as $k => $item){
                $array[$k]['url'] = HOME.'/Media/photos/'.$item['url'];
                $array[$k]['like_count'] = $this->getLikeCount($item['id']);
                $array[$k]['comments'] = $this->getComments($item['id']);
            }

        }

        return $array;
    }

    public function getPhotosFromUser($id_user, $offset, $per_page)
    {

        $array  = array();

        $sql = "SELECT * FROM photos WHERE id_user = :id ORDER BY id DESC LIMIT $offset, $per_page";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id', $id_user);
        $sql->execute();

        if($sql->rowCount() > 0){
            $array = $sql->fetchAll(\PDO::FETCH_ASSOC);

            foreach($array as $k => $item){
                $array[$k]['url'] = HOME.'/Media/photos/'.$item['url'];
                $array[$k]['like_count'] = $this->getLikeCount($item['id']);
                $array[$k]['comments'] = $this->getComments($item['id']);
            }
            
        }

        return $array;

    }

    public function getFeedCollection($ids, $offset, $per_page)
    {
        
        $array = array();
        $users = new Users();

        if(count($ids) > 0){

            $sql = "SELECT * FROM photos 
                    WHERE id_user IN (".implode(',', $ids).") 
                    ORDER BY id DESC 
                    LIMIT $offset, $per_page";
            $sql = $this->db->query($sql);

            if($sql->rowCount() > 0){

                $array = $sql->fetchAll(\PDO::FETCH_ASSOC);

                foreach($array as $k => $item){

                    $user_info = $users->getInfo($item['id_user']);

                    $array[$k]['name']   = $user_info['name'];
                    $array[$k]['avatar'] = $user_info['avatar'];
                    $array[$k]['url'] = HOME.'/Media/photos/'.$item['url'];

                    $array[$k]['like_count'] = $this->getLikeCount($item['id']);
                    $array[$k]['comments'] = $this->getComments($item['id']);
                }

            }
        }

        return $array;

    }

    public function getPhoto($id_photo)
    {
        $array = array();

        $users = new Users();

        $sql = "SELECT * FROM photos WHERE id = :id";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id', $id_photo);
        $sql->execute();

        if($sql->rowCount() > 0){

            $array = $sql->fetch(\PDO::FETCH_ASSOC);

                $user_info = $users->getInfo($array['id_user']);

                $array['name']   = $user_info['name'];
                $array['avatar'] = $user_info['avatar'];
                $array['url'] = HOME.'/Media/photos/'.$array['url'];

                $array['like_count'] = $this->getLikeCount($array['id']);
                $array['comments'] = $this->getComments($array['id']);


        }

        return $array;
    }

    public function getComments($id_photo)
    {
        $array = array();

        $sql = "SELECT photos_comment.*, users.name FROM photos_comment LEFT JOIN users ON users.id = photos_comment.id_user WHERE photos_comment.id_photo = :id";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id', $id_photo);
        $sql->execute();

        if($sql->rowCount() > 0){
            $array = $sql->fetchAll(\PDO::FETCH_ASSOC);
        }

        return $array;
    }

    public function getLikeCount($id_photo)
    {
        $sql = "SELECT COUNT(*) AS c FROM photos_likes WHERE id_photo = :id";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id', $id_photo);
        $sql->execute();

        $info = $sql->fetch();
        
        return $info['c'];

    }

    public function getPhotosCount($id_user)
    {
        $sql = "SELECT COUNT(*) AS c FROM photos WHERE id_user = :id";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id', $id_user);
        $sql->execute();

        $info = $sql->fetch();
        
        return $info['c'];
    }

    public function deletePhoto($id_photo, $id_user)
    {
        $sql = "SELECT id FROM photos WHERE id = :id AND id_user = :id_user";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id', $id_photo);
        $sql->bindValue(':id_user', $id_user);
        $sql->execute();

        if($sql->rowCount() > 0){

            $sql = "DELETE FROM photos_comment WHERE id_photo = :id_photo";
            $sql = $this->db->prepare($sql);
            $sql->bindValue(':id_photo', $id_photo);
            $sql->execute();

            $sql = "DELETE FROM photos_likes WHERE id_photo = :id_photo";
            $sql = $this->db->prepare($sql);
            $sql->bindValue(':id_photo', $id_photo);
            $sql->execute();

            $sql = "DELETE FROM photos WHERE id = :id";
            $sql = $this->db->prepare($sql);
            $sql->bindValue(':id', $id_photo);
            $sql->execute();

            return '';

        }else{
            return 'Esta foto não é sua';
        }
    }

    public function deleteAll($id_user)
    {
        $sql = "DELETE FROM photos_comment WHERE id_user = :id_user";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id_user', $id_user);
        $sql->execute();

        $sql = "DELETE FROM photos_likes WHERE id_user = :id_user";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id_user', $id_user);
        $sql->execute();

        $sql = "DELETE FROM photos WHERE id_user = :id_user";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id_user', $id_user);
        $sql->execute();
    }

    public function addComment($id_photo, $id_user, $txt)
    {
        if(!empty($txt)){

            $sql = "INSERT INTO photos_comment (id_user, id_photo, date_comment, txt) VALUES (:id_user, :id_photo, NOW(), :txt)";
            $sql = $this->db->prepare($sql);
            $sql->bindValue(':id_user', $id_user);
            $sql->bindValue(':id_photo', $id_photo);
            $sql->bindValue(':txt', $txt);
            $sql->execute();

        return '';

        }else{
            return 'Comentário Vazio!';
        }   

    }

    public function deleteComment($id_comment, $id_user)
    {
        $sql = "SELECT id FROM photos_comment WHERE id_user = :id_user AND id = :id";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id_user', $id_user);
        $sql->bindValue(':id', $id_comment);
        $sql->execute();

        if($sql->rowCount() > 0){

            $sql = "DELETE FROM photos_comment WHERE id = :id";
            $sql = $this->db->prepare($sql);
            $sql->bindValue(':id', $id_comment);
            $sql->execute();

            return '';

        }else{

            $sql = "SELECT * FROM photos_comment LEFT JOIN photos ON photos.id = photos_comment.id_photo WHERE photos.id_user = :id_user AND photos_comment.id = :id";
            $sql = $this->db->prepare($sql);
            $sql->bindValue(':id_user', $id_user);
            $sql->bindValue(':id', $id_comment);
            $sql->execute();

            if($sql->rowCount() > 0){

                $sql = "DELETE FROM photos_comment WHERE id = :id";
                $sql = $this->db->prepare($sql);
                $sql->bindValue(':id', $id_comment);
                $sql->execute();

                return '';

            }else{
                return 'Este comentário não é seu';
            }
        }
    }

    public function like($id_photo, $id_user)
    {
        $sql = "SELECT * FROM photos_likes WHERE id_photo = :id_photo AND id_user = :id_user";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id_user', $id_user);
        $sql->bindValue(':id_photo', $id_photo);
        $sql->execute();

        if($sql->rowCount() === 0){

            $sql = "INSERT INTO photos_likes (id_user, id_photo) VALUES (:id_user, :id_photo)";
            $sql = $this->db->prepare($sql);
            $sql->bindValue(':id_user', $id_user);
            $sql->bindValue(':id_photo', $id_photo);
            $sql->execute();

            return '';

        }else{
            return 'Você já deu like nessa foto';
        }

    }

    public function unlike($id_photo, $id_user)
    {
        $sql = "DELETE FROM photos_likes WHERE id_photo = :id_photo AND id_user = :id_user";
        $sql = $this->db->prepare($sql);
        $sql->bindValue(':id_user', $id_user);
        $sql->bindValue(':id_photo', $id_photo);
        $sql->execute();

        return '';
    }

}