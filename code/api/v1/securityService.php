<?php

   /**
    * Slim Middleware: Check user currently logged in user has access to this resource
    * (put here just to stop Slim from complaining)
    */
    $authenticateForRole = function ( $requiredRole = 'patientAdmin' ) {
        return function () use ( $requiredRole) {
            $app = \Slim\Slim::getInstance();
            if(!isset($_SESSION['user'])) {
              if($app->request->headers->get('x-session-token')!=null) {
                error_log('No regular session - checking provided "remember me" tokens');
                $tokenLoginSuccessful = SecurityService::validateSessionToken($app->request->headers->get('x-session-token'));
              } else {
                  $tokenLoginSuccessful = false; // There is not even a http header to try this for..
              }
              if(!$tokenLoginSuccessful) {
                error_log('Not logged in at all');
                $app->response->status(401);
              } else {
                error_log('Token validation successful ');
              }
                return;
            }

            $currentUser = $_SESSION['user'];
            if($currentUser === NULL || !$currentUser instanceof User) {
              $app->response->status(401);
              return;
            } elseif(SecurityService::isAuthorizedTo($currentUser, $requiredRole)) {
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

      /**
        * Generates and stored a random session token for the given user.
        * This token will be possible to use instead of username/password
        * to log in as the user.
        * @return Base64 encoded concatenation of $username : token (used as HTTP header x-session-token) or NULL if user doesn't exist
        */
      public static function storeSessionToken($username) {
          $token = bin2hex(openssl_random_pseudo_bytes(25)); // bin2hex for avoid possible issues with saving binary data to mysql
          $sql = "UPDATE user SET token = '$token', tokenValidThrough=DATE_ADD(NOW(), INTERVAL 7 DAY) WHERE email = '$username'";
          $db = connect_db();
          $db->query($sql);
          if($db->affected_rows == 0) {
            error_log("Tried to store session token for nonexisting user $username");
            return null;
          }
          return base64_encode($username .':'. $token);
      }

      /**
       * Tries to log in using only HTTP Header x-session-token
       * @param $tokenHttpHeader Base64 encoded username and token, as returned by "storeSessionToken"
       * @return array with user details if token is valid and non-expired, false otherwise
       */
      public static function validateSessionToken($tokenHttpHeader) {
        $strUsernameAndToken = base64_decode($tokenHttpHeader);
        $arrUsernameAndToken = split(':', $strUsernameAndToken);
        $sql = "SELECT email, role FROM user WHERE token = '{$arrUsernameAndToken[1]}' AND tokenValidThrough > NOW()";

        $db = connect_db();
        $result = $db->query($sql);
        if($row = $result->fetch_array(MYSQLI_ASSOC)) {
          // Found user with the provided token in database. Log user in.

          $user = SecurityService::getUserFromResultRow($row);
          $sessionToken = SecurityService::storeSessionToken($arrUsernameAndToken[0]);
          $_SESSION['user'] = $user;
          $loginResult = array(
            'loginStatus' => 'OK',
            'username' => $user->username,
            'displayName' => $user->name,
            'role' => $user->role,
            'sessionToken' => $sessionToken
          );
          // TODO Extend lifetime of token on each successful verification against it?
          return $loginResult;
        } else {
          return false;
        }
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
            $user = SecurityService::getUserFromResultRow($row);
            $sessionToken = SecurityService::storeSessionToken($username);
            $_SESSION['user'] = $user;
            $loginResult = array(
              'loginStatus' => 'OK',
              'username' => $user->username,
              'displayName' => $user->name,
              'role' => $user->role,
              'sessionToken' => $sessionToken
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
        // TODO Remove token from DB
      }

      public static function getUserFromResultRow($row) {
        $user = new User();
        $user->username = $row['email'];
        $user->name = $row['email'];
        $user->role = strtolower($row['role']);
        return $user;
      }


      public static function isAuthorizedTo(User $user, $requiredRole) {
        $requiredRole = strtolower($requiredRole);
        switch($requiredRole) {
          case 'systemadmin':
            return $user->role == 'systemadmin';
          case 'patientadmin':
            return $user->role == 'systemadmin' || $user->role == 'patientadmin';
          case 'assistant':
            return $user->role == 'systemadmin'
                || $user->role == 'patientadmin'
                || $user->role == 'assistant';
          default:
            return false;
        }
      }
    }
?>
