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


class RegisterAction implements RequestHandlerInterface
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
        $data = $request->getParsedBody();
        $validateData = (array)$request->getParsedBody();

        // Within the Application Service class: Do the validation
        $validationFactory = new CakeValidationFactory();
        $validator = $validationFactory->createValidator();
		//Form validation
        $validator
            ->notEmptyString('first_name', 'Field required')
			->requirePresence('first_name')
            ->notEmptyString('last_name', 'Field required')
			->requirePresence('last_name')
            ->notEmptyString('email', 'Field required')
			->add('email', 'validFormat', [
				'rule' => 'email',
				'message' => 'E-mail must be valid'
			])
			->requirePresence('email')
            ->notEmptyString('password', 'Field required')
			->add('password',[  
                'match'=>[  
                    'rule'=> ['compareWith','confirm_password'], 
                    'message'=>'The passwords does not match!', 
                ]  
            ])
			->requirePresence('password')
            ->notEmptyString('confirm_password', 'Field required')
			->add('confirm_password',[  
                'match'=>[  
                    'rule'=> ['compareWith','password'], 
                    'message'=>'The passwords does not match!', 
                ]  
            ])
			->requirePresence('confirm_password')
            ->notEmptyString('industry', 'Field required')
			->requirePresence('industry')
            ->notEmptyString('job_title', 'Field required')
			->requirePresence('job_title')
            ->notEmptyString('country', 'Field required')
			->requirePresence('country')
            ->notEmptyString('company_size', 'Field required')
			->requirePresence('company_size')
			->notEmptyString('brand_id', 'Field required')//TODO - get brand id dynamically from request domain
			->requirePresence('brand_id');

        $validationResult = $validationFactory->createValidationResult(
            $validator->validate($validateData)
        );
		//throw exception for validation failure
        if ($validationResult->fails()) {
            throw new ValidationException('Please check your input', $validationResult);
        }
		$db =  $this->connection;		
		$userHelper = new UserHelper();	
        $userdata = $userHelper->createUser($db, $data);
		$response = new Response();
		if( $userdata == 1 ) {
				$response->getBody()->write(
					json_encode(array(
						"code" => 1,
						"status" => 1,
						"message" => "User Registered"
					))
				);
			} else {
				$response->getBody()->write(
					json_encode(array(
						"code" => 1,
						"status" => 2,
						"message" => "User already exists"
					))
				);
			}
		return $response->withHeader('Content-Type', 'application/json');
    }
}