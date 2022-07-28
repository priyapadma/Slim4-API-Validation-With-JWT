<?php

declare(strict_types=1);

namespace App\Application\Actions\Users;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerInterface;
use Slim\Psr7\Response;
use Cake\Validation\Validator;
use Selective\Validation\ValidationResult;
use Selective\Validation\Factory\CakeValidationFactory;
use Selective\Validation\Exception\ValidationException;
use PDO;
use \Firebase\JWT\JWT;

class LoginAction implements RequestHandlerInterface
{
    private $logger;
    private $connection;

    public function __construct(PDO $connection,LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function isValidUserSession($userSession) {
        if($userSession >= strtotime('now')) {
            return true;
        } else {
            return false;
        }
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $this->logger->info('Login action: handler dispatched');
        $data = $request->getParsedBody();
        $validateData = (array)$request->getParsedBody();

        // Within the Application Service class: Do the validation
        $validationFactory = new CakeValidationFactory();
        $validator = $validationFactory->createValidator();
        //Form field validation
        $validator
            ->notEmptyString('email', 'Field required')
			->add('email', 'validFormat', [
				'rule' => 'email',
				'message' => 'E-mail must be valid'
			])
			->requirePresence('email')
            ->notEmptyString('password', 'Field required')
			->requirePresence('password')
            ->notEmptyString('brand_id', 'Field required')
			->requirePresence('brand_id');//TODO:get brand id dynamically from request domain
        $validationResult = $validationFactory->createValidationResult(
            $validator->validate($validateData)
        );
        if ($validationResult->fails()) {//throws an error if validation fails
            throw new ValidationException('Please check your input', $validationResult);
        }
        try {
            $email = isset($data['email']) ? $data["email"] : '';
            $password = isset($data['password']) ? $data["password"] :  '';
            $brand = isset($data['brand_id']) ? $data["brand_id"] :  '';
            $this->logger->info('Login action: email'.$email);
            $db =  $this->connection;
            $sql = $db->prepare('SELECT * from users where email=:email and brand_id=:brand and status=:status');
            $sql->execute(array(':email' => $email,':brand' => $brand,':status' => 1));
            $data = $sql->fetchAll(PDO::FETCH_OBJ);
            //$payload = json_encode($data);
            $response = new Response();
            if(count($data)>0){
                if(password_verify($password, $data[0]->password)) {//validate password
                    $userSession = $data[0]->session;
                    $userId = $data[0]->id;
                    $token = JWT::encode(['id' => $brand, 'email' => $email], "TOURSECERTKEY", "HS256");//create new token for api authorization
                    $userData = array('id' => $userId, 'brand' => $data[0]->brand_id, 'is_sub_user' => $data[0]->is_subscribed_user, 'fst_name' => $data[0]->first_name, 'last_name' => $data[0]->last_name, 'email' => $data[0]->email);
                    if($this->isValidUserSession($userSession)) {
                        $this->logger->info('Login action: User found '.$email);
                        $sql = $db->prepare("UPDATE users set last_logged_on=:last_logged_on where email = :email");	
                        $sql->execute(array(':last_logged_on' => date('Y-m-d h:i:s'),':email' => $email));
                        $checksum = $userSession."|".$userId."|".$brand;   
                        $checksum = hash('sha256', $checksum);  
                        $response->getBody()->write(
                            json_encode(array(
                                "code" => 1,
                                "status" => 1,
                                "message" => "User found",
                                "token" => $token,
                                "data" => $userData,
                                "checksum" => $checksum
                            ))
                        );
                    } else {
                        $this->logger->info('Login action: User found and new user session created'.$email);
                        $newSession = strtotime('+30 days');
                        $sql = $db->prepare("UPDATE users set session = :session,last_logged_on=:last_logged_on where email = :email");		
                        $sql->execute(array(':session' => $newSession,':last_logged_on' => date('Y-m-d h:i:s'),':email' => $email));
                        $data = $sql->rowCount();
                        $comp_id=1;
                        $checksum = $userId."|".$comp_id;
                        $checksum = hash('sha256', $checksum);  
                        $response->getBody()->write(
                            json_encode(array(
                                "code" => 1,
                                "status" => 1,
                                "message" => "User found and new user session created",
                                "token" => $token,
                                "data" => $userData,
                                "checksum" => $checksum
                            ))
                        );
                    }
                } else {
                    $this->logger->info('Login action: Password mismatch '.$email);
                    $response->getBody()->write(
                        json_encode(array(
                            "code" => 1,
                            "status" => 2,
                            "message" => "Password mismatch"
                        ))
                    );
                }
            } else {
                $this->logger->info('Login action: User not found '.$email);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 3,
                        "message" => "User not found"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('LoginAction: SQL error in login-----'.$email.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'SQL error in login'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('LoginAction: Error in login-----'.$email.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in login'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}