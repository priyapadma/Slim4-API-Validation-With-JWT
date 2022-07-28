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


class ListUserAction implements RequestHandlerInterface
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
        $this->logger->info('ListUserAction: handler dispatched');
        $data = $request->getParsedBody();
        $validateData = (array)$request->getParsedBody();

        // Within the Application Service class: Do the validation
        $validationFactory = new CakeValidationFactory();
        $validator = $validationFactory->createValidator();
        //Form field validation
        $validator
            ->requirePresence('brand_id')
            ->notEmptyString('brand_id', 'Field required')
            ->requirePresence('comp_id', 'Field required')
            ->notEmptyString('comp_id', 'Field required')
            ->requirePresence('user_id', 'Field required')
            ->notEmptyString('user_id', 'Field required');
        $validationResult = $validationFactory->createValidationResult(
            $validator->validate($validateData)
        );

        if ($validationResult->fails()) {//throws an error if validation fails
            throw new ValidationException('Please check your input', $validationResult);
        }
        try {
            $brand = isset($data['brand_id']) ? $data["brand_id"] :  '';
            $status = isset($data['status']) ? $data["status"] :  2;
            $this->logger->info('ListUserAction: brand_id'.$brand);
            $db =  $this->connection;
            if($status!=2) {
                $sql = $db->prepare('SELECT * from users where brand_id=:brand and status=:status');
                $sql->execute(array(':brand' => $brand, ':status' => $status));
            } else {
                $sql = $db->prepare('SELECT * from users where brand_id=:brand');
                $sql->execute(array(':brand' => $brand));
            }
            $data = $sql->fetchAll(PDO::FETCH_OBJ);
            $response = new Response();
            if(count($data)>0){
                $this->logger->info('ListUserAction: Number of users available for brand id-'.$brand.'-is-'.count($data));
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 1,
                        "message" => "Data found",
                        "result" => $data
                    ))
                );
            } else {
                $this->logger->info('ListUserAction: User not found for brand id-'.$brand);
                $response->getBody()->write(
                    json_encode(array(
                        "code" => 1,
                        "status" => 2,
                        "message" => "No data found"
                    ))
                );
            }
        }
        catch(MySQLException $e) {
            $this->logger->info('ListUserAction: SQL error in getting all users for brand-----'.$brand.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'SQL error in getting all users'
                ))
            );
        }
        catch(Exception $e) {
            $this->logger->info('ListUserAction: Error in getting all users for brand-----'.$brand.'----'.$e->getMessage());
            $response->getBody()->write(
                json_encode(array(
                    "code" => 0,
                    "status" => 0,
                    "message" => 'Error in getting all users'
                ))
            );
        }
        return $response->withHeader('Content-Type', 'application/json');
    }

   
}