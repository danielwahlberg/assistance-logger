<?php

   /**
    */
    $authenticateForRole = function ( $requiredRole = 'member' ) {
        return function () use ( $requiredRole) {
            $app = \Slim\Slim::getInstance();
            if(!isset($_SESSION['user'])) {
              error_log('Not logged in at all');
              $app->response->status(401);
              return;
            }

            $currentUser = $_SESSION['user'];
            if($currentUser === NULL || !$currentUser instanceof User) {
              error_log('Not properly logged in');
              $app->response->status(401);
              return;
            } elseif($currentUser->isAuthorizedTo($requiredRole)) {
              error_log('Logged in with right role');
              return;
            } else {
              error_log('Logged in with wrong role, '. $requiredRole .' needed, found '. $currentUser->role);
              $app->response->status(401);
            }
        };
    };

    class SecurityService {
      public static function login($username, $password) {
        $user = new User();
        $user->role = 'assistant';
        $_SESSION['user'] = $user;
      }
    }

    class User {
      public $name, $role;
      public function isAuthorizedTo($requiredRole) {
        if ($this->role == null) {
          return false;
        } elseif ($this->role == 'admin') {
          return true;
        } elseif ($this->role == 'assistant') {
          if($requiredRole == 'admin')
            return false;
          else {
            return true;
          }
        } else {
          return false;
        }
      }
    }

?>
