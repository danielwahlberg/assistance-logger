medicineApp.controller('FoodCtrl', function ($scope, $http, $modal, $log) {

  $http.get('/api/v1/food/feeding').success(function(data){
    $scope.meals = data    
  });

  $http.get('/api/v1/assistants').success(function(data){
    $scope.assistants = data    
  });

  $scope.feedingToStore = {};
  $scope.today = new Date();

  // TODO Get this from API instead
  $scope.foodTypes = [
	{"name": "Mat",
	 "id": "1"
	},
	{"name": "Sondmat",
	 "id": "2"
	},
	{"name": "Dryck",
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
  	if($scope.feedingToStore.assistant == null){
  		$scope.errors.push("Du måste välja assistent innan du sparar");
  		return 1;
  	}
    $http.post('/api/v1/food/feeding', $scope.feedingToStore)
    .success( 
      function(data, status, headers, config) {
      	$scope.allChangesSaved = true;
      	$scope.errors = [];
      	$scope.meals.push( // Dynamically update table with stored feeding
	      	{
	      		"name" : $scope.feedingToStore.foodType.name,
	      		"amount" : $scope.feedingToStore.amount,
	      		"givenBy" : $scope.feedingToStore.assistant.name		
	      	}
      	);
	  })
    .error(
    	function(data,status,headers, config) {
    		$scope.saveErrorOccured=true;
    		$scope.errors.push("Fel inträffade på servern när matningen skulle sparas");
    	}
    );
  }


});