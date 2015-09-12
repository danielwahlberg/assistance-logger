medicineApp.factory('AuthService', function ($http, Session, $rootScope) {
  var authService = {};

  // Attempt to login. If successful, user role and name is stored in session.
  // Return $http object to be able to continue processing when response arrives
  authService.login = function (credentials) {
    return $http
      .post('api/v1/login', credentials)
        .success( function(response) {
          if(response.loginStatus == 'OK') { // Login successful
            Session.create(response.username, response.role, response.displayName);
            $rootScope.$broadcast('auth-success');
          } else
            console.log('login attempt failed');
            $rootScope.$broadcast('auth-failed');
        })
        .error( function() {
          console.log('login attempt could not be processed');
        });
  };

  authService.isAuthenticated = function () {
    return !!Session.userId;
  };

  authService.isAuthorized = function (authorizedRoles) {
    if (!angular.isArray(authorizedRoles)) {
      authorizedRoles = [authorizedRoles];
    }
    return (authService.isAuthenticated() &&
      authorizedRoles.indexOf(Session.userRole) !== -1);
  };

  authService.getCurrentUser = function() {
    return Session.getCurrentUser();
  };

  authService.logout = function() {
    return $http
      .post('api/v1/logout')
      .then(function(){
          Session.destroy();
      });

  }

  return authService;
});


medicineApp.service('Session', function () {

  this.getCurrentUser = function () {
    var userSerialized = window.sessionStorage.loggedInUser;
    if(userSerialized == null)
      return null;
    try {
      var user = JSON.parse(userSerialized);
      return user;
    } catch(e) { // Invalid serialized object stored in session
      return null;
    }

  }

  this.create = function (username, role, displayName) {
    this.user = {
      username : username,
      role : role,
      displayName : displayName
    };

    window.sessionStorage.loggedInUser = JSON.stringify(this.user);
  }

  this.destroy = function () {
    this.username = null;
    this.role = null;
    this.displayName = null;
  }
});
