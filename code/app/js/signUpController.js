medicineApp.controller('SignUpCtrl', function ($scope, $http, AuthService) {
  $scope.registerUser = function() {
    $http.post('/api/v1/sign-up', $scope.userToRegister)
      .success(
        function(data, status, headers, config) {
          if(data.errorCode == 1) {
            $scope.signUpSuccessful = false;
            $scope.errorType = 'UserAlreadyExistsException';
          } else if(data.errorCode == 1) {
            $scope.signUpSuccessful = false;
            $scope.errorType = 'Exception';
          } else {
            $scope.signUpSuccessful = true;
          }
        })
      .error(
        function() {
          $scope.signUpSuccessful = false;
      });
  }
});
