<?php

use Slim\Http\Response as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Routing\RouteCollectorProxy;

use EcclesiaCRM\dto\SystemURLs;
use Slim\Views\PhpRenderer;
use EcclesiaCRM\UserQuery;
use EcclesiaCRM\Token;
use EcclesiaCRM\Emails\ResetPasswordTokenEmail;
use EcclesiaCRM\Emails\ResetPasswordEmail;
use EcclesiaCRM\TokenQuery;
use EcclesiaCRM\dto\SystemConfig;

if (SystemConfig::getBooleanValue('bEnableLostPassword')) {

    $app->group('/password', function (RouteCollectorProxy $group) {

        $group->get('/', function (Request $request, Response $response, array $args) {
            $renderer = new PhpRenderer('templates/password/');
            return $renderer->render($response, 'enter-username.php', ['sRootPath' => SystemURLs::getRootPath()]);
        });

        $group->post('/reset/{username}', function (Request $request, Response $response, array $args) {
            $userName = $args['username'];
            if (!empty($userName)) {
                $user = UserQuery::create()->findOneByUserName(strtolower(trim($userName)));
                if (!empty($user) && !empty($user->getEmail())) {
                    $token = new Token();
                    $token->build("password", $user->getId());
                    $token->save();
                    $email = new ResetPasswordTokenEmail($user, $token->getToken());
                    if (!$email->send()) {
                        $Logger = $this->get('Logger');
                        $Logger->error($email->getError());
                    }
                    return $response->withStatus(200)->withJson(['status' => "success"]);
                } else {
                    $Logger = $this->get('Logger');
                    $Logger->error("Password reset for user " . $userName . " found no user");
                }
            } else {
                $Logger = $this->get('Logger');
                $Logger->error("Password reset for user with no username");
            }
            return $response->withStatus(404);
        });

        $group->get('/set/{token}', function (Request $request, Response $response, array $args) {
            $renderer = new PhpRenderer('templates/password/');
            $token = TokenQuery::create()->findPk($args['token']);
            $haveUser = false;
            if ($token != null && $token->isPasswordResetToken() && $token->isValid()) {
                $user = UserQuery::create()->findPk($token->getReferenceId());
                $haveUser = empty($user);
                if ($token->getRemainingUses() > 0) {
                    $token->setRemainingUses($token->getRemainingUses() - 1);
                    $token->save();
                    $password = $user->resetPasswordToRandom();
                    $user->save();
                    $email = new ResetPasswordEmail($user, $password);
                    if ($email->send()) {
                        return $renderer->render($response, 'password-check-email.php', ['sRootPath' => SystemURLs::getRootPath()]);
                    } else {
                        $Logger = $this->get('Logger');
                        $Logger->error($email->getError());
                        throw new \Exception($email->getError());
                    }
                }
            }

            return $renderer->render($response, "/../404.php", array("message" => gettext("Unable to reset password")));
        });

    });
}
