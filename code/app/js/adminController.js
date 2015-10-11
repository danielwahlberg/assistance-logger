medicineApp.controller('AdminMainCtrl', function ($scope, $http) {
  $http.get('/api/v1/medicines/all/').success(function(data){
    $scope.medicines = data
  });

  $http.get('/api/v1/medicines/'+ new Date().yyyymmdd()).success(function(data){
    $scope.medicineDoses = data;
    $scope.previouslyStoredMedicineDoses = angular.copy(data); // Store a copy to be able to compare with when saving (to avoid sending unaltered fields)
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
  };

  $scope.saveRegularDose = function () {
    var medicine, storedMedicine;
    var changesToStore = new Array();

    // Loop over medicine doses to detect changes
    for (i = 0; i < $scope.medicineDoses.length; i++) {
      medicine = $scope.medicineDoses[i];
      storedMedicine = $scope.previouslyStoredMedicineDoses[i];

      if(medicine.dose != storedMedicine.dose
        || medicine.time != storedMedicine.time ) {
          changesToStore.push(medicine);
      }
    }

    $http.post('/api/v1/medicines/storeDoses', changesToStore)
      .success(function(data, status){
        $scope.regularDoseChangesSaved = true;
      });
  };
});
