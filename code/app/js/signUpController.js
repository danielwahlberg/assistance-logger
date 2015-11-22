medicineApp.controller('SignUpCtrl', function ($scope, $http, AuthService) {
  $scope.registerUser = function() {
    $http.post('/api/v1/sign-up', $scope.userToRegister)
      .success(
        function(data, status, headers, config) {
          $scope.signUpSuccessful = true;
        })
      .error(
        function() {
          $scope.signUpSuccessful = false;
      });
  }
});
