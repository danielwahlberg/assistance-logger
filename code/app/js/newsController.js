medicineApp.controller('ViewNewsCtrl', function ($scope, $http, $modal, $log) {

  $http.get('/api/v1/news/list').success(function(data){
    $scope.news = data
  });

});

medicineApp.controller('AdminNewsCtrl', function ($scope, $http, $modal, $log) {

  $scope.newEntry; 
  $scope.allChangesSaved = false;
  $scope.saveDisabled = true;

  $http.get('/api/v1/news/list/all').success(function(data){
    $scope.news = data
  });

  $scope.isSaveDisabled = function(){
    return typeof $scope.newEntry !== 'object'
      || $scope.newEntry.title == "" 
      || $scope.newEntry.body == ""
      || $scope.allChangesSaved;
  };

  $scope.saveNewEntry = function(){
    $http.post('api/v1/news/create/', $scope.newEntry).success(function(){
      $scope.allChangesSaved = true;
    });
  };

  $scope.unpublishEntry = function(newsEntry){
    $http.post('api/v1/news/unpublish/', newsEntry.id).success(function(){
      newsEntry.unpublishedAt = Date.now();
    });
  };

  $scope.republishEntry = function(newsEntry){
    $http.post('api/v1/news/republish/', newsEntry.id).success(function(){
      newsEntry.unpublishedAt = null;
    });
  };

});
