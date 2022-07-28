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
use App\Application\Helpers\UserHelper;
use App\Application\Helpers\CommonHelper;

class UpdateUserAction implements RequestHandlerInterface
{
    private $logger;
    private $connection;

    public function __construct(PDO $connection,LoggerInterface $logger)
    {
        $this->connection = $connection;
        $this->logger = $logger;
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {		
        $this->logger->info('Home page handler dispatched');
		$commonHelper = new CommonHelper();
        $data = $request->getParsedBody();		
		$db =  $this->connection;		
		$userHelper = new UserHelper();	
        $userdata = $userHelper->updateAccount($db, $data, $commonHelper->resolveArg($request,'id'));
		$response = new Response();
		if( $userdata == 1 ) {
				$response->getBody()->write(
					json_encode(array(
						"code" => 1,
						"status" => 1,
						"message" => "User Registered",
						"result" => $data
					))
				);
			} else {
				$response->getBody()->write(
					json_encode(array(
						"code" => 1,
						"status" => 2,
						"message" => "User already exists",
						"result" => $data
					))
				);
			}
		return $response->withHeader('Content-Type', 'application/json');
    }
}