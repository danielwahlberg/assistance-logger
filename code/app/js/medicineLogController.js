medicineApp.controller('MedicineLogCtrl', function ($scope, $http, $modal, $log) {

  $scope.selectedDate = new Date();
  $scope.selectedWhenNeededMedication = null;
  $scope.saveDisabled = false;
  $scope.storeComplete = false;
  $scope.medicationToStore = []; // Updated by function openSaveConfirmation

  /*
   * Retrieve initial data
   */

  // Retrieve medicines, their doses and possible given status from backend
  $http.get('/api/v1/medicines/'+ $scope.selectedDate.yyyymmdd()).success(function(data){
    $scope.medicines = data
  });

  // Retrieve when needed medicines and their doses from backend
  $http.get('/api/v1/medicines/whenNeededMedicines/'+ $scope.selectedDate.yyyymmdd()).success(function(data){
    $scope.whenNeededMedicines = data
  });

  // Retrieve log of given when needed medicines from backend
  $http.get('/api/v1/medicines/whenNeededLog/'+ $scope.selectedDate.yyyymmdd()).success(function(data){
    $scope.whenNeededMedicineLog = data
  });

  // Retrieve list of assistants
  $http.get('/api/v1/assistants').success(function(data){
    $scope.assistants = data
  });

  /*
   * Functions handling user's actions
   */

  // Update medicine list when date is changed
  $scope.dateChanged = function(){
    $scope.selectedDate = new Date($scope.dt);
    if($scope.selectedDate.yyyymmdd() != new Date().yyyymmdd())
      $scope.saveDisabled = true; // Disable saving for other days than today (this is due to the fact that saving is done for current time, not the selected date)
    else
      $scope.saveDisabled = false;
    $http.get('/api/v1/medicines/'+ $scope.selectedDate.yyyymmdd()).success(function(data){
      $scope.medicines = data
    });
  };


  // Get the currently selected medicine in the "when needed medicine list"
  $scope.getCurrentWhenNeededMedicine = function() {
    var defaultText = "Medicin vid behov";

    if($scope.selectedWhenNeededMedication == null)
      return defaultText;
    else
      return $scope.selectedWhenNeededMedication.medicineName;
  };

  $scope.selectWhenNeededMedicine = function(medicine) {
    if($scope.currentAssistant==null) {
      alert('Välj assistent först!');
      return 1;
    }
    $scope.selectedWhenNeededMedication = medicine;
    $scope.selectedWhenNeededMedication.givenBy = $scope.currentAssistant.name; // Show name of assistant in med. list
    $scope.selectedWhenNeededMedication.givenByAssistantId = $scope.currentAssistant.id
    $scope.storeComplete=false;
  };

  // Handle click in medicine's checkbox
  $scope.toggleMedicineGiven = function(selectedMedicine) {
    if($scope.currentAssistant==null) {
      selectedMedicine.isGiven = false;
      alert('Välj assistent först!');
      return 1;
    }

    if(selectedMedicine.isGiven) {
      selectedMedicine.givenBy = $scope.currentAssistant.name; // Show name of assistant in med. list
      selectedMedicine.givenByAssistantId = $scope.currentAssistant.id
      $scope.storeComplete=false;
    } else {
      selectedMedicine.givenBy = null;
      // TODO Loop over medicationToStore and remove previously selected medicine
    }
  };


  /*
   * Functions handling the save dialog
   */

   $scope.openSaveConfirmation = function (size) {
       if($scope.currentAssistant == null) {
         // Assistant has not been selected
         alert('Välj assistent först');
         return 1;
       }

       var modalInstance = $modal.open({
         animation: $scope.animationsEnabled,
         templateUrl: 'saveConfirmation.html',
         controller: 'SaveConfirmationModalCtrl',
         size: size,
         resolve: {
           medicationToStore: function () {
             // Pick out selected, un-saved, items in the medicine list
             $scope.medicationToStore = []; // Reset possible old save attempts
             for(i=0; i < $scope.medicines.length; i++){
               if($scope.medicines[i].isGiven && !$scope.medicines[i].givenMedicineStored) {
                 $scope.medicationToStore.push($scope.medicines[i]);
               }
             }

             if($scope.selectedWhenNeededMedication != null) {
               $scope.medicationToStore.push($scope.selectedWhenNeededMedication);
             }
             return $scope.medicationToStore;
           }
         }
       });

     modalInstance.result.then(function () {
       $scope.saveChanges();
     }, function () {
       $log.info('Modal dismissed at: ' + new Date());
     });
   };

   $scope.saveChanges = function() {
     $http.post('/api/v1/medication', $scope.medicationToStore).success(
       function(data, status, headers, config) {
         $scope.storeComplete=true;
         // Set "givenMedicineStored" to true for the medicines in the list which were given
         for(i=0; i < $scope.medicines.length; i++){
           if($scope.medicines[i].isGiven)
            $scope.medicines[i].givenMedicineStored = true;
         }

         if($scope.selectedWhenNeededMedication != null) {
           // If a "when needed" medicine has been given, add it to the list of stored such medicines
           $scope.whenNeededMedicineLog.push($scope.selectedWhenNeededMedication);
           // ...and reset dropdown for when needed medicines
           $scope.selectedWhenNeededMedication = null;
         }
       }
     );
    };

   $scope.cancelSave = function () {
     $modalInstance.dismiss('cancel');
   };

});

medicineApp.controller('SaveConfirmationModalCtrl', function ($scope, $modalInstance, medicationToStore) {

  $scope.medicationToStore = medicationToStore;

  $scope.ok = function () {
    $modalInstance.close(); // OK
  };

  $scope.cancel = function () {
    $modalInstance.dismiss('cancel');
  };
});
