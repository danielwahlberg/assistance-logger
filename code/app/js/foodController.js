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
  $scope.today = new Date();

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
