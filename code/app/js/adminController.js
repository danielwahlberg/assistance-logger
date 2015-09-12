medicineApp.controller('AdminMainCtrl', function ($scope, $http) {
  $http.get('/api/v1/medicines/all/').success(function(data){
    $scope.medicines = data
  });

  $http.get('/api/v1/medicines/'+ new Date().yyyymmdd()).success(function(data){
    $scope.medicineDoses = data
  });


  $http.get('/api/v1/medicines/whenNeededMedicines/'+ new Date().yyyymmdd()).success(function(data){
    $scope.medicinesWhenNeeded = data;
  });

  $scope.addRowDoses = function() {
    var medicineDose = {
      medicineName: "",
      dose: "",
      time: ""
    };
    $scope.medicineDoses.push(medicineDose);
  }
});
