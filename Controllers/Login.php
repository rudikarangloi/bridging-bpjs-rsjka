<?php
 
namespace App\Controllers;
 
use CodeIgniter\RESTful\ResourceController;
use CodeIgniter\API\ResponseTrait;
use App\Models\UserModel;
use Firebase\JWT\JWT;
use CodeIgniter\HTTP\IncomingRequest;
 
class Login extends ResourceController
{
    /**
     * Return an array of resource objects, themselves in array format
     *
     * @return mixed
     */
    use ResponseTrait;
    public function index()
    {
        
        $request = service('request');
        
        // Extract the token
        $user = $request->getHeader('x-username')->getValue();
        $pass = $request->getHeader('x-password')->getValue();
        

        //if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $model = new UserModel();
        $user = $model->where("email", $user)->first();
        if(!$user) return $this->failNotFound('Email Not Found');
        // echo $this->request->getVar('password');
        // echo $user['password'];
        //echo strlen($user['password']);
        $verify = password_verify($pass, $user['password']);
        if(!$verify) return $this->fail('Wrong Password');
 
        $key = getenv('TOKEN_SECRET');
        $iat = time(); // current timestamp value
        $exp = $iat + 3600;
  
        $payload = array(
            "iss" => "RSJKA",
            "aud" => "MJKN",
            "sub" => "ws mjkn",
            "iat" => $iat, //Time the JWT issued at
            "exp" => $exp, // Expiration time of token
            "uid" => $user['id'],
            "email" => $user['email']
            
        );
       
        $token = JWT::encode($payload, $key, 'HS256');
        //$token = JWT::encode($payload, $key);
       
            $response = array(
                'response' => array(
                    'token' => $token
                ),
                'metadata' => array(
                    'message' => 'ok',
                    'code' => '200'
                )
            );
         return   $this->respond($response,200);
        //return $this->respond($token);
    }
    public function auth()
    {
        helper(['form']);
        $rules = [
            'email' => 'required|valid_email',
            'password' => 'required|min_length[6]'
        ];
        if(!$this->validate($rules)) return $this->fail($this->validator->getErrors());
        $model = new UserModel();
        $user = $model->where("email", $this->request->getVar('email'))->first();
        if(!$user) return $this->failNotFound('Email Not Found');
        // echo $this->request->getVar('password');
        // echo $user['password'];
        //echo strlen($user['password']);
        $verify = password_verify($this->request->getVar('password'), $user['password']);
        if(!$verify) return $this->fail('Wrong Password');
 
        $key = getenv('TOKEN_SECRET');
        $payload = array(
            "iat" => 1356999524,
            "nbf" => 1357000000,
            "uid" => $user['id'],
            "email" => $user['email']
        );
        $token = JWT::encode($payload, $key, 'HS256');
        //$token = JWT::encode($payload, $key);
        //$status = parent::HTTP_OK;
            $response = array(
                'response' => array(
                    'token' => $token
                ),
                'metadata' => array(
                    'message' => 'ok',
                    'code' => '200'
                )
            );
         return   $this->respond($response, $status);
        //return $this->respond($token);
    }
}