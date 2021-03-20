medicineApp.controller('ApplicationController', function ($scope, AuthService) {

  $scope.currentUser = AuthService.getCurrentUser();

  //$scope.userRoles = USER_ROLES;
  $scope.isAuthorized = AuthService.isAuthorized;

  $scope.setCurrentUser = function (user) {
    $scope.currentUser = user;
  };

  Date.prototype.yyyymmdd = function() {
     var yyyy = this.getFullYear().toString();
     var mm = (this.getMonth()+1).toString(); // getMonth() is zero-based
     var dd  = this.getDate().toString();
     return yyyy +'-'+ (mm[1]?mm:"0"+mm[0]) +'-'+ (dd[1]?dd:"0"+dd[0]); // padding
    };

});


medicineApp.controller('MainMenuCtrl', function ($scope, $location) {

    $scope.isActive = function (viewLocation) {
        return viewLocation === $location.path();
    };
});

medicineApp.controller('LoginCtrl', function ($scope, $http, AUTH_EVENTS, AuthService, $rootScope, $location) {

  // Initialize
  $scope.loginFailed = false;
  $scope.showLoginRequiredMessage = false;

  $rootScope.$on('not-auth-event', function(event, args){
    console.log("Tried to access a resource that requires login");
    $scope.showLoginRequiredMessage = true;// This stuff is not working
  });

  $rootScope.$on('auth-failed', function(event, args){
    console.log("Login failed");
    $scope.loginFailed = true;
    $scope.showLoginRequiredMessage = false;
  });
  $rootScope.$on('auth-success', function(event, args){
    console.log("Login succeeded");
    $location.path("/viewNews"); // Login succeeded, redirect to news page
    $scope.currentUser = AuthService.getCurrentUser();
    $scope.loginFailed = false;
    $scope.showLoginRequiredMessage = false;

  });

  $scope.attemptLogin = function() {
    AuthService.login($scope.credentials);
  }
});

medicineApp.controller('LogoutCtrl', function($scope, AuthService){
  AuthService.logout().then(function(httpResponse){
    $scope.setCurrentUser(null);
  });
});


medicineApp.controller('DatePickerController', function ($scope, AuthService) {
  // Datepicker settings
  $scope.today = function() {
    $scope.dt = new Date();
  };
  $scope.today();

  $scope.clear = function () {
    $scope.dt = null;
  };


  $scope.toggleMin = function() {
    $scope.minDate = $scope.minDate ? null : new Date();
  };
  $scope.toggleMin();

  $scope.open = function($event) {
    $event.preventDefault();
    $event.stopPropagation();

    $scope.opened = true;
  };

  $scope.dateOptions = {
    formatYear: 'yy',
    startingDay: 1
  };


  $scope.minDate = null;
  $scope.maxDate = new Date();

  var tomorrow = new Date();
  tomorrow.setDate(tomorrow.getDate() + 1);
  var afterTomorrow = new Date();
  afterTomorrow.setDate(tomorrow.getDate() + 2);
  $scope.events =
    [
      {
        date: tomorrow,
        status: 'full'
      },
      {
        date: afterTomorrow,
        status: 'partially'
      }
    ];

  $scope.getDayClass = function(date, mode) {
    if (mode === 'day') {
      var dayToCheck = new Date(date).setHours(0,0,0,0);

      for (var i=0;i<$scope.events.length;i++){
        var currentDay = new Date($scope.events[i].date).setHours(0,0,0,0);

        if (dayToCheck === currentDay) {
          return $scope.events[i].status;
        }
      }
    }

    return '';
  };
});

medicineApp.controller('ViewLogCtrl', ['$scope', '$routeParams',
  function($scope, $routeParams) {
    alert($routeParams.logId);
    $scope.logId = $routeParams.logId;
}]);

medicineApp.controller('ViewLogListCtrl', ['$scope', '$routeParams', '$http',
  function($scope, $routeParams, $http) {
    $scope.message = 'Do nothing to start with...';
    $http.get('/app/data/logEntries.json').success(
      function(data) {
        $scope.entries = data;
      }
      );

}]);

medicineApp.controller('SaveConfirmationModalCtrl', function ($scope, $modalInstance, medicationToStore) {

  $scope.medicationToStore = medicationToStore;

  $scope.ok = function () {
    $modalInstance.close(); // OK
  };

  $scope.cancel = function () {
    $modalInstance.dismiss('cancel');
  };
});
