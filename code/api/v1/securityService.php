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
                $arrTokenLoginResult = SecurityService::validateSessionToken($app->request->headers->get('x-session-token'));
                $tokenLoginSuccessful = $arrTokenLoginResult['loginSuccessful'];
                error_log('Token login result: '. ($tokenLoginSuccessful?'success':'failure'));
              } else {
                  $tokenLoginSuccessful = false; // There is not even a http header to try this for..
              }
            }

            if(!isset($_SESSION['user']) || empty($_SESSION['user'])){
              $app->halt(401, '');
              return false; // User still not set in session; stop executing to avoid issues with using unset user object
            }
            $currentUser = $_SESSION['user'];
            $app->currentUser = $currentUser;

            if($currentUser === NULL || !$currentUser instanceof User) {
              $app->halt(401, '"message":"Not logged in"');
            } elseif(!SecurityService::isAuthorizedTo($currentUser, $requiredRole)) {
              error_log('Logged in with wrong role, '. $requiredRole .' needed, found '. $currentUser->role);
              $app->halt(401, '"message":"Not sufficient privileges to view resource"');
            } else {
              // Do nothing, user has sufficient authorization
            }
        };
    };

    class User {
      public $username, $name, $role, $patientId;
    }

    class UserAlreadyExistsException extends Exception {}

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
       * Tries to log in using only HTTP Header x-session-token. Sets session variable 'user' if login was successful.
       * @param $tokenHttpHeader Base64 encoded username and token, as returned by "storeSessionToken"
       * @return array with login result in element 'loginSuccessful' and, if token is valid and non-expired, user details
       */
      public static function validateSessionToken($tokenHttpHeader) {
        $strUsernameAndToken = base64_decode($tokenHttpHeader);
        $arrUsernameAndToken = explode(':', $strUsernameAndToken);
        if(!is_array($arrUsernameAndToken) || count($arrUsernameAndToken)<2) {
          error_log('Tried to validate malformed token');
          return array('loginSuccessful'=> false);
        }

        $sql = "SELECT email, role, patient_id FROM user WHERE token = '{$arrUsernameAndToken[1]}' AND tokenValidThrough > NOW()";

        $db = connect_db();
        $result = $db->query($sql);
        if($row = $result->fetch_array(MYSQLI_ASSOC)) {
          // Found user with the provided token in database. Log user in.

          $user = SecurityService::getUserFromResultRow($row);
          // $sessionToken = SecurityService::storeSessionToken($arrUsernameAndToken[0]); // Why did I do this..? Won't this invalidate the local token stoed by user?!
          $sessionToken = $arrUsernameAndToken[1];

          $_SESSION['user'] = $user;
          $loginResult = array(
            'loginSuccessful' => true,
            'username' => $user->username,
            'displayName' => $user->name,
            'role' => $user->role,
            'sessionToken' => $sessionToken
          );
          // TODO Extend lifetime of token on each successful verification against it?
          return $loginResult;
        } else {
          $loginResult = array(
            'loginSuccessful' => false);
          return $loginResult;
        }
      }

      public static function login($username, $password) {
        $db = connect_db();
        $usernameEscaped = $db->real_escape_string($username);
        $sql = "SELECT u.email, u.role, u.password, u.patient_id from user u where email = '{$username}'";

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
        $user->patientId = $row['patient_id'];
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

      public function signUp($arrUserInfo) {
        $db = connect_db();
        if(isset($arrUserInfo['patientName']) && $arrUserInfo['patientName'] != '') {
          // Patient name provided; create a new patient which the signed up user becomes admin for
          $sqlPatient = "INSERT INTO patient (name) VALUES('".$db->real_escape_string($arrUserInfo['patientName'])."')";

          if($db->query($sqlPatient) === TRUE ) {
            $intPatientId = $db->insert_id;
            $strRole = 'PATIENTADMIN';
          }
        }
        if(!isset($intPatientId)) {
          // No new patient is created; connect new user to patient manually later
          // (using !isset to take care of cases when patient creation query fails)
          $strRole = 'ASSISTANT';
          $intPatientId = 'NULL';
        }


        $sqlUser = "INSERT INTO user (email, password, role, patient_id) VALUES('".$db->real_escape_string($arrUserInfo['email'])."','". SecurityService::getPasswordHash($arrUserInfo['newPassword']) ."','{$strRole}',{$intPatientId})";

        if($db->query($sqlUser) === TRUE) {
          $intUserId = $db->insert_id; // Retrieve inserted ID if query was successful

          if($intPatientId != 'NULL') {
              // Automatically create a dummy assistant for the new patient
              $sql = "INSERT INTO assistant (firstName, startDate, user_id, patient_id) VALUES('Assistent', NOW(), {$intUserId}, {$intPatientId})";
              $db->query($sql);
          }

        } elseif($db->errno == 1062) {
          throw new UserAlreadyExistsException();
        } else {
            throw new Exception('User creation failed with code '. $db->errno .', message: '. $db->error);
        }
        return array(
          'patient_id' => $intPatientId,
          'user_id' => $intUserId,
          'role' => $strRole
        );

      }
    }
?>
