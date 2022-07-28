<?php
declare(strict_types=1);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Application\Actions\Users\RegisterAction;
use App\Application\Actions\Users\LoginAction;
use App\Application\Actions\Users\ChangePwdAction;
use App\Application\Actions\Users\ViewUserAction;
use App\Application\Actions\Users\UpdateUserAction;
use App\Application\Actions\Users\ListUserAction;
use App\Application\Actions\Users\DeleteUserAction;

use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;
use Cake\Validation\Validator;
use Selective\Validation\ValidationResult;
use Selective\Validation\Factory\CakeValidationFactory;
use Selective\Validation\Exception\ValidationException;
use Tuupola\Middleware\JwtAuthentication;
use Slim\Exception\HttpNotFoundException;
use \Firebase\JWT\JWT;

return function (App $app) {

   $app->options('/{routes:.+}', function (Request $request, Response $response) {
        // CORS Pre-Flight OPTIONS Request Handler
        return $response;
    });

    $app->get('/', function (Request $request, Response $response) {
       $response->getBody()->write('Hello world!');
      // echo json_encode($request->headers()->all());exit;
        return $response;
    });

    $app->get('/db-test', function (Request $request, Response $response) {
        $db = $this->get(PDO::class);
        $sth = $db->prepare("SELECT * FROM users limit 10");
        $sth->execute();
        $data = $sth->fetchAll(PDO::FETCH_ASSOC);
        $payload = json_encode($data);
        $response->getBody()->write($payload);
        return $response;
    });

    // logout
    $app->get('/logout', function ($request, $response) {
        \RKA\Session::destroy();
        return $response->withRedirect('/');
    });

    //user actions
    $app->post('/login', LoginAction::class);
    $app->post('/register', RegisterAction::class);	

    $app->group('/v1/user', function (Group $group) {
        $group->get('', ListUserAction::class);
        $group->post('/changePwd/{id}', ChangePwdAction::class);
        $group->get('/{id}', ViewUserAction::class);
        $group->post('/{id}', UpdateUserAction::class);
        $group->post('/{id}/{status}', DeleteUserAction::class);
    });

    $app->map(['GET', 'POST', 'PUT', 'DELETE', 'PATCH'], '/{routes:.+}', function ($request, $response) {
        throw new HttpNotFoundException($request);
    });
	
};
