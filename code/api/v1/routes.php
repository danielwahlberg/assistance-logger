<?php
/**
 * Definitions of routes used in the application
 */

  $app->foodService = new FoodService();
  $app->medicineService = new MedicineService();
  $app->assistantService = new AssistantService();

  $app->get('/hello/:name', function ($name) {
      echo "Hello, $name";
  });


  $app->post('/login/', function () use ($app) {
    $arrInput = $app->request->getBody();
    $loginResult = SecurityService::login($arrInput['username'], $arrInput['password']);
    echo json_encode($loginResult);
  });


  $app->post('/logout/', function ()  {
      SecurityService::logout();
    });

  $app->get('/assistants/', function() use ($app){
    $data = $app->assistantService->getActiveAssistants();
    echo json_encode($data);
  });

  $app->get('/medicines/:forDate', $authenticateForRole('assistant'), function($forDate) use ($app){
    $data = $app->medicineService->getMedicineList($forDate);
    echo json_encode($data);
  });


  $app->post('/medication/', function() use ($app){
    $arrInput = $app->request()->getBody();
    $app->medicineService->storeMedication($arrInput);
  });

  $app->get('/medicines/whenNeededMedicines/:forDate', function($forDate) use ($app){
    $medicationList = $app->medicineService->getWhenNeededMedicationList($forDate);
    echo json_encode($medicationList);
  });

  /**
   * Retrieve logged medication given because it was needed (not given regularly)
   */
  $app->get('/medicines/whenNeededLog/:forDate', function($forDate) use ($app){
  	$data = $app->medicineService->getLoggedWhenNeededMedication($forDate);
    echo json_encode($data);
  });

  $app->get('/food/foodTypes/', function() use ($app){
    $data = $app->foodService->getFoodTypes();
    echo json_encode($data);
  });

  $app->get('/food/feeding/', $authenticateForRole('admin'),  function() use ($app) {
      $data = $app->foodService->getLoggedFeeding();
      echo json_encode($data);
  });

  $app->post('/food/feeding/', function() use ($app){
    $arrInput = $app->request()->getBody();
    $createdId = $app->foodService->storeFeeding($arrInput);
    echo json_encode($createdId);
  });

  $app->delete('/food/feeding/:id', function($id) use ($app){
    $app->foodService->deleteFeeding($id);
  });
?>
