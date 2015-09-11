medicineApp.factory('AuthService', function ($http, Session) {
  var authService = {};

  // Attempt to login. If successful, user role and name is stored in session.
  // Return $http object to be able to continue processing when response arrives
  authService.login = function (credentials) {
    return $http
      .post('api/v1/login', credentials)
        .success( function(response) {
          if(response.loginStatus == 'OK') { // Login successful
            Session.create(response.username, response.role, response.displayName);
          } else
            console.log('login attempt failed');
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
  this.create = function (username, role, displayName) {
    this.username = username;
    this.role = role;
    this.displayName = displayName;
  }

  this.destroy = function () {
    this.username = null;
    this.role = null;
    this.displayName = null;
  }
});
