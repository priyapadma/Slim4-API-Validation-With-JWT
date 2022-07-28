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
use App\Application\Helpers\CommonHelper;


class ViewUserAction implements RequestHandlerInterface
{
    private $logger;
    private $connection;
    private array $args;

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
        $this->logger->info('ViewUserAction: handler dispatched');
        $commonHelper = new CommonHelper();
        $data = $request->getParsedBody();
        $validateData = (array)$request->getParsedBody();

        // Within the Application Service class: Do the validation
        $validationFactory = new CakeValidationFactory();
        $validator = $validationFactory->createValidator();
        //Form field validation
        $validator
            ->requirePresence('brand_id')
            ->notEmptyString('brand_id', 'Field required');
        $validationResult = $validationFactory->createValidationResult(
            $validator->validate($validateData)
        );

        if ($validationResult->fails()) {//throws an error if validation fails
            throw new ValidationException('Please check your input', $validationResult);
        }
        try {
            $id = $commonHelper->resolveArg($request,'id');
            $brand = isset($data['brand_id']) ? $data["brand_id"] :  '';
            $this->logger->info('ViewUserAction: user id'.$id);
            $db =  $this->connection;
            $sql = $db->prepare('SELECT * from users where id=:id and brand_id=:brand');
            $sql->execute(array(':id' => $id,':brand' => $brand));
            $data = $sql->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            if(count($data)>0){
                $userSession = $data[0]->session;
                if(!$this->isValidUserSession($userSession)) {
                    $this->logger->info('ViewUserAction: session expired and new user session created'.$id);
                    $sql = $db->prepare("UPDATE users set session = :session where id = :id");		
                    $sql->execute(array(':session' => strtotime('+30 days'),':id' => $id));
                } 
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "User found",
                        "result" => $data
                    ))
                );
            } else {
                $this->logger->info('ViewUserAction: User not found'.$id);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "User not found"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('ViewUserAction: SQL error in getting user detail for user id-----'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'SQL error in getting user detail'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('ViewUserAction: Error in getting user detail for user id-----'.$id.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in getting user detail'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}