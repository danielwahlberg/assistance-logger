medicineApp.controller('AdminMainCtrl', function ($scope, $http) {
  $http.get('/api/v1/medicines/all/').success(function(data){
    $scope.medicines = data
  });

  // Retrieve medicine doses valid today
  $http.get('/api/v1/medicines/'+ new Date().yyyymmdd()).success(function(data){
    $scope.medicineDoses = data;

     // Store a copy to be able to compare with when saving (to avoid sending unaltered fields; using copy as Angular passes reference otherwise)
    $scope.previouslyStoredMedicineDoses = angular.copy(data);
  });


  $http.get('/api/v1/medicines/whenNeededMedicines/'+ new Date().yyyymmdd()).success(function(data){
    $scope.medicinesWhenNeeded = data;

    // Store a copy to be able to compare with when saving (to avoid sending unaltered fields; using copy as Angular passes reference otherwise)
   $scope.previouslyStoredWhenNeededDoses = angular.copy(data);
  });

  $scope.addRowDoses = function() {
    var medicineDose = {
      medicineName: "",
      dose: "",
      time: ""
    };
    $scope.medicineDoses.push(medicineDose);
  };

  $scope.regularDoseChangesSaved = false;
  $scope.regularDoseChangeMade = function() {
    $scope.regularDoseChangesSaved = false;
  };

  $scope.whenNeededDoseChangeMade = function() {
    $scope.whenNeededDoseChangesSaved = false;
  };

  $scope.saveRegularDose = function() {
    $scope.saveDose(false);
  };

  $scope.saveWhenNeededDose = function () {
    $scope.saveDose(true);
  };

  $scope.saveDose = function (storingWhenNeededDose) {
    var medicine, storedMedicine;
    var changesToStore = new Array();

    if(storingWhenNeededDose) {
        var doses = $scope.medicinesWhenNeeded;
        var storedDoses = $scope.previouslyStoredWhenNeededDoses;
    } else {
      var doses = $scope.medicineDoses;
      var storedDoses = $scope.previouslyStoredMedicineDoses;
    }

    // Loop over medicine doses to detect changes
    for (i = 0; i < doses.length; i++) {
      medicine = doses[i];
      storedMedicine = storedDoses[i];

      if(medicine.dose != storedMedicine.dose
        || medicine.time != storedMedicine.time ) {
          medicine.giveWhenNeeded = storingWhenNeededDose;
          if(medicine.time=='')
            medicine.time = null; // Avoid storing empty field as time 0:00:00
          changesToStore.push(medicine);
      }
    }

    $http.post('/api/v1/medicines/storeDoses', changesToStore)
      .success(function(data, status){
        if(storingWhenNeededDose)
          $scope.whenNeededDoseChangesSaved = true;
        else
          $scope.regularDoseChangesSaved = true;
      });
  };
});
