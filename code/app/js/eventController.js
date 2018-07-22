medicineApp.controller('EventCtrl', function ($scope, $http, $modal, $log) {

  $scope.errors = new Array();

  // Retrieve statistical data
  $http.get('/api/v1/event/'+ (new Date()).yyyymmdd()).success(function(data) {
    $scope.events = data;
  });

  $http.get('/api/v1/event/types/').success(function(data) {
    $scope.eventTypes = data;
  });

  $http.get('/api/v1/assistants').success(function(data){
    $scope.assistants = data
  });

  $scope.saveEvent = function() {
    $http.post('/api/v1/event/', $scope.eventToStore)
    .success(
      function(data, status, headers, config) {

        $scope.saveEventSuccessful = true;

        $scope.events.push( // Dynamically update table with stored feeding
          {
            "eventId" : data, // The newly created id will be returned from backend (not used in model/view currently)
            "timeSinceLastLog": "Reload to calculate",
            "eventStoredAt":"Just now",
            "eventTypeName" : $scope.eventToStore.eventType.name,
            "duration" : $scope.eventToStore.duration,
            "description" : $scope.eventToStore.description
          }
        );
      }
    )
    .error(function() {
      $scope.errors.push('Fel inträffade på servern');
    });
  };

});
