<?php

Class User{
    private $db;
    public function __construct(){
        $this->db = new Database();
    }

    public function register($data){
        $this->db->query("INSERT INTO user (full_name , email , password , imgUrl) VALUES (:full_name , :email , :password , :imgUrl) ");
        $this->db->bind(':full_name' , $data['full_name'] );
        $this->db->bind(':email' , $data['email'] );
        $this->db->bind(':password' , $data['password'] );
        $this->db->bind(':imgUrl' , $data['image'] );

        return $this->db->execute();
    }

    public function findUserByEmail($email) {
        $this->db->query('SELECT * FROM user WHERE email = :email');
        $this->db->bind(':email', $email);
    
        $row = $this->db->single();
    
        return ($row) ? true : false;
    }

    public function getNormalUser($userId){
      $this->db->query('
      SELECT u.id, u.full_name , u.email , u.imgUrl
    FROM user u
    LEFT JOIN Friendships fs ON (fs.userId1 = :userId AND fs.userId2 = u.id)
                            OR (fs.userId1 = u.id AND fs.userId2 = :userId)
    LEFT JOIN FriendRequests fr ON (fr.senderId = :userId AND fr.receiverId = u.id)
                              OR (fr.senderId = u.id AND fr.receiverId = :userId)
    WHERE fs.userId1 IS NULL 
      AND fs.userId2 IS NULL
      AND fr.senderId IS NULL
      AND u.id <> :userId;
      ');
      $this->db->bind(':userId', $userId);
      return $this->db->resultSet();
    }

    public function getAlreadySentRequest($userId){
      $this->db->query('
      SELECT u.id, u.full_name , u.email , u.imgUrl
      FROM user u
      JOIN friendrequests fr ON u.id = fr.receiverId
      WHERE fr.senderId = :userId 
        AND fr.status = :status
        AND NOT EXISTS (
          SELECT 1
          FROM Friendships fs
          WHERE (fs.userId1 = :userId  AND fs.userId2 = u.id)
             OR (fs.userId1 = u.id AND fs.userId2 = :userId )
        )
        AND u.id <> :userId ;  
      ');
      $this->db->bind(':userId', $userId);
      $this->db->bind(':status', 'pending');
      return $this->db->resultSet();
    }

    public function getMyFriends($userId){
      $this->db->query('
      SELECT U.id, U.full_name , u.email , u.imgUrl
      FROM user U
      JOIN Friendships F ON (U.id = F.userId1 OR U.id = F.userId2)
      WHERE :userId IN (F.userId1, F.userId2) and U.id <>:userId;  
      ');
      $this->db->bind(':userId', $userId);
      return $this->db->resultSet();
    }

    public function addFriendShip($senderId,$reveiverId){
      $this->db->query("INSERT INTO friendrequests(senderId ,receiverId) VALUES(:senderId,:reveiverId)");
      $this->db->bind(':senderId', $senderId);
      $this->db->bind(':reveiverId', $reveiverId);
      return $this->db->execute();
    }

    public function login($email, $password){
        $this->db->query('SELECT * FROM user WHERE email = :email ');
        $this->db->bind(':email', $email);
    
        $row = $this->db->single();
        
        if($row){
         
          $hashed_password = $row->password;
          if(password_verify($password, $hashed_password)){
            $this->db->query('SELECT id,email,full_name , imgUrl FROM user WHERE email = :email ');
            $this->db->bind(':email', $email);
            return $this->db->single();
          } else {
            return false;
          }
        }else{
          return false;
        }
        
      }
}