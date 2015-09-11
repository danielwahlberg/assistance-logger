<?php

   /**
    * Slim Middleware: Check user currently logged in user has access to this resource
    * (put here just to stop Slim from complaining)
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
            } elseif(SecurityService::isAuthorizedTo($currentUser, $requiredRole)) {
              error_log('Logged in with right role');
              return;
            } else {
              error_log('Logged in with wrong role, '. $requiredRole .' needed, found '. $currentUser->role);
              $app->response->status(401);
            }
        };
    };

    class User {
      public $username, $name, $role;
    }


    class SecurityService {

      /** Create a hash for a password for storage in database */
      public static function getPasswordHash($password) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        return $passwordHash;
      }

      public static function login($username, $password) {
        $db = connect_db();
        $usernameEscaped = $db->real_escape_string($username);
        $sql = "SELECT u.email, u.role, u.password from user u where email = '{$username}'";

        $result = $db->query( $sql );
        if($result === FALSE) { // Error occured
          error_log($db->error);
          return array('loginStatus'=>'ERROR');
        }
        if ($row = $result->fetch_array(MYSQLI_ASSOC) ) {
          if(!password_verify($password, $row['password'])) {
            // Passwords mismatch
            return array('loginStatus'=>'FAIL');
          } else {
            $user = new User();
            $user->username = $row['email'];
            $user->name = $row['email'];
            $user->role = $row['role'];
            $_SESSION['user'] = $user;
            $loginResult = array(
              'loginStatus' => 'OK',
              'username' => $user->username,
              'displayName' => $user->name,
              'role' => $user->role
            );
          }
          return $loginResult;

        } else {
          // No user found
          return array('loginStatus'=>'FAIL');
        }
      }

      public static function logout() {
        unset($_SESSION['user']);
      }


      public static function isAuthorizedTo(User $user, $requiredRole) {
        if ($user->role == null) {
          return false;
        } elseif ($user->role == 'admin') {
          return true;
        } elseif ($user->role == 'assistant') {
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
