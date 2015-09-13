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
  $scope.showLoginRequiredMessage = true;

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
    $scope.loginFailed = false;
    $scope.showLoginRequiredMessage = false;
  });

  $scope.attemptLogin = function() {
    console.log("Remember me? "+ $scope.credentials.rememberMe);
    AuthService.login($scope.credentials).then(function(httpResponse) {
      $location.path("/logMedicine")
    })
  }
});

medicineApp.controller('LogoutCtrl', function($scope, AuthService){
  AuthService.logout().then(function(httpResponse){
    $scope.setCurrentUser(null);
  });
});

medicineApp.controller('CreateLogCtrl', function ($scope, $http, $modal, $log) {

  $scope.medicationToStore = [];
  $scope.whenNeededMedicationToStore = null;
  $scope.selectedDate = new Date();

  $http.get('/api/v1/medicines/'+ $scope.selectedDate.yyyymmdd()).success(function(data){
    $scope.medicines = data
  });
  $http.get('/api/v1/medicines/whenNeededMedicines/'+ $scope.selectedDate.yyyymmdd()).success(function(data){
    $scope.whenNeededMedicines = data
  });
  $http.get('/api/v1/medicines/whenNeededLog/'+ $scope.selectedDate.yyyymmdd()).success(function(data){
    $scope.whenNeededMedicineLog = data
  });

  $http.get('/api/v1/assistants').success(function(data){
    $scope.assistants = data
  });

  $scope.getCurrentWhenNeededMedicine = function() {
    var defaultText = "Medicin vid behov";

    if($scope.whenNeededMedicationToStore == null)
      return defaultText;
    else
      return $scope.whenNeededMedicationToStore.medicineName;
  };

  $scope.selectWhenNeededMedicine = function(medicine) {
    $scope.whenNeededMedicationToStore = medicine;
  };

  // Update medicine list when date is changed
  $scope.dateChanged = function(){
    $scope.selectedDate = new Date($scope.dt);
    $scope.medicationToStore = []; // Reset medicines to store
    if($scope.selectedDate.yyyymmdd() != new Date().yyyymmdd())
      $scope.saveDisabled = true; // Disable saving for other days than today (this is due to the fact that saving is done for current time, not the selected date, which is kind of confusing)
    else
      $scope.saveDisabled = false;
    $http.get('/api/v1/medicines/'+ $scope.selectedDate.yyyymmdd()).success(function(data){
      $scope.medicines = data
    });
  };

  //$scope.currentAssistant = $scope.assistants[0];

  $scope.toggleMedicineGiven = function(selectedMedicine) {
    if($scope.currentAssistant==null) {
      selectedMedicine.isGiven = false;
      alert('Välj assistent först!');
      return 1;
    }

    if(selectedMedicine.isGiven) {
      selectedMedicine.givenBy = $scope.currentAssistant.name;
      selectedMedicine.givenByAssistantId = $scope.currentAssistant.id
      $scope.medicationToStore.push(selectedMedicine);
      $scope.storeComplete=false;
    } else {
      selectedMedicine.givenBy = null;
      // TODO Loop over medicationToStore and remove previously selected medicine
    }

  };

  $scope.isDisabled = function(selectedMedicine) {
    selectedMedicine.givenMedicineStored && selectedMedicine.isGiven;
  }

  $scope.storeComplete = false;

  $scope.cancelSave = function () {
    $modalInstance.dismiss('cancel');
  };

  $scope.openSaveConfirmation = function (size) {
      if($scope.currentAssistant == null) {
        // Assistant has not been selected
        alert('Välj assistent först');
        return 1;
      }

      if($scope.whenNeededMedicationToStore != null){
        $scope.whenNeededMedicationToStore.givenBy = $scope.currentAssistant.name;
        $scope.whenNeededMedicationToStore.givenByAssistantId = $scope.currentAssistant.id
        $scope.medicationToStore.push($scope.whenNeededMedicationToStore);
      }
      var modalInstance = $modal.open({
        animation: $scope.animationsEnabled,
        templateUrl: 'saveConfirmation.html',
        controller: 'SaveConfirmationModalCtrl',
        size: size,
        resolve: {
          medicationToStore: function () {
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
        for (i = 0; i < $scope.medicines.length; i++) {
          for (j = 0; j < $scope.medicationToStore.length; j++) {
            if($scope.medicines[i].id == $scope.medicationToStore[j].id)
              $scope.medicines[i].givenMedicineStored = true;
          }
        }
        $scope.medicationToStore = []; // Empty array; nothing more to store
        $scope.storeComplete=true;
        $scope.whenNeededMedicineLog.push($scope.whenNeededMedicationToStore);
        $scope.whenNeededMedicationToStore = null;
      });

  }


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
