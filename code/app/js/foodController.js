medicineApp.controller('FoodCtrl', function ($scope, $http, $modal, $log) {

  $http.get('/api/v1/food/feeding').success(function(data){
    $scope.meals = data
    $scope.sumAmount = 0;
    for(i=0; i<$scope.meals.length; i++) {
    	$scope.sumAmount += +$scope.meals[i].amount;
    }
  });

  $http.get('/api/v1/assistants').success(function(data){
    $scope.assistants = data
  });

  $scope.feedingToStore = {};
  $scope.feedingToStore.givenTime = new Date(); // Store "now" as feeding time by default
  $scope.timeCompensated = false; // When saving, keep track of that we haven't yet compensated for local time zone

  $scope.getStartDate = function(){
    var now = new Date();
    if(now.getHours()<6) {
      var yesterday = new Date();
      yesterday.setDate(yesterday.getDate()-1);
      return yesterday; // Before 06:00, we show the previous day's feeding log
    } else {
      return now; // At 06:00 and thereafter we show today's feeding log
    }
  };

  $scope.getEndDate = function(){
    var endDate = new Date();
    return endDate.setDate($scope.startDate.getDate() + 1); // End date is 1 day after start date
  };

  $scope.startDate = $scope.getStartDate();
  $scope.endDate = $scope.getEndDate();


  // TODO Get this from API instead
  $scope.foodTypes = [
	{"name": "Mat",
   "icon": "glyphicon-cutlery",
	 "id": "1"
	},
	{"name": "Sondmat",
   "icon": "glyphicon-baby-formula",
	 "id": "2"
	},
	{"name": "Dryck",
   "icon": "glyphicon-tint",
	 "id": "3"
	}
  ];

  $scope.changeMade = function() {
  	$scope.allChangesSaved = false;
  	$scope.errors = [] // Reset errors
  }

  $scope.selectFoodType = function(foodType) {
  	$scope.feedingToStore.foodType = foodType;
  	$scope.changeMade();
  };

  $scope.selectAssistant = function(assistant)  {
  	$scope.feedingToStore.assistant = assistant;
  	$scope.changeMade();
  };

  $scope.getCurrentAssistantName = function(){
  	var defaultText = "Assistent";
  	if($scope.feedingToStore.assistant == null)
  		return defaultText;
  	else
  		return $scope.feedingToStore.assistant.name;
  };


  $scope.allChangesSaved = false;

  $scope.errors = [];

  $scope.saveChanges = function() {
  	//if($scope.feedingToStore.assistant == null){
    if($scope.currentAssistant == null){
  		$scope.errors.push("Du måste välja assistent innan du sparar");
  		return 1;
  	} else {
      $scope.feedingToStore.assistant = $scope.currentAssistant;
    }

    // Make sure local time is sent to server (without this, time zone info is removed when date is implicitly JSON.stringify:ed (done when angular sends using $http.post))
    if(!$scope.timeCompensated) {
      $scope.feedingToStore.givenTime.setHours($scope.feedingToStore.givenTime.getHours() - $scope.feedingToStore.givenTime.getTimezoneOffset() / 60);
      $scope.timeCompensated = true; // Don't do this compensation again if the time hasn't changed
    }

    // If feeding seems to be stored before it was given, assume it was given the day before (e.g. given at 23:30 the 1st when stored 01:00 the 2nd)
    if($scope.feedingToStore.givenTime.getHours() > (new Date()).getHours()) {
      $scope.feedingToStore.givenTime.setDate($scope.feedingToStore.givenTime.getDate()-1);
    }



    /*
    $scope.feedingToStore.givenTime.setDate((new Date()).getDate()); // Only time is set by default; set today's date
    $scope.feedingToStore.givenTime.setMonth((new Date()).getMonth());
    $scope.feedingToStore.givenTime.setYear((new Date()).getFullYear()); */

    $http.post('/api/v1/food/feeding', $scope.feedingToStore)
    .success(
      function(data, status, headers, config) {
      	$scope.allChangesSaved = true;
      	$scope.errors = [];
      	$scope.meals.push( // Dynamically update table with stored feeding
	      	{
            "id" : data, // The newly created id will be returned from backend
	      		"name" : $scope.feedingToStore.foodType.name,
	      		"amount" : $scope.feedingToStore.amount,
	      		"givenBy" : $scope.feedingToStore.assistant.name
	      	}
      	);
      	$scope.sumAmount += +$scope.feedingToStore.amount;
	  })
    .error(
    	function(data,status,headers, config) {
    		$scope.saveErrorOccured=true;
    		$scope.errors.push("Fel inträffade på servern när matningen skulle sparas");
    	}
    );
  };

  $scope.deleteFeeding = function(feedingToDelete) {
    var deletionConfirmed = confirm("Vill du ta bort matningen?");
   if(deletionConfirmed) {
      $http.delete('/api/v1/food/feeding/' + feedingToDelete.id)
        .success(
          function(data, status, headers, config) {
            // Remove the deleted row from the table
            var index = $scope.meals.indexOf(feedingToDelete);
            $scope.meals.splice(index, 1);

            // Reduce the sum with the removed amount
            $scope.sumAmount -= feedingToDelete.amount;
          }
        )
        .error(
        function(data,status,headers, config) {
          $scope.saveErrorOccured=true;
          $scope.errors.push("Fel inträffade på servern när matningen skulle tas bort");
        });
    }
  };


});
